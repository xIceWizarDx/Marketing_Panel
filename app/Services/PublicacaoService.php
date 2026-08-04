<?php

namespace App\Services;

use App\Enums\StatusDestino;
use App\Enums\StatusPublicacao;
use App\Exceptions\TransicaoInvalida;
use App\Models\Destino;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Models\Tentativa;
use App\Support\ContextoDoUsuario;
use App\Support\Midia\LiberacaoDeArquivo;
use App\Support\RegistroDeSeguranca;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ESCRITOR ÚNICO da máquina de estados.
 *
 * Nenhum controller, job ou model muda `destinos.status` ou `publicacoes.status`
 * por conta própria. Toda mudança passa por um método nomeado daqui, que:
 *   1. confere se a transição é permitida (senão lança);
 *   2. aplica junto os campos que aquele estado exige;
 *   3. recalcula o agregado da publicação **só em estado terminal**.
 *
 * Sem esse funil, a máquina de estados vira decoração: basta um
 * `update(['status' => 'publicado'])` esquecido num controller para o painel
 * mentir — que é justamente o defeito que o produto vende como diferencial.
 */
class PublicacaoService
{
    /** Quantas vezes o motor tenta antes de desistir. */
    public const MAX_TENTATIVAS = 3;

    /**
     * Marca o disparo: a partir daqui a publicacao deixa de ser rascunho.
     *
     * `enviada_em` e a fonte unica desse fato — sem ele, uma publicacao com
     * destinos ja enviando continuaria aparecendo como rascunho na tela.
     */
    public function marcarEnviada(Publicacao $publicacao): void
    {
        $publicacao->forceFill(['enviada_em' => now()])->save();

        $this->recalcularStatus($publicacao->id);
    }

    /** O job pegou o destino e começou a enviar. */
    public function marcarEnviando(Destino $destino): Tentativa
    {
        $this->mover($destino, StatusDestino::Enviando);

        return $destino->tentativas()->create([
            'numero' => $destino->tentativas_feitas + 1,
            'iniciada_em' => now(),
        ]);
    }

    /**
     * A rede ACEITOU o arquivo — mas ainda não confirmou que está no ar.
     *
     * ⭐ DEC-31: é aqui que a maioria das ferramentas erra, marcando "publicado"
     * porque recebeu HTTP 200. TikTok e YouTube moderam depois do aceite.
     */
    public function marcarProcessando(Destino $destino, ?string $identificadorExterno = null): void
    {
        $this->mover($destino, StatusDestino::Processando, [
            'identificador_externo' => $identificadorExterno ?? $destino->identificador_externo,
            'tentativas_feitas' => $destino->tentativas_feitas + 1,
        ]);
    }

    /**
     * Confirmado: relemos o post na rede e ele existe.
     *
     * A URL é obrigatória — é A PROVA. Marcar publicado sem link seria repetir
     * exatamente o que criticamos nos concorrentes.
     */
    public function marcarPublicado(
        Destino $destino,
        string $urlPublicada,
        ?string $identificadorExterno = null,
        ?string $qualidade = null,
    ): void {
        if (trim($urlPublicada) === '') {
            throw new \InvalidArgumentException(
                'Um destino só vira publicado com o link do post — é a prova de entrega (DEC-31).'
            );
        }

        $this->mover($destino, StatusDestino::Publicado, [
            'url_publicada' => $urlPublicada,
            'identificador_externo' => $identificadorExterno ?? $destino->identificador_externo,
            // ⭐ O que a rede DIZ que entregou. Se enviamos 1080 e voltou `sd`,
            // é a plataforma admitindo que degradou (DEC-33).
            'qualidade_entregue' => $qualidade,
            'publicado_em' => now(),
            'erro_mensagem' => null,
        ]);

        $this->fecharTentativaAberta($destino, sucesso: true);
        $this->recalcularStatus($destino->publicacao_id);
    }

    /** Erro final: acabaram as tentativas ou a rede recusou de vez. */
    public function marcarFalha(Destino $destino, string $mensagem, ?string $codigo = null): void
    {
        $this->mover($destino, StatusDestino::Falhou, [
            'erro_mensagem' => $mensagem,
            'tentativas_feitas' => max($destino->tentativas_feitas, 1),
        ]);

        $this->fecharTentativaAberta($destino, sucesso: false, codigo: $codigo, mensagem: $mensagem);
        $this->recalcularStatus($destino->publicacao_id);
    }

    /**
     * Falha transitória (429, timeout, 5xx): volta pra fila.
     *
     * ⚠️ Retry ≠ falha. Aqui NÃO se recalcula o agregado nem se notifica —
     * senão o status oscila na tela e o aviso dispara a cada tentativa.
     */
    public function devolverParaFila(Destino $destino, ?string $motivo = null): void
    {
        $tentativas = $destino->tentativas_feitas + 1;

        if ($tentativas >= self::MAX_TENTATIVAS) {
            $this->marcarFalha(
                $destino,
                $motivo ?? 'A rede não respondeu depois de várias tentativas.',
            );

            return;
        }

        $this->mover($destino, StatusDestino::Pendente, ['tentativas_feitas' => $tentativas]);
        $this->fecharTentativaAberta($destino, sucesso: false, mensagem: $motivo);
    }

    /** Quota diária da rede esgotada — não é erro, é espera (DEC-24). */
    public function aguardarJanela(Destino $destino, string $motivo): void
    {
        $this->mover($destino, StatusDestino::AguardandoJanela, ['erro_mensagem' => $motivo]);
    }

    public function liberarDaJanela(Destino $destino): void
    {
        $this->mover($destino, StatusDestino::Pendente, ['erro_mensagem' => null]);
    }

    /** Reprocessar à mão um destino que falhou. */
    public function reprocessar(Destino $destino): void
    {
        $this->mover($destino, StatusDestino::Pendente, [
            'erro_mensagem' => null,
            'tentativas_feitas' => 0,
        ]);

        $this->recalcularStatus($destino->publicacao_id);
    }

    /**
     * Guarda o handle de retomada ANTES do efeito irreversível.
     *
     * ⚠️ Anti-double-post: se a fila re-entregar o job, o motor retoma por este
     * handle em vez de recomeçar. Sem isso, um timeout de rede publica o mesmo
     * vídeo duas vezes — e não há como desfazer.
     */
    public function guardarHandle(Destino $destino, string $handle): void
    {
        $destino->forceFill(['handle_externo' => $handle])->save();
    }

    /**
     * Recalcula o estado da publicação a partir dos destinos.
     *
     * Roda com lock do registro pai: dois destinos terminando no mesmo instante
     * leriam o agregado pela metade e gravariam resultados diferentes.
     */
    public function recalcularStatus(Publicacao|int $publicacao): StatusPublicacao
    {
        // Aceita o ID de proposito: `$destino->publicacao` dispararia o escopo
        // do dono, e o motor roda no worker, que nao tem sessao. Ler a coluna
        // evita a consulta — e o `withoutGlobalScopes` abaixo e explicito.
        $id = $publicacao instanceof Publicacao ? $publicacao->id : $publicacao;

        return DB::transaction(function () use ($id) {
            $publicacao = Publicacao::withoutGlobalScopes()->lockForUpdate()->findOrFail($id);

            $contagem = Destino::where('publicacao_id', $publicacao->id)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $novo = $this->deduzirStatus($contagem, $publicacao->enviada_em !== null);

            if ($novo !== $publicacao->status) {
                $publicacao->forceFill(['status' => $novo])->save();
            }

            if ($novo->ehTerminal()) {
                $this->liberarArquivo($publicacao);
            }

            return $novo;
        });
    }

    /**
     * ⭐ Terminou de publicar? o vídeo sai. Agora, não amanhã.
     *
     * O produto não guarda acervo (DEC-59). O arquivo existia para subir; subiu,
     * acabou a função. Esperar o comando diário guardaria o vídeo por até 24
     * horas sem motivo nenhum.
     *
     * ⛔ **O registro fica.** Some só o arquivo pesado; miniatura, laudo, links e
     * prova continuam — é o histórico que sustenta o produto.
     *
     * Nunca lança: falhar ao apagar não pode desfazer uma publicação que deu
     * certo. O comando diário é a rede de segurança para o que escapar aqui.
     */
    private function liberarArquivo(Publicacao $publicacao): void
    {
        try {
            $midia = Midia::withoutGlobalScopes()->find($publicacao->midia_id);

            if (! $midia || ! $midia->temArquivo()) {
                return;
            }

            // Uma mídia pode servir a mais de uma publicação: só sai quando
            // NENHUMA delas ainda precisa dela.
            if (LiberacaoDeArquivo::em($midia)?->isFuture() ?? true) {
                return;
            }

            Storage::disk(config('midia.disco'))->delete($midia->caminho);
            $midia->forceFill(['arquivo_removido_em' => now()])->save();
        } catch (\Throwable $erro) {
            Log::warning('Não consegui liberar o arquivo depois de publicar', [
                'publicacao' => $publicacao->ulid,
                'motivo' => $erro->getMessage(),
            ]);
        }
    }

    /** @param Collection<string, int> $contagem */
    private function deduzirStatus($contagem, bool $foiEnviada): StatusPublicacao
    {
        // Rascunho e "nunca foi enviada" — decidido por `enviada_em`, nao pelo
        // status anterior. Deduzir do status anterior fazia uma publicacao com
        // destinos ja enviando continuar aparecendo como rascunho na tela.
        if (! $foiEnviada || $contagem->sum() === 0) {
            return StatusPublicacao::Rascunho;
        }

        $total = $contagem->sum();
        $publicados = (int) $contagem->get(StatusDestino::Publicado->value, 0);
        $falhados = (int) $contagem->get(StatusDestino::Falhou->value, 0);

        // Ainda há destino em andamento: a publicação não terminou, ponto.
        if ($publicados + $falhados < $total) {
            return StatusPublicacao::Processando;
        }

        return match (true) {
            $falhados === 0 => StatusPublicacao::Concluida,
            $publicados === 0 => StatusPublicacao::Falhou,
            default => StatusPublicacao::ConcluidaComFalhas,
        };
    }

    /**
     * O único ponto que escreve `destinos.status`.
     *
     * @param  array<string, mixed>  $camposExtras
     */
    private function mover(Destino $destino, StatusDestino $novo, array $camposExtras = []): void
    {
        $atual = $destino->status;

        // Sem atalho de "mesmo estado = no-op": o servico e estrito de proposito.
        // Idempotencia (job re-entregue sobre destino ja publicado) e decidida
        // pelo JOB, que confere o estado antes de agir — se fosse silenciada
        // aqui, um envio duplicado passaria despercebido.
        if (! $atual->podeIrPara($novo)) {
            throw TransicaoInvalida::de($atual, $novo);
        }

        $destino->forceFill(['status' => $novo, ...$camposExtras])->save();

        if ($novo->ehTerminal()) {
            RegistroDeSeguranca::registrar('destino_'.$novo->value, [
                'destino_ulid' => $destino->ulid,
                // `semEscopo` explícito: o motor roda no worker, que não tem
                // sessão. Ler a conta pelo escopo do usuário estouraria aqui —
                // e o registro de segurança é concern da plataforma, não do dono.
                'plataforma' => ContextoDoUsuario::semEscopo(
                    fn () => $destino->contaSocial?->plataforma->value
                ),
            ]);
        }
    }

    private function fecharTentativaAberta(
        Destino $destino,
        bool $sucesso,
        ?string $codigo = null,
        ?string $mensagem = null,
    ): void {
        $destino->tentativas()
            ->whereNull('finalizada_em')
            ->latest('id')
            ->first()
            ?->forceFill([
                'finalizada_em' => now(),
                'sucesso' => $sucesso,
                'codigo_resposta' => $codigo,
                'erro_mensagem' => $mensagem,
            ])->save();
    }
}

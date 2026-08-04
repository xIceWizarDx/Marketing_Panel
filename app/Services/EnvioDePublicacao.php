<?php

namespace App\Services;

use App\Enums\StatusDestino;
use App\Jobs\PublicarDestinoJob;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Support\Midia\EspecificacaoDaRede;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Monta a publicação e dispara o motor.
 *
 * Separado do `PublicacaoService` de propósito: aqui vive a decisão de negócio
 * (*o que enviar, para onde*); lá vive a máquina de estados (*em que estado o
 * destino está*). Misturar os dois faria a regra de negócio poder mexer em
 * status, que é justamente o que a máquina existe para impedir.
 */
class EnvioDePublicacao
{
    /**
     * @param  list<string>  $contasUlid
     * @param  list<string>  $hashtags
     *
     * @throws ValidationException
     */
    public function enviar(
        string $midiaUlid,
        array $contasUlid,
        ?string $titulo = null,
        ?string $legenda = null,
        array $hashtags = [],
    ): Publicacao {
        // `firstOrFail` sob o escopo do dono: mídia ou conta alheia dá 404.
        $midia = Midia::where('ulid', $midiaUlid)->firstOrFail();

        $contas = ContaSocial::whereIn('ulid', $contasUlid)->get();

        if ($contas->isEmpty()) {
            throw ValidationException::withMessages([
                'contas' => 'Escolha pelo menos uma conta para publicar.',
            ]);
        }

        $this->recusarContaQueNaoVoltou($contas, $contasUlid);
        $grupoId = $this->recusarGruposMisturados($contas);
        $this->recusarSemArquivo($midia);
        $this->recusarContasSemConexao($contas);
        $this->recusarTextoLongoDemais($contas, $titulo, $legenda, $hashtags);
        $this->recusarFormatoIncompativel($contas, $midia);

        $publicacao = DB::transaction(function () use ($midia, $contas, $grupoId, $titulo, $legenda, $hashtags) {
            $publicacao = Publicacao::create([
                // ⭐ Gravado, não deduzido: mover um canal de grupo depois não
                // pode mudar retroativamente onde este post saiu (DEC-75).
                'grupo_id' => $grupoId,
                'midia_id' => $midia->id,
                'titulo' => $titulo,
                'legenda' => $legenda,
                'hashtags' => array_values(array_filter($hashtags)),
            ]);

            foreach ($contas as $conta) {
                Destino::create([
                    'publicacao_id' => $publicacao->id,
                    'conta_social_id' => $conta->id,
                ]);
            }

            return $publicacao;
        });

        // `marcarEnviada` ANTES de despachar: se a fila rodar primeiro, o
        // agregado ainda diria "rascunho" enquanto os destinos já foram.
        app(PublicacaoService::class)->marcarEnviada($publicacao);

        foreach ($publicacao->destinos as $destino) {
            PublicarDestinoJob::dispatch($destino->id);
        }

        return $publicacao;
    }

    public function reprocessarDestino(string $ulid): Destino
    {
        $destino = Destino::whereHas(
            'publicacao',
            fn ($consulta) => $consulta // o escopo do dono já filtra a publicação
        )->where('ulid', $ulid)->firstOrFail();

        if ($destino->status !== StatusDestino::Falhou) {
            throw ValidationException::withMessages([
                'destino' => 'Só dá para tentar de novo o que falhou.',
            ]);
        }

        app(PublicacaoService::class)->reprocessar($destino);

        PublicarDestinoJob::dispatch($destino->id);

        return $destino->fresh();
    }

    /**
     * ⛔ Recusa quando alguma conta pedida não voltou da consulta.
     *
     * ⚠️ Antes elas sumiam **em silêncio**: o `whereIn` sob o escopo do dono
     * descartava a conta alheia e a publicação seguia com as que sobraram. A
     * pessoa recebia "enviamos para 3 contas" tendo escolhido 4.
     *
     * Com grupo isso ficou intolerável: filtrar calado é exatamente a
     * implementação errada da DEC-73 — a que deixa o servidor "consertar" a
     * escolha em vez de recusá-la.
     *
     * @param  Collection<int, ContaSocial>  $contas
     * @param  list<string>  $contasUlid
     *
     * @throws ValidationException
     */
    private function recusarContaQueNaoVoltou($contas, array $contasUlid): void
    {
        if ($contas->count() === count(array_unique($contasUlid))) {
            return;
        }

        throw ValidationException::withMessages([
            'contas' => 'Uma das contas escolhidas não está mais disponível. Atualize a página e escolha de novo.',
        ]);
    }

    /**
     * ⭐ **A trava da feature.** Todas as contas têm que ser do mesmo grupo.
     *
     * A sessão só decide o que a tela mostra; a verdade sobre onde o post sai
     * vem daqui, das contas escolhidas (DEC-73). É isto que torna impossível
     * uma aba velha, um POST montado à mão ou um defeito de interface publicar
     * novela no canal de notícias — e publicação não tem desfazer.
     *
     * ⚠️ Compara a **coluna** `grupo_id`, nunca `$conta->grupo?->id`: grupo
     * arquivado faz a relação devolver `null`, e dois `null` passariam por "o
     * mesmo grupo".
     *
     * @param  Collection<int, ContaSocial>  $contas
     * @return int o grupo único das contas
     *
     * @throws ValidationException
     */
    private function recusarGruposMisturados($contas): int
    {
        $grupos = $contas->pluck('grupo_id')->unique();

        if ($grupos->count() > 1) {
            throw ValidationException::withMessages([
                'contas' => 'Você escolheu canais de grupos diferentes. '.
                    'Uma publicação sai de um grupo só — envie uma para cada.',
            ]);
        }

        $grupoId = $grupos->first();

        // Conta sem grupo é dado incompleto, não "grupo nenhum": publicar assim
        // criaria um post que não aparece em lista nenhuma.
        if ($grupoId === null) {
            throw ValidationException::withMessages([
                'contas' => 'Este canal ainda não está em nenhum grupo. Atualize a página e tente de novo.',
            ]);
        }

        return $grupoId;
    }

    /**
     * ⛔ Sem arquivo não há o que publicar.
     *
     * O vídeo original sai depois de cumprir a função (DEC-54). O registro fica,
     * com laudo, links e prova — mas publicar de novo exige reenviar o arquivo.
     *
     * A tela já mostra a mídia desabilitada; esta é a trava do servidor, porque
     * o que a tela impede é conveniência e o que o serviço impede é regra.
     *
     * @throws ValidationException
     */
    private function recusarSemArquivo(Midia $midia): void
    {
        if ($midia->temArquivo()) {
            return;
        }

        throw ValidationException::withMessages([
            'midia' => 'O arquivo deste vídeo já saiu da nossa guarda. '.
                'Envie ele de novo para publicar — o histórico e as provas continuam aqui.',
        ]);
    }

    private function recusarContasSemConexao($contas): void
    {
        $quebradas = $contas->reject->podePublicar();

        if ($quebradas->isEmpty()) {
            return;
        }

        // Barra ANTES de criar a publicação: deixar passar geraria um destino
        // que já nasce condenado, e a pessoa só descobriria na lista.
        throw ValidationException::withMessages([
            'contas' => 'Reconecte antes de publicar: '.$quebradas->pluck('nome_exibicao')->implode(', ').'.',
        ]);
    }

    /**
     * ⛔ Recusa texto que não cabe — **nunca corta**.
     *
     * A política do YouTube proíbe *"modificar valores fornecidos pelo usuário
     * (truncar, anexar, alterar) sem consentimento explícito"*. E cortar em
     * silêncio é o defeito que o produto critica: a pessoa escreve 120
     * caracteres, saem 100, e ela só descobre olhando o post no ar.
     *
     * @param  Collection<int, ContaSocial>  $contas
     * @param  list<string>  $hashtags
     */
    private function recusarTextoLongoDemais($contas, ?string $titulo, ?string $legenda, array $hashtags): void
    {
        foreach ($contas as $conta) {
            $achados = EspecificacaoDaRede::de($conta->plataforma)
                ->conferirTextos($titulo, $legenda, $hashtags);

            foreach ($achados as $achado) {
                // O nome da rede na mensagem: com várias contas selecionadas, a
                // pessoa precisa saber QUAL delas recusou.
                throw ValidationException::withMessages([
                    'legenda' => "{$conta->plataforma->rotulo()} — {$achado->mensagem} {$achado->providencia}",
                ]);
            }
        }
    }

    /**
     * Recusa formato que a rede não aceita, antes de gastar o upload.
     *
     * @param  Collection<int, ContaSocial>  $contas
     */
    private function recusarFormatoIncompativel($contas, Midia $midia): void
    {
        foreach ($contas as $conta) {
            $achado = EspecificacaoDaRede::de($conta->plataforma)->conferirContainer($midia->mime_type);

            if ($achado !== null) {
                throw ValidationException::withMessages([
                    'midia' => "{$achado->mensagem} {$achado->providencia}",
                ]);
            }
        }
    }
}

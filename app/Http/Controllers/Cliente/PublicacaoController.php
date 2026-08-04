<?php

namespace App\Http\Controllers\Cliente;

use App\Enums\StatusDestino;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\PublicarRequest;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Services\EnvioDePublicacao;
use App\Support\Midia\EspecificacaoDaRede;
use App\Support\Midia\LimiteDeEnvio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PublicacaoController extends Controller
{
    /** @var Collection<string, int>|null */
    private $contasPorRede = null;

    public function __construct(private readonly EnvioDePublicacao $envio) {}

    /** O compositor: escolher mídia, escrever e mandar. */
    /**
     * As abas por estado — ideia do Buffer, e melhor que um filtro solto.
     *
     * ⭐ O número na aba já responde *"tem coisa parada?"* e *"falhou alguma?"*
     * sem ninguém precisar clicar. Um seletor de filtro esconderia justamente a
     * informação que a pessoa abriu a tela para ver.
     */
    private const ABAS = ['tudo', 'andando', 'no_ar', 'falharam'];

    public function listar(Request $request): Response
    {
        $aba = in_array($request->query('aba'), self::ABAS, true)
            ? $request->query('aba')
            : 'tudo';

        $publicacoes = Publicacao::query()
            // ⚠️ `ulid`, `miniatura` e `arquivo_removido_em` fazem parte da
            // seleção: com strict mode ligado, ler coluna fora da lista estoura
            // 500 — e só em produção, que é onde ele costuma estar ligado.
            ->with([
                'midia:id,ulid,nome_original,tipo,miniatura,arquivo_removido_em',
                'destinos.contaSocial:id,nome_exibicao,plataforma',
            ])
            ->when($aba !== 'tudo', fn ($q) => $q->whereHas('destinos', $this->filtroDaAba($aba)))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through($this->paraTela(...));

        return Inertia::render('cliente/publicacoes', [
            'publicacoes' => $publicacoes,
            'aba' => $aba,
            'contagem' => $this->contagemPorAba(),
            // ⭐ O compositor abre POR CIMA da lista, por uma rota de verdade.
            // Vazio aqui; preenchido quando a rota é `/publicar` (ver `compor`).
            'compositor' => null,
        ]);
    }

    public function enviar(PublicarRequest $request): RedirectResponse
    {
        $publicacao = $this->envio->enviar(
            midiaUlid: $request->string('midia')->toString(),
            contasUlid: $request->input('contas', []),
            titulo: $request->string('titulo')->toString() ?: null,
            legenda: $request->string('legenda')->toString() ?: null,
            hashtags: $request->input('hashtags', []),
        );

        return to_route('publicacoes')->with(
            'sucesso',
            'Enviamos para '.$publicacao->destinos->count().' conta(s). '.
                'O link aparece aqui assim que confirmarmos na própria rede.'
        );
    }

    /**
     * ⭐ Publicar deixou de ser tela e virou AÇÃO, aberta por cima da lista.
     *
     * ⚠️ Mesmo sendo modal, é uma **rota de verdade**: atualizar a página reabre
     * no mesmo ponto e o botão voltar fecha o compositor em vez de sair do
     * painel. Modal guardado só em memória perderia o texto escrito à mão — que
     * é justamente o defeito U-9 do estudo de usabilidade.
     *
     * @param  string|null  $publicacao  ULID de onde copiar, ao republicar
     */
    public function compor(Request $request, ?string $publicacao = null): Response
    {
        $resposta = $this->listar($request);

        return $resposta->with('compositor', [
            /*
             * ⭐ Os limites de cada rede, de UMA fonte só.
             *
             * A tela dizia sempre "o Bluesky aceita 300", mesmo publicando só no
             * YouTube (que aceita 5.000), e o campo de título deixava digitar 255
             * quando o YouTube corta em 100 — a pessoa escrevia tudo e levava
             * erro depois de enviar.
             */
            'limites' => $this->limitesPorRede(),
            // O que o servidor aguenta de fato, nunca o que o produto gostaria:
            // prometer 300 MB numa maquina que corta em 2 seria mentir.
            'tamanhoMaximoMb' => LimiteDeEnvio::megabytes(),
            'contas' => ContaSocial::query()->get()->map(fn (ContaSocial $c) => [
                'ulid' => $c->ulid,
                'nome' => $c->nome_exibicao,
                'plataforma' => $c->plataforma->value,
                'plataformaRotulo' => $c->plataforma->rotulo(),
                'podePublicar' => $c->podePublicar(),
                'statusRotulo' => $c->status->rotulo(),
            ]),

            /*
             * ⛔ O compositor NÃO sugere nada (DEC-60): não existe lista de
             * vídeos anteriores porque não existe acervo.
             *
             * O único arquivo que aparece é o que acabou de ser enviado, nesta
             * mesma composição — e ele chega por flash, não por consulta ao
             * banco. Recarregar a página volta a mostrar só a área de envio.
             */
            'midiaEnviada' => $this->recemEnviada($request),

            'inicial' => $publicacao ? $this->paraRepublicar($publicacao) : null,
        ]);
    }

    /**
     * O arquivo que acabou de ser enviado — ou `null`, que é o normal.
     *
     * ⚠️ Sai da sessão, nunca de uma busca no banco: buscar "a última mídia do
     * usuário" transformaria o compositor num acervo pela porta dos fundos.
     */
    private function recemEnviada(Request $request): ?array
    {
        $ulid = $request->session()->get('midiaEnviada');

        if (! $ulid) {
            return null;
        }

        $midia = Midia::where('ulid', $ulid)->first();

        if (! $midia || ! $midia->temArquivo()) {
            return null;
        }

        return [
            'ulid' => $midia->ulid,
            'nome' => $midia->nome_original,
            'tipo' => $midia->tipo->value,
            'miniatura' => $midia->miniatura ? route('midias.miniatura', $midia->ulid) : null,
            // Pedido só quando alguém manda tocar a prévia.
            'arquivo' => route('midias.arquivo', $midia->ulid),
            'duracao' => $midia->duracaoLegivel(),
            'tamanho' => $midia->tamanhoLegivel(),
            'vertical' => $midia->ehVertical(),
            'laudo' => $midia->laudo,
        ];
    }

    /**
     * O que copiar de uma publicação anterior — **só o texto** (DEC-61).
     *
     * ⚠️ O vídeo **não** vem junto: ele saiu no instante em que a publicação
     * terminou (DEC-59). É o preço honesto de não guardar acervo — e a
     * assinatura do conteúdo (DEC-58) devolve o arquivo ao mesmo registro
     * quando a pessoa reenviar.
     *
     * As contas onde já subiu vêm **desmarcadas**, só listadas como aviso.
     * Marcar sozinho repetiria o post — e publicação não tem desfazer.
     *
     * @return array<string, mixed>|null
     */
    private function paraRepublicar(string $ulid): ?array
    {
        $anterior = Publicacao::with('destinos.contaSocial:id,ulid')
            ->where('ulid', $ulid)
            ->first();

        if (! $anterior) {
            return null;
        }

        return [
            'titulo' => (string) $anterior->titulo,
            'legenda' => (string) $anterior->legenda,
            'hashtags' => $anterior->hashtags ?? [],
            'jaPublicadoEm' => $anterior->destinos
                ->map(fn ($d) => $d->contaSocial->ulid)
                ->unique()
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, array{titulo: ?int, legenda: ?int, medidaDaLegenda: string}>
     */
    private function limitesPorRede(): array
    {
        $limites = [];

        foreach (EspecificacaoDaRede::todas() as $spec) {
            $limites[$spec->plataforma->value] = [
                'titulo' => $spec->texto->titulo,
                'legenda' => $spec->texto->legenda,
                // ⚠️ Como a rede CONTA, não só quanto aceita. O Bluesky conta
                // grafema (emoji de família = 1); o YouTube conta byte na
                // descrição. Contar errado recusa texto que caberia.
                'medidaDaLegenda' => $spec->texto->medidaDaLegenda->value,
            ];
        }

        return $limites;
    }

    /** O recorte de destinos que faz uma publicação pertencer à aba. */
    private function filtroDaAba(string $aba): callable
    {
        return match ($aba) {
            'no_ar' => fn ($q) => $q->where('status', StatusDestino::Publicado),
            'falharam' => fn ($q) => $q->where('status', StatusDestino::Falhou),
            // Nem publicado, nem falhado — por exclusão, de propósito.
            default => fn ($q) => $q->whereNotIn('status', [
                StatusDestino::Publicado->value,
                StatusDestino::Falhou->value,
            ]),
        };
    }

    /**
     * Quantas publicações caem em cada aba.
     *
     * Conta PUBLICAÇÕES, não destinos: uma publicação com um destino no ar e
     * outro falhado aparece nas duas abas, porque nas duas ela tem algo a dizer.
     *
     * @return array<string, int>
     */
    private function contagemPorAba(): array
    {
        $contagem = ['tudo' => Publicacao::query()->count()];

        foreach (['andando', 'no_ar', 'falharam'] as $aba) {
            $contagem[$aba] = Publicacao::query()
                ->whereHas('destinos', $this->filtroDaAba($aba))
                ->count();
        }

        return $contagem;
    }

    /** Tentar de novo um destino que falhou. */
    public function reprocessar(string $ulid): RedirectResponse
    {
        $destino = $this->envio->reprocessarDestino($ulid);

        return back()->with(
            'sucesso',
            "Tentando de novo em {$destino->contaSocial->nome_exibicao}."
        );
    }

    /**
     * Quantas contas o cliente tem em cada rede.
     *
     * Memorizado: a lista chama isto por destino, e sem cache seria uma consulta
     * por linha da tela.
     *
     * @return Collection<string, int>
     */
    private function contasPorRede()
    {
        return $this->contasPorRede ??= ContaSocial::query()
            ->groupBy('plataforma')
            ->pluck(DB::raw('count(*)'), 'plataforma');
    }

    private function paraTela(Publicacao $publicacao): array
    {
        return [
            'ulid' => $publicacao->ulid,
            'titulo' => $publicacao->titulo,
            'legenda' => $publicacao->legenda,
            'status' => $publicacao->status->value,
            'statusRotulo' => $publicacao->status->rotulo(),
            'midia' => $publicacao->midia->nome_original,
            // A miniatura vale em toda tela onde se reconhece um vídeo, não só
            // na lista — o bundle.social tem coluna própria para ela.
            'miniatura' => $publicacao->midia->miniatura
                ? route('midias.miniatura', $publicacao->midia->ulid)
                : null,
            'criadaEm' => $publicacao->created_at?->toIso8601String(),
            // ⚠️ Sempre pode: republicar reaproveita o TEXTO, não o arquivo
            // (DEC-61). O vídeo é reenviado no compositor.
            'podeRepublicar' => true,
            'destinos' => $publicacao->destinos->map(fn (Destino $d) => [
                'ulid' => $d->ulid,
                /*
                 * ⚠️ O nome da conta só vem quando ele **desambigua**.
                 *
                 * Com um canal só no YouTube, repetir o nome do dono em cada
                 * linha do painel dele não informa nada — ele sabe de quem é a
                 * conta. Com dois canais, saber em qual subiu é o que importa.
                 *
                 * Regra: mostra quando há mais de uma conta naquela rede.
                 */
                'conta' => $this->contasPorRede()->get($d->contaSocial->plataforma->value, 0) > 1
                    ? $d->contaSocial->nome_exibicao
                    : null,
                'plataforma' => $d->contaSocial->plataforma->value,
                'plataformaRotulo' => $d->contaSocial->plataforma->rotulo(),
                'status' => $d->status->value,
                'statusRotulo' => $d->status->rotulo(),
                // ⭐ A PROVA (DEC-31): o link só existe depois que relemos o post.
                'url' => $d->url_publicada,
                // ⭐ `sd` quando enviamos vertical 1080 = a rede admitindo que
                // degradou. Nenhum concorrente mostra isso.
                'qualidade' => $d->qualidade_entregue,
                'erro' => $d->erro_mensagem,
                'podeReprocessar' => $d->status === StatusDestino::Falhou,
            ])->all(),
        ];
    }
}

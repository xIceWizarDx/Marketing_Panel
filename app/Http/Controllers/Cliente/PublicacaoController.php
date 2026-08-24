<?php

namespace App\Http\Controllers\Cliente;

use App\Enums\Plataforma;
use App\Enums\StatusDestino;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\PublicarRequest;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Publicadores\RegistroDePublicadores;
use App\Services\EnvioDePublicacao;
use App\Support\AlcanceSomado;
use App\Support\CustoDaPublicacao;
use App\Support\DataEmPalavras;
use App\Support\GrupoCorrente;
use App\Support\MediaPorRede;
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
            // ⭐ Pelo grupo GRAVADO na publicação (DEC-75): a lista de Notícias
            // não pode mudar no dia em que um canal for para outro grupo.
            ->where('grupo_id', GrupoCorrente::id())
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
            /*
             * ⭐ O total, com as ressalvas que o tornam honesto (DEC-146).
             *
             * ⚠️ Ele responde "estou crescendo?" e NADA mais — a comparação
             * entre redes é o `comparativo`, ao lado, cada uma na medida dela.
             */
            'alcance' => AlcanceSomado::doDono(app(RegistroDePublicadores::class))->paraTela(),
            'comparativo' => $this->comparativo(),
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
            // ⭐ Só os canais deste grupo. É o que torna o acidente impossível
            // de cometer, e não só improvável (DEC-71).
            'contas' => ContaSocial::query()
                ->where('grupo_id', GrupoCorrente::id())
                ->get()->map(fn (ContaSocial $c) => [
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

            /*
             * ⭐ **As hashtags que este grupo já traz escritas** (DEC-152).
             *
             * ⚠️ Ponto de partida, nunca carimbo: o campo continua editável, e
             * o que sobe é o que estiver escrito na hora de publicar. Elas moram
             * no grupo porque é ele que separa linhas de conteúdo (DEC-69).
             */
            'hashtagsPadrao' => GrupoCorrente::grupo()?->hashtags ?? [],

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
     * @return array<string, array{titulo: ?int, legenda: ?int, medidaDaLegenda: string, tituloEntraNaLegenda: bool, avisoDeLink: ?string}>
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
                /*
                 * ⛔ Rede sem campo de título soma os dois no mesmo orçamento
                 * (Threads, TikTok). Sem isto, o contador da tela diria que
                 * cabe e o servidor recusaria em seguida — duas verdades
                 * diferentes para o mesmo texto.
                 */
                'tituloEntraNaLegenda' => $spec->texto->tituloEntraNaLegenda,
                /*
                 * ⛔ **Rede que EXIGE título** (DEC-166) — o botão avisa antes
                 * do clique, em vez de a publicação falhar lá na frente.
                 */
                'tituloObrigatorio' => $spec->texto->tituloObrigatorio,
                /*
                 * ⛔ O aviso de que publicar nesta rede custa mais por causa do
                 * link (DEC-126) — ou `null`, que é o caso de todas menos uma.
                 *
                 * ⚠️ A frase vem PRONTA do servidor: os preços não podem existir
                 * escritos em dois idiomas, porque no dia em que o X mudar a
                 * tabela uma das cópias fica errada — e é a errada que a pessoa
                 * vai ler.
                 */
                'avisoDeLink' => $spec->plataforma->value === CustoDaPublicacao::REDE_QUE_COBRA
                    ? CustoDaPublicacao::fraseDoLink()
                    : null,
            ];
        }

        return $limites;
    }

    /** O recorte de destinos que faz uma publicação pertencer à aba. */
    private function filtroDaAba(string $aba): callable
    {
        return match ($aba) {
            'no_ar' => fn ($q) => $q->where('status', StatusDestino::Publicado),
            /*
             * ⚠️ `Removido` entra aqui junto com `Falhou`: nos dois casos há algo
             * a resolver, e é esta a aba que a pessoa abre para resolver.
             *
             * ⛔ O que NÃO se mistura é a frase — "falhou" é não subiu; "saiu do
             * ar" é subiu e a rede tirou (DEC-148). A aba junta o que precisa de
             * atenção; o cartão diz qual dos dois é.
             */
            'falharam' => fn ($q) => $q->whereIn('status', [
                StatusDestino::Falhou->value,
                StatusDestino::Removido->value,
            ]),
            // Nada que já terminou — por exclusão, de propósito: estado novo
            // aparece como "andando" em vez de sumir da tela.
            default => fn ($q) => $q->whereNotIn('status', [
                StatusDestino::Publicado->value,
                StatusDestino::Falhou->value,
                StatusDestino::Removido->value,
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
    /**
     * ⭐ **Um gráfico por REDE, e cada rede na medida dela** (DEC-94).
     *
     * O YouTube compara os posts por visualização; o Bluesky **não tem**
     * visualização e compara por curtida. Uma tabela com coluna igual para as
     * duas obrigaria a inventar um valor para a célula que não existe — e é aí
     * que o painel começa a mentir.
     *
     * ⚠️ **Sai da lista inteira do grupo, não da página aberta.** Comparar só os
     * quinze da página faria o gráfico mudar de conclusão ao virar a página.
     *
     * ⛔ **Comparação exige pelo menos dois.** Um post sozinho vira uma barra de
     * 100% ao lado de nada, que não informa — informa que existe um post, e isso
     * a lista já diz.
     *
     * @return list<array<string, mixed>>
     */
    private function comparativo(): array
    {
        /** @var Collection<int, Destino> $destinos */
        $destinos = Destino::query()
            ->whereNotNull('metricas_lidas_em')
            ->where('status', StatusDestino::Publicado)
            ->whereIn('publicacao_id', Publicacao::query()
                ->where('grupo_id', GrupoCorrente::id())
                ->select('id'))
            ->with(['contaSocial:id,plataforma', 'publicacao:id,titulo,midia_id', 'publicacao.midia:id,nome_original'])
            ->get();

        $porRede = [];

        foreach ($destinos->groupBy(fn (Destino $d) => $d->contaSocial->plataforma->value) as $rede => $daRede) {
            $plataforma = Plataforma::from((string) $rede);
            $medida = $plataforma->metricaDeComparacao();

            if (! $medida) {
                continue;
            }

            $barras = $daRede
                ->filter(fn (Destino $d) => $d->{$medida} !== null)
                ->sortByDesc(fn (Destino $d) => $d->{$medida})
                // ⚠️ Teto de 8: acima disso a barra mais baixa vira um fio e a
                // comparação para de ser legível. O que fica de fora é sempre o
                // menor, e a lista completa está logo abaixo.
                ->take(8)
                ->map(fn (Destino $d) => [
                    'ulid' => $d->ulid,
                    'titulo' => $d->publicacao->titulo ?: $d->publicacao->midia?->nome_original ?: 'sem título',
                    'valor' => (int) $d->{$medida},
                    'url' => $d->url_publicada,
                ])
                ->values();

            if ($barras->count() < 2) {
                continue;
            }

            $porRede[] = [
                'rede' => $plataforma->value,
                'redeRotulo' => $plataforma->rotulo(),
                'medida' => __("rotulos.metrica.{$medida}"),
                'barras' => $barras,
                /*
                 * ⭐ **Zero em tudo é um estado, não um gráfico vazio.**
                 *
                 * No YouTube isso é o esperado hoje: enquanto a auditoria não
                 * passa, todo vídeo sobe privado, e vídeo privado tem zero
                 * visualização de verdade. Sem esta frase, a tela pareceria
                 * quebrada justamente quando está certa.
                 */
                'tudoZerado' => $barras->max('valor') === 0,
                'notaDeZero' => $plataforma === Plataforma::Youtube
                    ? 'Enquanto o aplicativo não passar pela auditoria do YouTube, todo vídeo sobe privado — e vídeo privado não recebe visualização. O número enche sozinho quando a aprovação sair.'
                    : null,
            ];
        }

        return $porRede;
    }

    private function contagemPorAba(): array
    {
        // ⚠️ MESMO filtro da lista. Contagem sem grupo faria a aba dizer 7 e a
        // lista mostrar 3 — e aí nenhum dos dois números é confiável.
        $doGrupo = fn () => Publicacao::query()->where('grupo_id', GrupoCorrente::id());

        $contagem = ['tudo' => $doGrupo()->count()];

        foreach (['andando', 'no_ar', 'falharam'] as $aba) {
            $contagem[$aba] = $doGrupo()
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
            ->where('grupo_id', GrupoCorrente::id())
            ->groupBy('plataforma')
            ->pluck(DB::raw('count(*)'), 'plataforma');
    }

    private function paraTela(Publicacao $publicacao): array
    {
        // ⚠️ Uma vez por requisição, não por destino: são N posts na tela.
        $medias = MediaPorRede::doDono();

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
                /*
                 * ⛔ **E quando a rede NÃO deixa reler, a tela diz isso**
                 * (DEC-106). O LinkedIn exige `r_member_social` para ler um
                 * post, e ela é restrita a aprovados.
                 *
                 * ⚠️ Mostrar o link com a mesma cara das outras redes seria
                 * afirmar uma conferência que não aconteceu.
                 */
                'notaDaProva' => $d->contaSocial->plataforma->notaDaProva(),
                // ⭐ `sd` quando enviamos vertical 1080 = a rede admitindo que
                // degradou. Nenhum concorrente mostra isso.
                'qualidade' => $d->qualidade_entregue,
                'erro' => $d->erro_mensagem,
                'podeReprocessar' => $d->status === StatusDestino::Falhou,
                /*
                 * ⭐ O contador ao lado da prova — e **só o que aquela rede
                 * publica** (DEC-94).
                 *
                 * ⚠️ Cada campo é `int` **ou `null`**, e `null` nunca vira zero
                 * na tela (DEC-95). No Bluesky visualização não existe no
                 * protocolo: `0` ali diria "ninguém viu", quando o certo é
                 * "ninguém conta". A frase que explica isso vem em `nota`.
                 */
                'visualizacoes' => $d->visualizacoes,
                'curtidas' => $d->curtidas,
                'comentarios' => $d->comentarios,
                'compartilhamentos' => $d->compartilhamentos,
                'metricasLidas' => DataEmPalavras::leitura($d->metricas_lidas_em),
                /*
                 * ⭐ **Quando conferimos pela última vez que continua no ar**
                 * (DEC-145).
                 *
                 * ⚠️ "No ar" sem data é afirmação sem prazo — e afirmação sem
                 * prazo envelhece em silêncio. Era exatamente o buraco que a
                 * reconferência veio tapar, e sem esta linha ela ficaria
                 * invisível para quem usa.
                 */
                'conferidoEm' => DataEmPalavras::leitura($d->reconferido_em),
                /*
                 * ⭐ **A comparação que resolve o problema das unidades**
                 * (DEC-147): este post contra a MÉDIA da própria rede.
                 *
                 * ⛔ Comparar 900 visualizações do YouTube com 900 do TikTok é
                 * comparar réguas diferentes (DEC-146). Comparar um post do
                 * TikTok com a média dos seus posts no TikTok **não tem esse
                 * problema** — é a mesma régua dos dois lados.
                 *
                 * ⚠️ `null` quando não há base suficiente, e a tela cala em vez
                 * de afirmar tendência com dois pontos.
                 */
                'contraMedia' => $medias->comparar($d),
                'notaDeMetrica' => $d->contaSocial->plataforma->notaDoPost(),
            ])->all(),
        ];
    }
}

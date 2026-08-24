<?php

namespace App\Http\Controllers\Cliente;

use App\Enums\StatusConta;
use App\Http\Controllers\Controller;
use App\Models\ContaSocial;
use App\Models\Grupo;
use App\Models\Publicacao;
use App\Support\Conexao\ResumoDasRedes;
use App\Support\GrupoCorrente;
use App\Support\ResumoDoPainel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A porta de entrada — e a única pergunta que ela responde.
 *
 * ⚠️ Antes esta tela era um texto fixo dizendo *"vamos começar conectando uma
 * rede"*, sem controller nenhum. Do segundo dia em diante ela era a única página
 * que não sabia de nada, e passava a impressão de que o sistema não tinha
 * registrado nada do que a pessoa fez.
 *
 * Quem abre o painel quer saber **o que aconteceu enquanto eu não estava
 * olhando**. Tudo aqui responde a isso, ou não deveria estar aqui.
 *
 * ⛔ **Publicação não mora aqui** (DEC-68). Uma prévia das últimas era a mesma
 * lista de Publicações com outra moldura — e lista duplicada envelhece: um dia
 * uma mostra o que a outra não mostra, e nenhuma das duas é confiável. Aqui
 * ficam os **números** delas, que é coisa diferente.
 */
class VisaoGeralController extends Controller
{
    public function __construct(
        private readonly ResumoDasRedes $redes,
        private readonly ResumoDoPainel $resumo,
    ) {}

    public function __invoke(): Response
    {
        // ⚠️ `refresh_token` entra na seleção porque `venceEmBreve()` pergunta se
        // ele EXISTE. Fora da lista, com strict mode ligado, isso estoura 500 — e
        // só em produção, que é onde ele costuma estar ligado.
        // ⚠️ TODAS as contas, de todos os grupos: o aviso de saúde ignora o
        // filtro (DEC-80). Conta da outra ponta não pode morrer calada só
        // porque a pessoa está olhando outro grupo.
        $contas = ContaSocial::with(['credencial:id,conta_social_id,expira_em,refresh_token', 'grupo:id,ulid,nome'])->get();
        // ⭐ O total soma TODOS os grupos: esta é a única tela que vê tudo
        // (DEC-88). Antes ela contava só o grupo em foco — com um nome que
        // dizia "geral".
        $numeros = $this->resumo->total();

        return Inertia::render('cliente/visao-geral', [
            'numeros' => $numeros,
            // ⭐ O bloco que justifica abrir o painel: o que está esperando VOCÊ.
            'pendencias' => $this->pendencias($contas, $numeros),
            /*
             * ⭐ Uma entrada por grupo, SEMPRE — inclusive zerada, e na mesma
             * ordem de `grupos.lista`, que é por onde a tela casa nome e marcas.
             *
             * ⛔ Nome, marcas e contagem de redes NÃO vêm aqui: eles já viajam
             * em `grupos.lista` a cada requisição. Mandar de novo é o começo do
             * número diferente para o mesmo fato.
             */
            'resumoDosGrupos' => $this->resumoDosGrupos($contas),
            // ⭐ Conexões deixou de ser tela (DEC-63): o estado das redes mora
            // aqui, com o semáforo à vista. Escondido atrás de um clique, ele
            // vira algo que se descobre depois de já ter perdido a publicação.
            ...$this->redes->montar(),
        ]);
    }

    /**
     * ⭐ O que está esperando você.
     *
     * ⚠️ **Some quando não há nada.** Um bloco que vive dizendo "está tudo bem"
     * treina a pessoa a ignorá-lo — e no dia em que houver problema de verdade,
     * ela não vai olhar. Aviso que aparece sempre não é aviso, é decoração.
     *
     * ⚠️ **Cada aviso diz de QUAL rede se trata.** "A conexão de Fulano está para
     * vencer" nomeia o canal e mais nada — quem lê não sabe onde o problema
     * está, e nome de canal do YouTube costuma ser nome de pessoa, o que faz o
     * aviso parecer outra coisa.
     *
     * @param  Collection<int, ContaSocial>  $contas
     * @param  array{noAr: int, andando: int, falharam: int}  $numeros
     * @return list<array{tom: string, texto: string, acao: string, url: ?string, rede: ?string}>
     */
    private function pendencias($contas, array $numeros): array
    {
        $pendencias = [];

        $pendencias = array_merge($pendencias, $this->avisoDoQueNaoSubiu());

        $vencendo = $contas->filter(fn (ContaSocial $c) => (bool) $c->credencial?->venceEmBreve());

        if ($vencendo->isNotEmpty()) {
            $conta = $vencendo->first();

            $pendencias[] = [
                'tom' => 'atencao',
                'texto' => $vencendo->count() === 1
                    ? "Sua autorização do {$conta->plataforma->rotulo()} está para vencer. ".
                        "Quando vencer, «{$conta->nome_exibicao}»{$this->ondeEsta($conta)} para de publicar."
                    : "{$vencendo->count()} autorizações estão para vencer. Quando vencerem, essas contas param de publicar.",
                'acao' => 'Reconectar',
                'url' => null,
                'grupo' => $this->grupoParaEntrar($vencendo),
                'rede' => $this->redeEmComum($vencendo),
            ];
        }

        /*
         * ⛔ **Quem VOCÊ desconectou não é pendência** (DEC-155).
         *
         * ⚠️ `podePublicar()` responde "não" para os dois casos, mas eles são
         * opostos: a conta expirada **parou sozinha** e precisa de conserto; a
         * desconectada **parou porque você mandou**. Cobrar conserto de um
         * gesto deliberado é o painel discutindo a decisão de quem usa — e é
         * assim que a pessoa aprende a ignorar o bloco de avisos inteiro.
         */
        $quebradas = $contas
            ->reject(fn (ContaSocial $c) => $c->status === StatusConta::Desconectada)
            ->reject->podePublicar();

        if ($quebradas->isNotEmpty()) {
            $conta = $quebradas->first();

            $pendencias[] = [
                'tom' => 'erro',
                'texto' => $quebradas->count() === 1
                    ? "«{$conta->nome_exibicao}»{$this->ondeEsta($conta)} parou de publicar no {$conta->plataforma->rotulo()}."
                    : "{$quebradas->count()} contas pararam de publicar.",
                // ⛔ Conta de OUTRO grupo: "Resolver" abriria a janela daquela
                // rede VAZIA — a grade é filtrada pelo grupo em foco. Entrar no
                // grupo primeiro é o conserto (DEC-89).
                'acao' => $this->grupoParaEntrar($quebradas) ? 'Entrar no grupo' : 'Resolver',
                'url' => null,
                'grupo' => $this->grupoParaEntrar($quebradas),
                'rede' => $this->grupoParaEntrar($quebradas) ? null : $this->redeEmComum($quebradas),
            ];
        }

        return $pendencias;
    }

    /**
     * ⛔ O aviso do que não subiu — e ele conta POSTS (DEC-90).
     *
     * ⚠️ Antes dizia "3 publicações não subiram" contando **destinos**. Uma
     * publicação vira um post por canal, então o número não batia com a aba de
     * Publicações, que conta publicações. Dois números para o mesmo fato.
     *
     * ⭐ A ação segue a intenção: falha num grupo que não está em foco leva
     * para dentro dele. Com falha em mais de um grupo **não há ação** —
     * escolher uma seria decidir por conta própria qual é o problema da pessoa.
     *
     * @return list<array<string, mixed>>
     */
    private function avisoDoQueNaoSubiu(): array
    {
        $porGrupo = collect($this->resumo->porGrupo())
            ->filter(fn (array $n) => $n['naoSubiram'] > 0);

        if ($porGrupo->isEmpty()) {
            return [];
        }

        $quantos = $porGrupo->sum('naoSubiram');
        $texto = $quantos === 1 ? 'Um post não subiu.' : "{$quantos} posts não subiram.";

        // Tudo num grupo só: dá para levar direto até lá.
        if ($porGrupo->count() === 1) {
            $grupo = $this->grupoPorId((int) $porGrupo->keys()->first());
            $noFoco = $grupo?->id === GrupoCorrente::id();

            return [[
                'tom' => 'erro',
                'texto' => $noFoco || ! $grupo
                    ? $texto
                    : rtrim($texto, '.')." em «{$grupo->nome}».",
                'acao' => $noFoco ? 'Ver o que houve' : "Entrar em «{$grupo?->nome}»",
                'url' => $noFoco ? route('publicacoes', ['aba' => 'falharam']) : null,
                'grupo' => $noFoco ? null : $grupo?->ulid,
                'rede' => null,
            ]];
        }

        return [[
            'tom' => 'erro',
            'texto' => "{$texto} Eles estão em {$porGrupo->count()} grupos.",
            'acao' => '',
            'url' => null,
            'grupo' => null,
            'rede' => null,
        ]];
    }

    /**
     * Um por grupo, sempre — a tela precisa da linha mesmo zerada.
     *
     * @param  Collection<int, ContaSocial>  $contas
     * @return list<array<string, mixed>>
     */
    private function resumoDosGrupos($contas): array
    {
        $numeros = $this->resumo->porGrupo();
        $ultimas = Publicacao::query()
            ->whereNotNull('enviada_em')
            ->groupBy('grupo_id')
            ->pluck(DB::raw('max(enviada_em)'), 'grupo_id');

        // Contas agrupadas em memória: elas já estão carregadas, e uma consulta
        // por grupo aqui viraria N+1 recarregado de 4 em 4 segundos pela
        // atualização viva.
        $porGrupo = $contas->groupBy('grupo_id');

        return Grupo::query()->oldest('id')->get(['id', 'ulid'])
            ->map(function (Grupo $grupo) use ($numeros, $ultimas, $porGrupo) {
                $doGrupo = $porGrupo->get($grupo->id, collect());

                return [
                    'ulid' => $grupo->ulid,
                    ...($numeros[$grupo->id] ?? ['noAr' => 0, 'andando' => 0, 'naoSubiram' => 0, 'saiuDoAr' => 0]),
                    'cadencia' => $this->cadencia($doGrupo, $ultimas->get($grupo->id)),
                    // ⚠️ Contas que a pessoa VÊ na grade: desconectada não conta.
                    'canaisParados' => $doGrupo
                        ->filter(fn (ContaSocial $c) => $c->status !== StatusConta::Desconectada && ! $c->podePublicar())
                        ->count(),
                    'autorizacoesVencendo' => $doGrupo
                        ->filter(fn (ContaSocial $c) => (bool) $c->credencial?->venceEmBreve())
                        ->count(),
                ];
            })
            ->all();
    }

    /**
     * A frase pronta, em PT-BR — a tela não formata data nem recebe `Date`.
     *
     * ⚠️ Ancorada em `enviada_em`, jamais em `publicado_em`: o que falhou não
     * tem data de publicação, e ancorar ali apagaria justamente as falhas.
     *
     * @param  Collection<int, ContaSocial>  $contas
     */
    private function cadencia($contas, ?string $ultima): string
    {
        $conectadas = $contas->filter(fn (ContaSocial $c) => $c->status !== StatusConta::Desconectada)->count();

        if ($conectadas === 0) {
            // Sozinho: acrescentar "ainda não publicou" seria dizer o óbvio.
            return 'sem canal conectado';
        }

        $canais = $conectadas === 1 ? '1 canal' : "{$conectadas} canais";

        if (! $ultima) {
            return "{$canais} · ainda não publicou";
        }

        $dias = (int) Carbon::parse($ultima)->startOfDay()->diffInDays(now()->startOfDay());

        return match (true) {
            $dias <= 0 => "{$canais} · publicou hoje",
            $dias === 1 => "{$canais} · publicou ontem",
            default => "{$canais} · última publicação há {$dias} dias",
        };
    }

    private function grupoPorId(int $id): ?Grupo
    {
        return Grupo::query()->find($id);
    }

    /**
     * ", em Novelas" — ou nada, quando a conta está no grupo que já está à vista.
     *
     * ⚠️ Sem isto, o aviso fala de um canal que não aparece em tela nenhuma e a
     * pessoa procura o problema onde ele não está.
     */
    private function ondeEsta(ContaSocial $conta): string
    {
        return $conta->grupo_id === GrupoCorrente::id()
            ? ''
            : ", em {$conta->grupo->nome},";
    }

    /**
     * O grupo em que entrar, quando o problema está fora do que está à vista.
     *
     * ⛔ `null` quando a conta já está no grupo em foco (aí a ação resolve ali
     * mesmo) ou quando são grupos diferentes (aí não há para onde levar sem
     * escolher pela pessoa).
     *
     * @param  Collection<int, ContaSocial>  $contas
     */
    private function grupoParaEntrar($contas): ?string
    {
        $grupos = $contas->pluck('grupo_id')->unique();

        if ($grupos->count() !== 1 || $grupos->first() === GrupoCorrente::id()) {
            return null;
        }

        return $contas->first()->grupo?->ulid;
    }

    /**
     * A rede que o aviso pode abrir direto — ou `null` quando são várias.
     *
     * ⚠️ Com contas de redes diferentes no mesmo aviso, abrir uma delas seria
     * escolher por conta própria qual o problema da pessoa. Aí o aviso vira só
     * texto, e quem aponta o dedo é o ponto colorido na grade logo abaixo.
     *
     * @param  Collection<int, ContaSocial>  $contas
     */
    private function redeEmComum($contas): ?string
    {
        $redes = $contas->map(fn (ContaSocial $c) => $c->plataforma->value)->unique();

        return $redes->count() === 1 ? $redes->first() : null;
    }
}

<?php

namespace App\Http\Controllers\Cliente;

use App\Enums\StatusDestino;
use App\Http\Controllers\Controller;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Publicacao;
use App\Support\Conexao\ResumoDasRedes;
use App\Support\GrupoCorrente;
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
    ) {}

    public function __invoke(): Response
    {
        // ⚠️ `refresh_token` entra na seleção porque `venceEmBreve()` pergunta se
        // ele EXISTE. Fora da lista, com strict mode ligado, isso estoura 500 — e
        // só em produção, que é onde ele costuma estar ligado.
        // ⚠️ TODAS as contas, de todos os grupos: o aviso de saúde ignora o
        // filtro (DEC-80). Conta da outra ponta não pode morrer calada só
        // porque a pessoa está olhando outro grupo.
        $contas = ContaSocial::with(['credencial:id,conta_social_id,expira_em,refresh_token', 'grupo:id,nome'])->get();
        $numeros = $this->numeros();

        return Inertia::render('cliente/visao-geral', [
            'numeros' => $numeros,
            // ⭐ O bloco que justifica abrir o painel: o que está esperando VOCÊ.
            'pendencias' => $this->pendencias($contas, $numeros),
            'primeirosPassos' => $this->primeirosPassos($contas),
            // ⭐ Conexões deixou de ser tela (DEC-63): o estado das redes mora
            // aqui, com o semáforo à vista. Escondido atrás de um clique, ele
            // vira algo que se descobre depois de já ter perdido a publicação.
            ...$this->redes->montar(),
        ]);
    }

    /**
     * Os três lados do mesmo fato.
     *
     * ⭐ Mostrar só o que deu certo é o que os concorrentes fazem, e é por isso
     * que o painel deles mente. A falha aparece do lado do acerto.
     *
     * @return array{noAr: int, andando: int, falharam: int}
     */
    private function numeros(): array
    {
        $contagem = Destino::query()
            ->join('contas_sociais', 'contas_sociais.id', '=', 'destinos.conta_social_id')
            ->join('publicacoes', 'publicacoes.id', '=', 'destinos.publicacao_id')
            // ⛔ Não sai daqui: é o que aplica o escopo do dono num `join` cru.
            // O filtro de grupo abaixo SOMA a ele, nunca substitui (DEC-74).
            ->whereIn('contas_sociais.id', ContaSocial::query()->select('id'))
            // ⭐ Pelo grupo da PUBLICAÇÃO, não pelo da conta (DEC-75).
            ->where('publicacoes.grupo_id', GrupoCorrente::id())
            ->groupBy('destinos.status')
            ->pluck(DB::raw('count(*)'), 'destinos.status');

        $de = fn (StatusDestino ...$status) => collect($status)
            ->sum(fn (StatusDestino $s) => (int) $contagem->get($s->value, 0));

        return [
            'noAr' => $de(StatusDestino::Publicado),
            'andando' => $de(
                StatusDestino::Pendente,
                StatusDestino::Enviando,
                StatusDestino::Processando,
                StatusDestino::AguardandoJanela,
            ),
            'falharam' => $de(StatusDestino::Falhou),
        ];
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

        if ($numeros['falharam'] > 0) {
            $quantas = $numeros['falharam'];

            $pendencias[] = [
                'tom' => 'erro',
                'texto' => $quantas === 1
                    ? 'Uma publicação não subiu.'
                    : "{$quantas} publicações não subiram.",
                'acao' => 'Ver o que houve',
                'url' => route('publicacoes'),
                'rede' => null,
            ];
        }

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
                'rede' => $this->redeEmComum($vencendo),
            ];
        }

        $quebradas = $contas->reject->podePublicar();

        if ($quebradas->isNotEmpty()) {
            $conta = $quebradas->first();

            $pendencias[] = [
                'tom' => 'erro',
                'texto' => $quebradas->count() === 1
                    ? "«{$conta->nome_exibicao}»{$this->ondeEsta($conta)} parou de publicar no {$conta->plataforma->rotulo()}."
                    : "{$quebradas->count()} contas pararam de publicar.",
                'acao' => 'Resolver',
                'url' => null,
                'rede' => $this->redeEmComum($quebradas),
            ];
        }

        return $pendencias;
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

    /**
     * Os primeiros passos — que **se marcam** conforme acontecem.
     *
     * A tela antiga repetia o mesmo convite para sempre. Uma lista que completa
     * mostra progresso; um cartaz fixo mostra que ninguém está olhando.
     *
     * @param  Collection<int, ContaSocial>  $contas
     * @return list<array{titulo: string, texto: string, feito: bool, url: ?string, abreCatalogo: bool}>
     */
    private function primeirosPassos($contas): array
    {
        return [
            [
                'titulo' => 'Conectar uma rede',
                'texto' => 'Você autoriza no site da própria rede; sua senha nunca passa por aqui.',
                // Só conta canal DESTE grupo: com o grupo novo vazio, o passo
                // reabre — é a instrução certa para o estado em que ela está.
                'feito' => $contas->filter(fn (ContaSocial $c) => $c->grupo_id === GrupoCorrente::id())
                    ->filter->podePublicar()
                    ->isNotEmpty(),
                // ⚠️ Não é link: conectar acontece NESTA tela, num modal. Levar
                // para a página onde a pessoa já está seria um link morto.
                'url' => null,
                'abreCatalogo' => true,
            ],
            [
                // ⚠️ Enviar deixou de ser etapa própria: o arquivo entra dentro
                // do compositor. Manter dois passos descreveria um caminho que
                // não existe mais.
                'titulo' => 'Publicar o primeiro vídeo',
                'texto' => 'A gente confere o arquivo contra as regras de cada rede, publica e depois '.
                    'relê na própria rede para guardar o link como prova.',
                'feito' => Publicacao::query()
                    ->where('grupo_id', GrupoCorrente::id())
                    ->whereNotNull('enviada_em')
                    ->exists(),
                'url' => route('publicar'),
                'abreCatalogo' => false,
            ],
        ];
    }
}

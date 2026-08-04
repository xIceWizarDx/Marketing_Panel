<?php

namespace App\Http\Controllers\Cliente;

use App\Enums\StatusDestino;
use App\Http\Controllers\Controller;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Publicacao;
use App\Support\Conexao\ResumoDasRedes;
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
        $contas = ContaSocial::with('credencial:id,conta_social_id,expira_em')->get();
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
            // `destinos` não tem dono próprio: o filtro vem pela conta, que tem.
            ->whereIn('contas_sociais.id', ContaSocial::query()->select('id'))
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
     * @param  Collection<int, ContaSocial>  $contas
     * @param  array{noAr: int, andando: int, falharam: int}  $numeros
     * @return list<array{tom: string, texto: string, acao: string, url: string}>
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
            ];
        }

        $vencendo = $contas->filter(fn (ContaSocial $c) => (bool) $c->credencial?->venceEmBreve());

        if ($vencendo->isNotEmpty()) {
            $pendencias[] = [
                'tom' => 'atencao',
                'texto' => $vencendo->count() === 1
                    ? "A conexão de {$vencendo->first()->nome_exibicao} está para vencer."
                    : "{$vencendo->count()} conexões estão para vencer.",
                'acao' => 'Reconectar',
                // A grade fica logo abaixo, nesta mesma tela.
                'url' => route('painel').'#redes',
            ];
        }

        $quebradas = $contas->reject->podePublicar();

        if ($quebradas->isNotEmpty()) {
            $pendencias[] = [
                'tom' => 'erro',
                'texto' => $quebradas->count() === 1
                    ? "{$quebradas->first()->nome_exibicao} parou de publicar."
                    : "{$quebradas->count()} contas pararam de publicar.",
                'acao' => 'Resolver',
                'url' => route('painel').'#redes',
            ];
        }

        return $pendencias;
    }

    /**
     * Os primeiros passos — que **se marcam** conforme acontecem.
     *
     * A tela antiga repetia o mesmo convite para sempre. Uma lista que completa
     * mostra progresso; um cartaz fixo mostra que ninguém está olhando.
     *
     * @param  Collection<int, ContaSocial>  $contas
     * @return list<array{titulo: string, texto: string, feito: bool, url: string}>
     */
    private function primeirosPassos($contas): array
    {
        return [
            [
                'titulo' => 'Conectar uma rede',
                'texto' => 'Você autoriza no site da própria rede; sua senha nunca passa por aqui.',
                'feito' => $contas->filter->podePublicar()->isNotEmpty(),
                'url' => route('painel').'#redes',
            ],
            [
                // ⚠️ Enviar deixou de ser etapa própria: o arquivo entra dentro
                // do compositor. Manter dois passos descreveria um caminho que
                // não existe mais.
                'titulo' => 'Publicar o primeiro vídeo',
                'texto' => 'A gente confere o arquivo contra as regras de cada rede, publica e depois '.
                    'relê na própria rede para guardar o link como prova.',
                'feito' => Publicacao::query()->whereNotNull('enviada_em')->exists(),
                'url' => route('publicar'),
            ],
        ];
    }
}

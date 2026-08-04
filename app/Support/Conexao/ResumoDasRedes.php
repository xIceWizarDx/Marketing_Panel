<?php

namespace App\Support\Conexao;

use App\Enums\Plataforma;
use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Publicadores\RegistroDePublicadores;
use App\Support\GrupoCorrente;
use Illuminate\Support\Facades\DB;

/**
 * Como estão as redes — **a fonte única dessa resposta** (DEC-65).
 *
 * ⚠️ Existe porque a pergunta passou a ser feita em mais de um lugar. Com duas
 * montagens separadas, elas divergem — e a divergência aparece como número
 * diferente para o mesmo fato em telas diferentes, que é o defeito que mais
 * rápido faz alguém parar de confiar no painel.
 */
class ResumoDasRedes
{
    public function __construct(
        private readonly RegistroDePublicadores $publicadores,
    ) {}

    /**
     * Uma carta por REDE, não uma lista solta de contas.
     *
     * ⭐ Assim dá para ver num relance o que está ligado, o que falta ligar e o
     * que ainda não existe — em vez de a pessoa ter que deduzir pela ausência.
     *
     * @return array{redes: list<array<string, mixed>>, totalConectado: int}
     */
    public function montar(): array
    {
        $contas = ContaSocial::query()
            // Só os canais do grupo em foco (DEC-71): a grade é a resposta de
            // "onde eu publico DAQUI", não de "tudo que já conectei".
            ->where('grupo_id', GrupoCorrente::id())
            // ⚠️ `refresh_token` entra na seleção porque `venceEmBreve()` pergunta
            // se ele EXISTE. Fora da lista, com strict mode ligado, isso estoura
            // 500 — e só em produção, que é onde ele costuma estar ligado.
            ->with('credencial:id,conta_social_id,expira_em,refresh_token')
            ->latest()
            ->get()
            ->groupBy(fn (ContaSocial $c) => $c->plataforma->value);

        $numeros = $this->numerosPorRede();

        $redes = array_map(fn (Plataforma $plataforma) => [
            'valor' => $plataforma->value,
            'rotulo' => $plataforma->rotulo(),
            // `podeConectar`, não `disponivel`: o publicador do YouTube existe,
            // mas sem a credencial do Google Cloud o botão levaria a um erro.
            // Botão que falha é pior que botão ausente.
            'disponivel' => $this->publicadores->podeConectar($plataforma),
            // Publicador escrito, faltando só a configuração do servidor — a
            // tela diz isso em vez de fingir que a rede não existe.
            'faltaConfigurar' => $this->publicadores->disponivel($plataforma)
                && ! $this->publicadores->podeConectar($plataforma),
            // "Aguardando aprovação" e "em estudo" são coisas diferentes: uma
            // tem caminho definido, a outra é ideia. Dizer o mesmo das duas
            // seria prometer o que ninguém decidiu.
            'situacao' => $plataforma->situacao()->value,
            'situacaoRotulo' => $plataforma->situacao()->rotulo(),
            // ⭐ O número que importa é o de posts CONFIRMADOS na rede — não o
            // de envios feitos. Contar envio seria contar promessa (DEC-31).
            'publicados' => $numeros[$plataforma->value]['publicados'] ?? 0,
            // A contrapartida honesta: o que a rede recusou aparece do lado.
            'falhas' => $numeros[$plataforma->value]['falhas'] ?? 0,
            // Nem sucesso nem falha — ainda em curso, e dizer isso evita a
            // pergunta "cadê meu vídeo?".
            'andando' => $numeros[$plataforma->value]['andando'] ?? 0,
            'contas' => $contas->get($plataforma->value, collect())->map($this->paraTela(...))->values(),
        ], Plataforma::cases());

        return [
            'redes' => $redes,
            'totalConectado' => $contas->flatten()->filter->podePublicar()->count(),
        ];
    }

    /**
     * Os três números de cada rede, numa consulta só.
     *
     * ⭐ Mostrar **só o que deu certo** é exatamente o que os concorrentes fazem,
     * e é a razão de o painel deles mentir. Aqui a falha aparece do lado do
     * acerto, no mesmo tamanho.
     *
     * @return array<string, array{publicados: int, falhas: int, andando: int}>
     */
    private function numerosPorRede(): array
    {
        $contagem = Destino::query()
            ->join('contas_sociais', 'contas_sociais.id', '=', 'destinos.conta_social_id')
            ->join('publicacoes', 'publicacoes.id', '=', 'destinos.publicacao_id')
            /*
             * ⛔ Este `whereIn` NÃO sai daqui.
             *
             * O escopo do dono não acompanha um `join` cru, então esta
             * subconsulta escopada é a **única** coisa que aplica isolamento
             * nesta contagem. O filtro de grupo abaixo SOMA a ela; trocar um
             * pelo outro substituiria a trava de segurança por uma preferência
             * de tela (DEC-74).
             */
            ->whereIn('contas_sociais.id', ContaSocial::query()->select('id'))
            /*
             * ⭐ O grupo vem de `publicacoes`, não da conta.
             *
             * Contar pela conta faria o número histórico mudar sozinho no dia
             * em que alguém movesse um canal de grupo (DEC-75). Sem `when()`
             * de propósito: grupo não resolvido é bug, e bug tem que estourar
             * em vez de devolver a contagem de todo mundo.
             */
            ->where('publicacoes.grupo_id', GrupoCorrente::id())
            ->groupBy('contas_sociais.plataforma', 'destinos.status')
            ->get([
                'contas_sociais.plataforma',
                'destinos.status',
                DB::raw('count(*) as total'),
            ]);

        $numeros = [];

        foreach ($contagem as $linha) {
            $rede = $linha->plataforma instanceof Plataforma
                ? $linha->plataforma->value
                : (string) $linha->plataforma;

            $status = $linha->status instanceof StatusDestino
                ? $linha->status
                : StatusDestino::from((string) $linha->status);

            $numeros[$rede] ??= ['publicados' => 0, 'falhas' => 0, 'andando' => 0];

            // ⚠️ `match` sem `default`: status novo obriga a decidir em qual
            // coluna ele entra, em vez de sumir de todas em silêncio.
            $coluna = match ($status) {
                StatusDestino::Publicado => 'publicados',
                StatusDestino::Falhou => 'falhas',
                StatusDestino::Pendente,
                StatusDestino::Enviando,
                StatusDestino::Processando,
                StatusDestino::AguardandoJanela => 'andando',
            };

            $numeros[$rede][$coluna] += (int) $linha->total;
        }

        return $numeros;
    }

    /** @return array<string, mixed> */
    private function paraTela(ContaSocial $conta): array
    {
        return [
            'ulid' => $conta->ulid,
            'plataforma' => $conta->plataforma->value,
            'plataformaRotulo' => $conta->plataforma->rotulo(),
            'nome' => $conta->nome_exibicao,
            'status' => $conta->status->value,
            'statusRotulo' => $conta->status->rotulo(),
            // ⭐ O semaforo do DEC-32.
            'cor' => $conta->status->cor(),
            'detalhe' => $conta->status_detalhe,
            'podePublicar' => $conta->podePublicar(),
            // ⛔ NUNCA o token — nem para o admin impersonando.
            'venceEm' => $conta->credencial?->expira_em?->toIso8601String(),
            'venceEmBreve' => (bool) $conta->credencial?->venceEmBreve(),
        ];
    }
}

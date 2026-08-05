<?php

namespace App\Support\Conexao;

use App\Enums\Plataforma;
use App\Models\ContaSocial;
use App\Publicadores\RegistroDePublicadores;
use App\Support\GrupoCorrente;
use App\Support\ResumoDoPainel;

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
        // ⚠️ Ele NÃO conta mais por conta própria: pede a quem sabe (DEC-65).
        // Duas contagens da mesma coisa divergem, e aí nenhuma é confiável.
        private readonly ResumoDoPainel $resumo,
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

        $numeros = $this->resumo->porRedeDoGrupo(GrupoCorrente::id());

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
            'publicados' => $numeros[$plataforma->value]['noAr'] ?? 0,
            // A contrapartida honesta: o que a rede recusou aparece do lado.
            'falhas' => $numeros[$plataforma->value]['naoSubiram'] ?? 0,
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

<?php

namespace App\Support;

use App\Enums\Plataforma;
use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;
use Illuminate\Support\Facades\DB;

/**
 * Quantos posts em que estado — **a fonte única dessa conta** (DEC-65).
 *
 * ⚠️ Existe porque a mesma pergunta é feita em três recortes: o total do
 * painel, o de cada grupo e o de cada rede. Com três contagens separadas elas
 * divergem, e número diferente para o mesmo fato em telas diferentes é o
 * defeito que mais rápido faz alguém parar de confiar no painel.
 *
 * ⭐ **Uma consulta serve os três.** Ela agrupa por grupo, plataforma e status;
 * os recortes são somas em memória sobre o mesmo resultado.
 *
 * ⚠️ **A unidade aqui é o POST, não a publicação** (DEC-90). Publicação é o
 * vídeo que a pessoa mandou; ela vira um post por canal escolhido. As abas da
 * tela de Publicações contam publicações — por isso os textos daqui dizem
 * "posts", e nunca mandam a pessoa "ver os 3".
 */
class ResumoDoPainel
{
    /** @var list<object>|null */
    private ?array $contagem = null;

    /**
     * Os três lados do mesmo fato, somando **todos os grupos**.
     *
     * ⭐ A falha aparece do lado do acerto, no mesmo tamanho. Mostrar só o que
     * deu certo é o que os concorrentes fazem, e é por isso que o painel deles
     * mente.
     *
     * @return array{noAr: int, andando: int, naoSubiram: int, saiuDoAr: int}
     */
    public function total(): array
    {
        return $this->somar($this->linhas());
    }

    /**
     * O mesmo, por grupo.
     *
     * @return array<int, array{noAr: int, andando: int, naoSubiram: int, saiuDoAr: int}>
     */
    public function porGrupo(): array
    {
        $porGrupo = [];

        foreach ($this->linhas() as $linha) {
            $porGrupo[(int) $linha->grupo_id][] = $linha;
        }

        return array_map($this->somar(...), $porGrupo);
    }

    /**
     * E por rede, dentro de um grupo — é o que alimenta os quadrados de "Suas
     * redes".
     *
     * @return array<string, array{noAr: int, andando: int, naoSubiram: int, saiuDoAr: int}>
     */
    public function porRedeDoGrupo(?int $grupoId): array
    {
        if ($grupoId === null) {
            return [];
        }

        $porRede = [];

        foreach ($this->linhas() as $linha) {
            if ((int) $linha->grupo_id !== $grupoId) {
                continue;
            }

            $porRede[$this->plataforma($linha)][] = $linha;
        }

        return array_map($this->somar(...), $porRede);
    }

    /**
     * A consulta — **uma só, por requisição**.
     *
     * @return list<object>
     */
    private function linhas(): array
    {
        return $this->contagem ??= Destino::query()
            ->join('contas_sociais', 'contas_sociais.id', '=', 'destinos.conta_social_id')
            ->join('publicacoes', 'publicacoes.id', '=', 'destinos.publicacao_id')
            /*
             * ⛔ Este `whereIn` NÃO sai daqui.
             *
             * O escopo do dono não acompanha um `join` cru, então esta
             * subconsulta escopada é a **única** coisa que aplica isolamento
             * nesta contagem (DEC-74). Ela é trava de segurança, não filtro de
             * conveniência.
             */
            ->whereIn('contas_sociais.id', ContaSocial::query()->select('id'))
            /*
             * ⭐ O grupo vem de `publicacoes`, nunca da conta (DEC-75).
             *
             * Contar pela conta faria o número histórico de um grupo mudar
             * sozinho no dia em que alguém movesse um canal — e número que muda
             * retroativamente não serve para decidir nada.
             */
            ->groupBy('publicacoes.grupo_id', 'contas_sociais.plataforma', 'destinos.status')
            ->get([
                'publicacoes.grupo_id',
                'contas_sociais.plataforma',
                'destinos.status',
                DB::raw('count(*) as total'),
            ])
            ->all();
    }

    /**
     * @param  list<object>  $linhas
     * @return array{noAr: int, andando: int, naoSubiram: int, saiuDoAr: int}
     */
    private function somar(array $linhas): array
    {
        $soma = ['noAr' => 0, 'andando' => 0, 'naoSubiram' => 0, 'saiuDoAr' => 0];

        foreach ($linhas as $linha) {
            $status = $linha->status instanceof StatusDestino
                ? $linha->status
                : StatusDestino::from((string) $linha->status);

            // ⚠️ `match` sem `default`: status novo obriga a decidir em qual
            // coluna ele entra, em vez de sumir de todas em silêncio.
            $coluna = match ($status) {
                StatusDestino::Publicado => 'noAr',
                StatusDestino::Falhou => 'naoSubiram',
                /*
                 * ⛔ **Balde PRÓPRIO** (DEC-165).
                 *
                 * ⚠️ Isto já entrou em `naoSubiram`, com a justificativa de que
                 * "a frase da tela diz qual dos dois casos é". **No quadrado da
                 * rede não existe frase** — existe a palavra *"não foi"*, e ela
                 * é falsa: o post foi, a pessoa viu, e depois foi apagado.
                 *
                 * ⛔ Juntar os dois desfaz exatamente a distinção que a
                 * reconferência (DEC-145) criou, e faz o painel acusar falha
                 * onde houve uma decisão de quem publica.
                 */
                StatusDestino::Removido => 'saiuDoAr',
                StatusDestino::Pendente,
                StatusDestino::Enviando,
                StatusDestino::Processando,
                StatusDestino::AguardandoJanela => 'andando',
            };

            $soma[$coluna] += (int) $linha->total;
        }

        return $soma;
    }

    private function plataforma(object $linha): string
    {
        return $linha->plataforma instanceof Plataforma
            ? $linha->plataforma->value
            : (string) $linha->plataforma;
    }
}

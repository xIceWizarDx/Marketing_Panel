<?php

namespace App\Support\Midia;

use App\Models\Destino;
use App\Models\Midia;
use App\Models\Publicacao;
use Carbon\CarbonInterface;

/**
 * Quando o arquivo original perde a função — **a fonte única dessa resposta**.
 *
 * ⚠️ Existe porque a pergunta é feita em dois lugares: o comando de limpeza
 * decide se pode apagar, e a tela mostra até quando dá para republicar. Com duas
 * contas separadas, elas divergem — e a tela passa a prometer uma data que o
 * comando não respeita, que é pior que não mostrar data nenhuma.
 */
class LiberacaoDeArquivo
{
    /**
     * A data em que o arquivo pode sair, ou `null` enquanto ele tiver função.
     *
     * ⛔ Segura o arquivo enquanto ele tiver função:
     *   - **destino pendente** — publicação agendada precisa do vídeo na hora
     *     marcada, e tentar de novo precisa dele para reenviar;
     *   - **rascunho** — publicação criada e ainda não enviada continua
     *     esperando o arquivo.
     */
    public static function em(Midia $midia): ?CarbonInterface
    {

        $rascunho = Publicacao::withoutGlobalScopes()
            ->where('midia_id', $midia->id)
            ->whereNull('enviada_em')
            ->exists();

        if ($rascunho) {
            return null;
        }

        $destinos = Destino::withoutGlobalScopes()
            ->whereIn('publicacao_id', Publicacao::withoutGlobalScopes()
                ->where('midia_id', $midia->id)
                ->select('id'))
            ->get(['status', 'updated_at']);

        if ($destinos->isEmpty()) {
            /*
             * Enviada e abandonada — a pessoa desistiu no meio.
             *
             * ⚠️ Não é carência: é limpeza de lixo. Existe só para o arquivo
             * sobreviver enquanto ela escreve a legenda; passado isso, ninguém
             * mais vai usá-lo.
             */
            return $midia->created_at?->copy()->addDays((int) config('midia.limpar_abandonado_em_dias'));
        }

        if ($destinos->contains(fn (Destino $d) => ! $d->status->ehTerminal())) {
            return null;
        }

        /*
         * ⭐ Terminou = sai. Sem espera.
         *
         * O produto não guarda acervo (DEC-59). O arquivo existia para subir;
         * subiu, acabou a função. Republicar passa a exigir reenviar o vídeo —
         * o registro, o laudo e as provas continuam aqui.
         */
        return $destinos->max('updated_at');
    }

    /** Por que ela pode sair — texto curto para o comando mostrar. */
    public static function motivo(Midia $midia): string
    {
        return Publicacao::withoutGlobalScopes()->where('midia_id', $midia->id)->exists()
            ? 'publicação concluída'
            : 'nunca publicada';
    }
}

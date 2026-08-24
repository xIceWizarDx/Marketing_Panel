<?php

namespace App\Enums;

/**
 * Em que pé está cada rede no projeto.
 *
 * Existe porque "em breve" não dá conta: o YouTube tem data e caminho definido,
 * o Pinterest ainda é ideia. Chamar os dois de "em breve" seria prometer o que
 * não foi decidido — e é justamente o que o produto critica nos concorrentes.
 */
enum SituacaoDaRede: string
{
    /** Publica hoje. */
    case Disponivel = 'disponivel';

    /** Decidida e no roteiro — falta a aprovação da plataforma ou a integração. */
    case Planejada = 'planejada';

    /** Mapeada, sem decisão. Pode nunca entrar. */
    case EmEstudo = 'em_estudo';

    /**
     * ⛔ **Decidida como FORA** — e isso é diferente de "em estudo".
     *
     * ⚠️ "Em estudo" quer dizer que ninguém decidiu; esta quer dizer que
     * alguém decidiu **não**, com motivo escrito (doc 28). Chamar as duas de
     * "em estudo" faria alguém pesquisar Snapchat de novo daqui a seis meses e
     * chegar à mesma conclusão pela segunda vez.
     */
    case Fora = 'fora';

    public function rotulo(): string
    {
        return __("rotulos.situacao_rede.{$this->value}");
    }
}

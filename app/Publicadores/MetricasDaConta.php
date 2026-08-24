<?php

namespace App\Publicadores;

/**
 * O que a rede publica sobre a CONTA conectada.
 *
 * ⚠️ Um campo só, de propósito. "Total de visualizações do canal" e "quantos
 * vídeos o canal tem" existem no YouTube e não existem no Bluesky — e nenhum dos
 * dois responde a pergunta que este produto sabe responder. O contador que
 * importa aqui é o do **post**, que é onde mora a prova.
 *
 * ⭐ `null` continua significando "esta rede não publica este número" (DEC-95).
 */
readonly class MetricasDaConta
{
    public function __construct(
        /**
         * Seguidores — inscritos, no YouTube.
         *
         * ⚠️ O YouTube devolve **arredondado para 3 algarismos** (1.230 e não
         * 1.234). É assim para todo mundo, inclusive para o dono do canal, e a
         * tela precisa dizer isso — senão o número aqui parece errado ao lado do
         * que o YouTube Studio mostra.
         *
         * ⚠️ Inscritos ocultos: o campo **some** da resposta. Vira `null`, nunca
         * `0` — dizer "você tem 0 inscritos" para quem escondeu o número é
         * afirmar algo falso.
         */
        public ?int $seguidores = null,
    ) {}

    public function temAlgum(): bool
    {
        return $this->seguidores !== null;
    }
}

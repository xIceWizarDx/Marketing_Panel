<?php

namespace App\Publicadores;

/**
 * O que a rede respondeu quando fomos CONFERIR se o post existe mesmo.
 *
 * ⭐ E a resposta que vira a prova de entrega — o diferencial do produto.
 */
readonly class ResultadoConciliacao
{
    private function __construct(
        public bool $noAr,
        public bool $aindaProcessando,
        public ?string $url = null,
        public ?string $erro = null,
        /**
         * Qualidade que a rede entregou, quando ela informa.
         *
         * ⭐ No YouTube vem `hd` ou `sd`. Enviamos 1080×1920: se voltar `sd`, a
         * rede esta admitindo que degradou o video — e a gente mostra isso com
         * a palavra dela, nao com suposicao nossa.
         */
        public ?string $qualidade = null,
    ) {}

    /** Confirmado: o post existe e este e o link. */
    public static function noAr(string $url, ?string $qualidade = null): self
    {
        return new self(noAr: true, aindaProcessando: false, url: $url, qualidade: $qualidade);
    }

    /** A rede ainda esta moderando/transcodificando — perguntar de novo depois. */
    public static function aindaProcessando(): self
    {
        return new self(noAr: false, aindaProcessando: true);
    }

    /** A rede recusou o conteudo depois de ter aceitado o upload. */
    public static function recusado(string $motivo): self
    {
        return new self(noAr: false, aindaProcessando: false, erro: $motivo);
    }
}

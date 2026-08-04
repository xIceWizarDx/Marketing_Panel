<?php

namespace App\Publicadores;

/**
 * O que uma rede respondeu ao motor.
 *
 * ⭐ Repare que NAO existe "publicado" aqui. A rede so pode dizer que ACEITOU;
 * quem diz que esta publicado e a conciliacao, depois de reler o post (DEC-31).
 */
readonly class ResultadoPublicacao
{
    private function __construct(
        public bool $aceito,
        public ?string $identificadorExterno = null,
        /** Link, quando a rede ja devolve na hora (o Bluesky devolve). */
        public ?string $url = null,
        public ?string $erro = null,
        /** Vale a pena tentar de novo? 429/timeout/5xx = sim. */
        public bool $transitorio = false,
        /** Cota da rede esgotada — espera a janela, nao e erro. */
        public bool $semCota = false,
        public ?string $codigo = null,
    ) {}

    public static function aceito(string $identificadorExterno, ?string $url = null): self
    {
        return new self(aceito: true, identificadorExterno: $identificadorExterno, url: $url);
    }

    /** Erro definitivo: a rede recusou e tentar de novo nao muda nada. */
    public static function recusado(string $erro, ?string $codigo = null): self
    {
        return new self(aceito: false, erro: $erro, codigo: $codigo);
    }

    /** Erro passageiro: rede fora do ar, limite de taxa, timeout. */
    public static function tentarDepois(string $erro, ?string $codigo = null): self
    {
        return new self(aceito: false, erro: $erro, transitorio: true, codigo: $codigo);
    }

    public static function semCota(string $motivo): self
    {
        return new self(aceito: false, erro: $motivo, semCota: true);
    }
}

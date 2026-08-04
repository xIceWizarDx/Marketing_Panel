<?php

namespace App\Exceptions;

use App\Enums\StatusDestino;
use RuntimeException;

/**
 * Alguém tentou levar um destino para um estado que não faz sentido.
 *
 * Exemplo do que isto impede: `pendente → publicado` sem passar por
 * `enviando`/`processando` — ou seja, dizer que publicou sem nunca ter enviado
 * nem conferido. É a mentira que o produto existe para não contar (DEC-31).
 */
class TransicaoInvalida extends RuntimeException
{
    public static function de(StatusDestino $atual, StatusDestino $novo): self
    {
        $permitidas = implode(', ', array_map(
            fn (StatusDestino $s) => $s->value,
            $atual->transicoesPermitidas()
        ));

        return new self(
            "Destino não pode ir de '{$atual->value}' para '{$novo->value}'. ".
            ($permitidas === ''
                ? "'{$atual->value}' é estado final."
                : "De '{$atual->value}' só é possível ir para: {$permitidas}.")
        );
    }
}

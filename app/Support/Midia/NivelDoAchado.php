<?php

namespace App\Support\Midia;

enum NivelDoAchado: string
{
    case Ok = 'ok';
    case Atencao = 'atencao';
    case Erro = 'erro';

    public function rotulo(): string
    {
        return __("rotulos.nivel_achado.{$this->value}");
    }

    /** Peso pra ordenar: o problema aparece primeiro na tela. */
    public function gravidade(): int
    {
        return match ($this) {
            self::Erro => 2,
            self::Atencao => 1,
            self::Ok => 0,
        };
    }
}

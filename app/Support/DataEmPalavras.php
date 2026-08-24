<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Data virada frase, no servidor.
 *
 * ⚠️ **A tela não formata data** — regra do projeto, e ela existe por um motivo
 * prático: formatação espalhada em React vira três jeitos de escrever "ontem" em
 * três telas, e nenhum deles concorda com o fuso do servidor.
 */
class DataEmPalavras
{
    /**
     * *"lido hoje às 05:10"* · *"lido ontem"* · *"lido há 4 dias"*.
     *
     * ⭐ Hoje ganha a hora e os outros dias não: dentro do mesmo dia a hora é o
     * que diz se o número é de antes ou depois do que aconteceu; passada a
     * meia-noite, ela vira ruído.
     */
    public static function leitura(?CarbonInterface $quando): ?string
    {
        if (! $quando) {
            return null;
        }

        $dias = (int) $quando->copy()->startOfDay()->diffInDays(now()->startOfDay());

        return match (true) {
            $dias <= 0 => 'lido hoje às '.$quando->format('H:i'),
            $dias === 1 => 'lido ontem',
            default => "lido há {$dias} dias",
        };
    }
}

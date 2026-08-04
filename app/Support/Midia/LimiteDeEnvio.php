<?php

namespace App\Support\Midia;

/**
 * O tamanho que a tela pode PROMETER.
 *
 * ⚠️ Não adianta o produto aceitar 300 MB se o PHP corta em 2 MB: a pessoa
 * escolhe o arquivo, espera o envio e recebe "não completou. Tente de novo" —
 * conselho inútil, porque tentar de novo vai falhar igual.
 *
 * Foi o que aconteceu no primeiro envio real. Por isso o teto é o **menor** entre
 * o que o produto quer e o que a instalação suporta: a tela passa a dizer a
 * verdade sozinha, em qualquer máquina, inclusive no servidor.
 */
class LimiteDeEnvio
{
    /** Quanto dá para enviar de fato, em MB. */
    public static function megabytes(): int
    {
        $tetos = array_filter([
            (int) config('midia.tamanho_maximo_mb'),
            self::doPhp('upload_max_filesize'),
            // ⚠️ `post_max_size` limita o corpo INTEIRO, com os outros campos
            // junto. É ele que corta primeiro quando alguém esquece de subir os
            // dois — e o erro que aparece não menciona nenhum dos dois.
            self::doPhp('post_max_size'),
        ]);

        return $tetos === [] ? 0 : (int) min($tetos);
    }

    /** O produto quer mais do que esta instalação aguenta? */
    public static function phpEstaSegurando(): bool
    {
        return self::megabytes() < (int) config('midia.tamanho_maximo_mb');
    }

    /**
     * Lê um limite do `php.ini` em MB.
     *
     * Os valores vêm com sufixo (`2M`, `1G`) ou como bytes puros, e `-1`
     * significa sem limite.
     */
    private static function doPhp(string $diretiva): ?int
    {
        $bruto = trim((string) ini_get($diretiva));

        if ($bruto === '' || $bruto === '-1' || $bruto === '0') {
            return null;
        }

        $numero = (float) $bruto;

        $multiplicador = match (strtoupper(substr($bruto, -1))) {
            'G' => 1024,
            'M' => 1,
            'K' => 1 / 1024,
            // Sem sufixo: o valor está em bytes.
            default => 1 / (1024 * 1024),
        };

        return (int) floor($numero * $multiplicador);
    }
}

<?php

namespace App\Support\Midia;

/**
 * Como cada rede conta o tamanho de um texto.
 *
 * Não é preciosismo: os três dão números **muito** diferentes para o mesmo
 * texto, e usar o errado recusa conteúdo válido ou deixa passar o que a rede
 * vai cortar.
 *
 *   "👨‍👩‍👧‍👦"  →  1 grafema · 7 caracteres · 25 bytes
 *   "coração" →  7 grafemas · 7 caracteres ·  9 bytes
 */
enum Medida: string
{
    /** Como a pessoa enxerga: um emoji de família é UM. (Bluesky) */
    case Grafemas = 'grafemas';

    /** Ponto de código Unicode. */
    case Caracteres = 'caracteres';

    /** Bytes na codificação UTF-8. (descrição do YouTube) */
    case Bytes = 'bytes';

    public function contar(string $texto): int
    {
        return match ($this) {
            // `\X` do PCRE casa um cluster de grafema inteiro — dá o mesmo
            // resultado que a extensão `intl`, sem depender dela estar ativa
            // no servidor (não está no XAMPP por padrão).
            self::Grafemas => (int) preg_match_all('/\X/u', $texto),
            self::Caracteres => mb_strlen($texto),
            self::Bytes => strlen($texto),
        };
    }

    /** Nome para a mensagem de erro, no plural que a pessoa entende. */
    public function rotulo(): string
    {
        return match ($this) {
            self::Grafemas, self::Caracteres => 'caracteres',
            self::Bytes => 'bytes',
        };
    }
}

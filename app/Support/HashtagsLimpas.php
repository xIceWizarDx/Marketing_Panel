<?php

namespace App\Support;

/**
 * ⭐ A hashtag entra limpa — **de todas as portas** (DEC-152).
 *
 * ⛔ Guardar com `#` obrigaria cada rede a desfazer isso na hora de publicar, e
 * a que esquecesse mandaria `##noticias`. Então o `#` cai aqui, uma vez, antes
 * de qualquer validação.
 *
 * ⚠️ **E vale para as duas portas — publicar e o grupo.** Enquanto só o campo
 * do compositor limpava, colar `#noticias` em uma delas passava e na outra
 * levava uma recusa por um caractere que a pessoa não escolheu escrever.
 */
final class HashtagsLimpas
{
    /**
     * @param  mixed  $bruto  o que veio do formulário
     * @return list<string>
     */
    public static function de(mixed $bruto): array
    {
        if (! is_array($bruto)) {
            return [];
        }

        $limpas = array_map(
            fn (mixed $t) => is_string($t) ? trim(ltrim(trim($t), '#')) : $t,
            $bruto,
        );

        /*
         * ⚠️ O que não é texto **sobrevive** à limpeza de propósito: quem mandou
         * um número onde vai hashtag precisa receber a recusa da validação, não
         * ver o valor sumir em silêncio.
         */
        return array_values(array_filter($limpas, fn (mixed $t) => $t !== '' && $t !== null));
    }
}

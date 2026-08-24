<?php

namespace App\Support;

/**
 * ⛔ **O aviso de que esta publicação vai custar mais** (DEC-126).
 *
 * O X é a única rede do painel em que publicar custa dinheiro — e em que uma
 * escolha de **texto** muda o custo:
 *
 * | Post: criar | US$ 0,015 |
 * | Post: criar **com URL** | **US$ 0,200** |
 *
 * Treze vezes. Em 500 posts por mês: US$ 7,50 sem link, **US$ 100,00** com link
 * em todos.
 *
 * ⛔ Descobrir isso na fatura é o tipo de surpresa que faz alguém parar de
 * confiar na ferramenta — e o painel **sabe ler o texto antes de enviar**.
 *
 * ⚠️ **Aviso, não bloqueio.** Pode ser exatamente o que a pessoa quer; quem
 * decide gastar é ela. O que não pode é ela não saber.
 */
final class CustoDaPublicacao
{
    /** Só o X cobra por publicação, e só ele cobra a mais por link. */
    public const REDE_QUE_COBRA = 'x';

    /**
     * ⭐ A frase que a tela mostra — **e os números moram só aqui**.
     *
     * ⚠️ A tela precisa decidir na hora da digitação se o texto tem link, então
     * essa checagem existe dos dois lados. Os PREÇOS, não: eles são escritos uma
     * vez, aqui, e viajam prontos para o React. Duas cópias de "US$ 0,20"
     * divergem no dia em que o X mudar a tabela — e a que estiver errada é a que
     * a pessoa vai ler.
     */
    public static function fraseDoLink(): string
    {
        return 'Esta legenda tem link. No X, publicar com link custa US$ 0,20 em vez de US$ 0,015 — '.
            'treze vezes mais, por publicação.';
    }

    /**
     * O aviso para este texto e estas redes — ou `null` quando não há o que dizer.
     *
     * @param  list<string>  $plataformas  os valores das redes escolhidas
     */
    public static function avisoDeLink(?string $texto, array $plataformas): ?string
    {
        if (! in_array(self::REDE_QUE_COBRA, $plataformas, true)) {
            return null;
        }

        if (! self::temLink($texto)) {
            return null;
        }

        return self::fraseDoLink();
    }

    /**
     * O texto tem endereço de site?
     *
     * ⚠️ Deliberadamente **abrangente**: pega `http://`, `https://`, `www.` e
     * também o domínio solto (`bit.ly/abc`), que é como a maioria das pessoas
     * escreve. Um aviso a mais custa uma frase; um a menos custa US$ 0,185 por
     * publicação.
     *
     * ⛔ Não é validação de URL, e não deve virar uma: o objetivo é **avisar**,
     * não recusar. Falso positivo aqui é barato; falso negativo, não.
     */
    public static function temLink(?string $texto): bool
    {
        $texto = trim((string) $texto);

        if ($texto === '') {
            return false;
        }

        return (bool) preg_match(
            // protocolo, ou `www.`, ou algo.dominio/ com barra
            '~(https?://|www\.[a-z0-9-]+\.[a-z]{2,}|[a-z0-9-]+\.[a-z]{2,}/\S)~i',
            $texto
        );
    }
}

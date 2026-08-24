<?php

namespace App\Support\Tiktok;

use Generator;

/**
 * As regras de pedaço do TikTok — que enganam (DEC-120).
 *
 * ⛔ **`total_chunk_count` arredonda para BAIXO**, não para cima:
 * *"Total count calculation: video_size ÷ chunk_size, rounded down."*
 *
 * Um vídeo de 12 MB com pedaço de 5 MB dá **2** pedaços, e o último carrega
 * 7 MB. Todo mundo escreveria `ceil()` aqui, porque é o que faz sentido em
 * qualquer outro protocolo de envio em partes — e o número que não bate faz o
 * envio falhar **depois** de o arquivo inteiro ter subido.
 *
 * ⚠️ Mora numa classe própria porque é aritmética com regra escondida: separada
 * assim, ela pode ser provada com números na mão, sem rede nenhuma no meio.
 */
final class PedacosDoEnvio
{
    /** Pedaço mínimo aceito pela rede. */
    public const MINIMO = 5 * 1024 * 1024;

    /** Pedaço máximo — o último pode passar disso, para absorver a sobra. */
    public const MAXIMO = 64 * 1024 * 1024;

    /** Teto do último pedaço. */
    public const MAXIMO_DO_ULTIMO = 128 * 1024 * 1024;

    public const MAXIMO_DE_PEDACOS = 1000;

    /** Vídeo inteiro. */
    public const MAXIMO_DO_VIDEO = 4 * 1024 * 1024 * 1024;

    /**
     * O tamanho que usamos quando o arquivo comporta divisão.
     *
     * ⚠️ Não é o máximo de propósito: cada pedaço é lido inteiro para a memória
     * antes de subir, e 64 MB por vez estouraria o limite de memória do PHP em
     * servidor apertado. 10 MB dá 409 pedaços num vídeo de 4 GB — bem abaixo do
     * teto de 1000.
     */
    private const PADRAO = 10 * 1024 * 1024;

    /**
     * O plano de envio, ou `null` se o arquivo passa do teto da rede.
     *
     * @return array{tamanho: int, pedaco: int, total: int}|null
     */
    public static function de(int $tamanho): ?array
    {
        if ($tamanho <= 0 || $tamanho > self::MAXIMO_DO_VIDEO) {
            return null;
        }

        // ⛔ **Para baixo.** É a regra inteira desta classe.
        $total = intdiv($tamanho, self::PADRAO);

        /*
         * ⚠️ Um pedaço só: aí `chunk_size` é o arquivo inteiro.
         *
         * É o caso de vídeo menor que o mínimo — a documentação pede
         * explicitamente *"upload as a single chunk with chunk_size equal to
         * the entire file size"* — e também o de vídeo pequeno o bastante para
         * o arredondamento para baixo dar 1. Declarar um `chunk_size` diferente
         * do que sobe seria mandar dois números que não se explicam.
         */
        if ($total <= 1) {
            return ['tamanho' => $tamanho, 'pedaco' => $tamanho, 'total' => 1];
        }

        return ['tamanho' => $tamanho, 'pedaco' => self::PADRAO, 'total' => $total];
    }

    /**
     * Os intervalos de bytes, na ordem — **e o último absorve a sobra**.
     *
     * ⚠️ Os pedaços sobem em SEQUÊNCIA: *"File chunks must be uploaded
     * sequentially"*. Por isso um gerador, e não uma lista pronta: ele deixa
     * explícito que existe ordem, e não convida ninguém a paralelizar.
     *
     * @param  array{tamanho: int, pedaco: int, total: int}  $plano
     * @return Generator<int, array{0: int, 1: int}>
     */
    public static function intervalos(array $plano): Generator
    {
        for ($i = 0; $i < $plano['total']; $i++) {
            $de = $i * $plano['pedaco'];

            // ⭐ O último vai até o fim do arquivo, doa o que doer no tamanho:
            // é ele que carrega tudo que o arredondamento para baixo deixou.
            $ate = $i === $plano['total'] - 1
                ? $plano['tamanho'] - 1
                : $de + $plano['pedaco'] - 1;

            yield [$de, $ate];
        }
    }
}

<?php

use App\Support\Tiktok\PedacosDoEnvio;

/*
| Guardiao da ARITMETICA DE PEDACOS do TikTok (plano 23, DEC-120).
|
| ⛔ A regra que engana: `total_chunk_count` = video_size ÷ chunk_size,
| ARREDONDADO PARA BAIXO. Todo mundo escreveria `ceil()` aqui — e o numero que
| nao bate faz o envio falhar DEPOIS de o arquivo inteiro ter subido.
|
| ⚠️ Este arquivo nao toca em rede nenhuma de proposito: e aritmetica, e
| aritmetica se prova com numeros na mao.
*/

/** @return array{0: int, 1: int}[] */
function intervalosDe(array $plano): array
{
    return iterator_to_array(PedacosDoEnvio::intervalos($plano), false);
}

describe('⛔ o arredondamento é para BAIXO (DEC-120)', function () {
    it('⭐ 25 MB com pedaço de 10 MB dá DOIS pedaços, e o último carrega 15 MB', function () {
        // ⛔ Com `ceil()` daria tres, e o terceiro nao teria byte nenhum para
        // carregar. O numero declarado nao bateria com o que sobe.
        $plano = PedacosDoEnvio::de(25 * 1024 * 1024);

        expect($plano['total'])->toBe(2)
            ->and($plano['pedaco'])->toBe(10 * 1024 * 1024);

        $intervalos = intervalosDe($plano);

        expect($intervalos)->toHaveCount(2)
            ->and($intervalos[0])->toBe([0, 10485759])
            // ⭐ O ultimo absorve a sobra: 15 MB, nao 10.
            ->and($intervalos[1])->toBe([10485760, 26214399]);
    });

    it('⭐ os pedaços cobrem o arquivo inteiro, sem buraco e sem sobreposição', function () {
        // Buraco vira video corrompido; sobreposicao vira byte duplicado. Nada
        // na resposta da rede avisa de nenhum dos dois.
        foreach ([7, 12, 25, 64, 137, 512] as $mb) {
            $tamanho = $mb * 1024 * 1024;
            $intervalos = intervalosDe(PedacosDoEnvio::de($tamanho));

            expect($intervalos[0][0])->toBe(0)
                ->and(end($intervalos)[1])->toBe($tamanho - 1);

            foreach ($intervalos as $i => [$de, $ate]) {
                expect($ate)->toBeGreaterThanOrEqual($de);

                if ($i > 0) {
                    expect($de)->toBe($intervalos[$i - 1][1] + 1);
                }
            }
        }
    });
});

describe('o pedaço único', function () {
    it('⛔ vídeo menor que 5 MB sobe INTEIRO, com `chunk_size` igual ao arquivo', function () {
        // A documentacao pede isso literalmente. Declarar 5 MB para um arquivo
        // de 3 MB mandaria dois numeros que nao se explicam.
        $plano = PedacosDoEnvio::de(3 * 1024 * 1024);

        expect($plano['total'])->toBe(1)
            ->and($plano['pedaco'])->toBe(3 * 1024 * 1024)
            ->and($plano['tamanho'])->toBe(3 * 1024 * 1024)
            ->and(intervalosDe($plano))->toBe([[0, 3145727]]);
    });

    it('⚠️ e quando o arredondamento dá 1, o pedaço também vira o arquivo inteiro', function () {
        // 12 MB: `intdiv(12, 10)` = 1. Declarar pedaco de 10 MB e subir 12
        // seria o mesmo desencontro.
        $plano = PedacosDoEnvio::de(12 * 1024 * 1024);

        expect($plano['total'])->toBe(1)
            ->and($plano['pedaco'])->toBe(12 * 1024 * 1024);
    });
});

describe('⛔ os limites da rede', function () {
    it('nenhum pedaço passa do máximo, e o último respeita o teto dele', function () {
        foreach ([6, 19, 137, 1024, 4095] as $mb) {
            $plano = PedacosDoEnvio::de($mb * 1024 * 1024);
            $intervalos = intervalosDe($plano);

            foreach ($intervalos as $i => [$de, $ate]) {
                $bytes = $ate - $de + 1;
                $ehUltimo = $i === count($intervalos) - 1;

                expect($bytes)->toBeLessThanOrEqual(
                    $ehUltimo ? PedacosDoEnvio::MAXIMO_DO_ULTIMO : PedacosDoEnvio::MAXIMO
                );
            }
        }
    });

    it('nunca passa de 1000 pedaços, nem no arquivo de 4 GB', function () {
        expect(PedacosDoEnvio::de(PedacosDoEnvio::MAXIMO_DO_VIDEO)['total'])
            ->toBeLessThanOrEqual(PedacosDoEnvio::MAXIMO_DE_PEDACOS);
    });

    it('⛔ arquivo acima de 4 GB é recusado ANTES de qualquer chamada', function () {
        // Descobrir isso depois de comecar a subir gastaria a cota da pessoa —
        // e a cota do TikTok e curta.
        expect(PedacosDoEnvio::de(PedacosDoEnvio::MAXIMO_DO_VIDEO + 1))->toBeNull()
            ->and(PedacosDoEnvio::de(0))->toBeNull();
    });
});

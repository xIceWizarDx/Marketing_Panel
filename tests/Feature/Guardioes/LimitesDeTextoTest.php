<?php

use App\Enums\Plataforma;
use App\Support\Midia\EspecificacaoDaRede;
use App\Support\Midia\Medida;

/*
| Guardiao dos LIMITES DE TEXTO.
|
| ⛔ A politica do YouTube proibe "modificar valores fornecidos pelo usuario
| (truncar, anexar, alterar) sem consentimento explicito". Cortar em silencio
| e violacao de politica E o defeito que o produto critica: a pessoa escreve
| 120 caracteres, saem 100, e ela so descobre olhando o post no ar.
*/

describe('como cada rede conta', function () {
    it('conta grafema, nao ponto de codigo', function () {
        $familia = '👨‍👩‍👧‍👦';

        // A pessoa ve UM emoji. `mb_strlen` ve 7 — e recusaria texto valido.
        expect(Medida::Grafemas->contar($familia))->toBe(1)
            ->and(Medida::Caracteres->contar($familia))->toBe(7);
    });

    it('conta byte, que nao e caractere', function () {
        // "coração" tem 7 letras e 9 bytes: o ç e o ã ocupam 2 cada.
        expect(Medida::Caracteres->contar('coração'))->toBe(7)
            ->and(Medida::Bytes->contar('coração'))->toBe(9);
    });

    it('trata bandeira e tom de pele como um so', function () {
        expect(Medida::Grafemas->contar('🇧🇷'))->toBe(1)
            ->and(Medida::Grafemas->contar('👍🏽'))->toBe(1);
    });
});

describe('Bluesky — 300 grafemas', function () {
    it('aceita 300 grafemas cheios de emoji', function () {
        // 300 emojis de familia = 300 grafemas (mas 2100 pontos de codigo).
        // Contar caracteres recusaria este texto, que a rede aceita.
        $texto = str_repeat('👨‍👩‍👧‍👦', 300);

        expect(EspecificacaoDaRede::de(Plataforma::Bluesky)->conferirTextos(null, $texto))->toBeEmpty();
    });

    it('recusa 301 grafemas', function () {
        $achados = EspecificacaoDaRede::de(Plataforma::Bluesky)
            ->conferirTextos(null, str_repeat('a', 301));

        expect($achados)->toHaveCount(1)
            ->and($achados[0]->mensagem)->toContain('301')
            // Diz quanto tirar, nao so que passou.
            ->and($achados[0]->providencia)->toContain('Tire 1');
    });
});

describe('YouTube — bytes na descricao', function () {
    it('mede a descricao em BYTES, nao em caracteres', function () {
        // 2600 "ç" = 2600 caracteres, mas 5200 bytes. Cabe por caractere,
        // NAO cabe por byte — e a API recusaria depois do upload inteiro.
        $legenda = str_repeat('ç', 2600);

        $achados = EspecificacaoDaRede::de(Plataforma::Youtube)->conferirTextos('Titulo', $legenda);

        expect($achados)->toHaveCount(1)
            ->and($achados[0]->mensagem)->toContain('bytes')
            ->and($achados[0]->mensagem)->toContain('5200');
    });

    it('recusa titulo acima de 100', function () {
        $achados = EspecificacaoDaRede::de(Plataforma::Youtube)
            ->conferirTextos(str_repeat('a', 101), 'ok');

        expect($achados)->toHaveCount(1)
            ->and($achados[0]->mensagem)->toStartWith('Título');
    });

    it('mede as hashtags pelo orcamento TOTAL, com separadores', function () {
        // O YouTube conta 500 caracteres somando as tags e as virgulas.
        $tags = array_fill(0, 60, 'palavradez');

        $achados = EspecificacaoDaRede::de(Plataforma::Youtube)->conferirTextos('Titulo', 'ok', $tags);

        expect($achados)->toHaveCount(1)
            ->and($achados[0]->mensagem)->toStartWith('Hashtags');
    });

    it('aceita o que cabe', function () {
        expect(EspecificacaoDaRede::de(Plataforma::Youtube)
            ->conferirTextos('Meu corte', 'Uma legenda normal com acentuação.', ['corte', 'shorts']))
            ->toBeEmpty();
    });
});

describe('formato do arquivo', function () {
    it('o Bluesky so aceita mp4 — nem o .mov do iPhone', function () {
        $bluesky = EspecificacaoDaRede::de(Plataforma::Bluesky);

        expect($bluesky->conferirContainer('video/mp4'))->toBeNull();

        $achado = $bluesky->conferirContainer('video/quicktime');

        // O envio aceita .mov porque a Meta aceita. Aqui ele seria recusado
        // DEPOIS do upload inteiro, com mensagem em ingles.
        expect($achado)->not->toBeNull()
            ->and($achado->providencia)->toContain('MP4');
    });

    it('rede sem restricao de contêiner aceita o que o codec permitir', function () {
        expect(EspecificacaoDaRede::de(Plataforma::Youtube)->conferirContainer('video/quicktime'))->toBeNull();
    });
});

describe('os tetos vieram do lexicon oficial', function () {
    it('usa os numeros publicados, nao estimativa', function () {
        $bluesky = EspecificacaoDaRede::de(Plataforma::Bluesky);

        // 100.000.000 bytes exatos — o codigo tinha 50 MB chutados, metade.
        expect($bluesky->tamanhoMaximoBytes)->toBe(100_000_000);
    });
});

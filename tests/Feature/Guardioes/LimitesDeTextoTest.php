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

describe('⛔ rede sem campo de título soma o título na legenda', function () {
    it('⭐ e a lista de quem TEM campo de título é decidida uma vez', function () {
        /*
         * ⛔ O título que a pessoa escreve tem que ir para algum lugar. Nas redes
         * que têm campo próprio, ele vai para lá; nas que não têm, ele sobe
         * **colado na legenda** — e aí os dois dividem um orçamento só.
         *
         * ⚠️ Sem isso acontecem duas coisas ruins, e as duas já aconteceram
         * aqui: ou o título **desaparece sem aviso** (era o caso do Bluesky,
         * Instagram, Threads, TikTok e X), ou ele estoura o limite **depois** de
         * o vídeo inteiro ter subido.
         *
         * ⛔ **Rede nova quebra este teste de propósito.** É para alguém decidir
         * na hora onde o título dela vai parar, em vez de descobrir depois que
         * ele sumiu.
         */
        $temCampoProprio = [
            Plataforma::Youtube,   // `snippet.title`
            Plataforma::Facebook,  // `title`, separado da `description`
            Plataforma::Linkedin,  // `content.media.title`
            Plataforma::Pinterest, // `title` (100), separado da `description` (800)
        ];

        foreach (Plataforma::comEspecificacao() as $rede) {
            $soma = EspecificacaoDaRede::de($rede)->texto->tituloEntraNaLegenda;

            expect($soma)->toBe(
                ! in_array($rede, $temCampoProprio, true),
                "O {$rede->rotulo()} não decidiu onde o título vai parar."
            );
        }
    });

    it('⭐ e quando soma, mede título, legenda e hashtags JUNTOS', function () {
        // É assim que o texto sobe; medir outra coisa seria conferir um texto
        // que a rede não vai receber.
        $achados = EspecificacaoDaRede::de(Plataforma::Bluesky)
            ->conferirTextos(str_repeat('t', 200), str_repeat('a', 90), ['umahashtag']);

        expect($achados)->not->toBeEmpty()
            ->and($achados[0]->mensagem)->toContain('juntos');
    });
});

describe('⛔ as hashtags contam SEMPRE, porque sempre sobem juntas', function () {
    it('⭐ nenhuma rede tem campo separado de hashtag — elas viajam no texto', function () {
        /*
         * ⛔ A conferência media só a legenda, e as hashtags entravam de graça.
         * No Pinterest, com 800 de descrição, quinze hashtags passavam aqui e
         * eram recusadas lá — **depois** do vídeo inteiro ter subido.
         *
         * ⚠️ Isto valia para toda rede com campo de título próprio, porque
         * nelas a regra do "título junto" não se aplicava e ninguém tinha olhado
         * o que acontecia com as hashtags.
         */
        $spec = EspecificacaoDaRede::de(Plataforma::Pinterest);

        // 795 + 1 espaço + 4 = 800: cabe raspando.
        expect($spec->conferirTextos('Título', str_repeat('a', 795), ['abc']))->toBeEmpty();

        // A MESMA legenda com uma hashtag a mais passa dos 800.
        $achados = $spec->conferirTextos('Título', str_repeat('a', 795), ['abc', 'de']);

        expect($achados)->not->toBeEmpty()
            ->and($achados[0]->mensagem)->toContain('hashtags');
    });

    it('⭐ e o título com campo próprio continua medido sozinho', function () {
        // No Pinterest o título tem 100 e a descrição tem 800: são orçamentos
        // separados, e juntá-los seria o erro oposto.
        $achados = EspecificacaoDaRede::de(Plataforma::Pinterest)
            ->conferirTextos(str_repeat('t', 101), 'curta');

        expect($achados)->not->toBeEmpty()
            ->and($achados[0]->mensagem)->toStartWith('Título:');
    });

    it('⚠️ e o YouTube continua com DOIS orçamentos de hashtag', function () {
        /*
         * ⚠️ Ele manda as tags num campo separado ALÉM de elas irem na
         * descrição. Dois limites existem, e as duas conferências existem.
         */
        $achados = EspecificacaoDaRede::de(Plataforma::Youtube)
            ->conferirTextos('Titulo', 'curta', [str_repeat('a', 501)]);

        expect(collect($achados)->contains(fn ($a) => str_contains($a->mensagem, 'Hashtags')))->toBeTrue();
    });
});

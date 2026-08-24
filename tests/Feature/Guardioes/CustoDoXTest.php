<?php

use App\Enums\Plataforma;
use App\Support\CustoDaPublicacao;

/*
| Guardiao do AVISO DE CUSTO do X (plano 24, DEC-126).
|
| ⛔ O X e a unica rede do painel em que publicar custa dinheiro — e em que uma
| escolha de TEXTO muda o custo em treze vezes:
|
|   Post: criar            US$ 0,015
|   Post: criar com URL    US$ 0,200
|
| Em 500 posts por mes: US$ 7,50 sem link, US$ 100,00 com link em todos.
*/

describe('⛔ o aviso só aparece quando ele importa', function () {
    it('⭐ legenda com link + X escolhido = aviso, com o número na frente', function () {
        // ⚠️ "Pode custar mais" nao ajuda ninguem a decidir. O numero, sim.
        $aviso = CustoDaPublicacao::avisoDeLink('olha isso https://meusite.com', ['x', 'bluesky']);

        expect($aviso)->not->toBeNull()
            ->and($aviso)->toContain('0,20')
            ->and($aviso)->toContain('0,015');
    });

    it('⛔ sem o X entre as redes, silêncio — nenhuma outra cobra por link', function () {
        expect(CustoDaPublicacao::avisoDeLink('olha isso https://meusite.com', ['bluesky', 'youtube']))
            ->toBeNull();
    });

    it('⛔ sem link, silêncio — o aviso que aparece à toa é o que ninguém lê', function () {
        expect(CustoDaPublicacao::avisoDeLink('olha esse corte novo #humor', ['x']))->toBeNull()
            ->and(CustoDaPublicacao::avisoDeLink(null, ['x']))->toBeNull()
            ->and(CustoDaPublicacao::avisoDeLink('   ', ['x']))->toBeNull();
    });
});

describe('⭐ o que conta como link', function () {
    it('pega as formas que as pessoas realmente escrevem', function () {
        /*
         * ⚠️ Deliberadamente abrangente. Falso positivo custa uma frase; falso
         * negativo custa US$ 0,185 por publicacao — e a pessoa so descobre na
         * fatura.
         */
        foreach ([
            'https://exemplo.com',
            'http://exemplo.com',
            'entra em www.exemplo.com hoje',
            'link na bio: bit.ly/meucorte',
            'veja em meusite.com.br/promo',
        ] as $texto) {
            expect(CustoDaPublicacao::temLink($texto))->toBeTrue($texto);
        }
    });

    it('⛔ e NÃO confunde texto comum com endereço', function () {
        // Aviso errado toda vez tambem faz a pessoa parar de ler o aviso.
        foreach ([
            'corte novo, muito bom.',
            'olha isso... e depois isso',
            '#humor #corte #shorts',
            'preço: R$ 19,90',
            'chegou às 10h30. incrível',
        ] as $texto) {
            expect(CustoDaPublicacao::temLink($texto))->toBeFalse($texto);
        }
    });
});

describe('⛔ os preços moram num lugar só', function () {
    it('⭐ a frase que a tela mostra vem do SERVIDOR, pronta', function () {
        /*
         * ⚠️ A tela decide QUANDO mostrar (e ela que ve a pessoa digitando); o
         * servidor decide O QUE dizer.
         *
         * ⛔ Os precos existiam escritos em dois idiomas — PHP e TypeScript. No
         * dia em que o X mudasse a tabela, uma das copias ficaria errada, e e a
         * errada que a pessoa leria.
         */
        expect(CustoDaPublicacao::fraseDoLink())
            ->toContain('0,20')
            ->toContain('0,015')
            ->and(CustoDaPublicacao::avisoDeLink('veja https://x.com', ['x']))
            ->toBe(CustoDaPublicacao::fraseDoLink());
    });

    it('⭐ e ela CHEGA na tela — só na rede que cobra', function () {
        /*
         * ⚠️ Passa pela requisicao de verdade, nao pela classe: o defeito que
         * este teste pega e a frase existir no servidor e nao chegar no React —
         * que foi exatamente o estado em que este codigo nasceu.
         */
        $this->actingAs(cliente())
            ->get('/publicar')
            ->assertOk()
            ->assertInertia(function ($pagina) {
                // ⚠️ Os limites moram dentro de `compositor`, nao na raiz das props.
                $limites = $pagina->toArray()['props']['compositor']['limites'];

                expect($limites['x']['avisoDeLink'])->toBe(CustoDaPublicacao::fraseDoLink());

                // ⛔ E silêncio em todas as outras: nenhuma delas cobra.
                foreach (Plataforma::comEspecificacao() as $rede) {
                    if ($rede !== Plataforma::X) {
                        expect($limites[$rede->value]['avisoDeLink'])->toBeNull($rede->rotulo());
                    }
                }
            });
    });
});

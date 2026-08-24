<?php

use App\Enums\Plataforma;
use App\Support\ContextoDoUsuario;

/*
| Guardiao da FORMA DE CONEXAO.
|
| ⛔ O defeito que estes testes travam chegou na tela: o modal decidia o que
| mostrar com `if (rede === 'youtube')`, e **tudo que nao era YouTube caia no
| formulario do Bluesky**. Com uma rede so de cada tipo isso passava despercebido;
| no dia em que a Meta foi ligada, o modal do Facebook comecou a pedir "senha de
| aplicativo" e "voce.bsky.social" para conectar uma Pagina.
|
| ⚠️ A correcao foi tirar a decisao da tela: a rede DECLARA como se conecta, e o
| servidor diz para onde ir. Estes testes existem para que ela nao volte.
*/

beforeEach(fn () => ContextoDoUsuario::limpar());
afterEach(fn () => ContextoDoUsuario::limpar());

it('⛔ só o Bluesky pede senha aqui — o resto autoriza fora', function () {
    /*
     * ⚠️ A diferenca nao e estetica: e onde a pessoa digita a senha. Em
     * `autorizacao` ela digita no site da rede e a senha nunca passa por nos.
     * Mostrar um campo de senha para uma rede desse tipo seria pedir uma coisa
     * que nao existe — e ensinar a pessoa a digitar senha em lugar errado.
     */
    expect(Plataforma::Bluesky->formaDeConexao())->toBe('senha_de_aplicativo');

    foreach ([Plataforma::Youtube, Plataforma::Facebook, Plataforma::Instagram, Plataforma::Threads] as $rede) {
        expect($rede->formaDeConexao())->toBe('autorizacao');
    }
});

it('⭐ Facebook e Instagram apontam para a MESMA porta — uma autorização acende as duas', function () {
    // A conta do Instagram fica pendurada numa Pagina do Facebook e o login e o
    // mesmo. Duas portas dariam duas conexoes para o mesmo consentimento.
    $ana = cliente();

    $this->actingAs($ana)->get('/painel')->assertInertia(fn ($p) => $p->where('redes', function ($redes) {
        $por = collect($redes)->keyBy('valor');

        expect($por['facebook']['enderecoDeConexao'])->toBe($por['instagram']['enderecoDeConexao'])
            ->and($por['facebook']['enderecoDeConexao'])->toContain('/conexoes/meta');

        return true;
    }));
});

it('⛔ cada rede que autoriza tem porta PRÓPRIA, e nenhuma reaproveita a de outra', function () {
    // O Threads nao usa o Login do Facebook (DEC-99): apontar para `/conexoes/meta`
    // levaria a pessoa a autorizar a rede errada.
    $ana = cliente();

    $this->actingAs($ana)->get('/painel')->assertInertia(fn ($p) => $p->where('redes', function ($redes) {
        $por = collect($redes)->keyBy('valor');

        expect($por['youtube']['enderecoDeConexao'])->toContain('/conexoes/youtube')
            ->and($por['threads']['enderecoDeConexao'])->toContain('/conexoes/threads')
            ->and($por['threads']['enderecoDeConexao'])->not->toContain('/conexoes/meta');

        return true;
    }));
});

it('⛔ rede que conecta por senha NÃO leva endereço de autorização', function () {
    // Endereco preenchido numa rede de senha faria a tela oferecer os dois
    // caminhos, e um deles nao existe.
    $ana = cliente();

    $this->actingAs($ana)->get('/painel')->assertInertia(fn ($p) => $p->where('redes', function ($redes) {
        $bluesky = collect($redes)->firstWhere('valor', 'bluesky');

        expect($bluesky['formaDeConexao'])->toBe('senha_de_aplicativo')
            ->and($bluesky['enderecoDeConexao'])->toBeNull();

        return true;
    }));
});

it('⚠️ toda rede CONECTÁVEL sabe para onde mandar a pessoa', function () {
    /*
     * ⛔ Rede oferecida na tela sem endereco de conexao e um botao que nao leva
     * a lugar nenhum — o pior tipo de defeito aqui, porque a pessoa clica,
     * espera autorizar, e nada acontece.
     */
    $ana = cliente();

    $this->actingAs($ana)->get('/painel')->assertInertia(fn ($p) => $p->where('redes', function ($redes) {
        foreach ($redes as $rede) {
            if (! $rede['disponivel'] || $rede['formaDeConexao'] !== 'autorizacao') {
                continue;
            }

            expect($rede['enderecoDeConexao'])->not->toBeNull("a rede {$rede['valor']} é oferecida e não tem para onde ir");
        }

        return true;
    }));
});

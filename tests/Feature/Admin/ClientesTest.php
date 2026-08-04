<?php

use App\Models\Midia;
use App\Support\ContextoDoUsuario;

beforeEach(fn () => ContextoDoUsuario::limpar());
afterEach(fn () => ContextoDoUsuario::limpar());

it('lista os clientes para o admin', function () {
    cliente(['nome' => 'Ana Silva']);
    cliente(['nome' => 'Bruno Costa']);

    $this->actingAs(admin())
        ->get('/admin/clientes')
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina
            ->component('admin/clientes')
            ->has('clientes.data', 2));
});

it('não lista outros administradores junto dos clientes', function () {
    cliente();
    admin(['nome' => 'Outro Admin']);

    $this->actingAs(admin())
        ->get('/admin/clientes')
        ->assertInertia(fn ($pagina) => $pagina->has('clientes.data', 1));
});

it('busca por nome e por e-mail', function () {
    cliente(['nome' => 'Ana Silva', 'email' => 'ana@exemplo.com.br']);
    cliente(['nome' => 'Bruno Costa', 'email' => 'bruno@exemplo.com.br']);

    $this->actingAs(admin())
        ->get('/admin/clientes?busca=Ana')
        ->assertInertia(fn ($pagina) => $pagina->has('clientes.data', 1));

    $this->actingAs(admin())
        ->get('/admin/clientes?busca=bruno@')
        ->assertInertia(fn ($pagina) => $pagina->has('clientes.data', 1));
});

it('conta as mídias de cada cliente sem misturar', function () {
    $ana = cliente();
    Midia::factory()->count(3)->doUsuario($ana)->create();
    Midia::factory()->count(7)->doUsuario(cliente())->create();

    // Sem `semEscopo` no controller, a contagem usaria o admin como dono e
    // voltaria zero para todo mundo.
    $this->actingAs(admin())
        ->get('/admin/clientes')
        ->assertInertia(fn ($pagina) => $pagina->where(
            'clientes.data',
            fn ($lista) => collect($lista)->pluck('midias')->sort()->values()->all() === [3, 7]
        ));
});

it('tira e devolve o acesso de um cliente', function () {
    $ana = cliente();

    $this->actingAs(admin())
        ->patch("/admin/clientes/{$ana->ulid}/acesso")
        ->assertRedirect();

    expect($ana->fresh()->ativo)->toBeFalse();

    // E quem perdeu o acesso não entra mais. (Sai da sessão do admin antes:
    // `/entrar` é rota de visitante e redirecionaria quem já está logado.)
    $this->post('/sair');
    $this->post('/entrar', ['email' => $ana->email, 'password' => senhaDaFactory()])
        ->assertSessionHasErrors('email');

    $this->actingAs(admin())->patch("/admin/clientes/{$ana->ulid}/acesso");
    expect($ana->fresh()->ativo)->toBeTrue();
});

it('não deixa o admin tirar o acesso de outro admin por esta rota', function () {
    $outro = admin();

    $this->actingAs(admin())
        ->patch("/admin/clientes/{$outro->ulid}/acesso")
        ->assertNotFound();

    expect($outro->fresh()->ativo)->toBeTrue();
});

it('barra o cliente na lista de clientes', function () {
    $this->actingAs(cliente())->get('/admin/clientes')->assertNotFound();
});

it('barra o visitante', function () {
    $this->get('/admin/clientes')->assertRedirect(route('entrar'));
});

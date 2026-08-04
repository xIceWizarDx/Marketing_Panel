<?php

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

it('mostra a tela de entrada', function () {
    $this->get('/entrar')
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina->component('acesso/entrar'));
});

it('entra com a senha certa e cai no painel do proprio papel', function () {
    $usuario = cliente();

    $this->post('/entrar', [
        'email' => $usuario->email,
        'password' => senhaDaFactory(),
    ])->assertRedirect(route('painel'));

    $this->assertAuthenticatedAs($usuario);
});

it('manda o administrador para o painel do admin', function () {
    $usuario = admin();

    $this->post('/entrar', [
        'email' => $usuario->email,
        'password' => senhaDaFactory(),
    ])->assertRedirect(route('admin.painel'));
});

it('recusa a senha errada', function () {
    $usuario = cliente();

    $this->post('/entrar', [
        'email' => $usuario->email,
        'password' => 'senha-errada',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('recusa conta desativada sem dizer que ela existe', function () {
    $usuario = cliente(['ativo' => false]);

    $resposta = $this->post('/entrar', [
        'email' => $usuario->email,
        'password' => senhaDaFactory(),
    ]);

    $resposta->assertSessionHasErrors('email');
    $this->assertGuest();

    // A mensagem tem que ser a MESMA de senha errada — se fosse diferente,
    // qualquer um descobriria quais e-mails existem no sistema.
    expect(session('errors')->first('email'))->toBe(__('auth.failed'));
});

it('trava depois de cinco tentativas erradas', function () {
    $usuario = cliente();

    foreach (range(1, 5) as $tentativa) {
        $this->post('/entrar', ['email' => $usuario->email, 'password' => 'errada']);
    }

    $this->post('/entrar', ['email' => $usuario->email, 'password' => senhaDaFactory()])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('sai da conta', function () {
    $this->actingAs(cliente())
        ->post('/sair')
        ->assertRedirect('/');

    $this->assertGuest();
});

it('nao deixa quem ja entrou abrir a tela de entrada de novo', function () {
    $this->actingAs(cliente())
        ->get('/entrar')
        ->assertRedirect(route('painel'));
});

it('manda o visitante para a tela de entrada, nao para uma rota em ingles', function () {
    $this->get('/painel')->assertRedirect(route('entrar'));
});

it('guarda a senha com hash, nunca em texto puro', function () {
    $usuario = Usuario::factory()->create(['password' => Hash::make('uma-senha-bem-longa')]);

    expect($usuario->password)->not->toBe('uma-senha-bem-longa')
        ->and(Hash::check('uma-senha-bem-longa', $usuario->password))->toBeTrue();
});

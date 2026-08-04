<?php

use App\Enums\Papel;
use App\Models\Usuario;

it('mostra a tela de cadastro', function () {
    $this->get('/cadastrar')
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina->component('acesso/cadastrar'));
});

it('cria a conta e ja entra', function () {
    $this->post('/cadastrar', [
        'nome' => 'Fulano de Tal',
        'email' => 'fulano@exemplo.com.br',
        'password' => 'uma-senha-bem-longa',
        'password_confirmation' => 'uma-senha-bem-longa',
    ])->assertRedirect(route('painel'));

    $this->assertAuthenticated();

    expect(Usuario::where('email', 'fulano@exemplo.com.br')->first())
        ->nome->toBe('Fulano de Tal')
        ->papel->toBe(Papel::Cliente)
        ->ativo->toBeTrue()
        ->ulid->not->toBeEmpty();
});

it('ignora o papel enviado pelo formulario e cria sempre como cliente', function () {
    // Sem esta trava, bastaria adicionar papel=admin no formulario pra virar
    // administrador da plataforma.
    $this->post('/cadastrar', [
        'nome' => 'Espertinho',
        'email' => 'espertinho@exemplo.com.br',
        'papel' => 'admin',
        'password' => 'uma-senha-bem-longa',
        'password_confirmation' => 'uma-senha-bem-longa',
    ]);

    expect(Usuario::where('email', 'espertinho@exemplo.com.br')->first()->papel)
        ->toBe(Papel::Cliente);
});

it('exige senha de pelo menos doze caracteres', function () {
    $this->post('/cadastrar', [
        'nome' => 'Fulano',
        'email' => 'curta@exemplo.com.br',
        'password' => 'curta123',
        'password_confirmation' => 'curta123',
    ])->assertSessionHasErrors('password');

    expect(Usuario::where('email', 'curta@exemplo.com.br')->exists())->toBeFalse();
});

it('nao aceita e-mail repetido', function () {
    cliente(['email' => 'repetido@exemplo.com.br']);

    $this->post('/cadastrar', [
        'nome' => 'Outro',
        'email' => 'repetido@exemplo.com.br',
        'password' => 'uma-senha-bem-longa',
        'password_confirmation' => 'uma-senha-bem-longa',
    ])->assertSessionHasErrors('email');
});

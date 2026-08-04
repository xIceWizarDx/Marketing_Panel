<?php

use Illuminate\Support\Facades\Auth;

it('grava o token de lembrar quando a caixa vem marcada', function () {
    $usuario = cliente(['remember_token' => null]);

    $resposta = $this->post('/entrar', [
        'email' => $usuario->email,
        'password' => senhaDaFactory(),
        'lembrar' => true,
    ]);

    $resposta->assertCookie(Auth::guard('web')->getRecallerName());
    expect($usuario->fresh()->remember_token)->not->toBeNull();
});

it('nao grava o token quando a caixa vem desmarcada', function () {
    $usuario = cliente(['remember_token' => null]);

    $this->post('/entrar', [
        'email' => $usuario->email,
        'password' => senhaDaFactory(),
        'lembrar' => false,
    ])->assertCookieMissing(Auth::guard('web')->getRecallerName());

    expect($usuario->fresh()->remember_token)->toBeNull();
});

it('aceita o valor que o React manda de verdade (booleano JSON)', function () {
    $usuario = cliente(['remember_token' => null]);

    // O Inertia manda JSON: `true`, nao a string "on" de formulario HTML.
    $this->postJson('/entrar', [
        'email' => $usuario->email,
        'password' => senhaDaFactory(),
        'lembrar' => true,
    ]);

    expect($usuario->fresh()->remember_token)->not->toBeNull();
});

it('derruba os outros aparelhos ao trocar a senha estando logado', function () {
    $usuario = cliente();

    // "Outro aparelho" = o token que o cookie de lembrar dele carrega.
    $this->post('/entrar', [
        'email' => $usuario->email,
        'password' => senhaDaFactory(),
        'lembrar' => true,
    ]);
    $tokenDoOutroAparelho = $usuario->fresh()->remember_token;

    $this->actingAs($usuario)->put('/minha-conta/senha', [
        'current_password' => senhaDaFactory(),
        'password' => 'outra-senha-bem-longa',
        'password_confirmation' => 'outra-senha-bem-longa',
    ])->assertSessionHasNoErrors();

    // Token diferente = o cookie guardado no aparelho perdido não vale mais.
    expect($usuario->fresh()->remember_token)->not->toBe($tokenDoOutroAparelho);
});

it('mantem quem trocou a senha conectado', function () {
    $usuario = cliente();

    $this->actingAs($usuario)->put('/minha-conta/senha', [
        'current_password' => senhaDaFactory(),
        'password' => 'outra-senha-bem-longa',
        'password_confirmation' => 'outra-senha-bem-longa',
    ]);

    $this->assertAuthenticatedAs($usuario);
});

<?php

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

it('mostra a tela de esqueci a senha', function () {
    $this->get('/esqueci-a-senha')
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina->component('acesso/esqueci-a-senha'));
});

it('envia o link de redefinicao', function () {
    Notification::fake();

    $usuario = cliente();

    $this->post('/esqueci-a-senha', ['email' => $usuario->email]);

    Notification::assertSentTo($usuario, ResetPassword::class);
});

it('responde igual para e-mail que nao existe', function () {
    Notification::fake();

    $this->post('/esqueci-a-senha', ['email' => 'ninguem@exemplo.com.br'])
        ->assertSessionHasNoErrors();

    Notification::assertNothingSent();
});

it('monta o link do e-mail com a rota em portugues', function () {
    Notification::fake();

    $usuario = cliente();
    $this->post('/esqueci-a-senha', ['email' => $usuario->email]);

    Notification::assertSentTo($usuario, ResetPassword::class, function (ResetPassword $aviso) use ($usuario) {
        $link = $aviso->toMail($usuario)->actionUrl;

        // Trava o desvio declarado no AppServiceProvider: o Laravel montaria
        // /reset-password/{token} sozinho e o link chegaria quebrado.
        return str_contains($link, '/redefinir-senha/');
    });
});

it('redefine a senha pelo token', function () {
    Notification::fake();

    $usuario = cliente();
    $this->post('/esqueci-a-senha', ['email' => $usuario->email]);

    Notification::assertSentTo($usuario, ResetPassword::class, function (ResetPassword $aviso) use ($usuario) {
        $this->post('/redefinir-senha', [
            'token' => $aviso->token,
            'email' => $usuario->email,
            'password' => 'outra-senha-bem-longa',
            'password_confirmation' => 'outra-senha-bem-longa',
        ])->assertRedirect(route('entrar'))->assertSessionHasNoErrors();

        return true;
    });

    $this->post('/entrar', ['email' => $usuario->email, 'password' => 'outra-senha-bem-longa']);
    $this->assertAuthenticated();
});

it('pede a senha atual para trocar de senha', function () {
    $usuario = cliente();

    $this->actingAs($usuario)
        ->from('/minha-conta/senha')
        ->put('/minha-conta/senha', [
            'current_password' => 'senha-errada',
            'password' => 'outra-senha-bem-longa',
            'password_confirmation' => 'outra-senha-bem-longa',
        ])
        ->assertSessionHasErrors('current_password');
});

it('troca a senha quando a atual confere', function () {
    $usuario = cliente();

    $this->actingAs($usuario)
        ->from('/minha-conta/senha')
        ->put('/minha-conta/senha', [
            'current_password' => senhaDaFactory(),
            'password' => 'outra-senha-bem-longa',
            'password_confirmation' => 'outra-senha-bem-longa',
        ])
        ->assertSessionHasNoErrors();

    expect(Hash::check('outra-senha-bem-longa', $usuario->fresh()->password))->toBeTrue();
});

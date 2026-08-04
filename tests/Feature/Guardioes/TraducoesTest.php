<?php

use Illuminate\Support\Facades\RateLimiter;

/*
| Guardiao das TRADUCOES.
|
| O Laravel 11+ nao traz os arquivos de traducao prontos. Sem eles, e como o
| fallback tambem e pt_BR, a tela mostra a CHAVE crua — "auth.failed" — no lugar
| da mensagem. A pessoa le isso e nao entende nada: parece defeito do sistema.
*/

it('mostra mensagem de verdade quando a senha nao confere', function () {
    $usuario = cliente();

    $this->post('/entrar', ['email' => $usuario->email, 'password' => 'errada'])
        ->assertSessionHasErrors('email');

    $mensagem = session('errors')->first('email');

    // O sintoma exato do problema: a chave vazando para a tela.
    expect($mensagem)->not->toContain('auth.')
        ->not->toContain('validation.')
        ->toContain('não conferem');
});

it('mostra em portugues quando o campo fica vazio', function () {
    $this->post('/entrar', ['email' => '', 'password' => ''])
        ->assertSessionHasErrors('email');

    $mensagem = session('errors')->first('email');

    // `:attribute` traduzido: "Preencha o e-mail", nao "Preencha email".
    expect($mensagem)->not->toContain('validation.')
        ->toContain('o e-mail');
});

it('explica em portugues quando bate no limite de tentativas', function () {
    $usuario = cliente();

    foreach (range(1, 6) as $tentativa) {
        $this->post('/entrar', ['email' => $usuario->email, 'password' => 'errada']);
    }

    $mensagem = session('errors')->first('email');

    // Era esta mensagem que aparecia como `auth.throttle` cru — e quem lia
    // achava que a senha estava errada, quando so precisava esperar.
    expect($mensagem)->not->toContain('auth.')
        ->toContain('Aguarde')
        ->toContain('segundos');

    RateLimiter::clear(strtolower($usuario->email).'|127.0.0.1');
});

it('traduz o aviso de senha repetida no cadastro', function () {
    cliente(['email' => 'ocupado@exemplo.com.br']);

    $this->post('/cadastrar', [
        'nome' => 'Fulano',
        'email' => 'ocupado@exemplo.com.br',
        'password' => 'uma-senha-bem-longa',
        'password_confirmation' => 'uma-senha-bem-longa',
    ])->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))
        ->not->toContain('validation.')
        ->toContain('já está em uso');
});

it('traduz a confirmacao de senha que nao bate', function () {
    $this->post('/cadastrar', [
        'nome' => 'Fulano',
        'email' => 'novo@exemplo.com.br',
        'password' => 'uma-senha-bem-longa',
        'password_confirmation' => 'outra-coisa-diferente',
    ])->assertSessionHasErrors('password');

    expect(session('errors')->first('password'))
        ->not->toContain('validation.')
        ->toContain('não são iguais');
});

it('nao deixa nenhuma chave de traducao usada vazar crua', function () {
    // Varre as chaves que as telas realmente usam. Arquivo de traducao que
    // sumir ou chave que faltar aparece aqui, nao para o cliente.
    $chaves = [
        'auth.failed', 'auth.password', 'auth.throttle',
        'validation.required', 'validation.email', 'validation.unique',
        'validation.confirmed', 'validation.current_password',
        'validation.mimetypes', 'validation.uploaded',
        'validation.attributes.email', 'validation.attributes.password',
        'passwords.reset', 'passwords.token', 'passwords.sent',
    ];

    $faltando = collect($chaves)->filter(fn (string $chave) => __($chave) === $chave)->values();

    expect($faltando)->toBeEmpty('Sem tradução (a tela mostraria a chave crua): '.$faltando->implode(', '));
});

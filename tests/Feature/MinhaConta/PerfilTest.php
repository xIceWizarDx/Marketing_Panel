<?php

it('mostra a tela de perfil', function () {
    $this->actingAs(cliente())
        ->get('/minha-conta/perfil')
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina->component('minha-conta/perfil'));
});

it('atualiza nome e e-mail', function () {
    $usuario = cliente();

    $this->actingAs($usuario)
        ->patch('/minha-conta/perfil', [
            'nome' => 'Nome Novo',
            'email' => 'novo@exemplo.com.br',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/minha-conta/perfil');

    expect($usuario->fresh())
        ->nome->toBe('Nome Novo')
        ->email->toBe('novo@exemplo.com.br');
});

it('exige nova confirmacao quando o e-mail muda', function () {
    $usuario = cliente();

    $this->actingAs($usuario)->patch('/minha-conta/perfil', [
        'nome' => $usuario->nome,
        'email' => 'outro@exemplo.com.br',
    ]);

    // Sem isto, daria pra apontar a conta pra um e-mail que a pessoa nao controla.
    expect($usuario->fresh()->email_verified_at)->toBeNull();
});

it('mantem a confirmacao quando so o nome muda', function () {
    $usuario = cliente();

    $this->actingAs($usuario)->patch('/minha-conta/perfil', [
        'nome' => 'Outro Nome',
        'email' => $usuario->email,
    ]);

    expect($usuario->fresh()->email_verified_at)->not->toBeNull();
});

it('nao deixa usar o e-mail de outra pessoa', function () {
    cliente(['email' => 'ocupado@exemplo.com.br']);
    $usuario = cliente();

    $this->actingAs($usuario)
        ->patch('/minha-conta/perfil', [
            'nome' => $usuario->nome,
            'email' => 'ocupado@exemplo.com.br',
        ])
        ->assertSessionHasErrors('email');
});

it('apaga a conta com a senha certa', function () {
    $usuario = cliente();

    $this->actingAs($usuario)
        ->delete('/minha-conta/perfil', ['password' => senhaDaFactory()])
        ->assertRedirect('/');

    $this->assertGuest();
    expect($usuario->fresh())->toBeNull();
});

it('nao apaga a conta com a senha errada', function () {
    $usuario = cliente();

    $this->actingAs($usuario)
        ->from('/minha-conta/perfil')
        ->delete('/minha-conta/perfil', ['password' => 'senha-errada'])
        ->assertSessionHasErrors('password');

    $this->assertAuthenticated();
    expect($usuario->fresh())->not->toBeNull();
});

it('barra o visitante em minha conta', function () {
    $this->get('/minha-conta/perfil')->assertRedirect(route('entrar'));
});

it('vale para os dois papeis', function () {
    $this->actingAs(admin())->get('/minha-conta/perfil')->assertOk();
    $this->actingAs(cliente())->get('/minha-conta/perfil')->assertOk();
});

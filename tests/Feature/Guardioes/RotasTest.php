<?php

use Illuminate\Support\Facades\Route;

/*
| Guardiao de ROTAS.
|
| Nomes de rota em portugues so funcionam porque desviamos alguns pontos onde o
| framework crava nomes em ingles. Este arquivo trava esses desvios: se alguem
| renomear uma rota e esquecer o desvio, o teste quebra antes do usuario ver a
| tela de erro.
*/

it('registra todas as rotas de acesso com nome em portugues', function () {
    $esperadas = [
        'inicio', 'entrar', 'sair', 'cadastrar',
        'senha.solicitar', 'senha.enviarLink', 'senha.redefinir', 'senha.salvar', 'senha.confirmar',
        'verificacao.aviso', 'verificacao.confirmar', 'verificacao.reenviar',
        'painel', 'admin.painel',
        'minha-conta.perfil.editar', 'minha-conta.perfil.atualizar', 'minha-conta.perfil.remover',
        'minha-conta.senha.editar', 'minha-conta.senha.atualizar', 'minha-conta.aparencia',
    ];

    $faltando = collect($esperadas)->reject(fn (string $nome) => Route::has($nome))->values();

    expect($faltando)->toBeEmpty('Rotas nao registradas: '.$faltando->implode(', '));
});

it('nao mantem nenhuma rota com nome em ingles do starter', function () {
    $proibidos = ['login', 'logout', 'register', 'dashboard', 'home', 'password.request',
        'password.email', 'password.reset', 'password.store', 'password.confirm',
        'password.edit', 'password.update', 'profile.edit', 'profile.update', 'profile.destroy',
        'verification.notice', 'verification.verify', 'verification.send', 'appearance'];

    $sobrando = collect($proibidos)->filter(fn (string $nome) => Route::has($nome))->values();

    expect($sobrando)->toBeEmpty('Nome de rota em ingles ainda registrado: '.$sobrando->implode(', '));
});

it('nao tem duas rotas disputando a mesma URI e metodo', function () {
    // Colisao de URI some do route:list e vira 500 silencioso: a segunda rota
    // nunca e alcancada.
    $duplicadas = collect(Route::getRoutes())
        ->flatMap(fn ($rota) => collect($rota->methods())
            ->reject(fn ($metodo) => in_array($metodo, ['HEAD', 'OPTIONS'], true))
            ->map(fn ($metodo) => "{$metodo} {$rota->uri()}"))
        ->countBy()
        ->filter(fn (int $vezes) => $vezes > 1)
        ->keys();

    expect($duplicadas)->toBeEmpty('URI duplicada: '.$duplicadas->implode(', '));
});

it('resolve o link de confirmacao de e-mail pela rota em portugues', function () {
    $usuario = cliente(['email_verified_at' => null]);

    $link = URL::temporarySignedRoute('verificacao.confirmar', now()->addHour(), [
        'id' => $usuario->id,
        'hash' => sha1($usuario->email),
    ]);

    $this->actingAs($usuario)->get($link)->assertRedirect();

    expect($usuario->fresh()->hasVerifiedEmail())->toBeTrue();
});

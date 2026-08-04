<?php

/*
| Guardiao de VAZAMENTO.
|
| Tudo que o HandleInertiaRequests compartilha entra no HTML de toda pagina.
| Este teste trava a lista: campo novo no model nao pode chegar ao navegador
| sem alguem decidir isso de proposito.
*/

it('compartilha apenas os campos escolhidos do usuario', function () {
    $usuario = cliente();

    $this->actingAs($usuario)
        ->get('/painel')
        ->assertInertia(fn ($pagina) => $pagina
            ->has('auth.usuario', fn ($u) => $u
                ->where('ulid', $usuario->ulid)
                ->where('nome', $usuario->nome)
                ->where('email', $usuario->email)
                ->where('papel', 'cliente')
                ->where('papelRotulo', 'Cliente')
                ->where('emailVerificado', true)
                // `etc()` proibido de proposito: qualquer chave a mais quebra
                // o teste e obriga a decisao consciente de expor o campo.
            ));
});

it('nunca manda o hash da senha nem o id sequencial para o navegador', function () {
    $usuario = cliente();

    $html = $this->actingAs($usuario)->get('/painel')->getContent();

    expect($html)->not->toContain($usuario->password)
        ->and($html)->not->toContain('"id":'.$usuario->id)
        ->and($html)->not->toContain('remember_token')
        ->and($html)->not->toContain('google_id');
});

it('esconde os campos sensiveis na serializacao do model', function () {
    $vetor = cliente(['google_id' => 'g-123'])->toArray();

    expect($vetor)->not->toHaveKeys(['password', 'remember_token', 'google_id']);
});

it('nao expoe o usuario para quem nao entrou', function () {
    $this->get('/')
        ->assertInertia(fn ($pagina) => $pagina->where('auth.usuario', null));
});

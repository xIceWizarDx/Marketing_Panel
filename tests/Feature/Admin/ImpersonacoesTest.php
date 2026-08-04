<?php

it('lista os acessos de suporte', function () {
    $admin = admin(['nome' => 'Ana Admin']);
    $alvo = cliente(['nome' => 'Bruno Cliente']);

    session(['auth.password_confirmed_at' => time()]);
    $this->actingAs($admin)->post("/admin/impersonar/{$alvo->ulid}");
    $this->post('/sair-da-impersonacao');

    $this->actingAs($admin)
        ->get('/admin/impersonacoes')
        ->assertOk()
        ->assertInertia(fn ($pagina) => $pagina
            ->component('admin/impersonacoes')
            ->has('registros.data', 1)
            ->where('registros.data.0.admin', 'Ana Admin')
            ->where('registros.data.0.usuario', 'Bruno Cliente')
            ->where('registros.data.0.emAndamento', false)
            ->where('emAndamento', 0));
});

it('avisa quando ha acesso em andamento', function () {
    $admin = admin();
    $alvo = cliente();

    session(['auth.password_confirmed_at' => time()]);
    $this->actingAs($admin)->post("/admin/impersonar/{$alvo->ulid}");

    // Ainda impersonando: o admin nao alcanca /admin, entao consulta com outro.
    $this->actingAs(admin())
        ->get('/admin/impersonacoes')
        ->assertInertia(fn ($pagina) => $pagina->where('emAndamento', 1));
});

it('mostra "conta removida" quando o cliente apagou a conta', function () {
    $admin = admin();
    $alvo = cliente();

    session(['auth.password_confirmed_at' => time()]);
    $this->actingAs($admin)->post("/admin/impersonar/{$alvo->ulid}");
    $this->post('/sair-da-impersonacao');

    $this->actingAs($alvo)->delete('/minha-conta/perfil', ['password' => senhaDaFactory()]);

    // DEC-44: a pessoa some, o evento fica.
    $this->actingAs($admin)
        ->get('/admin/impersonacoes')
        ->assertInertia(fn ($pagina) => $pagina->where('registros.data.0.usuario', 'conta removida'));
});

it('barra o cliente', function () {
    $this->actingAs(cliente())->get('/admin/impersonacoes')->assertNotFound();
});

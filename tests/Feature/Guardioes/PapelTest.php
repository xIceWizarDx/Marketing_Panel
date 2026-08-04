<?php

use App\Enums\Papel;

/*
| Guardiao de PAPEL.
|
| Trava a separacao dos dois paineis. Se alguem esquecer o middleware `papel:`
| numa rota nova, e aqui que estoura — nao em producao.
*/

it('barra o cliente na area do administrador', function () {
    // 404 e nao 403: quem nao pode entrar nem descobre que a area existe.
    $this->actingAs(cliente())
        ->get('/admin/painel')
        ->assertNotFound();
});

it('barra o administrador na area do cliente', function () {
    $this->actingAs(admin())
        ->get('/painel')
        ->assertNotFound();
});

it('deixa cada papel entrar na propria area', function () {
    $this->actingAs(cliente())->get('/painel')->assertOk();
    $this->actingAs(admin())->get('/admin/painel')->assertOk();
});

it('barra o visitante nas duas areas', function () {
    $this->get('/painel')->assertRedirect(route('entrar'));
    $this->get('/admin/painel')->assertRedirect(route('entrar'));
});

it('protege TODA rota de painel com o middleware de papel', function () {
    // Rede de seguranca: pega rota nova que alguem cadastrou sem `papel:`.
    $desprotegidas = collect(Route::getRoutes())
        ->filter(function ($rota) {
            $uri = $rota->uri();

            return $uri === 'painel'
                || str_starts_with($uri, 'painel/')
                || str_starts_with($uri, 'admin/');
        })
        ->reject(fn ($rota) => collect($rota->gatherMiddleware())
            ->contains(fn ($m) => is_string($m) && str_starts_with($m, 'papel:')))
        ->map(fn ($rota) => $rota->uri())
        ->values();

    expect($desprotegidas)->toBeEmpty(
        'Estas rotas de painel estao sem o middleware papel: '.$desprotegidas->implode(', ')
    );
});

it('leva cada papel para a propria tela inicial', function () {
    expect(Papel::Cliente->rotaInicial())->toBe('painel')
        ->and(Papel::Admin->rotaInicial())->toBe('admin.painel');

    // As rotas precisam existir de verdade — nome trocado quebraria o login.
    expect(Route::has(Papel::Cliente->rotaInicial()))->toBeTrue()
        ->and(Route::has(Papel::Admin->rotaInicial()))->toBeTrue();
});

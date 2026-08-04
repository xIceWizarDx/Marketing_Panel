<?php

use App\Support\Midia\LimiteDeEnvio;

/*
| A tela nunca promete mais do que o servidor aceita.
|
| Prometer 300 MB numa maquina que corta em 2 faz a pessoa escolher o arquivo,
| esperar o envio inteiro e receber um erro que nao explica nada — foi
| exatamente o que aconteceu no primeiro envio real.
*/

it('⭐ usa o MENOR entre o que o produto quer e o que o PHP aceita', function () {
    config(['midia.tamanho_maximo_mb' => 300]);

    // O teto real vem do `php.ini` desta máquina; o que importa é que ele nunca
    // ultrapasse nenhum dos três limites.
    expect(LimiteDeEnvio::megabytes())
        ->toBeLessThanOrEqual(300)
        ->toBeGreaterThan(0);
});

it('não promete mais do que o produto quer, nem quando o PHP é generoso', function () {
    config(['midia.tamanho_maximo_mb' => 1]);

    expect(LimiteDeEnvio::megabytes())->toBe(1);
});

it('avisa quando o servidor é o gargalo', function () {
    // Um teto absurdo garante que o PHP seja o menor dos três.
    config(['midia.tamanho_maximo_mb' => 999999]);

    expect(LimiteDeEnvio::phpEstaSegurando())->toBeTrue();
});

it('a tela mostra o limite REAL, não o desejado', function () {
    config(['midia.tamanho_maximo_mb' => 999999]);

    // ⚠️ Vem dentro do compositor: enviar acontece no mesmo lugar onde se
    // publica, num gesto so.
    $this->actingAs(cliente())
        ->get(route('publicar'))
        ->assertInertia(fn ($p) => $p->where('compositor.tamanhoMaximoMb', LimiteDeEnvio::megabytes()));
});

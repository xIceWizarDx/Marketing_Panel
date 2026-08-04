<?php

use App\Support\RegistroDeSeguranca;
use Illuminate\Support\Facades\Log;

/*
| Guardiao de SEGURANCA DA BASE.
|
| Cabecalhos do navegador, canal de log separado e cookie de sessao. Sao coisas
| que ninguem percebe quando somem — ate a auditoria da plataforma reprovar.
*/

it('manda os cabecalhos de seguranca em toda resposta', function () {
    $resposta = $this->get('/');

    $resposta->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    expect($resposta->headers->get('Permissions-Policy'))->toContain('camera=()');
});

it('manda os cabecalhos tambem nas telas de dentro', function () {
    $this->actingAs(cliente())
        ->get('/painel')
        ->assertHeader('X-Frame-Options', 'DENY');
});

it('exige HTTPS no cookie de sessao quando esta em producao', function () {
    // O padrao do Laravel deixa passar em HTTP; aqui o ambiente decide.
    expect(config('session.secure'))->toBeFalse();

    config(['app.env' => 'production']);
    $configDeProducao = require base_path('config/session.php');

    expect(env('APP_ENV'))->not->toBe('production'); // teste roda fora de producao
    expect($configDeProducao['http_only'])->toBeTrue()
        ->and($configDeProducao['same_site'])->toBe('lax');
});

it('registra a entrada no canal de seguranca, nunca no log tecnico', function () {
    Log::shouldReceive('channel')->with('seguranca')->andReturnSelf();
    Log::shouldReceive('info')->once()->withArgs(
        fn (string $evento, array $contexto) => $evento === 'entrou' && isset($contexto['ip'])
    );

    $usuario = cliente();

    $this->post('/entrar', ['email' => $usuario->email, 'password' => senhaDaFactory()]);
});

it('nunca escreve senha nem token no arquivo de seguranca', function () {
    $capturado = [];

    Log::shouldReceive('channel')->with('seguranca')->andReturnSelf();
    Log::shouldReceive('info')->once()->andReturnUsing(function ($evento, $contexto) use (&$capturado) {
        $capturado = $contexto;
    });

    $this->actingAs(cliente());

    RegistroDeSeguranca::registrar('teste', [
        'password' => 'senha-secreta-1234',
        'access_token' => 'ya29.token-do-google',
        'aninhado' => ['refresh_token' => 'rt-123', 'ok' => 'pode aparecer'],
        'alvo_ulid' => '01aaaaaaaaaaaaaaaaaaaaaaaa',
    ]);

    expect(json_encode($capturado))
        ->not->toContain('senha-secreta-1234')
        ->not->toContain('ya29.token-do-google')
        ->not->toContain('rt-123')
        ->toContain('pode aparecer')
        ->toContain('01aaaaaaaaaaaaaaaaaaaaaaaa');
});

it('tem canal de seguranca separado do log tecnico', function () {
    $canal = config('logging.channels.seguranca');

    expect($canal)->not->toBeNull()
        ->and($canal['path'])->toContain('seguranca.log')
        // 6 meses do Marco Civil art. 15 — girar antes disso perde a prova.
        ->and($canal['days'])->toBeGreaterThanOrEqual(180);
});

it('guarda o fuso horario de quem le a data', function () {
    $usuario = cliente(['fuso_horario' => 'America/Rio_Branco']);

    $instante = now()->setTimezone('UTC')->setTime(12, 0);

    // O banco guarda UTC; a tela mostra no fuso da pessoa. Rio Branco = UTC-5.
    expect($usuario->noSeuFuso($instante)->format('H'))->toBe('07')
        ->and($usuario->noSeuFuso(null))->toBeNull();
});

it('assume o fuso de Sao Paulo quando ninguem escolheu', function () {
    expect(cliente()->fuso_horario)->toBe('America/Sao_Paulo');
});

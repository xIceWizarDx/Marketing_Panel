<?php

use App\Http\Middleware\CabecalhosDeSeguranca;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\VerificarPapel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            CabecalhosDeSeguranca::class,
        ]);

        $middleware->alias([
            'papel' => VerificarPapel::class,
        ]);

        // Visitante em area protegida cai na tela de entrada.
        // (O Laravel procuraria uma rota chamada `login`; a nossa se chama `entrar`.)
        $middleware->redirectGuestsTo(fn () => route('entrar'));

        // Quem ja entrou e tenta abrir /entrar volta pro painel do proprio papel.
        $middleware->redirectUsersTo(fn ($request) => route($request->user()->papel->rotaInicial()));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

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
        /*
         * ⭐ **O painel vive atrás de um proxy, e precisa saber disso.**
         *
         * Quem encerra o HTTPS é o proxy; para cá a requisição chega em texto
         * puro. Sem confiar nos cabeçalhos `X-Forwarded-*`, o Laravel conclui
         * que está em HTTP e monta **todo link e todo endereço de arquivo com
         * `http://`** — o navegador, que abriu a página em HTTPS, bloqueia cada
         * um deles por conteúdo misto. O resultado é uma tela em branco sem
         * erro nenhum no servidor.
         *
         * ⛔ E isso não é detalhe de desenvolvimento: quebra igual atrás do
         * nginx em produção, e quebra pior — porque lá o endereço temporário da
         * mídia (DEC-100) sairia em `http://`, e a rede recusaria buscar.
         *
         * ⚠️ `'*'` porque o endereço do proxy não é fixo nem conhecido de
         * antemão. É seguro enquanto a aplicação **só** for alcançável através
         * dele: quem fala direto com o PHP forjaria os cabeçalhos. No servidor,
         * isso significa não expor a porta do PHP para fora.
         */
        $middleware->trustProxies(at: '*');

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

<?php

use App\Enums\Papel;
use App\Http\Controllers\Admin\ClienteController;
use App\Http\Controllers\Admin\ImpersonacaoController;
use App\Http\Controllers\Admin\LogImpersonacaoController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
| Painel de quem opera a plataforma.
|
| A lista de papeis vem do enum (`Papel::listaDeOperadores()`), nao esta escrita
| aqui: quando entrar um papel de operador novo — comercial, suporte — estas
| rotas passam a aceita-lo sem ninguem precisar lembrar de editar este arquivo.
| O que cada papel PODE fazer dentro delas continua sendo decidido por Policy.
*/

Route::middleware(['auth', 'papel:'.Papel::listaDeOperadores()])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('painel', fn () => Inertia::render('admin/visao-geral'))->name('painel');

        Route::get('clientes', [ClienteController::class, 'listar'])->name('clientes.listar');
        Route::patch('clientes/{ulid}/acesso', [ClienteController::class, 'alternarAtivo'])->name('clientes.acesso');

        Route::get('impersonacoes', [LogImpersonacaoController::class, 'listar'])->name('impersonacoes.listar');

        // Entrar na conta de um cliente exige a senha de novo: sessao aberta
        // esquecida no computador nao pode virar acesso ao dado de terceiro.
        // `using()` aponta o middleware para a nossa rota em portugues — sem
        // isso ele procuraria uma rota chamada `password.confirm`.
        Route::post('impersonar/{ulid}', [ImpersonacaoController::class, 'iniciar'])
            ->middleware(RequirePassword::using('senha.confirmar'))
            ->name('impersonar');
    });

// Sair da impersonacao fica FORA do grupo `papel:admin`: durante a impersonacao
// o papel da sessao e o do cliente, e o admin ficaria preso sem poder voltar.
Route::middleware('auth')
    ->post('sair-da-impersonacao', [ImpersonacaoController::class, 'encerrar'])
    ->name('impersonacao.sair');

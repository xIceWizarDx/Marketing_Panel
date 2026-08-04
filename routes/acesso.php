<?php

use App\Http\Controllers\Acesso\AvisoVerificacaoEmailController;
use App\Http\Controllers\Acesso\CadastroController;
use App\Http\Controllers\Acesso\ConfirmarSenhaController;
use App\Http\Controllers\Acesso\EsqueciSenhaController;
use App\Http\Controllers\Acesso\RedefinirSenhaController;
use App\Http\Controllers\Acesso\ReenviarVerificacaoEmailController;
use App\Http\Controllers\Acesso\SessaoController;
use App\Http\Controllers\Acesso\VerificarEmailController;
use Illuminate\Support\Facades\Route;

/*
| Entrada, cadastro e senha.
|
| Caminhos e nomes em PT-BR. Onde o framework crava um nome em ingles
| (redirecionamento de visitante, link do email, confirmacao de senha), o desvio
| esta declarado em bootstrap/app.php e AppServiceProvider — em um lugar so.
*/

Route::middleware('guest')->group(function () {
    Route::get('cadastrar', [CadastroController::class, 'criar'])->name('cadastrar');
    Route::post('cadastrar', [CadastroController::class, 'salvar']);

    Route::get('entrar', [SessaoController::class, 'criar'])->name('entrar');
    Route::post('entrar', [SessaoController::class, 'salvar']);

    Route::get('esqueci-a-senha', [EsqueciSenhaController::class, 'criar'])->name('senha.solicitar');
    Route::post('esqueci-a-senha', [EsqueciSenhaController::class, 'salvar'])->name('senha.enviarLink');

    Route::get('redefinir-senha/{token}', [RedefinirSenhaController::class, 'criar'])->name('senha.redefinir');
    Route::post('redefinir-senha', [RedefinirSenhaController::class, 'salvar'])->name('senha.salvar');
});

Route::middleware('auth')->group(function () {
    Route::get('verificar-email', AvisoVerificacaoEmailController::class)->name('verificacao.aviso');

    Route::get('verificar-email/{id}/{hash}', VerificarEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verificacao.confirmar');

    Route::post('verificar-email/reenviar', [ReenviarVerificacaoEmailController::class, 'salvar'])
        ->middleware('throttle:6,1')
        ->name('verificacao.reenviar');

    Route::get('confirmar-senha', [ConfirmarSenhaController::class, 'mostrar'])->name('senha.confirmar');
    Route::post('confirmar-senha', [ConfirmarSenhaController::class, 'salvar']);

    Route::post('sair', [SessaoController::class, 'remover'])->name('sair');
});

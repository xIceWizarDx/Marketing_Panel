<?php

use App\Http\Controllers\MinhaConta\PerfilController;
use App\Http\Controllers\MinhaConta\SenhaController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
| Conta da propria pessoa — vale para admin e cliente.
*/

Route::middleware('auth')
    ->prefix('minha-conta')
    ->name('minha-conta.')
    ->group(function () {
        Route::redirect('/', '/minha-conta/perfil');

        Route::get('perfil', [PerfilController::class, 'editar'])->name('perfil.editar');
        Route::patch('perfil', [PerfilController::class, 'atualizar'])->name('perfil.atualizar');
        Route::delete('perfil', [PerfilController::class, 'remover'])->name('perfil.remover');

        Route::get('senha', [SenhaController::class, 'editar'])->name('senha.editar');
        Route::put('senha', [SenhaController::class, 'atualizar'])->name('senha.atualizar');

        Route::get('aparencia', fn () => Inertia::render('minha-conta/aparencia'))->name('aparencia');
    });

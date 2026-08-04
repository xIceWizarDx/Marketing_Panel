<?php

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Atalhos usados pelos testes
|--------------------------------------------------------------------------
*/

/** Cria um cliente. */
function cliente(array $atributos = []): Usuario
{
    return Usuario::factory()->create($atributos);
}

/** Cria um administrador. */
function admin(array $atributos = []): Usuario
{
    return Usuario::factory()->admin()->create($atributos);
}

/** A senha que a factory grava — evita repetir a string em cada teste. */
function senhaDaFactory(): string
{
    return 'senha-de-teste';
}

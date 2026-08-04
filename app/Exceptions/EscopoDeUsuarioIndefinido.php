<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Alguem consultou dado de cliente sem dizer de qual cliente.
 *
 * Esta excecao existe para NAO deixar isso passar em silencio. A alternativa
 * seria filtrar por `WHERE usuario_id IS NULL` — que nao retorna nada, nao dá
 * erro, e faz o recurso "funcionar" vazio. O caso classico e o worker da fila:
 * ele nao tem sessao, entao nao tem usuario autenticado.
 *
 * Como resolver, conforme o caso:
 *   • Job/comando  → ContextoDoUsuario::definir($usuarioId) antes de consultar
 *   • Admin/seeder → ContextoDoUsuario::semEscopo(fn () => ...)
 */
class EscopoDeUsuarioIndefinido extends RuntimeException
{
    public static function para(string $model): self
    {
        return new self(
            "Consulta a [{$model}] sem dono definido. ".
            'Em job ou comando, chame ContextoDoUsuario::definir($usuarioId); '.
            'para varrer todos os clientes de propósito, use ContextoDoUsuario::semEscopo().'
        );
    }
}

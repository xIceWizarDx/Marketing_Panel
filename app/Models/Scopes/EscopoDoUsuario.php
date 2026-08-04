<?php

namespace App\Models\Scopes;

use App\Exceptions\EscopoDeUsuarioIndefinido;
use App\Support\ContextoDoUsuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Filtra toda consulta pelo dono do dado.
 *
 * É a Camada 2 da segurança (0.M): um cliente nunca alcança o outro, mesmo que
 * alguém esqueça o `where` no controller.
 */
class EscopoDoUsuario implements Scope
{
    public function apply(Builder $consulta, Model $model): void
    {
        if (ContextoDoUsuario::estaSemEscopo()) {
            return;
        }

        $usuarioId = ContextoDoUsuario::id();

        // Sem dono definido NÃO vira `WHERE usuario_id IS NULL`: isso devolveria
        // lista vazia sem erro, e o bug só apareceria como "sumiu tudo".
        if ($usuarioId === null) {
            throw EscopoDeUsuarioIndefinido::para($model::class);
        }

        $consulta->where($model->qualifyColumn('usuario_id'), $usuarioId);
    }
}

<?php

namespace App\Models\Concerns;

use App\Exceptions\EscopoDeUsuarioIndefinido;
use App\Models\Scopes\EscopoDoUsuario;
use App\Models\Usuario;
use App\Support\ContextoDoUsuario;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marca um model como "dado de cliente".
 *
 * Quem usa esta trait ganha, de graça e sem chance de esquecer:
 *   • filtro automático por dono em TODA consulta;
 *   • `usuario_id` preenchido sozinho na criação;
 *   • relação `usuario()`.
 *
 * Toda tabela de cliente usa isto — `midias`, `contas_sociais`, `publicacoes`.
 * O padrão nasce com a primeira tabela de propósito: fazer isso depois de 9
 * tabelas prontas significa auditar 9 models e todos os seus testes.
 */
trait PertenceAoUsuario
{
    public static function bootPertenceAoUsuario(): void
    {
        static::addGlobalScope(new EscopoDoUsuario);

        static::creating(function ($model) {
            if ($model->usuario_id !== null) {
                return;
            }

            $usuarioId = ContextoDoUsuario::id();

            // Criar sem dono deixaria um registro órfão, invisível para todo
            // mundo (o escopo nunca o encontraria). Melhor recusar.
            if ($usuarioId === null) {
                throw EscopoDeUsuarioIndefinido::para($model::class);
            }

            $model->usuario_id = $usuarioId;
        });
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}

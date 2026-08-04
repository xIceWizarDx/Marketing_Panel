<?php

namespace App\Models;

use App\Enums\StatusPublicacao;
use App\Models\Concerns\PertenceAoUsuario;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Publicacao extends Model
{
    use HasFactory, HasUlids, PertenceAoUsuario;

    protected $table = 'publicacoes';

    // ⭐ `grupo_id` e GRAVADO, derivado das contas no envio (DEC-73/DEC-75).
    // Depois de gravado nao muda: mover canal de grupo nao pode alterar
    // retroativamente o numero historico de um grupo.
    protected $fillable = ['grupo_id', 'midia_id', 'titulo', 'legenda', 'hashtags', 'status', 'enviada_em'];

    protected function casts(): array
    {
        return [
            'status' => StatusPublicacao::class,
            'hashtags' => 'array',
            'enviada_em' => 'datetime',
        ];
    }

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function midia(): BelongsTo
    {
        return $this->belongsTo(Midia::class);
    }

    /** @return BelongsTo<Grupo, $this> */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    public function destinos(): HasMany
    {
        return $this->hasMany(Destino::class);
    }
}

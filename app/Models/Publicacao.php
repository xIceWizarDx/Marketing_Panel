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

    protected $fillable = ['midia_id', 'titulo', 'legenda', 'hashtags', 'status', 'enviada_em'];

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

    public function destinos(): HasMany
    {
        return $this->hasMany(Destino::class);
    }
}

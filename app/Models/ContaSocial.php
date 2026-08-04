<?php

namespace App\Models;

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Models\Concerns\PertenceAoUsuario;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ContaSocial extends Model
{
    use HasFactory, HasUlids, PertenceAoUsuario;

    protected $table = 'contas_sociais';

    protected $fillable = [
        // Escritor unico: `GrupoService`. Nasce com o grupo corrente e so muda
        // por acao explicita de mover canal (DEC-77).
        'grupo_id',
        'plataforma',
        'identificador_externo',
        'nome_exibicao',
        'avatar_url',
        'status',
        'status_detalhe',
    ];

    protected function casts(): array
    {
        return [
            'plataforma' => Plataforma::class,
            'status' => StatusConta::class,
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

    /** @return BelongsTo<Grupo, $this> */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    public function credencial(): HasOne
    {
        return $this->hasOne(Credencial::class);
    }

    public function destinos(): HasMany
    {
        return $this->hasMany(Destino::class);
    }

    public function podePublicar(): bool
    {
        return $this->status->podePublicar();
    }
}

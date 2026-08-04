<?php

namespace App\Models;

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Models\Concerns\PertenceAoUsuario;
use App\Services\GrupoService;
use App\Support\GrupoCorrente;
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

    /**
     * ⭐ Todo canal nasce no grupo em foco.
     *
     * ⚠️ Mora no model, e não nos serviços de conexão, porque há **quatro**
     * portas por onde uma conta nasce: Bluesky, YouTube, e a da Meta que cria
     * várias de uma vez (Página + Instagram). Espalhar por quatro lugares
     * garantiria esquecer um, e o esquecido faria a conta nascer sem grupo —
     * invisível em toda tela, depois de a pessoa já ter autorizado no Google.
     *
     * ⛔ Não sobrescreve valor já definido: é o que permite a factory montar
     * cenário e o serviço mover um canal de grupo sem briga.
     */
    protected static function booted(): void
    {
        static::creating(function (self $conta) {
            if ($conta->grupo_id !== null) {
                return;
            }

            $conta->grupo_id = GrupoCorrente::id()
                ?? app(GrupoService::class)->garantirPrincipal($conta->usuario)->id;
        });
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

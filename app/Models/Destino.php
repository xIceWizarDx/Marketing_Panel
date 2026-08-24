<?php

namespace App\Models;

use App\Enums\StatusDestino;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Uma publicacao numa conta.
 *
 * ⚠️ NAO usa `PertenceAoUsuario`: destino nao tem `usuario_id` proprio — o dono
 * vem da publicacao. O isolamento e garantido por SEMPRE chegar pela relacao do
 * pai (0.M Camada 2), nunca por consulta solta.
 *
 * ⛔ `status` nao se escreve aqui. Quem muda e o `PublicacaoService`, com os
 * metodos nomeados. Model com `update(['status' => ...])` espalhado foi como a
 * maquina de estados vira ficcao.
 */
class Destino extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'publicacao_id', 'conta_social_id',
        'titulo_override', 'legenda_override', 'hashtags_override', 'opcoes',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusDestino::class,
            'hashtags_override' => 'array',
            'opcoes' => 'array',
            'publicado_em' => 'datetime',
            // ⭐ Quando confirmamos pela ultima vez que continua no ar (DEC-145).
            'reconferido_em' => 'datetime',
            'metricas_lidas_em' => 'datetime',
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

    public function publicacao(): BelongsTo
    {
        return $this->belongsTo(Publicacao::class);
    }

    public function contaSocial(): BelongsTo
    {
        return $this->belongsTo(ContaSocial::class);
    }

    public function tentativas(): HasMany
    {
        return $this->hasMany(Tentativa::class);
    }

    /** DEC-11: texto proprio do destino, ou o da publicacao. */
    public function titulo(): ?string
    {
        return $this->titulo_override ?? $this->publicacao->titulo;
    }

    public function legenda(): ?string
    {
        return $this->legenda_override ?? $this->publicacao->legenda;
    }

    public function hashtags(): array
    {
        return $this->hashtags_override ?? $this->publicacao->hashtags ?? [];
    }

    /** Legenda pronta pra rede: texto + hashtags. */
    public function textoFinal(): string
    {
        $hashtags = array_map(fn (string $tag) => '#'.ltrim($tag, '#'), $this->hashtags());

        return trim(($this->legenda() ?? '').' '.implode(' ', $hashtags));
    }
}

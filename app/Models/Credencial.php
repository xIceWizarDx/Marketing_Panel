<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * O cofre (0.M Camada 1).
 *
 * ⛔ Estes campos NUNCA vao para log, resposta de API, exportacao ou tela —
 * nem para o admin impersonando. Por isso `$hidden` cobre tudo: mesmo um
 * `toArray()` distraido nao vaza.
 */
class Credencial extends Model
{
    protected $table = 'credenciais';

    protected $fillable = [
        'conta_social_id',
        'access_token',
        'refresh_token',
        'expira_em',
        'escopos',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    protected function casts(): array
    {
        return [
            // `encrypted`: a chave vive no .env, entao o dump do banco sozinho
            // nao serve para publicar no nome de ninguem.
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expira_em' => 'datetime',
            'escopos' => 'array',
        ];
    }

    public function contaSocial(): BelongsTo
    {
        return $this->belongsTo(ContaSocial::class);
    }

    public function expirada(): bool
    {
        return $this->expira_em !== null && $this->expira_em->isPast();
    }

    /** ⭐ DEC-32: avisar ANTES de quebrar, nao depois. */
    public function venceEmBreve(int $dias = 7): bool
    {
        return $this->expira_em !== null && $this->expira_em->isBefore(now()->addDays($dias));
    }
}

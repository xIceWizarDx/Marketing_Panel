<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tentativa extends Model
{
    protected $fillable = [
        'destino_id', 'numero', 'iniciada_em', 'finalizada_em',
        'sucesso', 'codigo_resposta', 'erro_mensagem',
    ];

    protected function casts(): array
    {
        return [
            'iniciada_em' => 'datetime',
            'finalizada_em' => 'datetime',
            'sucesso' => 'boolean',
        ];
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(Destino::class);
    }

    /** Tentativa em aberto = worker ainda trabalhando (ou morreu no meio). */
    public function emAberto(): bool
    {
        return $this->finalizada_em === null;
    }
}

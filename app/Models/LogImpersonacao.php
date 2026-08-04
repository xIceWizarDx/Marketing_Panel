<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma sessao de impersonacao: quem entrou, em quem, quando e de onde.
 *
 * Registro so cresce — nunca e editado nem apagado, exceto o carimbo de saida.
 */
class LogImpersonacao extends Model
{
    protected $table = 'logs_impersonacao';

    protected $fillable = [
        'admin_id',
        'usuario_id',
        'admin_ulid',
        'usuario_ulid',
        'iniciada_em',
        'finalizada_em',
        'ip',
        'agente_usuario',
    ];

    protected function casts(): array
    {
        return [
            'iniciada_em' => 'datetime',
            'finalizada_em' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'admin_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function emAndamento(): bool
    {
        return $this->finalizada_em === null;
    }
}

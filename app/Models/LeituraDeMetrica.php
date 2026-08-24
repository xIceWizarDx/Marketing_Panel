<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A leitura de um dia — o que permite desenhar a curva de vida do post (DEC-144).
 *
 * ⛔ **Escritor único: `AtualizarMetricas`.** Ninguém mais grava aqui, e por isso
 * não há `fillable`: métrica é escrita por máquina, a partir do que a rede
 * respondeu, e não por formulário.
 */
class LeituraDeMetrica extends Model
{
    protected $table = 'leituras_de_metrica';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'dia' => 'date:Y-m-d',
            'visualizacoes' => 'integer',
            'curtidas' => 'integer',
            'comentarios' => 'integer',
            'compartilhamentos' => 'integer',
        ];
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(Destino::class);
    }
}

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

    /**
     * ⭐ DEC-32: avisar ANTES de quebrar, nao depois.
     *
     * ⛔ **`expira_em` sozinho NAO responde isso.** No YouTube ele guarda o token
     * de acesso, que dura **1 hora** e o `TokenDoGoogle` renova sozinho — comparar
     * essa data com "vence nos proximos 7 dias" da verdadeiro SEMPRE. O aviso
     * aparecia para todo mundo, o tempo todo, sem significar nada; e alerta que
     * nunca desliga e a forma mais rapida de ensinar alguem a ignorar alertas.
     *
     * A pergunta certa e **"ainda da para renovar?"**. Tendo `refresh_token`, da:
     * o vencimento de hora em hora e encanamento, nao noticia. Quando a renovacao
     * falha de verdade (`invalid_grant`), a conta vira `expirada` e quem avisa e o
     * status — nao este metodo.
     *
     * ⚠️ Le o valor CRU: aqui so importa se existe, nunca o que ele e. Assim o
     * segredo nem chega a ser decifrado para responder um booleano.
     */
    public function venceEmBreve(int $dias = 7): bool
    {
        if ($this->getRawOriginal('refresh_token') !== null) {
            return false;
        }

        return $this->expira_em !== null && $this->expira_em->isBefore(now()->addDays($dias));
    }
}

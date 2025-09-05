<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthCredential extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'platform_account_id',
        'access_token',
        'refresh_token',
        'token_type',
        'scope',
        'expires_at',
        'obtained_at',
        'revoked_at',
        'error_last',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'obtained_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function platformAccount(): BelongsTo
    {
        return $this->belongsTo(PlatformAccount::class);
    }
}


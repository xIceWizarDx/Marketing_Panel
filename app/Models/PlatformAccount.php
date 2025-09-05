<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlatformAccount extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'platform',
        'provider_account_id',
        'account_username',
        'account_email',
        'connection_status',
        'is_connected',
        'last_sync_at',
        'last_sync_error',
        'settings',
        'stats',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_connected' => 'boolean',
            'last_sync_at' => 'datetime',
            'settings' => 'array',
            'stats' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function oauthCredentials(): HasMany
    {
        return $this->hasMany(OauthCredential::class);
    }

    public function latestCredential(): HasOne
    {
        return $this->hasOne(OauthCredential::class)->latestOfMany('obtained_at');
    }

    public function postPlatforms(): HasMany
    {
        return $this->hasMany(PostPlatform::class);
    }
}

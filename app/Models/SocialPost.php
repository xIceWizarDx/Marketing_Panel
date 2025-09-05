<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialPost extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'caption',
        'hashtags',
        'status',
        'publish_type',
        'timezone',
        'scheduled_at',
        'published_at',
        'fail_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function platforms(): HasMany
    {
        return $this->hasMany(PostPlatform::class, 'post_id');
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(MediaFile::class, 'post_media', 'post_id', 'media_file_id')
            ->withPivot('position')
            ->orderBy('post_media.position');
    }
}


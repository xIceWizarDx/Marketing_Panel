<?php

namespace Database\Factories;

use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialPost>
 */
class SocialPostFactory extends Factory
{
    protected $model = SocialPost::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['draft', 'scheduled', 'published', 'failed']);
        $scheduled = $status === 'scheduled' ? now()->addDays(fake()->numberBetween(1, 14)) : null;
        $published = $status === 'published' ? now()->subDays(fake()->numberBetween(1, 14)) : null;
        return [
            'user_id' => User::factory(),
            'caption' => fake()->sentence(10),
            'hashtags' => '#marketing #social #brand',
            'status' => $status,
            'publish_type' => $status === 'draft' ? 'scheduled' : fake()->randomElement(['now', 'scheduled']),
            'timezone' => 'UTC',
            'scheduled_at' => $scheduled,
            'published_at' => $published,
            'fail_reason' => $status === 'failed' ? fake()->sentence(6) : null,
        ];
    }
}


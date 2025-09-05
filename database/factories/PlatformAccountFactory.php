<?php

namespace Database\Factories;

use App\Models\PlatformAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformAccount>
 */
class PlatformAccountFactory extends Factory
{
    protected $model = PlatformAccount::class;

    public function definition(): array
    {
        $platform = fake()->randomElement(['instagram','facebook','youtube','tiktok','twitter','linkedin','pinterest','google_ads']);
        $status = fake()->randomElement(['connected','expired','revoked','error']);
        return [
            'user_id' => User::factory(),
            'platform' => $platform,
            'provider_account_id' => (string) fake()->numberBetween(1000000, 9999999),
            'account_username' => fake()->userName(),
            'account_email' => fake()->safeEmail(),
            'connection_status' => $status,
            'is_connected' => $status === 'connected',
            'last_sync_at' => now()->subMinutes(fake()->numberBetween(10, 2000)),
            'settings' => null,
            'stats' => null,
        ];
    }
}


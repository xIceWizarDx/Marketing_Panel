<?php

namespace Database\Factories;

use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaFile>
 */
class MediaFileFactory extends Factory
{
    protected $model = MediaFile::class;

    public function definition(): array
    {
        $w = fake()->randomElement([640, 800, 1024, 1280]);
        $h = fake()->randomElement([480, 600, 768, 960]);
        return [
            'user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'url' => sprintf('https://picsum.photos/seed/%s/%d/%d', fake()->uuid(), $w, $h),
            'mime_type' => 'image/jpeg',
            'size_bytes' => fake()->numberBetween(30_000, 500_000),
            'width' => $w,
            'height' => $h,
            'description' => fake()->optional()->sentence(),
            'uploaded_at' => now()->subDays(fake()->numberBetween(0, 30)),
        ];
    }
}


<?php

namespace Database\Factories;

use App\Models\MediaTag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaTag>
 */
class MediaTagFactory extends Factory
{
    protected $model = MediaTag::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->word()),
        ];
    }
}


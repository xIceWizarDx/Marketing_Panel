<?php

namespace Database\Seeders;

use App\Models\MediaFile;
use App\Models\MediaTag;
use App\Models\PlatformAccount;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);

        PlatformAccount::factory()->count(4)->create(['user_id' => $user->id]);

        $tags = MediaTag::factory()->count(5)->create();
        $media = MediaFile::factory()->count(12)->create(['user_id' => $user->id]);
        foreach ($media as $m) {
            $m->tags()->attach($tags->random(rand(0, 3))->pluck('id'));
        }

        SocialPost::factory()->count(8)->create(['user_id' => $user->id]);
    }
}


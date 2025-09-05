<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_platforms', function (Blueprint $table) {
            // Allow multiple accounts of the same platform per post.
            // Drop the old unique on (post_id, platform) if it exists
            try {
                $table->dropUnique(['post_id', 'platform']);
            } catch (\Throwable $e) {
                // ignore if index name differs or does not exist in environment
            }

            // Add a more precise unique constraint per selected account
            $table->unique(['post_id', 'platform_account_id'], 'post_platforms_post_id_platform_account_unique');

            // Helpful index for filtering by platform name if used
            if (!app()->runningUnitTests()) {
                try { $table->index('platform'); } catch (\Throwable $e) {}
            }
        });
    }

    public function down(): void
    {
        Schema::table('post_platforms', function (Blueprint $table) {
            // Revert to the previous unique layout
            try {
                $table->dropUnique('post_platforms_post_id_platform_account_unique');
            } catch (\Throwable $e) {
            }
            try {
                $table->unique(['post_id', 'platform']);
            } catch (\Throwable $e) {
            }
        });
    }
};


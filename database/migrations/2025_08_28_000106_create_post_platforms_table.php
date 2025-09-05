<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_platforms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->string('platform', 50);
            $table->foreignId('platform_account_id')->nullable()->constrained('platform_accounts')->nullOnDelete();
            $table->string('status', 20)->default('scheduled');
            $table->dateTime('scheduled_for')->nullable();
            $table->dateTime('posted_at')->nullable();
            $table->string('external_post_id', 191)->nullable();
            $table->text('error_message')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->unique(['post_id', 'platform']);
            $table->index('platform');
            $table->index('status');
            $table->index('scheduled_for');
            $table->index('platform_account_id');
            $table->unique(['platform', 'external_post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_platforms');
    }
};


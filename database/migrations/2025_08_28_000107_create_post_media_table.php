<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_media', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->foreignId('media_file_id')->constrained('media_files')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);

            $table->primary(['post_id', 'media_file_id']);
            $table->index('media_file_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_media');
    }
};


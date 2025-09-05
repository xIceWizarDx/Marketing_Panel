<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 50);
            $table->string('provider_account_id', 191)->nullable();
            $table->string('account_username', 150)->nullable();
            $table->string('account_email', 191)->nullable();
            $table->string('connection_status', 20)->default('connected');
            $table->boolean('is_connected')->default(false);
            $table->dateTime('last_sync_at')->nullable();
            $table->text('last_sync_error')->nullable();
            $table->json('settings')->nullable();
            $table->json('stats')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->unique(['user_id', 'platform', 'provider_account_id']);
            $table->index('is_connected');
            $table->index('last_sync_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_accounts');
    }
};


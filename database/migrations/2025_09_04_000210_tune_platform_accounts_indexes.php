<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_accounts', function (Blueprint $table) {
            try { $table->index('platform'); } catch (\Throwable $e) {}
            try { $table->index('connection_status'); } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        Schema::table('platform_accounts', function (Blueprint $table) {
            try { $table->dropIndex(['platform']); } catch (\Throwable $e) {}
            try { $table->dropIndex(['connection_status']); } catch (\Throwable $e) {}
        });
    }
};


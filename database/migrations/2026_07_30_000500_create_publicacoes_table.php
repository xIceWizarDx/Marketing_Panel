<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publicacoes', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();

            // `restrict`: midia usada em publicacao nao pode sumir do disco —
            // levaria junto a prova de o que foi publicado.
            $table->foreignId('midia_id')->constrained('midias')->restrictOnDelete();

            $table->string('titulo')->nullable();
            $table->text('legenda')->nullable();
            $table->json('hashtags')->nullable();

            // Derivado dos destinos — escrito so por recalcularStatus().
            $table->string('status')->default('rascunho')->index();
            $table->timestamp('enviada_em')->nullable();

            $table->timestamps();
            $table->index(['usuario_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publicacoes');
    }
};

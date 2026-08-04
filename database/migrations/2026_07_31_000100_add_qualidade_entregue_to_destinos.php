<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ A qualidade que a rede DIZ que entregou.
 *
 * No YouTube vem `hd` ou `sd` (campo `contentDetails.definition`). Enviamos
 * 1080x1920: se voltar `sd`, a plataforma esta admitindo que degradou o video.
 *
 * E a diferenca entre "achamos que degradou" e "o YouTube disse que esta em SD"
 * — e e a prova que sustenta a promessa central do produto (DEC-33).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('destinos', function (Blueprint $table) {
            // Curta de proposito: guarda o que a rede respondeu, sem traduzir.
            $table->string('qualidade_entregue', 20)->nullable()->after('url_publicada');
        });
    }

    public function down(): void
    {
        Schema::table('destinos', function (Blueprint $table) {
            $table->dropColumn('qualidade_entregue');
        });
    }
};

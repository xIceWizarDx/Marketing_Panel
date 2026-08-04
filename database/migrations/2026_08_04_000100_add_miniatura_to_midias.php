<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ A miniatura — o que faz o historico ser reconhecivel.
 *
 * Sem ela, tres videos baixados do WhatsApp na mesma tarde viram tres linhas
 * identicas, e escolher o errado nao tem desfazer.
 *
 * Pesa ~40 KB contra ~20 MB do video: 500x menor. E por isso que ela pode ser
 * guardada para sempre, mesmo quando o arquivo original for apagado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('midias', function (Blueprint $table) {
            // Anulavel: ffmpeg pode faltar no servidor, e midia sem miniatura
            // ainda e midia. O envio nunca pode falhar por causa disto.
            $table->string('miniatura')->nullable()->after('caminho');
        });
    }

    public function down(): void
    {
        Schema::table('midias', function (Blueprint $table) {
            $table->dropColumn('miniatura');
        });
    }
};

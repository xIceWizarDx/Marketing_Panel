<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ O arquivo vive enquanto tem funcao (DEC-54).
 *
 * `assinatura` — o conteudo do arquivo resumido. Serve a duas coisas: reencontrar
 * a midia quando a pessoa reenviar o mesmo video, e **provar** que o que foi para
 * duas redes era byte por byte o mesmo arquivo (DEC-58).
 *
 * `arquivo_removido_em` — quando o original saiu do disco. Nulo = ele esta aqui.
 * O registro sobrevive: miniatura, laudo, links e prova continuam (DEC-56).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('midias', function (Blueprint $table) {
            // 64 caracteres: hexadecimal de um SHA-256.
            $table->string('assinatura', 64)->nullable()->after('caminho');
            $table->timestamp('arquivo_removido_em')->nullable()->after('miniatura');

            // ⚠️ Composto com o dono: a busca por assinatura SEMPRE acontece
            // dentro do escopo de um cliente. Arquivo igual de outro cliente e
            // outro registro — reaproveitar cruzaria dado entre contas.
            $table->index(['usuario_id', 'assinatura']);
        });
    }

    public function down(): void
    {
        Schema::table('midias', function (Blueprint $table) {
            $table->dropIndex(['usuario_id', 'assinatura']);
            $table->dropColumn(['assinatura', 'arquivo_removido_em']);
        });
    }
};

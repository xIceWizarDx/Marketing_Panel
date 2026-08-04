<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A conta social passa a pertencer a um grupo (DEC-70).
 *
 * ⚠️ Nasce NULLABLE de proposito: quem ja usa o painel tem contas conectadas, e
 * apertar para NOT NULL antes de o preenchimento rodar deixaria o banco de
 * desenvolvimento entalado. O aperto vem em migration separada, depois do
 * comando `grupos:garantir-principal`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contas_sociais', function (Blueprint $tabela) {
            /*
             * ⛔ `grupo_id` NAO entra na unica (usuario_id, plataforma,
             * identificador_externo).
             *
             * Dentro dela, o MESMO canal do YouTube poderia ser conectado em
             * dois grupos do mesmo dono — que e exatamente o que a unica existe
             * para impedir, e o que a DEC-70 proibe.
             */
            $tabela->foreignId('grupo_id')
                ->nullable()
                ->after('usuario_id')
                ->constrained('grupos')
                ->restrictOnDelete();

            // "Os canais deste grupo" e a consulta de toda tela do cliente.
            $tabela->index(['usuario_id', 'grupo_id']);
        });
    }

    public function down(): void
    {
        Schema::table('contas_sociais', function (Blueprint $tabela) {
            // ⚠️ O indice cai ANTES da coluna: o SQLite recusa dropar coluna
            // que ainda participa de indice, e a reversao quebrada so aparece
            // no dia em que alguem precisa dela.
            $tabela->dropIndex(['usuario_id', 'grupo_id']);
            $tabela->dropConstrainedForeignId('grupo_id');
        });
    }
};

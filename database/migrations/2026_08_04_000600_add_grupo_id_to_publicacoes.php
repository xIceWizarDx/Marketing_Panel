<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ A publicacao GRAVA o grupo — nao o deduz do canal (DEC-75).
 *
 * ⚠️ E excecao consciente a regra "derivado nunca vira coluna". Deduzir pelo
 * canal faria o numero historico de um grupo MUDAR SOZINHO quando alguem
 * reorganizasse os canais (DEC-77 permite mover). Numero que muda
 * retroativamente nao serve para decidir nada.
 *
 * O valor vem das contas escolhidas no envio, com o servidor recusando contas
 * de grupos diferentes na mesma publicacao (DEC-73).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publicacoes', function (Blueprint $tabela) {
            $tabela->foreignId('grupo_id')
                ->nullable()
                ->after('usuario_id')
                ->constrained('grupos')
                ->restrictOnDelete();

            // "As publicacoes deste grupo, mais recentes primeiro" — a tela
            // mais aberta do produto.
            $tabela->index(['usuario_id', 'grupo_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('publicacoes', function (Blueprint $tabela) {
            // O indice cai antes da coluna: ver a migration das contas.
            $tabela->dropIndex(['usuario_id', 'grupo_id', 'created_at']);
            $tabela->dropConstrainedForeignId('grupo_id');
        });
    }
};

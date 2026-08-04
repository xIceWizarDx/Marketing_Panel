<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O cofre. Este e o ativo mais valioso do sistema (0.M): quem tem o token
 * publica no nome do cliente.
 *
 * Tabela separada de `contas_sociais` de proposito: a conta e listada, exibida e
 * serializada o tempo todo; o token nao pode ir junto por descuido. Aqui os
 * campos tem cast `encrypted` — o dump do banco sozinho nao serve pra nada,
 * porque a chave vive no .env.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credenciais', function (Blueprint $table) {
            $table->id();

            // 1:1 e `cascade`: token nao sobrevive a conta. Desconectar apaga a
            // credencial e PRESERVA a conta (o historico aponta pra ela).
            $table->foreignId('conta_social_id')->unique()->constrained('contas_sociais')->cascadeOnDelete();

            // `text` e nao `string`: o valor cifrado e bem maior que o original.
            $table->text('access_token');
            $table->text('refresh_token')->nullable();

            $table->timestamp('expira_em')->nullable()->index();
            $table->json('escopos')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credenciais');
    }
};

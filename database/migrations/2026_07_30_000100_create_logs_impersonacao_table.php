<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de impersonacao (DEC-04).
 *
 * Guardar quem entrou na conta de quem, quando entrou e quando saiu, e o que
 * separa suporte legitimo de acesso indevido. Sem esse registro nao ha como
 * responder "quem viu meu dado?" — e essa resposta e obrigacao da LGPD.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs_impersonacao', function (Blueprint $table) {
            $table->id();

            // Quem entrou (sempre um operador) e em quem entrou.
            //
            // `nullOnDelete` e nao `restrict`: com restrict, quem ja recebeu
            // suporte NUNCA MAIS conseguiria apagar a propria conta — o
            // direito de eliminacao (LGPD art. 18) ficaria bloqueado por um log.
            $table->foreignId('admin_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();

            // Apelido que sobrevive a exclusao da conta.
            //
            // Sem isto, apagar o usuario deixaria uma linha so de nulos — o log
            // existiria mas nao responderia "quantos acessos de suporte houve
            // naquela conta?", que e justamente a pergunta de uma auditoria.
            // O ULID e opaco: sem a linha em `usuarios`, nao identifica ninguem.
            $table->ulid('usuario_ulid')->index();
            $table->ulid('admin_ulid');

            $table->timestamp('iniciada_em');
            $table->timestamp('finalizada_em')->nullable();

            // Contexto tecnico de quem acessou — usado em auditoria.
            $table->string('ip', 45)->nullable();
            $table->text('agente_usuario')->nullable();

            $table->timestamps();

            $table->index(['usuario_id', 'iniciada_em']);
            $table->index(['admin_id', 'iniciada_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_impersonacao');
    }
};

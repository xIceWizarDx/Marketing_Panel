<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uma publicacao numa conta. E o coracao do motor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destinos', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->foreignId('publicacao_id')->constrained('publicacoes')->cascadeOnDelete();
            // `restrict`: desconectar a conta nao apaga o historico do que ja foi
            // publicado por ela. Desconectar muda o STATUS da conta, nao deleta.
            $table->foreignId('conta_social_id')->constrained('contas_sociais')->restrictOnDelete();

            $table->string('status')->default('pendente')->index();

            // DEC-11 — texto por destino. `null` = usa o da publicacao.
            $table->string('titulo_override')->nullable();
            $table->text('legenda_override')->nullable();
            $table->json('hashtags_override')->nullable();

            // Escolhas que a rede exige por post (privacidade do TikTok,
            // visibilidade do YouTube, declaracao de conteudo comercial...).
            $table->json('opcoes')->nullable();

            // Id do post NA REDE, preenchido quando ela aceita.
            $table->string('identificador_externo')->nullable();

            // ⚠️ ANTI-DOUBLE-POST: gravado ANTES do efeito irreversivel
            // (session URI do upload resumavel, creation_id do container). No
            // retry, o motor RETOMA por este handle em vez de recomecar — sem
            // ele, uma re-entrega da fila publica o mesmo video duas vezes.
            $table->string('handle_externo')->nullable();

            // ⭐ A PROVA (DEC-31): o link do post, lido de volta da rede.
            $table->string('url_publicada')->nullable();

            $table->text('erro_mensagem')->nullable();
            $table->unsignedTinyInteger('tentativas_feitas')->default(0);
            $table->timestamp('publicado_em')->nullable();

            $table->timestamps();

            // Impede DOIS destinos para a mesma conta na mesma publicacao.
            // ⚠️ Nao impede o mesmo destino postar duas vezes — quem impede isso
            // e o `handle_externo` + a verificacao antes de enviar.
            $table->unique(['publicacao_id', 'conta_social_id'], 'destinos_conta_unica');

            // O watchdog varre por status + tempo parado.
            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destinos');
    }
};

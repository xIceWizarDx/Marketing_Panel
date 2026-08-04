<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada vez que o motor tentou enviar um destino.
 *
 * Serve para tres coisas: contar retentativas, alimentar o watchdog (destino
 * `enviando` sem tentativa ativa ha muito tempo = worker morreu), e responder ao
 * cliente "por que demorou/falhou" com fato, nao com suposicao.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tentativas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destino_id')->constrained('destinos')->cascadeOnDelete();

            $table->unsignedTinyInteger('numero');
            $table->timestamp('iniciada_em');
            $table->timestamp('finalizada_em')->nullable();

            $table->boolean('sucesso')->default(false);

            // Codigo HTTP ou codigo de erro da rede — o que permite decidir
            // entre "tenta de novo" e "desiste".
            $table->string('codigo_resposta')->nullable();
            $table->text('erro_mensagem')->nullable();

            $table->timestamps();

            // O watchdog procura tentativa em aberto por destino.
            $table->index(['destino_id', 'finalizada_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tentativas');
    }
};

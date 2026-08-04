<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('midias', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            // `restrict`: apagar cliente com midia exige decisao explicita, nunca
            // cascata silenciosa. Quem apaga e o servico de exclusao de conta,
            // que remove os arquivos do disco antes de remover as linhas.
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();

            $table->string('tipo')->index();
            $table->string('nome_original');

            // Caminho relativo ao disco privado — nunca sob a raiz publica.
            $table->string('caminho');

            // Descoberto por conteudo (magic bytes), nunca pela extensao enviada.
            $table->string('mime_type');
            $table->unsignedBigInteger('tamanho_bytes');

            $table->unsignedInteger('duracao_segundos')->nullable();
            $table->unsignedInteger('largura')->nullable();
            $table->unsignedInteger('altura')->nullable();

            // Laudo do ffprobe. `null` = ainda nao inspecionado (ou ferramenta
            // indisponivel no servidor); nao confundir com "inspecionado e vazio".
            $table->json('laudo')->nullable();

            $table->timestamps();

            // Toda listagem e "as midias deste cliente, mais recentes primeiro".
            $table->index(['usuario_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('midias');
    }
};

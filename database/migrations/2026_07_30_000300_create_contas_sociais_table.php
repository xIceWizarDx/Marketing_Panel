<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas_sociais', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();

            $table->string('plataforma')->index();

            // Id do canal/página/perfil NA REDE. Nome sempre igual em todas as
            // tabelas (glossário): sem `youtube_channel_id`, `ig_user_id` etc.
            $table->string('identificador_externo');

            $table->string('nome_exibicao');
            $table->string('avatar_url')->nullable();

            $table->string('status')->default('ativa')->index();
            // Motivo em português do problema — o que a tela mostra.
            $table->string('status_detalhe')->nullable();

            $table->timestamps();

            // DEC-10: várias contas por rede, mas a MESMA conta não entra duas
            // vezes — senão o mesmo vídeo iria em duplicidade para o mesmo lugar.
            $table->unique(['usuario_id', 'plataforma', 'identificador_externo'], 'contas_sociais_conta_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_sociais');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            // Identificador publico usado nas rotas — o id sequencial nunca sai do servidor (0.M).
            $table->ulid('ulid')->unique();
            $table->string('nome');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            // Null quando a conta nasce pelo Google ou pelo convite do admin (senha vem depois).
            $table->string('password')->nullable();
            $table->string('google_id')->nullable()->unique();
            $table->string('papel')->default('cliente')->index();
            $table->boolean('ativo')->default(true);

            // O banco guarda tudo em UTC; esta coluna diz em que fuso a pessoa
            // LE a data. O Brasil tem 4 fusos, e o produto inteiro gira em
            // torno de "que horas isso foi publicado" — sem ela, quem esta em
            // Rio Branco ve o horario de Sao Paulo e acha que o post atrasou.
            $table->string('fuso_horario', 64)->default('America/Sao_Paulo');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            // `user_id` e cravado no DatabaseSessionHandler do Laravel — renomear aqui
            // quebra a gravacao de sessao. Territorio do framework, fica em ingles.
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

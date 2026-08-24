<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ Onde esta conta MORA — só para rede federada.
 *
 * ⚠️ O Mastodon não é um serviço: é um protocolo com milhares de servidores
 * independentes, cada um com endereço, regras e aplicativo próprios. Duas contas
 * do painel podem estar no mesmo Mastodon e em servidores diferentes, e toda
 * chamada de API precisa saber em qual.
 *
 * ⛔ A alternativa era derivar o servidor do nome de exibição (`@alguem@casa.social`).
 * Isso monta endereço de API a partir de texto de tela — e no dia em que o nome
 * mudar, as chamadas vão para o servidor errado ou para nenhum.
 *
 * Fica `null` em todas as outras redes: elas têm um endereço só, e ele mora no
 * publicador.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contas_sociais', function (Blueprint $tabela) {
            $tabela->string('servidor')->nullable()->after('identificador_externo');
        });
    }

    public function down(): void
    {
        Schema::table('contas_sociais', function (Blueprint $tabela) {
            $tabela->dropColumn('servidor');
        });
    }
};

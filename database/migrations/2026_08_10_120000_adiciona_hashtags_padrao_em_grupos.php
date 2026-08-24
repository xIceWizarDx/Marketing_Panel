<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ As hashtags que já vêm escritas ao compor neste grupo (DEC-152).
 *
 * ⚠️ **Elas moram no GRUPO, e não na conta nem no usuário**, porque é o grupo
 * que separa linhas de conteúdo (DEC-69). Quem tem um canal de notícias e um de
 * novelas escreve `#noticias` cem vezes por mês num, e nunca no outro.
 *
 * ⛔ **É ponto de PARTIDA, não carimbo.** O texto continua editável no
 * compositor, e o que sobe é o que está escrito lá na hora de publicar — nunca
 * o que estava guardado aqui. Fosse carimbo, seria uma decisão do sistema sobre
 * o conteúdo de alguém.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grupos', function (Blueprint $tabela) {
            // Mesmo formato de `publicacoes.hashtags`: lista limpa, sem `#` e
            // sem espaço. Guardar com `#` obrigaria cada rede a desfazer isso.
            $tabela->json('hashtags')->nullable()->after('nome');
        });
    }

    public function down(): void
    {
        Schema::table('grupos', function (Blueprint $tabela) {
            $tabela->dropColumn('hashtags');
        });
    }
};

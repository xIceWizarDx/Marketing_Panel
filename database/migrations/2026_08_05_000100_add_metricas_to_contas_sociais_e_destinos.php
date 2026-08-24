<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Os contadores que a rede publica, guardados ao lado da prova.
 *
 * ⭐ **Todas as colunas são anuláveis, e o nulo tem significado** (DEC-95):
 * *"esta rede não publica este número"*. Default `0` seria afirmar um fato que
 * ninguém verificou — e no Bluesky, onde visualização não existe no protocolo, o
 * zero seria uma mentira permanente.
 *
 * ⛔ **Não há tabela de histórico** (DEC-97). Sem gráfico ao longo do tempo não
 * existe foto diária para guardar, e as Políticas do YouTube proíbem criar
 * métrica derivada dos dados deles — *"ganhou 12 inscritos hoje"* calculado
 * subtraindo duas fotos nossas é exatamente isso. Quando o gráfico existir, ele
 * nasce de `subscribersGained` da Analytics API, não de conta nossa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contas_sociais', function (Blueprint $tabela) {
            $tabela->unsignedBigInteger('seguidores')->nullable()->after('avatar_url');
            $tabela->timestamp('metricas_lidas_em')->nullable()->after('seguidores');
        });

        Schema::table('destinos', function (Blueprint $tabela) {
            $tabela->unsignedBigInteger('visualizacoes')->nullable()->after('publicado_em');
            $tabela->unsignedBigInteger('curtidas')->nullable()->after('visualizacoes');
            $tabela->unsignedBigInteger('comentarios')->nullable()->after('curtidas');
            $tabela->unsignedBigInteger('compartilhamentos')->nullable()->after('comentarios');
            $tabela->timestamp('metricas_lidas_em')->nullable()->after('compartilhamentos');
        });
    }

    public function down(): void
    {
        Schema::table('contas_sociais', function (Blueprint $tabela) {
            $tabela->dropColumn(['seguidores', 'metricas_lidas_em']);
        });

        Schema::table('destinos', function (Blueprint $tabela) {
            $tabela->dropColumn(['visualizacoes', 'curtidas', 'comentarios', 'compartilhamentos', 'metricas_lidas_em']);
        });
    }
};

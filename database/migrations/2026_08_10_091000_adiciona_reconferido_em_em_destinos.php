<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ **Quando foi a última vez que conferimos que este post continua no ar**
 * (DEC-145).
 *
 * ⚠️ Sem esta data, "No ar" é uma afirmação sem prazo — e afirmação sem prazo
 * envelhece em silêncio. Com ela, a tela pode dizer *"no ar · conferido hoje"*,
 * que é o que separa este produto de quem só olhou uma vez.
 *
 * ⛔ É diferente de `publicado_em`: aquele é quando subiu, este é quando
 * confirmamos pela última vez que continua lá.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('destinos', function (Blueprint $tabela) {
            $tabela->timestamp('reconferido_em')->nullable()->after('publicado_em');
        });
    }

    public function down(): void
    {
        Schema::table('destinos', function (Blueprint $tabela) {
            $tabela->dropColumn('reconferido_em');
        });
    }
};

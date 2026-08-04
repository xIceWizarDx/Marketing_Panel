<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fecha a porta: canal e publicacao passam a EXIGIR grupo.
 *
 * ⚠️ **Vem depois do preenchimento, nunca junto.** Enquanto `grupo_id` for
 * nulavel, uma linha solta e um dado incompleto; depois disto ela nem chega a
 * existir. E linha solta e o que faz uma publicacao nao aparecer em lista
 * nenhuma — some da tela sem erro nenhum.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->recusarSeSobrouLinhaSolta();

        Schema::table('contas_sociais', function (Blueprint $tabela) {
            $tabela->foreignId('grupo_id')->nullable(false)->change();
        });

        Schema::table('publicacoes', function (Blueprint $tabela) {
            $tabela->foreignId('grupo_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('contas_sociais', function (Blueprint $tabela) {
            $tabela->foreignId('grupo_id')->nullable()->change();
        });

        Schema::table('publicacoes', function (Blueprint $tabela) {
            $tabela->foreignId('grupo_id')->nullable()->change();
        });
    }

    /**
     * ⛔ Estoura ANTES de tocar em schema, com uma frase que diz o que fazer.
     *
     * O SQLite nao tem transacao de DDL: `->change()` recria a tabela inteira,
     * e falhar no meio deixa uma `__temp__contas_sociais` orfa que trava todas
     * as migrations seguintes, para sempre. Um banco de desenvolvimento
     * entalado custa muito mais caro que uma mensagem de erro.
     */
    private function recusarSeSobrouLinhaSolta(): void
    {
        $canais = DB::table('contas_sociais')->whereNull('grupo_id')->count();
        $publicacoes = DB::table('publicacoes')->whereNull('grupo_id')->count();

        if ($canais === 0 && $publicacoes === 0) {
            return;
        }

        throw new RuntimeException(
            "Ainda ha {$canais} canal(is) e {$publicacoes} publicacao(oes) sem grupo. ".
            'Rode `php artisan grupos:garantir-principal` antes desta migration.'
        );
    }
};

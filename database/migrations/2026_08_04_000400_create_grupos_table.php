<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ O grupo — a rede de canais de uma linha de conteudo (DEC-69).
 *
 * Quem produz noticias e novelas tem dois trios de canais. Sem esta tabela, o
 * compositor mostra os seis juntos e a unica coisa que separa um do outro e a
 * atencao da pessoa na hora de marcar a caixinha — e publicacao nao desfaz.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->ulid()->unique();
            // `restrict` como toda tabela de cliente: apagar dono com dado
            // exige decisao explicita, nunca cascata silenciosa.
            $tabela->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();

            /*
             * ⛔ SEM unique.
             *
             * Indice unico + soft delete e a armadilha classica: a pessoa
             * arquiva "Noticias", tenta criar "Noticias" de novo e leva um erro
             * sobre um grupo que ela nao enxerga mais em lugar nenhum.
             *
             * Dois grupos com o mesmo nome sao problema dela; o ULID distingue.
             */
            $tabela->string('nome');

            // `deleted_at` e nome do framework: o SoftDeletes procura por ele.
            // Renomear para `arquivado_em` desligaria o recurso em silencio.
            $tabela->softDeletes();
            $tabela->timestamps();

            // A pergunta de toda tela: "os grupos vivos deste dono".
            $tabela->index(['usuario_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};

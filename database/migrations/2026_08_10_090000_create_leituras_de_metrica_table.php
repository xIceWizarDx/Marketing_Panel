<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ **O número passa a ter ontem** (DEC-144).
 *
 * ⛔ Até aqui `AtualizarMetricas` sobrescrevia as colunas do destino: sabíamos
 * quanto **tem**, e nunca quanto **tinha**. Sem isso não existe curva de vida do
 * post — e é ela que responde a pergunta que decide repostar: *"ainda está
 * subindo, ou já morreu?"*.
 *
 * ⚠️ **Uma linha por destino por DIA**, não por leitura. Rodar o comando duas
 * vezes no mesmo dia atualiza a linha; não cria uma segunda. O que interessa é a
 * série diária, não o número de vezes que perguntamos.
 *
 * ⛔ Ela **não substitui** as colunas do destino: aquelas continuam sendo o
 * "agora", que a tela lê sem varrer histórico.
 *
 * ⚠️ E ela só começa a valer **depois de coletar** — por isso nasce antes da
 * tela que a desenha.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leituras_de_metrica', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('destino_id')->constrained('destinos')->cascadeOnDelete();

            /*
             * ⚠️ O DIA, não o instante. É a chave que impede duas leituras do
             * mesmo dia virarem dois pontos no gráfico.
             */
            $tabela->date('dia');

            // ⭐ Todos anuláveis, e `null` nunca é zero (DEC-95): campo ausente
            // quer dizer que aquela rede não publica aquele número.
            $tabela->unsignedBigInteger('visualizacoes')->nullable();
            $tabela->unsignedBigInteger('curtidas')->nullable();
            $tabela->unsignedBigInteger('comentarios')->nullable();
            $tabela->unsignedBigInteger('compartilhamentos')->nullable();

            $tabela->timestamps();

            /*
             * ⚠️ O único, e mais nenhum: ele **já indexa** `destino_id, dia`, que
             * é exatamente a consulta da curva ("este destino, em ordem de
             * dia"). Um índice separado com as mesmas colunas seria peso de
             * escrita sem ganho de leitura.
             */
            $tabela->unique(['destino_id', 'dia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leituras_de_metrica');
    }
};

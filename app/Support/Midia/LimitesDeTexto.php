<?php

namespace App\Support\Midia;

/**
 * Quanto texto cada rede aceita, e em que unidade.
 *
 * ⛔ **Estes limites nunca cortam texto — recusam.** A política do YouTube
 * proíbe *"modificar valores fornecidos pelo usuário (truncar, anexar, alterar)
 * sem consentimento explícito"*, e cortar em silêncio é exatamente o defeito que
 * o produto critica nos concorrentes: a pessoa só descobre olhando o post no ar.
 */
readonly class LimitesDeTexto
{
    public function __construct(
        /** Título. `null` = a rede não tem título separado. */
        public ?int $titulo = null,
        public Medida $medidaDoTitulo = Medida::Caracteres,

        /** Legenda / descrição. */
        public ?int $legenda = null,
        public Medida $medidaDaLegenda = Medida::Caracteres,

        /** Orçamento total das hashtags juntas. */
        public ?int $hashtags = null,
        public Medida $medidaDasHashtags = Medida::Caracteres,

        /**
         * ⛔ **Esta rede não tem campo de título: ele vai colado na legenda.**
         *
         * ⚠️ Quando é assim, os dois dividem **um orçamento só** — e conferir
         * separado deixa passar o que a rede vai recusar. No Threads, com 500
         * bytes, um título de 200 e uma legenda de 400 passam nas duas
         * conferências e estouram o limite ao chegar lá: a recusa acontece
         * depois de o vídeo inteiro ter subido.
         */
        public bool $tituloEntraNaLegenda = false,

        /**
         * ⛔ **Esta rede EXIGE título — sem ele não publica** (DEC-166).
         *
         * ⚠️ Nasceu de uma falha de campo evitável: o painel deixou enviar sem
         * título para o YouTube, a publicação subiu na fila, o publicador
         * recusou lá na frente e o quadrado da rede virou "não foi" em
         * vermelho. **Tudo isso para uma coisa que dava para saber antes de
         * clicar.**
         *
         * ⭐ Contar falha é placar. Impedir é produto.
         */
        public bool $tituloObrigatorio = false,
    ) {}

    /**
     * Confere um texto contra um limite.
     *
     * @return Achado|null null = cabe
     */
    public function conferir(string $texto, ?int $limite, Medida $medida, string $campo): ?Achado
    {
        if ($limite === null) {
            return null;
        }

        $tamanho = $medida->contar($texto);

        if ($tamanho <= $limite) {
            return null;
        }

        $sobra = $tamanho - $limite;
        $unidade = $medida->rotulo();

        return Achado::erro(
            "{$campo}: {$tamanho} {$unidade}, e o limite é {$limite}.",
            "Tire {$sobra} ".($sobra === 1 ? $this->singular($unidade) : $unidade).
            ' — ou escreva um texto próprio para esta rede.'
        );
    }

    private function singular(string $unidade): string
    {
        return rtrim($unidade, 's');
    }
}

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

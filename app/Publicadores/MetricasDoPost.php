<?php

namespace App\Publicadores;

/**
 * Os contadores que a rede publica sobre UM post — e só os que ela publica.
 *
 * ⭐ **`null` é uma resposta, e ela não é zero** (DEC-95). Quatro coisas
 * diferentes virariam `0` se ninguém as separasse:
 *   1. *a rede não tem esse número* — o Bluesky não conta visualização, ponto;
 *   2. *o dono escondeu* — no YouTube o campo **some** da resposta;
 *   3. *a rede ainda não calculou* — devolve lista vazia, não zero;
 *   4. *nós ainda não lemos*.
 *
 * Escrever `0` em qualquer um desses quatro casos é afirmar um fato que ninguém
 * verificou — exatamente o defeito que este produto existe para não ter. Por
 * isso todo campo aqui é `?int`, e a tela sabe a diferença.
 *
 * ⛔ Não existe campo "total", "engajamento" nem "taxa". Número derivado dos
 * dados da rede é proibido por escrito pelo YouTube (DEC-97), e sem base nas
 * outras.
 */
readonly class MetricasDoPost
{
    public function __construct(
        /** Visualizações. ⚠️ Cada rede define isso de um jeito, e o Bluesky não define. */
        public ?int $visualizacoes = null,
        /** Curtidas. */
        public ?int $curtidas = null,
        /** Comentários — no Bluesky, respostas. */
        public ?int $comentarios = null,
        /** Compartilhamentos — no Bluesky, reposts. O YouTube não publica este número. */
        public ?int $compartilhamentos = null,
    ) {}

    /** Veio alguma coisa? Resposta em branco não vira leitura registrada. */
    public function temAlgum(): bool
    {
        return $this->visualizacoes !== null
            || $this->curtidas !== null
            || $this->comentarios !== null
            || $this->compartilhamentos !== null;
    }
}

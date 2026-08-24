<?php

namespace App\Publicadores;

use App\Models\ContaSocial;
use App\Models\Destino;

/**
 * Lê os contadores que uma rede publica — e só quem tem o que ler implementa.
 *
 * ⛔ **Isto NÃO é parte do contrato do `Publicador`** (DEC-96), por dois
 * motivos:
 *
 * 1. **0.L** — a interface `Publicador` trava as 15 redes futuras, e mexer nela
 *    é decisão cara. Aqui, rede sem métrica simplesmente não implementa, e nada
 *    quebra.
 * 2. **A prova não pode depender de um enfeite.** Se o contador viesse junto da
 *    conciliação, a prova de que o post está no ar passaria a depender de uma
 *    cota que pode acabar. A prova é o que o produto vende; o contador é o que
 *    ele mostra por cima.
 *
 * ⚠️ Devolver `null` significa **"não deu para ler agora"** — rede fora do ar,
 * cota estourada, autorização caída. É diferente de devolver um objeto com todos
 * os campos `null`, que significa "li, e esta rede não publica nada disso".
 */
interface LeitorDeMetricas
{
    /** Seguidores da conta conectada, ou `null` se não deu para ler agora. */
    public function metricasDaConta(ContaSocial $conta): ?MetricasDaConta;

    /** Contadores de um post que já está no ar, ou `null` se não deu para ler agora. */
    public function metricasDoPost(Destino $destino): ?MetricasDoPost;
}

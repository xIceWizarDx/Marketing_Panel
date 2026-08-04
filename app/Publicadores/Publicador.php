<?php

namespace App\Publicadores;

use App\Enums\Plataforma;
use App\Models\Destino;

/**
 * O contrato de cada rede.
 *
 * ⚠️ 0.L — mexer nesta interface e decisao CARA: ela trava as 15 redes futuras.
 * Mudanca aqui exige confirmacao, nao entra por conta propria.
 *
 * Duas responsabilidades, separadas de proposito:
 *   `publicar()`   — envia. A rede pode so ACEITAR, nunca confirmar.
 *   `conciliar()`  — rele o post e diz se esta mesmo no ar. E a prova (DEC-31).
 *
 * Se fossem um metodo so, a tentacao de marcar "publicado" no retorno do envio
 * seria irresistivel — e e exatamente o defeito que o produto combate.
 */
interface Publicador
{
    public function plataforma(): Plataforma;

    /**
     * Envia o conteudo. Retorna o que a rede respondeu, sem interpretar demais.
     *
     * O `Retomada` existe para os envios em pedacos (YouTube): o publicador
     * guarda nele o endereco de sessao ANTES de o arquivo subir, e no retry
     * retoma dali em vez de recomecar. Rede de envio simples ignora o parametro.
     */
    public function publicar(Destino $destino, Retomada $retomada): ResultadoPublicacao;

    /** Pergunta a rede se o post existe. So aqui nasce a prova. */
    public function conciliar(Destino $destino): ResultadoConciliacao;
}

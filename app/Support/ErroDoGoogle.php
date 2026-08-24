<?php

namespace App\Support;

use Illuminate\Http\Client\Response;

/**
 * Separa o erro do Google que **passa** do erro que **fica**.
 *
 * ⚠️ Esta distinção é a diferença entre "tentamos de novo amanhã" e "a conta
 * parou de publicar até alguém reconectar na mão". Tratar as duas coisas igual
 * transforma um dia ruim da API do Google num apagão do produto (DEC-98).
 *
 * ⛔ **O 403 do Google serve para as duas coisas.** Ele responde 403 tanto para
 * "acabou sua cota do dia" quanto para "você não tem acesso a este canal" — e a
 * diferença só aparece em `error.errors[].reason`, dentro do corpo. Olhar só o
 * código de status é o erro que este arquivo existe para não deixar acontecer.
 */
class ErroDoGoogle
{
    /**
     * Motivos de 403 que somem sozinhos.
     *
     * Cota volta à meia-noite do Pacífico; limite por segundo volta no segundo
     * seguinte. Nenhum deles diz nada sobre a autorização da pessoa.
     */
    private const MOTIVOS_PASSAGEIROS = [
        'quotaexceeded',
        'dailylimitexceeded',
        'ratelimitexceeded',
        'userratelimitexceeded',
        'servicerequestquotaexceeded',
        'backenderror',
        'internalerror',
    ];

    /**
     * A falha some sozinha? Então não se mexe no status da conta.
     *
     * ⚠️ Resposta de sucesso devolve `false`: quem chama trata o conteúdo, e
     * uma resposta 200 com lista vazia continua significando "o canal não está
     * mais acessível" — que é achado de verdade, não falha de rede.
     */
    public static function ehPassageiro(Response $resposta): bool
    {
        if ($resposta->successful()) {
            return false;
        }

        // Servidor do Google fora do ar e "muitas requisições" nunca dizem nada
        // sobre a autorização.
        if ($resposta->serverError() || $resposta->status() === 429) {
            return true;
        }

        if ($resposta->status() !== 403) {
            return false;
        }

        return in_array(self::motivo($resposta), self::MOTIVOS_PASSAGEIROS, true);
    }

    /**
     * O `reason` do primeiro erro, em minúsculas — ou string vazia.
     *
     * ⚠️ Corpo sem JSON válido (página de erro em HTML, resposta cortada) cai
     * em string vazia, e string vazia **não** está na lista de passageiros: na
     * dúvida, o erro é tratado como permanente e alguém fica sabendo. O
     * contrário — presumir que passa — esconderia uma conta quebrada para
     * sempre.
     */
    private static function motivo(Response $resposta): string
    {
        return strtolower((string) $resposta->json('error.errors.0.reason', ''));
    }
}

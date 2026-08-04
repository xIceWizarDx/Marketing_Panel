<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    | Google / YouTube.
    |
    | ⚠️ As credenciais vem do projeto no Google Cloud — cada instalacao tem a
    | sua. Sem elas o YouTube nao conecta nem publica.
    */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/conexoes/youtube/retorno'),

        /*
         * A tela de permissão do Google ainda está como "Testes"?
         *
         * Muda o que a pessoa lê quando a conexão cai: em modo de Testes o
         * Google encerra a autorização a cada 7 dias, e sem essa explicação a
         * queda semanal parece defeito nosso.
         */
        'em_testes' => (bool) env('GOOGLE_EM_TESTES', true),
    ],

    /*
     * Facebook e Instagram: uma credencial so.
     *
     * As duas redes sao a mesma API por baixo — a conta do Instagram fica
     * pendurada numa Pagina do Facebook. Pedir dois aplicativos seria pedir
     * duas vezes a mesma autorizacao.
     */
    'meta' => [
        'client_id' => env('META_CLIENT_ID'),
        'client_secret' => env('META_CLIENT_SECRET'),
        'redirect' => env('META_REDIRECT_URI', env('APP_URL').'/conexoes/meta/retorno'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];

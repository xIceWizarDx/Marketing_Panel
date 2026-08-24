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
        /*
         * ⭐ **A configuracao do Login para Empresas** (DEC-162).
         *
         * ⛔ Sem ela o endereco de autorizacao vai com `scope`, do login
         * classico — e a Meta concede TODAS as permissoes sem anexar ativo
         * nenhum. Nem Pagina, nem Instagram. E responde "deu certo" em todas as
         * telas: a integracao aparece "Ativa", `/me/permissions` diz `granted`,
         * e `/me/accounts` volta vazio.
         *
         * Fica no painel do app: *Login do Facebook para Empresas ->
         * Configuracoes*, na lista de configuracoes.
         */
        'config_id' => env('META_CONFIG_ID'),
    ],

    /*
     * ⚠️ SEPARADO do `meta`, e nao por organizacao — por necessidade.
     *
     * O Threads e da Meta e mora no mesmo aplicativo, mas tem credencial
     * PROPRIA: outra janela de autorizacao (threads.net), outro servidor
     * (graph.threads.net) e outros escopos (threads_*). O `client_id` do
     * Facebook nao serve aqui.
     *
     * ⛔ Juntar os dois no mesmo bloco faria alguem preencher um e achar que
     * ligou os tres — e o erro so apareceria na tela de autorizacao da rede.
     */
    'threads' => [
        'client_id' => env('THREADS_CLIENT_ID'),
        'client_secret' => env('THREADS_CLIENT_SECRET'),
        'redirect' => env('THREADS_REDIRECT_URI', env('APP_URL').'/conexoes/threads/retorno'),
    ],

    /*
     * ⚠️ O LinkedIn nao tem nada a ver com a Meta: outro portal, outro
     * aplicativo, outro par de credenciais.
     *
     * ⛔ E o `redirect` tem que ser HTTPS e absoluto — a rede recusa qualquer
     * outra coisa, inclusive `http://localhost`.
     */
    'linkedin' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('LINKEDIN_REDIRECT_URI', env('APP_URL').'/conexoes/linkedin/retorno'),
        /*
         * ⭐ A versao datada que vai no cabecalho `LinkedIn-Version`.
         *
         * ⚠️ Ela ENVELHECE: a propria documentacao avisa que versoes sao
         * aposentadas, e uma versao morta derruba a publicacao inteira de uma
         * vez. Fica no `.env` para poder ser trocada sem deploy de codigo.
         */
        'versao' => env('LINKEDIN_VERSAO', '202607'),
    ],

    /*
     * ⚠️ O TikTok chama de `client_key`, nao `client_id` — o nome e diferente
     * de toda outra rede, e mandar o errado devolve um erro que nao diz qual
     * parametro faltou.
     */
    'tiktok' => [
        'client_key' => env('TIKTOK_CLIENT_KEY'),
        'client_secret' => env('TIKTOK_CLIENT_SECRET'),
        'redirect' => env('TIKTOK_REDIRECT_URI', env('APP_URL').'/conexoes/tiktok/retorno'),
        /*
         * ⛔ Enquanto o aplicativo nao passar pela auditoria do TikTok, TODO
         * post e privado — a rede recusa qualquer outra privacidade com
         * `unaudited_client_can_only_post_to_private_accounts` (DEC-116).
         *
         * ⚠️ E post privado NUNCA recebe `publicaly_available_post_id`, entao
         * nao existe link de prova ate a auditoria sair.
         */
        'auditado' => (bool) env('TIKTOK_AUDITADO', false),
    ],

    /*
     * ⛔ O X e a UNICA rede do painel em que publicar CUSTA DINHEIRO: US$ 0,015
     * por post, e US$ 0,200 se a legenda tiver link — treze vezes mais. Nao ha
     * faixa gratuita; os creditos sao comprados antes, no console deles.
     */
    'x' => [
        'client_id' => env('X_CLIENT_ID'),
        'client_secret' => env('X_CLIENT_SECRET'),
        'redirect' => env('X_REDIRECT_URI', env('APP_URL').'/conexoes/x/retorno'),
    ],

    /*
     * ⚠️ O Pinterest publica em QUADRO, nao em perfil: conectar traz um canal
     * por quadro, como a Meta traz um por Pagina (DEC-134).
     */
    'pinterest' => [
        'client_id' => env('PINTEREST_CLIENT_ID'),
        'client_secret' => env('PINTEREST_CLIENT_SECRET'),
        'redirect' => env('PINTEREST_REDIRECT_URI', env('APP_URL').'/conexoes/pinterest/retorno'),
    ],

    /*
     * ⛔ O Mastodon NAO tem client_id nem client_secret aqui — e nao e
     * esquecimento. Cada servidor emite o proprio par, na hora, por API e sem
     * autenticacao (DEC-139). O que existe e o endereco de retorno.
     */
    'mastodon' => [
        'redirect' => env('MASTODON_REDIRECT_URI', env('APP_URL').'/conexoes/mastodon/retorno'),
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

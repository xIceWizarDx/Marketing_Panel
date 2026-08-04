<?php

use App\Models\Usuario;

return [

    /*
    |--------------------------------------------------------------------------
    | Padroes de autenticacao
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'usuarios'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Guardas
    |--------------------------------------------------------------------------
    | Um guarda so. Admin e cliente sao o MESMO tipo de conta, separados pela
    | coluna `papel` — guarda por papel multiplicaria a superficie de erro.
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'usuarios',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Provedores de usuario
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'usuarios' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', Usuario::class),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redefinicao de senha
    |--------------------------------------------------------------------------
    | O link vale 60 minutos e so pode ser pedido de novo a cada 60 segundos.
    */

    'passwords' => [
        'usuarios' => [
            'provider' => 'usuarios',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validade da confirmacao de senha
    |--------------------------------------------------------------------------
    | Depois de confirmar a senha, acoes sensiveis ficam liberadas por este tempo.
    | 3 horas e o padrao do Laravel; aqui e 30 minutos, porque as acoes protegidas
    | sao pesadas (impersonar, desconectar rede, apagar conta).
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 1800),

];

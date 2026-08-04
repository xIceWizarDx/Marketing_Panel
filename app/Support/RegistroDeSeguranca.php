<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Porta unica do canal de seguranca.
 *
 * Existe para que ninguem escreva `Log::channel('seguranca')` espalhado: aqui o
 * formato e sempre o mesmo (evento + quem + de onde + quando), e a regra de
 * "nunca gravar segredo" fica em um lugar so, onde da pra revisar.
 */
class RegistroDeSeguranca
{
    /** Chaves que NUNCA vao para o arquivo, venham de onde vierem. */
    private const PROIBIDAS = [
        'password', 'password_confirmation', 'current_password', 'senha',
        'token', 'access_token', 'refresh_token', 'remember_token',
        'secret', 'client_secret', 'authorization', 'cookie',
    ];

    /**
     * @param  string  $evento  verbo no passado: 'entrou', 'impersonou', 'trocou_senha'
     * @param  array<string, mixed>  $contexto  dados de apoio, sem nada sigiloso
     */
    public static function registrar(string $evento, array $contexto = [], ?Request $request = null): void
    {
        $request ??= request();
        $usuario = $request?->user();

        Log::channel('seguranca')->info($evento, [
            // ULID e nao id: o arquivo de log nao precisa da chave do banco.
            'usuario_ulid' => $usuario?->ulid,
            'papel' => $usuario?->papel->value,
            'ip' => $request?->ip(),
            'agente_usuario' => $request?->userAgent(),
            ...self::limpar($contexto),
        ]);
    }

    /**
     * Ultima barreira: mesmo que alguem passe uma senha por engano, ela nao
     * chega ao disco.
     *
     * @param  array<string, mixed>  $contexto
     * @return array<string, mixed>
     */
    private static function limpar(array $contexto): array
    {
        foreach ($contexto as $chave => $valor) {
            if (in_array(strtolower((string) $chave), self::PROIBIDAS, true)) {
                $contexto[$chave] = '[removido]';

                continue;
            }

            if (is_array($valor)) {
                $contexto[$chave] = self::limpar($valor);
            }
        }

        return $contexto;
    }
}

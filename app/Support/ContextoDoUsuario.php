<?php

namespace App\Support;

use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;

/**
 * Quem é o dono do dado nesta execução.
 *
 * Numa requisição web a resposta é obvia: o usuario logado. Mas o **worker da
 * fila nao tem sessao** — e é ai que mora o bug clássico de multi-tenant: o
 * escopo vira `WHERE usuario_id IS NULL`, o job "roda", nao acha nada, nao dá
 * erro, e ninguem percebe. Pior: `Queue::fake()` esconde isso em todos os
 * testes, porque o job nunca chega a executar de verdade.
 *
 * Por isso aqui há TRÊS estados, e nenhum deles é "sem dono por omissão":
 *   1. sessão web        → dono = usuario autenticado (inclui impersonação)
 *   2. definido à mão    → job/comando declara de quem é o trabalho
 *   3. sem escopo        → varredura de propósito, sempre explícita
 * Fora desses três, consultar dado de cliente **lança exceção**.
 */
class ContextoDoUsuario
{
    private static ?int $usuarioId = null;

    private static bool $semEscopo = false;

    /**
     * Id do dono, ou null se ninguem assumiu a execucao.
     *
     * A sessao tem prioridade: se o job rodar dentro de uma requisicao (fila
     * `sync`), o usuario logado continua valendo.
     */
    public static function id(): ?int
    {
        return Auth::id() ?? self::$usuarioId;
    }

    /** Declara de quem é o trabalho. Todo job que toca dado de cliente chama isto. */
    public static function definir(Usuario|int $usuario): void
    {
        self::$usuarioId = $usuario instanceof Usuario ? $usuario->id : $usuario;
    }

    public static function esquecer(): void
    {
        self::$usuarioId = null;
    }

    public static function estaSemEscopo(): bool
    {
        return self::$semEscopo;
    }

    /**
     * Roda algo enxergando TODOS os clientes.
     *
     * Uso legítimo: tela do admin, seeder, relatório da plataforma, watchdog da
     * fila. Sempre explícito e sempre curto — nunca embrulhar a requisição
     * inteira, senão o isolamento deixa de existir sem ninguem notar.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function semEscopo(callable $callback): mixed
    {
        $anterior = self::$semEscopo;
        self::$semEscopo = true;

        try {
            return $callback();
        } finally {
            // `finally` garante que uma exceção lá dentro não deixe a aplicação
            // inteira sem isolamento pelo resto da requisição.
            self::$semEscopo = $anterior;
        }
    }

    /** Zera tudo — usado entre testes. */
    public static function limpar(): void
    {
        self::$usuarioId = null;
        self::$semEscopo = false;
    }
}

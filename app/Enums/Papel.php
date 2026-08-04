<?php

namespace App\Enums;

/**
 * Papel do usuario no sistema.
 *
 * A chave (`admin`, `cliente`) e canonica: vive no banco, nas rotas e nos testes,
 * e NUNCA muda. O texto que aparece na tela vem de lang/pt_BR/rotulos.php e pode
 * ser reescrito a vontade sem tocar em uma linha de codigo (DEC-18).
 *
 * ── Como nasce um papel novo (comercial, suporte...) ───────────────────────
 * 1. Adicionar o case aqui.
 * 2. Responder as DUAS perguntas obrigatorias abaixo (`ehOperador` e
 *    `rotaInicial`) — sao `match` sem `default` de proposito: papel novo sem
 *    resposta explode na hora, em vez de herdar o poder do admin em silencio.
 * 3. Escrever o rotulo em lang/pt_BR/rotulos.php.
 * 4. Se o papel enxerga menos que o admin (ex.: o comercial so ve a propria
 *    carteira), isso e ESCOPO, nao papel — e um modulo a parte. Papel diz
 *    "de que lado voce esta"; escopo diz "quanto voce enxerga desse lado".
 */
enum Papel: string
{
    case Admin = 'admin';
    case Cliente = 'cliente';

    /** Texto exibido na interface. */
    public function rotulo(): string
    {
        return __("rotulos.papel.{$this->value}");
    }

    /**
     * Este papel opera a plataforma (nosso time) ou e atendido por ela (cliente)?
     *
     * E a divisao mais importante do sistema: define quem entra nas telas de
     * `/admin` e quem entra no painel de publicacao. Sem `default` — papel novo
     * obriga a decidir o lado.
     */
    public function ehOperador(): bool
    {
        return match ($this) {
            self::Admin => true,
            self::Cliente => false,
        };
    }

    /** Rota inicial de cada papel — fonte unica do redirecionamento pos-login. */
    public function rotaInicial(): string
    {
        return match ($this) {
            self::Admin => 'admin.painel',
            self::Cliente => 'painel',
        };
    }

    /** @return list<self> */
    public static function operadores(): array
    {
        return array_values(array_filter(self::cases(), fn (self $papel) => $papel->ehOperador()));
    }

    /**
     * Chaves dos papeis que operam a plataforma, no formato do middleware.
     *
     * As rotas de `/admin` usam isto em vez de escrever "admin" na mao: quando
     * um papel de operador novo entrar, todas elas passam a aceita-lo sozinhas.
     */
    public static function listaDeOperadores(): string
    {
        return implode(',', array_column(self::operadores(), 'value'));
    }

    /** Lista pronta para <select> no frontend. */
    public static function paraSelecao(): array
    {
        return array_map(
            fn (self $papel) => ['valor' => $papel->value, 'rotulo' => $papel->rotulo()],
            self::cases()
        );
    }
}

<?php

namespace App\Enums;

/**
 * Saúde da conexão com a rede — o semáforo do DEC-32.
 *
 * ⚠️ `desconectada` NUNCA apaga a linha: o histórico de publicações aponta para
 * a conta, e apagar levaria junto a prova de entrega. Desconectar revoga o token
 * na rede e apaga só a credencial.
 */
enum StatusConta: string
{
    case Ativa = 'ativa';
    case Expirada = 'expirada';
    case Erro = 'erro';
    case Desconectada = 'desconectada';

    public function rotulo(): string
    {
        return __("rotulos.status_conta.{$this->value}");
    }

    /** Dá pra publicar por esta conta agora? */
    public function podePublicar(): bool
    {
        return $this === self::Ativa;
    }

    /** Cor do semáforo na tela. */
    public function cor(): string
    {
        return match ($this) {
            self::Ativa => 'ok',
            self::Expirada => 'atencao',
            self::Erro => 'erro',
            self::Desconectada => 'neutro',
        };
    }

    public static function paraSelecao(): array
    {
        return array_map(fn (self $s) => ['valor' => $s->value, 'rotulo' => $s->rotulo()], self::cases());
    }
}

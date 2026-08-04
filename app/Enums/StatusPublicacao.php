<?php

namespace App\Enums;

/**
 * Estado da publicação — agregado dos seus destinos.
 *
 * Nunca é escrito à mão: `PublicacaoService::recalcularStatus()` deriva do
 * conjunto de destinos. Duas fontes para o mesmo fato é como o painel passa a
 * dizer "publicado" enquanto um destino ainda está na fila.
 */
enum StatusPublicacao: string
{
    case Rascunho = 'rascunho';
    case Processando = 'processando';
    case Concluida = 'concluida';
    case ConcluidaComFalhas = 'concluida_com_falhas';
    case Falhou = 'falhou';

    public function rotulo(): string
    {
        return __("rotulos.status_publicacao.{$this->value}");
    }

    public function ehTerminal(): bool
    {
        return match ($this) {
            self::Rascunho, self::Processando => false,
            default => true,
        };
    }

    public static function paraSelecao(): array
    {
        return array_map(fn (self $s) => ['valor' => $s->value, 'rotulo' => $s->rotulo()], self::cases());
    }
}

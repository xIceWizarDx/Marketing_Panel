<?php

namespace App\Enums;

enum TipoMidia: string
{
    case Video = 'video';
    case Imagem = 'imagem';

    public function rotulo(): string
    {
        return __("rotulos.tipo_midia.{$this->value}");
    }

    /** Formatos aceitos no upload, pelo perfil canônico (doc 07 §6). */
    public function mimesAceitos(): array
    {
        return match ($this) {
            // MP4 é o único contêiner que passa nas 4 redes. `quicktime` entra
            // porque o iPhone grava .mov e recusar isso na porta seria hostil.
            self::Video => ['video/mp4', 'video/quicktime'],
            // JPEG é o formato universal — o Instagram rejeita PNG.
            self::Imagem => ['image/jpeg'],
        };
    }

    public static function paraSelecao(): array
    {
        return array_map(
            fn (self $tipo) => ['valor' => $tipo->value, 'rotulo' => $tipo->rotulo()],
            self::cases()
        );
    }
}

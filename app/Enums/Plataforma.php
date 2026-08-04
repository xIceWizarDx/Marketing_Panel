<?php

namespace App\Enums;

/**
 * As redes onde o produto publica — ou pretende publicar.
 *
 * A chave é canônica (vai pro banco e pras rotas); o nome exibido vem de
 * `rotulos.php` — nomes de marca mudam (Twitter virou X) e não podem arrastar
 * migration junto.
 *
 * Estão aqui as redes mapeadas no doc 10. Ficaram **de fora de propósito**:
 * Reddit (o plano gratuito proíbe uso comercial) e Slack (é chat interno, não
 * rede de publicação).
 */
enum Plataforma: string
{
    /*
     * ⚠️ A ORDEM AQUI É A ORDEM DA TELA.
     *
     * `cases()` é o que a tela de Conexões e o laudo percorrem, então esta lista
     * está em ordem de **prioridade do produto** — não por situação, que é
     * assunto de `situacao()`. Duplicar a situação aqui, em comentário, só criaria
     * duas verdades para discordarem depois.
     */

    // ── As quatro que importam, nesta ordem ─────────────────────────────────
    case Youtube = 'youtube';
    case Tiktok = 'tiktok';
    case Facebook = 'facebook';
    case Instagram = 'instagram';

    // ── Publica hoje, sem depender de aprovação de ninguém (DEC-29) ─────────
    case Bluesky = 'bluesky';

    // ── No roteiro, sem urgência ────────────────────────────────────────────
    /** Pega carona no mesmo App Review do Instagram e do Facebook (DEC-30). */
    case Threads = 'threads';
    /** Barreira zero também: a permissão é self-service, liberada em minutos. */
    case Linkedin = 'linkedin';

    // ── Mapeadas, sem decisão ───────────────────────────────────────────────
    case Pinterest = 'pinterest';
    /** ⚠️ Única que cobra POR PUBLICAÇÃO — decisão de custo, não técnica. */
    case X = 'x';
    case Mastodon = 'mastodon';
    case Discord = 'discord';
    case LinkedinPagina = 'linkedin_pagina';
    case Snapchat = 'snapchat';
    /** Não aceita vídeo — entraria só pelo lado das imagens. */
    case GoogleBusiness = 'google_business';

    public function rotulo(): string
    {
        return __("rotulos.plataforma.{$this->value}");
    }

    /**
     * Em que pé a rede está.
     *
     * `match` sem `default` de propósito: rede nova obriga a decidir a situação
     * na hora, em vez de aparecer na tela como algo que ninguém decidiu.
     */
    public function situacao(): SituacaoDaRede
    {
        return match ($this) {
            self::Bluesky => SituacaoDaRede::Disponivel,

            self::Linkedin,
            self::Youtube,
            self::Instagram,
            self::Facebook,
            self::Threads,
            self::Tiktok => SituacaoDaRede::Planejada,

            default => SituacaoDaRede::EmEstudo,
        };
    }

    /**
     * A rede tem regras de mídia registradas (doc 07 §6 / doc 10)?
     *
     * Só entram no laudo as que têm. Julgar um arquivo contra limites que
     * ninguém pesquisou seria inventar — e o laudo perde o sentido.
     */
    public function temEspecificacao(): bool
    {
        return $this->situacao() !== SituacaoDaRede::EmEstudo;
    }

    /** @return list<self> */
    public static function comEspecificacao(): array
    {
        return array_values(array_filter(self::cases(), fn (self $p) => $p->temEspecificacao()));
    }

    public static function paraSelecao(): array
    {
        return array_map(
            fn (self $p) => ['valor' => $p->value, 'rotulo' => $p->rotulo()],
            self::cases()
        );
    }
}

import {
    siBluesky,
    siDiscord,
    siFacebook,
    siInstagram,
    siMastodon,
    siPinterest,
    siSnapchat,
    siThreads,
    siTiktok,
    siX,
    siYoutube,
} from 'simple-icons';

import { cn } from '@/lib/utils';

/**
 * Logotipo de cada rede.
 *
 * Vêm de dois acervos empacotados com o projeto — `simple-icons` e
 * `bootstrap-icons`. Nada de CDN nem de imagem baixada solta: rede externa vira
 * dependência que some quando menos se espera, e arquivo de origem duvidosa não
 * sobrevive a uma auditoria de plataforma.
 *
 * LinkedIn e Google não estão no `simple-icons` (as marcas pediram remoção do
 * acervo), então vêm do `bootstrap-icons`, que os mantém.
 */

interface Desenho {
    path: string;
    hex: string;
    /** O `bootstrap-icons` desenha em 16×16; o `simple-icons`, em 24×24. */
    caixa?: string;
}

const bootstrap = {
    linkedin:
        'M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854zm4.943 12.248V6.169H2.542v7.225zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248S2.4 3.226 2.4 3.934c0 .694.521 1.248 1.327 1.248zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016l.016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225z',
    google: 'M15.545 6.558a9.4 9.4 0 0 1 .139 1.626c0 2.434-.87 4.492-2.384 5.885h.002C11.978 15.292 10.158 16 8 16A8 8 0 1 1 8 0a7.7 7.7 0 0 1 5.352 2.082l-2.284 2.284A4.35 4.35 0 0 0 8 3.166c-2.087 0-3.86 1.408-4.492 3.304a4.8 4.8 0 0 0 0 3.063h.003c.635 1.893 2.405 3.301 4.492 3.301 1.078 0 2.004-.276 2.722-.764h-.003a3.7 3.7 0 0 0 1.599-2.431H8v-3.08z',
};

const marcas: Record<string, Desenho> = {
    bluesky: siBluesky,
    youtube: siYoutube,
    instagram: siInstagram,
    facebook: siFacebook,
    threads: siThreads,
    tiktok: siTiktok,
    pinterest: siPinterest,
    x: siX,
    mastodon: siMastodon,
    discord: siDiscord,
    snapchat: siSnapchat,

    linkedin: { path: bootstrap.linkedin, hex: '0A66C2', caixa: '0 0 16 16' },
    linkedin_pagina: { path: bootstrap.linkedin, hex: '004182', caixa: '0 0 16 16' },
    google_business: { path: bootstrap.google, hex: '4285F4', caixa: '0 0 16 16' },
};

/** Fundo amarelo do Snapchat exige glifo escuro para ter contraste legível. */
const glifoEscuro = new Set(['snapchat']);

export default function MarcaDaRede({ rede, className }: { rede: string; className?: string }) {
    const marca = marcas[rede];

    if (!marca) {
        return (
            <span
                aria-hidden="true"
                className={cn('bg-muted text-muted-foreground flex size-10 shrink-0 items-center justify-center rounded-xl', className)}
            >
                ?
            </span>
        );
    }

    return (
        <span
            aria-hidden="true"
            className={cn('flex size-10 shrink-0 items-center justify-center rounded-xl', className)}
            style={{ backgroundColor: `#${marca.hex}` }}
        >
            {/* ⚠️ Metade do quadro, não 20px fixos.
                Com tamanho fixo, todo uso menor que `size-10` ficava com o
                glifo grande demais — e nos de 16px ele TRANSBORDAVA a marca,
                porque nada aqui corta o que sobra. A proporção é a mesma do
                tamanho padrão, então nenhum uso existente muda de cara. */}
            <svg viewBox={marca.caixa ?? '0 0 24 24'} className="size-1/2" fill={glifoEscuro.has(rede) ? '#000' : '#fff'} role="presentation">
                <path d={marca.path} />
            </svg>
        </span>
    );
}

import { FileVideo, Image as IconeImagem, Play } from 'lucide-react';

import { cn } from '@/lib/utils';

/**
 * O quadradinho que faz a pessoa reconhecer o vídeo.
 *
 * ⭐ Usado onde se reconhece uma mídia (compositor, lista de publicações,
 * publicar e publicações). Um componente só para as três concordarem — se cada
 * tela desenhasse do seu jeito, um dia elas divergiriam e a mesma mídia
 * apareceria de duas formas.
 *
 * Sem miniatura (ffmpeg ausente, ou imagem), cai no ícone. **Nunca** mostra
 * imagem quebrada: é pior que não mostrar nada.
 */
export default function Miniatura({
    url,
    tipo,
    alt,
    className,
    comPlay = false,
}: {
    url: string | null;
    tipo: string;
    /** Descreve a mídia, não a imagem: quem usa leitor de tela quer saber qual vídeo é. */
    alt: string;
    className?: string;
    /** Marca de "tocar" por cima, quando o clique abre a prévia. */
    comPlay?: boolean;
}) {
    const Icone = tipo === 'video' ? FileVideo : IconeImagem;

    return (
        <span
            className={cn('bg-muted text-muted-foreground relative flex shrink-0 items-center justify-center overflow-hidden rounded-md', className)}
        >
            {url ? (
                <img
                    src={url}
                    alt={alt}
                    // `cover` porque a grade é quadrada e o vídeo é 9:16: encaixar
                    // inteiro deixaria duas tarjas enormes em cada card.
                    className="size-full object-cover"
                    loading="lazy"
                />
            ) : (
                <Icone className="size-1/3" aria-hidden="true" />
            )}

            {comPlay && (
                <span className="absolute inset-0 flex items-center justify-center bg-black/25 opacity-0 transition-opacity hover:opacity-100">
                    <Play className="size-1/4 fill-white text-white" aria-hidden="true" />
                </span>
            )}
        </span>
    );
}

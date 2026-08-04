import { X } from 'lucide-react';
import { useEffect } from 'react';

/**
 * Toca o vídeo antes de publicar.
 *
 * ⭐ Publicar o vídeo errado **não tem desfazer**. A miniatura resolve o
 * reconhecimento; a prévia resolve a certeza — dois cortes do mesmo take têm a
 * mesma primeira imagem.
 *
 * ⚠️ O arquivo só é pedido quando esta janela abre. Deixar um `<video>` em cada
 * card da grade faria o navegador baixar todos os vídeos para desenhar a tela.
 */
export default function Previa({
    url,
    nome,
    aoFechar,
}: {
    url: string;
    nome: string;
    aoFechar: () => void;
}) {
    // `Esc` fecha, como em qualquer janela: quem já usa computador espera isso, e
    // procurar o botão de fechar é fricção à toa.
    useEffect(() => {
        const noTeclado = (evento: KeyboardEvent) => {
            if (evento.key === 'Escape') aoFechar();
        };

        document.addEventListener('keydown', noTeclado);

        return () => document.removeEventListener('keydown', noTeclado);
    }, [aoFechar]);

    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-label={`Prévia de ${nome}`}
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
            onClick={aoFechar}
        >
            <div
                className="relative flex max-h-full w-full max-w-md flex-col"
                // Clique no vídeo não fecha; só no fundo.
                onClick={(evento) => evento.stopPropagation()}
            >
                <button
                    type="button"
                    onClick={aoFechar}
                    aria-label="Fechar prévia"
                    className="absolute -top-9 right-0 rounded p-1 text-white/80 transition-colors hover:text-white focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                >
                    <X className="size-5" aria-hidden="true" />
                </button>

                {/* `autoPlay` sem `muted` é bloqueado pelo navegador, e o vídeo
                    ficaria parado sem explicação. Melhor a pessoa dar play. */}
                <video src={url} controls playsInline className="max-h-[80vh] w-full rounded-lg bg-black" />

                <p className="mt-2 truncate text-center text-sm text-white/80">{nome}</p>
            </div>
        </div>
    );
}

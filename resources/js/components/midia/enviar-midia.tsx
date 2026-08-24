import { router } from '@inertiajs/react';
import { Image, LoaderCircle, UploadCloud, Video } from 'lucide-react';
import { DragEvent, useRef, useState } from 'react';

import ErroDeCampo from '@/components/erro-de-campo';
import { cn } from '@/lib/utils';

const aceitos = {
    video: 'video/mp4,video/quicktime',
    imagem: 'image/jpeg',
} as const;

type Tipo = keyof typeof aceitos;

/**
 * O campo de envio — e **só** o envio.
 *
 * ⛔ **Ele não escolhe o arquivo depois de enviar, de propósito.** Isso é do
 * compositor, que já recebe `midiaEnviada` como propriedade tipada. Aqui, a
 * única forma de saber o ULID seria cavar as propriedades da página por um
 * caminho escrito em texto — e caminho de texto o TypeScript não confere. Foi
 * exatamente assim que o vídeo passou a subir, ser salvo e não aparecer.
 */
export default function EnviarMidia({ tamanhoMaximoMb }: { tamanhoMaximoMb: number }) {
    const campo = useRef<HTMLInputElement>(null);
    const [tipo, setTipo] = useState<Tipo>('video');
    const [arrastando, setArrastando] = useState(false);
    const [enviando, setEnviando] = useState(false);
    const [progresso, setProgresso] = useState(0);
    const [erro, setErro] = useState<string | undefined>();

    const enviar = (arquivo: File) => {
        setErro(undefined);
        setEnviando(true);
        setProgresso(0);

        // `router.post` em vez de `useForm`: o arquivo chega pelo seletor ou
        // pelo arrastar, e precisa ir na hora — `useForm` só agendaria o estado
        // e mandaria o valor anterior na primeira seleção.
        router.post(
            route('midias.salvar'),
            { tipo, arquivo },
            {
                forceFormData: true,
                // ⚠️ O texto já escrito não pode se perder porque a pessoa
                // resolveu enviar o vídeo depois de escrever a legenda.
                preserveState: true,
                preserveScroll: true,
                onProgress: (evento) => setProgresso(evento?.percentage ?? 0),
                /*
                 * ⛔ **Recusa sem motivo é pior que recusa**: aqui já se leu só
                 * `arquivo` e `tipo`, e qualquer outra recusa virava `undefined`
                 * — a barra sumia, nada aparecia escrito, e o arquivo parecia
                 * ter evaporado. Agora, o que não for previsto ainda diz algo.
                 */
                onError: (erros) =>
                    setErro(erros.arquivo ?? erros.tipo ?? Object.values(erros)[0] ?? 'Não consegui enviar o arquivo. Tente de novo.'),
                onFinish: () => {
                    setEnviando(false);
                    setProgresso(0);
                    if (campo.current) campo.current.value = '';
                },
            },
        );
    };

    const aoSoltar = (evento: DragEvent<HTMLLabelElement>) => {
        evento.preventDefault();
        setArrastando(false);

        const arquivo = evento.dataTransfer.files?.[0];
        if (arquivo) enviar(arquivo);
    };

    return (
        <div className="space-y-3">
            <div role="radiogroup" aria-label="Tipo de arquivo" className="bg-muted inline-flex gap-1 rounded-lg p-1">
                {(['video', 'imagem'] as const).map((opcao) => {
                    const ativo = tipo === opcao;
                    const Icone = opcao === 'video' ? Video : Image;

                    return (
                        <button
                            key={opcao}
                            type="button"
                            role="radio"
                            aria-checked={ativo}
                            onClick={() => setTipo(opcao)}
                            disabled={enviando}
                            className={cn(
                                'focus-visible:ring-ring flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm transition-colors focus-visible:ring-2 focus-visible:outline-none',
                                ativo ? 'bg-card text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground',
                            )}
                        >
                            <Icone className="size-4" aria-hidden="true" />
                            {opcao === 'video' ? 'Vídeo' : 'Imagem'}
                        </button>
                    );
                })}
            </div>

            <label
                onDragOver={(e) => {
                    e.preventDefault();
                    setArrastando(true);
                }}
                onDragLeave={() => setArrastando(false)}
                onDrop={aoSoltar}
                className={cn(
                    'flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed px-6 py-10 text-center transition-colors',
                    'focus-within:ring-ring focus-within:ring-2',
                    arrastando ? 'border-[color:var(--accent)] bg-[color:var(--accent)]/5' : 'border-border hover:border-[color:var(--accent)]/50',
                    enviando && 'pointer-events-none opacity-60',
                )}
            >
                <input
                    ref={campo}
                    type="file"
                    accept={aceitos[tipo]}
                    className="sr-only"
                    disabled={enviando}
                    onChange={(e) => {
                        const arquivo = e.target.files?.[0];
                        if (arquivo) enviar(arquivo);
                    }}
                />

                {enviando ? (
                    <>
                        <LoaderCircle className="text-muted-foreground size-6 animate-spin" aria-hidden="true" />
                        <span className="text-sm font-medium">Enviando… {progresso}%</span>
                        <span className="bg-muted h-1 w-full max-w-xs overflow-hidden rounded-full">
                            <span className="block h-full bg-[color:var(--accent)] transition-[width]" style={{ width: `${progresso}%` }} />
                        </span>
                    </>
                ) : (
                    <>
                        <UploadCloud className="text-muted-foreground size-6" aria-hidden="true" />
                        <span className="text-sm font-medium">
                            Arraste o arquivo aqui ou <span className="text-[color:var(--accent)]">escolha do computador</span>
                        </span>
                        <span className="text-muted-foreground text-xs">
                            {tipo === 'video' ? 'MP4 ou MOV' : 'JPEG'} · até {tamanhoMaximoMb} MB · o ideal é vertical 9:16
                        </span>

                        {/* O que ensinar vem AQUI dentro, na única caixa que
                            existe. Uma segunda caixa dizendo "envie um vídeo
                            acima" só repetiria o que já está na frente. */}
                        <span className="text-muted-foreground mt-1 max-w-prose text-xs leading-relaxed">
                            Assim que ele chegar, dizemos em segundos se passa em cada rede — e o que faremos com ele se algo estiver fora do padrão.
                        </span>
                    </>
                )}
            </label>

            <ErroDeCampo mensagem={erro} />
        </div>
    );
}

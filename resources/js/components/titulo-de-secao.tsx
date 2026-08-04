import { type ReactNode } from 'react';

interface Props {
    titulo: string;
    /** Explica a seção, embaixo do título — onde há largura para uma frase. */
    descricao?: string;
    /**
     * O que fica à DIREITA do título, na mesma linha.
     *
     * ⚠️ É para o que acompanha sem competir: um resumo de duas palavras, um
     * "ver todas". Explicação de verdade vai em `descricao`.
     */
    apoio?: ReactNode;
}

/** Abre uma seção dentro da tela — abaixo do título da página, nunca no lugar dele. */
export default function TituloDeSecao({ titulo, descricao, apoio }: Props) {
    return (
        <header className="mb-2.5">
            <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-0.5">
                <h2 className="text-base font-medium">{titulo}</h2>
                {apoio && <div className="text-muted-foreground text-xs">{apoio}</div>}
            </div>

            {descricao && <p className="text-muted-foreground mt-0.5 text-sm">{descricao}</p>}
        </header>
    );
}

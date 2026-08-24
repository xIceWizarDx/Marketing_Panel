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

/**
 * Abre uma seção dentro da tela — abaixo do título da página, nunca no lugar dele.
 *
 * ⚠️ A separação do título da página é de **peso**, não de tamanho: os dois têm
 * quase o mesmo corpo, e é o negrito que diz qual é qual. Aumentar o corpo aqui
 * faria a seção competir com o título da tela; usar peso normal fazia ela
 * desaparecer dentro do texto. Ficou no meio, de propósito.
 */
export default function TituloDeSecao({ titulo, descricao, apoio }: Props) {
    return (
        <header className="mb-3">
            <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-0.5">
                <h2 className="text-base font-semibold tracking-tight">{titulo}</h2>

                {/* ⚠️ Nunca `text-xs` aqui: a fonte base do painel varia de 13px
                    a 15px conforme a tela, e num monitor de 1280px o `xs` cai
                    para uns 9,7px — pequeno demais para uma linha que carrega
                    número ("3 contas · 43 posts no ar"). */}
                {apoio && <div className="text-muted-foreground text-[0.8125rem]">{apoio}</div>}
            </div>

            {descricao && <p className="text-muted-foreground mt-0.5 text-sm">{descricao}</p>}
        </header>
    );
}

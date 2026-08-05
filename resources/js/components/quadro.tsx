import { type ComponentPropsWithoutRef, type ReactNode } from 'react';

import { cn } from '@/lib/utils';

interface Props extends Omit<ComponentPropsWithoutRef<'button'>, 'children'> {
    children: ReactNode;
    /** `button` quando clicar faz algo; `div` quando é só informação. */
    como?: 'button' | 'div';
    /** Contorno tracejado — usado no quadro que convida a acrescentar algo. */
    tracejado?: boolean;
    /**
     * `pequeno` é para dentro de um modal, onde a largura é curta.
     *
     * ⚠️ Sem isto, um catálogo de catorze redes viraria cinco linhas de rolagem
     * — e escolher exige ver as opções juntas, não uma de cada vez.
     *
     * ⚠️ Pequeno **não é apertado**: ele ainda precisa caber logo, nome e
     * situação sem os três se encostarem. Espremer o quadro para ganhar uma
     * coluna a mais troca uma linha de rolagem por três linhas ilegíveis.
     */
    tamanho?: 'normal' | 'pequeno';
}

/**
 * ⭐ O quadrado do painel — e ele é **sempre** quadrado.
 *
 * ⚠️ O tamanho é FIXO de propósito. Numa grade que estica, duas redes conectadas
 * viram dois retângulos enormes e o painel muda de cara conforme o conteúdo. Com
 * lado fixo e quebra de linha, três quadros ou onze desenham o mesmo ritmo.
 *
 * ⛔ Retângulo continua existindo onde ele é a forma certa — aviso com texto
 * corrido, lista de passos. O que não existe é retângulo por acidente de grade.
 */
export default function Quadro({ children, como = 'div', tracejado = false, tamanho = 'normal', className, ...resto }: Props) {
    const aparencia = cn(
        'relative flex shrink-0 flex-col items-center justify-center rounded-xl p-2.5 text-center',
        tamanho === 'pequeno' ? 'size-[6.5rem]' : 'size-[6.5rem] sm:size-[7.75rem]',
        tracejado ? 'border-border border-2 border-dashed' : 'border-border bg-card border',
        className,
    );

    if (como === 'div') {
        return <div className={aparencia}>{children}</div>;
    }

    return (
        <button
            type="button"
            {...resto}
            className={cn(
                aparencia,
                'focus-visible:ring-ring transition-colors focus-visible:ring-2 focus-visible:outline-none',
                resto.disabled ? 'cursor-default opacity-45' : 'hover:border-[color:var(--accent)]',
            )}
        >
            {children}
        </button>
    );
}

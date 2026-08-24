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
    /**
     * Tinge o quadro com a cor de um ESTADO, no lugar do branco com contorno.
     *
     * ⚠️ Existe porque quadro branco dentro de painel branco vira um contorno
     * solto: o cartão some e sobra a linha. Tingido, ele volta a ter chão sem
     * precisar de sombra — o painel inteiro não usa nenhuma.
     *
     * ⛔ Cor aqui é estado, nunca enfeite. É a mesma cor do número que está
     * dentro dele e da fatia da barra ao lado: duas cores para o mesmo fato é
     * onde um painel começa a ficar bonito e a mentir.
     */
    tom?: string;
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
export default function Quadro({ children, como = 'div', tracejado = false, tamanho = 'normal', tom, className, ...resto }: Props) {
    const aparencia = cn(
        'relative flex shrink-0 flex-col items-center justify-center rounded-xl p-2.5 text-center',
        tamanho === 'pequeno' ? 'size-[6.5rem]' : 'size-[6.5rem] sm:size-[7.75rem]',
        // ⛔ **Um recurso por elemento: contorno OU preenchimento, nunca os
        //    dois.** Tingido e contornado ao mesmo tempo, o quadrado passa a
        //    gritar duas vezes o mesmo estado — e quando tudo grita, some a
        //    ordem de leitura.
        tracejado ? 'border-border border-2 border-dashed' : tom ? '' : 'bg-card shadow-[0_0_0_1px_var(--border)]',
        className,
    );

    // ⚠️ `color-mix` e não uma cor pronta: o tom chega como `var(--saude-ok)` e
    // troca sozinho no tema escuro. Uma cor calculada aqui congelaria o claro.
    const tingido = tom ? { backgroundColor: `color-mix(in oklab, ${tom} 10%, transparent)` } : undefined;

    if (como === 'div') {
        return (
            <div className={aparencia} style={tingido}>
                {children}
            </div>
        );
    }

    return (
        <button
            type="button"
            {...resto}
            style={tingido}
            className={cn(
                aparencia,
                // ⚠️ O anel do foco ganha 2px do FUNDO por dentro antes do
                // accent: sem esse vão ele encosta no contorno de 1px do quadro
                // e os dois viram um traço grosso só, que não se lê como foco.
                'focus-visible:ring-ring transition-shadow focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-[color:var(--background)] focus-visible:outline-none',
                resto.disabled
                    ? 'cursor-default opacity-45'
                    : // O realce da passagem do mouse é o mesmo anel, na cor da
                      // marca — e não uma borda, que mudaria o miolo em 2px.
                      'hover:shadow-[0_0_0_1px_var(--accent)]',
            )}
        >
            {children}
        </button>
    );
}

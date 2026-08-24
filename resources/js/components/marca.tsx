import { usePage } from '@inertiajs/react';

import { cn } from '@/lib/utils';
import { type DadosCompartilhados } from '@/types';

interface Props {
    /** Só o símbolo, sem o nome escrito ao lado. */
    compacta?: boolean;
    /**
     * Só o nome, sem o símbolo.
     *
     * ⚠️ Existe para a barra lateral, onde o símbolo mora num compartimento de
     * largura fixa (o eixo dos ícones) e o nome vive fora dele, na parte que é
     * cortada quando a barra recolhe. Juntos num componente só, os dois andariam
     * grudados e o símbolo sairia do eixo.
     */
    somenteNome?: boolean;
    className?: string;
}

/**
 * Nome e símbolo do produto.
 *
 * ⛔ **O nome não está escrito aqui** — ele vem do servidor, de `APP_NAME`
 * (regra 0.N). Renomear o produto é mudar uma linha do `.env` e nada mais.
 *
 * ⚠️ **A letra do símbolo também não.** Ela é a inicial do nome, calculada na
 * hora: escrita à mão, ela seria mais um pedaço do nome do produto morando no
 * código — e ficaria para trás no dia da renomeação, com o símbolo dizendo uma
 * letra e o nome ao lado dizendo outra.
 */
export default function Marca({ compacta = false, somenteNome = false, className }: Props) {
    const { nomeDoApp } = usePage<DadosCompartilhados>().props;

    const inicial = (nomeDoApp ?? '').trim().charAt(0).toUpperCase();

    if (somenteNome) {
        return <span className={cn('truncate text-sm leading-none font-semibold whitespace-nowrap', className)}>{nomeDoApp}</span>;
    }

    return (
        <span className={cn('flex items-center gap-2', className)}>
            <span
                aria-hidden="true"
                className="flex size-8 shrink-0 items-center justify-center rounded-md bg-[var(--accent)] text-sm font-bold text-[color:var(--accent-foreground)]"
            >
                {inicial}
            </span>

            {!compacta && <span className="truncate text-sm leading-none font-semibold">{nomeDoApp}</span>}
        </span>
    );
}

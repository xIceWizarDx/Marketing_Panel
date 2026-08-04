import { usePage } from '@inertiajs/react';

import { type DadosCompartilhados } from '@/types';
import { cn } from '@/lib/utils';

/**
 * Nome e simbolo do produto.
 *
 * O nome vem do servidor (config('app.name')) — nao esta escrito no React, pra
 * que renomear o produto seja mudar uma linha do .env e nada mais.
 */
export default function Marca({ compacta = false, className }: { compacta?: boolean; className?: string }) {
    const { nomeDoApp } = usePage<DadosCompartilhados>().props;

    return (
        <span className={cn('flex items-center gap-2', className)}>
            <span
                aria-hidden="true"
                className="bg-[var(--accent)] text-[color:var(--accent-foreground)] flex size-8 shrink-0 items-center justify-center rounded-md text-sm font-bold"
            >
                M
            </span>

            {!compacta && <span className="truncate text-sm leading-none font-semibold">{nomeDoApp}</span>}
        </span>
    );
}

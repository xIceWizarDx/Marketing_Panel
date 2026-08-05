import { usePage } from '@inertiajs/react';

import { cn } from '@/lib/utils';
import { type DadosCompartilhados } from '@/types';

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
                className="flex size-8 shrink-0 items-center justify-center rounded-md bg-[var(--accent)] text-sm font-bold text-[color:var(--accent-foreground)]"
            >
                M
            </span>

            {!compacta && <span className="truncate text-sm leading-none font-semibold">{nomeDoApp}</span>}
        </span>
    );
}

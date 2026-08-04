import { useCallback, useState } from 'react';

import BarraInferior from '@/components/barra-inferior';
import BarraLateral from '@/components/barra-lateral';
import BarraSuperior from '@/components/barra-superior';
import TarjaImpersonacao from '@/components/tarja-impersonacao';
import Avisos from '@/components/avisos';
import { type Migalha } from '@/types';

interface Props {
    children: React.ReactNode;
    migalhas?: Migalha[];
}

const CHAVE_RECOLHIDA = 'barra-lateral-recolhida';

/**
 * Casca das telas internas — vale pro painel do cliente e pro do admin.
 *
 * Desktop: barra lateral fixa, largura fluida (DEC-37).
 * Celular: barra de navegacao embaixo, no alcance do polegar; sem lateral.
 */
export default function LayoutPainel({ children, migalhas }: Props) {
    const [recolhida, setRecolhida] = useState(() =>
        typeof window === 'undefined' ? false : window.localStorage.getItem(CHAVE_RECOLHIDA) === 'sim',
    );

    const alternar = useCallback(() => {
        setRecolhida((atual) => {
            const proximo = !atual;
            window.localStorage.setItem(CHAVE_RECOLHIDA, proximo ? 'sim' : 'nao');
            return proximo;
        });
    }, []);

    return (
        <div className="bg-background text-foreground min-h-svh">
            <TarjaImpersonacao />

            <BarraLateral recolhida={recolhida} aoAlternar={alternar} />

            <div
                className="flex min-h-svh flex-col transition-[padding] duration-200 ease-out motion-reduce:transition-none md:pl-(--largura-lateral)"
                style={
                    {
                        '--largura-lateral': recolhida ? 'var(--sidebar-width-collapsed)' : 'var(--sidebar-width)',
                    } as React.CSSProperties
                }
            >
                <BarraSuperior migalhas={migalhas} />

                {/* pb-20 no celular: espaco pra barra de baixo nao cobrir o conteudo */}
                <main className="flex-1 px-4 pt-4 pb-20 sm:px-6 md:pb-8">
                    <Avisos />
                    {children}
                </main>
            </div>

            <BarraInferior />
        </div>
    );
}

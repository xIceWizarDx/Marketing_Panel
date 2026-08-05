import { Link, usePage } from '@inertiajs/react';
import { PanelLeftClose, PanelLeftOpen } from 'lucide-react';

import Marca from '@/components/marca';
import MenuDoUsuario from '@/components/menu-do-usuario';
import { menuPara } from '@/lib/menu';
import { cn } from '@/lib/utils';
import { type DadosCompartilhados } from '@/types';

interface Props {
    recolhida: boolean;
    aoAlternar: () => void;
}

/**
 * Barra lateral do desktop (>= 768px).
 *
 * A largura vem de --sidebar-width, que muda por faixa de tela (DEC-37):
 *   tablet   180 -> 200px
 *   desktop  200 -> 240px
 * No celular ela nao existe: la a navegacao e a barra inferior.
 */
export default function BarraLateral({ recolhida, aoAlternar }: Props) {
    const { props, url } = usePage<DadosCompartilhados>();
    const papel = props.auth?.usuario?.papel ?? 'cliente';
    const itens = menuPara(papel);

    return (
        <aside
            className={cn(
                'bg-sidebar border-sidebar-border text-sidebar-foreground',
                'fixed inset-y-0 left-0 z-30 hidden flex-col border-r md:flex',
                'transition-[width] duration-200 ease-out motion-reduce:transition-none',
            )}
            style={{ width: recolhida ? 'var(--sidebar-width-collapsed)' : 'var(--sidebar-width)' }}
        >
            <div className={cn('flex h-14 shrink-0 items-center border-b', recolhida ? 'justify-center px-2' : 'px-3')}>
                <Link href="/" className="min-w-0">
                    <Marca compacta={recolhida} />
                </Link>
            </div>

            <nav className="flex-1 overflow-y-auto p-2" aria-label="Menu principal">
                <ul className="space-y-0.5">
                    {itens.map((item) => {
                        const ativo = url === item.url || url.startsWith(`${item.url}/`);

                        const conteudo = (
                            <>
                                {item.icone && <item.icone className="size-4 shrink-0" aria-hidden="true" />}
                                {!recolhida && (
                                    <span className="flex min-w-0 flex-1 items-center gap-2">
                                        <span className="truncate">{item.titulo}</span>
                                        {item.emBreve && (
                                            <span className="bg-muted text-muted-foreground shrink-0 rounded-md px-1.5 py-0.5 text-[0.65rem] leading-none">
                                                em breve
                                            </span>
                                        )}
                                    </span>
                                )}
                            </>
                        );

                        const classes = cn(
                            'flex items-center gap-2.5 rounded-md text-sm transition-colors',
                            recolhida ? 'justify-center px-0 py-2.5' : 'px-2.5 py-2',
                        );

                        // Tela ainda não construída: mostra que existe, mas não
                        // leva a lugar nenhum. Item de menu que dá 404 é pior
                        // que item ausente.
                        if (item.emBreve) {
                            return (
                                <li key={item.url}>
                                    <span
                                        aria-disabled="true"
                                        title={recolhida ? `${item.titulo} (em breve)` : undefined}
                                        className={cn(classes, 'text-sidebar-foreground/40 cursor-default')}
                                    >
                                        {conteudo}
                                    </span>
                                </li>
                            );
                        }

                        return (
                            <li key={item.url}>
                                <Link
                                    href={item.url}
                                    aria-current={ativo ? 'page' : undefined}
                                    title={recolhida ? item.titulo : undefined}
                                    className={cn(
                                        classes,
                                        'focus-visible:ring-ring focus-visible:ring-2 focus-visible:outline-none',
                                        ativo
                                            ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium'
                                            : 'hover:bg-sidebar-accent/60 text-sidebar-foreground/75 hover:text-sidebar-foreground',
                                    )}
                                >
                                    {conteudo}
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>

            <div className="border-sidebar-border space-y-1 border-t p-2">
                <button
                    type="button"
                    onClick={aoAlternar}
                    aria-expanded={!recolhida}
                    className={cn(
                        'hover:bg-sidebar-accent/60 text-sidebar-foreground/70 hover:text-sidebar-foreground',
                        'focus-visible:ring-ring flex w-full items-center gap-2.5 rounded-md text-sm transition-colors focus-visible:ring-2 focus-visible:outline-none',
                        recolhida ? 'justify-center py-2.5' : 'px-2.5 py-2',
                    )}
                >
                    {recolhida ? <PanelLeftOpen className="size-4" aria-hidden="true" /> : <PanelLeftClose className="size-4" aria-hidden="true" />}
                    <span className={recolhida ? 'sr-only' : ''}>Recolher menu</span>
                </button>

                <MenuDoUsuario recolhida={recolhida} />
            </div>
        </aside>
    );
}

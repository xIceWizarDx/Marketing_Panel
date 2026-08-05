import { menuDeToque } from '@/lib/menu';
import { cn } from '@/lib/utils';
import { type DadosCompartilhados } from '@/types';
import { Link, usePage } from '@inertiajs/react';

/**
 * Navegacao do celular (< 768px).
 *
 * Barra fixa embaixo, no alcance do polegar — e onde o app nativo poe a
 * navegacao, e e por isso que o menu tem no maximo 5 itens (DEC-17).
 * `env(safe-area-inset-bottom)` respeita a faixa do iPhone sem notch cortado.
 */
export default function BarraInferior() {
    const { props, url } = usePage<DadosCompartilhados>();
    const papel = props.auth?.usuario?.papel ?? 'cliente';
    const itens = menuDeToque(papel);

    return (
        <nav
            aria-label="Menu principal"
            className="bg-sidebar border-sidebar-border fixed inset-x-0 bottom-0 z-30 border-t md:hidden"
            style={{ paddingBottom: 'env(safe-area-inset-bottom)' }}
        >
            <ul className="flex items-stretch">
                {itens.map((item) => {
                    const ativo = url === item.url || url.startsWith(`${item.url}/`);

                    return (
                        <li key={item.url} className="flex-1">
                            <Link
                                href={item.url}
                                aria-current={ativo ? 'page' : undefined}
                                className={cn(
                                    // min-h-14: alvo de toque de 56px, acima do minimo recomendado de 44px
                                    'flex min-h-14 flex-col items-center justify-center gap-1 px-1 py-2 transition-colors',
                                    'focus-visible:ring-ring focus-visible:ring-2 focus-visible:outline-none focus-visible:ring-inset',
                                    ativo ? 'text-[color:var(--accent)]' : 'text-sidebar-foreground/60',
                                )}
                            >
                                {item.icone && <item.icone className="size-5 shrink-0" aria-hidden="true" />}
                                <span className={cn('w-full truncate text-center text-[0.65rem] leading-none', ativo && 'font-medium')}>
                                    {item.titulo}
                                </span>
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}

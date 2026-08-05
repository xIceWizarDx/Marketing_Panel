import { Link, usePage } from '@inertiajs/react';

import CabecalhoDePagina from '@/components/cabecalho-de-pagina';
import LayoutPainel from '@/layouts/painel';
import { itemAtivo, menuDaConta } from '@/lib/menu';
import { cn } from '@/lib/utils';

/**
 * Abas da area "Minha conta".
 *
 * Abas em vez de menu lateral secundario: sao tres telas curtas, e no celular a
 * lista rola na horizontal em vez de empilhar e empurrar o conteudo pra baixo.
 */
export default function LayoutMinhaConta({ children }: { children: React.ReactNode }) {
    const { url } = usePage();

    return (
        <LayoutPainel migalhas={[{ titulo: 'Minha conta', url: '/minha-conta/perfil' }]}>
            <CabecalhoDePagina titulo="Minha conta" descricao="Seus dados de acesso e preferências." />

            <div className="border-border -mx-4 mb-6 overflow-x-auto border-b px-4 sm:mx-0 sm:px-0">
                <nav aria-label="Seções da conta" className="flex min-w-max gap-1">
                    {menuDaConta.map((item) => {
                        const ativo = itemAtivo(item, url);

                        return (
                            <Link
                                key={item.url}
                                href={item.url}
                                aria-current={ativo ? 'page' : undefined}
                                className={cn(
                                    'focus-visible:ring-ring -mb-px rounded-t-md border-b-2 px-3 py-2 text-sm transition-colors focus-visible:ring-2 focus-visible:outline-none',
                                    ativo
                                        ? 'text-foreground border-[color:var(--accent)] font-medium'
                                        : 'text-muted-foreground hover:text-foreground border-transparent',
                                )}
                            >
                                {item.titulo}
                            </Link>
                        );
                    })}
                </nav>
            </div>

            <div className="max-w-xl space-y-10">{children}</div>
        </LayoutPainel>
    );
}

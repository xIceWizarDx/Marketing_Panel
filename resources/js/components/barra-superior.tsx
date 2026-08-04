import { Link } from '@inertiajs/react';
import { Fragment } from 'react';

import MenuDoUsuario from '@/components/menu-do-usuario';
import SeletorDeGrupo from '@/components/seletor-de-grupo';
import { type Migalha } from '@/types';

/**
 * Barra do topo.
 *
 * ⭐ E aqui que mora o SELETOR DE GRUPO, em toda largura: saber em qual grupo
 * se esta tem que ser gratuito, porque publicar no grupo errado nao desfaz.
 *
 * ⚠️ No celular o seletor toma o lugar do logotipo. O logo e decoracao; o modo
 * nao e — e a tela estreita nao comporta os dois.
 */
export default function BarraSuperior({ migalhas = [] }: { migalhas?: Migalha[] }) {
    return (
        <header className="bg-background/85 border-border sticky top-0 z-20 flex h-14 shrink-0 items-center gap-3 border-b px-4 backdrop-blur">
            <div className="min-w-0 md:hidden">
                <SeletorDeGrupo />
            </div>

            {migalhas.length > 0 && (
                <nav aria-label="Você está em" className="hidden min-w-0 md:block">
                    <ol className="text-muted-foreground flex items-center gap-1.5 text-sm">
                        {migalhas.map((migalha, indice) => {
                            const ultima = indice === migalhas.length - 1;

                            return (
                                <Fragment key={migalha.url}>
                                    <li className="min-w-0">
                                        {ultima ? (
                                            <span className="text-foreground truncate font-medium" aria-current="page">
                                                {migalha.titulo}
                                            </span>
                                        ) : (
                                            <Link href={migalha.url} className="hover:text-foreground truncate transition-colors">
                                                {migalha.titulo}
                                            </Link>
                                        )}
                                    </li>
                                    {!ultima && (
                                        <li aria-hidden="true" className="select-none">
                                            /
                                        </li>
                                    )}
                                </Fragment>
                            );
                        })}
                    </ol>
                </nav>
            )}

            <div className="ml-auto flex items-center gap-2">
                <div className="hidden md:block">
                    <SeletorDeGrupo />
                </div>

                <div className="md:hidden">
                    <MenuDoUsuario somenteAvatar />
                </div>
            </div>
        </header>
    );
}

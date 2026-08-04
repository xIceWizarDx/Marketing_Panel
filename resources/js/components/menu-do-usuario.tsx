import { Link, usePage } from '@inertiajs/react';
import { ChevronsUpDown, LogOut, Palette, KeyRound, UserCog } from 'lucide-react';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useIniciais } from '@/hooks/use-iniciais';
import { cn } from '@/lib/utils';
import { type DadosCompartilhados } from '@/types';

interface Props {
    recolhida?: boolean;
    /** No celular o gatilho e so o avatar, sem nome nem seta. */
    somenteAvatar?: boolean;
}

export default function MenuDoUsuario({ recolhida = false, somenteAvatar = false }: Props) {
    const { auth } = usePage<DadosCompartilhados>().props;
    const iniciaisDe = useIniciais();
    const usuario = auth?.usuario;

    if (!usuario) {
        return null;
    }

    const compacto = recolhida || somenteAvatar;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                className={cn(
                    'hover:bg-sidebar-accent/60 focus-visible:ring-ring flex items-center gap-2 rounded-md transition-colors focus-visible:ring-2 focus-visible:outline-none',
                    compacto ? 'justify-center p-1' : 'w-full px-2 py-1.5',
                )}
                aria-label="Menu da conta"
            >
                <span
                    aria-hidden="true"
                    className="bg-[color:var(--accent)] text-[color:var(--accent-foreground)] flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                >
                    {iniciaisDe(usuario.nome)}
                </span>

                {!compacto && (
                    <>
                        <span className="min-w-0 flex-1 text-left">
                            <span className="block truncate text-sm leading-tight font-medium">{usuario.nome}</span>
                            <span className="text-muted-foreground block truncate text-xs leading-tight">{usuario.papelRotulo}</span>
                        </span>
                        <ChevronsUpDown className="text-muted-foreground size-3.5 shrink-0" aria-hidden="true" />
                    </>
                )}
            </DropdownMenuTrigger>

            <DropdownMenuContent align="end" side="top" className="min-w-56">
                <DropdownMenuLabel className="font-normal">
                    <span className="block truncate text-sm font-medium">{usuario.nome}</span>
                    <span className="text-muted-foreground block truncate text-xs">{usuario.email}</span>
                </DropdownMenuLabel>

                <DropdownMenuSeparator />

                <DropdownMenuItem asChild>
                    <Link href={route('minha-conta.perfil.editar')} className="w-full">
                        <UserCog className="mr-2 size-4" aria-hidden="true" />
                        Meu perfil
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuItem asChild>
                    <Link href={route('minha-conta.senha.editar')} className="w-full">
                        <KeyRound className="mr-2 size-4" aria-hidden="true" />
                        Senha
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuItem asChild>
                    <Link href={route('minha-conta.aparencia')} className="w-full">
                        <Palette className="mr-2 size-4" aria-hidden="true" />
                        Aparência
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuSeparator />

                <DropdownMenuItem asChild>
                    <Link href={route('sair')} method="post" as="button" className="w-full">
                        <LogOut className="mr-2 size-4" aria-hidden="true" />
                        Sair
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

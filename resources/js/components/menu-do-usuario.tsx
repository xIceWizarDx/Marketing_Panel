import { Link, usePage } from '@inertiajs/react';
import { ChevronsUpDown, KeyRound, LogOut, Palette, UserCog } from 'lucide-react';

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
    /**
     * No celular o gatilho é só o avatar, sem nome nem seta.
     *
     * ⚠️ Não existe mais um modo "recolhido" aqui. Na barra lateral quem some
     * com o nome é a **borda da barra**, cortando — e é isso que faz o nome
     * deslizar em vez de piscar. Quem avisa de quem é a conta com a barra
     * estreita é a dica, e ela mora lá, não aqui.
     */
    somenteAvatar?: boolean;
}

export default function MenuDoUsuario({ somenteAvatar = false }: Props) {
    const { auth } = usePage<DadosCompartilhados>().props;
    const iniciaisDe = useIniciais();
    const usuario = auth?.usuario;

    if (!usuario) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                className={cn(
                    'hover:bg-sidebar-accent/60 focus-visible:ring-ring flex items-center gap-2 overflow-hidden rounded-md transition-colors focus-visible:ring-2 focus-visible:outline-none',
                    somenteAvatar ? 'justify-center p-1' : 'w-full py-1.5 pr-2',
                )}
                aria-label="Menu da conta"
            >
                {/*
                 * ⭐ O avatar divide o EIXO DOS ÍCONES com os itens do menu.
                 *
                 * ⚠️ Ele é maior que um ícone de menu (32px contra 16px), mas o
                 * que precisa coincidir é o CENTRO, não o tamanho. Sem o
                 * compartimento, ele andava alguns pixels para o lado enquanto a
                 * barra recolhia e era o único elemento fora de esquadro na
                 * coluna inteira.
                 */}
                <span
                    className={cn('flex shrink-0 items-center justify-center', somenteAvatar && 'w-auto')}
                    style={somenteAvatar ? undefined : { width: 'max(calc(var(--sidebar-width-collapsed) - 1rem), 3rem)' }}
                >
                    <span
                        aria-hidden="true"
                        className="flex size-8 shrink-0 items-center justify-center rounded-full bg-[color:var(--accent)] text-xs font-semibold text-[color:var(--accent-foreground)]"
                    >
                        {iniciaisDe(usuario.nome)}
                    </span>
                </span>

                {/* ⚠️ Presente sempre: é a borda da barra que corta, não o
                    React que apaga. Removido do DOM, o nome sumia de uma vez no
                    primeiro quadro da animação — piscada, não deslizamento. */}
                {!somenteAvatar && (
                    <>
                        <span className="min-w-0 flex-1 overflow-hidden text-left whitespace-nowrap">
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

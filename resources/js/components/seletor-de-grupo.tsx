import { Link, router, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown, Layers, Settings2 } from 'lucide-react';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { type DadosCompartilhados } from '@/types';

/**
 * ⭐ Em qual grupo você está — **e ele faz uma coisa só: trocar**.
 *
 * Grupo não tem tela e não é item de menu: é **modo** (DEC-71). Filtro se
 * esquece de marcar; modo se está dentro. Como o acidente que este produto
 * existe para evitar é publicar no lugar errado, saber onde se está tem que ser
 * gratuito — daí ele viver na barra do topo, visível em toda tela.
 *
 * ⛔ **Criar, renomear e arquivar NÃO moram aqui.** São configuração, e vivem em
 * Minha conta › Grupos. Trocar de grupo é o gesto de todo dia; administrar é o
 * de uma vez por mês — juntos, o raro atrapalha o frequente e a lista de grupos
 * some no meio de botões.
 *
 * ⛔ O estado vem sempre do servidor. Nada de `useState` guardando qual grupo
 * está ativo, nada de `localStorage`: duas abas discordariam, e a que estivesse
 * errada mostraria a lista de um grupo enquanto publica no outro.
 */
export default function SeletorDeGrupo() {
    const { grupos } = usePage<DadosCompartilhados>().props;

    // Admin e visitante não publicam: para eles o seletor seria enfeite.
    if (!grupos?.atual) {
        return null;
    }

    const { atual, lista } = grupos;

    // ⚠️ POST: com GET, o prefetch do navegador trocaria o modo sozinho.
    const trocar = (ulid: string) => ulid !== atual.ulid && router.post(route('grupos.usar', ulid), {}, { preserveScroll: true });

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <button
                    type="button"
                    className="border-border focus-visible:ring-ring flex max-w-[13rem] items-center gap-2 rounded-lg border px-2.5 py-1.5 text-sm transition-colors hover:border-[color:var(--accent)] focus-visible:ring-2 focus-visible:outline-none"
                >
                    <Layers className="text-muted-foreground size-4 shrink-0" aria-hidden="true" />
                    <span className="truncate font-medium">{atual.nome}</span>
                    <ChevronsUpDown className="text-muted-foreground ml-auto size-3.5 shrink-0" aria-hidden="true" />
                </button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="start" className="w-60">
                <DropdownMenuLabel className="text-muted-foreground text-xs font-normal">Você está publicando em</DropdownMenuLabel>

                {lista.map((grupo) => (
                    <DropdownMenuItem key={grupo.ulid} onSelect={() => trocar(grupo.ulid)} className="gap-2">
                        <Check className={`size-4 shrink-0 ${grupo.ulid === atual.ulid ? '' : 'invisible'}`} aria-hidden="true" />
                        <span className="truncate">{grupo.nome}</span>
                    </DropdownMenuItem>
                ))}

                <DropdownMenuSeparator />

                {/* A porta para a configuração — um item só, no fim, onde ele não
                    compete com a lista que a pessoa veio ver. */}
                <DropdownMenuItem asChild className="gap-2">
                    <Link href={route('grupos')}>
                        <Settings2 className="size-4 shrink-0" aria-hidden="true" />
                        Gerenciar grupos
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

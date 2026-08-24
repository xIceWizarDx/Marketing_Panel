import { Link, usePage } from '@inertiajs/react';
import { ChevronsLeft, ChevronsRight } from 'lucide-react';
import { type ReactNode } from 'react';

import Marca from '@/components/marca';
import MenuDoUsuario from '@/components/menu-do-usuario';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { itemAtivo, menuPara } from '@/lib/menu';
import { cn } from '@/lib/utils';
import { type DadosCompartilhados } from '@/types';

interface Props {
    recolhida: boolean;
    aoAlternar: () => void;
}

/**
 * ⭐ **O EIXO DO ÍCONE — a regra que sustenta a animação inteira.**
 *
 * Todo ícone da barra vive num compartimento de largura fixa, calculada a partir
 * da largura RECOLHIDA. O centro desse compartimento é o mesmo com a barra
 * aberta e fechada, então o ícone **nunca anda para o lado** enquanto a largura
 * anima: só o texto ao lado dele entra e sai.
 *
 * ⚠️ Sem isto — trocando `justify-center` por `padding` conforme o estado — cada
 * ícone salta alguns pixels na horizontal no meio da transição, e a barra inteira
 * parece tremer. Era esse o "não refinado".
 *
 * ⚠️ **A conta tem que fechar exatamente.** O `- 1rem` desconta o `px-2` da
 * lista, então o compartimento ocupa toda a largura útil do item recolhido e o
 * centro dele cai no centro da barra: `8px de padding + 26px = 34px`, que é
 * metade de 68px. Descontar o padding **duas vezes** joga o ícone 8px para a
 * esquerda — parece centrado sozinho e denuncia quando está ao lado do símbolo
 * da marca, que está certo.
 *
 * O `max(..., 3rem)` é o piso: no tablet a barra recolhida encolhe, e sem o piso
 * o compartimento ficaria menor que o alvo de toque.
 */
const EIXO_DO_ICONE = 'max(calc(var(--sidebar-width-collapsed) - 1rem), 3rem)';

/**
 * Barra lateral do desktop (>= 768px).
 *
 * A largura vem de `--sidebar-width`, que muda por faixa de tela (DEC-37):
 * tablet 180→200px, desktop 200→240px. No celular ela não existe: lá a navegação
 * é a barra inferior.
 *
 * ⭐ **O cabeçalho inteiro é o botão de recolher.** A alternância morava num
 * botão no rodapé, longe de onde o olho vai e longe da borda que se mexe. Aqui
 * ela fica onde a barra começa, e o alvo é a linha toda em vez de um ícone de
 * 16px.
 *
 * ⚠️ **Nenhum texto é removido do DOM ao recolher** — nem o nome dos itens, nem
 * o nome do produto no cabeçalho. Todos são *cortados* pela borda. Removido, o
 * texto some de uma vez no primeiro quadro, e como ele ocupava largura, o que
 * estava ao lado dele **anda** para preencher o vazio: era isso que fazia o
 * símbolo da marca escorregar enquanto a barra fechava.
 */
export default function BarraLateral({ recolhida, aoAlternar }: Props) {
    const { props, url } = usePage<DadosCompartilhados>();
    const papel = props.auth?.usuario?.papel ?? 'cliente';
    const itens = menuPara(papel);

    return (
        // ⚠️ `delayDuration` curto: a dica só existe porque o rótulo está
        // escondido. Meio segundo de espera para ler o nome de um item de menu
        // faz a pessoa desistir e clicar para descobrir.
        <TooltipProvider delayDuration={150} skipDelayDuration={0}>
            <aside
                className={cn(
                    'bg-sidebar border-sidebar-border text-sidebar-foreground',
                    'fixed inset-y-0 left-0 z-30 hidden flex-col overflow-x-hidden border-r md:flex',
                    'transition-[width] duration-200 ease-out motion-reduce:transition-none',
                )}
                style={{ width: recolhida ? 'var(--sidebar-width-collapsed)' : 'var(--sidebar-width)' }}
            >
                {/* ── O CABEÇALHO, QUE É O BOTÃO ── */}
                <button
                    type="button"
                    onClick={aoAlternar}
                    aria-expanded={!recolhida}
                    aria-label={recolhida ? 'Expandir menu' : 'Recolher menu'}
                    title={recolhida ? 'Expandir menu' : 'Recolher menu'}
                    className={cn(
                        'border-sidebar-border hover:bg-sidebar-accent/50 focus-visible:ring-ring',
                        'relative flex h-14 w-full shrink-0 items-center gap-0.5 overflow-hidden border-b transition-colors focus-visible:ring-2 focus-visible:outline-none',
                    )}
                >
                    {/* ⭐ **O símbolo também mora no eixo** — e o compartimento aqui
                        é a largura RECOLHIDA inteira, porque o cabeçalho não tem o
                        `px-2` que a lista tem. As duas contas caem no mesmo lugar:
                        `68/2 = 34px` aqui, `8 + 52/2 = 34px` lá embaixo. O símbolo
                        fica no mesmo prumo dos ícones dos itens, e não anda um
                        pixel ao recolher.

                        ⛔ E o tamanho dele não muda entre os dois estados. Encolher
                        o símbolo ao recolher é uma segunda coisa se mexendo ao
                        mesmo tempo que a largura — o olho lê como tremida. */}
                    <span className="flex shrink-0 items-center justify-center" style={{ width: 'var(--sidebar-width-collapsed)' }}>
                        <Marca compacta />
                    </span>

                    {/* ⚠️ **O nome NUNCA sai do DOM** — é a borda da barra que o
                        corta, exatamente como nos itens do menu. Removendo, ele
                        sumia de uma vez no primeiro quadro e a largura do
                        cabeçalho mudava junto, empurrando o símbolo. Era isso que
                        fazia a logo "deslocar".

                        O `pr-9` reserva o canto da seta, que flutua por cima. */}
                    <span className="min-w-0 flex-1 overflow-hidden pr-9 text-left">
                        <Marca somenteNome />
                    </span>

                    {/* ⚠️ **Fora do fluxo, de propósito.** Recolhida, a barra tem
                        68px e o compartimento do símbolo já os ocupa inteiros —
                        no fluxo, a seta seria empurrada para fora e cortada, e a
                        barra fechada ficaria sem nada dizendo que ela abre. */}
                    <span className="absolute inset-y-0 right-1 flex items-center">
                        {recolhida ? (
                            /* ⭐ O empurrãozinho de 3px vive só aqui: recolhida, a
                               barra é uma coluna de ícones e nada nela diz que
                               ela abre. */
                            <ChevronsRight className="text-sidebar-foreground/60 dica-de-expandir size-[14px] shrink-0" aria-hidden="true" />
                        ) : (
                            <ChevronsLeft className="text-sidebar-foreground/60 size-[18px] shrink-0" aria-hidden="true" />
                        )}
                    </span>
                </button>

                {/* ── OS ITENS ── */}
                <nav className="flex-1 overflow-x-hidden overflow-y-auto py-3" aria-label="Menu principal">
                    <ul className="space-y-0.5 px-2">
                        {itens.map((item) => {
                            const ativo = itemAtivo(item, url);

                            const miolo = (
                                <>
                                    <span className="flex shrink-0 items-center justify-center" style={{ width: EIXO_DO_ICONE }}>
                                        {item.icone && <item.icone className="size-[22px] shrink-0" aria-hidden="true" />}
                                    </span>

                                    {/* ⚠️ Sempre presente, sempre numa linha só:
                                        é a borda da barra que o corta. */}
                                    <span className="flex min-w-0 flex-1 items-center gap-2 overflow-hidden whitespace-nowrap">
                                        <span className="truncate">{item.titulo}</span>
                                        {item.emBreve && (
                                            <span className="bg-muted text-muted-foreground shrink-0 rounded-md px-1.5 py-0.5 text-[0.8125rem] leading-none">
                                                em breve
                                            </span>
                                        )}
                                    </span>
                                </>
                            );

                            const forma = 'flex items-center gap-2.5 overflow-hidden rounded-md py-2 pr-2 text-sm transition-colors';

                            // Tela ainda não construída: mostra que existe, mas não
                            // leva a lugar nenhum. Item de menu que dá 404 é pior
                            // que item ausente.
                            const elemento = item.emBreve ? (
                                <span aria-disabled="true" className={cn(forma, 'text-sidebar-foreground/40 cursor-default')}>
                                    {miolo}
                                </span>
                            ) : (
                                <Link
                                    href={item.url}
                                    aria-current={ativo ? 'page' : undefined}
                                    className={cn(
                                        forma,
                                        'focus-visible:ring-ring focus-visible:ring-2 focus-visible:outline-none',
                                        ativo
                                            ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium'
                                            : 'hover:bg-sidebar-accent/60 text-sidebar-foreground/75 hover:text-sidebar-foreground',
                                    )}
                                >
                                    {miolo}
                                </Link>
                            );

                            return (
                                <li key={item.url}>
                                    <ComDica ativa={recolhida} texto={item.emBreve ? `${item.titulo} (em breve)` : item.titulo}>
                                        {elemento}
                                    </ComDica>
                                </li>
                            );
                        })}
                    </ul>
                </nav>

                {/* ── O RODAPÉ ──
                    ⛔ O tema NÃO mora aqui: ele vive na barra do topo, ao lado do
                    seletor de grupo. Dois lugares para o mesmo gesto é como
                    nasce o "mudei ali e não mudou aqui". */}
                <div className="border-sidebar-border border-t px-2 py-2">
                    <ComDica ativa={recolhida} texto="Sua conta">
                        <span className="block">
                            <MenuDoUsuario />
                        </span>
                    </ComDica>
                </div>
            </aside>
        </TooltipProvider>
    );
}

/**
 * Embrulha o item na dica — e **só quando ela tem função**.
 *
 * ⛔ Com a barra aberta o nome está escrito ao lado do ícone; uma dica repetindo
 * a palavra que já se lê é ruído com atraso.
 */
function ComDica({ ativa, texto, children }: { ativa: boolean; texto: string; children: ReactNode }) {
    if (!ativa) {
        return <>{children}</>;
    }

    return (
        <Tooltip>
            <TooltipTrigger asChild>{children}</TooltipTrigger>
            <TooltipContent side="right">{texto}</TooltipContent>
        </Tooltip>
    );
}

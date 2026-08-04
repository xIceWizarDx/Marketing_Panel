import { router, useForm, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown, Layers, Pencil, Plus, Trash2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import ErroDeCampo from '@/components/erro-de-campo';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type DadosCompartilhados } from '@/types';

/**
 * ⭐ Em qual grupo você está — **e a única porta de entrada da funcionalidade**.
 *
 * Grupo não tem tela e não é item de menu: é **modo** (DEC-71). Filtro se
 * esquece de marcar; modo se está dentro. Como o acidente que este produto
 * existe para evitar é publicar no lugar errado, saber onde se está tem que ser
 * gratuito — daí ele viver na barra do topo, visível em toda tela.
 *
 * ⚠️ Aparece **mesmo com um grupo só**. Com um, ele mostra o nome e oferece
 * criar outro; escondê-lo faria a funcionalidade não existir para quem ainda
 * não sabe que ela existe.
 *
 * ⛔ O estado vem sempre do servidor. Nada de `useState` guardando qual grupo
 * está ativo, nada de `localStorage`: duas abas discordariam, e a que estivesse
 * errada mostraria a lista de um grupo enquanto publica no outro.
 */
export default function SeletorDeGrupo() {
    const { grupos } = usePage<DadosCompartilhados>().props;

    const [criando, setCriando] = useState(false);
    const [renomeando, setRenomeando] = useState(false);
    const [aArquivar, setAArquivar] = useState(false);

    // Admin e visitante não publicam: para eles o seletor seria enfeite.
    if (!grupos?.atual) {
        return null;
    }

    const { atual, lista } = grupos;
    const trocar = (ulid: string) => ulid !== atual.ulid && router.post(route('grupos.usar', ulid), {}, { preserveScroll: true });

    return (
        <>
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

                    <DropdownMenuItem onSelect={() => setCriando(true)} className="gap-2">
                        <Plus className="size-4 shrink-0" aria-hidden="true" />
                        Criar um grupo
                    </DropdownMenuItem>
                    <DropdownMenuItem onSelect={() => setRenomeando(true)} className="gap-2">
                        <Pencil className="size-4 shrink-0" aria-hidden="true" />
                        Renomear «{atual.nome}»
                    </DropdownMenuItem>
                    <DropdownMenuItem onSelect={() => setAArquivar(true)} className="gap-2">
                        <Trash2 className="size-4 shrink-0" aria-hidden="true" />
                        Arquivar «{atual.nome}»
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <JanelaDeNome
                aberta={criando}
                aoFechar={() => setCriando(false)}
                titulo="Criar um grupo"
                /* ⚠️ Diz o que NÃO vai acontecer. Sem esta frase, a pessoa
                   espera que o painel mude e estranha quando não muda — e se
                   mudasse, ela acharia que perdeu o trabalho (DEC-78). */
                descricao="Um grupo é uma linha de conteúdo com seus próprios canais. Seus canais e publicações de agora continuam onde estão."
                rotulo="Nome do grupo"
                exemplo="Notícias"
                confirmar="Criar"
                metodo="post"
                destino={route('grupos.criar')}
            />

            <JanelaDeNome
                aberta={renomeando}
                aoFechar={() => setRenomeando(false)}
                titulo={`Renomear «${atual.nome}»`}
                descricao="Só muda o nome. Os canais e o histórico continuam iguais."
                rotulo="Novo nome"
                exemplo={atual.nome}
                valorInicial={atual.nome}
                confirmar="Salvar"
                metodo="patch"
                destino={route('grupos.renomear', atual.ulid)}
            />

            <Dialog open={aArquivar} onOpenChange={(estado) => !estado && setAArquivar(false)}>
                <DialogContent>
                    <DialogTitle>Arquivar «{atual.nome}»?</DialogTitle>
                    <DialogDescription>
                        Ele sai do seletor e você deixa de publicar por ele. Só dá para arquivar um grupo sem canais — se ainda houver algum, mova ou
                        desconecte antes.
                    </DialogDescription>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="secondary">Cancelar</Button>
                        </DialogClose>
                        <Button
                            variant="destructive"
                            onClick={() =>
                                router.delete(route('grupos.arquivar', atual.ulid), {
                                    preserveScroll: true,
                                    onFinish: () => setAArquivar(false),
                                })
                            }
                        >
                            Arquivar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

/** Criar e renomear pedem a mesma coisa: um nome. */
function JanelaDeNome({
    aberta,
    aoFechar,
    titulo,
    descricao,
    rotulo,
    exemplo,
    valorInicial = '',
    confirmar,
    metodo,
    destino,
}: {
    aberta: boolean;
    aoFechar: () => void;
    titulo: string;
    descricao: string;
    rotulo: string;
    exemplo: string;
    valorInicial?: string;
    confirmar: string;
    metodo: 'post' | 'patch';
    destino: string;
}) {
    const { data, setData, post, patch, processing, errors, reset, clearErrors } = useForm({ nome: valorInicial });

    const enviar: FormEventHandler = (evento) => {
        evento.preventDefault();

        const opcoes = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                aoFechar();
            },
        };

        if (metodo === 'post') {
            post(destino, opcoes);
        } else {
            patch(destino, opcoes);
        }
    };

    return (
        <Dialog
            open={aberta}
            onOpenChange={(estado) => {
                if (!estado) {
                    clearErrors();
                    aoFechar();
                }
            }}
        >
            <DialogContent>
                <DialogTitle>{titulo}</DialogTitle>
                <DialogDescription>{descricao}</DialogDescription>

                <form onSubmit={enviar} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="nome-do-grupo">{rotulo}</Label>
                        <Input
                            id="nome-do-grupo"
                            value={data.nome}
                            onChange={(e) => setData('nome', e.target.value)}
                            placeholder={exemplo}
                            maxLength={60}
                            autoComplete="off"
                            aria-invalid={!!errors.nome}
                        />
                        <ErroDeCampo mensagem={errors.nome} />
                    </div>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Cancelar
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing || !data.nome.trim()}>
                            {confirmar}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

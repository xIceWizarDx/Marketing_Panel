import { router, useForm, usePage } from '@inertiajs/react';
import { Check, Layers, Pencil, Plus, Trash2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import ErroDeCampo from '@/components/erro-de-campo';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type DadosCompartilhados, type Grupo } from '@/types';

/**
 * Onde os grupos se administram — **por cima da tela em que a pessoa já estava**.
 *
 * ⭐ Não é tela, e nem precisa ser: criar e renomear são gestos de um campo só,
 * e arquivar é uma confirmação. Uma página inteira para isso obrigaria a sair
 * de onde se está e voltar depois, para uma tarefa de quinze segundos.
 *
 * ⚠️ Abre do **seletor**, que vive na barra do topo de toda tela. Enterrar isto
 * dentro de Minha conta faria a pessoa procurar em configurações uma coisa que
 * ela tem na frente dos olhos o tempo todo.
 *
 * ⛔ Os números de canais e publicações não são enfeite: são o **motivo** de o
 * botão de arquivar estar apagado. Sumir com o botão deixaria a pessoa sem
 * saber o que fazer para conseguir arquivar.
 */
export default function GerenciarGrupos({ aberta, aoFechar }: { aberta: boolean; aoFechar: () => void }) {
    const { grupos } = usePage<DadosCompartilhados>().props;

    const [criando, setCriando] = useState(false);
    const [aRenomear, setARenomear] = useState<Grupo | null>(null);
    const [aArquivar, setAArquivar] = useState<Grupo | null>(null);

    if (!grupos?.atual) {
        return null;
    }

    const { atual, lista } = grupos;

    return (
        <>
            <Dialog open={aberta} onOpenChange={(estado) => !estado && aoFechar()}>
                <DialogContent className="max-h-[85svh] overflow-y-auto sm:max-w-lg">
                    <DialogTitle>Seus grupos</DialogTitle>
                    <DialogDescription>
                        Um grupo é uma linha de conteúdo com seus próprios canais — notícias e novelas, por exemplo. Uma publicação sai de um grupo
                        só.
                    </DialogDescription>

                    <ul className="border-border divide-border divide-y rounded-lg border">
                        {lista.map((grupo) => {
                            const porQueNaoArquiva =
                                grupo.canais > 0
                                    ? 'Mova ou desconecte os canais antes de arquivar'
                                    : lista.length === 1
                                      ? 'Você precisa de pelo menos um grupo'
                                      : null;

                            return (
                                <li key={grupo.ulid} className="flex flex-wrap items-center gap-x-3 gap-y-2 p-3">
                                    <Layers className="text-muted-foreground size-4 shrink-0" aria-hidden="true" />

                                    <div className="min-w-0 flex-1">
                                        <p className="flex items-center gap-2 text-sm font-medium">
                                            <span className="truncate">{grupo.nome}</span>
                                            {grupo.ulid === atual.ulid && (
                                                <span className="text-muted-foreground inline-flex shrink-0 items-center gap-1 text-xs font-normal">
                                                    <Check className="size-3" aria-hidden="true" />
                                                    em uso
                                                </span>
                                            )}
                                        </p>
                                        <p className="text-muted-foreground text-xs">
                                            {contar(grupo.canais, 'canal', 'canais')} · {contar(grupo.publicacoes, 'publicação', 'publicações')}
                                        </p>
                                    </div>

                                    <div className="flex shrink-0 items-center gap-0.5">
                                        <button
                                            type="button"
                                            onClick={() => setARenomear(grupo)}
                                            aria-label={`Renomear ${grupo.nome}`}
                                            className="text-muted-foreground hover:text-foreground focus-visible:ring-ring rounded-md p-1.5 transition-colors focus-visible:ring-2 focus-visible:outline-none"
                                        >
                                            <Pencil className="size-3.5" aria-hidden="true" />
                                        </button>

                                        <button
                                            type="button"
                                            disabled={!!porQueNaoArquiva}
                                            title={porQueNaoArquiva ?? `Arquivar ${grupo.nome}`}
                                            onClick={() => setAArquivar(grupo)}
                                            aria-label={`Arquivar ${grupo.nome}`}
                                            className="text-muted-foreground focus-visible:ring-ring rounded-md p-1.5 transition-colors hover:text-[color:var(--destructive)] focus-visible:ring-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:text-current"
                                        >
                                            <Trash2 className="size-3.5" aria-hidden="true" />
                                        </button>
                                    </div>
                                </li>
                            );
                        })}
                    </ul>

                    <Button variant="secondary" onClick={() => setCriando(true)}>
                        <Plus className="mr-1.5 size-4" aria-hidden="true" />
                        Criar um grupo
                    </Button>
                </DialogContent>
            </Dialog>

            <JanelaDeNome
                aberta={criando}
                aoFechar={() => setCriando(false)}
                titulo="Criar um grupo"
                /* ⚠️ Diz o que NÃO vai acontecer. Sem esta frase, a pessoa espera
                   que o painel mude — e se mudasse, ela acharia que perdeu o
                   trabalho, porque a tela de vazio afirma que ela nunca publicou
                   nada (DEC-78). */
                descricao="Ele nasce vazio, e seus canais e publicações de agora continuam onde estão. Depois você conecta canais nele, ou traz um de outro grupo."
                rotulo="Nome do grupo"
                exemplo="Notícias"
                confirmar="Criar"
                metodo="post"
                destino={route('grupos.criar')}
            />

            <JanelaDeNome
                aberta={!!aRenomear}
                aoFechar={() => setARenomear(null)}
                titulo={`Renomear «${aRenomear?.nome}»`}
                descricao="Só muda o nome. Os canais e o histórico continuam iguais."
                rotulo="Novo nome"
                exemplo={aRenomear?.nome ?? ''}
                valorInicial={aRenomear?.nome ?? ''}
                confirmar="Salvar"
                metodo="patch"
                destino={aRenomear ? route('grupos.renomear', aRenomear.ulid) : ''}
            />

            <Dialog open={!!aArquivar} onOpenChange={(estado) => !estado && setAArquivar(null)}>
                <DialogContent>
                    <DialogTitle>Arquivar «{aArquivar?.nome}»?</DialogTitle>
                    <DialogDescription>
                        Ele sai do seletor e você deixa de publicar por ele.{' '}
                        {(aArquivar?.publicacoes ?? 0) > 0
                            ? 'As publicações que já saíram continuam no histórico.'
                            : 'Ele está vazio, então nada se perde.'}
                    </DialogDescription>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="secondary">Cancelar</Button>
                        </DialogClose>
                        <Button
                            variant="destructive"
                            onClick={() =>
                                aArquivar &&
                                router.delete(route('grupos.arquivar', aArquivar.ulid), {
                                    preserveScroll: true,
                                    onFinish: () => setAArquivar(null),
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

/** "1 canal" / "3 canais" — plural sem gambiarra de `(s)`. */
function contar(quantos: number, singular: string, plural: string): string {
    return `${quantos} ${quantos === 1 ? singular : plural}`;
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

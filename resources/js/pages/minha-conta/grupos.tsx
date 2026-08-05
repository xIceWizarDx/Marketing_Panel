import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Check, Layers, Pencil, Plus, Trash2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import ErroDeCampo from '@/components/erro-de-campo';
import TituloDeSecao from '@/components/titulo-de-secao';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import LayoutMinhaConta from '@/layouts/minha-conta';
import { type DadosCompartilhados } from '@/types';

interface GrupoDaTela {
    ulid: string;
    nome: string;
    canais: number;
    publicacoes: number;
}

/**
 * Onde os grupos se administram.
 *
 * ⭐ **Criar, renomear e arquivar são configuração, não navegação.** O seletor
 * do topo faz uma coisa só — trocar de grupo, que é o gesto de todo dia.
 * Misturar nele o que se faz uma vez por mês faria os dois disputarem o mesmo
 * espaço, e o gesto raro é sempre o que atrapalha o frequente.
 */
export default function Grupos({ grupos }: { grupos: GrupoDaTela[] }) {
    const atual = usePage<DadosCompartilhados>().props.grupos?.atual;

    const [criando, setCriando] = useState(false);
    const [aRenomear, setARenomear] = useState<GrupoDaTela | null>(null);
    const [aArquivar, setAArquivar] = useState<GrupoDaTela | null>(null);

    return (
        <LayoutMinhaConta>
            <Head title="Grupos" />

            <section>
                <TituloDeSecao
                    titulo="Grupos"
                    descricao="Um grupo é uma linha de conteúdo com seus próprios canais — notícias e novelas, por exemplo. Uma publicação sai de um grupo só."
                />

                <ul className="border-border bg-card divide-border divide-y rounded-xl border">
                    {grupos.map((grupo) => {
                        const emFoco = grupo.ulid === atual?.ulid;
                        // ⛔ Não dá para arquivar grupo com canal nem o último.
                        // A tela diz o motivo em vez de sumir com o botão.
                        const porQueNaoArquiva =
                            grupo.canais > 0
                                ? 'Mova ou desconecte os canais antes de arquivar'
                                : grupos.length === 1
                                  ? 'Você precisa de pelo menos um grupo'
                                  : null;

                        return (
                            <li key={grupo.ulid} className="flex flex-wrap items-center gap-x-3 gap-y-2 p-4">
                                <Layers className="text-muted-foreground size-4 shrink-0" aria-hidden="true" />

                                <div className="min-w-0 flex-1">
                                    <p className="flex items-center gap-2 text-sm font-medium">
                                        <span className="truncate">{grupo.nome}</span>
                                        {emFoco && (
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

                                <div className="flex shrink-0 items-center gap-1">
                                    <Button variant="ghost" size="sm" className="h-8 px-2" onClick={() => setARenomear(grupo)}>
                                        <Pencil className="mr-1 size-3.5" aria-hidden="true" />
                                        Renomear
                                    </Button>

                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="h-8 px-2"
                                        disabled={!!porQueNaoArquiva}
                                        title={porQueNaoArquiva ?? undefined}
                                        onClick={() => setAArquivar(grupo)}
                                    >
                                        <Trash2 className="mr-1 size-3.5" aria-hidden="true" />
                                        Arquivar
                                    </Button>
                                </div>
                            </li>
                        );
                    })}
                </ul>

                <Button variant="secondary" className="mt-4" onClick={() => setCriando(true)}>
                    <Plus className="mr-1.5 size-4" aria-hidden="true" />
                    Criar um grupo
                </Button>
            </section>

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
        </LayoutMinhaConta>
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

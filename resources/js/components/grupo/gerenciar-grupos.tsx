import { router, useForm, usePage } from '@inertiajs/react';
import { Pencil, Plus, Settings2, Trash2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import MarcaDaRede from '@/components/conexao/marca-da-rede';
import ErroDeCampo from '@/components/erro-de-campo';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { type DadosCompartilhados, type Grupo } from '@/types';

/**
 * Onde os grupos se administram — **por cima da tela em que a pessoa já estava**.
 *
 * ⭐ Não é tela, e nem precisa ser: criar e renomear são gestos de um campo só,
 * e excluir é uma confirmação. Uma página inteira para isso obrigaria a sair
 * de onde se está e voltar depois, para uma tarefa de quinze segundos.
 *
 * ⚠️ Abre do **seletor**, que vive na barra do topo de toda tela. Enterrar isto
 * dentro de Minha conta faria a pessoa procurar em configurações uma coisa que
 * ela tem na frente dos olhos o tempo todo.
 *
 * ⭐ **Cada grupo mostra as redes que tem dentro** — o grupo É seus canais
 * (DEC-69), então é isso que se configura aqui. As marcas fazem um grupo ser
 * reconhecido antes de o nome ser lido.
 *
 * ⛔ Os números não são enfeite: são o **motivo** de o botão de excluir estar
 * apagado. Sumir com o botão deixaria a pessoa sem saber o que fazer.
 */
export default function GerenciarGrupos({ aberta, aoFechar }: { aberta: boolean; aoFechar: () => void }) {
    const { grupos } = usePage<DadosCompartilhados>().props;

    const [criando, setCriando] = useState(false);
    const [aRenomear, setARenomear] = useState<Grupo | null>(null);
    const [aExcluir, setAExcluir] = useState<Grupo | null>(null);

    if (!grupos?.atual) {
        return null;
    }

    const { atual, lista } = grupos;

    return (
        <>
            <Dialog open={aberta} onOpenChange={(estado) => !estado && aoFechar()}>
                <DialogContent className="max-h-[85svh] overflow-y-auto sm:max-w-lg">
                    <DialogTitle>Grupos</DialogTitle>
                    <DialogDescription>
                        Cada grupo é uma linha de conteúdo com seus próprios canais. Uma publicação sai de um grupo só.
                    </DialogDescription>

                    <ul className="-mx-1">
                        {lista.map((grupo) => {
                            const emFoco = grupo.ulid === atual.ulid;

                            // ⚠️ O motivo vai no `title` em vez de o botão sumir:
                            // sumir deixaria a pessoa sem saber o que fazer.
                            const porQueNaoExclui =
                                grupo.redes > 0
                                    ? 'Desconecte ou mova as redes antes de excluir'
                                    : lista.length === 1
                                      ? 'Você precisa de pelo menos um grupo'
                                      : null;

                            return (
                                <li
                                    key={grupo.ulid}
                                    /* O grupo em foco se marca por uma barra fina,
                                       não por mais uma palavra na linha. */
                                    className={cn(
                                        'flex items-center gap-3 border-l-2 py-3 pr-1 pl-3',
                                        emFoco ? 'border-[color:var(--accent)]' : 'border-transparent',
                                    )}
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium">{grupo.nome}</p>

                                        <div className="mt-1 flex items-center gap-2">
                                            {/* As marcas do que ele tem dentro. */}
                                            {grupo.plataformas.length > 0 && (
                                                <span className="flex items-center gap-1">
                                                    {grupo.plataformas.map((rede) => (
                                                        <MarcaDaRede key={rede} rede={rede} className="size-4 rounded-sm" />
                                                    ))}
                                                </span>
                                            )}

                                            <span className="text-muted-foreground text-xs">
                                                {grupo.redes === 0 ? 'sem rede conectada' : contar(grupo.publicacoes, 'publicação', 'publicações')}
                                            </span>
                                        </div>
                                    </div>

                                    <div className="flex shrink-0 items-center gap-0.5">
                                        {/* ⭐ Conectar aqui LEVA o modo junto: o painel
                                            entra neste grupo antes de abrir o catálogo.
                                            Sem isso a conta nasceria num grupo que a
                                            pessoa não está olhando — o mesmo acidente
                                            que o grupo existe para evitar, só que na
                                            hora de conectar. */}
                                        <AcaoDaLinha
                                            rotulo={`Conectar uma rede em ${grupo.nome}`}
                                            aoClicar={() => {
                                                // ⚠️ Fecha ANTES de navegar: esta janela
                                                // sobrevive à visita e ficaria por cima do
                                                // catálogo — dando a impressão de que o
                                                // clique não fez nada.
                                                aoFechar();
                                                router.post(route('grupos.usar', grupo.ulid), { conectar: true });
                                            }}
                                        >
                                            {/* ⚠️ Engrenagem, não `+`. O `+` diz "acrescentar
                                                mais um" — e o que esta ação faz é configurar
                                                de que redes este grupo é feito. */}
                                            <Settings2 className="size-3.5" aria-hidden="true" />
                                        </AcaoDaLinha>

                                        <AcaoDaLinha rotulo={`Renomear ${grupo.nome}`} aoClicar={() => setARenomear(grupo)}>
                                            <Pencil className="size-3.5" aria-hidden="true" />
                                        </AcaoDaLinha>

                                        <AcaoDaLinha
                                            rotulo={porQueNaoExclui ?? `Excluir ${grupo.nome}`}
                                            desabilitada={!!porQueNaoExclui}
                                            perigosa
                                            aoClicar={() => setAExcluir(grupo)}
                                        >
                                            <Trash2 className="size-3.5" aria-hidden="true" />
                                        </AcaoDaLinha>
                                    </div>
                                </li>
                            );
                        })}
                    </ul>

                    <button
                        type="button"
                        onClick={() => setCriando(true)}
                        className="text-muted-foreground hover:text-foreground focus-visible:ring-ring -mb-1 flex items-center gap-1.5 self-start rounded-md px-1 py-1 text-sm transition-colors focus-visible:ring-2 focus-visible:outline-none"
                    >
                        <Plus className="size-4" aria-hidden="true" />
                        Criar grupo
                    </button>
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

            {/* ⛔ Não diz que dá para recuperar. Por baixo é *soft delete*,
                mas isso é assunto do banco — existe para auditoria, não para a
                pessoa. Prometer volta criaria uma expectativa que tela nenhuma
                cumpre, e "excluí sem querer, dá pra voltar?" vira suporte. */}
            <Dialog open={!!aExcluir} onOpenChange={(estado) => !estado && setAExcluir(null)}>
                <DialogContent>
                    <DialogTitle>Excluir «{aExcluir?.nome}»?</DialogTitle>
                    <DialogDescription>
                        {(aExcluir?.publicacoes ?? 0) > 0
                            ? `Ele sai do seletor, e as ${aExcluir?.publicacoes} publicações feitas por ele saem do painel.`
                            : 'Ele sai do seletor. Está vazio, então nada mais sai junto.'}
                    </DialogDescription>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="secondary">Cancelar</Button>
                        </DialogClose>
                        <Button
                            variant="destructive"
                            onClick={() =>
                                aExcluir &&
                                router.delete(route('grupos.excluir', aExcluir.ulid), {
                                    preserveScroll: true,
                                    onFinish: () => setAExcluir(null),
                                })
                            }
                        >
                            Excluir
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

/**
 * Uma ação de linha — ícone só, e o texto vive no `title` e no leitor de tela.
 *
 * ⚠️ Três rótulos escritos por linha encheriam a janela de palavra repetida; o
 * ícone sozinho, com o nome do grupo no `title`, diz o mesmo em um terço do
 * espaço.
 */
function AcaoDaLinha({
    rotulo,
    aoClicar,
    desabilitada = false,
    perigosa = false,
    children,
}: {
    rotulo: string;
    aoClicar: () => void;
    desabilitada?: boolean;
    perigosa?: boolean;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            title={rotulo}
            aria-label={rotulo}
            disabled={desabilitada}
            onClick={aoClicar}
            className={cn(
                'text-muted-foreground focus-visible:ring-ring rounded-md p-1.5 transition-colors focus-visible:ring-2 focus-visible:outline-none',
                'disabled:cursor-not-allowed disabled:opacity-40',
                perigosa ? 'enabled:hover:text-[color:var(--destructive)]' : 'enabled:hover:text-foreground',
            )}
        >
            {children}
        </button>
    );
}

/** "1 publicação" / "3 publicações" — plural sem gambiarra de `(s)`. */
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

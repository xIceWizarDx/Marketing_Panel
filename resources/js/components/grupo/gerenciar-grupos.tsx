import { router, useForm, usePage } from '@inertiajs/react';
import { Pencil, Plus, Settings2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import CampoDeHashtags from '@/components/campo-de-hashtags';
import MarcaDaRede from '@/components/conexao/marca-da-rede';
import ErroDeCampo from '@/components/erro-de-campo';
import Quadro from '@/components/quadro';
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
 * (DEC-69). As marcas fazem um grupo ser reconhecido antes de o nome ser lido.
 *
 * ⭐ **Esta janela LISTA; quem configura é a janela do grupo.** Aqui há uma
 * engrenagem por linha e mais nada. Nome, canais e excluir moram juntos em
 * `ConfigurarGrupo`, porque são as três coisas do mesmo grupo — espalhadas em
 * três botões de ícone, nenhuma delas mostrava o estado antes de agir.
 */
export default function GerenciarGrupos({ aberta, aoFechar }: { aberta: boolean; aoFechar: () => void }) {
    const { grupos } = usePage<DadosCompartilhados>().props;

    const [criando, setCriando] = useState(false);
    /*
     * ⚠️ Guarda o **ULID**, não o objeto do grupo.
     *
     * Com o objeto, renomear dentro da janela deixava o título dela com o nome
     * antigo: a lista do servidor se atualizava e a cópia guardada aqui não.
     * Guardando a chave e relendo da lista, a janela sempre mostra o estado de
     * agora.
     */
    const [aConfigurar, setAConfigurar] = useState<string | null>(null);
    const [aExcluir, setAExcluir] = useState<Grupo | null>(null);

    if (!grupos?.atual) {
        return null;
    }

    const { atual, lista } = grupos;

    const emConfiguracao = lista.find((g) => g.ulid === aConfigurar) ?? null;

    /*
     * ⚠️ O motivo é calculado AQUI, e não dentro da janela, porque um dos dois
     * motivos depende da lista inteira — "você precisa de pelo menos um grupo"
     * não é uma pergunta que o grupo saiba responder sozinho.
     *
     * ⛔ E ele é escrito na tela, nunca só um botão apagado: sumir com a ação
     * deixaria a pessoa procurando onde exclui, e apagar sem explicar deixaria
     * ela clicando num botão morto.
     */
    const porQueNaoExclui = emConfiguracao
        ? emConfiguracao.redes > 0
            ? 'Desconecte ou mova os canais deste grupo antes de excluí-lo.'
            : lista.length === 1
              ? 'Você precisa de pelo menos um grupo.'
              : null
        : null;

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
                                            {/* ⭐ As marcas SOBREPOSTAS, uma por cima da
                                                lateral da outra.

                                                O anel na cor do fundo e o que separa uma
                                                da outra sem linha nenhuma — sem ele, dois
                                                logos escuros encostados viram uma mancha
                                                so. Encostadas, elas leem como UM conjunto:
                                                "as redes deste grupo", e nao quatro coisas
                                                soltas disputando atencao com o nome. */}
                                            {grupo.plataformas.length > 0 && (
                                                <span className="flex items-center -space-x-2">
                                                    {grupo.plataformas.map((rede) => (
                                                        <span key={rede} className="ring-background rounded-md ring-2">
                                                            <MarcaDaRede rede={rede} className="size-6 rounded-md" />
                                                        </span>
                                                    ))}
                                                </span>
                                            )}

                                            <span className="text-muted-foreground text-xs">
                                                {grupo.redes === 0 ? 'sem rede conectada' : contar(grupo.publicacoes, 'publicação', 'publicações')}
                                            </span>
                                        </div>
                                    </div>

                                    {/* ⭐ **Uma engrenagem por grupo, e ela abre uma
                                        janela — não um menuzinho.** Três alvos de 26px
                                        lado a lado, com desenhos parecidos e sem rótulo,
                                        obrigavam a passar o mouse em cada um para
                                        descobrir qual era qual — e no celular não existe
                                        passar o mouse.

                                        ⭐ Tudo o que é DAQUELE grupo passou a morar num
                                        lugar só: o nome, os canais e o excluir. Antes
                                        estavam em três botões que abriam três coisas
                                        diferentes, e nenhum deles mostrava o estado atual
                                        do grupo antes de agir. */}
                                    <button
                                        type="button"
                                        onClick={() => setAConfigurar(grupo.ulid)}
                                        title={`Configurar ${grupo.nome}`}
                                        aria-label={`Configurar ${grupo.nome}`}
                                        className="text-muted-foreground hover:text-foreground focus-visible:ring-ring shrink-0 rounded-md p-1.5 transition-colors focus-visible:ring-2 focus-visible:outline-none"
                                    >
                                        <Settings2 className="size-4" aria-hidden="true" />
                                    </button>
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

            {/* ⭐ Tudo o que é de UM grupo, numa janela só. */}
            {aConfigurar && (
                <ConfigurarGrupo
                    grupo={emConfiguracao}
                    porQueNaoExclui={porQueNaoExclui}
                    aoFechar={() => setAConfigurar(null)}
                    aoExcluir={() => emConfiguracao && setAExcluir(emConfiguracao)}
                />
            )}

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
 * ⭐ **A janela de UM grupo** — nome, canais e excluir, no mesmo lugar.
 *
 * ⚠️ Ela substituiu três botões de ícone que abriam três coisas diferentes.
 * Nenhum deles mostrava o **estado** do grupo antes de agir: dava para clicar em
 * excluir sem lembrar quantos canais ele tinha, e para conectar sem ver o que já
 * estava conectado. Aqui o estado vem primeiro e a ação vem depois dele.
 *
 * ⛔ Gerenciar **conta** (desconectar, mover de grupo) não mora aqui. Isso é
 * assunto da rede, e vive na janela dela — repetir aqui criaria dois lugares
 * para o mesmo gesto, que é como nasce o "desconectei e continuou aparecendo".
 */
function ConfigurarGrupo({
    grupo,
    porQueNaoExclui,
    aoFechar,
    aoExcluir,
}: {
    grupo: Grupo | null;
    /** Vem de cima: um dos motivos depende da lista inteira, não deste grupo. */
    porQueNaoExclui: string | null;
    aoFechar: () => void;
    aoExcluir: () => void;
}) {
    const { data, setData, patch, processing, errors, clearErrors, reset } = useForm({ nome: grupo?.nome ?? '' });

    /*
     * ⚠️ **Formulário próprio, e não um campo a mais no do nome.** São duas
     * decisões diferentes: juntá-las faria trocar a hashtag exigir reconfirmar
     * o nome, e o nome nasce em modo leitura de propósito.
     */
    const {
        data: dadosHashtags,
        setData: setDadosHashtags,
        patch: enviarHashtags,
        processing: salvandoHashtags,
        errors: errosHashtags,
    } = useForm<{ hashtags: string[] }>({ hashtags: grupo?.hashtags ?? [] });

    /*
     * ⚠️ **O nome nasce em modo leitura.**
     *
     * Um campo de texto aberto convida a digitar, e o nome do grupo não é coisa
     * que se mude toda vez que esta janela abre — ela existe principalmente para
     * ver os canais. Campo sempre aberto também dá a impressão de que algo está
     * pela metade, e é fácil apagar o nome sem querer e sair achando que salvou.
     */
    const [editandoNome, setEditandoNome] = useState(false);

    /** Só vira `true` quando alguém tenta excluir e não pode. */
    const [motivoAVista, setMotivoAVista] = useState(false);

    if (!grupo) {
        return null;
    }

    const mudouONome = data.nome.trim() !== grupo.nome && data.nome.trim() !== '';

    const hashtags = dadosHashtags.hashtags;
    const setHashtags = (lista: string[]) => setDadosHashtags('hashtags', lista);
    // ⚠️ Compara a LISTA, não o texto: `a b` e `a  b` são a mesma coisa aqui.
    const mudaramAsHashtags = hashtags.join(' ') !== grupo.hashtags.join(' ');

    /*
     * ⛔ A recusa de UMA hashtag chega como `hashtags.3`, não como `hashtags` —
     * ler só a chave exata deixaria a pessoa com um Salvar que não salva e
     * nenhuma explicação na tela.
     */
    const erroDasHashtags = Object.entries(errosHashtags).find(([chave]) => chave.startsWith('hashtags'))?.[1];

    const salvarNome: FormEventHandler = (evento) => {
        evento.preventDefault();
        patch(route('grupos.renomear', grupo.ulid), {
            preserveScroll: true,
            onSuccess: () => setEditandoNome(false),
        });
    };

    const desistirDoNome = () => {
        clearErrors();
        reset();
        setEditandoNome(false);
    };

    const salvarHashtags: FormEventHandler = (evento) => {
        evento.preventDefault();
        enviarHashtags(route('grupos.hashtags', grupo.ulid), { preserveScroll: true });
    };

    return (
        <Dialog
            open
            onOpenChange={(estado) => {
                if (!estado) {
                    clearErrors();
                    aoFechar();
                }
            }}
        >
            <DialogContent className="max-h-[85svh] overflow-y-auto sm:max-w-lg">
                {/* ⭐ **O nome do grupo É o título da janela** — e o lápis ao lado
                    é a porta para mudá-lo.

                    ⚠️ Antes havia "Configurar «Notícias»" no topo e, logo abaixo,
                    um bloco "Nome: Notícias". O mesmo nome duas vezes em quatro
                    centímetros. Título é identidade; campo é edição — e quando os
                    dois mostram a mesma coisa ao mesmo tempo, um dos dois está
                    sobrando.

                    ⛔ Campo aberto o tempo todo também convida a digitar numa
                    janela que se abre para ver canais, e é fácil apagar sem
                    querer e sair achando que salvou. */}
                {editandoNome ? (
                    <>
                        {/* A janela precisa continuar tendo título para quem usa
                            leitor de tela, mesmo com o nome virando campo. */}
                        <DialogTitle className="sr-only">Renomear «{grupo.nome}»</DialogTitle>

                        <form onSubmit={salvarNome} className="space-y-2 pr-8">
                            <div className="flex items-start gap-2">
                                <div className="min-w-0 flex-1">
                                    <Input
                                        value={data.nome}
                                        onChange={(e) => setData('nome', e.target.value)}
                                        aria-label="Nome do grupo"
                                        autoComplete="off"
                                        // ⚠️ O cursor entra no campo sozinho: quem
                                        // clicou no lápis já quer digitar, e pedir
                                        // um segundo clique é cobrar duas vezes
                                        // pelo mesmo gesto.
                                        autoFocus
                                        // Esc desiste sem fechar a janela inteira.
                                        onKeyDown={(e) => e.key === 'Escape' && desistirDoNome()}
                                        aria-invalid={!!errors.nome}
                                    />
                                    <ErroDeCampo mensagem={errors.nome} />
                                </div>

                                {/* ⚠️ Salvar só acorda quando o nome mudou de
                                    verdade: salvar o mesmo nome gasta uma
                                    requisição para dizer "pronto!" sobre nada. */}
                                <Button type="submit" size="sm" disabled={!mudouONome || processing}>
                                    Salvar
                                </Button>
                                <Button type="button" size="sm" variant="secondary" onClick={desistirDoNome}>
                                    Cancelar
                                </Button>
                            </div>

                            <p className="text-muted-foreground text-xs">Só muda o nome. Os canais e o histórico continuam iguais.</p>
                        </form>
                    </>
                ) : (
                    /* ⚠️ `pr-8` reserva o canto para o X do próprio modal, que é
                       posicionado em `right-4`. Sem a folga, um nome comprido
                       passa por baixo dele. */
                    <DialogTitle className="flex items-center gap-2 pr-8">
                        <span className="min-w-0 truncate">{grupo.nome}</span>

                        <button
                            type="button"
                            onClick={() => setEditandoNome(true)}
                            title={`Renomear ${grupo.nome}`}
                            aria-label={`Renomear ${grupo.nome}`}
                            className="text-muted-foreground hover:text-foreground focus-visible:ring-ring shrink-0 rounded-md p-1 transition-colors focus-visible:ring-2 focus-visible:outline-none"
                        >
                            <Pencil className="size-4" aria-hidden="true" />
                        </button>
                    </DialogTitle>
                )}

                {/* ⚠️ A descrição diz o ESTADO, não o conceito. "Um grupo é uma
                    linha de conteúdo" já está escrito na janela anterior, e
                    repetir aqui gasta a linha mais visível da janela com uma
                    frase que a pessoa acabou de ler. */}
                <DialogDescription>
                    {grupo.redes === 0
                        ? 'Sem canal conectado ainda.'
                        : `${contar(grupo.redes, 'canal conectado', 'canais conectados')} · ${contar(grupo.publicacoes, 'publicação', 'publicações')}`}
                </DialogDescription>

                {/* ── OS CANAIS ──
                    ⚠️ Sem rótulo "Canais conectados" em cima: a linha logo acima
                    já os contou, e os quadrados com o logo dizem o resto sozinhos. */}
                <div className="space-y-2">
                    <ul className="flex flex-wrap gap-x-3 gap-y-4">
                        {/* ⭐ **O quadrado LEVA à janela da rede** (DEC-154) — é
                            lá que moram desconectar e mover de grupo.

                            ⛔ Repetir "desconectar" aqui seria uma segunda porta
                            para uma ação sem volta, e é assim que nasce o
                            "desconectei e continuou aparecendo". Mas ficar sem
                            saída nenhuma era pior: a janela mostrava as redes do
                            grupo e não deixava agir sobre nenhuma. */}
                        {grupo.plataformas.map((rede) => (
                            <li key={rede}>
                                <Quadro
                                    como="button"
                                    tamanho="pequeno"
                                    className="gap-2"
                                    title={`Ver contas do ${rede} deste grupo`}
                                    onClick={() => router.post(route('grupos.usar', grupo.ulid), { rede }, { preserveScroll: true })}
                                >
                                    <MarcaDaRede rede={rede} className="size-9" />
                                    <span className="text-muted-foreground text-[0.8125rem] leading-tight">conectada</span>
                                </Quadro>
                            </li>
                        ))}

                        {/* ⭐ Conectar aqui LEVA o modo junto: o painel entra
                            neste grupo antes de abrir o catálogo. Sem isso a
                            conta nasceria num grupo que a pessoa não está
                            olhando — o mesmo acidente que o grupo existe para
                            evitar, só que na hora de conectar.

                            ⚠️ `preserveState` é o que mantém ESTA janela aberta
                            por baixo do catálogo. Sem ele o Inertia remonta a
                            página inteira, o seletor do topo remonta junto e
                            leva esta janela embora no meio do gesto. */}
                        <li>
                            <Quadro
                                como="button"
                                tamanho="pequeno"
                                tracejado
                                className="gap-2"
                                onClick={() =>
                                    router.post(route('grupos.usar', grupo.ulid), { conectar: true }, { preserveState: true, preserveScroll: true })
                                }
                            >
                                <Plus className="text-muted-foreground size-6" aria-hidden="true" />
                                <span className="text-[0.8125rem] leading-tight font-medium">Conectar uma rede</span>
                            </Quadro>
                        </li>
                    </ul>

                    {/* ⛔ Aqui a frase ganha o que a contagem não diz: a
                        CONSEQUÊNCIA. "Sem canal conectado" é estado; "não tem
                        para onde publicar" é o que isso significa. */}
                    {grupo.redes === 0 && (
                        <p className="text-muted-foreground text-xs">Sem canal conectado, este grupo ainda não tem para onde publicar.</p>
                    )}
                </div>

                {/* ── HASHTAGS QUE JÁ VÊM ESCRITAS ──
                    ⭐ **Elas moram no grupo porque é ele que separa linhas de
                    conteúdo** (DEC-69 e DEC-152): quem tem um canal de notícias
                    e um de novelas escreve `#noticias` cem vezes por mês num, e
                    nunca no outro.

                    ⛔ **Ponto de partida, não carimbo.** A frase abaixo do campo
                    diz isso com todas as letras — sem ela, a pessoa pode achar
                    que o painel vai colar essas hashtags em tudo, inclusive no
                    post em que ela não as quer. */}
                <form onSubmit={salvarHashtags} className="border-border/60 space-y-1.5 border-t pt-3">
                    <Label htmlFor="hashtags-do-grupo" className="text-sm font-medium">
                        Hashtags deste grupo <span className="text-muted-foreground font-normal">(separadas por espaço, sem #)</span>
                    </Label>

                    <div className="flex items-start gap-2">
                        <div className="min-w-0 flex-1">
                            <CampoDeHashtags
                                id="hashtags-do-grupo"
                                valor={hashtags}
                                aoMudar={setHashtags}
                                placeholder="noticias jornalismo"
                                invalido={!!erroDasHashtags}
                            />
                            <ErroDeCampo mensagem={erroDasHashtags} />
                        </div>

                        {/* ⚠️ Salvar só acorda quando mudou de verdade — igual ao
                            nome: gastar requisição para dizer "pronto!" sobre
                            nada ensina a pessoa a ignorar o aviso de sucesso. */}
                        <Button type="submit" size="sm" disabled={!mudaramAsHashtags || salvandoHashtags}>
                            Salvar
                        </Button>
                    </div>

                    <p className="text-muted-foreground text-xs">
                        Já vêm escritas ao publicar neste grupo, e você muda ou apaga em cada post. Elas não mexem no que já foi publicado.
                    </p>
                </form>

                {/* ── EXCLUIR ──
                    ⭐ **Discreto de propósito.** É a ação mais rara desta janela
                    e a única sem volta — botão vermelho cheio no rodapé dá a ela
                    o peso visual de uma ação principal, que é o contrário do que
                    ela é. Texto pequeno chama quem procura e não puxa quem não
                    está procurando.

                    ⛔ A consequência ("as 40 publicações saem do painel") NÃO
                    fica aqui: a janela de confirmação já a escreve, e dizer duas
                    vezes só faz a pessoa parar de ler as duas.

                    ⭐ **O motivo só aparece quando a pessoa TENTA.** Escrito de
                    saída, ele avisava sobre um problema que ninguém tinha ainda
                    — e ocupava uma linha permanente para responder uma pergunta
                    que quase nunca é feita. Aqui a janela fica quieta até alguém
                    querer excluir, e aí responde.

                    ⚠️ `aria-disabled`, e **não** `disabled`: botão de verdade
                    desabilitado não recebe clique nenhum, então não teria como
                    explicar por que não dá. Quem lê tela continua sendo avisado
                    de que ele está indisponível. */}
                <div className="border-border/60 space-y-1.5 border-t pt-3">
                    {/* ⚠️ Cinza em repouso, vermelho só ao passar por cima. A cor
                        de perigo é um alarme — aceso o tempo todo, ela vira
                        enfeite e para de significar perigo justamente onde
                        precisa significar. Sem ícone: a palavra já diz. */}
                    <button
                        type="button"
                        aria-disabled={!!porQueNaoExclui}
                        onClick={() => (porQueNaoExclui ? setMotivoAVista(true) : aoExcluir())}
                        className="text-muted-foreground focus-visible:ring-ring rounded-md text-xs transition-colors hover:text-[color:var(--destructive)] focus-visible:ring-2 focus-visible:outline-none"
                    >
                        Excluir grupo
                    </button>

                    {/* ⭐ **O lugar da frase já existe antes de ela existir.**
                        Aparecendo do nada, ela empurrava o pé da janela para
                        baixo e a janela inteira se remexia — e coisa que pula
                        na tela no instante do clique faz a pessoa duvidar do
                        que clicou.

                        ⚠️ O espaço é reservado por um **gêmeo invisível com o
                        mesmo texto**, não por uma altura chutada: assim ele
                        continua exato quando a frase quebra em duas linhas no
                        celular, que é onde uma altura fixa erraria.

                        ⚠️ Reservado só quando há motivo. Onde a exclusão é
                        permitida, frase nenhuma vai aparecer — e guardar lugar
                        para o que nunca vem é sobra de espaço, não estabilidade.

                        ⚠️ Depende dos DOIS: se a pessoa desconectar o canal com
                        a janela aberta, o motivo deixa de existir e some junto,
                        em vez de acusar um impedimento já resolvido. */}
                    {porQueNaoExclui && (
                        <div className="relative">
                            <p aria-hidden="true" className="invisible text-xs">
                                {porQueNaoExclui}
                            </p>

                            {motivoAVista && (
                                <p role="status" className="absolute inset-0 text-xs text-[color:var(--saude-atencao)]">
                                    {porQueNaoExclui}
                                </p>
                            )}
                        </div>
                    )}
                </div>
            </DialogContent>
        </Dialog>
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

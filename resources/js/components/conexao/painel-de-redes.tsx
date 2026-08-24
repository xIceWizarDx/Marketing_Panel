import { router, useForm, usePage } from '@inertiajs/react';
import { AlertTriangle, FolderInput, LoaderCircle, Plus, ShieldCheck, Unplug } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import CampoSenha from '@/components/campo-senha';
import MarcaDaRede from '@/components/conexao/marca-da-rede';
import TermosDaRede from '@/components/conexao/termos-da-rede';
import ErroDeCampo from '@/components/erro-de-campo';
import Quadro from '@/components/quadro';
import TituloDeSecao from '@/components/titulo-de-secao';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAtualizacaoViva } from '@/hooks/use-atualizacao-viva';
import { type DadosCompartilhados } from '@/types';

interface Conta {
    ulid: string;
    plataforma: string;
    plataformaRotulo: string;
    nome: string;
    status: string;
    statusRotulo: string;
    cor: 'ok' | 'atencao' | 'erro' | 'neutro';
    detalhe: string | null;
    podePublicar: boolean;
    venceEm: string | null;
    venceEmBreve: boolean;
    /**
     * Seguidores — inscritos, no YouTube.
     *
     * ⛔ `null` é resposta, e ela NÃO é zero: ou a rede não publica esse número,
     * ou ainda não lemos. Escrever `0` para quem escondeu os inscritos seria
     * afirmar um fato falso.
     */
    seguidores: number | null;
    /** A ressalva da rede sobre esse número — o YouTube arredonda, por exemplo. */
    seguidoresNota: string | null;
    /** Frase pronta do servidor: a tela não formata data. */
    metricasLidas: string | null;
}

export interface Rede {
    valor: string;
    rotulo: string;
    disponivel: boolean;
    /** Publicador pronto, faltando só a configuração do servidor. */
    faltaConfigurar: boolean;
    situacao: 'disponivel' | 'planejada' | 'em_estudo';
    situacaoRotulo: string;
    /**
     * Como esta rede se conecta — quem responde e o servidor (`Plataforma`).
     *
     * ⛔ `autorizacao` = a pessoa sai daqui e volta; `senha_de_aplicativo` = ela
     * digita aqui. A tela NUNCA deduz isso pelo nome da rede: era assim que o
     * modal do Facebook acabava pedindo senha do Bluesky.
     */
    /**
     * ⭐ Três formas, e quem responde é o servidor (`Plataforma::formaDeConexao`).
     *
     * ⛔ Este bloco já perguntou `=== 'youtube'`, e tudo que não era YouTube caía
     * no formulário do Bluesky — o modal do Facebook chegou a pedir senha de
     * aplicativo do Bluesky.
     */
    formaDeConexao: 'autorizacao' | 'senha_de_aplicativo' | 'servidor_e_autorizacao' | 'endereco_de_webhook';
    /** Para onde ir autorizar. `null` quando a conexao e por senha. */
    enderecoDeConexao: string | null;
    publicados: number;
    /** Recusados pela rede. Aparece do lado do acerto, no mesmo tamanho. */
    falhas: number;
    /**
     * ⛔ **Publicados e depois APAGADOS — e não é falha** (DEC-165).
     *
     * ⚠️ "Não foi" e "saiu do ar" são opostos: um nunca chegou; o outro chegou,
     * foi visto, e depois foi apagado — quase sempre por quem publica. Contados
     * juntos, o painel acusa falha onde houve uma decisão.
     */
    saiuDoAr: number;
    /** Ainda em curso: nem sucesso nem falha. */
    andando: number;
    /** O que esta rede não conta sobre um post — dito uma vez, aqui. */
    notaDeMetrica: string | null;
    contas: Conta[];
}

const corDoSemaforo: Record<Conta['cor'], string> = {
    ok: 'var(--saude-ok)',
    atencao: 'var(--saude-atencao)',
    erro: 'var(--saude-erro)',
    neutro: 'var(--saude-neutro)',
};

interface Props {
    redes: Rede[];
    totalConectado: number;
    /** Plataforma cujo detalhe está aberto, ou `null`. */
    aberta: string | null;
    aoAbrir: (rede: string | null) => void;
    /** O catálogo está aberto? Fechado por padrão: escolher rede é raro. */
    escolhendo: boolean;
    aoEscolher: (aberto: boolean) => void;
}

/**
 * ⭐ Onde as redes moram — e elas moram na **porta de entrada** (DEC-63).
 *
 * ⚠️ Isto já foi uma tela. Virou seção da Visão geral porque respondia a mesma
 * pergunta que ela: *"como está tudo?"*. Duas telas para uma pergunta obrigavam
 * a pessoa a passar pelas duas para ter certeza.
 *
 * ⛔ **Não vira modal.** O semáforo do token é o diferencial (DEC-32), e
 * diferencial atrás de um clique é algo que se descobre quando a publicação já
 * foi perdida. O detalhe de cada rede é que é modal — e é aberto daqui.
 */
export default function PainelDeRedes({ redes, totalConectado, aberta: redeAberta, aoAbrir, escolhendo, aoEscolher }: Props) {
    const [conectando, setConectando] = useState<Rede | null>(null);
    const [aDesconectar, setADesconectar] = useState<Conta | null>(null);
    const [aMover, setAMover] = useState<Conta | null>(null);

    const { grupos } = usePage<DadosCompartilhados>().props;
    const outrosGrupos = (grupos?.lista ?? []).filter((g) => g.ulid !== grupos?.atual.ulid);

    /*
     * ⭐ Qual rede está aberta é decisão de QUEM USA este painel, não dele.
     *
     * ⚠️ É o que permite o aviso lá em cima abrir a rede com problema **sem
     * navegar**. Levar para a própria tela onde a pessoa já está é link morto,
     * e sujar a barra de endereço com uma âncora para rolar até um bloco já
     * visível é pior que não ter ação nenhuma.
     */
    const aberta = redes.find((rede) => rede.valor === redeAberta) ?? null;
    const setAberta = (rede: Rede | null) => aoAbrir(rede?.valor ?? null);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        identificador: '',
        senha_de_aplicativo: '',
    });

    const conectar: FormEventHandler = (evento) => {
        evento.preventDefault();
        post(route('conexoes.bluesky'), {
            onSuccess: () => {
                reset();
                setConectando(null);
            },
        });
    };

    const fechar = () => {
        clearErrors();
        reset();
        setConectando(null);
    };

    /**
     * Cada rede conecta do seu jeito: o Bluesky por senha de aplicativo (modal),
     * o YouTube por autorização no site do Google (sai daqui e volta).
     */
    const abrirConexao = (rede: Rede) => {
        if (!rede.disponivel) return;

        // ⚠️ O YouTube sai da tela por autorização no Google. Mesmo assim abre o
        // modal antes: a política exige que a pessoa **consinta expressamente** e
        // veja os Termos **antes** da ação, e ela nunca voltaria para ler depois.
        setConectando(rede);
    };

    const totalPublicado = redes.reduce((soma, rede) => soma + rede.publicados, 0);

    /** A rede aberta para conectar pede a senha aqui, ou manda autorizar fora? */
    // ⚠️ Só a conexão federada usa: aquela porta redireciona para outro
    // domínio, e isso exige navegação de verdade — com token na página.
    const csrf = usePage<DadosCompartilhados>().props.csrf;

    const porSenha = conectando?.formaDeConexao === 'senha_de_aplicativo';

    /**
     * ⛔ **A rede federada precisa saber ONDE a conta mora antes de autorizar.**
     *
     * ⚠️ Não existe "o Mastodon" para onde mandar todo mundo: são milhares de
     * servidores independentes, e o endereço de autorização só existe depois que
     * a pessoa disser qual é o dela.
     */
    const porServidor = conectando?.formaDeConexao === 'servidor_e_autorizacao';

    /**
     * ⭐ A forma mais simples de todas: a pessoa cria o webhook no próprio
     * Discord e cola o endereço — não há ida ao site da rede, nem volta.
     *
     * ⛔ E o endereço **é** a credencial: quem o tem, publica.
     */
    const porWebhook = conectando?.formaDeConexao === 'endereco_de_webhook';

    /*
     * ⭐ Na tela ficam SÓ as redes conectadas.
     *
     * Mostrar as catorze de uma vez enchia a tela de coisa que não é da pessoa
     * — e a que é dela, que é o que ela veio ver, ficava do mesmo tamanho de um
     * "em estudo". O catálogo inteiro mora atrás do botão de conectar, que é
     * onde ele tem função.
     *
     * ⚠️ Conta DESCONECTADA não conta: a linha dela sobrevive porque o histórico
     * aponta para ela, mas quem desconectou não quer a rede de volta na tela.
     * O que já foi publicado continua em Publicações, com os links.
     */
    const minhas = redes.filter((rede) => rede.contas.some((c) => c.status !== 'desconectada'));

    // Os números do cartão mudam sozinhos enquanto houver envio em curso.
    useAtualizacaoViva({
        ativo: redes.some((rede) => rede.andando > 0),
        propriedades: ['redes'],
    });

    return (
        <section>
            <TituloDeSecao
                titulo="Suas redes"
                // ⚠️ O número é o de posts CONFIRMADOS na rede, não o de envios
                // feitos. Contar envio seria contar promessa (DEC-31).
                apoio={
                    totalConectado === 0
                        ? 'Sem uma conta conectada não há para onde publicar'
                        : `${totalConectado} ${totalConectado === 1 ? 'conta' : 'contas'} · ` +
                          `${totalPublicado} ${totalPublicado === 1 ? 'post no ar' : 'posts no ar'}`
                }
            />

            {/* Quadrados de tamanho fixo: uma grade que estica transformaria
                duas redes conectadas em dois retângulos enormes. */}
            <ul className="flex flex-wrap gap-2.5 empty:hidden">
                {minhas.map((rede) => {
                    /*
                     * ⭐ **O ponto no canto é o que substitui o placar**
                     * (DEC-167): acende quando existe algo que precisa de você —
                     * conta quebrada **ou** post que não subiu.
                     *
                     * ⛔ "Saiu do ar" NÃO acende: apagar um post é decisão de
                     * quem publica, e o painel não cobra providência de uma
                     * escolha (DEC-165).
                     */
                    const problemas = rede.contas.filter((c) => c.podePublicar === false && c.status !== 'desconectada');
                    const precisaDeVoce = problemas.length > 0 || rede.falhas > 0;

                    return (
                        <li key={rede.valor}>
                            <Quadro como="button" onClick={() => setAberta(rede)} className="gap-1.5">
                                {/* Um ponto no canto avisa que algo precisa de você. */}
                                {precisaDeVoce && (
                                    <span
                                        aria-hidden="true"
                                        className="absolute top-2 right-2 size-2 rounded-full"
                                        style={{ backgroundColor: corDoSemaforo[problemas[0]?.cor ?? 'erro'] }}
                                    />
                                )}

                                <MarcaDaRede rede={rede.valor} />

                                <span className="w-full truncate text-center text-xs leading-tight font-medium">{rede.rotulo}</span>

                                {/* ⭐ **UM número: o que está no ar** (DEC-167).
                                    ⚠️ Isto já mostrou três de uma vez — "no ar",
                                    "não foi" e "saiu do ar" — e virou **placar**
                                    num quadrado de 6,5rem. Placar de falha não
                                    ajuda ninguém a agir.

                                    ⛔ E nada foi ESCONDIDO: a falha continua na
                                    janela da rede, em Publicações e no bloco
                                    "Precisa de você", e aqui ela acende o ponto
                                    no canto. Some do placar, não do produto. */}
                                <span className="flex items-end justify-center gap-2.5">
                                    <NumeroDaRede
                                        valor={rede.publicados}
                                        rotulo="no ar"
                                        cor={rede.publicados > 0 ? 'var(--saude-ok)' : 'var(--muted-foreground)'}
                                    />
                                </span>
                            </Quadro>
                        </li>
                    );
                })}
            </ul>

            {/* ⛔ Grupo sem rede não fica com um retângulo vazio: diz onde a
                pessoa resolve isso. Conectar é configuração do grupo (DEC-87),
                e não pode ter uma segunda porta aqui — duas portas para a mesma
                coisa é como o "conectei e não apareceu" nasce. */}
            {minhas.length === 0 && (
                <p className="text-muted-foreground text-sm">
                    Este grupo ainda não tem rede. Conecte uma pelo seletor de grupos, lá em cima — a engrenagem ao lado do nome dele.
                </p>
            )}

            {/* Escolher qual rede conectar — o catálogo */}
            <Dialog open={escolhendo} onOpenChange={aoEscolher}>
                <DialogContent className="max-h-[85svh] overflow-y-auto sm:max-w-2xl">
                    <DialogTitle>Conectar uma rede</DialogTitle>
                    <DialogDescription>Você autoriza no site da própria rede — sua senha nunca passa por aqui.</DialogDescription>

                    {/* ⚠️ Respiro maior entre as LINHAS que entre as colunas. Com
                        o mesmo valor nos dois eixos, quatro fileiras de quadrados
                        viram uma malha — e o olho perde onde uma linha acaba.

                        ⛔ **Grade de trilhas fixas, não `flex-wrap`.** O quadro
                        tem lado fixo de propósito (ver `Quadro`), então cinco
                        deles somam menos que a largura do modal — e com
                        `flex-wrap` toda a sobra ficava empilhada **à direita**,
                        como se o catálogo estivesse torto.

                        ⭐ `auto-fit` com trilha de 6.5rem mantém o quadro do
                        tamanho que ele tem que ter, alinha as colunas entre as
                        linhas, e `justify-center` divide a sobra pelos dois
                        lados em vez de jogar tudo num canto. E responde sozinho
                        a telas estreitas: cabem menos colunas, sem `breakpoint`
                        escrito à mão. */}
                    <ul className="grid grid-cols-[repeat(auto-fit,6.5rem)] justify-center gap-x-3 gap-y-4">
                        {redes.map((rede) => {
                            const clicavel = rede.disponivel;

                            return (
                                <li key={rede.valor}>
                                    <Quadro
                                        como="button"
                                        tamanho="pequeno"
                                        disabled={!clicavel}
                                        onClick={() => {
                                            aoEscolher(false);
                                            abrirConexao(rede);
                                        }}
                                        className="gap-2"
                                    >
                                        <MarcaDaRede rede={rede.valor} className="size-9" />

                                        <span className="w-full truncate text-center text-xs leading-tight font-medium">{rede.rotulo}</span>

                                        {/* ⚠️ "Aguardando aprovação" e "em estudo" são coisas
                                            diferentes: uma tem caminho, a outra é ideia. */}
                                        <span className="text-muted-foreground text-center text-[0.8125rem] leading-tight">
                                            {rede.contas.some((c) => c.status !== 'desconectada')
                                                ? 'já conectada'
                                                : rede.disponivel
                                                  ? 'conectar'
                                                  : rede.faltaConfigurar
                                                    ? 'falta configurar'
                                                    : rede.situacaoRotulo.toLowerCase()}
                                        </span>
                                    </Quadro>
                                </li>
                            );
                        })}
                    </ul>
                </DialogContent>
            </Dialog>

            {/* Detalhe de uma rede — contas, saúde e ações */}
            <Dialog open={!!aberta} onOpenChange={(estado) => !estado && setAberta(null)}>
                <DialogContent className="max-h-[85svh] overflow-y-auto">
                    {/* ⚠️ `pr-8` reserva o canto para o X do próprio modal, que
                        e posicionado em `right-4`. Sem a folga, os dois se
                        sobrepoem — e o que fica por cima e o de fechar. */}
                    <div className="flex items-center gap-3 pr-8">
                        <DialogTitle className="flex flex-1 items-center gap-2.5">
                            <MarcaDaRede rede={aberta?.valor ?? ''} className="size-8" />
                            {aberta?.rotulo}
                        </DialogTitle>

                        {aberta?.disponivel && (
                            <Button
                                variant="secondary"
                                size="sm"
                                className="shrink-0"
                                onClick={() => {
                                    const rede = aberta;
                                    setAberta(null);
                                    if (rede) abrirConexao(rede);
                                }}
                            >
                                <Plus className="mr-1.5 size-4" aria-hidden="true" />
                                Conectar outra conta
                            </Button>
                        )}
                    </div>
                    <DialogDescription>
                        {aberta?.publicados === 1
                            ? '1 post confirmado no ar nesta rede.'
                            : `${aberta?.publicados ?? 0} posts confirmados no ar nesta rede.`}
                        {/* ⭐ O que esta rede não conta, com todas as letras —
                            e uma vez só, aqui, em vez de embaixo de cada post
                            (DEC-94). */}
                        {aberta?.notaDeMetrica && <span className="mt-1 block">{aberta.notaDeMetrica}</span>}
                    </DialogDescription>

                    <ul className="space-y-2">
                        {aberta?.contas.map((conta) => (
                            <li key={conta.ulid} className="border-border flex items-start gap-2.5 rounded-lg border p-3">
                                {/* ⭐ O semáforo (DEC-32). */}
                                <span
                                    aria-hidden="true"
                                    className="mt-1.5 size-2 shrink-0 rounded-full"
                                    style={{ backgroundColor: corDoSemaforo[conta.cor] }}
                                />

                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-medium">{conta.nome}</p>
                                    <p className="text-xs" style={{ color: corDoSemaforo[conta.cor] }}>
                                        {conta.statusRotulo}
                                    </p>
                                    {conta.detalhe && <p className="text-muted-foreground mt-0.5 text-xs">{conta.detalhe}</p>}

                                    {/* ⭐ O contador da conta. Só aparece depois
                                        que houve leitura — sem leitura não
                                        existe traço nem zero, porque os dois
                                        seriam uma afirmação sobre um número que
                                        ninguém foi buscar. */}
                                    {conta.seguidores !== null && (
                                        <p className="mt-1.5 text-[0.8125rem]">
                                            <span className="font-medium tabular-nums">{conta.seguidores.toLocaleString('pt-BR')}</span>{' '}
                                            <span className="text-muted-foreground">
                                                {conta.seguidores === 1 ? 'seguidor' : 'seguidores'}
                                                {conta.metricasLidas && ` · ${conta.metricasLidas}`}
                                            </span>
                                        </p>
                                    )}

                                    {/* ⚠️ A ressalva anda junto do número, nunca
                                        num rodapé: o YouTube arredonda, e quem
                                        comparar com o YouTube Studio sem ler
                                        isto vai achar que o nosso número está
                                        errado. */}
                                    {conta.seguidores !== null && conta.seguidoresNota && (
                                        <p className="text-muted-foreground mt-0.5 text-xs">{conta.seguidoresNota}</p>
                                    )}

                                    {conta.venceEmBreve && (
                                        <p className="mt-1 flex items-start gap-1 text-xs text-[color:var(--saude-atencao)]">
                                            <AlertTriangle className="mt-0.5 size-3 shrink-0" aria-hidden="true" />
                                            Vence em breve. Reconecte para não perder publicações.
                                        </p>
                                    )}

                                    {/* ⛔ **Com TEXTO, não só ícone.**
                                    Desconectar é a ação sem volta desta janela,
                                    e uma tomada riscada em vermelho não diz o
                                    que vai acontecer — quem não reconhece o
                                    desenho descobre clicando, que é o pior
                                    momento possível para descobrir.

                                    ⚠️ Ficam ABAIXO da conta, não à direita: o
                                    nome do canal pode ser longo, e dois botões
                                    de texto disputando a mesma linha empurram a
                                    identificação para as reticências. */}
                                    {conta.status !== 'desconectada' && (
                                        <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1">
                                            {/* ⭐ Sem isto, o primeiro erro de cadastro vira
                                            permanente e a pessoa acaba conectando o mesmo
                                            canal duas vezes (DEC-77). Só aparece quando há
                                            para onde levar. */}
                                            {outrosGrupos.length > 0 && (
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setAberta(null);
                                                        setAMover(conta);
                                                    }}
                                                    className="text-muted-foreground hover:text-foreground focus-visible:ring-ring inline-flex items-center gap-1.5 rounded-md text-[0.8125rem] transition-colors focus-visible:ring-2 focus-visible:outline-none"
                                                >
                                                    <FolderInput className="size-3.5 shrink-0" aria-hidden="true" />
                                                    Mover de grupo
                                                </button>
                                            )}

                                            <button
                                                type="button"
                                                onClick={() => {
                                                    setAberta(null);
                                                    setADesconectar(conta);
                                                }}
                                                className="text-muted-foreground focus-visible:ring-ring inline-flex items-center gap-1.5 rounded-md text-[0.8125rem] transition-colors hover:text-[color:var(--destructive)] focus-visible:ring-2 focus-visible:outline-none"
                                            >
                                                <Unplug className="size-3.5 shrink-0" aria-hidden="true" />
                                                Desconectar
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                </DialogContent>
            </Dialog>

            {/* Conectar */}
            <Dialog open={!!conectando} onOpenChange={(estado) => !estado && fechar()}>
                <DialogContent className="max-h-[85svh] overflow-y-auto">
                    <DialogTitle className="flex items-center gap-2.5">
                        <MarcaDaRede rede={conectando?.valor ?? ''} className="size-8" />
                        Conectar {conectando?.rotulo}
                    </DialogTitle>
                    <DialogDescription>Você pode conectar quantas contas quiser — cada uma aparece separada na hora de publicar.</DialogDescription>

                    {/* ⭐ DEC-41: explicar o que pedimos e o que NÃO pedimos. O medo
                        de dar acesso total é documentado, e custa quase nada tratar. */}
                    <div className="border-border rounded-lg border border-dashed p-3">
                        <p className="flex items-center gap-1.5 text-sm font-medium">
                            <ShieldCheck className="size-4 shrink-0" style={{ color: 'var(--saude-ok)' }} aria-hidden="true" />O que pedimos, e o que
                            não pedimos
                        </p>

                        {/* ⛔ Olha a FORMA DE CONEXÃO, nunca o nome da rede. Este
                            bloco perguntava `=== 'youtube'`, e tudo que não era
                            YouTube caía no texto do Bluesky — então o modal do
                            Facebook pedia senha de aplicativo do Bluesky. */}
                        {porWebhook ? (
                            <ul className="text-muted-foreground mt-2 space-y-1 text-sm">
                                <li>
                                    Um <strong>webhook</strong> que você mesmo cria no canal, em <em>Editar canal → Integrações → Webhooks</em>. Nós
                                    nunca entramos no seu servidor.
                                </li>
                                <li>
                                    O endereço fica guardado criptografado e <strong>nunca aparece em tela nenhuma</strong>.
                                </li>
                                <li>Você revoga apagando o webhook no Discord — e o painel avisa na próxima publicação.</li>
                            </ul>
                        ) : porServidor ? (
                            <ul className="text-muted-foreground mt-2 space-y-1 text-sm">
                                <li>
                                    O Mastodon não é um site só: são <strong>milhares de servidores independentes</strong>. Diga em qual a sua conta
                                    mora e você autoriza <strong>lá</strong>.
                                </li>
                                <li>
                                    Permissão para <strong>publicar</strong> e <strong>enviar vídeo</strong>. Não pedimos acesso à sua linha do tempo
                                    nem às suas mensagens.
                                </li>
                                <li>Você revoga o acesso quando quiser, nas configurações do seu servidor.</li>
                            </ul>
                        ) : porSenha ? (
                            <ul className="text-muted-foreground mt-2 space-y-1 text-sm">
                                <li>
                                    Uma <strong>senha de aplicativo</strong> — não a senha da sua conta. Você gera na própria rede e{' '}
                                    <strong>revoga quando quiser</strong>.
                                </li>
                                <li>Ela serve para publicar. Não apagamos nem alteramos o que já existe.</li>
                                <li>Fica guardada criptografada e nunca aparece em tela nenhuma — nem para o nosso suporte.</li>
                            </ul>
                        ) : (
                            <ul className="text-muted-foreground mt-2 space-y-1 text-sm">
                                <li>
                                    Você autoriza <strong>no site da própria rede</strong> — sua senha nunca passa por aqui.
                                </li>
                                <li>
                                    Permissão para <strong>publicar</strong> e para <strong>conferir se o post subiu</strong>. Só isso.
                                </li>
                                <li>
                                    <strong>Não pedimos permissão para apagar nem alterar</strong> o que já existe, e você revoga o acesso quando
                                    quiser, nas configurações da rede.
                                </li>
                            </ul>
                        )}

                        {conectando?.valor === 'bluesky' && (
                            <p className="text-muted-foreground mt-2 text-xs">
                                Onde gerar: no aplicativo, em <em>Configurações → Privacidade e segurança → Senhas de aplicativo</em>.
                            </p>
                        )}
                    </div>

                    {conectando?.valor === 'youtube' && (
                        <div className="rounded-lg border border-[color:var(--saude-atencao)]/30 bg-[color:var(--saude-atencao)]/10 p-3 text-sm">
                            <p className="font-medium">Enquanto a aprovação do YouTube não sair</p>
                            <p className="text-muted-foreground mt-1">
                                Todo vídeo enviado por aqui fica <strong>privado</strong> — é regra do YouTube para aplicativos ainda não auditados, e
                                vale mesmo que você escolha “público”. Você consegue ver e testar tudo; só outras pessoas ainda não.
                            </p>
                            {/* DEC-46: a consequência do escopo mínimo, dita ANTES
                                de conectar — não descoberta depois. */}
                            <p className="text-muted-foreground mt-2">
                                Quando a aprovação sair, esses vídeos <strong>continuam privados</strong> e você os torna públicos no YouTube Studio.
                                Isso acontece porque <strong>não pedimos permissão para alterar nem apagar vídeos do seu canal</strong> — e não há
                                como pedir uma sem a outra.
                            </p>
                        </div>
                    )}

                    {/* ⭐ **A exigência da Meta, dita ANTES de autorizar** (DEC-150).
                        ⛔ Sem este bloco, quem não marca a Página no site da Meta
                        volta para cá e lê *"nenhuma Página foi encontrada"* — e
                        conclui que o painel quebrou, quando o que faltou foi um
                        passo que ninguém avisou que existia. */}
                    {(conectando?.valor === 'facebook' || conectando?.valor === 'instagram') && (
                        <div className="rounded-lg border border-[color:var(--saude-atencao)]/30 bg-[color:var(--saude-atencao)]/10 p-3 text-sm">
                            <p className="font-medium">Antes de autorizar</p>

                            <ul className="text-muted-foreground mt-1 space-y-1.5">
                                <li>
                                    O Facebook e o Instagram <strong>só publicam em Página</strong> — nunca no perfil pessoal. É regra da Meta, não
                                    nossa. Sem Página, não há como conectar.
                                </li>
                                <li>
                                    No site da Meta aparece um passo perguntando <strong>quais Páginas</strong> você quer usar.{' '}
                                    <strong>Marque a Página ali.</strong> Passar sem marcar traz você de volta sem nada.
                                </li>
                                {/* ⚠️ O caso de várias Páginas é o que mais assusta:
                                    a pessoa marca duas e acha que passou a publicar
                                    nas duas. Escolher o destino continua sendo dela,
                                    na hora de publicar. */}
                                <li>
                                    <strong>Tem mais de uma Página?</strong> Todas as que você marcar viram <strong>contas separadas aqui</strong>, e
                                    na hora de publicar você escolhe em quais quer postar. Marcar várias não publica em todas sozinho.
                                </li>
                                <li>
                                    O <strong>Instagram vem junto</strong>, sem conexão separada — desde que seja conta profissional e esteja
                                    vinculado a uma dessas Páginas.
                                </li>
                            </ul>
                        </div>
                    )}

                    <TermosDaRede rede={conectando?.valor ?? ''} />

                    {/* ⚠️ O endereço vem do servidor (`enderecoDeConexao`), não
                        montado aqui: Facebook e Instagram acendem pela MESMA
                        porta, e essa regra escrita em React seria outra fonte de
                        verdade para discordar da de lá. */}
                    {porWebhook ? (
                        /* ⚠️ Formulário de verdade, como o do Mastodon: a resposta
                           é um redirecionamento, e XHR não o atravessa bem. */
                        <form method="post" action={conectando?.enderecoDeConexao ?? '#'} className="space-y-4">
                            <input type="hidden" name="_token" value={csrf} />
                            <div className="grid gap-2">
                                <Label htmlFor="endereco">Endereço do webhook</Label>
                                <Input id="endereco" name="endereco" placeholder="https://discord.com/api/webhooks/..." autoComplete="off" required />
                                <p className="text-muted-foreground text-[0.8125rem]">
                                    ⚠️ O Discord não tem vitrine: aqui o vídeo vira <strong>aviso para quem já está no canal</strong>, não alcance
                                    novo.
                                </p>
                            </div>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancelar
                                    </Button>
                                </DialogClose>
                                <Button type="submit">Conectar canal</Button>
                            </DialogFooter>
                        </form>
                    ) : porServidor ? (
                        /* ⚠️ `method="post"` de verdade, com o token do Laravel:
                           esta porta RECEBE um formulário, não um clique — o
                           endereço de autorização ainda não existe neste ponto. */
                        <form method="post" action={conectando?.enderecoDeConexao ?? '#'} className="space-y-4">
                            <input type="hidden" name="_token" value={csrf} />
                            <div className="grid gap-2">
                                <Label htmlFor="servidor">Endereço do seu servidor</Label>
                                <Input id="servidor" name="servidor" placeholder="mastodon.social" autoComplete="off" required />
                                <p className="text-muted-foreground text-[0.8125rem]">
                                    É a parte depois do @ no seu perfil. Em <strong>@voce@mastodon.social</strong>, o servidor é{' '}
                                    <strong>mastodon.social</strong>.
                                </p>
                            </div>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancelar
                                    </Button>
                                </DialogClose>
                                <Button type="submit">Continuar</Button>
                            </DialogFooter>
                        </form>
                    ) : !porSenha ? (
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    Cancelar
                                </Button>
                            </DialogClose>
                            <Button
                                disabled={!conectando?.enderecoDeConexao}
                                onClick={() => conectando?.enderecoDeConexao && (window.location.href = conectando.enderecoDeConexao)}
                            >
                                Autorizar no {conectando?.rotulo}
                            </Button>
                        </DialogFooter>
                    ) : (
                        <form onSubmit={conectar} className="space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="identificador">Seu nome de usuário</Label>
                                <Input
                                    id="identificador"
                                    value={data.identificador}
                                    onChange={(e) => setData('identificador', e.target.value)}
                                    placeholder="voce.bsky.social"
                                    autoComplete="off"
                                    aria-invalid={!!errors.identificador}
                                />
                                <ErroDeCampo mensagem={errors.identificador} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="senha_de_aplicativo">Senha de aplicativo</Label>
                                <CampoSenha
                                    id="senha_de_aplicativo"
                                    value={data.senha_de_aplicativo}
                                    onChange={(e) => setData('senha_de_aplicativo', e.target.value)}
                                    placeholder="xxxx-xxxx-xxxx-xxxx"
                                    autoComplete="off"
                                    aria-invalid={!!errors.senha_de_aplicativo}
                                />
                                <ErroDeCampo mensagem={errors.senha_de_aplicativo} />
                            </div>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancelar
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {processing && <LoaderCircle className="mr-1.5 size-4 animate-spin" aria-hidden="true" />}
                                    Conectar
                                </Button>
                            </DialogFooter>
                        </form>
                    )}
                </DialogContent>
            </Dialog>

            {/* Mover de grupo — diz o que vai e o que FICA */}
            <Dialog open={!!aMover} onOpenChange={(estado) => !estado && setAMover(null)}>
                <DialogContent>
                    <DialogTitle>Levar «{aMover?.nome}» para outro grupo</DialogTitle>
                    <DialogDescription>
                        Daqui em diante ele publica pelo grupo novo. ⚠️ O que já foi publicado{' '}
                        <strong>continua no histórico de {grupos?.atual.nome}</strong> — é o que mantém os números de cada grupo estáveis.
                    </DialogDescription>

                    <ul className="space-y-2">
                        {outrosGrupos.map((grupo) => (
                            <li key={grupo.ulid}>
                                <button
                                    type="button"
                                    onClick={() =>
                                        aMover &&
                                        router.patch(
                                            route('conexoes.grupo', aMover.ulid),
                                            { grupo: grupo.ulid },
                                            { preserveScroll: true, onFinish: () => setAMover(null) },
                                        )
                                    }
                                    className="border-border focus-visible:ring-ring flex w-full items-center gap-2 rounded-lg border p-3 text-left text-sm transition-colors hover:border-[color:var(--accent)] focus-visible:ring-2 focus-visible:outline-none"
                                >
                                    <FolderInput className="text-muted-foreground size-4 shrink-0" aria-hidden="true" />
                                    <span className="truncate font-medium">{grupo.nome}</span>
                                </button>
                            </li>
                        ))}
                    </ul>
                </DialogContent>
            </Dialog>

            {/* Desconectar */}
            <Dialog open={!!aDesconectar} onOpenChange={(estado) => !estado && setADesconectar(null)}>
                <DialogContent>
                    <DialogTitle>Desconectar {aDesconectar?.nome}?</DialogTitle>
                    <DialogDescription>
                        Paramos de publicar nessa conta e apagamos a senha de aplicativo guardada aqui. O que já foi publicado continua no ar, e o
                        histórico com os links continua nesta tela.
                    </DialogDescription>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="secondary">Cancelar</Button>
                        </DialogClose>
                        <Button
                            variant="destructive"
                            onClick={() =>
                                aDesconectar &&
                                router.delete(route('conexoes.desconectar', aDesconectar.ulid), {
                                    onFinish: () => setADesconectar(null),
                                })
                            }
                        >
                            Desconectar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </section>
    );
}

/** Um dos três números do quadrado da rede. */
function NumeroDaRede({ valor, rotulo, cor }: { valor: number; rotulo: string; cor: string }) {
    return (
        <span className="text-center">
            <span className="block text-lg leading-none font-semibold tabular-nums" style={{ color: cor }}>
                {valor}
            </span>
            <span className="text-muted-foreground block text-[0.8125rem] leading-tight">{rotulo}</span>
        </span>
    );
}

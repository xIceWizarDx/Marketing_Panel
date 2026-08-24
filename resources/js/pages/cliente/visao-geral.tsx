import { Head, Link, router, usePage } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, CircleCheck, Clock, Layers, Link2, type LucideIcon } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

import CabecalhoDePagina from '@/components/cabecalho-de-pagina';
import MarcaDaRede from '@/components/conexao/marca-da-rede';
import PainelDeRedes, { type Rede } from '@/components/conexao/painel-de-redes';
import BarraDeEntrega, { type FatiaDaBarra } from '@/components/grafico/barra-de-entrega';
import TituloDeSecao from '@/components/titulo-de-secao';
import { useAtualizacaoViva } from '@/hooks/use-atualizacao-viva';
import LayoutPainel from '@/layouts/painel';
import { cn } from '@/lib/utils';
import { type DadosCompartilhados, type Grupo } from '@/types';

interface Pendencia {
    tom: 'erro' | 'atencao';
    texto: string;
    acao: string;
    /** Navegação de verdade — para outra tela. */
    url: string | null;
    /** Abre o detalhe desta rede aqui mesmo, sem sair da tela. */
    rede: string | null;
    /**
     * Entra neste grupo antes de resolver.
     *
     * ⚠️ Existe porque a grade de redes é filtrada pelo grupo em foco: sem
     * entrar, "Resolver" abriria a janela daquela rede VAZIA (DEC-89).
     */
    grupo: string | null;
}

/** O que cada grupo tem — casa com `grupos.lista` pelo `ulid`. */
interface ResumoDeGrupo {
    ulid: string;
    noAr: number;
    andando: number;
    naoSubiram: number;
    /** Frase pronta do servidor: a tela não formata data. */
    cadencia: string;
    canaisParados: number;
    autorizacoesVencendo: number;
}

interface Props {
    numeros: { noAr: number; andando: number; naoSubiram: number };
    pendencias: Pendencia[];
    resumoDosGrupos: ResumoDeGrupo[];
    /** ⭐ Conexões deixou de ser tela (DEC-63): as redes moram aqui. */
    redes: Rede[];
    totalConectado: number;
}

/**
 * A porta de entrada — e a única pergunta que ela responde.
 *
 * ⭐ **A ordem é a resposta**, de cima para baixo: *o que espera você* → *como
 * está* → *onde você publica*. Quem abre o painel quer saber o que aconteceu
 * enquanto não estava olhando, e cada seção responde uma parte disso — ou não
 * deveria estar aqui.
 *
 * ⛔ **Publicação não aparece aqui** (DEC-68). Uma prévia das últimas era a mesma
 * lista de Publicações com outra moldura, e lista duplicada envelhece: um dia
 * uma mostra o que a outra não mostra, e nenhuma das duas é confiável. O que
 * fica são os **números** — que respondem "como está", não "o que publiquei".
 *
 * ⚠️ **A tela tem duas colunas, e elas respondem coisas diferentes.** À esquerda,
 * a comparação entre grupos. À direita, *o que precisa de você* — os avisos e as
 * redes conectadas. O trilho tem largura fixa porque as barras dos grupos
 * precisam de régua estável para a comparação valer.
 *
 * ⛔ **Não existe placar do total.** Ele existiu, e dizia "40 de 44 posts estão
 * no ar" — um número somado desde sempre, que só sobe e nunca muda de opinião. O
 * que não subiu não é placar, é tarefa, e tarefa mora no trilho da direita.
 *
 * ⚠️ **A faixa de indicadores é a exceção à regra do quadrado.** Cinco quadrados
 * de 7rem seriam uma parede de 124px de altura antes de qualquer conteúdo;
 * cartão baixo e largo cabe rótulo, número e apoio, e devolve meia tela para o
 * que tem o que dizer. Fora dela, a forma continua sendo o quadrado.
 */
export default function VisaoGeral({ numeros, pendencias, resumoDosGrupos, redes, totalConectado }: Props) {
    const { abrirCatalogo, abrirRede } = usePage<DadosCompartilhados>().props;
    const { auth } = usePage<DadosCompartilhados>().props;
    const primeiroNome = auth.usuario?.nome.split(' ')[0] ?? '';

    // O motor é assíncrono: os números mudam sozinhos enquanto há envio em curso.
    useAtualizacaoViva({
        ativo: numeros.andando > 0,
        propriedades: ['numeros', 'pendencias', 'redes', 'resumoDosGrupos'],
    });

    /*
     * ⛔ O que está aberto na grade de redes mora AQUI, não lá dentro.
     *
     * É o que deixa o aviso do topo e o passo "conectar uma rede" abrirem a
     * grade **sem navegar**. Os dois resolvem nesta mesma tela, e ação que
     * resolve aqui não pode virar link para cá.
     */
    const [redeAberta, setRedeAberta] = useState<string | null>(null);

    const { grupos: dadosDosGrupos } = usePage<DadosCompartilhados>().props;
    const grupos = dadosDosGrupos?.lista ?? [];
    const atual = dadosDosGrupos?.atual;

    const porUlid = useMemo(() => new Map(resumoDosGrupos.map((r) => [r.ulid, r])), [resumoDosGrupos]);

    /*
     * ⭐ A MEDIDA COMPARTILHADA — o argumento central da seção.
     *
     * Todas as barras medidas pelo total do maior grupo. Sem isto cada uma se
     * escala por si, e o grupo de 5 posts desenha do mesmo tamanho do de 40:
     * um gráfico que mente por construção.
     */
    const maiorGrupo = useMemo(() => Math.max(1, ...resumoDosGrupos.map((r) => r.noAr + r.andando + r.naoSubiram)), [resumoDosGrupos]);

    const [escolhendoRede, setEscolhendoRede] = useState(false);

    /*
     * ⭐ É assim que "conectar uma rede neste grupo" chega aqui.
     *
     * A engrenagem de um grupo troca o modo no servidor e manda para cá com um
     * recado. O modo segue a intenção, então a conta nasce no grupo certo.
     *
     * ⚠️ **`useEffect`, não valor inicial de `useState`.** O valor inicial roda
     * uma vez só; quem já estava nesta tela clicava na engrenagem, o servidor
     * respondia — e nada abria, porque o componente não remonta numa visita
     * para a mesma página.
     */
    useEffect(() => {
        if (abrirCatalogo) setEscolhendoRede(true);
    }, [abrirCatalogo]);

    /*
     * ⭐ **E é assim que "mexer nesta rede" chega aqui vindo do grupo**
     * (DEC-154).
     *
     * ⛔ A janela do grupo mostra as redes de dentro dele e não age sobre
     * nenhuma — desconectar e mover moram na janela da rede, e devem morar num
     * lugar só: é ação sem volta, e duas portas para ela é como nasce o
     * "desconectei e continuou aparecendo". Então ela não repete o gesto; ela
     * **leva até ele**.
     */
    useEffect(() => {
        if (abrirRede) setRedeAberta(abrirRede);
    }, [abrirRede]);

    return (
        <LayoutPainel migalhas={[{ titulo: 'Visão geral', url: '/painel' }]}>
            <Head title="Visão geral" />

            <CabecalhoDePagina titulo={`Olá, ${primeiroNome}`} descricao="O que aconteceu enquanto você não estava olhando." />

            {/* ─── A FAIXA DE INDICADORES ───────────────────────────────────
                ⭐ Ela abre a tela porque responde em um relance a pergunta que
                trouxe a pessoa aqui. É densa de propósito: seis retângulos
                baixos ocupam menos altura que três quadrados grandes, e sobra
                dobro da tela para o que tem conteúdo.

                ⚠️ **A faixa tem sempre o mesmo tamanho**, inclusive com zero.
                Card que aparece e some faz os vizinhos trocarem de lugar, e o
                olho perde a posição aprendida. Zero não é escondido — ele é
                desenhado em cinza, porque "0 não subiram" é notícia boa e não
                pode ter a mesma tinta vermelha de "4 não subiram". */}
            <div className="mb-4 grid grid-cols-2 gap-2 md:grid-cols-3 xl:grid-cols-5">
                <Indicador rotulo="no ar" valor={numeros.noAr} Icone={CircleCheck} cor="var(--saude-ok)" apoio="confirmados na rede" />
                <Indicador rotulo="a caminho" valor={numeros.andando} Icone={Clock} cor="var(--saude-atencao)" apoio="ainda subindo" />
                <Indicador rotulo="não subiram" valor={numeros.naoSubiram} Icone={AlertTriangle} cor="var(--saude-erro)" apoio="dá para tentar" />
                <Indicador rotulo="canais" valor={totalConectado} Icone={Link2} apoio="prontos para publicar" />
                <Indicador rotulo="grupos" valor={grupos.length} Icone={Layers} apoio="linhas de conteúdo" />
            </div>

            {/* ─── O CORPO EM DUAS COLUNAS ──────────────────────────────────
                ⚠️ O trilho da direita tem largura FIXA. Em duas colunas que se
                dividem por igual, a barra de entrega encolhe junto com a
                janela e a comparação entre grupos perde a régua.

                ⛔ E ele nunca fica vazio: os avisos somem quando está tudo bem
                — de propósito —, então quem mora ali de forma permanente são as
                redes. Coluna que fica em branco na maior parte do tempo é pior
                que não ter coluna. */}
            <div className="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_320px]">
                <div className="min-w-0 space-y-8">
                    {/* ⛔ **Aqui existia um bloco "Como está" e ele foi removido.**
                        Ele escrevia "40 de 44 posts estão no ar" — os mesmos três
                        números da faixa logo acima, ditos por extenso. E, pior que
                        a repetição: era um placar somado desde sempre, que só
                        sobe. No dia em que for 4.000 de 4.004 a frase não diz
                        nada, e o que não subiu não é placar, é **tarefa** — e
                        tarefa já tem lugar próprio, no trilho da direita. */}

                    {/* ─── SEUS GRUPOS ──────────────────────────────────────────── */}
                    {/* ⛔ Com um grupo só a seção NÃO existe: ela repetiria a barra
                    do total palavra por palavra, e comparar uma coisa com nada
                    é decoração. */}
                    {grupos.length > 1 && (
                        <section>
                            <TituloDeSecao
                                titulo="Seus grupos"
                                descricao="As barras estão todas na mesma medida — dá para comparar o tamanho de um com o do outro."
                            />

                            {/* ⭐ Grade de cartões, não lista de linhas. Grupo é
                            *coisa*, não registro: tem nome, tem cara, tem
                            situação própria — e coisa se desenha em cartão.

                            ⚠️ A grade também **conserta** a comparação em vez
                            de atrapalhá-la: coluna de largura igual dá barras
                            de largura igual sem ninguém precisar fixar pixel.
                            Era isso que a linha de seis colunas fazia à mão, e
                            errava toda vez que um nome era comprido. */}
                            <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                {grupos.map((grupo) => {
                                    const resumo = porUlid.get(grupo.ulid);
                                    const emFoco = grupo.ulid === atual?.ulid;

                                    return <CartaoDoGrupo key={grupo.ulid} grupo={grupo} resumo={resumo} emFoco={emFoco} maximo={maiorGrupo} />;
                                })}
                            </ul>
                        </section>
                    )}
                </div>

                {/* ─── O TRILHO ─────────────────────────────────────────────
                    ⭐ O que precisa de VOCÊ fica aqui em cima, e o que está
                    ligado fica embaixo. É o lado da tela que responde "tenho
                    algo para fazer?", separado do lado que responde "como
                    está?".

                    ⚠️ As redes moram aqui de forma permanente, e é isso que
                    salva a coluna: os avisos somem quando está tudo bem — de
                    propósito —, e um trilho vazio na maior parte do tempo seria
                    pior que faixa larga. */}
                <aside className="flex flex-col gap-6">
                    {pendencias.length > 0 && (
                        <section>
                            <TituloDeSecao titulo="Precisa de você" />

                            <ul className="space-y-2">
                                {pendencias.map((pendencia, i) => {
                                    const cor = pendencia.tom === 'erro' ? 'var(--saude-erro)' : 'var(--saude-atencao)';

                                    return (
                                        <li
                                            key={i}
                                            /* ⛔ O fio colorido à esquerda não
                                               vive aqui: ele já significa "este
                                               é o grupo em foco" nos cartões ao
                                               lado, e o mesmo traço com dois
                                               sentidos na mesma tela é uma das
                                               coisas que fazem um painel parecer
                                               desleixado sem ninguém saber
                                               apontar por quê. O aviso se marca
                                               pelo fundo tingido. */
                                            className="flex flex-wrap items-start gap-x-2.5 gap-y-1.5 rounded-lg px-3 py-2.5 text-[0.8125rem]"
                                            style={{ backgroundColor: `color-mix(in oklab, ${cor} 12%, transparent)` }}
                                        >
                                            <AlertTriangle className="mt-0.5 size-4 shrink-0" style={{ color: cor }} aria-hidden="true" />
                                            <span className="min-w-0 flex-1">{pendencia.texto}</span>

                                            {/* Link quando leva para outra tela;
                                                botão quando resolve aqui mesmo.
                                                ⚠️ Aviso sobre várias redes de
                                                uma vez não ganha ação: escolher
                                                uma seria decidir por conta
                                                própria qual é o problema da
                                                pessoa. */}
                                            {pendencia.url ? (
                                                <Link href={pendencia.url} className={acaoDoAviso}>
                                                    {pendencia.acao}
                                                    <ArrowRight className="size-3" aria-hidden="true" />
                                                </Link>
                                            ) : pendencia.grupo ? (
                                                <button
                                                    type="button"
                                                    onClick={() => router.post(route('grupos.usar', pendencia.grupo!), {}, { preserveScroll: true })}
                                                    className={acaoDoAviso}
                                                >
                                                    {pendencia.acao}
                                                    <ArrowRight className="size-3" aria-hidden="true" />
                                                </button>
                                            ) : (
                                                pendencia.rede && (
                                                    <button type="button" onClick={() => setRedeAberta(pendencia.rede)} className={acaoDoAviso}>
                                                        {pendencia.acao}
                                                        <ArrowRight className="size-3" aria-hidden="true" />
                                                    </button>
                                                )
                                            )}
                                        </li>
                                    );
                                })}
                            </ul>
                        </section>
                    )}

                    {/* ⚠️ Fica na porta de entrada porque o semáforo do token
                        (DEC-32) precisa ser visto ANTES de a conexão quebrar —
                        não no dia em que a publicação já falhou por causa
                        dela. */}
                    <PainelDeRedes
                        redes={redes}
                        totalConectado={totalConectado}
                        aberta={redeAberta}
                        aoAbrir={setRedeAberta}
                        escolhendo={escolhendoRede}
                        aoEscolher={setEscolhendoRede}
                    />
                </aside>
            </div>
        </LayoutPainel>
    );
}

/*
 * ⚠️ **Nunca `text-xs` aqui.** Com a fonte base fluida do painel (13px a 15px),
 * `xs` cai para uns 9,7px — e isto é o *alvo clicável* do bloco mais urgente da
 * tela. Era o menor texto da página no lugar onde ele menos podia ser pequeno.
 *
 * O `-my-1 py-1` devolve altura de alvo sem empurrar a linha do aviso: o toque
 * mínimo é de 24px em cada direção, e o texto sozinho não chega lá.
 */
const acaoDoAviso =
    'inline-flex -my-1 items-center gap-1 rounded-md px-1 py-1 text-[0.8125rem] font-medium text-[color:var(--accent)] hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';

type Numeros = { noAr: number; andando: number; naoSubiram: number };

/** Terminou = subiu ou nao subiu. O que ainda esta indo nao e nem um nem outro. */
const terminados = (n: Numeros) => n.noAr + n.naoSubiram;

const temAlgo = (n: Numeros) => terminados(n) + n.andando > 0;

/**
 * As fatias, na ordem fixa: no ar -> a caminho -> nao subiram.
 *
 * ⚠️ Fatia zerada nao entra: pedaco invisivel na barra e ruido, e o numero
 * exato ja esta escrito ao lado.
 */
function fatiasDe(n: Numeros): FatiaDaBarra[] {
    const todas: FatiaDaBarra[] = [
        { chave: 'no_ar', rotulo: 'no ar', valor: n.noAr, cor: 'var(--saude-ok)' },
        // ⭐ Listrado: e o unico estado que ainda nao terminou, e o unico
        // desenhado como inacabado. Serve tambem de segundo canal para quem nao
        // distingue verde de ambar — cor nunca e o unico canal.
        { chave: 'a_caminho', rotulo: 'a caminho', valor: n.andando, cor: 'var(--saude-atencao)', padrao: 'listrado' },
        { chave: 'nao_subiram', rotulo: 'nao subiram', valor: n.naoSubiram, cor: 'var(--saude-erro)' },
    ];

    return todas.filter((f) => f.valor > 0);
}

const acessivel = (n: Numeros) =>
    fatiasDe(n)
        .map((f) => f.valor + ' ' + f.rotulo)
        .join(', ');

/**
 * Um grupo na comparação — **um cartão**, não uma linha de planilha.
 *
 * ⚠️ Isto já foi uma linha com seis elementos lado a lado. Cabia, mas lia-se
 * como tabela: o olho varria da esquerda para a direita atrás de qual coluna era
 * qual, e o nome do grupo — a única coisa que a pessoa procura — ficava do mesmo
 * tamanho do resto. Em cartão a leitura é de cima para baixo, sempre na mesma
 * ordem: *quem é* → *como vai* → *o que dá para fazer*.
 *
 * ⭐ A grade resolve de graça o que a linha resolvia à mão: colunas de largura
 * igual dão barras de largura igual, e é o alinhamento entre elas que torna a
 * medida compartilhada legível. Sem isso a seção inteira perde o argumento.
 */
function CartaoDoGrupo({ grupo, resumo, emFoco, maximo }: { grupo: Grupo; resumo?: ResumoDeGrupo; emFoco: boolean; maximo: number }) {
    const n: Numeros = resumo ?? { noAr: 0, andando: 0, naoSubiram: 0 };
    const parados = resumo?.canaisParados ?? 0;
    const vencendo = resumo?.autorizacoesVencendo ?? 0;

    /* ⛔ Um aviso por cartão, e o pior deles. Canal parado e autorização
       vencendo juntos viram duas linhas vermelhas que dizem quase a mesma
       coisa — e o cartão passa a gritar em vez de avisar. */
    const alerta = parados
        ? { texto: parados === 1 ? '1 canal parado' : `${parados} canais parados`, cor: 'var(--saude-erro)' }
        : vencendo
          ? { texto: vencendo === 1 ? '1 autorização vencendo' : `${vencendo} autorizações vencendo`, cor: 'var(--saude-atencao)' }
          : null;

    return (
        <li className="h-full">
            {/* ⭐ O grupo em foco se marca por TRÊS coisas ao mesmo tempo:
                contorno na cor da marca, fundo levemente tingido, e a frase no
                rodapé dizendo onde a pessoa está. Contorno sozinho, no tema
                claro, quase não aparece ao lado de um cartão branco — e cor
                sozinha nunca pode ser o único sinal. */}
            <article
                className={cn('flex h-full flex-col rounded-xl border p-4 sm:p-5', emFoco ? 'border-[color:var(--accent)]' : 'border-border bg-card')}
                style={emFoco ? { backgroundColor: 'color-mix(in oklab, var(--accent) 5%, var(--card))' } : undefined}
            >
                {/* As marcas empilhadas — reconhecem o grupo antes de o nome ser lido. */}
                <span className="flex items-center -space-x-2">
                    {grupo.plataformas.length > 0 ? (
                        grupo.plataformas.slice(0, 4).map((rede) => (
                            <span key={rede} className="ring-card rounded-md ring-2">
                                <MarcaDaRede rede={rede} className="size-7 rounded-md" />
                            </span>
                        ))
                    ) : (
                        <span className="border-border size-7 rounded-md border border-dashed" aria-hidden="true" />
                    )}
                </span>

                <p className="mt-3 truncate text-[0.9375rem] font-semibold" title={grupo.nome}>
                    {grupo.nome}
                </p>
                <p className="text-muted-foreground text-[0.8125rem]">{resumo?.cadencia}</p>

                {/* ⭐ Aqui o cartão paga o que a linha não pagava: **o problema
                    dito por escrito**. Antes era um ponto colorido de 2px do
                    lado do nome, com o texto só no leitor de tela — quem enxerga
                    via um ponto e não sabia de quê. Largura de cartão sobra para
                    a frase. */}
                {alerta && (
                    <p className="mt-1.5 flex items-center gap-1.5 text-[0.8125rem]" style={{ color: alerta.cor }}>
                        <AlertTriangle className="size-3.5 shrink-0" aria-hidden="true" />
                        {alerta.texto}
                    </p>
                )}

                {/* ⚠️ Contorno mais fraco que o do cartão: divisão DENTRO de uma
                    caixa não pode ter o mesmo peso da caixa, ou o cartão parece
                    dois cartões grudados. */}
                <div className="border-border/60 mt-auto border-t pt-4">
                    {temAlgo(n) ? (
                        <>
                            <BarraDeEntrega
                                fatias={fatiasDe(n)}
                                maximo={maximo}
                                corDoVazio="var(--trilho)"
                                rotuloAcessivel={`${grupo.nome}: ${acessivel(n)}`}
                            />

                            {/* ⭐ Caso em que a medida compartilhada mais engana:
                                5 falhas em 5 desenham menos vermelho que 3 em
                                44. A correção é texto, não pixel.

                                ⛔ **Só o desvio é colorido.** O mesmo fato já
                                aparece em cor na fatia da barra logo acima; se
                                aparecer de novo no número escrito, a cor deixa
                                de ser sinal e vira papel de parede — e aí a
                                falha, que é o que precisa saltar, some no meio
                                do verde e do âmbar. O que subiu fica em tinta
                                normal, o que não subiu fica vermelho. */}
                            <p className="text-muted-foreground mt-2 text-[0.8125rem] tabular-nums">
                                {n.noAr === 0 && terminados(n) > 0 ? (
                                    <span style={{ color: 'var(--saude-erro)' }}>nenhum dos {terminados(n)} subiu</span>
                                ) : (
                                    <>
                                        <span className="text-foreground font-medium">{n.noAr} no ar</span>
                                        {n.andando > 0 && <span> · {n.andando} a caminho</span>}
                                        {n.naoSubiram > 0 && (
                                            <span className="font-medium" style={{ color: 'var(--saude-erro)' }}>
                                                {' '}
                                                · {n.naoSubiram} {n.naoSubiram === 1 ? 'não subiu' : 'não subiram'}
                                            </span>
                                        )}
                                    </>
                                )}
                            </p>
                        </>
                    ) : (
                        <p className="text-muted-foreground text-[0.8125rem]">
                            {grupo.redes === 0 ? 'conecte um canal pela engrenagem do seletor' : 'nada publicado ainda'}
                        </p>
                    )}

                    {/* ⛔ O modo só muda por gesto com VERBO (DEC-91) — e o
                        cartão em foco ocupa a mesma altura com a frase que diz
                        onde a pessoa está, para os cartões não se
                        desalinharem por causa de um botão a menos. */}
                    <div className="mt-3">
                        {emFoco ? (
                            <p className="flex items-center gap-1.5 text-[0.8125rem] text-[color:var(--accent)]">
                                <span className="size-1.5 rounded-full bg-[color:var(--accent)]" aria-hidden="true" />
                                Você está neste grupo
                            </p>
                        ) : (
                            <button
                                type="button"
                                onClick={() => router.post(route('grupos.usar', grupo.ulid), {}, { preserveScroll: true })}
                                title={`Entrar em «${grupo.nome}» — o painel inteiro passa a mostrar este grupo`}
                                className="text-muted-foreground hover:text-foreground focus-visible:ring-ring inline-flex items-center gap-1 rounded-md text-[0.8125rem] transition-colors focus-visible:ring-2 focus-visible:outline-none"
                            >
                                Entrar neste grupo
                                <ArrowRight className="size-3.5" aria-hidden="true" />
                            </button>
                        )}
                    </div>
                </div>
            </article>
        </li>
    );
}

/**
 * Um cartão da faixa de indicadores.
 *
 * ⭐ **Zero apaga a cor.** Um "0 não subiram" pintado de vermelho grita uma
 * notícia boa — e depois de o painel gritar à toa uma vez, o vermelho de
 * verdade vale menos. Só o número que existe carrega a tinta do estado; zerado
 * fica cinza, e continua ocupando o mesmo lugar na faixa.
 *
 * ⚠️ **Retângulo aqui é a forma certa**, e é a única exceção à regra do
 * quadrado nesta tela. O quadrado de 7rem servia quando eram três; com cinco
 * indicadores ele viraria uma parede de 124px de altura antes de qualquer
 * conteúdo. Cartão baixo e largo cabe rótulo, número e apoio em três linhas
 * curtas, e devolve meia tela para o que tem o que dizer.
 */
function Indicador({
    rotulo,
    valor,
    apoio,
    Icone,
    cor,
}: {
    rotulo: string;
    valor: number;
    apoio: string;
    Icone: LucideIcon;
    /** A cor do estado. Ausente = indicador neutro, que não é bom nem ruim. */
    cor?: string;
}) {
    const aceso = cor !== undefined && valor > 0;

    return (
        <div
            className="border-border bg-card flex flex-col gap-1 rounded-lg border p-3.5"
            style={aceso ? { backgroundColor: `color-mix(in oklab, ${cor} 7%, var(--card))` } : undefined}
        >
            <div className="flex items-center justify-between gap-2">
                {/* Versalete com espaçamento positivo entre as letras — é ele
                    que devolve legibilidade ao tamanho pequeno. */}
                <span className="text-muted-foreground truncate text-[0.8125rem] font-semibold tracking-wider uppercase">{rotulo}</span>
                <Icone className="size-3.5 shrink-0" style={{ color: aceso ? cor : 'var(--muted-foreground)' }} aria-hidden="true" />
            </div>

            <span className="text-2xl leading-none font-semibold tabular-nums" style={aceso ? { color: cor } : undefined}>
                {valor}
            </span>

            <span className="text-muted-foreground truncate text-[0.8125rem]">{apoio}</span>
        </div>
    );
}

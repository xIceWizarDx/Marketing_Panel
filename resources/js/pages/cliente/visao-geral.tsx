import { Head, Link, router, usePage } from '@inertiajs/react';
import { AlertTriangle, ArrowRight } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

import CabecalhoDePagina from '@/components/cabecalho-de-pagina';
import MarcaDaRede from '@/components/conexao/marca-da-rede';
import PainelDeRedes, { type Rede } from '@/components/conexao/painel-de-redes';
import BarraDeEntrega, { type FatiaDaBarra } from '@/components/grafico/barra-de-entrega';
import Quadro from '@/components/quadro';
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
 * ⚠️ **A forma padrão é o quadrado.** Retângulo aparece só onde ele é a forma
 * certa: aviso com texto corrido e lista de passos. Faixa larga com um número
 * dentro desperdiça a linha inteira e faz o olho varrer da esquerda à direita
 * para ler três dígitos.
 */
export default function VisaoGeral({ numeros, pendencias, resumoDosGrupos, redes, totalConectado }: Props) {
    const { abrirCatalogo } = usePage<DadosCompartilhados>().props;
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

    return (
        <LayoutPainel migalhas={[{ titulo: 'Visão geral', url: '/painel' }]}>
            <Head title="Visão geral" />

            <CabecalhoDePagina titulo={`Olá, ${primeiroNome}`} descricao="O que aconteceu enquanto você não estava olhando." />

            <div className="space-y-7">
                {/* ⭐ O que está esperando VOCÊ — e some quando não há nada.
                    ⚠️ Um bloco que vive dizendo "está tudo bem" treina a pessoa a
                    ignorá-lo, e no dia em que houver problema de verdade ela não
                    vai olhar. Aviso que aparece sempre não é aviso, é decoração.

                    Aqui o retângulo é a forma CERTA: é frase mais ação, e frase
                    quer largura. */}
                {pendencias.length > 0 && (
                    <ul className="space-y-2">
                        {pendencias.map((pendencia, i) => {
                            const cor = pendencia.tom === 'erro' ? 'var(--saude-erro)' : 'var(--saude-atencao)';

                            return (
                                <li
                                    key={i}
                                    className="border-border bg-card flex flex-wrap items-center gap-x-3 gap-y-1.5 rounded-lg border py-2.5 pr-3 pl-3 text-sm"
                                    style={{ borderLeftWidth: '3px', borderLeftColor: cor }}
                                >
                                    <AlertTriangle className="size-4 shrink-0" style={{ color: cor }} aria-hidden="true" />
                                    <span className="flex-1">{pendencia.texto}</span>

                                    {/* Link quando leva para outra tela; botão quando
                                        resolve aqui mesmo. ⚠️ Aviso sobre várias redes
                                        de uma vez não ganha ação: escolher uma delas
                                        seria decidir por conta própria qual é o
                                        problema da pessoa. O ponto colorido na grade
                                        logo abaixo é quem aponta. */}
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
                )}

                {/* ─── COMO ESTÁ ────────────────────────────────────────────── */}
                <section>
                    <TituloDeSecao
                        titulo="Como está"
                        /* ⛔ Com mais de um grupo o link some (DEC-88): o número
                           soma tudo, e "ver publicações" abriria uma lista de UM
                           grupo que não bate com ele. Mandar a pessoa para um
                           recorte diferente do que ela clicou é onde o painel
                           começa a deixar de ser confiável. */
                        apoio={
                            grupos.length > 1 ? (
                                `somando seus ${grupos.length} grupos`
                            ) : (
                                <Link href={route('publicacoes')} className="hover:text-foreground">
                                    ver publicações
                                </Link>
                            )
                        }
                    />

                    {/* ⭐ Os três lados do mesmo fato — a falha do lado do acerto,
                        no mesmo tamanho. Mostrar só o que deu certo é o que os
                        concorrentes fazem, e é por isso que o painel deles mente.

                        "A caminho" e "não subiram" só aparecem quando existem:
                        zero em toda parte vira ruído e esconde o que importa. */}
                    <ul className="flex flex-wrap gap-2.5">
                        <li>
                            <Numero valor={numeros.noAr} rotulo="no ar" apoio="confirmados" cor="var(--saude-ok)" />
                        </li>
                        {numeros.andando > 0 && (
                            <li>
                                <Numero valor={numeros.andando} rotulo="a caminho" apoio="ainda subindo" cor="var(--saude-atencao)" />
                            </li>
                        )}
                        {numeros.naoSubiram > 0 && (
                            <li>
                                <Numero valor={numeros.naoSubiram} rotulo="não subiram" apoio="dá para tentar" cor="var(--saude-erro)" />
                            </li>
                        )}
                    </ul>

                    {/* ⭐ Os quadrados são a LEGENDA da barra: mesma ordem,
                        mesmas cores, mesmas palavras. Quadrado é número
                        absoluto; barra é proporção — um não existe sem o outro,
                        e por isso não são a mesma informação duas vezes.

                        Ela nasce com o primeiro envio: barra de 0% seria a
                        promessa de que existe algo ali. */}
                    {temAlgo(numeros) && (
                        <div className="mt-3">
                            <BarraDeEntrega fatias={fatiasDe(numeros)} corDoVazio="var(--muted)" rotuloAcessivel={acessivel(numeros)} />
                            <p className="text-muted-foreground mt-2 text-sm">{fraseDaEntrega(numeros)}</p>
                        </div>
                    )}
                </section>

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

                        <ul className="border-border divide-border bg-card divide-y rounded border">
                            {grupos.map((grupo) => {
                                const resumo = porUlid.get(grupo.ulid);
                                const emFoco = grupo.ulid === atual?.ulid;

                                return <LinhaDoGrupo key={grupo.ulid} grupo={grupo} resumo={resumo} emFoco={emFoco} maximo={maiorGrupo} />;
                            })}
                        </ul>
                    </section>
                )}

                {/* ─── ONDE VOCÊ PUBLICA ────────────────────────────────────── */}
                {/* ⚠️ Fica na porta de entrada porque o semáforo do token
                    (DEC-32) precisa ser visto ANTES de a conexão quebrar — não
                    no dia em que a publicação já falhou por causa dela. */}
                <PainelDeRedes
                    redes={redes}
                    totalConectado={totalConectado}
                    aberta={redeAberta}
                    aoAbrir={setRedeAberta}
                    escolhendo={escolhendoRede}
                    aoEscolher={setEscolhendoRede}
                />
            </div>
        </LayoutPainel>
    );
}

const acaoDoAviso = 'inline-flex items-center gap-1 text-xs font-medium text-[color:var(--accent)] hover:underline';

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
 * A frase que so este produto sabe escrever.
 *
 * ⛔ Sem porcentagem: com 3 posts, "100%" vira "67%" e a pessoa acha que o
 * painel piorou. "36 de 40" e a mesma verdade sem o numero que oscila.
 */
function fraseDaEntrega(n: Numeros): string {
    const fim = terminados(n);
    const aCaminho = n.andando > 0 ? ` · ${n.andando} ainda a caminho` : '';

    if (fim === 0) {
        return `Nada terminou ainda — ${n.andando} ${n.andando === 1 ? 'post' : 'posts'} a caminho.`;
    }
    if (n.naoSubiram === 0) {
        return (fim === 1 ? 'Seu primeiro post está no ar.' : `Os ${fim} posts estão no ar.`) + aCaminho;
    }
    if (n.noAr === 0) {
        return `Nenhum dos ${fim} posts subiu.` + aCaminho;
    }

    return `${n.noAr} de ${fim} posts estão no ar.` + aCaminho;
}

/**
 * Uma linha da comparacao entre grupos.
 *
 * ⚠️ A barra vive em coluna de **largura fixa**, nunca `flex-1`: e isso que
 * alinha as barras entre si. Sem alinhamento, a medida compartilhada — que e o
 * argumento inteiro da secao — deixa de ser legivel.
 */
function LinhaDoGrupo({ grupo, resumo, emFoco, maximo }: { grupo: Grupo; resumo?: ResumoDeGrupo; emFoco: boolean; maximo: number }) {
    const n: Numeros = resumo ?? { noAr: 0, andando: 0, naoSubiram: 0 };
    const parados = resumo?.canaisParados ?? 0;
    const vencendo = resumo?.autorizacoesVencendo ?? 0;
    const problema = parados > 0;
    const atencao = !problema && vencendo > 0;

    return (
        <li
            className={cn(
                'flex flex-wrap items-center gap-x-4 gap-y-3 border-l-2 py-3 pr-3 pl-3',
                emFoco ? 'border-[color:var(--accent)]' : 'border-transparent',
            )}
        >
            {/* As marcas empilhadas — reconhecem o grupo antes de o nome ser lido. */}
            <span className="flex shrink-0 items-center -space-x-2">
                {grupo.plataformas.length > 0 ? (
                    grupo.plataformas.slice(0, 4).map((rede) => (
                        <span key={rede} className="ring-card rounded-md ring-2">
                            <MarcaDaRede rede={rede} className="size-6 rounded-md" />
                        </span>
                    ))
                ) : (
                    <span className="border-border size-6 rounded-md border border-dashed" aria-hidden="true" />
                )}
            </span>

            <div className="min-w-0 flex-1">
                <p className="flex items-center gap-1.5 text-sm font-medium">
                    <span className="truncate" title={grupo.nome}>
                        {grupo.nome}
                    </span>

                    {/* ⭐ Problema NUNCA e medido por comprimento. O ponto tem o
                        mesmo tamanho no grupo de 40 e no de 5. */}
                    {(problema || atencao) && (
                        <span
                            className="size-2 shrink-0 rounded-full"
                            style={{ backgroundColor: problema ? 'var(--saude-erro)' : 'var(--saude-atencao)' }}
                        >
                            <span className="sr-only">{problema ? `${parados} canal parado` : `${vencendo} autorização vencendo`}</span>
                        </span>
                    )}
                </p>
                <p className="text-muted-foreground text-[0.8125rem]">{resumo?.cadencia}</p>
            </div>

            {/* ⚠️ No celular a barra fica sozinha na linha: dividir espaco com
                texto foi onde este desenho morria — 5 posts e 0 posts viravam a
                mesma mancha de meio centimetro. */}
            <div className="w-full sm:w-[clamp(140px,18vw,320px)]">
                {temAlgo(n) ? (
                    <BarraDeEntrega
                        fatias={fatiasDe(n)}
                        maximo={maximo}
                        corDoVazio="var(--muted)"
                        rotuloAcessivel={`${grupo.nome}: ${acessivel(n)}`}
                    />
                ) : (
                    <p className="text-muted-foreground text-[0.8125rem]">
                        {grupo.redes === 0 ? 'conecte um canal pela engrenagem do seletor' : 'nada publicado ainda'}
                    </p>
                )}
            </div>

            {/* ⭐ Caso em que a medida compartilhada mais engana: 5 falhas em 5
                desenham menos vermelho que 3 em 44. A correcao e texto de
                tamanho fixo, nao pixel. */}
            <p className="shrink-0 text-sm tabular-nums">
                {n.noAr === 0 && terminados(n) > 0 ? (
                    <span style={{ color: 'var(--saude-erro)' }}>nenhum dos {terminados(n)} subiu</span>
                ) : (
                    <>
                        <span style={{ color: 'var(--saude-ok)' }}>{n.noAr} no ar</span>
                        {n.andando > 0 && <span style={{ color: 'var(--saude-atencao)' }}> · {n.andando} a caminho</span>}
                        {n.naoSubiram > 0 && (
                            <span style={{ color: 'var(--saude-erro)' }}>
                                {' '}
                                · {n.naoSubiram} {n.naoSubiram === 1 ? 'não subiu' : 'não subiram'}
                            </span>
                        )}
                    </>
                )}
            </p>

            {/* ⛔ O modo so muda por gesto com VERBO (DEC-91). */}
            {!emFoco && (
                <button
                    type="button"
                    onClick={() => router.post(route('grupos.usar', grupo.ulid), {}, { preserveScroll: true })}
                    title={`Entrar em «${grupo.nome}» — o painel inteiro passa a mostrar este grupo`}
                    className="text-muted-foreground hover:text-foreground focus-visible:ring-ring shrink-0 rounded-md px-2 py-1 text-[0.8125rem] transition-colors focus-visible:ring-2 focus-visible:outline-none"
                >
                    Entrar neste grupo
                </button>
            )}
        </li>
    );
}

/** Um dos números do topo — quadrado, como tudo que é resumo aqui. */
function Numero({ valor, rotulo, apoio, cor }: { valor: number; rotulo: string; apoio: string; cor: string }) {
    return (
        <Quadro>
            <span className="text-3xl leading-none font-semibold tabular-nums" style={{ color: cor }}>
                {valor}
            </span>
            <span className="mt-1.5 text-xs leading-tight font-medium">{rotulo}</span>
            <span className="text-muted-foreground text-[0.625rem] leading-tight">{apoio}</span>
        </Quadro>
    );
}

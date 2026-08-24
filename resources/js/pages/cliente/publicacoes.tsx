import { Head, Link, router } from '@inertiajs/react';
import { ExternalLink, Eye, Heart, MessageCircle, Plus, Repeat2, RotateCw, Send } from 'lucide-react';

import CabecalhoDePagina from '@/components/cabecalho-de-pagina';
import MarcaDaRede from '@/components/conexao/marca-da-rede';
import BarraDeEntrega from '@/components/grafico/barra-de-entrega';
import Miniatura from '@/components/midia/miniatura';
import Compositor, { type DadosDoCompositor } from '@/components/publicacao/compositor';
import TituloDeSecao from '@/components/titulo-de-secao';
import { Button } from '@/components/ui/button';
import { useAtualizacaoViva } from '@/hooks/use-atualizacao-viva';
import LayoutPainel from '@/layouts/painel';
import { type Paginado } from '@/types';

interface DestinoDaLista {
    ulid: string;
    /** Só vem quando há mais de uma conta na mesma rede. */
    conta: string | null;
    plataforma: string;
    plataformaRotulo: string;
    status: string;
    statusRotulo: string;
    url: string | null;
    /** `hd` ou `sd` — o que a própria rede diz ter entregado. */
    qualidade: string | null;
    erro: string | null;
    podeReprocessar: boolean;
    /**
     * Os contadores que aquela rede publica — e **só** os que ela publica.
     *
     * ⛔ `null` NUNCA vira `0` na tela (DEC-95). No Bluesky visualização não
     * existe no protocolo: um zero ali diria "ninguém viu", quando o certo é
     * "ninguém conta". Quem explica isso é `notaDeMetrica`.
     */
    visualizacoes: number | null;
    curtidas: number | null;
    comentarios: number | null;
    compartilhamentos: number | null;
    /** Frase pronta do servidor: a tela não formata data. */
    metricasLidas: string | null;
    /**
     * ⭐ Quando conferimos pela última vez que este post **continua** no ar
     * (DEC-145).
     *
     * ⚠️ "No ar" sem data é afirmação sem prazo — e afirmação sem prazo
     * envelhece em silêncio. É o que separa este painel de quem olhou uma vez.
     */
    conferidoEm: string | null;
    /**
     * ⭐ Como este post se saiu perto da média **da própria rede** (DEC-147).
     *
     * ⛔ É a comparação que resolve o problema das unidades: comparar TikTok com
     * Reels é comparar réguas diferentes; comparar TikTok com o próprio TikTok,
     * não. `null` quando não há base — e aí a tela cala.
     */
    contraMedia: string | null;
    /** O que esta rede não conta, escrito por extenso. */
    notaDeMetrica: string | null;
    /**
     * ⛔ O que esta rede não deixa CONFERIR depois de publicar (DEC-106).
     *
     * ⚠️ `null` na maioria das redes, e aí o link é prova relida de verdade. No
     * LinkedIn não é — e mostrar o link com a mesma cara seria afirmar uma
     * conferência que não aconteceu.
     */
    notaDaProva: string | null;
}

interface PublicacaoDaLista {
    ulid: string;
    titulo: string | null;
    legenda: string | null;
    status: string;
    statusRotulo: string;
    midia: string;
    miniatura: string | null;
    /** Só enquanto o arquivo original estiver aqui (DEC-55). */
    podeRepublicar: boolean;
    criadaEm: string | null;
    destinos: DestinoDaLista[];
}

/** Curto de propósito: no cartão quadrado não cabe data por extenso. */
const quando = (iso: string | null) => (iso ? new Date(iso).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }) : '');

/**
 * Os estados que já terminaram — nada muda neles sozinho.
 *
 * ⚠️ A lista é dos TERMINAIS, e não dos intermediários, de propósito: assim um
 * estado novo é tratado como "ainda andando" e a tela continua atualizando, em
 * vez de dar por pronto o que ninguém previu.
 *
 * ⭐ `removido` entra aqui porque ele **terminou** (DEC-148): o post subiu e a
 * rede tirou. Sem ele nesta lista, a tela ficaria atualizando para sempre um
 * destino que não muda mais.
 */
const TERMINAIS = ['publicado', 'falhou', 'removido'];

const emAndamento = (status: string) => !TERMINAIS.includes(status);

/** Um gráfico — o de UMA rede, na medida que essa rede publica (DEC-94). */
interface ComparativoDeRede {
    rede: string;
    redeRotulo: string;
    /** "visualizações", "curtidas" — nunca a medida de outra rede. */
    medida: string;
    barras: { ulid: string; titulo: string; valor: number; url: string | null }[];
    /** Todo mundo em zero: é estado, e tem frase própria. */
    tudoZerado: boolean;
    notaDeZero: string | null;
}

const ABAS = [
    { chave: 'tudo', rotulo: 'Tudo' },
    { chave: 'andando', rotulo: 'Em andamento' },
    { chave: 'no_ar', rotulo: 'No ar' },
    { chave: 'falharam', rotulo: 'Não subiram' },
] as const;

/**
 * ⭐ O total — e as ressalvas que o tornam honesto (DEC-146).
 *
 * ⛔ O número serve para **sentir tamanho**, nunca para comparar redes: cada uma
 * conta visualização do seu jeito, e a rede que conta mais frouxo dominaria a
 * soma. A comparação mora no `comparativo`, ao lado.
 */
interface Alcance {
    visualizacoes: number | null;
    /** Frase fixa: a soma é bruta, e a tela diz isso. */
    nota: string;
    /** "Somando 3 de 4 redes…" — só aparece quando falta alguém. */
    redes: string | null;
}

export default function Publicacoes({
    publicacoes,
    aba,
    contagem,
    alcance,
    comparativo,
    compositor,
}: {
    publicacoes: Paginado<PublicacaoDaLista>;
    aba: string;
    contagem: Record<string, number>;
    alcance: Alcance;
    /** Um por rede que tem número comparável — pode vir vazio. */
    comparativo: ComparativoDeRede[];
    /** Vem preenchido quando a rota é `/publicar` — o modal abre por cima. */
    compositor: DadosDoCompositor | null;
}) {
    // O motor é assíncrono: enfileira, envia depois, confirma depois ainda. Sem
    // isto a tela ficaria congelada em "na fila" até alguém atualizar na mão.
    useAtualizacaoViva({
        ativo: publicacoes.data.some((p) => p.destinos.some((d) => emAndamento(d.status))),
        propriedades: ['publicacoes'],
    });

    // Fechar é NAVEGAR de volta: o compositor tem endereço próprio, então
    // fechá-lo por estado local deixaria a URL mentindo sobre a tela.
    const fecharCompositor = () => router.get(route('publicacoes', aba === 'tudo' ? {} : { aba }), {}, { preserveScroll: true });

    return (
        <LayoutPainel migalhas={[{ titulo: 'Publicações', url: '/publicacoes' }]}>
            <Head title="Publicações" />

            <CabecalhoDePagina
                titulo="Publicações"
                acoes={
                    <Button asChild size="sm">
                        <Link href={route('publicar')} preserveScroll>
                            <Send className="mr-1.5 size-4" aria-hidden="true" />
                            Publicar
                        </Link>
                    </Button>
                }
            />

            {/* ⭐ O TOTAL — e ele responde uma pergunta só: "estou crescendo?"
                (DEC-146).

                ⛔ Só aparece quando existe leitura. Um zero aqui diria que
                ninguém viu, quando o certo é que ninguém leu ainda. */}
            {alcance.visualizacoes !== null && (
                <section aria-label="Alcance somado" className="border-border bg-card mb-4 rounded-xl border p-4">
                    <p className="text-muted-foreground text-[0.8125rem]">Visualizações somadas</p>

                    <p className="mt-0.5 text-2xl font-semibold tabular-nums">{alcance.visualizacoes.toLocaleString('pt-BR')}</p>

                    {/* ⛔ A primeira ressalva, junto do número e não escondida
                        num rodapé: quem lê o número precisa ler o limite dele. */}
                    <p className="text-muted-foreground mt-1.5 max-w-prose text-[0.8125rem] leading-snug">{alcance.nota}</p>

                    {/* ⚠️ A segunda: sem ela, uma rede que não respondeu hoje
                        vira queda de desempenho que não aconteceu. */}
                    {alcance.redes && (
                        <p className="mt-1 text-[0.8125rem] leading-snug" style={{ color: 'var(--saude-atencao)' }}>
                            {alcance.redes}
                        </p>
                    )}
                </section>
            )}

            {/* ⭐ O número na aba já responde "tem coisa parada?" e "falhou
                alguma?" sem ninguém clicar. Um seletor de filtro esconderia
                justamente a informação que a pessoa abriu a tela para ver.

                A aba vive na URL: dá para voltar e compartilhar o recorte. */}
            <div role="tablist" aria-label="Filtrar publicações" className="border-border mb-4 flex flex-wrap gap-1 border-b">
                {ABAS.map(({ chave, rotulo }) => {
                    const ativa = aba === chave;
                    const quantas = contagem[chave] ?? 0;

                    // Abas vazias somem, menos a atual e "Tudo": mostrar
                    // "Não subiram (0)" para sempre é ruído, e some justamente
                    // quando não há o que fazer.
                    if (quantas === 0 && !ativa && chave !== 'tudo') return null;

                    return (
                        <Link
                            key={chave}
                            href={route('publicacoes', chave === 'tudo' ? {} : { aba: chave })}
                            role="tab"
                            aria-selected={ativa}
                            preserveScroll
                            className={`-mb-px flex items-center gap-1.5 border-b-2 px-3 py-2 text-sm transition-colors ${
                                ativa ? 'border-[color:var(--accent)] font-medium' : 'text-muted-foreground hover:text-foreground border-transparent'
                            }`}
                        >
                            {rotulo}
                            <span
                                className="text-xs tabular-nums"
                                style={chave === 'falharam' && quantas > 0 ? { color: 'var(--saude-erro)' } : undefined}
                            >
                                {quantas}
                            </span>
                        </Link>
                    );
                })}
            </div>

            {/* ⭐ A COMPARAÇÃO ENTRE OS POSTS — um gráfico por rede, cada um na
                medida daquela rede.

                ⚠️ Fica acima da lista de propósito: ela responde "o que eu
                publiquei", e isto responde "o que funcionou". São perguntas
                diferentes, e a segunda é a que faz a pessoa voltar amanhã.

                ⛔ Só aparece na aba "Tudo": nas outras a lista está filtrada, e
                um gráfico que ignora o filtro ao lado de uma lista que o
                obedece é a receita de dois números para o mesmo fato. */}
            {aba === 'tudo' && comparativo.map((rede) => <ComparativoDoPost key={rede.rede} comparativo={rede} />)}

            {publicacoes.data.length === 0 ? (
                <div className="border-border bg-card rounded-lg border p-8 text-center">
                    <p className="text-sm font-medium">Você ainda não publicou nada</p>
                    <p className="text-muted-foreground mx-auto mt-1.5 max-w-prose text-sm">
                        Quando publicar, cada conta aparece aqui com o próprio estado — e, quando confirmarmos na rede, com o link do post.
                    </p>
                </div>
            ) : (
                /* ⭐ Cartão QUADRADO, como os de conexão: o olho varre uma grade
                   muito mais rápido que uma lista de faixas largas. A miniatura
                   preenche o cartão e o texto vem por cima — é assim que toda
                   grade de vídeo funciona, e o conteúdo aqui é vídeo. */
                <ul className="grid grid-cols-2 gap-2.5 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 2xl:grid-cols-6">
                    {publicacoes.data.map((publicacao) => {
                        // O primeiro destino com prova manda: é o link que a pessoa quer.
                        const comProva = publicacao.destinos.find((d) => d.url);
                        /*
                         * ⚠️ `removido` conta junto: o cartão precisa chamar
                         * atenção nos dois casos. O que muda é a FRASE — "falhou"
                         * é não subiu; "saiu do ar" é subiu e a rede tirou
                         * (DEC-148).
                         */
                        const falhou = publicacao.destinos.some((d) => d.status === 'falhou' || d.status === 'removido');
                        const baixa = publicacao.destinos.some((d) => d.qualidade === 'sd');
                        const comErro = publicacao.destinos.find((d) => d.erro);

                        return (
                            <li key={publicacao.ulid}>
                                <article className="border-border bg-card relative flex aspect-square overflow-hidden rounded-xl border">
                                    <Miniatura
                                        url={publicacao.miniatura}
                                        tipo="video"
                                        alt={publicacao.midia}
                                        className="absolute inset-0 size-full rounded-none"
                                    />

                                    {/* Escurecido só embaixo: o texto precisa de contraste,
                                        e cobrir a imagem inteira apagaria justamente o que
                                        faz reconhecer o vídeo. */}
                                    <div className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 via-black/55 to-transparent p-2.5 pt-8">
                                        <p className="truncate text-xs font-medium text-white" title={publicacao.titulo || publicacao.midia}>
                                            {publicacao.titulo || publicacao.midia}
                                        </p>

                                        <div className="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-1">
                                            {publicacao.destinos.map((destino) => (
                                                <span key={destino.ulid} title={`${destino.plataformaRotulo} · ${destino.statusRotulo}`}>
                                                    <MarcaDaRede rede={destino.plataforma} className="size-4 rounded-md" />
                                                </span>
                                            ))}

                                            {/* ⛔ **O estado da PUBLICAÇÃO, não o do primeiro
                                                destino.** A cor sempre olhou todos os destinos e
                                                o texto olhava só o primeiro: com três redes onde
                                                a primeira subiu e a terceira falhou, o cartão
                                                escrevia "No ar" **em vermelho**. Num produto que
                                                se vende por não mentir sobre o que aconteceu, é o
                                                pior lugar possível para uma contradição.

                                                O agregado já vinha pronto do servidor
                                                ("Publicada com falhas") e a tela não usava.

                                                ⚠️ Ele é mais comprido que "No ar" e às vezes
                                                cai para a linha de baixo — o `flex-wrap` cuida
                                                disso. Encurtar a palavra para caber seria trocar
                                                a verdade por espaço. */}
                                            <span
                                                className="text-[0.8125rem] text-white/85"
                                                style={falhou ? { color: 'var(--saude-erro)' } : undefined}
                                            >
                                                {publicacao.statusRotulo}
                                            </span>

                                            {/* ⚠️ Nada de `0.625rem` aqui: com a fonte base
                                                fluida de 13px isso virava **8,1px** — e o que
                                                estava desse tamanho era justamente a palavra que
                                                o produto existe para dizer. */}
                                            <span className="ml-auto text-[0.8125rem] text-white/65">{quando(publicacao.criadaEm)}</span>
                                        </div>
                                    </div>

                                    {/* ⭐ A PROVA. Cobre o cartão inteiro para virar o alvo
                                        natural do clique — só existe quando relemos o post. */}
                                    {comProva?.url && (
                                        <a
                                            href={comProva.url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            title={comProva.notaDaProva ?? 'Ver o post na rede'}
                                            className="focus-visible:ring-ring absolute inset-0 focus-visible:ring-2 focus-visible:outline-none"
                                        >
                                            {/* ⛔ Rede que não deixa reler ganha o ponto de
                                                ressalva (DEC-106): o link existe, a conferência
                                                não aconteceu, e a tela não finge que sim. */}
                                            <span className="absolute top-2 right-2 flex items-center gap-1 rounded-md bg-black/55 p-1.5 backdrop-blur-sm">
                                                {comProva.notaDaProva && <span aria-hidden="true" className="text-[0.8125rem] leading-none text-white">*</span>}
                                                <ExternalLink className="size-3 text-white" aria-hidden="true" />
                                            </span>
                                            <span className="sr-only">
                                                Ver o post de {publicacao.titulo || publicacao.midia}
                                                {/* ⚠️ A ressalva vai TAMBÉM aqui: quem usa leitor
                                                    de tela não vê o asterisco nem alcança o
                                                    `title`, e é a mesma informação. */}
                                                {comProva.notaDaProva ? `. ${comProva.notaDaProva}` : ''}
                                            </span>
                                        </a>
                                    )}

                                    {/* ⭐ A rede admitindo que degradou o vídeo.
                                        ⚠️ Estava em `0.5625rem` — **7,3px** na fonte base do
                                        painel. Nenhum concorrente mostra esta informação, e a
                                        nossa estava no menor texto da tela inteira. */}
                                    {baixa && (
                                        <span
                                            className="pointer-events-none absolute top-2 left-2 rounded-md px-1.5 py-0.5 text-[0.8125rem] font-medium text-white backdrop-blur-sm"
                                            style={{ background: 'color-mix(in oklab, var(--saude-atencao) 75%, transparent)' }}
                                            title="Enviamos em alta. A rede informou que a versão publicada está em baixa qualidade."
                                        >
                                            entregue em baixa
                                        </span>
                                    )}
                                </article>

                                {/* ⭐ O CONTADOR AO LADO DA PROVA — e fora do
                                    cartão, porque o cartão inteiro já é o link
                                    do post e nada aqui é clicável.

                                    ⚠️ Uma linha por conta que já foi lida. Sem
                                    leitura, nada aparece: traço ou zero seriam
                                    afirmações sobre um número que ninguém foi
                                    buscar. */}
                                {publicacao.destinos
                                    .filter((d) => d.metricasLidas)
                                    .map((destino) => (
                                        <ContadoresDoPost key={destino.ulid} destino={destino} />
                                    ))}

                                {/* ⭐ Republicar deixa de ser refazer tudo: abre o
                                    compositor já preenchido, faltando marcar a rede.
                                    Só enquanto o arquivo estiver aqui (DEC-55). */}
                                {publicacao.podeRepublicar && (
                                    <Button asChild variant="ghost" size="sm" className="mt-1 h-7 w-full px-2 text-xs">
                                        <Link href={route('publicar.de-novo', publicacao.ulid)} preserveScroll>
                                            <Plus className="mr-1 size-3" aria-hidden="true" />
                                            Publicar em outra rede
                                        </Link>
                                    </Button>
                                )}

                                {/* Fora do cartão: botão não pode viver dentro de um link,
                                    e falha é coisa que se resolve olhando de perto. */}
                                {publicacao.destinos
                                    .filter((d) => d.podeReprocessar)
                                    .map((destino) => (
                                        <Button
                                            key={destino.ulid}
                                            variant="ghost"
                                            size="sm"
                                            className="mt-1 h-7 w-full px-2 text-xs"
                                            onClick={() => router.post(route('publicacoes.reprocessar', destino.ulid), {}, { preserveScroll: true })}
                                        >
                                            <RotateCw className="mr-1 size-3" aria-hidden="true" />
                                            Tentar de novo
                                        </Button>
                                    ))}

                                {comErro && <p className="text-muted-foreground mt-1 line-clamp-2 text-[0.8125rem] leading-snug">{comErro.erro}</p>}
                            </li>
                        );
                    })}
                </ul>
            )}

            {publicacoes.last_page > 1 && (
                <nav aria-label="Páginas" className="mt-6 flex flex-wrap gap-1.5">
                    {publicacoes.links.map((link, indice) => (
                        <Link
                            key={indice}
                            href={link.url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                link.active ? 'border-[color:var(--accent)] font-medium' : 'border-border text-muted-foreground'
                            } ${!link.url ? 'pointer-events-none opacity-40' : ''}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </nav>
            )}
            {compositor && <Compositor {...compositor} aoFechar={fecharCompositor} />}
        </LayoutPainel>
    );
}

/**
 * ⭐ Os posts de UMA rede, comparados na medida daquela rede (DEC-94).
 *
 * ⚠️ **Todas as barras na mesma medida** — a do post que mais teve. É isso que
 * torna a comparação legível: sem a medida compartilhada, cada barra se escala
 * por si e o post de 5 visualizações desenha do mesmo tamanho do de 5 mil, num
 * gráfico que mente por construção.
 *
 * ⛔ **Nada aqui é a soma de duas redes.** Visualização do YouTube e curtida do
 * Bluesky não são a mesma grandeza, e empilhá-las produziria um total que não
 * existe em lugar nenhum.
 *
 * ⚠️ O desenho vem do mesmo contrato da barra da Visão geral (`FatiaDaBarra`),
 * então trocar CSS puro por uma biblioteca de gráfico depois continua sendo
 * mexer em **um arquivo só** (DEC-92).
 */
function ComparativoDoPost({ comparativo }: { comparativo: ComparativoDeRede }) {
    const maximo = Math.max(...comparativo.barras.map((b) => b.valor));

    return (
        <section className="mb-6">
            <TituloDeSecao
                titulo={`Seus posts no ${comparativo.redeRotulo}`}
                apoio={`por ${comparativo.medida}`}
                descricao={`Todas as barras estão na mesma medida — dá para comparar um post com o outro.`}
            />

            {/* ⭐ Zero em tudo não é gráfico vazio: é um estado, e ele tem
                frase. No YouTube isto é o esperado hoje — sem a explicação, a
                tela pareceria quebrada justamente quando está certa. */}
            {comparativo.tudoZerado ? (
                <div className="border-border bg-card rounded-xl border p-4 sm:p-5">
                    <p className="text-sm font-medium">Nenhum destes posts recebeu {comparativo.medida} ainda.</p>
                    {comparativo.notaDeZero && <p className="text-muted-foreground mt-1 text-sm text-balance">{comparativo.notaDeZero}</p>}
                </div>
            ) : (
                <ul className="border-border bg-card space-y-3 rounded-xl border p-4 sm:p-5">
                    {comparativo.barras.map((barra) => (
                        <li key={barra.ulid}>
                            <div className="mb-1 flex items-baseline justify-between gap-3">
                                {/* ⚠️ O link é o título, não o gráfico: barra de
                                    10px não é alvo de toque, e no celular não há
                                    `hover` para avisar antes do clique (DEC-91). */}
                                {barra.url ? (
                                    <a
                                        href={barra.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="focus-visible:ring-ring truncate rounded-md text-[0.8125rem] hover:underline focus-visible:ring-2 focus-visible:outline-none"
                                        title={`Ver «${barra.titulo}» na rede`}
                                    >
                                        {barra.titulo}
                                    </a>
                                ) : (
                                    <span className="truncate text-[0.8125rem]" title={barra.titulo}>
                                        {barra.titulo}
                                    </span>
                                )}

                                <span className="shrink-0 text-[0.8125rem] font-medium tabular-nums">{barra.valor.toLocaleString('pt-BR')}</span>
                            </div>

                            <BarraDeEntrega
                                fatias={[{ chave: barra.ulid, rotulo: comparativo.medida, valor: barra.valor, cor: 'var(--chart-1)' }]}
                                maximo={maximo}
                                corDoVazio="var(--trilho)"
                                espessura={8}
                                rotuloAcessivel={`${barra.titulo}: ${barra.valor} ${comparativo.medida}`}
                            />
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

/**
 * Os contadores de UM post — e só os que aquela rede publica (DEC-94).
 *
 * ⛔ **Campo `null` não desenha nada.** Não vira `0`, não vira traço, não vira
 * espaço reservado. No Bluesky visualização não existe no protocolo: um zero ali
 * afirmaria que ninguém viu, quando o certo é que ninguém conta — e é
 * exatamente o tipo de número inventado que este produto existe para não
 * mostrar (DEC-95).
 *
 * ⚠️ A frase que explica a ausência mora na janela da rede, dita **uma vez**.
 * Repetir "o Bluesky não conta visualizações" embaixo de cada um de quarenta
 * cartões transformaria a honestidade em ruído.
 */
function ContadoresDoPost({ destino }: { destino: DestinoDaLista }) {
    const contadores = [
        { chave: 'visualizacoes', Icone: Eye, valor: destino.visualizacoes, rotulo: 'visualizações' },
        { chave: 'curtidas', Icone: Heart, valor: destino.curtidas, rotulo: 'curtidas' },
        { chave: 'comentarios', Icone: MessageCircle, valor: destino.comentarios, rotulo: 'comentários' },
        { chave: 'compartilhamentos', Icone: Repeat2, valor: destino.compartilhamentos, rotulo: 'compartilhamentos' },
    ].filter((c) => c.valor !== null);

    if (contadores.length === 0) {
        return null;
    }

    /*
     * ⚠️ A data da RECONFERÊNCIA entra aqui (DEC-145): "no ar" sem prazo é
     * afirmação que envelhece em silêncio, e era o buraco que ela veio tapar.
     */
    const explicacao = [
        destino.plataformaRotulo,
        destino.conferidoEm ? `no ar · conferido ${destino.conferidoEm}` : null,
        destino.metricasLidas,
        destino.notaDeMetrica,
    ]
        .filter(Boolean)
        .join(' · ');

    return (
        <div className="mt-1 px-0.5">
            {/* ⚠️ `0.8125rem` é o piso do projeto: com a fonte base fluida de
                13px, qualquer coisa abaixo disso desce de 9px — e o que estava
                nesse tamanho era justamente o número que a pessoa veio ver. */}
            <p className="text-muted-foreground flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[0.8125rem]" title={explicacao}>
                <MarcaDaRede rede={destino.plataforma} className="size-3 rounded-sm" />

                {contadores.map(({ chave, Icone, valor, rotulo }) => (
                    <span key={chave} className="inline-flex items-center gap-1 tabular-nums">
                        <Icone className="size-3 shrink-0" aria-hidden="true" />
                        {valor!.toLocaleString('pt-BR')}
                        <span className="sr-only">{rotulo}</span>
                    </span>
                ))}
            </p>

            {/* ⭐ A comparação honesta (DEC-147): esta rede contra ELA MESMA.
                Só aparece com base suficiente — sem ela, o servidor manda
                `null` e aqui não se inventa tendência. */}
            {destino.contraMedia && (
                <p className="text-muted-foreground text-[0.8125rem] leading-snug">{destino.contraMedia}</p>
            )}
        </div>
    );
}

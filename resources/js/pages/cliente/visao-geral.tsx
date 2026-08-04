import { Head, Link, usePage } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, Check, ExternalLink } from 'lucide-react';

import CabecalhoDePagina from '@/components/cabecalho-de-pagina';
import MarcaDaRede from '@/components/conexao/marca-da-rede';
import PainelDeRedes, { type Rede } from '@/components/conexao/painel-de-redes';
import Miniatura from '@/components/midia/miniatura';
import Quadro from '@/components/quadro';
import TituloDeSecao from '@/components/titulo-de-secao';
import { useAtualizacaoViva } from '@/hooks/use-atualizacao-viva';
import LayoutPainel from '@/layouts/painel';
import { type DadosCompartilhados } from '@/types';

interface DestinoResumido {
    plataforma: string;
    status: string;
    url: string | null;
}

interface Publicacao {
    ulid: string;
    titulo: string;
    miniatura: string | null;
    quando: string | null;
    statusRotulo: string;
    destinos: DestinoResumido[];
}

interface Pendencia {
    tom: 'erro' | 'atencao';
    texto: string;
    acao: string;
    url: string;
}

interface Passo {
    titulo: string;
    texto: string;
    feito: boolean;
    url: string;
}

interface Props {
    numeros: { noAr: number; andando: number; falharam: number };
    ultimas: Publicacao[];
    pendencias: Pendencia[];
    primeirosPassos: Passo[];
    /** ⭐ Conexões deixou de ser tela (DEC-63): as redes moram aqui. */
    redes: Rede[];
    totalConectado: number;
}

const quando = (iso: string | null) => (iso ? new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) : '');

/**
 * A porta de entrada — e a única pergunta que ela responde.
 *
 * ⭐ **A ordem é a resposta**, de cima para baixo: *o que espera você* → *como
 * está* → *onde você publica* → *o que você publicou*. Quem abre o painel quer
 * saber o que aconteceu enquanto não estava olhando, e cada seção responde uma
 * parte disso — ou não deveria estar aqui.
 *
 * ⚠️ **A forma padrão é o quadrado.** Retângulo aparece só onde ele é a forma
 * certa: aviso com texto corrido e lista de passos. Faixa larga com um número
 * dentro desperdiça a linha inteira e faz o olho varrer da esquerda à direita
 * para ler três dígitos.
 */
export default function VisaoGeral({ numeros, ultimas, pendencias, primeirosPassos, redes, totalConectado }: Props) {
    const { auth } = usePage<DadosCompartilhados>().props;
    const primeiroNome = auth.usuario?.nome.split(' ')[0] ?? '';

    // O motor é assíncrono: os números mudam sozinhos enquanto há envio em curso.
    useAtualizacaoViva({
        ativo: numeros.andando > 0,
        propriedades: ['numeros', 'ultimas', 'pendencias', 'redes'],
    });

    // Enquanto a pessoa não tiver publicado, ensinar vale mais que resumir.
    const comecando = primeirosPassos.some((passo) => !passo.feito);

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
                                    <Link
                                        href={pendencia.url}
                                        className="inline-flex items-center gap-1 text-xs font-medium text-[color:var(--accent)] hover:underline"
                                    >
                                        {pendencia.acao}
                                        <ArrowRight className="size-3" aria-hidden="true" />
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                )}

                {/* ─── COMO ESTÁ ────────────────────────────────────────────── */}
                <section>
                    <TituloDeSecao titulo="Como está" apoio="contando o que já foi conferido na rede" />

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
                        {numeros.falharam > 0 && (
                            <li>
                                <Numero valor={numeros.falharam} rotulo="não subiram" apoio="dá para tentar" cor="var(--saude-erro)" />
                            </li>
                        )}
                    </ul>
                </section>

                {/* ─── ONDE VOCÊ PUBLICA ────────────────────────────────────── */}
                {/* ⚠️ Vem ANTES das publicações: sem conta conectada não há
                    publicação nenhuma, e o semáforo do token (DEC-32) precisa
                    ser visto antes de a conexão quebrar. */}
                <PainelDeRedes redes={redes} totalConectado={totalConectado} />

                {/* ─── PRIMEIROS PASSOS ─────────────────────────────────────── */}
                {/* Some sozinho quando tudo está feito: uma lista que completa
                    mostra progresso; um cartaz fixo mostra que ninguém olha.

                    Retângulo de novo pela mesma razão: é texto explicando. */}
                {comecando && (
                    <section>
                        <TituloDeSecao titulo="Primeiros passos" />

                        <ol className="border-border bg-card divide-border divide-y rounded-xl border">
                            {primeirosPassos.map((passo) => (
                                <li key={passo.titulo} className="flex gap-3 p-4">
                                    <span
                                        aria-hidden="true"
                                        className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border"
                                        style={passo.feito ? { background: 'var(--saude-ok)', borderColor: 'var(--saude-ok)' } : undefined}
                                    >
                                        {passo.feito && <Check className="size-3 text-white" />}
                                    </span>

                                    <span className={`text-sm ${passo.feito ? 'text-muted-foreground line-through' : ''}`}>
                                        {passo.feito ? (
                                            <strong className="font-medium">{passo.titulo}</strong>
                                        ) : (
                                            <Link href={passo.url} className="font-medium text-[color:var(--accent)] hover:underline">
                                                {passo.titulo}
                                            </Link>
                                        )}
                                        <span className="text-muted-foreground block text-xs">{passo.texto}</span>
                                    </span>
                                </li>
                            ))}
                        </ol>
                    </section>
                )}

                {/* ─── O QUE VOCÊ PUBLICOU ──────────────────────────────────── */}
                <section>
                    <TituloDeSecao
                        titulo="Últimas publicações"
                        apoio={
                            ultimas.length > 0 && (
                                <Link href={route('publicacoes')} className="hover:text-foreground">
                                    ver todas
                                </Link>
                            )
                        }
                    />

                    {ultimas.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            {comecando
                                ? 'Assim que você publicar, o resultado aparece aqui — com o link do post como prova.'
                                : 'Tudo pronto. Quando você publicar, o resultado aparece aqui.'}
                        </p>
                    ) : (
                        /* ⭐ Cartão QUADRADO com a miniatura preenchendo o fundo,
                           igual à tela de Publicações: o olho varre uma grade
                           muito mais rápido que uma lista de faixas largas, e o
                           conteúdo aqui é vídeo — grade de vídeo é assim. */
                        <ul className="grid grid-cols-2 gap-2.5 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                            {ultimas.map((publicacao) => {
                                // O primeiro destino com prova manda: é o link que a pessoa quer.
                                const comProva = publicacao.destinos.find((d) => d.url);

                                return (
                                    <li key={publicacao.ulid}>
                                        <article className="border-border bg-card relative aspect-square overflow-hidden rounded-xl border">
                                            <Miniatura
                                                url={publicacao.miniatura}
                                                tipo="video"
                                                alt={publicacao.titulo}
                                                className="absolute inset-0 size-full rounded-none"
                                            />

                                            {/* Escurecido só embaixo: cobrir a imagem inteira
                                                apagaria justamente o que faz reconhecer o vídeo. */}
                                            <div className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 via-black/55 to-transparent p-2.5 pt-8">
                                                <p className="truncate text-xs font-medium text-white" title={publicacao.titulo}>
                                                    {publicacao.titulo}
                                                </p>

                                                <div className="mt-1 flex items-center gap-x-1.5">
                                                    {/* Um selo por rede: apagado enquanto não há prova. */}
                                                    {publicacao.destinos.map((destino, i) => (
                                                        <span
                                                            key={i}
                                                            className={destino.url ? undefined : 'opacity-40'}
                                                            title={destino.url ? 'No ar' : publicacao.statusRotulo}
                                                        >
                                                            <MarcaDaRede rede={destino.plataforma} className="size-4 rounded" />
                                                        </span>
                                                    ))}

                                                    <span className="ml-auto text-[0.625rem] text-white/65">{quando(publicacao.quando)}</span>
                                                </div>
                                            </div>

                                            {/* ⭐ A PROVA. Cobre o cartão inteiro para virar o
                                                alvo natural do clique — e só existe depois de
                                                relermos o post na rede. */}
                                            {comProva?.url && (
                                                <a
                                                    href={comProva.url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    title="Ver o post na rede"
                                                    className="focus-visible:ring-ring absolute inset-0 focus-visible:ring-2 focus-visible:outline-none"
                                                >
                                                    <span className="absolute top-2 right-2 rounded-full bg-black/55 p-1.5 backdrop-blur-sm">
                                                        <ExternalLink className="size-3 text-white" aria-hidden="true" />
                                                    </span>
                                                    <span className="sr-only">Ver o post de {publicacao.titulo}</span>
                                                </a>
                                            )}
                                        </article>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </section>
            </div>
        </LayoutPainel>
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

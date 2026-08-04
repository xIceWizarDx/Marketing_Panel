import { Head, Link, usePage } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, Check, ExternalLink, Info } from 'lucide-react';

import CabecalhoDePagina from '@/components/cabecalho-de-pagina';
import Miniatura from '@/components/midia/miniatura';
import MarcaDaRede from '@/components/conexao/marca-da-rede';
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
}

const quando = (iso: string | null) =>
    iso ? new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) : '';

export default function VisaoGeral({ numeros, ultimas, pendencias, primeirosPassos }: Props) {
    const { auth } = usePage<DadosCompartilhados>().props;
    const primeiroNome = auth.usuario?.nome.split(' ')[0] ?? '';

    // O motor é assíncrono: os números mudam sozinhos enquanto há envio em curso.
    useAtualizacaoViva({
        ativo: numeros.andando > 0,
        propriedades: ['numeros', 'ultimas', 'pendencias'],
    });

    // Enquanto a pessoa não tiver publicado, ensinar vale mais que resumir.
    const comecando = primeirosPassos.some((passo) => !passo.feito);

    return (
        <LayoutPainel migalhas={[{ titulo: 'Visão geral', url: '/painel' }]}>
            <Head title="Visão geral" />

            <CabecalhoDePagina titulo={`Olá, ${primeiroNome}`} descricao="Aqui é onde você acompanha suas publicações." />

            {/* ⭐ O que está esperando VOCÊ. Some quando não há nada: um bloco que
                vive dizendo "está tudo bem" treina a pessoa a ignorá-lo, e no dia
                em que houver problema de verdade ela não vai olhar. */}
            {pendencias.length > 0 && (
                <ul className="mb-5 space-y-2">
                    {pendencias.map((pendencia, i) => {
                        const cor = pendencia.tom === 'erro' ? 'var(--saude-erro)' : 'var(--saude-atencao)';

                        return (
                            <li
                                key={i}
                                className="border-border bg-card flex flex-wrap items-center gap-x-3 gap-y-1.5 rounded-lg border p-3 text-sm"
                                style={{ borderLeft: `3px solid ${cor}` }}
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

            {/* Os três lados do mesmo fato — a falha do lado do acerto. */}
            <div className="border-border bg-card mb-5 flex flex-wrap gap-x-8 gap-y-3 rounded-xl border p-4">
                <Numero valor={numeros.noAr} rotulo="confirmados no ar" cor="var(--saude-ok)" />
                {numeros.andando > 0 && <Numero valor={numeros.andando} rotulo="a caminho" cor="var(--saude-atencao)" />}
                {numeros.falharam > 0 && <Numero valor={numeros.falharam} rotulo="não subiram" cor="var(--saude-erro)" />}
            </div>

            {comecando && (
                <div className="border-border bg-card mb-5 rounded-xl border p-5">
                    <h2 className="text-base font-medium">Primeiros passos</h2>

                    <ol className="mt-4 space-y-3">
                        {primeirosPassos.map((passo) => (
                            <li key={passo.titulo} className="flex gap-3">
                                <span
                                    aria-hidden="true"
                                    className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border"
                                    style={
                                        passo.feito
                                            ? { background: 'var(--saude-ok)', borderColor: 'var(--saude-ok)' }
                                            : undefined
                                    }
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
                </div>
            )}

            {ultimas.length > 0 && (
                <section>
                    <div className="mb-2.5 flex items-center justify-between">
                        <h2 className="text-sm font-medium">Últimas publicações</h2>
                        <Link href={route('publicacoes')} className="text-muted-foreground text-xs hover:underline">
                            ver todas
                        </Link>
                    </div>

                    <ul className="space-y-2">
                        {ultimas.map((publicacao) => (
                            <li key={publicacao.ulid} className="border-border bg-card flex items-center gap-3 rounded-lg border p-3">
                                <Miniatura url={publicacao.miniatura} tipo="video" alt={publicacao.titulo} className="size-11" />

                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-medium">{publicacao.titulo}</p>
                                    <p className="text-muted-foreground text-xs">{quando(publicacao.quando)}</p>
                                </div>

                                {/* Um selo por rede: verde só quando existe prova. */}
                                <ul className="flex shrink-0 items-center gap-1.5">
                                    {publicacao.destinos.map((destino, i) => (
                                        <li key={i} title={destino.url ? 'No ar — clique para ver' : publicacao.statusRotulo}>
                                            {destino.url ? (
                                                <a
                                                    href={destino.url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="relative block"
                                                >
                                                    <MarcaDaRede rede={destino.plataforma} className="size-6" />
                                                    <ExternalLink
                                                        className="absolute -right-1 -bottom-1 size-3 text-[color:var(--saude-ok)]"
                                                        aria-hidden="true"
                                                    />
                                                </a>
                                            ) : (
                                                <span className="block opacity-40">
                                                    <MarcaDaRede rede={destino.plataforma} className="size-6" />
                                                </span>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {ultimas.length === 0 && !comecando && (
                <p className="text-muted-foreground text-sm">
                    <Info className="mr-1.5 inline size-4 align-text-bottom" aria-hidden="true" />
                    Tudo pronto. Quando você publicar, o resultado aparece aqui.
                </p>
            )}
        </LayoutPainel>
    );
}

function Numero({ valor, rotulo, cor }: { valor: number; rotulo: string; cor: string }) {
    return (
        <div>
            <p className="text-2xl leading-none font-semibold tabular-nums" style={{ color: cor }}>
                {valor}
            </p>
            <p className="text-muted-foreground mt-1 text-xs">{rotulo}</p>
        </div>
    );
}

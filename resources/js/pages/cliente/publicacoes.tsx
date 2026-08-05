import { Head, Link, router } from '@inertiajs/react';
import { ExternalLink, Plus, RotateCw, Send } from 'lucide-react';

import CabecalhoDePagina from '@/components/cabecalho-de-pagina';
import MarcaDaRede from '@/components/conexao/marca-da-rede';
import Miniatura from '@/components/midia/miniatura';
import Compositor, { type DadosDoCompositor } from '@/components/publicacao/compositor';
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
 * Um destino que ainda não terminou: nem publicado, nem falhado.
 *
 * ⚠️ Por exclusão, de propósito. Listar os estados intermediários um a um faria
 * um estado novo ser tratado como "pronto" em silêncio, e a tela pararia de
 * atualizar bem no caso que ninguém previu.
 */
const emAndamento = (status: string) => status !== 'publicado' && status !== 'falhou';

const ABAS = [
    { chave: 'tudo', rotulo: 'Tudo' },
    { chave: 'andando', rotulo: 'Em andamento' },
    { chave: 'no_ar', rotulo: 'No ar' },
    { chave: 'falharam', rotulo: 'Não subiram' },
] as const;

export default function Publicacoes({
    publicacoes,
    aba,
    contagem,
    compositor,
}: {
    publicacoes: Paginado<PublicacaoDaLista>;
    aba: string;
    contagem: Record<string, number>;
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
                descricao="O que você enviou, e a prova de que subiu. O link só aparece depois que confirmamos o post na própria rede."
                acoes={
                    <Button asChild size="sm">
                        <Link href={route('publicar')} preserveScroll>
                            <Send className="mr-1.5 size-4" aria-hidden="true" />
                            Publicar
                        </Link>
                    </Button>
                }
            />

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
                        const falhou = publicacao.destinos.some((d) => d.status === 'falhou');
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

                                            <span
                                                className="text-[0.625rem] text-white/85"
                                                style={falhou ? { color: 'var(--saude-erro)' } : undefined}
                                            >
                                                {publicacao.destinos[0]?.statusRotulo}
                                            </span>

                                            <span className="ml-auto text-[0.625rem] text-white/65">{quando(publicacao.criadaEm)}</span>
                                        </div>
                                    </div>

                                    {/* ⭐ A PROVA. Cobre o cartão inteiro para virar o alvo
                                        natural do clique — só existe quando relemos o post. */}
                                    {comProva?.url && (
                                        <a
                                            href={comProva.url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            title="Ver o post na rede"
                                            className="focus-visible:ring-ring absolute inset-0 focus-visible:ring-2 focus-visible:outline-none"
                                        >
                                            <span className="absolute top-2 right-2 rounded-md bg-black/55 p-1.5 backdrop-blur-sm">
                                                <ExternalLink className="size-3 text-white" aria-hidden="true" />
                                            </span>
                                            <span className="sr-only">Ver o post de {publicacao.titulo || publicacao.midia}</span>
                                        </a>
                                    )}

                                    {/* ⭐ A rede admitindo que degradou o vídeo. */}
                                    {baixa && (
                                        <span
                                            className="pointer-events-none absolute top-2 left-2 rounded-md px-1.5 py-0.5 text-[0.5625rem] font-medium text-white backdrop-blur-sm"
                                            style={{ background: 'color-mix(in oklab, var(--saude-atencao) 75%, transparent)' }}
                                            title="Enviamos em alta. A rede informou que a versão publicada está em baixa qualidade."
                                        >
                                            entregue em baixa
                                        </span>
                                    )}
                                </article>

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

                                {comErro && <p className="text-muted-foreground mt-1 line-clamp-2 text-[0.6875rem] leading-snug">{comErro.erro}</p>}
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

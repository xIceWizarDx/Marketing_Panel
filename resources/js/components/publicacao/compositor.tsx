import { Link, useForm } from '@inertiajs/react';
import { Check, LoaderCircle, Play, Send, X } from 'lucide-react';
import { FormEventHandler, useEffect, useMemo, useState } from 'react';

import MarcaDaRede from '@/components/conexao/marca-da-rede';
import ErroDeCampo from '@/components/erro-de-campo';
import EnviarMidia from '@/components/midia/enviar-midia';
import Miniatura from '@/components/midia/miniatura';
import Previa from '@/components/midia/previa';
import { IconeDoNivel } from '@/components/midia/selo-laudo';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { contar, limiteMaisApertado, type LimiteDaRede } from '@/lib/medida-de-texto';
import { cn } from '@/lib/utils';
import { type Laudo } from '@/types';

/** O arquivo desta composição — o único que o compositor conhece. */
interface ArquivoEnviado {
    ulid: string;
    nome: string;
    tipo: 'video' | 'imagem';
    miniatura: string | null;
    arquivo: string | null;
    duracao: string | null;
    tamanho: string;
    vertical: boolean;
    laudo: Laudo | null;
}

interface ContaDaLista {
    ulid: string;
    nome: string;
    plataforma: string;
    plataformaRotulo: string;
    podePublicar: boolean;
    statusRotulo: string;
}

export interface DadosDoCompositor {
    /** O arquivo desta sessão — chega depois do envio, nunca antes (DEC-60). */
    midiaEnviada: ArquivoEnviado | null;
    contas: ContaDaLista[];
    limites: Record<string, LimiteDaRede>;
    /** O que o servidor aguenta de fato — nunca o que o produto gostaria. */
    tamanhoMaximoMb: number;
    /**
     * Vem preenchido ao republicar — **só o texto** (DEC-61).
     *
     * O vídeo não vem junto porque não existe mais: ele saiu quando a publicação
     * anterior terminou.
     */
    inicial?: {
        titulo: string;
        legenda: string;
        hashtags: string[];
        /** Onde já foi publicado — vem desmarcado, com aviso. */
        jaPublicadoEm: string[];
    };
}

/**
 * ⭐ Publicar deixou de ser uma tela e virou uma AÇÃO, aberta por cima da lista.
 *
 * ⚠️ Ele abre por uma rota de verdade (`/publicar`), não por estado de memória.
 * Modal comum perde tudo se a página recarregar — e perder o que a pessoa
 * escreveu é justamente o defeito que este projeto cataloga como U-9.
 *
 * **O desenho tem três decisões e um veredito.** *O quê*, *o que dizer* e *para
 * onde* ficam numa coluna; o que cada rede fará com o arquivo fica ao lado. A
 * ação vive num rodapé que não rola — botão que exige rolar até o fim para ser
 * encontrado é botão escondido, e é justamente o que a pessoa veio clicar.
 */
export default function Compositor({
    midiaEnviada,
    contas,
    limites,
    tamanhoMaximoMb,
    inicial,
    aoFechar,
}: DadosDoCompositor & { aoFechar: () => void }) {
    const { data, setData, post, processing, errors } = useForm<{
        midia: string;
        contas: string[];
        titulo: string;
        legenda: string;
        hashtags: string[];
    }>({
        midia: '',
        // ⚠️ Contas SEMPRE vazias, mesmo ao republicar: marcar sozinho onde já
        // foi publicado repetiria o post, e publicação não tem desfazer.
        contas: [],
        titulo: inicial?.titulo ?? '',
        legenda: inicial?.legenda ?? '',
        hashtags: inicial?.hashtags ?? [],
    });

    const [aVer, setAVer] = useState<ArquivoEnviado | null>(null);

    // O arquivo é o desta sessão: só existe depois que a pessoa enviou.
    const escolhida = data.midia && midiaEnviada?.ulid === data.midia ? midiaEnviada : undefined;

    // As redes que a pessoa escolheu — é o que decide qual limite vale.
    const redesEscolhidas = useMemo(
        () => [...new Set(contas.filter((c) => data.contas.includes(c.ulid)).map((c) => c.plataforma))],
        [contas, data.contas],
    );

    const limiteDoTitulo = limiteMaisApertado(limites, redesEscolhidas, 'titulo');
    const limiteDaLegenda = limiteMaisApertado(limites, redesEscolhidas, 'legenda');

    const textoFinal = useMemo(
        () => [data.legenda, ...data.hashtags.map((t) => `#${t}`)].filter(Boolean).join(' ').trim(),
        [data.legenda, data.hashtags],
    );

    const alternarConta = (ulid: string) =>
        setData('contas', data.contas.includes(ulid) ? data.contas.filter((c) => c !== ulid) : [...data.contas, ulid]);

    const enviar: FormEventHandler = (evento) => {
        evento.preventDefault();
        post(route('publicar.enviar'));
    };

    const conectadas = contas.filter((c) => c.podePublicar);

    /**
     * O que impede publicar agora — ou `null` quando está tudo pronto.
     *
     * Vira o texto do próprio botão: dizer o que falta no lugar onde a pessoa
     * vai clicar é mais direto que deixá-la clicar para descobrir.
     */
    const oQueFalta = !data.midia ? 'Escolha o que publicar' : data.contas.length === 0 ? 'Escolha ao menos uma conta' : null;

    if (conectadas.length === 0) {
        return (
            <Janela titulo="Publicar" aoFechar={aoFechar}>
                {/* Só falta a CONTA: o arquivo entra aqui dentro, no mesmo
                    gesto, então nunca é ele o impedimento. */}
                <div className="p-6">
                    <h2 className="text-base font-medium">Falta conectar uma rede</h2>
                    <p className="text-muted-foreground mt-1.5 max-w-prose text-sm">
                        Enquanto não houver uma conta conectada, não há para onde publicar. Você autoriza no site da própria rede — sua senha nunca
                        passa por aqui.
                    </p>

                    <Button asChild className="mt-5">
                        <Link href={route('painel')}>Conectar uma rede</Link>
                    </Button>
                </div>
            </Janela>
        );
    }

    return (
        <Janela
            titulo={inicial ? 'Publicar em outra rede' : 'Publicar'}
            descricao={inicial ? 'O texto veio da publicação anterior. Envie o vídeo de novo e escolha as redes.' : undefined}
            aoFechar={aoFechar}
            rodape={
                <>
                    <p className="text-muted-foreground hidden flex-1 text-xs sm:block">
                        Depois de enviar, conferimos na própria rede e guardamos o link como prova.
                    </p>

                    <Button type="submit" form="compositor" className="h-10 w-full sm:w-auto" disabled={processing || !!oQueFalta}>
                        {processing ? (
                            <LoaderCircle className="mr-1.5 size-4 animate-spin" aria-hidden="true" />
                        ) : (
                            <Send className="mr-1.5 size-4" aria-hidden="true" />
                        )}
                        {oQueFalta ?? `Publicar em ${data.contas.length} conta${data.contas.length === 1 ? '' : 's'}`}
                    </Button>
                </>
            }
        >
            <form id="compositor" onSubmit={enviar} className="grid lg:grid-cols-[1fr_18rem]">
                <div className="space-y-6 p-5 lg:p-6">
                    {/* ─── O QUÊ ────────────────────────────────────────────── */}
                    <section>
                        <Rotulo>Vídeo</Rotulo>

                        {/* ⛔ NENHUMA sugestão. Não há lista de vídeos anteriores
                            porque não há acervo (DEC-60): o arquivo sai no instante
                            em que a publicação termina. Quem publica envia o vídeo
                            aqui, agora — e a ausência de biblioteca é a promessa
                            aparecendo na tela. */}
                        {escolhida ? (
                            <div className="mt-2 flex items-start gap-3">
                                <Miniatura url={escolhida.miniatura} tipo={escolhida.tipo} alt={escolhida.nome} className="size-20" />

                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-medium">{escolhida.nome}</p>
                                    <p className="text-muted-foreground text-xs">
                                        {[escolhida.duracao, escolhida.tamanho].filter(Boolean).join(' · ')}
                                    </p>

                                    <div className="mt-1.5 flex flex-wrap items-center gap-x-3 text-xs">
                                        {escolhida.tipo === 'video' && escolhida.arquivo && (
                                            <button
                                                type="button"
                                                onClick={() => setAVer(escolhida)}
                                                className="inline-flex items-center gap-1 font-medium text-[color:var(--accent)] hover:underline"
                                            >
                                                <Play className="size-3" aria-hidden="true" />
                                                ver antes
                                            </button>
                                        )}

                                        {/* Trocar é reenviar: não há lista de onde escolher. */}
                                        <button
                                            type="button"
                                            onClick={() => setData('midia', '')}
                                            className="text-muted-foreground hover:text-foreground"
                                        >
                                            trocar arquivo
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div className="mt-2">
                                <EnviarMidia tamanhoMaximoMb={tamanhoMaximoMb} aoEnviar={(ulid) => setData('midia', ulid)} />
                            </div>
                        )}

                        <ErroDeCampo mensagem={errors.midia} className="mt-2" />
                    </section>

                    {/* ─── O QUE DIZER ──────────────────────────────────────── */}
                    <section className="space-y-3.5">
                        <Rotulo>Texto</Rotulo>

                        <div className="grid gap-1.5">
                            <div className="flex items-baseline justify-between gap-3">
                                <Label htmlFor="titulo">Título</Label>
                                {limiteDoTitulo && (
                                    <span className="text-muted-foreground text-[0.6875rem]">
                                        {contar(data.titulo, 'caracteres')}/{limiteDoTitulo.valor} ·{' '}
                                        <span className="capitalize">{limiteDoTitulo.rede}</span>
                                    </span>
                                )}
                            </div>
                            <Input
                                id="titulo"
                                value={data.titulo}
                                onChange={(e) => setData('titulo', e.target.value)}
                                placeholder="Usado pelo YouTube"
                                // ⚠️ O teto vem da rede escolhida, não de um 255
                                // inventado: o YouTube corta em 100, e deixar
                                // digitar mais é deixar escrever para levar erro.
                                maxLength={limiteDoTitulo?.valor ?? 255}
                            />
                            <ErroDeCampo mensagem={errors.titulo} />
                        </div>

                        <div className="grid gap-1.5">
                            <Label htmlFor="legenda">Legenda</Label>
                            <textarea
                                id="legenda"
                                rows={4}
                                value={data.legenda}
                                onChange={(e) => setData('legenda', e.target.value)}
                                className="border-input bg-input focus-visible:ring-ring w-full resize-y rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none"
                                placeholder="O que você quer dizer com esse vídeo?"
                            />
                            <ErroDeCampo mensagem={errors.legenda} />
                        </div>

                        <div className="grid gap-1.5">
                            <Label htmlFor="hashtags">
                                Hashtags <span className="text-muted-foreground font-normal">(separadas por espaço, sem #)</span>
                            </Label>
                            <Input
                                id="hashtags"
                                value={data.hashtags.join(' ')}
                                onChange={(e) => setData('hashtags', e.target.value.split(/[\s,#]+/).filter(Boolean))}
                                placeholder="corte shorts humor"
                            />
                            <ErroDeCampo mensagem={errors.hashtags} />
                        </div>

                        <ContagemDoTexto texto={textoFinal} limite={limiteDaLegenda} />
                    </section>

                    {/* ─── PARA ONDE ────────────────────────────────────────── */}
                    <section>
                        <Rotulo>Para onde</Rotulo>

                        {/* Etiquetas com a marca da rede: mais rápidas de varrer que
                            linhas de largura inteira, e cabem lado a lado. */}
                        <ul className="mt-2 flex flex-wrap gap-2">
                            {contas.map((conta) => {
                                const marcada = data.contas.includes(conta.ulid);
                                const jaFoi = inicial?.jaPublicadoEm.includes(conta.ulid);

                                return (
                                    <li key={conta.ulid}>
                                        <button
                                            type="button"
                                            disabled={!conta.podePublicar}
                                            onClick={() => alternarConta(conta.ulid)}
                                            aria-pressed={marcada}
                                            className={cn(
                                                'flex items-center gap-2 rounded-full border py-1.5 pr-3 pl-1.5 text-left transition-colors',
                                                'focus-visible:ring-ring focus-visible:ring-2 focus-visible:outline-none',
                                                marcada
                                                    ? 'border-[color:var(--accent)] bg-[color:var(--accent)]/8'
                                                    : 'border-border hover:border-[color:var(--accent)]/50',
                                                !conta.podePublicar && 'cursor-not-allowed opacity-45',
                                            )}
                                        >
                                            <MarcaDaRede rede={conta.plataforma} className="size-6 rounded-full" />

                                            <span className="min-w-0">
                                                <span className="block max-w-[10rem] truncate text-xs font-medium">{conta.nome}</span>
                                                {/* ⚠️ Republicar na mesma conta é engano quase
                                                    sempre — e publicação não tem desfazer. */}
                                                {(jaFoi || !conta.podePublicar) && (
                                                    <span className="text-muted-foreground block text-[0.625rem]">
                                                        {conta.podePublicar ? 'já publicado aqui' : conta.statusRotulo}
                                                    </span>
                                                )}
                                            </span>

                                            {marcada && <Check className="size-3.5 shrink-0 text-[color:var(--accent)]" aria-hidden="true" />}
                                        </button>
                                    </li>
                                );
                            })}
                        </ul>

                        <ErroDeCampo mensagem={errors.contas} className="mt-2" />

                        {/* ⚠️ O aviso tem que estar AQUI, não só na tela de conexões.
                            Quem conectou semana passada já esqueceu, e descobrir que
                            o vídeo subiu privado depois de publicar é exatamente a
                            surpresa que o produto existe para evitar. */}
                        {contas.some((c) => c.plataforma === 'youtube' && data.contas.includes(c.ulid)) && (
                            <p className="text-muted-foreground mt-3 text-xs leading-relaxed">
                                No YouTube o vídeo sobe <strong>privado</strong> — é regra deles enquanto o aplicativo não passa pela auditoria, e
                                vale mesmo escolhendo público. Ele aparece no seu canal e você o torna público pelo YouTube Studio.
                            </p>
                        )}
                    </section>
                </div>

                {/* ─── O VEREDITO ───────────────────────────────────────────── */}
                <aside className="bg-muted/35 border-border border-t p-5 lg:border-t-0 lg:border-l lg:p-6">
                    <Rotulo>Antes de enviar</Rotulo>
                    <PainelDoLaudo escolhida={escolhida} />
                </aside>
            </form>

            {aVer?.arquivo && <Previa url={aVer.arquivo} nome={aVer.nome} aoFechar={() => setAVer(null)} />}
        </Janela>
    );
}

/** Rótulo de seção — discreto de propósito: quem manda é o conteúdo. */
function Rotulo({ children }: { children: React.ReactNode }) {
    return <h2 className="text-muted-foreground text-[0.6875rem] font-semibold tracking-wide uppercase">{children}</h2>;
}

/**
 * ⚠️ Contado como a rede conta, e comparado ao limite da rede ESCOLHIDA.
 *
 * A tela dizia sempre "o Bluesky aceita 300", mesmo publicando só no YouTube — e
 * contava emoji errado, mostrando um número que a rede não usaria.
 */
function ContagemDoTexto({ texto, limite }: { texto: string; limite: ReturnType<typeof limiteMaisApertado> }) {
    const quanto = contar(texto, limite?.medida ?? 'caracteres');
    const teto = limite?.valor;
    const estourou = teto != null && quanto > teto;

    return (
        <p className="text-xs">
            <span className="text-muted-foreground" style={estourou ? { color: 'var(--saude-erro)' } : undefined}>
                {quanto}
                {teto != null && ` de ${teto}`} no texto
            </span>

            {estourou && (
                <span className="mt-0.5 block" style={{ color: 'var(--saude-erro)' }}>
                    Passa do que o <span className="capitalize">{limite?.rede}</span> aceita. Não cortamos por você — reduza antes de enviar.
                </span>
            )}
        </p>
    );
}

/**
 * ⭐ O que cada rede fará com este arquivo, **antes** de enviar (DEC-32/33).
 *
 * Mostra o MOTIVO, não só o veredito: cada achado já traz mensagem e providência
 * escritas no servidor, e exibir só o ícone era jogar fora o trabalho e deixar a
 * pessoa sem saber o que fazer.
 */
function PainelDoLaudo({ escolhida }: { escolhida: ArquivoEnviado | undefined }) {
    if (!escolhida) {
        return <p className="text-muted-foreground mt-3 text-xs">Escolha um arquivo para ver o que cada rede fará com ele.</p>;
    }

    if (!escolhida.laudo?.disponivel) {
        return <p className="text-muted-foreground mt-3 text-xs">Este arquivo não foi analisado.</p>;
    }

    return (
        <>
            {!escolhida.vertical && escolhida.tipo === 'video' && (
                <p className="mt-3 flex gap-2 text-xs">
                    <IconeDoNivel nivel="atencao" className="mt-0.5 size-3.5 shrink-0" />
                    <span>Este vídeo não é vertical. Algumas redes vão cortar as laterais.</span>
                </p>
            )}

            <ul className="mt-3 space-y-2.5">
                {Object.entries(escolhida.laudo.por_rede).map(([rede, achados]) => {
                    const impedem = achados.filter((a) => a.nivel === 'erro');
                    const avisos = achados.filter((a) => a.nivel === 'atencao');
                    const recusa = impedem.length > 0;

                    return (
                        <li key={rede} className="text-xs">
                            <span className="flex items-center gap-2">
                                <IconeDoNivel nivel={recusa ? 'erro' : avisos.length ? 'atencao' : 'ok'} className="size-3.5 shrink-0" />
                                <span className="font-medium capitalize">{rede}</span>
                                <span className="text-muted-foreground">{recusa ? 'não aceita' : 'aceita'}</span>
                            </span>

                            {/* Só o que IMPEDE ou merece atenção. Repetir os "ok"
                                viraria parede de texto e esconderia justamente o
                                que a pessoa precisa ler. */}
                            {[...impedem, ...avisos].map((achado, i) => (
                                <span key={i} className="text-muted-foreground mt-0.5 block pl-[1.375rem] leading-snug">
                                    {achado.mensagem}
                                    {achado.providencia && <span className="block">{achado.providencia}</span>}
                                </span>
                            ))}
                        </li>
                    );
                })}
            </ul>
        </>
    );
}

/**
 * A moldura do compositor.
 *
 * ⚠️ **Tela cheia no celular.** Modal pequeno com sete campos num telefone é
 * pior que página — some o contexto e sobra rolagem dentro de rolagem.
 *
 * O rodapé **não rola**: botão que exige rolar até o fim para ser encontrado é
 * botão escondido, e este é justamente o botão que a pessoa veio clicar.
 *
 * `Esc` fecha, como em qualquer janela. Clicar no fundo **não** fecha: aqui
 * dentro há texto escrito à mão, e fechar por clique perdido custaria caro.
 */
function Janela({
    titulo,
    descricao,
    aoFechar,
    rodape,
    children,
}: {
    titulo: string;
    descricao?: string;
    aoFechar: () => void;
    rodape?: React.ReactNode;
    children: React.ReactNode;
}) {
    useEffect(() => {
        const noTeclado = (evento: KeyboardEvent) => {
            if (evento.key === 'Escape') aoFechar();
        };

        document.addEventListener('keydown', noTeclado);

        return () => document.removeEventListener('keydown', noTeclado);
    }, [aoFechar]);

    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-label={titulo}
            className="fixed inset-0 z-40 flex items-center justify-center bg-black/55 backdrop-blur-[2px] sm:p-6 md:p-8"
        >
            <div className="bg-background flex max-h-full w-full flex-col overflow-hidden shadow-2xl sm:max-w-4xl sm:rounded-2xl">
                <header className="border-border flex shrink-0 items-start gap-3 border-b px-5 py-4 lg:px-6">
                    <div className="min-w-0 flex-1">
                        <h1 className="text-base leading-tight font-semibold">{titulo}</h1>
                        {descricao && <p className="text-muted-foreground mt-0.5 text-xs">{descricao}</p>}
                    </div>

                    <button
                        type="button"
                        onClick={aoFechar}
                        aria-label="Fechar"
                        className="text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:ring-ring -mt-1 -mr-1.5 rounded-lg p-1.5 transition-colors focus-visible:ring-2 focus-visible:outline-none"
                    >
                        <X className="size-4" aria-hidden="true" />
                    </button>
                </header>

                <div className="min-h-0 flex-1 overflow-y-auto">{children}</div>

                {rodape && (
                    <footer className="border-border bg-background flex shrink-0 flex-wrap items-center justify-end gap-3 border-t px-5 py-3.5 lg:px-6">
                        {rodape}
                    </footer>
                )}
            </div>
        </div>
    );
}

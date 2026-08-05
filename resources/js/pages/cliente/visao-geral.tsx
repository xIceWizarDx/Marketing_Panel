import { Head, Link, usePage } from '@inertiajs/react';
import { AlertTriangle, ArrowRight } from 'lucide-react';
import { useState } from 'react';

import CabecalhoDePagina from '@/components/cabecalho-de-pagina';
import PainelDeRedes, { type Rede } from '@/components/conexao/painel-de-redes';
import Quadro from '@/components/quadro';
import TituloDeSecao from '@/components/titulo-de-secao';
import { useAtualizacaoViva } from '@/hooks/use-atualizacao-viva';
import LayoutPainel from '@/layouts/painel';
import { type DadosCompartilhados } from '@/types';

interface Pendencia {
    tom: 'erro' | 'atencao';
    texto: string;
    acao: string;
    /** Navegação de verdade — para outra tela. */
    url: string | null;
    /** Abre o detalhe desta rede aqui mesmo, sem sair da tela. */
    rede: string | null;
}

interface Props {
    numeros: { noAr: number; andando: number; falharam: number };
    pendencias: Pendencia[];
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
export default function VisaoGeral({ numeros, pendencias, redes, totalConectado }: Props) {
    const { auth } = usePage<DadosCompartilhados>().props;
    const primeiroNome = auth.usuario?.nome.split(' ')[0] ?? '';

    // O motor é assíncrono: os números mudam sozinhos enquanto há envio em curso.
    useAtualizacaoViva({
        ativo: numeros.andando > 0,
        propriedades: ['numeros', 'pendencias', 'redes'],
    });

    /*
     * ⛔ O que está aberto na grade de redes mora AQUI, não lá dentro.
     *
     * É o que deixa o aviso do topo e o passo "conectar uma rede" abrirem a
     * grade **sem navegar**. Os dois resolvem nesta mesma tela, e ação que
     * resolve aqui não pode virar link para cá.
     */
    const [redeAberta, setRedeAberta] = useState<string | null>(null);

    /*
     * ⭐ `?conectar` abre o catálogo assim que a tela monta.
     *
     * É como "conectar uma rede neste grupo", lá da janela de gerenciar, chega
     * até aqui: o servidor troca o grupo e manda para cá com a intenção na URL.
     * O modo segue a intenção, então a conta nasce no grupo certo.
     *
     * ⚠️ O parâmetro é apagado da barra de endereço no mesmo instante: ele é um
     * recado de uma vez só, e recarregar a página não pode reabrir uma janela
     * que a pessoa já fechou.
     */
    const [escolhendoRede, setEscolhendoRede] = useState(() => {
        if (typeof window === 'undefined') return false;

        const url = new URL(window.location.href);
        if (!url.searchParams.has('conectar')) return false;

        url.searchParams.delete('conectar');
        window.history.replaceState({}, '', url.pathname + url.search);

        return true;
    });

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
                        // ⚠️ O caminho para a lista mora aqui: os números são o
                        // resumo dela, e é daqui que se quer ir ver de perto.
                        apoio={
                            <Link href={route('publicacoes')} className="hover:text-foreground">
                                ver publicações
                            </Link>
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
                        {numeros.falharam > 0 && (
                            <li>
                                <Numero valor={numeros.falharam} rotulo="não subiram" apoio="dá para tentar" cor="var(--saude-erro)" />
                            </li>
                        )}
                    </ul>
                </section>

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

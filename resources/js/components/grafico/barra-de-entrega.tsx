/**
 * Uma fatia da barra — **valor absoluto, nunca porcentagem**.
 *
 * A porcentagem é conta de quem desenha, não de quem chama: quem chama sabe
 * quantos posts existem, e é esse número que aparece escrito ao lado.
 */
export interface FatiaDaBarra {
    /** Só serve de chave de React e gancho de teste — o desenho não sabe o que ela significa. */
    chave: string;
    rotulo: string;
    valor: number;
    /** Já resolvida por quem chama, ex.: `var(--saude-ok)`. */
    cor: string;
    /** `listrado` marca o que ainda não terminou — e é o segundo canal além da cor. */
    padrao?: 'solido' | 'listrado';
}

interface Props {
    fatias: FatiaDaBarra[];
    /**
     * A medida compartilhada.
     *
     * ⭐ É o que faz a barra de um grupo ser comparável com a de outro: todas
     * medidas pelo mesmo total. Sem ele, cada barra se escala por si e o grupo
     * de 5 posts desenha do mesmo tamanho do de 40 — um gráfico que mente por
     * construção. Ausente, é a soma das próprias fatias.
     */
    maximo?: number;
    corDoVazio: string;
    /** Obrigatório: vira `aria-label`. Gráfico sem isto é imagem muda. */
    rotuloAcessivel: string;
    espessura?: number;
}

/** Fatia menor que isto some — e fatia que existe nunca pode sumir. */
const PISO_EM_PIXEL = 4;

/**
 * ⭐ A barra da entrega — e **só isso**.
 *
 * Ela desenha uma barra empilhada. Não formata número, não escreve frase, não
 * sabe o que é grupo, rede, rota ou status, não escolhe cor, não navega, não
 * guarda estado e não anima.
 *
 * ⚠️ **É um contrato, não um componente qualquer.** Nada aqui recebe pixel de
 * largura, classe do Tailwind, `children`, callback, JSX ou `Date` — só número,
 * rótulo e cor. É isso que permite trocar este desenho por uma biblioteca de
 * gráfico (ApacheECharts, por exemplo) mexendo **neste arquivo e em mais
 * nenhum**: o adaptador consome o mesmo array, e a tela não muda um caractere.
 *
 * ⛔ **Nada aqui é clicável.** Segmento de 10px de altura é um vinte e quatro
 * avos do alvo mínimo de toque, e no celular não existe `hover` para avisar
 * antes do clique (DEC-91). A porta é sempre um botão com verbo, do lado de fora.
 */
export default function BarraDeEntrega({ fatias, maximo, corDoVazio, rotuloAcessivel, espessura = 10 }: Props) {
    const comValor = fatias.filter((fatia) => fatia.valor > 0);
    const soma = comValor.reduce((total, fatia) => total + fatia.valor, 0);

    // ⛔ Sem dado, nada é desenhado — nem esqueleto de 0%. Barra vazia é uma
    // promessa de que existe algo ali, e não existe.
    if (soma === 0) {
        return null;
    }

    const referencia = Math.max(maximo ?? soma, soma);

    return (
        <div
            role="img"
            aria-label={rotuloAcessivel}
            className="w-full overflow-hidden rounded-sm"
            style={{ height: espessura, backgroundColor: corDoVazio }}
        >
            <div className="flex h-full" style={{ width: `${(soma / referencia) * 100}%` }}>
                {comValor.map((fatia, indice) => (
                    <div
                        key={fatia.chave}
                        className="h-full"
                        style={{
                            // ⚠️ O piso é o que impede uma fatia de 1 em 400
                            // desaparecer. A proporção perde um fio de precisão;
                            // o número exato está escrito ao lado, e uma falha
                            // invisível seria a mentira que este painel existe
                            // para não contar.
                            flex: `1 1 ${(fatia.valor / soma) * 100}%`,
                            minWidth: PISO_EM_PIXEL,
                            backgroundColor: fatia.cor,
                            // Vão na cor do fundo, nunca contorno: contorno
                            // engorda a fatia pequena e mente sobre o tamanho.
                            marginLeft: indice === 0 ? 0 : 3,
                            backgroundImage:
                                fatia.padrao === 'listrado'
                                    ? 'repeating-linear-gradient(45deg, rgba(255,255,255,.55) 0 2px, transparent 2px 5px)'
                                    : undefined,
                        }}
                    />
                ))}
            </div>
        </div>
    );
}

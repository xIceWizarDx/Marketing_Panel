/**
 * Os avisos do painel — a fila e as regras.
 *
 * ⭐ **É aqui que se mexe** para mudar comportamento: tempo na tela, tons
 * disponíveis, quantos cabem de uma vez. O desenho (cor, ícone, posição) fica em
 * `components/avisos.tsx`, e ler o recado que veio do servidor fica em
 * `hooks/use-avisos.ts`.
 *
 * Três arquivos, três motivos de mudança. Misturar os três num só é o que
 * transforma "quero que o toast dure mais" numa caçada.
 *
 * ## Como usar de qualquer lugar
 *
 * ```ts
 * import { avisar } from '@/lib/avisos';
 *
 * avisar.sucesso('Copiado.');
 * avisar.erro('Não conseguimos ler o arquivo.');
 * ```
 *
 * Não precisa passar pelo servidor: dá para avisar sobre coisas que só o
 * navegador sabe, como um "copiado para a área de transferência".
 */

export type Tom = 'sucesso' | 'erro' | 'aviso';

export interface Recado {
    id: number;
    tom: Tom;
    texto: string;
}

/**
 * ⚠️ **Erro não some sozinho.**
 *
 * Sucesso pode desaparecer: quem acabou de agir já sabe o que fez. Erro, não —
 * some antes de a pessoa terminar de ler, e ela fica sem saber o que aconteceu e
 * sem como recuperar o texto. Quem fecha é ela.
 *
 * `null` = fica até fechar na mão.
 */
export const SEGUNDOS_NA_TELA: Record<Tom, number | null> = {
    sucesso: 5,
    aviso: 7,
    erro: null,
};

/**
 * Teto de recados simultâneos.
 *
 * Sem teto, uma tela que dispara vários avisos empilha uma parede que cobre o
 * conteúdo — e o mais antigo, que ninguém mais vai ler, é o que atrapalha.
 */
const MAXIMO_NA_TELA = 4;

let recados: Recado[] = [];
let proximoId = 0;

type Ouvinte = (recados: Recado[]) => void;
const ouvintes = new Set<Ouvinte>();

function publicar(): void {
    // Cópia nova a cada mudança: React compara por identidade e não redesenharia
    // se a gente mutasse o mesmo array.
    const atual = [...recados];
    ouvintes.forEach((ouvinte) => ouvinte(atual));
}

/** Assina a fila. Devolve a função que cancela a assinatura. */
export function ouvirAvisos(ouvinte: Ouvinte): () => void {
    ouvintes.add(ouvinte);
    ouvinte([...recados]);

    return () => {
        ouvintes.delete(ouvinte);
    };
}

export function mostrarAviso(tom: Tom, texto: string): number {
    const recado = { id: proximoId++, tom, texto };

    recados = [...recados, recado].slice(-MAXIMO_NA_TELA);
    publicar();

    return recado.id;
}

export function fecharAviso(id: number): void {
    recados = recados.filter((recado) => recado.id !== id);
    publicar();
}

/** Atalhos, para quem chama não precisar lembrar dos tons. */
export const avisar = {
    sucesso: (texto: string) => mostrarAviso('sucesso', texto),
    erro: (texto: string) => mostrarAviso('erro', texto),
    aviso: (texto: string) => mostrarAviso('aviso', texto),
};

/**
 * Conta o texto do jeito que cada rede conta.
 *
 * ⚠️ **`texto.length` mente.** Ele conta unidades de código UTF-16, não
 * caracteres visíveis:
 *
 * | Texto | `.length` | O que a pessoa vê |
 * |---|---|---|
 * | `👨‍👩‍👧‍👦` | 11 | 1 |
 * | `🇧🇷` | 4 | 1 |
 *
 * O servidor já conta certo (`Medida::Grafemas`) — foi corrigido quando lemos a
 * lexicon do Bluesky, justamente porque a contagem ingênua recusa texto que
 * caberia. A tela ficou para trás e mostrava um número que não batia com o da
 * rede.
 */

export type Medida = 'grafemas' | 'caracteres' | 'bytes';

/**
 * `Intl.Segmenter` é o equivalente no navegador do `\X` do servidor: agrupa os
 * pontos de código que formam **um** símbolo visível.
 *
 * Criado uma vez: instanciar a cada tecla digitada é caro à toa.
 */
const segmentador = typeof Intl !== 'undefined' && 'Segmenter' in Intl ? new Intl.Segmenter('pt-BR', { granularity: 'grapheme' }) : null;

export function contar(texto: string, medida: Medida): number {
    if (!texto) return 0;

    switch (medida) {
        case 'grafemas':
            // Sem `Intl.Segmenter` (navegador antigo), `[...texto]` conta pontos
            // de código: erra menos que `.length` e nunca conta a MENOS — então
            // não deixa passar texto que a rede recusaria.
            return segmentador ? [...segmentador.segment(texto)].length : [...texto].length;

        case 'bytes':
            return new TextEncoder().encode(texto).length;

        case 'caracteres':
            // Ponto de código, como o `mb_strlen` do servidor.
            return [...texto].length;
    }
}

export interface LimiteDaRede {
    titulo: number | null;
    legenda: number | null;
    medidaDaLegenda: Medida;
}

/**
 * O limite que vale quando se publica em várias redes de uma vez.
 *
 * ⭐ Sempre o **mais apertado** entre as escolhidas: é ele que decide se o texto
 * passa em todas. Mostrar o mais folgado deixaria a pessoa escrever à vontade
 * para ser recusada pela rede mais restrita.
 *
 * `null` quando nenhuma das escolhidas declara limite — aí não há o que avisar.
 */
export function limiteMaisApertado(
    limites: Record<string, LimiteDaRede>,
    plataformas: string[],
    campo: 'titulo' | 'legenda',
): { rede: string; valor: number; medida: Medida } | null {
    let menor: { rede: string; valor: number; medida: Medida } | null = null;

    for (const plataforma of plataformas) {
        const limite = limites[plataforma];
        const valor = limite?.[campo];

        if (!limite || valor == null) continue;

        if (!menor || valor < menor.valor) {
            menor = {
                rede: plataforma,
                valor,
                medida: campo === 'legenda' ? limite.medidaDaLegenda : 'caracteres',
            };
        }
    }

    return menor;
}

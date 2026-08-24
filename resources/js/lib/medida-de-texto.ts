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
    /**
     * ⛔ O aviso de que publicar nesta rede custa mais por causa do link — ou
     * `null`, que é o caso de todas menos uma (DEC-126).
     *
     * ⚠️ A frase vem PRONTA do servidor. Os preços não podem existir escritos em
     * dois idiomas: no dia em que o X mudar a tabela, uma das cópias fica errada
     * — e é a errada que a pessoa vai ler.
     */
    avisoDeLink: string | null;
    /**
     * ⛔ Esta rede **exige** título: sem ele, ela recusa a publicação (DEC-166).
     *
     * ⚠️ Conferido antes do envio, e não depois — descobrir isso com o vídeo já
     * na fila é o defeito que esta regra apaga.
     */
    tituloObrigatorio: boolean;
    /**
     * ⛔ Esta rede não tem campo de título: ele sobe **colado na legenda**, e os
     * dois dividem um orçamento só (Threads, TikTok).
     */
    tituloEntraNaLegenda: boolean;
}

/**
 * Alguma das redes escolhidas soma título e legenda no mesmo limite?
 *
 * ⚠️ Basta **uma**: se o Threads está entre as escolhidas, é o texto somado que
 * precisa caber. Contar só a legenda diria que cabe, e o servidor recusaria em
 * seguida — duas verdades diferentes para o mesmo texto.
 */
export function algumaSomaTituloNaLegenda(limites: Record<string, LimiteDaRede>, plataformas: string[]): boolean {
    return plataformas.some((p) => limites[p]?.tituloEntraNaLegenda === true);
}

/**
 * ⛔ **Alguma das redes escolhidas EXIGE título?** (DEC-166)
 *
 * ⚠️ Nasceu de uma falha evitável: publicar sem título no YouTube subia na
 * fila, era recusado lá na frente, e virava "não foi" em vermelho — por uma
 * coisa que dava para saber antes de clicar.
 *
 * ⭐ Contar falha é placar. Impedir é produto.
 *
 * @returns o rótulo da primeira rede que exige, ou `null`
 */
export function redeQueExigeTitulo(limites: Record<string, LimiteDaRede>, plataformas: string[], rotulos: Record<string, string>): string | null {
    const rede = plataformas.find((p) => limites[p]?.tituloObrigatorio === true);

    return rede ? (rotulos[rede] ?? rede) : null;
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

/**
 * O aviso de custo das redes escolhidas, para este texto — ou `null`.
 *
 * ⚠️ A tela decide **quando** mostrar (é ela que vê a pessoa digitando); o
 * servidor decide **o que** dizer. Números moram num lugar só.
 */
export function avisoDeCusto(limites: Record<string, LimiteDaRede>, plataformas: string[], texto: string): string | null {
    if (!temLink(texto)) return null;

    for (const plataforma of plataformas) {
        const aviso = limites[plataforma]?.avisoDeLink;

        if (aviso) return aviso;
    }

    return null;
}

/**
 * ⛔ **O texto tem link?** — e no X isso custa treze vezes mais (DEC-126).
 *
 * ⚠️ A mesma régua do servidor (`CustoDaPublicacao::temLink`), de propósito
 * abrangente: pega `http://`, `https://`, `www.` e o domínio solto com barra,
 * que é como a maioria escreve. Falso positivo custa uma frase; falso negativo
 * custa US$ 0,185 por publicação.
 *
 * ⛔ Não é validação de URL, e não deve virar uma: o objetivo é **avisar**, não
 * recusar.
 */
export function temLink(texto: string): boolean {
    return /(https?:\/\/|www\.[a-z0-9-]+\.[a-z]{2,}|[a-z0-9-]+\.[a-z]{2,}\/\S)/i.test(texto.trim());
}

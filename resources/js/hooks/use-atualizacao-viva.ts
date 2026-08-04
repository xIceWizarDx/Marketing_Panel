import { router } from '@inertiajs/react';
import { useEffect } from 'react';

/**
 * Mantém a tela acompanhando o motor, sem a pessoa precisar atualizar.
 *
 * O motor é assíncrono: publicar enfileira, o envio acontece depois e a
 * conciliação depois ainda. Sem isto, a tela vira uma foto do instante em que
 * foi aberta — e a pessoa fica olhando "na fila" achando que travou.
 *
 * ## Por que buscar de novo, e não WebSocket
 *
 * Empurrar por WebSocket exigiria um processo permanente no servidor, com porta
 * e monitoramento próprios — mais uma peça que cai em silêncio e leva junto uma
 * funcionalidade que ninguém percebe ter parado. O ciclo do motor leva dezenas
 * de segundos; perguntar de poucos em poucos segundos chega na mesma hora, com
 * uma peça a menos para manter.
 *
 * Duas travas evitam o desperdício que dá má fama a essa abordagem:
 *
 *   - **Só enquanto há o que esperar.** Terminou tudo, para sozinho. Em repouso
 *     não custa nada.
 *   - **Só com a aba à vista.** Aba em segundo plano não gera pedido — e volta a
 *     buscar assim que a pessoa retorna, já atualizando na hora.
 */
export function useAtualizacaoViva({
    ativo,
    propriedades,
    segundos = 4,
}: {
    /** Há algo em andamento? Quando vira `false`, a busca para. */
    ativo: boolean;
    /** Quais props recarregar — o resto da página não é refeito à toa. */
    propriedades: string[];
    segundos?: number;
}): void {
    // Comparado por conteúdo: um array novo a cada render reiniciaria o
    // relógio sem parar, e a atualização nunca aconteceria.
    const chave = propriedades.join(',');

    useEffect(() => {
        if (!ativo) return;

        const buscar = () => {
            if (document.hidden) return;

            router.reload({ only: chave.split(','), showProgress: false });
        };

        const relogio = setInterval(buscar, segundos * 1000);

        // Voltar para a aba atualiza na hora, sem esperar o próximo ciclo.
        document.addEventListener('visibilitychange', buscar);

        return () => {
            clearInterval(relogio);
            document.removeEventListener('visibilitychange', buscar);
        };
    }, [ativo, chave, segundos]);
}

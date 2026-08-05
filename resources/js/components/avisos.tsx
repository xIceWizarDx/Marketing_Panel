import { AlertTriangle, CheckCircle2, Info, X } from 'lucide-react';
import { useEffect, useState } from 'react';

import { useAvisos } from '@/hooks/use-avisos';
import { SEGUNDOS_NA_TELA, type Recado, type Tom } from '@/lib/avisos';

/**
 * ⭐ **É aqui que se mexe** para mudar a aparência: cor, ícone, canto da tela.
 *
 * O tempo e as regras da fila ficam em `lib/avisos.ts`; ler o que veio do
 * servidor fica em `hooks/use-avisos.ts`. Este arquivo só desenha.
 *
 * Para um tom novo, basta acrescentar aqui e em `Tom` — o TypeScript aponta os
 * dois lugares que faltam preencher, em vez de deixar um tom sem cor passar.
 */
const APARENCIA: Record<Tom, { Icone: typeof Info; cor: string; papel: 'status' | 'alert' }> = {
    sucesso: { Icone: CheckCircle2, cor: 'var(--saude-ok)', papel: 'status' },
    aviso: { Icone: Info, cor: 'var(--saude-atencao)', papel: 'status' },
    // `alert` faz o leitor de tela interromper e anunciar na hora. Só o erro
    // merece isso: interromper por um "salvo" é atrapalhar.
    erro: { Icone: AlertTriangle, cor: 'var(--saude-erro)', papel: 'alert' },
};

/** Tempo da animação de entrada e saída. */
const MILISSEGUNDOS_DA_ANIMACAO = 200;

export default function Avisos() {
    const { recados, fechar } = useAvisos();

    // O container vive sempre, mesmo vazio: leitor de tela só anuncia o que
    // aparece DENTRO de uma região que já existia antes.
    if (!recados.length) {
        return <div aria-live="polite" className="sr-only" />;
    }

    return (
        <div
            aria-live="polite"
            // ⚠️ `top-14` é a altura da barra superior, que é fixa: sem isso o
            // aviso nasce por baixo dela. E `z-50` fica acima do `z-20` da barra,
            // senão o toast some atrás no momento em que mais importa.
            className="pointer-events-none fixed inset-x-0 top-14 z-50 flex flex-col items-center gap-2 p-4 sm:right-0 sm:left-auto sm:items-end"
        >
            {recados.map((recado) => (
                <Toast key={recado.id} recado={recado} aoFechar={() => fechar(recado.id)} />
            ))}
        </div>
    );
}

function Toast({ recado, aoFechar }: { recado: Recado; aoFechar: () => void }) {
    const { Icone, cor, papel } = APARENCIA[recado.tom];
    const [visivel, setVisivel] = useState(false);

    // Entra no quadro seguinte para a transição acontecer — nascer já no lugar
    // final não anima nada.
    useEffect(() => {
        const entrada = requestAnimationFrame(() => setVisivel(true));

        return () => cancelAnimationFrame(entrada);
    }, []);

    useEffect(() => {
        const segundos = SEGUNDOS_NA_TELA[recado.tom];

        if (segundos === null) return;

        const saida = setTimeout(() => {
            setVisivel(false);
            // Espera a animação terminar antes de tirar do DOM.
            setTimeout(aoFechar, MILISSEGUNDOS_DA_ANIMACAO);
        }, segundos * 1000);

        return () => clearTimeout(saida);
    }, [recado.tom, aoFechar]);

    return (
        <div
            role={papel}
            // Desliza de cima para baixo: o aviso nasce no canto superior, então
            // entrar subindo iria contra o lado de onde ele veio.
            className={`border-border bg-card pointer-events-auto flex w-full max-w-sm items-start gap-2.5 rounded-xl border p-3 shadow-lg transition-all ease-out motion-reduce:transition-none ${
                visivel ? 'translate-y-0 opacity-100' : '-translate-y-2 opacity-0'
            }`}
            // A cor fica numa faixa lateral, não no fundo inteiro: texto longo
            // sobre fundo colorido cansa, e mensagem de erro costuma ser longa.
            style={{ borderLeft: `3px solid ${cor}`, transitionDuration: `${MILISSEGUNDOS_DA_ANIMACAO}ms` }}
        >
            <Icone className="mt-0.5 size-4 shrink-0" style={{ color: cor }} aria-hidden="true" />

            <p className="flex-1 text-sm leading-snug break-words">{recado.texto}</p>

            <button
                type="button"
                onClick={aoFechar}
                aria-label="Fechar aviso"
                className="text-muted-foreground hover:text-foreground focus-visible:ring-ring -mt-0.5 -mr-0.5 rounded-md p-0.5 transition-colors focus-visible:ring-2 focus-visible:outline-none"
            >
                <X className="size-3.5" aria-hidden="true" />
            </button>
        </div>
    );
}

import { Moon, Sun } from 'lucide-react';
import { useRef } from 'react';

import { useAparencia } from '@/hooks/use-aparencia';
import { cn } from '@/lib/utils';

/**
 * A chavinha de claro e escuro.
 *
 * ⚠️ **Ela tem dois estados, e a preferência tem três.** *"Do sistema"* continua
 * existindo e continua morando em **Minha conta → Aparência**, que é onde se
 * procura uma preferência guardada. Aqui a chavinha mostra o tema que está
 * **valendo agora** e, ao ser clicada, crava o oposto — porque quem mexe numa
 * chavinha está pedindo um dos dois lados, não "siga o sistema".
 *
 * ⛔ Não há terceiro estado no desenho. Chave de três posições não é chave: vira
 * um seletor disfarçado, e ninguém adivinha que o meio existe.
 *
 * ⚠️ Os dois leem e escrevem o mesmo `useAparencia` da tela de Aparência — não
 * existe estado paralelo para divergir.
 */
export default function SeletorDeTema() {
    const { escuro, definirAparencia } = useAparencia();
    const chave = useRef<HTMLButtonElement>(null);

    const alternar = () => {
        const proximo = escuro ? 'claro' : 'escuro';

        /*
         * ⭐ O tema novo entra por um círculo que cresce **de dentro da
         * chavinha**. Marcamos aqui onde ela está; o desenho do círculo é do
         * CSS (`revelar-tema`).
         */
        const caixa = chave.current?.getBoundingClientRect();

        if (caixa) {
            const raiz = document.documentElement;
            raiz.style.setProperty('--origem-do-tema-x', `${caixa.left + caixa.width / 2}px`);
            raiz.style.setProperty('--origem-do-tema-y', `${caixa.top + caixa.height / 2}px`);
        }

        /*
         * ⚠️ **A troca acontece com ou sem a animação.** `startViewTransition`
         * ainda não existe em todo navegador, e um tema que só troca onde há
         * efeito bonito é um tema quebrado — a animação é enfeite, a troca é a
         * função.
         */
        const documento = document as Document & {
            startViewTransition?: (troca: () => void) => void;
        };

        if (typeof documento.startViewTransition !== 'function') {
            definirAparencia(proximo);

            return;
        }

        documento.startViewTransition(() => definirAparencia(proximo));
    };

    return (
        <button
            ref={chave}
            type="button"
            role="switch"
            aria-checked={escuro}
            aria-label="Tema escuro"
            title={escuro ? 'Mudar para o tema claro' : 'Mudar para o tema escuro'}
            onClick={alternar}
            className="border-border bg-muted focus-visible:ring-ring relative flex h-6 w-11 shrink-0 items-center rounded-full border transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-[color:var(--background)] focus-visible:outline-none"
        >
            {/* ⚠️ O botão só desliza; ele não muda de tamanho nem de cor. Bola
                que cresce ao trocar de lado faz a chavinha parecer que travou no
                meio do caminho. */}
            <span
                aria-hidden="true"
                className={cn(
                    'bg-background absolute flex size-5 items-center justify-center rounded-full shadow-sm',
                    'transition-transform duration-300 ease-out motion-reduce:transition-none',
                    escuro ? 'translate-x-[1.375rem]' : 'translate-x-0.5',
                )}
            >
                {escuro ? <Moon className="size-2.5" /> : <Sun className="size-2.5" />}
            </span>
        </button>
    );
}

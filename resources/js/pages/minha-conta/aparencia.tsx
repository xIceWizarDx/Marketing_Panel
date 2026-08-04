import { Head } from '@inertiajs/react';
import { LucideIcon, Monitor, Moon, Sun } from 'lucide-react';

import TituloDeSecao from '@/components/titulo-de-secao';
import { Aparencia as TipoAparencia, useAparencia } from '@/hooks/use-aparencia';
import LayoutMinhaConta from '@/layouts/minha-conta';
import { cn } from '@/lib/utils';

const opcoes: { valor: TipoAparencia; Icone: LucideIcon; rotulo: string }[] = [
    { valor: 'claro', Icone: Sun, rotulo: 'Claro' },
    { valor: 'escuro', Icone: Moon, rotulo: 'Escuro' },
    { valor: 'sistema', Icone: Monitor, rotulo: 'Do sistema' },
];

export default function Aparencia() {
    const { aparencia, definirAparencia } = useAparencia();

    return (
        <LayoutMinhaConta>
            <Head title="Aparência" />

            <section className="space-y-5">
                <TituloDeSecao titulo="Aparência" descricao="Escolha o tema do painel. Fica salvo neste navegador." />

                <div role="radiogroup" aria-label="Tema do painel" className="bg-muted inline-flex gap-1 rounded-lg p-1">
                    {opcoes.map(({ valor, Icone, rotulo }) => {
                        const ativo = aparencia === valor;

                        return (
                            <button
                                key={valor}
                                type="button"
                                role="radio"
                                aria-checked={ativo}
                                onClick={() => definirAparencia(valor)}
                                className={cn(
                                    'focus-visible:ring-ring flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm transition-colors focus-visible:ring-2 focus-visible:outline-none',
                                    ativo ? 'bg-card text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground',
                                )}
                            >
                                <Icone className="size-4" aria-hidden="true" />
                                {rotulo}
                            </button>
                        );
                    })}
                </div>
            </section>
        </LayoutMinhaConta>
    );
}

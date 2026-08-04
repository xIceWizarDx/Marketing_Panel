import { Eye, EyeOff } from 'lucide-react';
import { ComponentProps, useState } from 'react';

import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type Props = Omit<ComponentProps<typeof Input>, 'type'>;

/**
 * Campo de senha com botão de ver.
 *
 * Existe porque digitar senha às cegas é a causa nº 1 de "minha senha está
 * certa e diz que não está" — principalmente no celular, onde o corretor
 * atrapalha e a tecla errada é fácil.
 *
 * O botão fica FORA da ordem de tabulação (`tabIndex={-1}`): quem usa teclado
 * passa direto do campo para o botão de entrar, sem tropeçar nele.
 */
export default function CampoSenha({ className, ...props }: Props) {
    const [visivel, setVisivel] = useState(false);

    return (
        <span className="relative block">
            <Input {...props} type={visivel ? 'text' : 'password'} className={cn('pr-10', className)} />

            <button
                type="button"
                onClick={() => setVisivel((atual) => !atual)}
                tabIndex={-1}
                aria-label={visivel ? 'Esconder a senha' : 'Mostrar a senha'}
                aria-pressed={visivel}
                className="text-muted-foreground hover:text-foreground focus-visible:ring-ring absolute top-1/2 right-1 -translate-y-1/2 rounded-md p-2 transition-colors focus-visible:ring-2 focus-visible:outline-none"
            >
                {visivel ? <EyeOff className="size-4" aria-hidden="true" /> : <Eye className="size-4" aria-hidden="true" />}
            </button>
        </span>
    );
}

import { HTMLAttributes } from 'react';

import { cn } from '@/lib/utils';

/**
 * Mensagem de erro de um campo.
 *
 * `role="alert"` faz o leitor de tela anunciar o erro assim que ele aparece —
 * sem isso, quem navega por teclado nao descobre por que o formulario nao enviou.
 */
export default function ErroDeCampo({ mensagem, className, ...props }: HTMLAttributes<HTMLParagraphElement> & { mensagem?: string }) {
    if (!mensagem) {
        return null;
    }

    return (
        <p {...props} role="alert" className={cn('text-sm text-[color:var(--destructive)]', className)}>
            {mensagem}
        </p>
    );
}

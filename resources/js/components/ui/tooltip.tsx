import * as TooltipPrimitive from '@radix-ui/react-tooltip';
import * as React from 'react';

import { cn } from '@/lib/utils';

const TooltipProvider = TooltipPrimitive.Provider;

const Tooltip = TooltipPrimitive.Root;

const TooltipTrigger = TooltipPrimitive.Trigger;

/**
 * A dica que aparece ao lado — e ela existe por um motivo estreito.
 *
 * ⭐ Serve à barra lateral **recolhida**, onde só o ícone aparece e o nome do
 * item precisa vir de algum lugar. O `title` do navegador fazia esse papel e
 * fazia mal: demora quase um segundo para aparecer, não segue o tema, some ao
 * mover o dedo e **não existe no celular**.
 *
 * ⛔ Não é para explicar botão que já tem palavra escrita. Dica que repete o
 * rótulo é ruído com atraso.
 */
const TooltipContent = React.forwardRef<
    React.ElementRef<typeof TooltipPrimitive.Content>,
    React.ComponentPropsWithoutRef<typeof TooltipPrimitive.Content>
>(({ className, sideOffset = 6, ...props }, ref) => (
    <TooltipPrimitive.Portal>
        <TooltipPrimitive.Content
            ref={ref}
            sideOffset={sideOffset}
            className={cn(
                'bg-primary text-primary-foreground z-50 overflow-hidden rounded-md px-2 py-1 text-xs',
                // ⚠️ A animação é só de opacidade e de um fio de deslocamento.
                // Dica que cresce da escala chama mais atenção que o conteúdo
                // que ela explica.
                'data-[state=delayed-open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=delayed-open]:fade-in-0',
                'data-[side=right]:slide-in-from-left-1 data-[side=left]:slide-in-from-right-1',
                className,
            )}
            {...props}
        />
    </TooltipPrimitive.Portal>
));
TooltipContent.displayName = TooltipPrimitive.Content.displayName;

export { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger };

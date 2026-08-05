import { Link } from '@inertiajs/react';
import { ComponentProps } from 'react';

import { cn } from '@/lib/utils';

type Props = ComponentProps<typeof Link>;

/** Link dentro de um texto corrido — sublinhado e na cor de destaque. */
export default function LinkTexto({ className, children, ...props }: Props) {
    return (
        <Link
            className={cn(
                'font-medium text-[color:var(--accent)] underline-offset-4 transition-colors hover:underline',
                'focus-visible:ring-ring rounded-sm focus-visible:ring-2 focus-visible:outline-none',
                className,
            )}
            {...props}
        >
            {children}
        </Link>
    );
}

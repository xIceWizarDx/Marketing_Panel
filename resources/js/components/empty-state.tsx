import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { PropsWithChildren } from 'react';

type EmptyStateProps = PropsWithChildren<{
    title: string;
    description?: string;
    action?: { label: string; href?: string; onClick?: () => void };
    className?: string;
}>;

export default function EmptyState({ title, description, action, className, children }: EmptyStateProps) {
    return (
        <div className={cn('flex flex-col items-center justify-center rounded-lg border bg-card text-card-foreground p-10 text-center shadow-soft', className)}>
            <h3 className="text-lg font-semibold">{title}</h3>
            {description && <p className="mt-2 max-w-md text-sm text-muted-foreground">{description}</p>}
            {children}
            {action && (
                <Button className="mt-6" asChild={!action.onClick} onClick={action.onClick}>
                    {action.href ? <a href={action.href}>{action.label}</a> : <span>{action.label}</span>}
                </Button>
            )}
        </div>
    );
}


import { cn } from '@/lib/utils';

type Step = { key: string; title: string; subtitle?: string };

export default function Stepper({ steps, current }: { steps: Step[]; current: string }) {
    return (
        <div className="flex flex-wrap items-center gap-6 rounded-lg border bg-card p-4 text-sm">
            {steps.map((s, idx) => {
                const active = s.key === current;
                return (
                    <div key={s.key} className="flex items-center">
                        <div className={cn('flex items-center gap-2', active ? 'text-foreground' : 'text-muted-foreground')}>
                            <div className={cn('flex size-6 items-center justify-center rounded-full border', active ? 'bg-accent text-accent-foreground' : 'bg-muted')}>
                                {idx + 1}
                            </div>
                            <div>
                                <div className="font-medium">{s.title}</div>
                                {s.subtitle && <div className="text-xs text-muted-foreground">{s.subtitle}</div>}
                            </div>
                        </div>
                        {idx < steps.length - 1 && <div className="mx-3 h-5 w-px bg-border" />}
                    </div>
                );
            })}
        </div>
    );
}


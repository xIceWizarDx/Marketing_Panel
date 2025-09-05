import AppHeaderLayout from '@/layouts/app/app-header-layout';
import type { PageProps } from '@inertiajs/react';
import EmptyState from '@/components/empty-state';
import { Button } from '@/components/ui/button';

type Day = { date: string; count: number; items: { id: number; caption: string | null; status: string; time: string }[] };

export default function ContentCalendar({ month, year, days }: PageProps<{ month: number; year: number; days: Record<string, Day> }>) {
    const any = Object.values(days || {}).some((d) => (d?.count ?? 0) > 0);
    const daysInMonth = new Date(year, month, 0).getDate();

    return (
        <AppHeaderLayout breadcrumbs={[{ title: 'Início', href: '/' }, { title: 'Calendário', href: '/calendar' }]}> 
            <div className="mb-4 flex items-center justify-between">
                <h1 className="text-2xl font-semibold">{new Date(year, month - 1).toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' })}</h1>
                <div className="flex items-center gap-2">
                    <Button variant="ghost">Dia</Button>
                    <Button variant="ghost">Semana</Button>
                    <Button> Mês </Button>
                    <Button className="ml-4">+ Novo Post</Button>
                </div>
            </div>
            {!any ? (
                <EmptyState
                    title="Nenhum conteúdo agendado"
                    description="Agende posts para visualizar aqui no calendário."
                    action={{ label: 'Criar post', href: '/posts' }}
                />
            ) : (
                <div className="grid grid-cols-7 gap-px rounded-lg border bg-border">
                    {Array.from({ length: daysInMonth }, (_, i) => i + 1).map((d) => {
                        const key = String(d);
                        const info = days[key];
                        return (
                            <div key={d} className="min-h-24 bg-card p-2">
                                <div className="text-xs text-muted-foreground">{d}</div>
                                {info?.items?.slice(0, 3).map((it) => (
                                    <div key={it.id} className="mt-1 truncate rounded bg-muted px-2 py-1 text-xs">
                                        {it.time} • {it.caption || 'Post'}
                                    </div>
                                ))}
                            </div>
                        );
                    })}
                </div>
            )}
        </AppHeaderLayout>
    );
}


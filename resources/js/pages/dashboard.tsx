import AppLayout from '@/layouts/app-layout';
import { Head, usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import PlatformIcon from '@/components/platform-icon';
import { Users, CalendarRange, DollarSign, TrendingUp, Heart, ArrowUpRight } from 'lucide-react';
import Sparkline from '@/components/sparkline';

type PlatformCard = {
  id: number;
  platform: string;
  username: string | null;
  status: 'connected' | 'expired' | 'revoked' | 'error';
  is_connected: boolean;
  last_sync_at: string | null;
};

type Metrics = {
  total_followers: number;
  engagement_rate: number;
  scheduled_posts: number;
  monthly_reach: number;
};

type DashboardProps = {
  greetingName: string;
  platforms: PlatformCard[];
  metrics: Metrics;
  recent: { id: number; caption: string | null; status: string; created_at: string | null; published_at: string | null }[];
};

function formatK(n: number) {
  if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
  return String(n);
}

export default function Dashboard() {
  const { props } = usePage<{ auth: any } & DashboardProps>();
  const firstName = (props.greetingName || '').split(' ')[0] || 'Usuário';

  const prettyDateDiff = (iso: string | null) => {
    if (!iso) return '-';
    const d = new Date(iso);
    const diffMin = Math.max(0, Math.floor((Date.now() - d.getTime()) / 60000));
    if (diffMin < 60) return `${diffMin}min atrás`;
    const diffH = Math.floor(diffMin / 60);
    if (diffH < 24) return `${diffH}h atrás`;
    return `${Math.floor(diffH / 24)}d atrás`;
  };

  const cards: PlatformCard[] = props.platforms || [];

  return (
    <AppLayout breadcrumbs={[{ title: 'Dashboard', href: '/dashboard' }]}>
      <Head title="Dashboard" />
      <div className="space-y-8">
        <header className="pb-4">
          <h1 className="text-2xl font-semibold">Olá, {firstName}</h1>
          <p className="mt-1 text-sm text-muted-foreground">Resumo das suas contas conectadas e desempenho recente</p>
        </header>

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <section className="lg:col-span-2 space-y-4">
            <h2 className="mb-2 text-lg font-semibold">Contas Conectadas</h2>
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
              {cards.map((c) => (
                <Card key={c.id} className="transition-smooth hover:shadow-soft">
                  <CardHeader className="pb-2">
                    <CardTitle className="flex items-center justify-between text-base">
                      <div className="flex items-center gap-2">
                        <PlatformIcon platform={c.platform} />
                        <span className="capitalize">{c.platform}</span>
                      </div>
                      <span className={`rounded px-2 py-0.5 text-xs ${c.status==='connected'?'bg-green-100 text-green-700':c.status==='expired'?'bg-amber-100 text-amber-700':c.status==='revoked'?'bg-neutral-200 text-neutral-700':'bg-red-100 text-red-700'}`}>{c.status}</span>
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="text-sm text-muted-foreground">
                    <div className="truncate">{c.username || '-'}</div>
                    <div className="mt-1 text-xs">Última sincronização: {prettyDateDiff(c.last_sync_at)}</div>
                  </CardContent>
                </Card>
              ))}
              {cards.length === 0 && (
                <Card>
                  <CardContent className="p-6 text-sm text-muted-foreground">Nenhuma conta conectada.</CardContent>
                </Card>
              )}
            </div>

            <h2 className="mb-4 mt-10 text-lg font-semibold">Atividades Recentes</h2>
            <div className="space-y-3">
              {(props.recent || []).map((r) => (
                <Card key={r.id}>
                  <CardContent className="p-4 text-sm">
                    <div className="font-medium">{r.caption || 'Post'}</div>
                    <div className="mt-1 text-xs text-muted-foreground">{r.published_at ? `Publicado ${new Date(r.published_at).toLocaleString()}` : `Criado ${r.created_at ? new Date(r.created_at).toLocaleString() : ''}`}</div>
                  </CardContent>
                </Card>
              ))}
              {(!props.recent || props.recent.length === 0) && (
                <Card>
                  <CardContent className="p-6 text-sm text-muted-foreground">Sem atividades recentes.</CardContent>
                </Card>
              )}
            </div>
          </section>

          <aside className="space-y-4">
            <h2 className="mb-3 text-lg font-semibold">Métricas Principais</h2>
            <div className="space-y-4">
              <Card>
                <CardHeader className="py-3">
                  <CardTitle className="flex items-center justify-between text-sm font-medium"><span className="inline-flex items-center gap-2"><Users className="h-4 w-4 text-muted-foreground" /> Total de Seguidores</span> <span className="inline-flex items-center text-green-600 text-xs"><ArrowUpRight className="mr-1 size-4" /> +12.5%</span></CardTitle>
                </CardHeader>
                <CardContent className="py-2 text-3xl font-semibold">{formatK(props.metrics?.total_followers || 0)}<Sparkline /></CardContent>
              </Card>
              <Card>
                <CardHeader className="py-3">
                  <CardTitle className="flex items-center justify-between text-sm font-medium"><span className="inline-flex items-center gap-2"><Heart className="h-4 w-4 text-muted-foreground" /> Taxa de Engajamento</span> <span className="inline-flex items-center text-green-600 text-xs"><ArrowUpRight className="mr-1 size-4" /> +0.8%</span></CardTitle>
                </CardHeader>
                <CardContent className="py-2 text-3xl font-semibold">{(props.metrics?.engagement_rate ?? 0).toFixed(1)}%<Sparkline /></CardContent>
              </Card>
              <Card>
                <CardHeader className="py-3">
                  <CardTitle className="flex items-center justify-between text-sm font-medium"><span className="inline-flex items-center gap-2"><CalendarRange className="h-4 w-4 text-muted-foreground" /> Posts Agendados</span></CardTitle>
                </CardHeader>
                <CardContent className="py-2 text-3xl font-semibold">{props.metrics?.scheduled_posts ?? 0}<Sparkline /></CardContent>
              </Card>
              <Card>
                <CardHeader className="py-3">
                  <CardTitle className="flex items-center justify-between text-sm font-medium"><span className="inline-flex items-center gap-2"><TrendingUp className="h-4 w-4 text-muted-foreground" /> Alcance Mensal</span> <span className="inline-flex items-center text-green-600 text-xs"><ArrowUpRight className="mr-1 size-4" /> +22.3%</span></CardTitle>
                </CardHeader>
                <CardContent className="py-2 text-3xl font-semibold">{formatK(props.metrics?.monthly_reach || 0)}<Sparkline /></CardContent>
              </Card>
              <Card>
                <CardHeader className="py-3">
                  <CardTitle className="flex items-center justify-between text-sm font-medium"><span className="inline-flex items-center gap-2"><DollarSign className="h-4 w-4 text-muted-foreground" /> Investimento (BRL)</span> <span className="inline-flex items-center text-green-600 text-xs"><ArrowUpRight className="mr-1 size-4" /> +15.2%</span></CardTitle>
                </CardHeader>
                <CardContent className="py-2">
                  <div className="text-3xl font-semibold">R$ 0,00</div>
                  <div className="text-xs text-muted-foreground">Integração financeira pendente</div>
                </CardContent>
              </Card>
            </div>
          </aside>
        </div>
      </div>
    </AppLayout>
  );
}


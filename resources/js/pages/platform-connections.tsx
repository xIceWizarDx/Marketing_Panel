import AppHeaderLayout from '@/layouts/app/app-header-layout';
import type { PageProps } from '@inertiajs/react';
import EmptyState from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import PlatformIcon from '@/components/platform-icon';
import * as React from 'react';
import { RefreshCw, Plus, Zap, Download, Upload, HelpCircle, Info, Book, MessageCircle } from 'lucide-react';

type Account = {
    id: number;
    platform: string;
    provider_account_id: string | null;
    account_username: string | null;
    account_email: string | null;
    connection_status: 'connected' | 'expired' | 'revoked' | 'error';
    is_connected: boolean;
    last_sync_at: string | null;
    stats?: Record<string, any> | null;
};

export default function PlatformConnections({ accounts = [], statusCounts = {} }: PageProps<{ accounts: Account[]; statusCounts: Record<string, number> }>) {
    const hasAny = accounts.length > 0;
    const [items, setItems] = React.useState<Account[]>(accounts);
    const [loadingId, setLoadingId] = React.useState<number | null>(null);

    const updateStatus = (id: number, status: Account['connection_status'], last_sync_at?: string | null) => {
        setItems((prev) => prev.map((a) => (a.id === id ? { ...a, connection_status: status, is_connected: status === 'connected', last_sync_at: last_sync_at ?? a.last_sync_at } : a)));
    };
    const csrf = () => (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const changeStatus = async (id: number, status: Account['connection_status']) => {
        setLoadingId(id);
        try {
            const res = await fetch(`/connections/${id}/status`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ status }),
            });
            const data = await res.json();
            updateStatus(id, data.connection_status as Account['connection_status'], data.last_sync_at as string | null);
        } catch (e) {
            console.error(e);
        } finally {
            setLoadingId(null);
        }
    };

    return (
        <AppHeaderLayout breadcrumbs={[{ title: 'Início', href: '/' }, { title: 'Conexões', href: '/connections' }]}>
            <div className="space-y-8">
                {/* Page Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Conexões de Plataformas</h1>
                        <p className="mt-1 text-sm text-muted-foreground">Gerencie suas conexões com redes sociais e plataformas de marketing</p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={() => window.location.reload()}>
                            <RefreshCw className="mr-2" /> Atualizar
                        </Button>
                        <Button>
                            <Plus className="mr-2" /> Conectar Todas
                        </Button>
                    </div>
                </div>

                {/* Quick Actions */}
                <div className="rounded-lg border bg-card p-6">
                    <h2 className="mb-4 text-lg font-semibold">Ações Rápidas</h2>
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <Button variant="outline" className="justify-start">
                            <Zap className="mr-2" /> Testar Conexões
                        </Button>
                        <Button variant="outline" className="justify-start">
                            <Download className="mr-2" /> Exportar Configurações
                        </Button>
                        <Button variant="outline" className="justify-start">
                            <Upload className="mr-2" /> Importar Configurações
                        </Button>
                        <Button variant="outline" className="justify-start" onClick={() => window.open('/help/connections', '_blank')}>
                            <HelpCircle className="mr-2" /> Ajuda
                        </Button>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 gap-5 sm:grid-cols-4">
                    {(['connected', 'expired', 'revoked', 'error'] as const).map((k) => (
                        <Card key={k}>
                            <CardHeader className="py-3">
                                <CardTitle className="text-sm font-medium capitalize">{k}</CardTitle>
                            </CardHeader>
                            <CardContent className="py-3">
                                <div className="text-2xl font-semibold">{(statusCounts as any)[k] ?? 0}</div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Platform Cards Grid */}
                {!hasAny ? (
                    <EmptyState
                        title="Nenhuma conexão adicionada"
                        description="Conecte suas contas de Instagram, Facebook, TikTok e outras para começar a publicar."
                        action={{ label: 'Conectar conta', href: '#' }}
                    />
                ) : (
                    <div className="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                        {items.map((a) => {
                            const label = a.connection_status === 'connected' ? 'Conectado' : a.connection_status === 'expired' ? 'Atenção' : a.connection_status === 'revoked' ? 'Desconectado' : 'Erro';
                            const cls = a.connection_status === 'connected' ? 'bg-green-100 text-green-700' : a.connection_status === 'expired' ? 'bg-amber-100 text-amber-700' : a.connection_status === 'revoked' ? 'bg-neutral-200 text-neutral-700' : 'bg-red-100 text-red-700';
                            return (
                                <Card key={a.id} className="transition-smooth hover:shadow-soft">
                                    <CardHeader className="pb-2">
                                        <CardTitle className="flex items-center justify-between text-base">
                                            <div className="flex items-center gap-2">
                                                <PlatformIcon platform={a.platform} />
                                                <span className="capitalize">{a.platform}</span>
                                            </div>
                                            <TooltipProvider>
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Badge variant="secondary" className={cls}>{label}</Badge>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p>{a.connection_status === 'error' ? 'Token expirado / erro de API' : a.connection_status === 'expired' ? 'Sessão expirada' : a.connection_status === 'revoked' ? 'Acesso revogado' : 'Conexão ativa'}</p>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="text-sm text-muted-foreground">
                                        <div className="truncate">{a.account_username || a.account_email || a.provider_account_id || '-'}</div>
                                        <div className="mt-2 text-xs">Última sincronização: {a.last_sync_at ? new Date(a.last_sync_at).toLocaleString() : '-'}</div>
                                        {a.connection_status === 'error' && (
                                            <div className="mt-2 rounded-md bg-red-50 p-2 text-xs text-red-700">Token expirado</div>
                                        )}
                                        {a.connection_status === 'expired' && (
                                            <div className="mt-2 rounded-md bg-amber-50 p-2 text-xs text-amber-700">Sessão expirada</div>
                                        )}
                                        {a.connection_status !== 'connected' && (
                                            <div className="mt-2 rounded-md bg-amber-50 p-2 text-xs text-amber-700">API limite próximo</div>
                                        )}
                                        <div className="mt-3 flex items-center justify-between">
                                            <Button variant="ghost">Configurações</Button>
                                            {a.connection_status === 'connected' ? (
                                                <Button variant="destructive" disabled={loadingId===a.id} onClick={() => changeStatus(a.id, 'revoked')}>{loadingId===a.id ? '...' : 'Desconectar'}</Button>
                                            ) : (
                                                <Button disabled={loadingId===a.id} onClick={() => changeStatus(a.id, 'connected')}>{loadingId===a.id ? 'Conectando...' : 'Reconectar'}</Button>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                )}

                {/* Help Section */}
                <div className="rounded-lg border border-accent/20 bg-accent/5 p-6">
                    <div className="flex items-start gap-4">
                        <div className="flex size-12 shrink-0 items-center justify-center rounded-lg bg-accent/10">
                            <Info className="text-accent" />
                        </div>
                        <div>
                            <h3 className="mb-2 text-lg font-semibold">Precisa de Ajuda?</h3>
                            <p className="mb-4 text-sm text-muted-foreground">Consulte nossa documentação para obter instruções detalhadas sobre como conectar cada plataforma.</p>
                            <div className="flex flex-col gap-3 sm:flex-row">
                                <Button variant="outline" onClick={() => window.open('/docs/connections', '_blank')}>
                                    <Book className="mr-2" /> Ver Documentação
                                </Button>
                                <Button variant="outline" onClick={() => window.open('/support', '_blank')}>
                                    <MessageCircle className="mr-2" /> Contatar Suporte
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppHeaderLayout>
    );
}


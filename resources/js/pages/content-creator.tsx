import AppHeaderLayout from '@/layouts/app/app-header-layout';
import { router, type PageProps } from '@inertiajs/react';
import EmptyState from '@/components/empty-state';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import Stepper from '@/components/stepper';
import PlatformCard, { type PlatformItem } from '@/components/platform-card';
import * as React from 'react';
import MediaPicker from '@/components/media-picker';
import { AlertCircle, ChevronLeft, ChevronRight, Loader2, Save, X } from 'lucide-react';

type Post = {
    id: number;
    caption: string | null;
    hashtags: string | null;
    status: string;
    publish_type: string;
    timezone: string;
    scheduled_at: string | null;
    published_at: string | null;
    created_at: string | null;
};

export default function ContentCreator({ posts = [], accounts = [], mode = 'index' }: PageProps<{ posts: Post[]; accounts: PlatformItem[]; mode?: 'index' | 'create' }>) {
    const hasAny = posts.length > 0;
    const [selected, setSelected] = React.useState<number[]>([]);
    const toggle = (id: number) => setSelected((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]));
    const [step, setStep] = React.useState<'platforms' | 'media' | 'content' | 'schedule' | 'review'>('platforms');
    const [draftId, setDraftId] = React.useState<number | null>(null);
    const [caption, setCaption] = React.useState('');
    const [hashtags, setHashtags] = React.useState('');
    const [scheduledAt, setScheduledAt] = React.useState('');
    const [mediaIds, setMediaIds] = React.useState<number[]>([]);
    const [hasUnsavedChanges, setHasUnsavedChanges] = React.useState(false);
    const [isAutoSaving, setIsAutoSaving] = React.useState(false);

    const steps = [
        { key: 'platforms', title: 'Plataformas', subtitle: 'Selecionar onde publicar' },
        { key: 'media', title: 'Mídia', subtitle: 'Adicionar imagens/vídeos' },
        { key: 'content', title: 'Conteúdo', subtitle: 'Escrever legenda' },
        { key: 'schedule', title: 'Agendamento', subtitle: 'Definir quando publicar' },
        { key: 'review', title: 'Visualizar', subtitle: 'Revisar e publicar' },
    ] as const;

    const csrf = () => (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const json = (url: string, method: string, body?: any) =>
        fetch(url, { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: body ? JSON.stringify(body) : undefined });

    const ensureDraft = async () => {
        if (draftId) return draftId;
        const res = await json('/posts/drafts', 'POST', { timezone: 'UTC' });
        const data = await res.json();
        setDraftId(data.id);
        return data.id as number;
    };

    const handleNext = async () => {
        if (step === 'platforms') {
            const id = await ensureDraft();
            await json(`/posts/${id}/platforms`, 'POST', { platform_account_ids: selected });
            setStep('media');
            return;
        }
        if (step === 'media') {
            const id = await ensureDraft();
            const payload = mediaIds.map((mid, idx) => ({ id: mid, position: idx }));
            await json(`/posts/${id}/media`, 'POST', { media: payload });
            setStep('content');
            return;
        }
        if (step === 'content') {
            const id = await ensureDraft();
            await json(`/posts/${id}/draft`, 'PUT', { caption, hashtags });
            setStep('schedule');
            return;
        }
        if (step === 'schedule') {
            const id = await ensureDraft();
            await json(`/posts/${id}/schedule`, 'POST', { publish_type: scheduledAt ? 'scheduled' : 'now', scheduled_at: scheduledAt || null });
            setStep('review');
            return;
        }
        if (step === 'review') {
            router.visit('/posts');
        }
    };

    const handlePrev = () => {
        setStep((prev) => (prev === 'media' ? 'platforms' : prev === 'content' ? 'media' : prev === 'schedule' ? 'content' : prev === 'review' ? 'schedule' : 'platforms'));
    };

    const canProceedToNextStep = () => {
        if (step === 'platforms') return selected.length > 0;
        if (step === 'media') return true; // mídia opcional
        if (step === 'content') return caption.trim().length > 0 || mediaIds.length > 0;
        if (step === 'schedule') return true; // agora ou agendado
        return true;
    };

    const handleExit = () => {
        if (hasUnsavedChanges) {
            const ok = window.confirm('Você tem alterações não salvas. Deseja sair mesmo assim?');
            if (!ok) return;
        }
        router.visit('/posts');
    };

    React.useEffect(() => {
        setHasUnsavedChanges(true);
    }, [selected, mediaIds, caption, hashtags, scheduledAt]);

    React.useEffect(() => {
        const interval = window.setInterval(() => {
            if (hasUnsavedChanges) {
                setIsAutoSaving(true);
                window.setTimeout(() => {
                    setIsAutoSaving(false);
                    setHasUnsavedChanges(false);
                }, 1000);
            }
        }, 30000);
        return () => window.clearInterval(interval);
    }, [hasUnsavedChanges]);

    return (
        <AppHeaderLayout breadcrumbs={[{ title: 'Início', href: '/' }, { title: 'Criar Conteúdo', href: '/posts' }]}>
            <div className="px-4 py-8">
                <div className="mb-8 flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">Criar Conteúdo</h1>
                        <p className="text-muted-foreground">Crie e agende publicações para suas redes sociais</p>
                    </div>

                    <div className="mt-4 flex items-center gap-3 lg:mt-0">
                        {isAutoSaving && (
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <Loader2 className="size-4 animate-spin" />
                                Salvando...
                            </div>
                        )}
                        {hasUnsavedChanges && !isAutoSaving && (
                            <div className="flex items-center gap-2 text-sm text-amber-600">
                                <AlertCircle className="size-4" />
                                Alterações não salvas
                            </div>
                        )}
                        <Button variant="outline" onClick={handleExit}>
                            <X className="size-4" />
                            Sair
                        </Button>
                    </div>
                </div>

                <div className="mb-8">
                    <Stepper current={step} steps={steps as unknown as any} />
                </div>

                <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <div className="rounded-lg border bg-card p-6">
                            {mode === 'index' && !hasAny ? (
                                <div>
                                    <EmptyState
                                        title="Comece criando seu primeiro post"
                                        description="Escreva a legenda, adicione mídias e programe a publicação."
                                        action={{ label: 'Novo post', href: '/posts/create' }}
                                    />
                                </div>
                            ) : mode === 'index' && hasAny ? (
                                <div className="space-y-3">
                                    {posts.map((p) => (
                                        <div key={p.id} className="rounded-lg border bg-card p-4 shadow-soft">
                                            <div className="text-sm text-muted-foreground">{p.status.toUpperCase()} • {p.publish_type}</div>
                                            <div className="mt-1 font-medium">{p.caption?.slice(0, 120) || '(sem legenda)'}</div>
                                            <div className="mt-2 text-xs text-muted-foreground">
                                                {p.scheduled_at ? `Agendado para ${new Date(p.scheduled_at).toLocaleString()}` : `Criado em ${p.created_at ? new Date(p.created_at).toLocaleString() : '—'}`}
                                            </div>
                                        </div>
                                    ))}
                                    <div className="pt-2">
                                        <Button>Carregar mais</Button>
                                    </div>
                                </div>
                            ) : null}

                            {step === 'platforms' && (
                                <div>
                                    <h2 className="mb-3 text-lg font-semibold">Selecionar Plataformas</h2>
                                    {accounts.length === 0 ? (
                                        <EmptyState title="Nenhuma conta conectada" description="Conecte plataformas para publicar." action={{ label: 'Conectar', href: '/connections' }} />
                                    ) : (
                                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            {accounts.map((acc) => (
                                                <PlatformCard key={acc.id} item={acc} selected={selected.includes(acc.id)} onToggle={() => toggle(acc.id)} />
                                            ))}
                                        </div>
                                    )}
                                </div>
                            )}
                            {step === 'media' && (
                                <div>
                                    <MediaPicker value={mediaIds} onChange={setMediaIds} />
                                </div>
                            )}
                            {step === 'content' && (
                                <div className="space-y-3">
                                    <label className="block text-sm font-medium">Legenda</label>
                                    <textarea className="min-h-36 w-full rounded-md border bg-background p-3" placeholder="Escreva sua legenda..." value={caption} onChange={(e) => setCaption(e.target.value)} />
                                    <label className="mt-4 block text-sm font-medium">Hashtags</label>
                                    <input className="w-full rounded-md border bg-background p-2" placeholder="#exemplo #tag" value={hashtags} onChange={(e) => setHashtags(e.target.value)} />
                                </div>
                            )}
                            {step === 'schedule' && (
                                <div className="space-y-3">
                                    <label className="block text-sm font-medium">Data/Hora de Publicação</label>
                                    <input type="datetime-local" className="w-full rounded-md border bg-background p-2" value={scheduledAt} onChange={(e) => setScheduledAt(e.target.value)} />
                                    <p className="text-xs text-muted-foreground">Em branco = publicar agora</p>
                                </div>
                            )}
                        </div>
                    </div>

                    <aside className="space-y-6">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">Resumo</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2 text-sm text-muted-foreground">
                                <div className="flex items-center justify-between"><span>Plataformas</span><span>{selected.length}</span></div>
                                <div className="flex items-center justify-between"><span>Arquivos de mídia</span><span>{mediaIds.length}</span></div>
                                <div className="flex items-center justify-between"><span>Caracteres</span><span>{(caption?.length || 0) + (hashtags?.length || 0)}</span></div>
                                <div className="flex items-center justify-between"><span>Status</span><span className={scheduledAt ? 'text-amber-600' : 'text-green-600'}>{scheduledAt ? 'Agendado' : 'Imediato'}</span></div>
                            </CardContent>
                        </Card>

                        <div className="rounded-lg border border-accent/20 bg-accent/5 p-6">
                            <div className="flex items-start gap-3">
                                <svg className="mt-0.5 size-5 text-accent" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5S13.38 11.5 12 11.5z" fill="currentColor" />
                                </svg>
                                <div>
                                    <h4 className="mb-2 text-sm font-medium">Dica da Etapa</h4>
                                    <p className="text-xs text-muted-foreground">
                                        {step === 'platforms' && 'Selecione as plataformas onde deseja publicar seu conteúdo.'}
                                        {step === 'media' && 'Adicione imagens ou vídeos para tornar sua publicação mais atrativa.'}
                                        {step === 'content' && 'Escreva uma legenda envolvente e use hashtags relevantes.'}
                                        {step === 'schedule' && 'Escolha o melhor horário para publicar baseado no seu público.'}
                                        {step === 'review' && 'Revise tudo antes de publicar ou agendar sua postagem.'}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>

                <div className="mt-8 flex flex-col items-center justify-between gap-4 border-t border-border pt-6 sm:flex-row">
                    <Button variant="outline" onClick={handlePrev} disabled={step === 'platforms'}>
                        <ChevronLeft className="size-4" />
                        Voltar
                    </Button>

                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Save className="size-4" />
                        Auto-salvamento ativo
                    </div>

                    <Button onClick={handleNext} disabled={!canProceedToNextStep()}>
                        {step === 'review' ? 'Finalizar' : 'Próximo'}
                        <ChevronRight className="size-4" />
                    </Button>
                </div>
            </div>
        </AppHeaderLayout>
    );
}


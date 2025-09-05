import AppHeaderLayout from '@/layouts/app/app-header-layout';
import * as React from 'react';
import { router, type PageProps } from '@inertiajs/react';
import EmptyState from '@/components/empty-state';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

type Media = {
    id: number;
    name: string;
    url: string;
    mime_type: string;
    size_bytes: number;
    width: number | null;
    height: number | null;
    uploaded_at: string | null;
};

export default function MediaLibrary({ media = [], tags = [], counts = {}, pagination, filters }: PageProps<{ media: Media[]; tags: { id: number; name: string }[]; counts: any; pagination: any; filters: any }>) {
    const hasAny = media.length > 0;
    const fileInputRef = React.useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = React.useState(false);
    const [selected, setSelected] = React.useState<number[]>([]);
    const [type, setType] = React.useState<string>(filters?.type || '');
    const [q, setQ] = React.useState<string>(filters?.q || '');
    const [ratio, setRatio] = React.useState<string>(filters?.ratio || '');
    const [tag, setTag] = React.useState<number | ''>(filters?.tag || '');
    const [start, setStart] = React.useState<string>(filters?.start || '');
    const [end, setEnd] = React.useState<string>(filters?.end || '');
    const [newTag, setNewTag] = React.useState('');

    const csrf = () => (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const uploadFiles = async (files: FileList | null) => {
        if (!files || files.length === 0) return;
        setUploading(true);
        const form = new FormData();
        Array.from(files).forEach((f) => form.append('files[]', f));
        await fetch('/media/upload', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf() }, body: form });
        setUploading(false);
        window.location.reload();
    };

    const applyFilters = () => {
        const params: any = {};
        if (type) params.type = type;
        if (q) params.q = q;
        if (ratio) params.ratio = ratio;
        if (tag) params.tag = tag;
        if (start) params.start = start;
        if (end) params.end = end;
        router.get('/media', params, { preserveState: true, replace: true });
    };
    const clearFilters = () => {
        setType(''); setQ(''); setRatio(''); setTag(''); setStart(''); setEnd(''); router.get('/media', {}, { preserveState: true, replace: true });
    };
    const bulkTag = async () => {
        if (selected.length === 0 || !newTag.trim()) return;
        await fetch('/media/bulk-tag', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify({ ids: selected, tag: newTag.trim() }) });
        setNewTag('');
        router.reload({ preserveState: true });
    };
    const bulkDelete = async () => {
        if (selected.length === 0) return;
        await fetch('/media/bulk-delete', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify({ ids: selected }) });
        router.reload({ preserveState: false });
    };

    return (
        <AppHeaderLayout breadcrumbs={[{ title: 'Dashboard', href: '/dashboard' }, { title: 'Biblioteca', href: '/media' }]}> 
            <div className="flex gap-8">
                <aside className="hidden w-72 shrink-0 lg:block">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">Filtros</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 text-sm">
                            <div>
                                <div className="mb-2 font-medium">Tipo de Arquivo</div>
                                <div className="space-y-2 text-muted-foreground">
                                    <label className="flex items-center gap-2"><input type="radio" name="type" checked={type==='image'} onChange={() => setType('image')} /> Imagens ({counts?.images ?? 0})</label>
                                    <label className="flex items-center gap-2"><input type="radio" name="type" checked={type==='video'} onChange={() => setType('video')} /> Vídeos ({counts?.videos ?? 0})</label>
                                    <label className="flex items-center gap-2"><input type="radio" name="type" checked={type==='gif'} onChange={() => setType('gif')} /> GIFs ({counts?.gifs ?? 0})</label>
                                    <label className="flex items-center gap-2"><input type="radio" name="type" checked={type===''} onChange={() => setType('')} /> Todos ({counts?.all ?? 0})</label>
                                </div>
                            </div>
                            <div>
                                <div className="mb-2 font-medium">Data de Upload</div>
                                <div className="space-y-2 text-muted-foreground">
                                    <label className="block text-xs">Início</label>
                                    <input type="date" className="w-full rounded-md border bg-background p-2" value={start} onChange={(e)=>setStart(e.target.value)} />
                                    <label className="mt-2 block text-xs">Fim</label>
                                    <input type="date" className="w-full rounded-md border bg-background p-2" value={end} onChange={(e)=>setEnd(e.target.value)} />
                                </div>
                            </div>
                            <div>
                                <div className="mb-2 font-medium">Proporções</div>
                                <div className="space-y-2 text-muted-foreground">
                                    <label className="flex items-center gap-2"><input type="radio" name="ratio" checked={ratio==='1:1'} onChange={() => setRatio('1:1')} /> 1:1</label>
                                    <label className="flex items-center gap-2"><input type="radio" name="ratio" checked={ratio==='16:9'} onChange={() => setRatio('16:9')} /> 16:9</label>
                                    <label className="flex items-center gap-2"><input type="radio" name="ratio" checked={ratio==='9:16'} onChange={() => setRatio('9:16')} /> 9:16</label>
                                    <label className="flex items-center gap-2"><input type="radio" name="ratio" checked={ratio===''} onChange={() => setRatio('')} /> Qualquer</label>
                                </div>
                            </div>
                            <div>
                                <div className="mb-2 font-medium">Tags</div>
                                <select className="w-full rounded-md border bg-background p-2" value={tag} onChange={(e) => setTag(e.target.value ? Number(e.target.value) : '')}>
                                    <option value="">Todas</option>
                                    {tags.map((t) => (
                                        <option key={t.id} value={t.id}>{t.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="flex items-center justify-between">
                                <Button variant="ghost" onClick={clearFilters}>Limpar</Button>
                                <Button onClick={applyFilters}>Aplicar</Button>
                            </div>
                        </CardContent>
                    </Card>
                </aside>
                <div className="min-w-0 flex-1">
                    <div className="mb-6 flex items-center justify-between gap-3">
                        <Input className="max-w-xl" placeholder="Buscar por nome..." value={q} onChange={(e)=>setQ(e.target.value)} onKeyDown={(e)=> e.key==='Enter' && applyFilters()} />
                        <div className="flex items-center gap-2">
                            <Button variant="ghost">Lista</Button>
                            <Button>Grade</Button>
                        </div>
                    </div>
                    <Card className="mb-6">
                        <CardContent className="flex flex-col items-center justify-center gap-2 p-8 text-center">
                            <div className="rounded-full bg-muted p-3">⬆️</div>
                            <div className="text-lg font-medium">Envie suas mídias</div>
                            <div className="text-sm text-muted-foreground">Arraste e solte ou clique para selecionar imagens e vídeos</div>
                            <input ref={fileInputRef} type="file" multiple hidden onChange={(e) => uploadFiles(e.target.files)} />
                            <Button className="mt-2" onClick={() => fileInputRef.current?.click()} disabled={uploading}>
                                {uploading ? 'Enviando...' : 'Selecionar Arquivos'}
                            </Button>
                        </CardContent>
                    </Card>

                    <div className="mb-4 flex flex-wrap items-center justify-between gap-3 text-sm">
                        <div className="flex items-center gap-2">
                            <label className="flex items-center gap-2"><input type="checkbox" checked={selected.length === media.length && media.length>0} onChange={(e)=> setSelected(e.target.checked ? media.map(m=>m.id) : [])} /> Selecionar Todos</label>
                            <span className="text-muted-foreground">{selected.length} selecionados</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <input value={newTag} onChange={(e)=>setNewTag(e.target.value)} placeholder="Nova tag" className="rounded-md border bg-background p-2" />
                            <Button variant="ghost" onClick={bulkTag}>Adicionar Tag</Button>
                            <Button variant="destructive" onClick={bulkDelete}>Excluir</Button>
                        </div>
                    </div>

                    {!hasAny ? (
                        <EmptyState title="Sua biblioteca está vazia" description="Faça upload de imagens e vídeos para usar em seus posts." />
                    ) : (
                        <div className="grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-4">
                            {media.map((m) => {
                                const isSel = selected.includes(m.id);
                                return (
                                    <Card key={m.id} className={"overflow-hidden " + (isSel ? 'ring-2 ring-accent' : '')}>
                                        <CardContent className="p-0">
                                            <button type="button" className="block w-full" onClick={()=> setSelected(isSel ? selected.filter(id=>id!==m.id) : [...selected, m.id])}>
                                                {(m.mime_type || '').startsWith('video/') ? (
                                                    <video src={m.url} className="h-40 w-full object-cover" muted playsInline preload="metadata" />
                                                ) : (
                                                    <img src={m.url} alt={m.name} className="h-40 w-full object-cover" />
                                                )}
                                            </button>
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </div>
                    )}

                    {(pagination?.last_page ?? 1) > 1 && (
                        <div className="mt-6 flex items-center justify-center gap-2 text-sm">
                            {Array.from({ length: pagination.last_page }, (_, i) => i + 1).map((p) => (
                                <button key={p} className={'rounded px-3 py-1 ' + (p === pagination.current_page ? 'bg-accent text-accent-foreground' : 'bg-muted')} onClick={() => router.get('/media', { ...filters, page: p }, { preserveState: true, replace: true })}>
                                    {p}
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppHeaderLayout>
    );
}

import * as React from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

type MediaItem = { id: number; url: string; name: string; mime_type: string; width?: number | null; height?: number | null };

export default function MediaPicker({ value, onChange }: { value: number[]; onChange: (ids: number[]) => void }) {
    const [items, setItems] = React.useState<MediaItem[]>([]);
    const [loading, setLoading] = React.useState(true);

    React.useEffect(() => {
        (async () => {
            setLoading(true);
            const res = await fetch('/media/list?per=48');
            const data = await res.json();
            setItems(data.data || []);
            setLoading(false);
        })();
    }, []);

    const toggle = (id: number) => {
        if (value.includes(id)) onChange(value.filter((x) => x !== id));
        else onChange([...value, id]);
    };
    const move = (id: number, dir: -1 | 1) => {
        const idx = value.indexOf(id);
        if (idx < 0) return;
        const next = [...value];
        const j = idx + dir;
        if (j < 0 || j >= next.length) return;
        [next[idx], next[j]] = [next[j], next[idx]];
        onChange(next);
    };

    const [dragId, setDragId] = React.useState<number | null>(null);
    const onDragStart = (id: number) => (e: React.DragEvent) => {
        setDragId(id);
        e.dataTransfer.effectAllowed = 'move';
    };
    const onDragOver = (e: React.DragEvent) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    };
    const onDrop = (targetId: number) => (e: React.DragEvent) => {
        e.preventDefault();
        if (dragId == null || dragId === targetId) return;
        const order = [...value];
        const from = order.indexOf(dragId);
        const to = order.indexOf(targetId);
        if (from < 0 || to < 0) return;
        order.splice(to, 0, ...order.splice(from, 1));
        onChange(order);
        setDragId(null);
    };

    const isVideo = (m: MediaItem) => (m.mime_type || '').startsWith('video/');
    const Thumb = ({ m, className }: { m: MediaItem; className?: string }) => (
        isVideo(m) ? (
            <video src={m.url} className={className} muted playsInline preload="metadata" />
        ) : (
            <img src={m.url} alt={m.name} className={className} />
        )
    );

    return (
        <div className="space-y-3">
            <div className="text-sm text-muted-foreground">Selecione as mídias que deseja anexar ao post. Clique para selecionar; arraste para reordenar.</div>
            {value.length > 0 && (
                <div className="flex items-center gap-2 overflow-x-auto rounded-md border bg-card p-2">
                    {value.map((id) => {
                        const m = items.find((x) => x.id === id);
                        if (!m) return null;
                        return (
                            <div key={id} draggable onDragStart={onDragStart(id)} onDragOver={onDragOver} onDrop={onDrop(id)} className="relative h-16 w-16 shrink-0 cursor-move overflow-hidden rounded">
                                <Thumb m={m} className="h-full w-full object-cover" />
                                <button type="button" className="absolute right-1 top-1 rounded bg-black/50 px-1 text-xs text-white" onClick={() => toggle(id)}>x</button>
                            </div>
                        );
                    })}
                </div>
            )}
            {loading ? (
                <div className="text-sm text-muted-foreground">Carregando mídia...</div>
            ) : (
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    {items.map((m) => {
                        const selected = value.includes(m.id);
                        return (
                            <Card key={m.id} className={selected ? 'ring-2 ring-accent' : ''}>
                                <CardContent className="p-0">
                                    <button type="button" className="block w-full" onClick={() => toggle(m.id)}>
                                        <Thumb m={m} className="h-36 w-full object-cover" />
                                    </button>
                                    {selected && (
                                        <div className="flex items-center justify-between p-2">
                                            <Button size="sm" variant="ghost" onClick={() => move(m.id, -1)}>↑</Button>
                                            <Button size="sm" variant="ghost" onClick={() => move(m.id, 1)}>↓</Button>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
            )}
            {value.length > 0 && (<div className="text-xs text-muted-foreground">Ordem: {value.join(', ')}</div>)}
        </div>
    );
}

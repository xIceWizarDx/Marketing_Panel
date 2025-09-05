import { Badge } from '@/components/ui/badge';

export type PlatformItem = {
    id: number;
    platform: string;
    account_username?: string | null;
    followers?: number | null;
    connected?: boolean;
};

export default function PlatformCard({ item, selected, onToggle }: { item: PlatformItem; selected: boolean; onToggle: () => void }) {
    return (
        <button
            type="button"
            onClick={onToggle}
            className="flex w-full items-center justify-between rounded-lg border bg-card p-4 text-left transition-smooth hover:shadow-soft"
        >
            <div>
                <div className="flex items-center gap-2">
                    <div className="size-8 rounded-md bg-muted" />
                    <div className="font-medium capitalize">{item.platform}</div>
                    <Badge variant={item.connected ? 'default' : 'secondary'}>{item.connected ? 'Conectado' : 'Desconectado'}</Badge>
                </div>
                <div className="mt-1 text-xs text-muted-foreground">{item.account_username || '@conta'}</div>
                {item.followers != null && <div className="mt-1 text-xs text-muted-foreground">{Intl.NumberFormat('pt-BR').format(item.followers)} seguidores</div>}
            </div>
            <input type="checkbox" className="size-4" checked={selected} readOnly />
        </button>
    );
}


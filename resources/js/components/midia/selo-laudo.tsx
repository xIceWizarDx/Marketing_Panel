import { AlertTriangle, CheckCircle2, HelpCircle, XCircle } from 'lucide-react';

import { cn } from '@/lib/utils';
import { type Laudo, type NivelDoAchado } from '@/types';

const aparencia: Record<NivelDoAchado, { Icone: typeof CheckCircle2; cor: string }> = {
    ok: { Icone: CheckCircle2, cor: 'var(--saude-ok)' },
    atencao: { Icone: AlertTriangle, cor: 'var(--saude-atencao)' },
    erro: { Icone: XCircle, cor: 'var(--saude-erro)' },
};

export function IconeDoNivel({ nivel, className }: { nivel: NivelDoAchado; className?: string }) {
    const { Icone, cor } = aparencia[nivel];

    return <Icone className={cn('size-4 shrink-0', className)} style={{ color: cor }} aria-hidden="true" />;
}

/**
 * Resumo do laudo em uma linha: "publica em 3 de 4 redes".
 *
 * A contagem vem antes do detalhe de propósito — na lista, o que importa é
 * saber num relance se o arquivo serve.
 */
export default function SeloLaudo({ laudo }: { laudo: Laudo | null }) {
    if (!laudo || !laudo.disponivel) {
        return (
            <span className="text-muted-foreground inline-flex items-center gap-1.5 text-xs">
                <HelpCircle className="size-3.5 shrink-0" aria-hidden="true" />
                Sem análise
            </span>
        );
    }

    const redes = Object.entries(laudo.por_rede);
    const aceitam = redes.filter(([, achados]) => !achados.some((a) => a.nivel === 'erro'));
    const todas = aceitam.length === redes.length;

    return (
        <span
            className="inline-flex items-center gap-1.5 text-xs font-medium"
            style={{ color: todas ? 'var(--saude-ok)' : 'var(--saude-atencao)' }}
        >
            <IconeDoNivel nivel={todas ? 'ok' : 'atencao'} className="size-3.5" />
            {todas ? 'Publica nas 4 redes' : `Publica em ${aceitam.length} de ${redes.length}`}
        </span>
    );
}

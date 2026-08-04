import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { fecharAviso, mostrarAviso, ouvirAvisos, type Recado, type Tom } from '@/lib/avisos';
import { type DadosCompartilhados } from '@/types';

const TONS: readonly Tom[] = ['sucesso', 'erro', 'aviso'];

/**
 * Liga a fila de avisos ao que vem do servidor.
 *
 * ⭐ **É aqui que se mexe** se o backend passar a mandar os recados de outro
 * jeito. O componente que desenha não sabe de onde veio a mensagem, e a fila em
 * `lib/avisos.ts` não sabe que existe Inertia — cada um ignora o que não é
 * problema seu.
 */
export function useAvisos(): { recados: Recado[]; fechar: (id: number) => void } {
    const { avisos } = usePage<DadosCompartilhados>().props;
    const [recados, setRecados] = useState<Recado[]>([]);

    useEffect(() => ouvirAvisos(setRecados), []);

    // ⚠️ O gatilho é o CONTEÚDO, não o objeto. As props de página chegam novas a
    // cada navegação; comparar por identidade repetiria o mesmo aviso a cada
    // clique. E comparar por conteúdo faz o recado repetido aparecer de novo —
    // que é o certo: dois envios com o mesmo erro são dois avisos.
    const assinatura = JSON.stringify(avisos ?? {});

    useEffect(() => {
        TONS.forEach((tom) => {
            const texto = avisos?.[tom];

            if (texto) mostrarAviso(tom, texto);
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [assinatura]);

    return { recados, fechar: fecharAviso };
}

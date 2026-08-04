import { Link, usePage } from '@inertiajs/react';
import { Eye } from 'lucide-react';

import { type DadosCompartilhados } from '@/types';

/**
 * Tarja fixa no topo enquanto o administrador esta vendo a conta de um cliente.
 *
 * Fica sempre visivel, em cor de alerta, de proposito: o risco real da
 * impersonacao e o admin esquecer que nao esta na propria conta e fazer uma
 * acao em nome de outra pessoa.
 */
export default function TarjaImpersonacao() {
    const { impersonacao } = usePage<DadosCompartilhados>().props;

    if (!impersonacao) {
        return null;
    }

    return (
        <div
            role="status"
            className="sticky top-0 z-40 flex flex-wrap items-center gap-x-3 gap-y-1.5 bg-[color:var(--saude-atencao)] px-4 py-2 text-sm text-black"
        >
            <span className="flex items-center gap-2 font-medium">
                <Eye className="size-4 shrink-0" aria-hidden="true" />
                Modo suporte
            </span>

            <span className="min-w-0 flex-1">
                Você ({impersonacao.adminNome}) está vendo o painel de <strong>{impersonacao.usuarioNome}</strong>. Tudo o que fizer aqui vale
                como ação dessa pessoa.
            </span>

            <Link
                href={route('impersonacao.sair')}
                method="post"
                as="button"
                className="shrink-0 rounded-md bg-black/85 px-3 py-1.5 font-medium text-white transition-colors hover:bg-black focus-visible:ring-2 focus-visible:ring-black focus-visible:outline-none"
            >
                Voltar para minha conta
            </Link>
        </div>
    );
}

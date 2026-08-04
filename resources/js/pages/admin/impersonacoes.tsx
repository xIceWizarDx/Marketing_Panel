import { Head, Link } from '@inertiajs/react';
import { Eye } from 'lucide-react';

import CabecalhoDePagina from '@/components/cabecalho-de-pagina';
import LayoutPainel from '@/layouts/painel';
import { type Paginado } from '@/types';

interface Registro {
    id: number;
    admin: string;
    usuario: string;
    usuarioUlid: string;
    iniciadaEm: string | null;
    finalizadaEm: string | null;
    duracao: string | null;
    emAndamento: boolean;
    ip: string | null;
}

const quando = (iso: string | null) =>
    iso ? new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) : '—';

export default function Impersonacoes({ registros, emAndamento }: { registros: Paginado<Registro>; emAndamento: number }) {
    return (
        <LayoutPainel
            migalhas={[
                { titulo: 'Visão geral', url: '/admin/painel' },
                { titulo: 'Impersonações', url: '/admin/impersonacoes' },
            ]}
        >
            <Head title="Impersonações" />

            <CabecalhoDePagina
                titulo="Impersonações"
                descricao="Todo acesso de suporte à conta de um cliente fica registrado aqui. Este histórico não pode ser editado nem apagado."
            />

            {emAndamento > 0 && (
                <div
                    role="status"
                    className="mb-5 rounded-md border border-[color:var(--saude-atencao)]/30 bg-[color:var(--saude-atencao)]/10 px-3 py-2.5 text-sm"
                >
                    {emAndamento === 1 ? 'Há 1 acesso em andamento agora.' : `Há ${emAndamento} acessos em andamento agora.`}
                </div>
            )}

            {registros.data.length === 0 ? (
                <div className="border-border bg-card rounded-lg border p-8 text-center">
                    <Eye className="text-muted-foreground mx-auto size-7" aria-hidden="true" />
                    <p className="mt-3 text-sm font-medium">Nenhum acesso de suporte até agora</p>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Quando alguém do time entrar na conta de um cliente, o registro aparece aqui.
                    </p>
                </div>
            ) : (
                <ul className="space-y-2">
                    {registros.data.map((registro) => (
                        <li key={registro.id} className="border-border bg-card rounded-lg border p-3.5">
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div className="min-w-0">
                                    <p className="text-sm">
                                        <span className="font-medium">{registro.admin}</span>
                                        <span className="text-muted-foreground"> entrou na conta de </span>
                                        <span className="font-medium">{registro.usuario}</span>
                                    </p>
                                    <p className="text-muted-foreground mt-0.5 text-xs">
                                        {quando(registro.iniciadaEm)}
                                        {registro.duracao && ` · durou ${registro.duracao}`}
                                        {registro.ip && ` · ${registro.ip}`}
                                    </p>
                                </div>

                                {registro.emAndamento && (
                                    <span className="shrink-0 self-start rounded-full bg-[color:var(--saude-atencao)]/10 px-2 py-0.5 text-xs text-[color:var(--saude-atencao)]">
                                        em andamento
                                    </span>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            {registros.last_page > 1 && (
                <nav aria-label="Páginas" className="mt-6 flex flex-wrap gap-1.5">
                    {registros.links.map((link, indice) => (
                        <Link
                            key={indice}
                            href={link.url ?? '#'}
                            preserveScroll
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                link.active ? 'border-[color:var(--accent)] font-medium' : 'border-border text-muted-foreground'
                            } ${!link.url ? 'pointer-events-none opacity-40' : ''}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </nav>
            )}
        </LayoutPainel>
    );
}

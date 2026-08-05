import { Head, Link, router } from '@inertiajs/react';
import { Eye, Search, ShieldCheck, ShieldOff } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import CabecalhoDePagina from '@/components/cabecalho-de-pagina';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import LayoutPainel from '@/layouts/painel';
import { type Paginado } from '@/types';

interface Cliente {
    ulid: string;
    nome: string;
    email: string;
    ativo: boolean;
    emailVerificado: boolean;
    criadoEm: string | null;
    midias: number;
}

export default function Clientes({ clientes, busca }: { clientes: Paginado<Cliente>; busca: string }) {
    const [termo, setTermo] = useState(busca);
    const [aImpersonar, setAImpersonar] = useState<Cliente | null>(null);

    const buscar: FormEventHandler = (evento) => {
        evento.preventDefault();
        router.get(route('admin.clientes.listar'), { busca: termo }, { preserveState: true, replace: true });
    };

    return (
        <LayoutPainel
            migalhas={[
                { titulo: 'Visão geral', url: '/admin/painel' },
                { titulo: 'Clientes', url: '/admin/clientes' },
            ]}
        >
            <Head title="Clientes" />

            <CabecalhoDePagina titulo="Clientes" descricao="Quem usa a plataforma. Daqui você entra na conta para dar suporte." />

            <form onSubmit={buscar} className="mb-5 flex gap-2">
                <div className="relative flex-1 sm:max-w-xs">
                    <Search
                        className="text-muted-foreground pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2"
                        aria-hidden="true"
                    />
                    <Input
                        value={termo}
                        onChange={(e) => setTermo(e.target.value)}
                        placeholder="Nome ou e-mail"
                        aria-label="Buscar cliente"
                        className="pl-8"
                    />
                </div>
                <Button type="submit" variant="secondary">
                    Buscar
                </Button>
            </form>

            {clientes.data.length === 0 ? (
                <div className="border-border bg-card rounded-lg border p-8 text-center">
                    <p className="text-sm font-medium">{busca ? 'Ninguém encontrado' : 'Ainda não há clientes'}</p>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {busca ? 'Tente outro nome ou e-mail.' : 'Assim que alguém criar uma conta, aparece aqui.'}
                    </p>
                </div>
            ) : (
                /* Cartões e não tabela: no celular a tabela viraria rolagem
                   horizontal, e o menu de ações some (DEC-38). */
                <ul className="space-y-2">
                    {clientes.data.map((cliente) => (
                        <li key={cliente.ulid} className="border-border bg-card rounded-lg border p-3.5">
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div className="min-w-0">
                                    <p className="flex items-center gap-2 font-medium">
                                        <span className="truncate">{cliente.nome}</span>
                                        {!cliente.ativo && (
                                            <span className="shrink-0 rounded-md bg-[color:var(--saude-erro)]/10 px-2 py-0.5 text-xs font-normal text-[color:var(--saude-erro)]">
                                                Sem acesso
                                            </span>
                                        )}
                                        {cliente.ativo && !cliente.emailVerificado && (
                                            <span className="shrink-0 rounded-md bg-[color:var(--saude-atencao)]/10 px-2 py-0.5 text-xs font-normal text-[color:var(--saude-atencao)]">
                                                E-mail não confirmado
                                            </span>
                                        )}
                                    </p>
                                    <p className="text-muted-foreground truncate text-sm">{cliente.email}</p>
                                    <p className="text-muted-foreground mt-0.5 text-xs">
                                        {cliente.midias} {cliente.midias === 1 ? 'mídia' : 'mídias'}
                                    </p>
                                </div>

                                <div className="flex shrink-0 items-center gap-1.5">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => router.patch(route('admin.clientes.acesso', cliente.ulid), {}, { preserveScroll: true })}
                                    >
                                        {cliente.ativo ? (
                                            <ShieldOff className="mr-1.5 size-4" aria-hidden="true" />
                                        ) : (
                                            <ShieldCheck className="mr-1.5 size-4" aria-hidden="true" />
                                        )}
                                        {cliente.ativo ? 'Tirar acesso' : 'Devolver acesso'}
                                    </Button>

                                    <Button variant="secondary" size="sm" onClick={() => setAImpersonar(cliente)}>
                                        <Eye className="mr-1.5 size-4" aria-hidden="true" />
                                        Ver como
                                    </Button>
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            {clientes.last_page > 1 && (
                <nav aria-label="Páginas" className="mt-6 flex flex-wrap gap-1.5">
                    {clientes.links.map((link, indice) => (
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

            <Dialog open={!!aImpersonar} onOpenChange={(estado) => !estado && setAImpersonar(null)}>
                <DialogContent>
                    <DialogTitle>Entrar na conta de {aImpersonar?.nome}?</DialogTitle>
                    <DialogDescription>
                        Você vai ver exatamente o que essa pessoa vê, e tudo o que fizer conta como ação dela. O acesso fica registrado. Vamos pedir
                        sua senha antes de continuar.
                    </DialogDescription>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="secondary">Cancelar</Button>
                        </DialogClose>
                        <Button onClick={() => aImpersonar && router.post(route('admin.impersonar', aImpersonar.ulid))}>Entrar na conta</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </LayoutPainel>
    );
}

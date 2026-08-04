import { Head, usePage } from '@inertiajs/react';

import CabecalhoDePagina from '@/components/cabecalho-de-pagina';
import LayoutPainel from '@/layouts/painel';
import { type DadosCompartilhados } from '@/types';

export default function VisaoGeralDoAdmin() {
    const { auth } = usePage<DadosCompartilhados>().props;
    const primeiroNome = auth.usuario?.nome.split(' ')[0] ?? '';

    return (
        <LayoutPainel migalhas={[{ titulo: 'Visão geral', url: '/admin/painel' }]}>
            <Head title="Painel do administrador" />

            <CabecalhoDePagina titulo={`Olá, ${primeiroNome}`} descricao="Painel do administrador da plataforma." />

            <div className="border-border bg-card rounded-lg border p-6 sm:p-8">
                <h2 className="text-base font-medium">Painel em construção</h2>
                <p className="text-muted-foreground mt-1.5 max-w-prose text-sm">
                    Os números da plataforma e a lista de clientes entram junto com os próximos módulos.
                </p>
            </div>
        </LayoutPainel>
    );
}

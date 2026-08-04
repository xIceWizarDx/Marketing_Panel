import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import LinkTexto from '@/components/link-texto';
import { Button } from '@/components/ui/button';
import LayoutAcesso from '@/layouts/acesso';

export default function VerificarEmail({ aviso }: { aviso?: string }) {
    const { post, processing } = useForm({});

    const enviar: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('verificacao.reenviar'));
    };

    return (
        <LayoutAcesso titulo="Confirme seu e-mail" descricao="Enviamos um link para o e-mail que você cadastrou. Abra a mensagem e clique nele.">
            <Head title="Confirmar e-mail" />

            {aviso === 'link-de-verificacao-enviado' && (
                <div
                    role="status"
                    className="mb-4 rounded-md border border-[color:var(--saude-ok)]/30 bg-[color:var(--saude-ok)]/10 px-3 py-2 text-sm text-[color:var(--saude-ok)]"
                >
                    Enviamos um link novo. Se não chegar em alguns minutos, confira a caixa de spam.
                </div>
            )}

            <form onSubmit={enviar} className="flex flex-col gap-4">
                <Button type="submit" variant="secondary" className="h-11 w-full" disabled={processing}>
                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                    Reenviar o link
                </Button>

                <LinkTexto href={route('sair')} method="post" as="button" className="text-muted-foreground mx-auto text-sm">
                    Sair
                </LinkTexto>
            </form>
        </LayoutAcesso>
    );
}

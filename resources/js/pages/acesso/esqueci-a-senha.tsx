import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import ErroDeCampo from '@/components/erro-de-campo';
import LinkTexto from '@/components/link-texto';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import LayoutAcesso from '@/layouts/acesso';

export default function EsqueciASenha({ aviso }: { aviso?: string }) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const enviar: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('senha.enviarLink'));
    };

    return (
        <LayoutAcesso titulo="Esqueci minha senha" descricao="Informe seu e-mail e enviamos um link para você criar uma senha nova.">
            <Head title="Esqueci minha senha" />

            {aviso && (
                <div
                    role="status"
                    className="mb-4 rounded-md border border-[color:var(--saude-ok)]/30 bg-[color:var(--saude-ok)]/10 px-3 py-2 text-sm text-[color:var(--saude-ok)]"
                >
                    {aviso}
                </div>
            )}

            <form className="flex flex-col gap-5" onSubmit={enviar}>
                <div className="grid gap-2">
                    <Label htmlFor="email">E-mail</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autoFocus
                        autoComplete="email"
                        inputMode="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        placeholder="voce@exemplo.com.br"
                        aria-invalid={!!errors.email}
                    />
                    <ErroDeCampo mensagem={errors.email} />
                </div>

                <Button type="submit" className="h-11 w-full" disabled={processing}>
                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                    Enviar link
                </Button>
            </form>

            <p className="text-muted-foreground mt-6 text-center text-sm">
                Lembrou a senha? <LinkTexto href={route('entrar')}>Voltar para a entrada</LinkTexto>
            </p>
        </LayoutAcesso>
    );
}

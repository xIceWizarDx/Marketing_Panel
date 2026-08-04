import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import ErroDeCampo from '@/components/erro-de-campo';
import CampoSenha from '@/components/campo-senha';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import LayoutAcesso from '@/layouts/acesso';

export default function ConfirmarSenha() {
    const { data, setData, post, processing, errors, reset } = useForm({ password: '' });

    const enviar: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('senha.confirmar'), { onFinish: () => reset('password') });
    };

    return (
        <LayoutAcesso
            titulo="Confirme sua senha"
            descricao="Esta parte do sistema é protegida. Digite sua senha de novo para continuar."
        >
            <Head title="Confirmar senha" />

            <form className="flex flex-col gap-5" onSubmit={enviar}>
                <div className="grid gap-2">
                    <Label htmlFor="password">Senha</Label>
                    <CampoSenha
                        id="password"
                        name="password"
                        required
                        autoFocus
                        autoComplete="current-password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder="Sua senha"
                        aria-invalid={!!errors.password}
                    />
                    <ErroDeCampo mensagem={errors.password} />
                </div>

                <Button type="submit" className="h-11 w-full" disabled={processing}>
                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                    Confirmar
                </Button>
            </form>
        </LayoutAcesso>
    );
}

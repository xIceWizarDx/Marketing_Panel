import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import ErroDeCampo from '@/components/erro-de-campo';
import CampoSenha from '@/components/campo-senha';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import LayoutAcesso from '@/layouts/acesso';

interface Props {
    token: string;
    email: string;
}

export default function RedefinirSenha({ token, email }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const enviar: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('senha.salvar'), { onFinish: () => reset('password', 'password_confirmation') });
    };

    return (
        <LayoutAcesso titulo="Criar uma senha nova" descricao="Escolha uma senha que você não use em outro lugar.">
            <Head title="Criar senha nova" />

            <form className="flex flex-col gap-5" onSubmit={enviar}>
                <div className="grid gap-2">
                    <Label htmlFor="email">E-mail</Label>
                    {/* Vem do link do e-mail e nao pode ser trocado aqui — trocar
                        abriria caminho pra redefinir a senha de outra conta. */}
                    <Input id="email" type="email" name="email" autoComplete="email" value={data.email} readOnly className="bg-muted" />
                    <ErroDeCampo mensagem={errors.email} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password">Nova senha</Label>
                    <CampoSenha
                        id="password"
                        name="password"
                        required
                        autoFocus
                        autoComplete="new-password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder="Pelo menos 12 caracteres"
                        aria-invalid={!!errors.password}
                    />
                    <ErroDeCampo mensagem={errors.password} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation">Repita a nova senha</Label>
                    <CampoSenha
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        autoComplete="new-password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        placeholder="A mesma senha de novo"
                        aria-invalid={!!errors.password_confirmation}
                    />
                    <ErroDeCampo mensagem={errors.password_confirmation} />
                </div>

                <Button type="submit" className="h-11 w-full" disabled={processing}>
                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                    Salvar nova senha
                </Button>

                <p className="text-muted-foreground text-xs">
                    Ao salvar, você sai das sessões abertas em outros aparelhos. É proposital: se alguém tinha acesso, perde agora.
                </p>
            </form>
        </LayoutAcesso>
    );
}

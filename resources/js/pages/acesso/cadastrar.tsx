import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import CampoSenha from '@/components/campo-senha';
import ErroDeCampo from '@/components/erro-de-campo';
import LinkTexto from '@/components/link-texto';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import LayoutAcesso from '@/layouts/acesso';

export default function Cadastrar() {
    const { data, setData, post, processing, errors, reset } = useForm({
        nome: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const enviar: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('cadastrar'), { onFinish: () => reset('password', 'password_confirmation') });
    };

    return (
        <LayoutAcesso titulo="Criar sua conta" descricao="Leva menos de um minuto.">
            <Head title="Criar conta" />

            <form className="flex flex-col gap-5" onSubmit={enviar}>
                <div className="grid gap-2">
                    <Label htmlFor="nome">Nome</Label>
                    <Input
                        id="nome"
                        type="text"
                        required
                        autoFocus
                        autoComplete="name"
                        value={data.nome}
                        onChange={(e) => setData('nome', e.target.value)}
                        disabled={processing}
                        placeholder="Como podemos te chamar"
                        aria-invalid={!!errors.nome}
                    />
                    <ErroDeCampo mensagem={errors.nome} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="email">E-mail</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autoComplete="email"
                        inputMode="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        disabled={processing}
                        placeholder="voce@exemplo.com.br"
                        aria-invalid={!!errors.email}
                    />
                    <ErroDeCampo mensagem={errors.email} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password">Senha</Label>
                    <CampoSenha
                        id="password"
                        required
                        autoComplete="new-password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        disabled={processing}
                        placeholder="Pelo menos 12 caracteres"
                        aria-invalid={!!errors.password}
                    />
                    <ErroDeCampo mensagem={errors.password} />
                    <p className="text-muted-foreground text-xs">
                        Use no mínimo 12 caracteres. Uma frase que só você sabe funciona melhor que uma palavra difícil.
                    </p>
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation">Repita a senha</Label>
                    <CampoSenha
                        id="password_confirmation"
                        required
                        autoComplete="new-password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        disabled={processing}
                        placeholder="A mesma senha de novo"
                        aria-invalid={!!errors.password_confirmation}
                    />
                    <ErroDeCampo mensagem={errors.password_confirmation} />
                </div>

                <Button type="submit" className="h-11 w-full" disabled={processing}>
                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                    Criar conta
                </Button>
            </form>

            <p className="text-muted-foreground mt-6 text-center text-sm">
                Já tem conta? <LinkTexto href={route('entrar')}>Entrar</LinkTexto>
            </p>
        </LayoutAcesso>
    );
}

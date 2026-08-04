import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import ErroDeCampo from '@/components/erro-de-campo';
import LinkTexto from '@/components/link-texto';
import CampoSenha from '@/components/campo-senha';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import LayoutAcesso from '@/layouts/acesso';

interface Props {
    aviso?: string;
    podeRedefinirSenha: boolean;
}

export default function Entrar({ aviso, podeRedefinirSenha }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        email: string;
        password: string;
        lembrar: boolean;
    }>({
        email: '',
        password: '',
        lembrar: false,
    });

    const enviar: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('entrar'), { onFinish: () => reset('password') });
    };

    return (
        <LayoutAcesso titulo="Entrar na sua conta" descricao="Informe seu e-mail e senha para continuar.">
            <Head title="Entrar" />

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

                <div className="grid gap-2">
                    <div className="flex items-center justify-between gap-2">
                        <Label htmlFor="password">Senha</Label>
                        {podeRedefinirSenha && (
                            <LinkTexto href={route('senha.solicitar')} className="text-xs">
                                Esqueci minha senha
                            </LinkTexto>
                        )}
                    </div>
                    <CampoSenha
                        id="password"
                        required
                        autoComplete="current-password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder="Sua senha"
                        aria-invalid={!!errors.password}
                    />
                    <ErroDeCampo mensagem={errors.password} />
                </div>

                <div className="flex items-center gap-3">
                    <Checkbox id="lembrar" name="lembrar" checked={data.lembrar} onCheckedChange={(v) => setData('lembrar', v === true)} />
                    <Label htmlFor="lembrar" className="text-muted-foreground font-normal">
                        Continuar conectado neste aparelho
                    </Label>
                </div>

                {/* h-11: alvo de toque confortavel no celular sem parecer inflado no desktop */}
                <Button type="submit" className="h-11 w-full" disabled={processing}>
                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                    Entrar
                </Button>
            </form>

            <p className="text-muted-foreground mt-6 text-center text-sm">
                Ainda não tem conta? <LinkTexto href={route('cadastrar')}>Criar conta</LinkTexto>
            </p>
        </LayoutAcesso>
    );
}

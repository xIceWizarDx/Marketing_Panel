import { Transition } from '@headlessui/react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import ApagarConta from '@/components/apagar-conta';
import ErroDeCampo from '@/components/erro-de-campo';
import TituloDeSecao from '@/components/titulo-de-secao';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import LayoutMinhaConta from '@/layouts/minha-conta';
import { type DadosCompartilhados } from '@/types';

export default function Perfil({ precisaVerificarEmail }: { precisaVerificarEmail: boolean }) {
    const { auth } = usePage<DadosCompartilhados>().props;

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        nome: auth.usuario?.nome ?? '',
        email: auth.usuario?.email ?? '',
    });

    const enviar: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('minha-conta.perfil.atualizar'), { preserveScroll: true });
    };

    return (
        <LayoutMinhaConta>
            <Head title="Meu perfil" />

            <section className="space-y-5">
                <TituloDeSecao titulo="Perfil" descricao="Seu nome e o e-mail que você usa para entrar." />

                <form onSubmit={enviar} className="space-y-5">
                    <div className="grid gap-2">
                        <Label htmlFor="nome">Nome</Label>
                        <Input
                            id="nome"
                            required
                            autoComplete="name"
                            value={data.nome}
                            onChange={(e) => setData('nome', e.target.value)}
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
                            aria-invalid={!!errors.email}
                        />
                        <ErroDeCampo mensagem={errors.email} />
                        <p className="text-muted-foreground text-xs">
                            Se você trocar o e-mail, vamos pedir a confirmação do novo endereço antes de liberar tudo de novo.
                        </p>
                    </div>

                    {precisaVerificarEmail && !auth.usuario?.emailVerificado && (
                        <div className="rounded-md border border-[color:var(--saude-atencao)]/30 bg-[color:var(--saude-atencao)]/10 px-3 py-2.5 text-sm">
                            Seu e-mail ainda não foi confirmado.{' '}
                            <Link href={route('verificacao.reenviar')} method="post" as="button" className="font-medium underline underline-offset-4">
                                Reenviar o link de confirmação
                            </Link>
                        </div>
                    )}

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={processing}>
                            Salvar
                        </Button>

                        <Transition
                            show={recentlySuccessful}
                            enter="transition ease-in-out"
                            enterFrom="opacity-0"
                            leave="transition ease-in-out"
                            leaveTo="opacity-0"
                        >
                            <p className="text-sm text-[color:var(--saude-ok)]">Salvo.</p>
                        </Transition>
                    </div>
                </form>
            </section>

            <ApagarConta />
        </LayoutMinhaConta>
    );
}

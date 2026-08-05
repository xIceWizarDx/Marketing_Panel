import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';

import CampoSenha from '@/components/campo-senha';
import ErroDeCampo from '@/components/erro-de-campo';
import TituloDeSecao from '@/components/titulo-de-secao';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import LayoutMinhaConta from '@/layouts/minha-conta';

export default function Senha() {
    const campoSenhaAtual = useRef<HTMLInputElement>(null);
    const campoSenhaNova = useRef<HTMLInputElement>(null);

    const { data, setData, put, errors, processing, reset, recentlySuccessful } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const enviar: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('minha-conta.senha.atualizar'), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (erros) => {
                // Devolve o foco pro campo que falhou — quem usa teclado ou leitor
                // de tela nao precisa procurar onde foi o erro.
                if (erros.password) {
                    reset('password', 'password_confirmation');
                    campoSenhaNova.current?.focus();
                }

                if (erros.current_password) {
                    reset('current_password');
                    campoSenhaAtual.current?.focus();
                }
            },
        });
    };

    return (
        <LayoutMinhaConta>
            <Head title="Senha" />

            <section className="space-y-5">
                <TituloDeSecao titulo="Trocar a senha" descricao="Use uma senha longa que você não repete em outro site." />

                <form onSubmit={enviar} className="space-y-5">
                    <div className="grid gap-2">
                        <Label htmlFor="current_password">Senha atual</Label>
                        <CampoSenha
                            id="current_password"
                            ref={campoSenhaAtual}
                            required
                            autoComplete="current-password"
                            value={data.current_password}
                            onChange={(e) => setData('current_password', e.target.value)}
                            aria-invalid={!!errors.current_password}
                        />
                        <ErroDeCampo mensagem={errors.current_password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">Nova senha</Label>
                        <CampoSenha
                            id="password"
                            ref={campoSenhaNova}
                            required
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
                            required
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            aria-invalid={!!errors.password_confirmation}
                        />
                        <ErroDeCampo mensagem={errors.password_confirmation} />
                    </div>

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={processing}>
                            Salvar nova senha
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
        </LayoutMinhaConta>
    );
}

import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';

import CampoSenha from '@/components/campo-senha';
import ErroDeCampo from '@/components/erro-de-campo';
import TituloDeSecao from '@/components/titulo-de-secao';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

export default function ApagarConta() {
    const campoSenha = useRef<HTMLInputElement>(null);
    const { data, setData, delete: apagar, processing, reset, errors, clearErrors } = useForm({ password: '' });

    const confirmar: FormEventHandler = (e) => {
        e.preventDefault();

        apagar(route('minha-conta.perfil.remover'), {
            preserveScroll: true,
            onError: () => campoSenha.current?.focus(),
            onFinish: () => reset(),
        });
    };

    const fechar = () => {
        clearErrors();
        reset();
    };

    return (
        <section className="space-y-4">
            <TituloDeSecao titulo="Apagar minha conta" descricao="Remove a conta e tudo que está ligado a ela." />

            <div className="rounded-lg border border-[color:var(--destructive)]/30 bg-[color:var(--destructive)]/5 p-4">
                <p className="text-sm font-medium text-[color:var(--destructive)]">Isto não tem volta</p>
                <p className="text-muted-foreground mt-1 text-sm">
                    Suas mídias, publicações e conexões são apagadas junto. Os posts que já subiram continuam nas redes — mas você perde o histórico e
                    a prova de entrega guardados aqui.
                </p>

                <Dialog onOpenChange={(aberto) => !aberto && fechar()}>
                    <DialogTrigger asChild>
                        <Button variant="destructive" className="mt-4">
                            Apagar minha conta
                        </Button>
                    </DialogTrigger>

                    <DialogContent>
                        <DialogTitle>Apagar sua conta?</DialogTitle>
                        <DialogDescription>Digite sua senha para confirmar. Depois de apagada, a conta não pode ser recuperada.</DialogDescription>

                        <form className="space-y-5" onSubmit={confirmar}>
                            <div className="grid gap-2">
                                <Label htmlFor="senha-para-apagar">Senha</Label>
                                <CampoSenha
                                    id="senha-para-apagar"
                                    name="password"
                                    ref={campoSenha}
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="Sua senha"
                                    autoComplete="current-password"
                                    aria-invalid={!!errors.password}
                                />
                                <ErroDeCampo mensagem={errors.password} />
                            </div>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancelar
                                    </Button>
                                </DialogClose>

                                <Button type="submit" variant="destructive" disabled={processing}>
                                    Apagar definitivamente
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </section>
    );
}

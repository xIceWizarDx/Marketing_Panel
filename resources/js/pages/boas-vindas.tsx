import { Head, Link, usePage } from '@inertiajs/react';

import Marca from '@/components/marca';
import { Button } from '@/components/ui/button';
import { type DadosCompartilhados } from '@/types';

export default function BoasVindas() {
    const { auth, nomeDoApp } = usePage<DadosCompartilhados>().props;
    const usuario = auth?.usuario;

    return (
        <>
            <Head title={nomeDoApp} />

            <div className="bg-background text-foreground flex min-h-svh flex-col">
                <header className="mx-auto flex w-full max-w-5xl items-center justify-between px-6 py-5">
                    <Marca />

                    <nav className="flex items-center gap-2">
                        {usuario ? (
                            <Button asChild size="sm">
                                <Link href={usuario.papel === 'admin' ? '/admin/painel' : '/painel'}>Ir para o painel</Link>
                            </Button>
                        ) : (
                            <>
                                <Button asChild variant="ghost" size="sm">
                                    <Link href={route('entrar')}>Entrar</Link>
                                </Button>
                                <Button asChild size="sm">
                                    <Link href={route('cadastrar')}>Criar conta</Link>
                                </Button>
                            </>
                        )}
                    </nav>
                </header>

                <main className="mx-auto flex w-full max-w-5xl flex-1 flex-col justify-center px-6 py-16">
                    <h1 className="max-w-3xl text-3xl leading-[1.15] font-semibold tracking-tight sm:text-4xl md:text-5xl">
                        Publique seu vídeo em várias redes — e tenha a <span className="text-[color:var(--accent)]">prova</span> de que publicou.
                    </h1>

                    <p className="text-muted-foreground mt-5 max-w-prose text-base">
                        As redes aceitam o envio e só depois decidem se o vídeo entra. Por isso a gente confere o post na rede e guarda o link. Se
                        falhar, você fica sabendo — não semanas depois.
                    </p>

                    <dl className="mt-12 grid gap-6 sm:grid-cols-3">
                        <div>
                            <dt className="font-medium">Prova de entrega</dt>
                            <dd className="text-muted-foreground mt-1 text-sm">
                                Depois de enviar, lemos o post de volta na rede e guardamos o link.
                            </dd>
                        </div>
                        <div>
                            <dt className="font-medium">Aviso antes de quebrar</dt>
                            <dd className="text-muted-foreground mt-1 text-sm">Conexão vencendo avisa com antecedência, em vez de falhar calada.</dd>
                        </div>
                        <div>
                            <dt className="font-medium">Laudo do vídeo</dt>
                            <dd className="text-muted-foreground mt-1 text-sm">
                                Dizemos o que vai acontecer com o arquivo antes de enviar, não depois.
                            </dd>
                        </div>
                    </dl>
                </main>
            </div>
        </>
    );
}

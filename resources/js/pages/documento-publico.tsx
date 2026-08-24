import { Head, Link, usePage } from '@inertiajs/react';
import { type ReactNode } from 'react';

import Marca from '@/components/marca';
import { type DadosCompartilhados } from '@/types';

/**
 * ⭐ **A moldura dos documentos públicos** — termos e privacidade (DEC-171).
 *
 * ⛔ Eles existem porque **três plataformas exigem**: TikTok e Meta bloqueiam o
 * cadastro do aplicativo sem os dois endereços, e o YouTube pede a referência à
 * política do Google. Não é enfeite jurídico — é porta de entrada.
 *
 * ⚠️ **E precisam ser públicos de verdade**: sem login, sem redirecionamento. O
 * robô da plataforma abre o endereço sem sessão nenhuma, e uma página que manda
 * entrar reprova a análise sem dizer por quê.
 */
export default function DocumentoPublico({ titulo, atualizadoEm, children }: { titulo: string; atualizadoEm: string; children: ReactNode }) {
    const { nomeDoApp } = usePage<DadosCompartilhados>().props;

    return (
        <>
            <Head title={`${titulo} · ${nomeDoApp}`} />

            <div className="bg-background text-foreground flex min-h-svh flex-col">
                <header className="mx-auto flex w-full max-w-3xl items-center justify-between px-6 py-5">
                    <Link href="/">
                        <Marca />
                    </Link>
                </header>

                <main className="mx-auto w-full max-w-3xl flex-1 px-6 pb-16">
                    <h1 className="text-2xl font-semibold">{titulo}</h1>
                    <p className="text-muted-foreground mt-1 text-sm">Atualizado em {atualizadoEm}</p>

                    {/* ⚠️ `0.9375rem` e não menor: é texto para ser lido inteiro,
                        e o piso do projeto vale aqui como em toda parte. */}
                    <div className="mt-8 space-y-6 text-[0.9375rem] leading-relaxed">{children}</div>
                </main>

                <footer className="border-border/60 mx-auto w-full max-w-3xl border-t px-6 py-6">
                    <nav className="text-muted-foreground flex gap-4 text-sm">
                        <Link href="/termos" className="hover:text-foreground transition-colors">
                            Termos de Serviço
                        </Link>
                        <Link href="/privacidade" className="hover:text-foreground transition-colors">
                            Política de Privacidade
                        </Link>
                    </nav>
                </footer>
            </div>
        </>
    );
}

/** Um bloco com título — a única estrutura que estes documentos precisam. */
export function Secao({ titulo, children }: { titulo: string; children: ReactNode }) {
    return (
        <section className="space-y-2">
            <h2 className="text-base font-semibold">{titulo}</h2>
            {children}
        </section>
    );
}

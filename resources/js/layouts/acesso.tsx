import { Link, usePage } from '@inertiajs/react';

import Marca from '@/components/marca';
import { type DadosCompartilhados } from '@/types';

interface Props {
    children: React.ReactNode;
    titulo: string;
    descricao: string;
}

/**
 * Layout das telas de entrada, cadastro e senha.
 *
 * Duas colunas no desktop: formulario a esquerda, promessa do produto a direita.
 * No celular a coluna da direita some — quem esta entrando pelo telefone quer o
 * campo de senha, nao o discurso de venda.
 */
export default function LayoutAcesso({ children, titulo, descricao }: Props) {
    const { nomeDoApp } = usePage<DadosCompartilhados>().props;

    return (
        <div className="bg-background flex min-h-svh flex-col lg:grid lg:grid-cols-[1fr_1fr]">
            {/* Coluna do formulario */}
            <div className="flex flex-1 flex-col items-center justify-center px-6 py-10 sm:px-10">
                <div className="w-full max-w-[min(24rem,100%)]">
                    <Link href={route('inicio')} className="mb-8 flex items-center gap-2">
                        <Marca />
                    </Link>

                    <div className="mb-6 space-y-1.5">
                        <h1 className="text-2xl leading-tight font-semibold tracking-tight">{titulo}</h1>
                        <p className="text-muted-foreground text-sm">{descricao}</p>
                    </div>

                    {children}
                </div>
            </div>

            {/* Coluna da promessa — some no celular */}
            <div className="bg-primary text-primary-foreground hidden flex-col justify-center px-12 py-16 lg:flex">
                <div className="max-w-md">
                    <p className="text-accent-foreground/70 mb-3 text-xs font-medium tracking-[0.14em] uppercase">{nomeDoApp}</p>

                    <p className="text-3xl leading-[1.15] font-semibold">
                        O painel que publica seu vídeo em várias redes — e <span className="text-[var(--accent)]">prova</span> que publicou.
                    </p>

                    <ul className="mt-8 space-y-3 text-sm/relaxed opacity-80">
                        <li className="flex gap-3">
                            <span aria-hidden="true">→</span>
                            <span>Se publicou, tem link. Conferimos o post na rede depois de enviar.</span>
                        </li>
                        <li className="flex gap-3">
                            <span aria-hidden="true">→</span>
                            <span>Se a conexão vai quebrar, você sabe antes — não semanas depois.</span>
                        </li>
                        <li className="flex gap-3">
                            <span aria-hidden="true">→</span>
                            <span>Seu vídeo não é degradado em silêncio.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    );
}

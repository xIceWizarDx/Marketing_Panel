interface Props {
    titulo: string;
    descricao?: string;
    acoes?: React.ReactNode;
}

/** Titulo de uma tela, com espaco para botoes de acao a direita. */
export default function CabecalhoDePagina({ titulo, descricao, acoes }: Props) {
    return (
        <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div className="min-w-0">
                <h1 className="text-xl leading-tight font-semibold tracking-tight">{titulo}</h1>
                {descricao && <p className="text-muted-foreground mt-1 text-sm">{descricao}</p>}
            </div>

            {acoes && <div className="flex shrink-0 flex-wrap items-center gap-2">{acoes}</div>}
        </div>
    );
}

export default function TituloDeSecao({ titulo, descricao }: { titulo: string; descricao?: string }) {
    return (
        <header>
            <h2 className="mb-0.5 text-base font-medium">{titulo}</h2>
            {descricao && <p className="text-muted-foreground text-sm">{descricao}</p>}
        </header>
    );
}

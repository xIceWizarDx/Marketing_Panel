/**
 * Iniciais de um nome, pro avatar de quem nao subiu foto.
 * "Gabriel Souza Lima" -> "GL"
 */
export function useIniciais() {
    return (nomeCompleto: string): string => {
        const partes = nomeCompleto.trim().split(/\s+/).filter(Boolean);

        if (partes.length === 0) {
            return '';
        }

        if (partes.length === 1) {
            return partes[0].charAt(0).toUpperCase();
        }

        return `${partes[0].charAt(0)}${partes[partes.length - 1].charAt(0)}`.toUpperCase();
    };
}

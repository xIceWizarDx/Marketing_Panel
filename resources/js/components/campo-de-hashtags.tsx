import { useState } from 'react';

import { Input } from '@/components/ui/input';

/**
 * ⭐ O campo de hashtags — **um só, usado no compositor e no grupo**.
 *
 * ⛔ **Ele guarda o TEXTO CRU, e a lista é derivada dele.** Isto não é detalhe
 * de implementação: enquanto o campo mostrava `lista.join(' ')`, digitar espaço
 * era impossível. Cada tecla virava lista, a lista voltava a virar texto **sem
 * o espaço do fim**, e o cursor ficava preso na primeira palavra — dava para
 * escrever uma hashtag e nunca a segunda.
 *
 * ⚠️ Por isso o `#` continua aparecendo enquanto se digita: some da lista, não
 * da tela. Caractere que desaparece embaixo do dedo é pior que caractere
 * indesejado — a pessoa acha que o teclado falhou.
 */
export default function CampoDeHashtags({
    id,
    valor,
    aoMudar,
    placeholder,
    invalido,
}: {
    id: string;
    valor: string[];
    aoMudar: (lista: string[]) => void;
    placeholder?: string;
    invalido?: boolean;
}) {
    /*
     * ⚠️ Nasce do que veio de fora e vive por conta própria a partir daí. É o
     * que permite o espaço existir enquanto a palavra não terminou.
     */
    const [texto, setTexto] = useState(valor.join(' '));

    return (
        <Input
            id={id}
            value={texto}
            onChange={(evento) => {
                /*
                 * ⚠️ **Um espaço entre palavras, e só um.** Espaço duplo não
                 * separa mais nada — só faria a mesma lista parecer diferente
                 * quando alguém comparasse os dois textos.
                 *
                 * ⛔ E a colagem é `{2,}`, nunca `\s+ → ' '` com `trim`: o
                 * espaço do fim precisa sobreviver enquanto a pessoa digita,
                 * senão a segunda hashtag nunca começa.
                 */
                const texto = evento.target.value.replace(/\s{2,}/g, ' ');

                setTexto(texto);
                // A lista é o texto sem separador e sem vazio — `#` incluído.
                aoMudar(texto.split(/[\s,#]+/).filter(Boolean));
            }}
            placeholder={placeholder}
            autoComplete="off"
            aria-invalid={invalido}
        />
    );
}

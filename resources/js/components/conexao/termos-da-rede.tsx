import { ExternalLink } from 'lucide-react';

/**
 * Os links que a plataforma exige antes de conectar.
 *
 * ⚠️ **Não é enfeite jurídico: é exigência literal.** As Políticas do
 * Desenvolvedor do YouTube mandam exibir link para os Termos de Serviço com a
 * frase de vínculo, e referenciar a Política de Privacidade do Google. Faltar
 * isso é motivo de reprovação na auditoria.
 *
 * Também dizemos o que guardamos e por quanto tempo — a política exige explicar
 * "que informações do usuário o cliente acessa, coleta, armazena e utiliza".
 */
const termos: Record<string, { nome: string; servico: string; privacidade: string; privacidadeNome: string }> = {
    youtube: {
        nome: 'YouTube',
        servico: 'https://www.youtube.com/t/terms',
        privacidade: 'https://policies.google.com/privacy',
        privacidadeNome: 'Política de Privacidade do Google',
    },
    bluesky: {
        nome: 'Bluesky',
        servico: 'https://bsky.social/about/support/tos',
        privacidade: 'https://bsky.social/about/support/privacy-policy',
        privacidadeNome: 'Política de Privacidade do Bluesky',
    },
};

export default function TermosDaRede({ rede }: { rede: string }) {
    const t = termos[rede];

    if (!t) {
        return null;
    }

    return (
        <div className="text-muted-foreground space-y-2 text-xs">
            <p>
                Ao conectar, você concorda em ficar vinculado aos{' '}
                <a
                    href={t.servico}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-0.5 font-medium text-[color:var(--accent)] hover:underline"
                >
                    Termos de Serviço do {t.nome}
                    <ExternalLink className="size-3" aria-hidden="true" />
                </a>
                . O uso dos seus dados também segue a{' '}
                <a
                    href={t.privacidade}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-0.5 font-medium text-[color:var(--accent)] hover:underline"
                >
                    {t.privacidadeNome}
                    <ExternalLink className="size-3" aria-hidden="true" />
                </a>
                .
            </p>

            <p>
                <strong>O que guardamos:</strong> o nome e o identificador da conta, e a autorização de publicar — criptografada. Atualizamos esses
                dados a cada 30 dias enquanto a conta estiver conectada. Ao desconectar, eles são apagados na hora.
            </p>
        </div>
    );
}

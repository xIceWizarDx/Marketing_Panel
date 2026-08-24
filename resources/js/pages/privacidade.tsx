import { usePage } from '@inertiajs/react';

import DocumentoPublico, { Secao } from '@/pages/documento-publico';
import { type DadosCompartilhados } from '@/types';

/**
 * ⭐ **A política de privacidade** — exigida por TikTok, Meta e Google (DEC-171).
 *
 * ⛔ **Ela descreve o que o produto FAZ, não o que seria bonito prometer.** Cada
 * frase aqui corresponde a um comportamento que existe no código: o vídeo sai
 * quando a publicação termina (DEC-59), a credencial é guardada cifrada e nunca
 * aparece em tela, desconectar apaga o dado do titular na hora (exigência do
 * YouTube), e a exclusão da conta apaga tudo.
 *
 * ⚠️ Prometer aqui o que o código não faz é o defeito mais caro possível: vira
 * declaração falsa numa análise de plataforma, e derruba o aplicativo inteiro.
 */
export default function Privacidade() {
    const { nomeDoApp } = usePage<DadosCompartilhados>().props;

    return (
        <DocumentoPublico titulo="Política de Privacidade" atualizadoEm="14 de agosto de 2026">
            <p>
                Esta política explica quais dados o {nomeDoApp} guarda, por quanto tempo e por quê. Ela descreve o comportamento real do produto — não
                intenções.
            </p>

            <Secao titulo="Quem somos">
                <p>
                    O {nomeDoApp} é um painel que publica um mesmo vídeo em várias redes sociais e depois confere, na própria rede, se a publicação
                    foi feita — guardando o endereço do post como comprovante.
                </p>
            </Secao>

            <Secao titulo="O que guardamos da sua conta">
                <ul className="list-disc space-y-1 pl-5">
                    <li>Seu nome e e-mail de cadastro, para você entrar no painel.</li>
                    <li>
                        Das redes que você conecta: <strong>o nome e o identificador público da conta</strong> (por exemplo, o nome do canal ou o @ do
                        perfil) e a foto de perfil, para você reconhecer onde está publicando.
                    </li>
                    <li>
                        <strong>A autorização de publicar</strong>, guardada de forma cifrada. Ela nunca é exibida em tela nenhuma — nem para o nosso
                        suporte.
                    </li>
                    <li>O que você escreveu e o endereço dos posts publicados, que é o comprovante da publicação.</li>
                </ul>
            </Secao>

            <Secao titulo="O que NÃO fazemos">
                <ul className="list-disc space-y-1 pl-5">
                    <li>Não vendemos, alugamos nem compartilhamos seus dados com terceiros para publicidade.</li>
                    <li>Não pedimos permissão para apagar nem alterar conteúdo que já existe nas suas redes.</li>
                    <li>Não lemos suas mensagens privadas nem sua lista de contatos.</li>
                    <li>Não publicamos nada sem você mandar, uma publicação por vez.</li>
                </ul>
            </Secao>

            <Secao titulo="O vídeo que você envia">
                <p>
                    O arquivo fica guardado só o tempo necessário para subir às redes escolhidas.{' '}
                    <strong>Assim que a publicação termina, o vídeo é apagado do nosso servidor</strong> — o registro do que foi publicado continua,
                    com o endereço do post, mas o arquivo pesado não.
                </p>
            </Secao>

            <Secao titulo="Dados das plataformas">
                <p>
                    Quando você conecta uma rede, usamos as APIs oficiais dela. O uso dessas informações segue também as políticas de cada plataforma,
                    entre elas a{' '}
                    <a
                        href="https://policies.google.com/privacy"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="font-medium text-[color:var(--accent)] hover:underline"
                    >
                        Política de Privacidade do Google
                    </a>
                    . Os números que mostramos (visualizações, curtidas, comentários) são lidos das APIs e guardados apenas para desenhar a sua tela.
                </p>
            </Secao>

            <Secao titulo="Como remover seus dados">
                <p>Você tem duas formas, e as duas têm efeito imediato:</p>
                <ul className="list-disc space-y-1 pl-5">
                    <li>
                        <strong>Desconectar uma rede</strong>, dentro do painel: apagamos na hora a autorização guardada e o nome da conta. O
                        histórico do que já foi publicado continua, sem dado pessoal.
                    </li>
                    <li>
                        <strong>Excluir sua conta</strong>, em Minha Conta: apaga seus dados de cadastro, as autorizações, as mídias e as publicações.
                    </li>
                </ul>
                <p>Você também pode revogar o acesso do {nomeDoApp} diretamente nas configurações de cada rede social, sem passar por aqui.</p>
            </Secao>

            <Secao titulo="Contato">
                <p>
                    Dúvidas ou pedidos sobre seus dados: escreva para o e-mail de suporte informado no painel, em Minha Conta. Respondemos em até 7
                    dias.
                </p>
            </Secao>
        </DocumentoPublico>
    );
}

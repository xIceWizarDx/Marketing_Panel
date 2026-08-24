import { usePage } from '@inertiajs/react';

import DocumentoPublico, { Secao } from '@/pages/documento-publico';
import { type DadosCompartilhados } from '@/types';

/**
 * ⭐ **Os termos de serviço** — exigidos por TikTok e Meta no cadastro (DEC-171).
 *
 * ⛔ **Descrevem o produto como ele é**, inclusive onde ele NÃO promete: o painel
 * não garante que a rede vai aceitar o vídeo, nem que o post ficará no ar. É a
 * mesma honestidade que a tela pratica — dizer "no ar" só depois de reler.
 *
 * ⚠️ Texto funcional, escrito para descrever comportamento. **Ainda não passou
 * por revisão jurídica**, e isso está anotado como pendência antes de haver
 * cliente pagante.
 */
export default function Termos() {
    const { nomeDoApp } = usePage<DadosCompartilhados>().props;

    return (
        <DocumentoPublico titulo="Termos de Serviço" atualizadoEm="14 de agosto de 2026">
            <p>Ao usar o {nomeDoApp}, você concorda com o que está descrito aqui.</p>

            <Secao titulo="O que o serviço faz">
                <p>
                    O {nomeDoApp} recebe um vídeo, publica nas redes sociais que você conectou e escolheu, e depois confere na própria rede se o post
                    existe — guardando o endereço dele como comprovante.
                </p>
            </Secao>

            <Secao titulo="Sua conta e suas redes">
                <ul className="list-disc space-y-1 pl-5">
                    <li>Você é responsável por manter sua senha em segurança.</li>
                    <li>Você só pode conectar contas que administra, e precisa ter autorização de quem é dono delas quando não for você.</li>
                    <li>Você pode desconectar qualquer rede a qualquer momento, aqui ou nas configurações da própria rede.</li>
                </ul>
            </Secao>

            <Secao titulo="O conteúdo é seu">
                <p>
                    O vídeo e os textos que você envia continuam seus. Você nos autoriza apenas a transmiti-los para as redes que você escolher, e
                    garante que tem direito de publicar aquele conteúdo.
                </p>
            </Secao>

            <Secao titulo="O que não é permitido">
                <ul className="list-disc space-y-1 pl-5">
                    <li>Publicar conteúdo ilegal, ou que viole os termos das redes sociais de destino.</li>
                    <li>Usar o serviço para spam, automação em massa ou para se passar por outra pessoa.</li>
                    <li>Tentar contornar limites das plataformas ou do próprio painel.</li>
                </ul>
                <p>Nesses casos podemos suspender o acesso.</p>
            </Secao>

            <Secao titulo="O que NÃO garantimos">
                <p>
                    Cada rede social decide se aceita ou não uma publicação, e pode remover conteúdo pelas próprias regras. O {nomeDoApp}{' '}
                    <strong>não garante</strong> que um vídeo será aceito, aprovado pela moderação ou que permanecerá no ar. O que fazemos é dizer a
                    verdade sobre o que aconteceu: quando não sobe, dizemos que não subiu; quando sai do ar depois, dizemos que saiu.
                </p>
                <p>
                    Também dependemos das APIs das redes. Quando uma delas fica fora do ar ou muda suas regras, a publicação por ali pode parar até
                    que seja ajustada.
                </p>
            </Secao>

            <Secao titulo="Encerramento">
                <p>
                    Você pode excluir sua conta quando quiser, em Minha Conta. Ao excluir, seus dados são apagados conforme a{' '}
                    <a href="/privacidade" className="font-medium text-[color:var(--accent)] hover:underline">
                        Política de Privacidade
                    </a>
                    . O que já foi publicado nas redes continua lá — a exclusão aqui não apaga post nenhum.
                </p>
            </Secao>

            <Secao titulo="Mudanças">
                <p>Se estes termos mudarem, a data no topo desta página muda junto, e avisamos no painel.</p>
            </Secao>
        </DocumentoPublico>
    );
}

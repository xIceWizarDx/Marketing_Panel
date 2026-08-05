import { IconeDoNivel } from '@/components/midia/selo-laudo';
import { type Laudo } from '@/types';

const nomeDaRede: Record<string, string> = {
    youtube: 'YouTube',
    instagram: 'Instagram',
    facebook: 'Facebook',
    tiktok: 'TikTok',
};

/**
 * O laudo aberto, rede por rede.
 *
 * ⭐ É o diferencial nº 1 (DEC-32/33): dizer ANTES de agendar o que vai
 * acontecer com o arquivo. Por isso toda linha de problema mostra também a
 * PROVIDÊNCIA — o que o sistema fará. "Deu erro" e ponto foi exatamente a
 * experiência que os concorrentes entregam.
 */
export default function PainelLaudo({ laudo }: { laudo: Laudo | null }) {
    if (!laudo || !laudo.disponivel) {
        return <p className="text-muted-foreground text-sm">{laudo?.indisponivel_porque ?? 'Este arquivo ainda não foi analisado.'}</p>;
    }

    const { ficha } = laudo;

    return (
        <div className="space-y-5">
            <dl className="text-muted-foreground grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs sm:grid-cols-3">
                {ficha.largura && (
                    <div>
                        <dt className="inline">Resolução: </dt>
                        <dd className="text-foreground inline font-medium">
                            {ficha.largura}×{ficha.altura}
                        </dd>
                    </div>
                )}
                {ficha.codec_video && (
                    <div>
                        <dt className="inline">Vídeo: </dt>
                        <dd className="text-foreground inline font-medium">{ficha.codec_video}</dd>
                    </div>
                )}
                <div>
                    <dt className="inline">Áudio: </dt>
                    <dd className="text-foreground inline font-medium">{ficha.codec_audio ?? 'sem áudio'}</dd>
                </div>
                {ficha.fps && (
                    <div>
                        <dt className="inline">Quadros: </dt>
                        <dd className="text-foreground inline font-medium">{ficha.fps}/s</dd>
                    </div>
                )}
            </dl>

            <div className="space-y-4">
                {Object.entries(laudo.por_rede).map(([rede, achados]) => {
                    const recusado = achados.some((a) => a.nivel === 'erro');

                    return (
                        <section key={rede}>
                            <h4 className="mb-1.5 flex items-center gap-2 text-sm font-medium">
                                {nomeDaRede[rede] ?? rede}
                                <span className="text-xs font-normal" style={{ color: recusado ? 'var(--saude-erro)' : 'var(--saude-ok)' }}>
                                    {recusado ? 'não publica' : 'publica'}
                                </span>
                            </h4>

                            <ul className="space-y-1.5">
                                {achados.map((achado, indice) => (
                                    <li key={indice} className="flex gap-2 text-sm">
                                        <IconeDoNivel nivel={achado.nivel} className="mt-0.5" />
                                        <span className="min-w-0">
                                            {achado.mensagem}
                                            {achado.providencia && <span className="text-muted-foreground block text-xs">{achado.providencia}</span>}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    );
                })}
            </div>
        </div>
    );
}

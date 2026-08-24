<?php

namespace App\Services\Meta;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Sobe o arquivo para o `rupload.facebook.com`.
 *
 * Facebook e Instagram usam o **mesmo protocolo** de envio, mudando só o
 * caminho. Escrever duas vezes seria garantir que um dia as cópias divirjam.
 *
 * O desenho é o mesmo do YouTube: quem chama guarda o identificador da sessão
 * **antes** do primeiro byte, e a retomada continua de onde parou em vez de
 * recomeçar — publicação não tem desfazer.
 */
class EnvioRetomavel
{
    private const HOST = 'https://rupload.facebook.com';

    private const GRAPH = 'https://graph.facebook.com';

    private const VERSAO = 'v25.0';

    /** Vídeo grande em rede ruim: 10 minutos é folga, não otimismo. */
    private const TEMPO_LIMITE = 600;

    private function __construct(
        private readonly string $caminhoDaApi,
        /**
         * ⭐ **Qual campo carrega o quanto já subiu** (DEC-158).
         *
         * ⚠️ As duas redes documentam retomada, e **cada uma num campo com nome
         * diferente**: o Facebook responde em `status`, o Instagram em
         * `video_status`. Perguntar pelo campo errado não devolve nulo — o
         * Graph derruba a chamada inteira com o erro 100.
         */
        private readonly string $campoDoStatus,
    ) {}

    /** O caminho do Facebook: reels de Página. */
    public static function paraFacebook(): self
    {
        return new self('video-upload', campoDoStatus: 'status');
    }

    /** O caminho do Instagram: container já criado. */
    public static function paraInstagram(): self
    {
        return new self('ig-api-upload', campoDoStatus: 'video_status');
    }

    /**
     * @param  string  $identificador  o `video_id` (Facebook) ou o container (Instagram)
     * @param  int  $deslocamento  de qual byte continuar; 0 recomeça do início
     */
    public function enviar(
        string $token,
        string $identificador,
        string $conteudo,
        int $deslocamento = 0,
    ): Response {
        $bytes = $deslocamento > 0 ? substr($conteudo, $deslocamento) : $conteudo;

        return Http::withHeaders([
            // ⚠️ `OAuth <token>`, não `Bearer` — este host não aceita Bearer, e o
            // erro que ele devolve não diz isso.
            'Authorization' => 'OAuth '.$token,
            'offset' => (string) $deslocamento,
            // Sempre o tamanho TOTAL do arquivo, mesmo retomando no meio: é como
            // a rede sabe quando terminou.
            'file_size' => (string) strlen($conteudo),
            'Content-Type' => 'application/octet-stream',
        ])
            ->timeout(self::TEMPO_LIMITE)
            ->withBody($bytes, 'application/octet-stream')
            ->post(self::HOST.'/'.$this->caminhoDaApi.'/'.self::VERSAO.'/'.$identificador);
    }

    /**
     * Quantos bytes a rede já recebeu.
     *
     * ⚠️ O caminho documentado é o **Graph**, não o `rupload`: pergunta-se o
     * status do envio e lê-se `uploading_phase.bytes_transfered`.
     *
     * ⭐ **As DUAS redes documentam isto** (DEC-158). Aqui o Instagram devolvia
     * `0` sempre, com um comentário dizendo que ele não descrevia retomada — e
     * descreve: no guia de *resumable uploads*, no campo `video_status`. O
     * preço do engano era um vídeo inteiro reenviado do zero a cada tropeço de
     * rede, que é justamente o que esta classe existe para evitar.
     *
     * ⚠️ **Quatro grafias conferidas, e não é zelo excessivo:** a Meta escreve
     * `bytes_transfered` (com um `r`) no exemplo do Facebook e
     * `bytes_transferred` no do Instagram, e chama o bloco de `upload_phase` no
     * texto corrido e de `uploading_phase` na tabela. Ler só uma grafia devolve
     * `0` em silêncio — e `0` aqui não parece defeito, parece envio novo.
     *
     * Devolve `0` quando não dá para saber, e aí recomeça do zero: reenviar é
     * lento, continuar de um ponto errado corrompe o arquivo.
     */
    public function jaRecebidos(string $token, string $identificador): int
    {
        try {
            $resposta = Http::withToken($token)->timeout(30)
                ->get(self::GRAPH.'/'.self::VERSAO.'/'.$identificador, ['fields' => $this->campoDoStatus]);
        } catch (ConnectionException) {
            return 0;
        }

        if (! $resposta->successful()) {
            return 0;
        }

        foreach (['uploading_phase', 'upload_phase'] as $bloco) {
            foreach (['bytes_transfered', 'bytes_transferred'] as $campo) {
                $bytes = $resposta->json("{$this->campoDoStatus}.{$bloco}.{$campo}");

                if (is_numeric($bytes)) {
                    return (int) $bytes;
                }
            }
        }

        return 0;
    }
}

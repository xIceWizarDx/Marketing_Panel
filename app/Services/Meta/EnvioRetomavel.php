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
         * A rede diz quanto já recebeu?
         *
         * Só o Facebook documenta isso. No Instagram não há mecanismo descrito —
         * e continuar de um ponto adivinhado corromperia o arquivo, o que é bem
         * pior que reenviar.
         */
        private readonly bool $sabeRetomar,
    ) {}

    /** O caminho do Facebook: reels de Página. */
    public static function paraFacebook(): self
    {
        return new self('video-upload', sabeRetomar: true);
    }

    /** O caminho do Instagram: container já criado. */
    public static function paraInstagram(): self
    {
        return new self('ig-api-upload', sabeRetomar: false);
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
     * `status` do vídeo e lê-se `uploading_phase.bytes_transfered`. (A mesma
     * página chama esse bloco de `upload_phase` no texto corrido e de
     * `uploading_phase` na tabela de campos — os dois são conferidos.)
     *
     * Devolve `0` quando não dá para saber, e aí recomeça do zero: reenviar é
     * lento, continuar de um ponto errado corrompe o arquivo.
     */
    public function jaRecebidos(string $token, string $identificador): int
    {
        if (! $this->sabeRetomar) {
            return 0;
        }

        try {
            $resposta = Http::withToken($token)->timeout(30)
                ->get(self::GRAPH.'/'.self::VERSAO.'/'.$identificador, ['fields' => 'status']);
        } catch (ConnectionException) {
            return 0;
        }

        if (! $resposta->successful()) {
            return 0;
        }

        $bytes = $resposta->json('status.uploading_phase.bytes_transfered')
            ?? $resposta->json('status.upload_phase.bytes_transfered');

        return is_numeric($bytes) ? (int) $bytes : 0;
    }
}

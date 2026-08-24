<?php

namespace App\Publicadores;

use App\Enums\Plataforma;
use App\Models\Destino;
use App\Models\Midia;
use App\Services\ConexaoComPinterest;
use App\Support\FalhaDeConexao;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Pinterest — o arquivo sobe para a **AWS**, e o Pin mora num **quadro**.
 *
 * ```
 * registrar → POST /v5/media       { media_type: video } → media_id + upload_url + upload_parameters
 * enviar    → POST {upload_url}    multipart, para a Amazon
 * conferir  → GET  /v5/media/{id}  → status
 * fixar     → POST /v5/pins        { board_id, media_source } → o Pin
 * ```
 *
 * ⭐ **Encaixe de formato melhor que o de todas as outras:** o Pinterest é
 * nativamente vertical, e o 9:16 que o painel produz serve sem reconversão.
 *
 * ⛔ **O `board_id` é o "para onde" desta rede** (DEC-134) — e ele é o
 * `identificador_externo` do canal, porque um Pin sem quadro não existe.
 */
class PublicadorPinterest implements Publicador
{
    public function plataforma(): Plataforma
    {
        return Plataforma::Pinterest;
    }

    public function publicar(Destino $destino, Retomada $retomada): ResultadoPublicacao
    {
        $token = $destino->contaSocial->credencial?->access_token;

        if (! $token) {
            return ResultadoPublicacao::recusado(
                'A conexão com o Pinterest não está mais válida. Reconecte a conta para publicar.'
            );
        }

        $midia = $destino->publicacao->midia;

        if (! $midia?->ehVideo()) {
            return ResultadoPublicacao::recusado('O Pinterest recebe vídeo por aqui.');
        }

        try {
            // ⛔ Já começou antes? O registro anterior continua valendo, e
            // refazer subiria o arquivo inteiro de novo.
            if ($retomada->comecouAntes()) {
                $pronto = $this->jaSubiu($retomada->handle(), $token);

                if ($pronto !== null) {
                    return $pronto;
                }
            }

            $registro = $this->registrar($token);

            if ($registro instanceof ResultadoPublicacao) {
                return $registro;
            }

            // ⛔ Guardado antes do primeiro byte.
            $retomada->guardar($registro['media_id']);

            $enviado = $this->enviarParaAws($midia, $registro);

            if ($enviado !== null) {
                return $enviado;
            }

            // ⭐ DEC-31: aceito ≠ no ar. O Pin ainda nem existe — ele nasce na
            // conciliação, quando o vídeo terminar de processar.
            return ResultadoPublicacao::aceito($registro['media_id']);
        } catch (ConnectionException $erro) {
            return ResultadoPublicacao::tentarDepois(FalhaDeConexao::explicar($erro, 'Pinterest'));
        }
    }

    public function conciliar(Destino $destino): ResultadoConciliacao
    {
        $token = $destino->contaSocial->credencial?->access_token;

        if (! $token || ! $destino->handle_externo) {
            return ResultadoConciliacao::recusado('Não há como conferir esta publicação no Pinterest.');
        }

        // Já fixado: relemos o Pin — é a prova.
        if ($destino->identificador_externo) {
            return $this->conferirPin($destino, $token);
        }

        try {
            $resposta = Http::withToken($token)->timeout(20)
                ->get(ConexaoComPinterest::API.'/media/'.$destino->handle_externo);
        } catch (ConnectionException) {
            return ResultadoConciliacao::aindaProcessando();
        }

        if (! $resposta->successful()) {
            return ResultadoConciliacao::aindaProcessando();
        }

        return match ((string) $resposta->json('status')) {
            'succeeded' => $this->fixarPin($destino, $token),
            'failed' => ResultadoConciliacao::recusado('O Pinterest não conseguiu processar este vídeo.'),
            // `registered` e `processing` seguem esperando.
            default => ResultadoConciliacao::aindaProcessando(),
        };
    }

    /** O passo que de fato publica. */
    private function fixarPin(Destino $destino, string $token): ResultadoConciliacao
    {
        try {
            $resposta = Http::withToken($token)->timeout(30)
                ->post(ConexaoComPinterest::API.'/pins', array_filter([
                    // ⛔ O quadro é o `identificador_externo` do canal (DEC-134).
                    'board_id' => $destino->contaSocial->identificador_externo,
                    // ⭐ Aqui título tem campo PRÓPRIO (100), separado da
                    // descrição (800) — como YouTube e Facebook.
                    'title' => $destino->titulo(),
                    'description' => $destino->textoFinal(),
                    'media_source' => [
                        'source_type' => 'video_id',
                        'media_id' => $destino->handle_externo,
                        /*
                         * ⭐ A capa sai de um QUADRO do próprio vídeo (DEC-136).
                         * É a única das três formas que não exige subir um
                         * segundo arquivo — as outras trariam uma imagem que o
                         * painel teria que gerar, guardar e servir.
                         */
                        'cover_image_key_frame_time' => 1,
                    ],
                ], fn ($valor) => $valor !== null && $valor !== ''));
        } catch (ConnectionException) {
            /*
             * ⛔ Não se tenta de novo — mesma razão do LinkedIn (DEC-125) e do
             * X: fixar um Pin não é idempotente, e a conciliação roda vinte
             * vezes.
             */
            return ResultadoConciliacao::recusado(
                'O Pinterest não respondeu a tempo depois de receber o Pin. '.
                'Confira no Pinterest antes de publicar de novo: ele pode ter subido.'
            );
        }

        $id = (string) ($resposta->json('id') ?? '');

        if (! $resposta->successful() || $id === '') {
            return ResultadoConciliacao::recusado($this->frase($resposta, 'O Pinterest recusou este Pin.'));
        }

        $destino->forceFill(['identificador_externo' => $id])->save();

        return ResultadoConciliacao::noAr("https://www.pinterest.com/pin/{$id}/");
    }

    /** ⭐ Relê o Pin — a prova (DEC-31). */
    private function conferirPin(Destino $destino, string $token): ResultadoConciliacao
    {
        try {
            $resposta = Http::withToken($token)->timeout(20)
                ->get(ConexaoComPinterest::API.'/pins/'.$destino->identificador_externo);
        } catch (ConnectionException) {
            return ResultadoConciliacao::aindaProcessando();
        }

        if ($resposta->successful() && $resposta->json('id')) {
            return ResultadoConciliacao::noAr("https://www.pinterest.com/pin/{$destino->identificador_externo}/");
        }

        return $resposta->status() === 404
            ? ResultadoConciliacao::recusado('O Pin não está mais no Pinterest.')
            : ResultadoConciliacao::aindaProcessando();
    }

    /**
     * @return array{media_id: string, url: string, campos: array<string, string>}|ResultadoPublicacao
     */
    private function registrar(string $token): array|ResultadoPublicacao
    {
        $resposta = Http::withToken($token)->timeout(30)
            ->post(ConexaoComPinterest::API.'/media', ['media_type' => 'video']);

        $id = (string) ($resposta->json('media_id') ?? '');

        if (! $resposta->successful() || $id === '') {
            return $this->erro($resposta);
        }

        return [
            'media_id' => $id,
            'url' => (string) $resposta->json('upload_url'),
            'campos' => (array) $resposta->json('upload_parameters', []),
        ];
    }

    /**
     * Sobe o arquivo — **para a Amazon, não para o Pinterest** (DEC-135).
     *
     * @param  array{media_id: string, url: string, campos: array<string, string>}  $registro
     */
    private function enviarParaAws(Midia $midia, array $registro): ?ResultadoPublicacao
    {
        $caminho = Storage::disk(config('midia.disco'))->path($midia->caminho);

        if (! is_readable($caminho)) {
            return ResultadoPublicacao::recusado('Não foi possível ler o arquivo para enviar.');
        }

        $partes = [];

        /*
         * ⛔ **Todos os parâmetros PRIMEIRO, e o arquivo por último.**
         *
         * ⚠️ Formulário assinado do S3 ignora o que vier DEPOIS do campo
         * `file`. Mandar `key` ou `policy` no fim faz a Amazon recusar com um
         * erro de XML que não menciona ordem nenhuma — e ninguém adivinha isso
         * lendo a mensagem.
         */
        foreach ($registro['campos'] as $nome => $valor) {
            $partes[] = ['name' => $nome, 'contents' => (string) $valor];
        }

        $arquivo = @fopen($caminho, 'rb');

        if ($arquivo === false) {
            return ResultadoPublicacao::recusado('Não foi possível ler o arquivo para enviar.');
        }

        $partes[] = ['name' => 'file', 'contents' => $arquivo, 'filename' => basename($caminho)];

        try {
            /*
             * ⚠️ **Sem o nosso token aqui.** Quem autoriza é a assinatura que
             * veio dentro dos parâmetros; mandar o `Authorization` do Pinterest
             * para a Amazon é pedir 403.
             */
            $resposta = Http::asMultipart()->timeout(600)->post($registro['url'], $partes);
        } finally {
            if (is_resource($arquivo)) {
                fclose($arquivo);
            }
        }

        if (! $resposta->successful()) {
            return ResultadoPublicacao::tentarDepois('O envio do vídeo para o Pinterest não foi aceito.');
        }

        return null;
    }

    /** O envio deste `media_id` já aconteceu? */
    private function jaSubiu(?string $mediaId, string $token): ?ResultadoPublicacao
    {
        if (! $mediaId) {
            return null;
        }

        try {
            $resposta = Http::withToken($token)->timeout(20)
                ->get(ConexaoComPinterest::API.'/media/'.$mediaId);
        } catch (ConnectionException) {
            return null;
        }

        if (! $resposta->successful()) {
            return null;
        }

        // ⚠️ `registered` quer dizer que o arquivo NÃO chegou: aí o envio tem
        // que acontecer de verdade.
        return in_array((string) $resposta->json('status'), ['processing', 'succeeded'], true)
            ? ResultadoPublicacao::aceito($mediaId)
            : null;
    }

    private function erro(Response $resposta): ResultadoPublicacao
    {
        if ($resposta->status() === 401) {
            return ResultadoPublicacao::recusado(
                'A conexão com o Pinterest não está mais válida. Reconecte a conta para publicar.'
            );
        }

        if ($resposta->status() === 429) {
            return ResultadoPublicacao::semCota('O Pinterest pediu para esperar antes da próxima publicação.');
        }

        return $resposta->serverError()
            ? ResultadoPublicacao::tentarDepois('O Pinterest teve um erro interno. Vamos tentar de novo.')
            : ResultadoPublicacao::recusado($this->frase($resposta, 'O Pinterest recusou o envio.'));
    }

    private function frase(Response $resposta, string $padrao): string
    {
        return (string) ($resposta->json('message') ?: $padrao);
    }
}

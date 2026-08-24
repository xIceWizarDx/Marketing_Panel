<?php

namespace App\Publicadores;

use App\Enums\Plataforma;
use App\Models\Destino;
use App\Services\ConexaoComDiscord;
use App\Support\FalhaDeConexao;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Discord — uma chamada, e pronto.
 *
 * ```
 * publicar → POST /webhooks/{id}/{token}?wait=true   multipart, sem autenticação
 * conferir → GET  /webhooks/{id}/{token}/messages/{id}
 * ```
 *
 * ⭐ **É o publicador mais curto do painel**, e por um motivo bom: o Discord não
 * processa vídeo. O arquivo sobe e a mensagem existe na mesma chamada — não há
 * segundo passo, nem espera, nem estado intermediário.
 *
 * ⛔ **`wait=true` é obrigatório aqui** (DEC-142). Sem ele o Discord responde
 * `204` sem corpo e *"unconfirmed messages don't generate errors"* — ou seja,
 * a publicação poderia falhar em silêncio e o painel diria que deu certo. É
 * exatamente o que o produto existe para não fazer.
 *
 * ⚠️ **E aqui não há alcance:** o Discord não tem feed nem descoberta. Isto é
 * aviso para uma comunidade que já existe, não distribuição.
 */
class PublicadorDiscord implements Publicador
{
    public function plataforma(): Plataforma
    {
        return Plataforma::Discord;
    }

    public function publicar(Destino $destino, Retomada $retomada): ResultadoPublicacao
    {
        $conta = $destino->contaSocial;
        $token = $conta->credencial?->access_token;

        if (! $token) {
            return ResultadoPublicacao::recusado(
                'O webhook do Discord não está mais guardado. Conecte o canal de novo.'
            );
        }

        $midia = $destino->publicacao->midia;

        if (! $midia) {
            return ResultadoPublicacao::recusado('Não há arquivo para publicar.');
        }

        // Já publicado numa tentativa anterior: o identificador da mensagem vale.
        if ($retomada->comecouAntes()) {
            return ResultadoPublicacao::aceito((string) $retomada->handle());
        }

        $caminho = Storage::disk(config('midia.disco'))->path($midia->caminho);
        $arquivo = @fopen($caminho, 'rb');

        if ($arquivo === false) {
            return ResultadoPublicacao::recusado('Não foi possível ler o arquivo para enviar.');
        }

        try {
            $resposta = Http::asMultipart()->timeout(300)->post(
                // ⛔ `wait=true`: sem ele o Discord responde 204 e a falha vira
                // silêncio (DEC-142).
                ConexaoComDiscord::enderecoDe($conta->identificador_externo, $token).'?wait=true',
                [
                    [
                        'name' => 'payload_json',
                        'contents' => json_encode([
                            'content' => $this->texto($destino),
                        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    ],
                    ['name' => 'files[0]', 'contents' => $arquivo, 'filename' => basename($caminho)],
                ]
            );
        } catch (ConnectionException $erro) {
            return ResultadoPublicacao::tentarDepois(FalhaDeConexao::explicar($erro, 'Discord'));
        } finally {
            if (is_resource($arquivo)) {
                fclose($arquivo);
            }
        }

        $id = (string) ($resposta->json('id') ?? '');

        if (! $resposta->successful() || $id === '') {
            return $this->erro($resposta);
        }

        /*
         * ⚠️ Guardado ANTES de devolver: aqui a mensagem já existe do outro
         * lado. Se o processo morrer neste ponto sem o identificador salvo, a
         * tentativa seguinte publicaria uma segunda vez.
         */
        $retomada->guardar($id);

        return ResultadoPublicacao::aceito($id);
    }

    /** ⭐ A prova (DEC-31): relê a mensagem no canal. */
    public function conciliar(Destino $destino): ResultadoConciliacao
    {
        $conta = $destino->contaSocial;
        $token = $conta->credencial?->access_token;

        if (! $token || ! $destino->handle_externo) {
            return ResultadoConciliacao::recusado('Não há como conferir esta publicação no Discord.');
        }

        try {
            $resposta = Http::timeout(20)->get(
                ConexaoComDiscord::enderecoDe($conta->identificador_externo, $token)
                .'/messages/'.$destino->handle_externo
            );
        } catch (ConnectionException) {
            return ResultadoConciliacao::aindaProcessando();
        }

        if ($resposta->successful() && $resposta->json('id')) {
            $destino->forceFill(['identificador_externo' => $destino->handle_externo])->save();

            /*
             * ⛔ O endereço de uma mensagem precisa de TRÊS partes:
             * `channels/{servidor}/{canal}/{mensagem}`.
             *
             * ⚠️ O canal vem na resposta; o **servidor** não vem — ele só existe
             * na resposta do webhook, e por isso foi guardado na conexão. Sem
             * ele o link ia para `channels/@me`, que é conversa privada: um link
             * de prova que não prova nada.
             */
            $canal = (string) ($resposta->json('channel_id') ?? '');
            $servidor = (string) ($conta->servidor ?? '');

            return ResultadoConciliacao::noAr(
                $canal !== '' && $servidor !== ''
                    ? "https://discord.com/channels/{$servidor}/{$canal}/{$destino->handle_externo}"
                    // ⚠️ Sem as três partes não há endereço honesto — e a prova
                    // continua sendo a releitura, que acabou de acontecer.
                    : 'https://discord.com/channels/@me'
            );
        }

        // ⚠️ Mensagem apagada no canal — e é o caso que só quem relê descobre.
        return $resposta->status() === 404
            ? ResultadoConciliacao::recusado('A mensagem não está mais no canal do Discord.')
            : ResultadoConciliacao::aindaProcessando();
    }

    /**
     * ⚠️ **2000 caracteres, e o Discord recusa o post inteiro se passar** — não
     * corta. O limite é conferido antes, na `EspecificacaoDaRede`.
     */
    private function texto(Destino $destino): string
    {
        return trim(($destino->titulo() ?? '').' '.$destino->textoFinal());
    }

    private function erro(Response $resposta): ResultadoPublicacao
    {
        if (in_array($resposta->status(), [401, 403, 404], true)) {
            /*
             * ⚠️ No Discord esses três dizem a mesma coisa na prática: o webhook
             * não vale mais. A causa quase sempre é alguém tê-lo apagado no
             * canal — e a saída é criar outro, não "tentar de novo".
             */
            return ResultadoPublicacao::recusado(
                'O webhook do Discord não vale mais. Ele pode ter sido apagado no canal — crie outro e conecte de novo.'
            );
        }

        if ($resposta->status() === 429) {
            return ResultadoPublicacao::semCota('O Discord pediu para esperar antes da próxima publicação.');
        }

        // ⚠️ 413 é arquivo grande demais para AQUELE servidor: o teto sobe com o
        // nível de impulsionamento, então não existe número único do Discord.
        if ($resposta->status() === 413) {
            return ResultadoPublicacao::recusado(
                'O arquivo passou do tamanho que esse servidor do Discord aceita. '.
                'O limite depende do nível de impulsionamento do servidor.'
            );
        }

        return $resposta->serverError()
            ? ResultadoPublicacao::tentarDepois('O Discord teve um erro interno. Vamos tentar de novo.')
            : ResultadoPublicacao::recusado(
                (string) ($resposta->json('message') ?: 'O Discord recusou o envio.')
            );
    }
}

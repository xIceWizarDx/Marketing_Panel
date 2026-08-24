<?php

namespace App\Publicadores;

use App\Enums\Plataforma;
use App\Models\Destino;
use App\Support\FalhaDeConexao;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Mastodon — e **a primeira rede do painel que aceita chave de idempotência**.
 *
 * ```
 * subir    → POST /api/v2/media       → 202 com id, e `url` ainda nula
 * conferir → GET  /api/v1/media/{id}  → 200 pronto, 206 processando
 * publicar → POST /api/v1/statuses    → o status, com `id` e `url`
 * ```
 *
 * ⭐ **`Idempotency-Key` muda a regra aqui** (DEC-140). No LinkedIn, no X e no
 * Pinterest, um tempo esgotado depois de a rede receber o pedido nos obriga a
 * **parar e avisar**, porque repetir criaria um segundo post. Aqui não: a chave
 * vale uma hora, e repetir com a mesma chave devolve o mesmo post.
 *
 * ⛔ **E cada conta mora num servidor diferente** (DEC-138): não existe endereço
 * fixo neste publicador. Toda chamada monta a URL a partir do `servidor` da
 * conta.
 */
class PublicadorMastodon implements Publicador
{
    public function plataforma(): Plataforma
    {
        return Plataforma::Mastodon;
    }

    public function publicar(Destino $destino, Retomada $retomada): ResultadoPublicacao
    {
        $conta = $destino->contaSocial;
        $token = $conta->credencial?->access_token;

        if (! $token || ! $conta->servidor) {
            return ResultadoPublicacao::recusado(
                'A conexão com o Mastodon não está mais válida. Reconecte a conta para publicar.'
            );
        }

        $midia = $destino->publicacao->midia;

        if (! $midia) {
            return ResultadoPublicacao::recusado('Não há arquivo para publicar.');
        }

        // Já subiu antes: o identificador da mídia continua valendo.
        if ($retomada->comecouAntes()) {
            return ResultadoPublicacao::aceito((string) $retomada->handle());
        }

        $caminho = Storage::disk(config('midia.disco'))->path($midia->caminho);
        $arquivo = @fopen($caminho, 'rb');

        if ($arquivo === false) {
            return ResultadoPublicacao::recusado('Não foi possível ler o arquivo para enviar.');
        }

        try {
            $resposta = Http::withToken($token)->asMultipart()->timeout(600)
                ->post($this->em($conta->servidor, '/api/v2/media'), [
                    ['name' => 'file', 'contents' => $arquivo, 'filename' => basename($caminho)],
                ]);
        } catch (ConnectionException $erro) {
            return ResultadoPublicacao::tentarDepois(FalhaDeConexao::explicar($erro, (string) $conta->servidor));
        } finally {
            if (is_resource($arquivo)) {
                fclose($arquivo);
            }
        }

        $id = (string) ($resposta->json('id') ?? '');

        if (! $resposta->successful() || $id === '') {
            return $this->erro($resposta, (string) $conta->servidor);
        }

        /*
         * ⚠️ Vídeo volta **202**, com `url` nula: o servidor aceitou e está
         * processando. Publicar agora daria um post sem vídeo — por isso o
         * status nasce na conciliação.
         */
        $retomada->guardar($id);

        return ResultadoPublicacao::aceito($id);
    }

    public function conciliar(Destino $destino): ResultadoConciliacao
    {
        $conta = $destino->contaSocial;
        $token = $conta->credencial?->access_token;

        if (! $token || ! $conta->servidor || ! $destino->handle_externo) {
            return ResultadoConciliacao::recusado('Não há como conferir esta publicação no Mastodon.');
        }

        // Já publicado: relemos o status — é a prova.
        if ($destino->identificador_externo) {
            return $this->conferirStatus($destino, $token);
        }

        try {
            $resposta = Http::withToken($token)->timeout(20)
                ->get($this->em($conta->servidor, '/api/v1/media/'.$destino->handle_externo));
        } catch (ConnectionException) {
            return ResultadoConciliacao::aindaProcessando();
        }

        /*
         * ⭐ **206 quer dizer "ainda processando"**, e é um código de sucesso —
         * um motor que trate `successful()` como pronto publicaria um post sem
         * vídeo. Só o **200** libera.
         */
        if ($resposta->status() === 206) {
            return ResultadoConciliacao::aindaProcessando();
        }

        /*
         * ⛔ **Nem todo "não é 200" é espera.** Devolver `aindaProcessando` para
         * tudo fazia a conciliação insistir vinte vezes contra um erro
         * definitivo — e o desfecho, três horas depois, era a frase genérica
         * *"a rede aceitou mas não confirmou"*, que não diz nada sobre a causa.
         */
        if ($resposta->status() === 404) {
            return ResultadoConciliacao::recusado(
                'O servidor não encontra mais o vídeo enviado. Publique de novo.'
            );
        }

        if ($resposta->status() === 401 || $resposta->status() === 403) {
            return ResultadoConciliacao::recusado(
                'A conexão com o Mastodon não está mais válida. Reconecte a conta.'
            );
        }

        if ($resposta->status() !== 200) {
            return ResultadoConciliacao::aindaProcessando();
        }

        return $this->publicarStatus($destino, $token);
    }

    /** O passo que de fato publica. */
    private function publicarStatus(Destino $destino, string $token): ResultadoConciliacao
    {
        try {
            $resposta = Http::withToken($token)
                /*
                 * ⭐ **A chave de idempotência** (DEC-140), e ela é o `ulid` do
                 * destino: estável entre tentativas, único entre destinos.
                 *
                 * ⚠️ Repetir com a mesma chave devolve **o mesmo post** — por
                 * isso aqui, ao contrário do LinkedIn e do X, um tempo esgotado
                 * pode voltar para a fila sem risco de publicar duas vezes.
                 */
                ->withHeaders(['Idempotency-Key' => $destino->ulid])
                ->asForm()->timeout(30)
                ->post($this->em((string) $destino->contaSocial->servidor, '/api/v1/statuses'), [
                    'status' => trim(($destino->titulo() ?? '').' '.$destino->textoFinal()),
                    'media_ids' => [$destino->handle_externo],
                    // ⚠️ `public` explícito: o padrão do servidor pode ser outro,
                    // e publicar sem alcance é publicar onde ninguém vê.
                    'visibility' => 'public',
                ]);
        } catch (ConnectionException) {
            // ⭐ Seguro voltar para a fila: a chave garante que a repetição não
            // cria um segundo post.
            return ResultadoConciliacao::aindaProcessando();
        }

        $id = (string) ($resposta->json('id') ?? '');

        if (! $resposta->successful() || $id === '') {
            return ResultadoConciliacao::recusado(
                (string) ($resposta->json('error') ?: 'O Mastodon recusou esta publicação.')
            );
        }

        $destino->forceFill(['identificador_externo' => $id])->save();

        return ResultadoConciliacao::noAr((string) ($resposta->json('url') ?: $this->enderecoDoStatus($destino, $id)));
    }

    /** ⭐ Relê o status — a prova (DEC-31). */
    private function conferirStatus(Destino $destino, string $token): ResultadoConciliacao
    {
        try {
            $resposta = Http::withToken($token)->timeout(20)->get(
                $this->em((string) $destino->contaSocial->servidor, '/api/v1/statuses/'.$destino->identificador_externo)
            );
        } catch (ConnectionException) {
            return ResultadoConciliacao::aindaProcessando();
        }

        if ($resposta->successful() && $resposta->json('id')) {
            return ResultadoConciliacao::noAr(
                (string) ($resposta->json('url') ?: $this->enderecoDoStatus($destino, (string) $destino->identificador_externo))
            );
        }

        return $resposta->status() === 404
            ? ResultadoConciliacao::recusado('O post não está mais no Mastodon.')
            : ResultadoConciliacao::aindaProcessando();
    }

    private function erro(Response $resposta, string $servidor): ResultadoPublicacao
    {
        if ($resposta->status() === 401) {
            return ResultadoPublicacao::recusado(
                'A conexão com o Mastodon não está mais válida. Reconecte a conta para publicar.'
            );
        }

        if ($resposta->status() === 429) {
            return ResultadoPublicacao::semCota("O servidor «{$servidor}» pediu para esperar antes da próxima publicação.");
        }

        /*
         * ⚠️ **422 aqui costuma ser limite do SERVIDOR, não da rede.** Cada
         * Mastodon escolhe o próprio teto de arquivo, e o do vizinho é outro.
         * Por isso a frase nomeia o servidor: "o Mastodon recusou" mandaria a
         * pessoa procurar uma regra geral que não existe.
         */
        if ($resposta->status() === 422) {
            return ResultadoPublicacao::recusado(
                (string) ($resposta->json('error') ?: "O servidor «{$servidor}» não aceitou este arquivo.")
            );
        }

        return $resposta->serverError()
            ? ResultadoPublicacao::tentarDepois("O servidor «{$servidor}» teve um erro interno. Vamos tentar de novo.")
            : ResultadoPublicacao::recusado(
                (string) ($resposta->json('error') ?: "O servidor «{$servidor}» recusou o envio.")
            );
    }

    private function em(string $servidor, string $caminho): string
    {
        return "https://{$servidor}{$caminho}";
    }

    private function enderecoDoStatus(Destino $destino, string $id): string
    {
        return $this->em((string) $destino->contaSocial->servidor, '/web/statuses/'.$id);
    }
}

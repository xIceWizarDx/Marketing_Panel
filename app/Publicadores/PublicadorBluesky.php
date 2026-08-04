<?php

namespace App\Publicadores;

use App\Enums\Plataforma;
use App\Models\Destino;
use App\Support\Midia\EspecificacaoDaRede;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Bluesky — a primeira rede do projeto (DEC-29).
 *
 * Escolhida para começar por um motivo prático: **não depende de auditoria**.
 * A autenticação é por senha de aplicativo que a própria pessoa gera nas
 * configurações da conta, então dá pra publicar de verdade hoje, enquanto as
 * outras redes esperam aprovação por semanas.
 *
 * Fluxo do AT Protocol: abrir sessão → subir o arquivo (blob) → criar o post.
 */
class PublicadorBluesky implements Publicador
{
    private const SERVIDOR = 'https://bsky.social';

    private const APP_VIEW = 'https://bsky.app';

    public function plataforma(): Plataforma
    {
        return Plataforma::Bluesky;
    }

    /**
     * O Bluesky envia o arquivo de uma vez so, entao nao ha o que retomar: ou o
     * blob subiu inteiro, ou nao subiu.
     */
    public function publicar(Destino $destino, Retomada $retomada): ResultadoPublicacao
    {
        $conta = $destino->contaSocial;
        $credencial = $conta->credencial;

        if (! $credencial) {
            return ResultadoPublicacao::recusado(
                'A conexão com o Bluesky foi perdida. Reconecte a conta para publicar.'
            );
        }

        $texto = $destino->textoFinal();
        $especificacao = EspecificacaoDaRede::de(Plataforma::Bluesky);

        // Barra antes de gastar upload. O Bluesky conta GRAFEMAS: emoji de
        // família vale 1, não 7 — contar caracteres recusaria texto válido.
        foreach ($especificacao->conferirTextos(null, $texto) as $achado) {
            return ResultadoPublicacao::recusado($achado->mensagem.' '.$achado->providencia);
        }

        // ⚠️ O lexicon aceita SÓ `video/mp4`. O envio aceita `.mov` porque a
        // Meta aceita — aqui ele seria recusado depois do upload inteiro.
        $midia = $destino->publicacao->midia;

        if ($midia->ehVideo()) {
            $formato = $especificacao->conferirContainer($midia->mime_type);

            if ($formato !== null) {
                return ResultadoPublicacao::recusado($formato->mensagem.' '.$formato->providencia);
            }
        }

        try {
            $sessao = $this->abrirSessao($credencial->access_token, $conta->nome_exibicao);

            if ($sessao === null) {
                return ResultadoPublicacao::recusado(
                    'O Bluesky não aceitou a senha de aplicativo. Gere uma nova e reconecte a conta.'
                );
            }

            $blob = $this->enviarArquivo($sessao['accessJwt'], $destino);

            if ($blob === null) {
                return ResultadoPublicacao::tentarDepois('O envio do arquivo para o Bluesky não completou.');
            }

            return $this->criarPost($sessao, $destino, $texto, $blob);
        } catch (ConnectionException $e) {
            // Rede fora do ar ou timeout: passageiro, vale tentar de novo.
            return ResultadoPublicacao::tentarDepois('O Bluesky não respondeu a tempo.');
        }
    }

    public function conciliar(Destino $destino): ResultadoConciliacao
    {
        $conta = $destino->contaSocial;
        $credencial = $conta->credencial;

        if (! $credencial || ! $destino->identificador_externo) {
            return ResultadoConciliacao::recusado('Não há como conferir este post no Bluesky.');
        }

        try {
            $sessao = $this->abrirSessao($credencial->access_token, $conta->nome_exibicao);

            if ($sessao === null) {
                return ResultadoConciliacao::aindaProcessando();
            }

            // ⭐ AQUI nasce a prova: lemos o post de volta na rede. Se ele não
            // estiver lá, a publicação NÃO aconteceu — por mais que o envio
            // tenha respondido 200.
            $resposta = Http::withToken($sessao['accessJwt'])
                ->get(self::SERVIDOR.'/xrpc/com.atproto.repo.getRecord', [
                    'repo' => $sessao['did'],
                    'collection' => 'app.bsky.feed.post',
                    'rkey' => $this->chaveDoPost($destino->identificador_externo),
                ]);

            if ($resposta->successful()) {
                return ResultadoConciliacao::noAr(
                    $destino->url_publicada ?? $this->montarUrl($conta->nome_exibicao, $destino->identificador_externo)
                );
            }

            // 400 com "RecordNotFound" = o post sumiu (moderação apagou).
            if ($resposta->status() === 400) {
                return ResultadoConciliacao::recusado('O post não está mais no Bluesky.');
            }

            return ResultadoConciliacao::aindaProcessando();
        } catch (ConnectionException) {
            return ResultadoConciliacao::aindaProcessando();
        }
    }

    /** @return array{accessJwt: string, did: string}|null */
    private function abrirSessao(string $senhaDeAplicativo, string $identificador): ?array
    {
        $resposta = Http::asJson()->post(self::SERVIDOR.'/xrpc/com.atproto.server.createSession', [
            'identifier' => $identificador,
            'password' => $senhaDeAplicativo,
        ]);

        if (! $resposta->successful()) {
            return null;
        }

        return [
            'accessJwt' => $resposta->json('accessJwt'),
            'did' => $resposta->json('did'),
        ];
    }

    private function enviarArquivo(string $token, Destino $destino): ?array
    {
        $midia = $destino->publicacao->midia;
        $caminho = Storage::disk(config('midia.disco'))->path($midia->caminho);

        $resposta = Http::withToken($token)
            ->withBody(file_get_contents($caminho), $midia->mime_type)
            ->post(self::SERVIDOR.'/xrpc/com.atproto.repo.uploadBlob');

        return $resposta->successful() ? $resposta->json('blob') : null;
    }

    /** @param array{accessJwt: string, did: string} $sessao */
    private function criarPost(array $sessao, Destino $destino, string $texto, array $blob): ResultadoPublicacao
    {
        $midia = $destino->publicacao->midia;

        $registro = [
            '$type' => 'app.bsky.feed.post',
            'text' => $texto,
            'createdAt' => now()->toIso8601ZuluString(),
            'langs' => ['pt-BR'],
            'embed' => $midia->ehVideo()
                ? ['$type' => 'app.bsky.embed.video', 'video' => $blob]
                : ['$type' => 'app.bsky.embed.images', 'images' => [['image' => $blob, 'alt' => $texto]]],
        ];

        $resposta = Http::withToken($sessao['accessJwt'])
            ->asJson()
            ->post(self::SERVIDOR.'/xrpc/com.atproto.repo.createRecord', [
                'repo' => $sessao['did'],
                'collection' => 'app.bsky.feed.post',
                'record' => $registro,
            ]);

        if ($resposta->successful()) {
            $uri = $resposta->json('uri');

            return ResultadoPublicacao::aceito(
                $uri,
                $this->montarUrl($destino->contaSocial->nome_exibicao, $uri)
            );
        }

        return $this->interpretarErro($resposta);
    }

    private function interpretarErro(Response $resposta): ResultadoPublicacao
    {
        $codigo = (string) $resposta->status();
        $motivo = $resposta->json('message') ?? 'O Bluesky recusou a publicação.';

        return match (true) {
            // Limite de taxa: espera e tenta de novo, não é culpa do conteúdo.
            $resposta->status() === 429 => ResultadoPublicacao::tentarDepois(
                'O Bluesky pediu para esperar antes de publicar de novo.', $codigo
            ),
            $resposta->serverError() => ResultadoPublicacao::tentarDepois(
                'O Bluesky está com problema no servidor.', $codigo
            ),
            $resposta->status() === 401 => ResultadoPublicacao::recusado(
                'A senha de aplicativo do Bluesky não vale mais. Gere outra e reconecte a conta.', $codigo
            ),
            default => ResultadoPublicacao::recusado($motivo, $codigo),
        };
    }

    /** `at://did/app.bsky.feed.post/CHAVE` → `CHAVE` */
    private function chaveDoPost(string $uri): string
    {
        return (string) str($uri)->afterLast('/');
    }

    private function montarUrl(string $handle, string $uri): string
    {
        return self::APP_VIEW.'/profile/'.$handle.'/post/'.$this->chaveDoPost($uri);
    }
}

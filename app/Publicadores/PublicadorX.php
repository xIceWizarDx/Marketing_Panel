<?php

namespace App\Publicadores;

use App\Enums\Plataforma;
use App\Models\Destino;
use App\Models\Midia;
use App\Services\TokenDoX;
use App\Support\FalhaDeConexao;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * X — envio em pedaços numerados, e um post que **custa dinheiro**.
 *
 * ```
 * INIT     → POST /2/media/upload  command=INIT      → media_id
 * APPEND   → POST /2/media/upload  command=APPEND    → um por pedaço, por `segment_index`
 * FINALIZE → POST /2/media/upload  command=FINALIZE  → processing_info
 * STATUS   → GET  /2/media/upload  command=STATUS    → até `succeeded`
 * postar   → POST /2/tweets        { text, media }   → data.id
 * ```
 *
 * ⛔ **Aqui publicar tem preço** (DEC-126): US$ 0,015 por post, e **US$ 0,200 se
 * a legenda tiver link** — treze vezes mais. O aviso é da tela, porque quando
 * este código roda o gasto já aconteceu.
 *
 * ⭐ **E a prova também tem preço, baixo:** reler o próprio post é *owned read*,
 * US$ 0,001. É a primeira rede em que insistir gasta crédito de alguém, e não
 * só limite de uso (DEC-127).
 */
class PublicadorX implements Publicador
{
    private const MIDIA = 'https://api.x.com/2/media/upload';

    private const POSTS = 'https://api.x.com/2/tweets';

    /**
     * ⚠️ Um megabyte, que é o que os exemplos oficiais usam.
     *
     * ⛔ A documentação **não declara teto de pedaço**, e por isso o código
     * segue o exemplo em vez de inventar um número maior (DEC-132). Chutar 8 MB
     * porque "deve caber" seria descobrir o contrário com o arquivo já subindo.
     */
    private const PEDACO = 1024 * 1024;

    public function __construct(
        private readonly TokenDoX $tokens,
    ) {}

    public function plataforma(): Plataforma
    {
        return Plataforma::X;
    }

    public function publicar(Destino $destino, Retomada $retomada): ResultadoPublicacao
    {
        // ⭐ Renovado antes de começar: o token vive 2 horas e o envio em
        // pedaços de 1 MB pode levar minutos (DEC-130).
        $token = $this->tokens->valido($destino->contaSocial);

        if (! $token) {
            return ResultadoPublicacao::recusado(
                'A conexão com o X não está mais válida. Reconecte a conta para publicar.'
            );
        }

        $midia = $destino->publicacao->midia;

        if (! $midia?->ehVideo()) {
            return ResultadoPublicacao::recusado('O X recebe vídeo por aqui.');
        }

        try {
            /*
             * ⛔ Já começou antes? O `media_id` vale ~24 h, e mandar tudo de
             * novo custaria requisições — que aqui são cobradas — além de poder
             * duplicar o envio.
             */
            if ($retomada->comecouAntes()) {
                $pronto = $this->jaSubiu($retomada->handle(), $token);

                if ($pronto !== null) {
                    return $pronto;
                }
            }

            $mediaId = $this->iniciar($midia, $token);

            if ($mediaId instanceof ResultadoPublicacao) {
                return $mediaId;
            }

            // ⛔ Guardado antes do primeiro byte: é ele que impede um segundo
            // envio quando o processo morre no meio.
            $retomada->guardar($mediaId);

            $enviado = $this->enviarPedacos($midia, $mediaId, $token);

            if ($enviado !== null) {
                return $enviado;
            }

            return $this->finalizar($mediaId, $token);
        } catch (ConnectionException $erro) {
            return ResultadoPublicacao::tentarDepois(FalhaDeConexao::explicar($erro, 'X'));
        }
    }

    /**
     * ⭐ A prova (DEC-31) — **e o segundo passo da publicação**.
     *
     * O post só nasce depois de a rede terminar de processar o vídeo; esperar
     * dormindo seguraria um worker. Mesmo desenho do Threads (DEC-103) e do
     * LinkedIn (DEC-107).
     */
    public function conciliar(Destino $destino): ResultadoConciliacao
    {
        $token = $this->tokens->valido($destino->contaSocial);

        if (! $token || ! $destino->handle_externo) {
            return ResultadoConciliacao::recusado('Não há como conferir esta publicação no X.');
        }

        // Já postado: relemos o post — é a prova, e custa US$ 0,001.
        if ($destino->identificador_externo) {
            return $this->conferirPublicado($destino, $token);
        }

        try {
            $resposta = Http::withToken($token)->timeout(20)
                ->get(self::MIDIA, ['command' => 'STATUS', 'media_id' => $destino->handle_externo]);
        } catch (ConnectionException) {
            return ResultadoConciliacao::aindaProcessando();
        }

        if (! $resposta->successful()) {
            return ResultadoConciliacao::aindaProcessando();
        }

        return match ((string) $resposta->json('data.processing_info.state', 'succeeded')) {
            // ⛔ Só aqui o post pode nascer. Criar antes devolve erro de mídia
            // não pronta — o mesmo erro clássico do Instagram e do Threads.
            'succeeded' => $this->criarPost($destino, $token),
            'failed' => ResultadoConciliacao::recusado($this->motivoDaMidia($resposta)),
            default => ResultadoConciliacao::aindaProcessando(),
        };
    }

    /** O passo que de fato publica — e que custa. */
    private function criarPost(Destino $destino, string $token): ResultadoConciliacao
    {
        try {
            $resposta = Http::withToken($token)->timeout(30)->post(self::POSTS, [
                /*
                 * ⛔ **Título junto** — o X não tem campo separado, e o `text`
                 * do post é tudo que a pessoa escreveu.
                 *
                 * ⚠️ Mandar só `textoFinal()` deixava o título de fora sem
                 * avisar ninguém: era o mesmo defeito que Threads e TikTok já
                 * tiveram, e ele reapareceu na rede seguinte.
                 */
                'text' => trim(($destino->titulo() ?? '').' '.$destino->textoFinal()),
                'media' => ['media_ids' => [$destino->handle_externo]],
            ]);
        } catch (ConnectionException) {
            /*
             * ⛔ **Não se tenta de novo** — mesma razão do LinkedIn (DEC-125).
             * Criar post não é idempotente, e um tempo esgotado depois de a
             * rede ter recebido o pedido significa post publicado e resposta
             * perdida. A conciliação roda vinte vezes: repetir criaria vinte
             * posts, e cada um seria cobrado.
             */
            return ResultadoConciliacao::recusado(
                'O X não respondeu a tempo depois de receber o post. '.
                'Confira no X antes de publicar de novo: ele pode ter subido.'
            );
        }

        $id = (string) ($resposta->json('data.id') ?? '');

        if (! $resposta->successful() || $id === '') {
            return $this->erroDoPost($resposta);
        }

        $destino->forceFill(['identificador_externo' => $id])->save();

        return ResultadoConciliacao::noAr($this->enderecoDoPost($destino, $id));
    }

    /**
     * ⭐ Relê o post publicado — a prova, por US$ 0,001 (*owned read*).
     *
     * ⚠️ Post apagado ou removido pela moderação some daqui, e é justamente esse
     * o caso que nenhum concorrente pega.
     */
    private function conferirPublicado(Destino $destino, string $token): ResultadoConciliacao
    {
        try {
            $resposta = Http::withToken($token)->timeout(20)
                ->get(self::POSTS.'/'.$destino->identificador_externo);
        } catch (ConnectionException) {
            return ResultadoConciliacao::aindaProcessando();
        }

        if ($resposta->successful() && $resposta->json('data.id')) {
            return ResultadoConciliacao::noAr(
                $this->enderecoDoPost($destino, (string) $destino->identificador_externo)
            );
        }

        return $resposta->status() === 404
            ? ResultadoConciliacao::recusado('O post não está mais no X.')
            : ResultadoConciliacao::aindaProcessando();
    }

    /** O envio deste `media_id` já aconteceu? */
    private function jaSubiu(?string $mediaId, string $token): ?ResultadoPublicacao
    {
        if (! $mediaId) {
            return null;
        }

        try {
            $resposta = Http::withToken($token)->timeout(20)
                ->get(self::MIDIA, ['command' => 'STATUS', 'media_id' => $mediaId]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $resposta->successful()) {
            return null;
        }

        // ⚠️ `failed` é o único estado que autoriza refazer: qualquer outro quer
        // dizer que o arquivo já chegou lá.
        return (string) $resposta->json('data.processing_info.state', 'succeeded') === 'failed'
            ? null
            : ResultadoPublicacao::aceito($mediaId);
    }

    private function iniciar(Midia $midia, string $token): string|ResultadoPublicacao
    {
        $resposta = Http::withToken($token)->asMultipart()->timeout(30)->post(self::MIDIA, [
            ['name' => 'command', 'contents' => 'INIT'],
            ['name' => 'media_type', 'contents' => $midia->mime_type ?: 'video/mp4'],
            ['name' => 'total_bytes', 'contents' => (string) $midia->tamanho_bytes],
            // ⚠️ Obrigatório, e para vídeo de post o valor é este — não
            // `tweet_gif`, não `dm_video`.
            ['name' => 'media_category', 'contents' => 'tweet_video'],
        ]);

        $id = (string) ($resposta->json('data.id') ?? $resposta->json('media_id_string') ?? '');

        if (! $resposta->successful() || $id === '') {
            return $this->erroDoEnvio($resposta);
        }

        return $id;
    }

    /** Sobe os pedaços numerados. Devolve `null` quando tudo subiu. */
    private function enviarPedacos(Midia $midia, string $mediaId, string $token): ?ResultadoPublicacao
    {
        $caminho = Storage::disk(config('midia.disco'))->path($midia->caminho);
        $arquivo = @fopen($caminho, 'rb');

        if ($arquivo === false) {
            return ResultadoPublicacao::recusado('Não foi possível ler o arquivo para enviar.');
        }

        $indice = 0;

        try {
            while (! feof($arquivo)) {
                $pedaco = (string) fread($arquivo, self::PEDACO);

                if ($pedaco === '') {
                    break;
                }

                $resposta = Http::withToken($token)->asMultipart()->timeout(120)->post(self::MIDIA, [
                    ['name' => 'command', 'contents' => 'APPEND'],
                    ['name' => 'media_id', 'contents' => $mediaId],
                    /*
                     * ⭐ **A ordem é dita pelo NÚMERO do pedaço**, não por faixa
                     * de bytes (YouTube, TikTok) nem por ordem de recibos
                     * (LinkedIn). Quatro redes, quatro convenções — e nenhuma
                     * empresta código para a outra.
                     */
                    ['name' => 'segment_index', 'contents' => (string) $indice],
                    ['name' => 'media', 'contents' => $pedaco, 'filename' => 'pedaco'],
                ]);

                if (! $resposta->successful()) {
                    return $this->erroDoEnvio($resposta);
                }

                $indice++;
            }
        } finally {
            fclose($arquivo);
        }

        return null;
    }

    private function finalizar(string $mediaId, string $token): ResultadoPublicacao
    {
        $resposta = Http::withToken($token)->asMultipart()->timeout(30)->post(self::MIDIA, [
            ['name' => 'command', 'contents' => 'FINALIZE'],
            ['name' => 'media_id', 'contents' => $mediaId],
        ]);

        if (! $resposta->successful()) {
            return $this->erroDoEnvio($resposta);
        }

        // ⭐ DEC-31: aceito ≠ no ar. O post ainda nem existe — ele nasce na
        // conciliação, quando a rede terminar de processar o vídeo.
        return ResultadoPublicacao::aceito($mediaId);
    }

    private function motivoDaMidia(Response $resposta): string
    {
        $motivo = (string) ($resposta->json('data.processing_info.error.message')
            ?: $resposta->json('data.processing_info.error.name')
            ?: '');

        return $motivo !== ''
            ? "O X não conseguiu processar este vídeo: {$motivo}"
            : 'O X não conseguiu processar este vídeo.';
    }

    private function erroDoEnvio(Response $resposta): ResultadoPublicacao
    {
        /*
         * ⛔ **Crédito acabado também acontece AQUI**, não só na criação do post
         * — e a frase não pode mandar a pessoa mexer no vídeo. O que falta é
         * dinheiro no console do X.
         */
        if ($resposta->status() === 402) {
            return ResultadoPublicacao::recusado(
                'O X não aceitou o envio porque os créditos da API acabaram ou o limite de gasto foi atingido.'
            );
        }

        if ($resposta->status() === 401) {
            return ResultadoPublicacao::recusado(
                'A conexão com o X não está mais válida. Reconecte a conta para publicar.'
            );
        }

        /*
         * ⚠️ `403` no envio costuma ser falta de `media.write` — o escopo
         * separado que é fácil esquecer (DEC-131). A frase diz isso em vez de
         * mandar a pessoa procurar defeito no vídeo.
         */
        if ($resposta->status() === 403) {
            return ResultadoPublicacao::recusado(
                'O X não autorizou o envio do vídeo. Reconecte a conta e mantenha a permissão de enviar mídia marcada.'
            );
        }

        if ($resposta->status() === 429) {
            return ResultadoPublicacao::semCota('O X pediu para esperar antes da próxima publicação.');
        }

        return $resposta->serverError()
            ? ResultadoPublicacao::tentarDepois('O X teve um erro interno. Vamos tentar de novo.')
            : ResultadoPublicacao::recusado($this->frase($resposta, 'O X recusou o envio do vídeo.'));
    }

    private function erroDoPost(Response $resposta): ResultadoConciliacao
    {
        /*
         * ⛔ **`402` é crédito acabado, não erro de conteúdo** — e é exclusivo
         * desta rede. A frase não pode mandar a pessoa mexer no vídeo: o que
         * falta é dinheiro no console do X.
         */
        if ($resposta->status() === 402) {
            return ResultadoConciliacao::recusado(
                'O X não publicou porque os créditos da API acabaram ou o limite de gasto foi atingido.'
            );
        }

        if ($resposta->status() === 429 || $resposta->serverError()) {
            return ResultadoConciliacao::aindaProcessando();
        }

        return ResultadoConciliacao::recusado($this->frase($resposta, 'O X recusou esta publicação.'));
    }

    private function frase(Response $resposta, string $padrao): string
    {
        return (string) ($resposta->json('detail')
            ?: $resposta->json('title')
            ?: $resposta->json('errors.0.message')
            ?: $padrao);
    }

    private function enderecoDoPost(Destino $destino, string $id): string
    {
        $usuario = ltrim((string) $destino->contaSocial->nome_exibicao, '@');

        return "https://x.com/{$usuario}/status/{$id}";
    }
}

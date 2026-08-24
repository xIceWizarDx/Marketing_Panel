<?php

namespace App\Publicadores;

use App\Enums\Plataforma;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Midia;
use App\Services\TokenDoTiktok;
use App\Support\FalhaDeConexao;
use App\Support\Tiktok\FichaDoCriador;
use App\Support\Tiktok\PedacosDoEnvio;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * TikTok — a rede que separa "subiu" de "a moderação aprovou".
 *
 * ```
 * perguntar → POST /v2/post/publish/creator_info/query/  OBRIGATÓRIO antes (DEC-117)
 * iniciar   → POST /v2/post/publish/video/init/          → publish_id + upload_url
 * subir     → PUT  {upload_url}                          → em pedaços, EM SEQUÊNCIA
 * conferir  → POST /v2/post/publish/status/fetch/        → a PROVA
 * ```
 *
 * ⭐ **Aqui a tese do produto vem implementada pela própria rede** (DEC-115). O
 * `publicaly_available_post_id` só aparece *"for public posts approved by
 * moderation"*: `PUBLISH_COMPLETE` sem ele quer dizer subiu e ainda não
 * liberado. É a prova mais forte de todas as redes do painel.
 *
 * ⛔ **E ela não existe enquanto o aplicativo não for auditado** (DEC-116): post
 * privado nunca recebe esse identificador.
 */
class PublicadorTiktok implements LeitorDeMetricas, Publicador
{
    private const API = 'https://open.tiktokapis.com/v2';

    /**
     * Motivos de falha, em português.
     *
     * ⭐ **Só `internal` volta para a fila** (DEC-123) — é o único que a
     * documentação marca como *retryable*. Todo o resto é recusa de conteúdo,
     * de conta ou de formato: repetir dá o mesmo resultado três vezes.
     */
    private const MOTIVOS = [
        'file_format_check_failed' => 'O TikTok não aceita o formato deste arquivo.',
        'duration_check_failed' => 'A duração do vídeo está fora do que o TikTok aceita.',
        'frame_rate_check_failed' => 'A taxa de quadros do vídeo não é aceita pelo TikTok.',
        'picture_size_check_failed' => 'As dimensões do vídeo não são aceitas pelo TikTok.',
        'video_pull_failed' => 'O TikTok não conseguiu buscar o vídeo.',
        'photo_pull_failed' => 'O TikTok não conseguiu buscar a imagem.',
        'publish_cancelled' => 'O envio para o TikTok foi cancelado.',
        // ⚠️ Não é falha de vídeo nem de rede: a pessoa tirou a autorização no
        // aplicativo do TikTok. Dizer "falhou" mandaria ela procurar defeito no
        // arquivo dela.
        'auth_removed' => 'A autorização do TikTok foi removida nesta conta. Reconecte para publicar.',
        'spam_risk_too_many_posts' => 'A conta atingiu o limite de publicações do TikTok nas últimas 24 horas.',
        'spam_risk_user_banned_from_posting' => 'O TikTok está impedindo publicações nesta conta.',
        'spam_risk_text' => 'O TikTok marcou a legenda como arriscada. Reescreva e tente de novo.',
        'spam_risk' => 'O TikTok marcou esta publicação como arriscada.',
    ];

    public function __construct(
        private readonly TokenDoTiktok $tokens,
    ) {}

    public function plataforma(): Plataforma
    {
        return Plataforma::Tiktok;
    }

    public function publicar(Destino $destino, Retomada $retomada): ResultadoPublicacao
    {
        /*
         * ⭐ Renovado ANTES de começar (DEC-118). O token vive 24 horas, e um
         * envio de vídeo grande sobe em pedaços sequenciais: começar válido e
         * terminar vencido é um caminho real aqui, não teórico.
         */
        $token = $this->tokens->valido($destino->contaSocial);

        if (! $token) {
            return ResultadoPublicacao::recusado(
                'A conexão com o TikTok não está mais válida. Reconecte a conta para publicar.'
            );
        }

        $midia = $destino->publicacao->midia;

        if (! $midia?->ehVideo()) {
            return ResultadoPublicacao::recusado('O TikTok recebe vídeo por aqui.');
        }

        /*
         * ⛔ **Sem auditoria não há o que provar, então não se publica** (DEC-124).
         *
         * ⚠️ A DEC-116 dizia "mesma resposta do YouTube: publica privado e a
         * tela diz por quê" — e estava errada. No YouTube o vídeo privado TEM
         * endereço; aqui o `publicaly_available_post_id` só vem para post
         * público aprovado, então um post privado **nunca** ganha link.
         *
         * ⛔ E `marcarPublicado()` recusa destino sem link, de propósito: é o
         * DEC-31 em forma de guarda. Publicar aqui só poderia terminar em
         * "falhou" depois de o vídeo ter subido de verdade — o painel dizendo
         * que não subiu o que subiu, e ainda oferecendo republicar, que
         * duplicaria o vídeo.
         */
        if (! config('services.tiktok.auditado', false)) {
            return ResultadoPublicacao::recusado(
                'Enquanto a auditoria do TikTok não sair, o vídeo só pode subir como privado — e a rede '.
                'não devolve link de post privado, então não teríamos como provar que publicou.'
            );
        }

        try {
            /*
             * ⛔ **Já começou antes?** Sem esta pergunta, um envio que terminou
             * e cuja resposta se perdeu — tempo esgotado, processo morto —
             * voltaria para a fila, criaria um SEGUNDO `publish_id` e subiria o
             * vídeo de novo. **Dois vídeos publicados**, e publicação não tem
             * desfazer.
             *
             * ⚠️ Era o buraco que o YouTube já tinha tapado com `quantoJaSubiu`
             * e o LinkedIn com `jaSubiu`. Aqui faltava.
             */
            if ($retomada->comecouAntes()) {
                $pronto = $this->jaEnviou($retomada->handle(), $token);

                if ($pronto !== null) {
                    return $pronto;
                }
            }

            // ⛔ Obrigatório pela documentação — e enforçado (DEC-117).
            $ficha = $this->perguntarAoCriador($token);

            if ($ficha instanceof ResultadoPublicacao) {
                return $ficha;
            }

            /*
             * ⭐ A duração é conferida contra o teto DESTA CONTA, não da rede
             * (DEC-117). Contas novas têm limite menor, e descobrir isso depois
             * do envio inteiro gastaria a cota da pessoa para nada.
             */
            $longoDemais = $ficha->recusaPorDuracao($midia->duracao_segundos);

            if ($longoDemais !== null) {
                return ResultadoPublicacao::recusado($longoDemais);
            }

            $pedacos = PedacosDoEnvio::de($midia->tamanho_bytes);

            if ($pedacos === null) {
                return ResultadoPublicacao::recusado(
                    'O arquivo passa do tamanho que o TikTok aceita (4 GB).'
                );
            }

            $inicio = $this->iniciar($destino, $ficha, $pedacos, $token);

            if ($inicio instanceof ResultadoPublicacao) {
                return $inicio;
            }

            /*
             * ⛔ Guardado ANTES do primeiro byte. Se o processo morrer no meio
             * do envio, é ele que impede a próxima tentativa de criar um
             * segundo envio — e publicação não tem desfazer.
             */
            $retomada->guardar($inicio['publish_id']);

            return $this->subir($midia, $inicio['upload_url'], $pedacos, $inicio['publish_id']);
        } catch (ConnectionException $erro) {
            return ResultadoPublicacao::tentarDepois(FalhaDeConexao::explicar($erro, 'TikTok'));
        }
    }

    /**
     * ⭐ A prova (DEC-115) — e ela tem DOIS degraus.
     *
     * ⛔ Parar em `PUBLISH_COMPLETE` seria o erro que o produto critica: a rede
     * aceitou, e o post pode não estar visível para ninguém. O identificador só
     * vem *"for public posts approved by moderation"*.
     */
    public function conciliar(Destino $destino): ResultadoConciliacao
    {
        $token = $this->tokens->valido($destino->contaSocial);

        if (! $token || ! $destino->handle_externo) {
            return ResultadoConciliacao::recusado('Não há como conferir esta publicação no TikTok.');
        }

        try {
            $resposta = Http::withToken($token)->timeout(20)
                ->post(self::API.'/post/publish/status/fetch/', ['publish_id' => $destino->handle_externo]);
        } catch (ConnectionException) {
            return ResultadoConciliacao::aindaProcessando();
        }

        /*
         * ⛔ **HTTP 200 com erro dentro é a armadilha desta API** (DEC-121).
         * Confiar no código HTTP trataria `invalid_publish_id` como sucesso, e
         * o destino ficaria esperando para sempre por um post que não existe.
         */
        $codigo = (string) $resposta->json('error.code', 'ok');

        if ($codigo !== 'ok' && $codigo !== '') {
            return $this->erroDaConferencia($codigo);
        }

        return match ((string) $resposta->json('data.status')) {
            'PUBLISH_COMPLETE' => $this->desfecho($destino, $resposta),
            'FAILED' => ResultadoConciliacao::recusado($this->motivo((string) $resposta->json('data.fail_reason'))),
            // `PROCESSING_UPLOAD`, `PROCESSING_DOWNLOAD` e `SEND_TO_USER_INBOX`
            // caem aqui: nenhum é desfecho.
            default => ResultadoConciliacao::aindaProcessando(),
        };
    }

    /**
     * ⭐ Publicado — mas liberado pela moderação?
     *
     * ⚠️ É a diferença que nenhum concorrente mostra, porque nenhum relê o post.
     */
    private function desfecho(Destino $destino, Response $resposta): ResultadoConciliacao
    {
        $ids = (array) $resposta->json('data.publicaly_available_post_id', []);
        $id = (string) ($ids[0] ?? '');

        if ($id !== '') {
            $destino->forceFill(['identificador_externo' => $id])->save();

            return ResultadoConciliacao::noAr("https://www.tiktok.com/video/{$id}");
        }

        /*
         * ⛔ Subiu e a moderação ainda não se pronunciou. Dizer "no ar" aqui
         * seria afirmar o que a rede não afirmou.
         *
         * ⚠️ O caso do aplicativo não auditado — em que o identificador **nunca**
         * viria — não chega até aqui: ele é recusado antes de subir (DEC-124),
         * justamente para não terminar em "falhou" depois de o vídeo ter subido.
         */
        return ResultadoConciliacao::aindaProcessando();
    }

    /**
     * O envio deste `publish_id` já aconteceu?
     *
     * Devolve o resultado pronto quando não há mais nada a enviar, ou `null`
     * quando o envio precisa acontecer (ou recomeçar do zero).
     *
     * ⚠️ Custa **uma** requisição e evita subir o arquivo inteiro de novo — e
     * evita, principalmente, publicar duas vezes.
     */
    private function jaEnviou(?string $publishId, string $token): ?ResultadoPublicacao
    {
        if (! $publishId) {
            return null;
        }

        try {
            $resposta = Http::withToken($token)->timeout(20)
                ->post(self::API.'/post/publish/status/fetch/', ['publish_id' => $publishId]);
        } catch (ConnectionException) {
            return null;
        }

        // ⚠️ Erro dentro do 200 também aqui (DEC-121): `invalid_publish_id`
        // significa que aquele envio não existe mais, e aí recomeçar é o certo.
        if (! $resposta->successful() || (string) $resposta->json('error.code', 'ok') !== 'ok') {
            return null;
        }

        /*
         * ⛔ Só `FAILED` autoriza recomeçar. Qualquer outro estado — subindo,
         * processando, publicado — quer dizer que o arquivo já chegou lá, e
         * mandar de novo criaria um segundo vídeo.
         */
        return (string) $resposta->json('data.status') === 'FAILED'
            ? null
            : ResultadoPublicacao::aceito($publishId);
    }

    private function perguntarAoCriador(string $token): FichaDoCriador|ResultadoPublicacao
    {
        $resposta = Http::withToken($token)->timeout(20)
            ->post(self::API.'/post/publish/creator_info/query/');

        $codigo = (string) $resposta->json('error.code', 'ok');

        if (! $resposta->successful() || ($codigo !== 'ok' && $codigo !== '')) {
            return $this->interpretar($resposta, $codigo);
        }

        return FichaDoCriador::daResposta((array) $resposta->json('data', []));
    }

    /**
     * @param  array{tamanho: int, pedaco: int, total: int}  $pedacos
     * @return array{publish_id: string, upload_url: string}|ResultadoPublicacao
     */
    private function iniciar(Destino $destino, FichaDoCriador $ficha, array $pedacos, string $token): array|ResultadoPublicacao
    {
        /*
         * ⛔ **`textoFinal()`, não a legenda crua** — ele junta legenda e
         * hashtags e respeita o texto próprio do destino.
         *
         * ⚠️ Montar à mão jogava as hashtags fora, e no TikTok elas **são** o
         * mecanismo de descoberta: um post sem hashtag aqui é um post que
         * ninguém acha.
         */
        $legenda = trim(($destino->titulo() ?? '').' '.$destino->textoFinal());

        $resposta = Http::withToken($token)->timeout(30)
            ->post(self::API.'/post/publish/video/init/', [
                // ⚠️ `array_filter` com `!== null`: campo nulo não pode viajar.
                // `is_aigc: null` seria a rede recebendo uma declaração vazia.
                'post_info' => array_filter([
                    'title' => $legenda,
                    'privacy_level' => $ficha->privacidade(),
                    // ⚠️ Respeitar o que a conta desligou é obrigatório: o
                    // `creator_info` diz, e ignorar seria publicar com uma
                    // configuração que a pessoa recusou no aplicativo dela.
                    'disable_comment' => $ficha->comentarioDesligado,
                    'disable_duet' => $ficha->duetoDesligado,
                    'disable_stitch' => $ficha->stitchDesligado,
                    /*
                     * ⭐ **A declaração de conteúdo feito por IA** (DEC-169).
                     *
                     * ⚠️ A pessoa marca isso no compositor, o Instagram já
                     * recebia (`is_ai_generated`) e o TikTok **não** — a mesma
                     * caixinha, marcada uma vez, valia numa rede e sumia na
                     * outra. Declaração de IA não é preferência de interface: é
                     * transparência com quem assiste, e some sem ninguém notar.
                     *
                     * ⛔ Escolha da pessoa, nunca padrão nosso: `false` sai do
                     * pedido em vez de ir declarado como "não é IA".
                     */
                    'is_aigc' => ($destino->opcoes['feito_com_ia'] ?? false) ? true : null,
                ], fn ($valor) => $valor !== null),
                'source_info' => [
                    'source' => 'FILE_UPLOAD',
                    'video_size' => $pedacos['tamanho'],
                    'chunk_size' => $pedacos['pedaco'],
                    'total_chunk_count' => $pedacos['total'],
                ],
            ]);

        $publishId = (string) ($resposta->json('data.publish_id') ?? '');
        $codigo = (string) $resposta->json('error.code', 'ok');

        if (! $resposta->successful() || $publishId === '' || ($codigo !== 'ok' && $codigo !== '')) {
            return $this->interpretar($resposta, $codigo);
        }

        return [
            'publish_id' => $publishId,
            'upload_url' => (string) $resposta->json('data.upload_url'),
        ];
    }

    /**
     * @param  array{tamanho: int, pedaco: int, total: int}  $pedacos
     */
    private function subir(Midia $midia, string $url, array $pedacos, string $publishId): ResultadoPublicacao
    {
        $caminho = Storage::disk(config('midia.disco'))->path($midia->caminho);
        $arquivo = @fopen($caminho, 'rb');

        if ($arquivo === false) {
            return ResultadoPublicacao::recusado('Não foi possível ler o arquivo para enviar.');
        }

        $total = $pedacos['tamanho'];

        try {
            // ⛔ **EM SEQUÊNCIA.** A documentação é literal: *"File chunks must
            // be uploaded sequentially"* — paralelo não é permitido.
            foreach (PedacosDoEnvio::intervalos($pedacos) as [$de, $ate]) {
                fseek($arquivo, $de);
                $conteudo = (string) fread($arquivo, $ate - $de + 1);

                $resposta = Http::withHeaders([
                    'Content-Type' => $midia->mime_type ?: 'video/mp4',
                    'Content-Range' => "bytes {$de}-{$ate}/{$total}",
                ])->withBody($conteudo, $midia->mime_type ?: 'video/mp4')->timeout(120)->put($url);

                if (! $resposta->successful()) {
                    // ⚠️ O `upload_url` vale UMA HORA. Vencido, o envio recomeça
                    // do início — e recomeçar é envio novo, não repetição.
                    return $resposta->status() === 403 || $resposta->status() === 404
                        ? ResultadoPublicacao::recusado('O prazo de envio para o TikTok venceu. Envie de novo.')
                        : ResultadoPublicacao::tentarDepois('O TikTok não aceitou uma parte do vídeo.');
                }
            }
        } finally {
            fclose($arquivo);
        }

        // ⭐ DEC-31: aceito ≠ no ar. E aqui nem publicado ≠ no ar (DEC-115).
        return ResultadoPublicacao::aceito($publishId);
    }

    /** A frase em português para o motivo que a rede deu. */
    private function motivo(string $codigo): string
    {
        if ($codigo === 'internal') {
            return 'O TikTok teve um erro interno ao processar o vídeo.';
        }

        return self::MOTIVOS[$codigo] ?? 'O TikTok recusou esta publicação.';
    }

    /** Erro do `status/fetch` — dentro de um HTTP 200 (DEC-121). */
    private function erroDaConferencia(string $codigo): ResultadoConciliacao
    {
        return match ($codigo) {
            'invalid_publish_id', 'token_not_authorized_for_specified_publish_id' => ResultadoConciliacao::recusado(
                'O TikTok não reconhece mais este envio. Publique de novo.'
            ),
            // Token e limite se resolvem sozinhos na próxima passada.
            default => ResultadoConciliacao::aindaProcessando(),
        };
    }

    private function interpretar(Response $resposta, string $codigo): ResultadoPublicacao
    {
        return match ($codigo) {
            /*
             * ⚠️ **Não é culpa da pessoa** (DEC-123). É o NOSSO aplicativo que
             * estourou a cota de usuários do dia, e a saída é esperar — não
             * reconectar, nem mexer no vídeo.
             */
            'reached_active_user_cap' => ResultadoPublicacao::semCota(
                'O TikTok limitou o uso do painel por hoje. Vamos tentar de novo mais tarde.'
            ),
            'spam_risk_too_many_posts' => ResultadoPublicacao::semCota(
                'A conta atingiu o limite de publicações do TikTok nas últimas 24 horas.'
            ),
            'spam_risk_user_banned_from_posting' => ResultadoPublicacao::recusado(
                'O TikTok está impedindo publicações nesta conta.'
            ),
            /*
             * ⛔ Este é o aplicativo sem auditoria tentando publicar em público
             * (DEC-116). A frase diz o que está acontecendo, em vez de mandar a
             * pessoa procurar defeito no vídeo.
             */
            'unaudited_client_can_only_post_to_private_accounts' => ResultadoPublicacao::recusado(
                'Enquanto a auditoria do TikTok não sair, o painel só consegue publicar como privado nesta conta.'
            ),
            'privacy_level_option_mismatch' => ResultadoPublicacao::recusado(
                'Esta conta do TikTok não permite a privacidade escolhida para o post.'
            ),
            'access_token_invalid', 'scope_not_authorized' => ResultadoPublicacao::recusado(
                'A conexão com o TikTok não está mais válida. Reconecte a conta para publicar.'
            ),
            'rate_limit_exceeded' => ResultadoPublicacao::tentarDepois(
                'O TikTok pediu para esperar um pouco antes da próxima publicação.'
            ),
            default => $resposta->serverError()
                ? ResultadoPublicacao::tentarDepois('O TikTok teve um erro interno. Vamos tentar de novo.')
                : ResultadoPublicacao::recusado(
                    (string) ($resposta->json('error.message') ?: 'O TikTok recusou o envio.')
                ),
        };
    }

    /**
     * ⭐ Seguidores da conta.
     *
     * ⚠️ `follower_count` vem do mesmo `user/info` que já busca nome e avatar —
     * não custa chamada nem permissão nova.
     */
    public function metricasDaConta(ContaSocial $conta): ?MetricasDaConta
    {
        $token = $this->tokens->valido($conta);

        if (! $token) {
            return null;
        }

        try {
            $resposta = Http::withToken($token)->timeout(20)
                ->get('https://open.tiktokapis.com/v2/user/info/', ['fields' => 'follower_count']);
        } catch (ConnectionException) {
            return null;
        }

        if (! $resposta->successful() || (string) $resposta->json('error.code', 'ok') !== 'ok') {
            return null;
        }

        $seguidores = $resposta->json('data.user.follower_count');

        return new MetricasDaConta(seguidores: is_numeric($seguidores) ? (int) $seguidores : null);
    }

    /**
     * ⭐ Os contadores do vídeo publicado.
     *
     * ⚠️ Exige o escopo `video.list` (DEC-143) — que, apesar do nome, lê só os
     * vídeos **da conta autorizada**.
     *
     * ⛔ E o identificador usado aqui é o do POST, não o do envio: o
     * `publicaly_available_post_id` que só chega depois da moderação aprovar
     * (DEC-115). Vídeo ainda não liberado não tem número para ler — e isso é
     * `null`, não zero.
     */
    public function metricasDoPost(Destino $destino): ?MetricasDoPost
    {
        $token = $this->tokens->valido($destino->contaSocial);

        if (! $token || ! $destino->identificador_externo) {
            return null;
        }

        try {
            $resposta = Http::withToken($token)->timeout(20)->post(
                self::API.'/video/query/?fields=id,view_count,like_count,comment_count,share_count',
                // ⚠️ Ate 20 ids por chamada. Lemos um por vez porque a
                // conciliacao e por destino — agrupar exigiria outro desenho.
                ['filters' => ['video_ids' => [$destino->identificador_externo]]]
            );
        } catch (ConnectionException) {
            return null;
        }

        // ⛔ Erro dentro do 200, como em todo o resto desta API (DEC-121).
        if (! $resposta->successful() || (string) $resposta->json('error.code', 'ok') !== 'ok') {
            return null;
        }

        $video = $resposta->json('data.videos.0');

        if (! is_array($video)) {
            return null;
        }

        return new MetricasDoPost(
            visualizacoes: $this->numero($video, 'view_count'),
            curtidas: $this->numero($video, 'like_count'),
            comentarios: $this->numero($video, 'comment_count'),
            compartilhamentos: $this->numero($video, 'share_count'),
        );
    }

    /** @param array<string, mixed> $video */
    private function numero(array $video, string $campo): ?int
    {
        // ⚠️ Campo ausente e campo zerado sao coisas diferentes (DEC-95).
        return isset($video[$campo]) && is_numeric($video[$campo]) ? (int) $video[$campo] : null;
    }
}

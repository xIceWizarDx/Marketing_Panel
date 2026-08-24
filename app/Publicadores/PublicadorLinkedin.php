<?php

namespace App\Publicadores;

use App\Enums\Plataforma;
use App\Models\Destino;
use App\Models\Midia;
use App\Services\ConexaoComLinkedin;
use App\Support\FalhaDeConexao;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * LinkedIn — o vídeo é um recurso próprio, e o post vem depois.
 *
 * ```
 * inicializar → POST /rest/videos?action=initializeUpload   → URN + pedaços
 * subir       → PUT  {uploadUrl de cada pedaço}             → ETag de cada um
 * finalizar   → POST /rest/videos?action=finalizeUpload     → junta os pedaços
 * postar      → POST /rest/posts                            → 201 + URN no CABEÇALHO
 * ```
 *
 * ⛔ **Aqui a prova da DEC-31 não existe inteira, e isso é da rede, não do
 * código** (DEC-106). Reler um post exige `r_member_social`, que é restrita a
 * aprovados. Dá para conferir que o vídeo foi aceito e processado; não dá para
 * conferir que o post continua no ar depois.
 *
 * ⭐ **O que dá para provar é a falha que importa:** o vídeo que a rede aceita e
 * depois recusa aparece no `status` do vídeo — e esse a permissão de escrita lê.
 *
 * ⚠️ **O post é criado na conciliação, não no envio** (DEC-107). Criar antes de
 * o vídeo ficar `AVAILABLE` devolve `MEDIA_ASSET_WAITING_UPLOAD`, e esperar
 * dormindo seguraria um worker.
 */
class PublicadorLinkedin implements Publicador
{
    private const API = 'https://api.linkedin.com/rest';

    /** Separa o URN do vídeo do `uploadToken` dentro do `handle_externo`. */
    private const SEPARADOR = '|';

    /**
     * Motivos de falha do processamento, em português.
     *
     * ⚠️ A documentação não publica a lista fechada de `processingFailureReason`
     * — o que ela lista são os códigos de erro das chamadas. Por isso o
     * casamento é por **pedaço**, e o que não casar cai numa frase honesta em
     * vez de num código cru em inglês.
     */
    private const MOTIVOS = [
        'ASPECT' => 'O LinkedIn recusou a proporção do vídeo.',
        'DURATION' => 'A duração do vídeo está fora do que o LinkedIn aceita (3 segundos a 30 minutos).',
        'SIZE' => 'O arquivo está fora do tamanho que o LinkedIn aceita.',
        'FORMAT' => 'O LinkedIn só aceita vídeo em MP4.',
        'CODEC' => 'O LinkedIn não conseguiu ler a codificação deste vídeo.',
        'AUDIO' => 'O LinkedIn não conseguiu processar o áudio deste vídeo.',
    ];

    public function plataforma(): Plataforma
    {
        return Plataforma::Linkedin;
    }

    public function publicar(Destino $destino, Retomada $retomada): ResultadoPublicacao
    {
        $conta = $destino->contaSocial;
        $token = $conta->credencial?->access_token;

        if (! $token) {
            /*
             * ⚠️ Frase diferente das outras redes de propósito: aqui o token
             * vence sozinho em 60 dias e **não existe renovação em segundo
             * plano** (DEC-112). "Reconecte" não é um conselho genérico — é
             * literalmente a única saída.
             */
            return ResultadoPublicacao::recusado(
                'A conexão com o LinkedIn venceu. Reconecte a conta para publicar.'
            );
        }

        $midia = $destino->publicacao->midia;

        if (! $midia?->ehVideo()) {
            return ResultadoPublicacao::recusado('O LinkedIn recebe vídeo por aqui.');
        }

        try {
            /*
             * ⭐ Já começou antes? O URN do vídeo existe, e o que ele já
             * recebeu continua valendo.
             *
             * ⚠️ Perguntar custa UMA requisição e pode economizar dez. A cota
             * do LinkedIn é contada em requisições, não em posts (DEC-113): um
             * reenvio cego de um vídeo de 40 MB queima 12 das 150 do dia da
             * pessoa para refazer o que já estava feito.
             */
            if ($retomada->comecouAntes()) {
                $pronto = $this->jaSubiu($retomada->handle(), $token);

                if ($pronto !== null) {
                    return $pronto;
                }
            }

            $inicio = $this->inicializar($conta->identificador_externo, $midia->tamanho_bytes, $token);

            if ($inicio instanceof ResultadoPublicacao) {
                return $inicio;
            }

            /*
             * ⛔ Guardado ANTES do primeiro byte (DEC-108). O URN já existe, e
             * se o processo morrer no meio do envio é ele que impede a próxima
             * tentativa de criar um segundo vídeo.
             */
            $retomada->guardar($inicio['urn'].self::SEPARADOR.$inicio['token']);

            $recibos = $this->subirPedacos($midia, $inicio['pedacos'], $token);

            if ($recibos instanceof ResultadoPublicacao) {
                return $recibos;
            }

            return $this->finalizar($inicio['urn'], $inicio['token'], $recibos, $token);
        } catch (ConnectionException $erro) {
            return ResultadoPublicacao::tentarDepois(FalhaDeConexao::explicar($erro, 'LinkedIn'));
        }
    }

    /**
     * ⭐ A prova possível — **e o segundo passo da publicação** (DEC-107).
     *
     * ⚠️ Sem `r_member_social` não há como reler o post. O que se confere aqui
     * é o **vídeo**: se ele ficou disponível, o post é criado; se ele falhou, a
     * pessoa fica sabendo o motivo que a rede deu.
     */
    public function conciliar(Destino $destino): ResultadoConciliacao
    {
        $token = $destino->contaSocial->credencial?->access_token;

        if (! $token || ! $destino->handle_externo) {
            return ResultadoConciliacao::recusado('Não há como conferir esta publicação no LinkedIn.');
        }

        /*
         * ⛔ Já postado: **não relemos, porque a rede não deixa** (DEC-106).
         * Devolver o endereço sem gastar requisição é o mais honesto que dá —
         * e a tela é que diz que aqui não houve conferência.
         */
        if ($destino->identificador_externo) {
            return ResultadoConciliacao::noAr($this->enderecoDoPost($destino->identificador_externo));
        }

        [$urnDoVideo] = $this->partesDoHandle($destino->handle_externo);

        try {
            $resposta = Http::withHeaders($this->cabecalhos($token))->timeout(20)
                ->get(self::API.'/videos/'.rawurlencode($urnDoVideo));
        } catch (ConnectionException) {
            return ResultadoConciliacao::aindaProcessando();
        }

        if (! $resposta->successful()) {
            return ResultadoConciliacao::aindaProcessando();
        }

        return match ((string) $resposta->json('status')) {
            'AVAILABLE' => $this->criarPost($destino, $urnDoVideo, $token),
            'PROCESSING_FAILED' => ResultadoConciliacao::recusado(
                $this->motivo((string) $resposta->json('processingFailureReason'))
            ),
            // `WAITING_UPLOAD` também cai aqui: é pressa nossa, não falha dela.
            default => ResultadoConciliacao::aindaProcessando(),
        };
    }

    /** O passo que de fato publica. */
    private function criarPost(Destino $destino, string $urnDoVideo, string $token): ResultadoConciliacao
    {
        $publicacao = $destino->publicacao;
        /*
         * ⛔ **`textoFinal()`, não a legenda crua.** Ele junta legenda e
         * hashtags e respeita o texto próprio do destino.
         *
         * ⚠️ Montar à mão jogava as hashtags fora — o mesmo defeito que Threads,
         * TikTok e X já tiveram. Aqui ele passou despercebido porque o título
         * tem campo próprio (`content.media.title`), e a atenção ficou nele.
         */
        $legenda = $destino->textoFinal();

        try {
            $resposta = Http::withHeaders($this->cabecalhos($token))->timeout(30)
                ->post(self::API.'/posts', [
                    'author' => ConexaoComLinkedin::urnDaPessoa($destino->contaSocial->identificador_externo),
                    'commentary' => $legenda,
                    'visibility' => 'PUBLIC',
                    'distribution' => [
                        'feedDistribution' => 'MAIN_FEED',
                        'targetEntities' => [],
                        'thirdPartyDistributionChannels' => [],
                    ],
                    'content' => ['media' => array_filter([
                        'title' => $publicacao->titulo,
                        'id' => $urnDoVideo,
                    ])],
                    // ⚠️ `PUBLISHED` é o único valor aceito na criação.
                    'lifecycleState' => 'PUBLISHED',
                    'isReshareDisabledByAuthor' => false,
                ]);
        } catch (ConnectionException) {
            /*
             * ⛔ **Aqui NÃO se tenta de novo** (DEC-125), e é o oposto do que o
             * resto do código faz com tempo esgotado.
             *
             * ⚠️ Criar post não é idempotente e o LinkedIn não aceita chave de
             * repetição. Um tempo esgotado **depois** de a rede ter recebido o
             * pedido significa post publicado e resposta perdida — e a
             * conciliação roda até vinte vezes. Devolver "ainda processando"
             * criaria um segundo post, um terceiro, um quarto.
             *
             * ⛔ E não dá para conferir antes de criar: reler post exige
             * `r_member_social`, que é restrita (DEC-106).
             *
             * Entre repetir e duplicar, ou parar e avisar, o produto para e
             * avisa — publicação não tem desfazer.
             */
            return ResultadoConciliacao::recusado(
                'O LinkedIn não respondeu a tempo depois de receber o post. '.
                'Confira no LinkedIn antes de publicar de novo: ele pode ter subido.'
            );
        }

        /*
         * ⛔ **O identificador vem no CABEÇALHO, e o corpo vem vazio** (DEC-111).
         *
         * ⚠️ Procurá-lo no JSON acharia `null`, e o motor concluiria que
         * falhou — com o post já publicado. Na passada seguinte, publicaria de
         * novo. Publicação não tem desfazer.
         */
        $urnDoPost = (string) ($resposta->header('x-restli-id') ?: '');

        if (! $resposta->successful() || $urnDoPost === '') {
            return $this->interpretarConciliacao($resposta);
        }

        $destino->forceFill(['identificador_externo' => $urnDoPost])->save();

        return ResultadoConciliacao::noAr($this->enderecoDoPost($urnDoPost));
    }

    /**
     * O vídeo deste `handle` já subiu inteiro?
     *
     * Devolve o resultado pronto quando não há mais nada a enviar, ou `null`
     * quando o envio precisa acontecer (ou refazer).
     */
    private function jaSubiu(?string $handle, string $token): ?ResultadoPublicacao
    {
        if (! $handle) {
            return null;
        }

        [$urn] = $this->partesDoHandle($handle);

        try {
            $resposta = Http::withHeaders($this->cabecalhos($token))->timeout(20)
                ->get(self::API.'/videos/'.rawurlencode($urn));
        } catch (ConnectionException) {
            return null;
        }

        if (! $resposta->successful()) {
            return null;
        }

        // ⚠️ `WAITING_UPLOAD` quer dizer que o arquivo NÃO chegou: aí o envio
        // tem que acontecer de verdade.
        return in_array((string) $resposta->json('status'), ['PROCESSING', 'AVAILABLE'], true)
            ? ResultadoPublicacao::aceito($urn)
            : null;
    }

    /**
     * Registra o envio e recebe os pedaços.
     *
     * @return array{urn: string, token: string, pedacos: list<array{url: string, de: int, ate: int}>}|ResultadoPublicacao
     */
    private function inicializar(string $identificador, int $tamanho, string $token): array|ResultadoPublicacao
    {
        $resposta = Http::withHeaders($this->cabecalhos($token))->timeout(30)
            ->post(self::API.'/videos?action=initializeUpload', [
                'initializeUploadRequest' => [
                    'owner' => ConexaoComLinkedin::urnDaPessoa($identificador),
                    'fileSizeBytes' => $tamanho,
                    'uploadCaptions' => false,
                    'uploadThumbnail' => false,
                ],
            ]);

        $urn = (string) ($resposta->json('value.video') ?? '');

        if (! $resposta->successful() || $urn === '') {
            return $this->interpretar($resposta);
        }

        $pedacos = [];

        foreach ((array) $resposta->json('value.uploadInstructions', []) as $instrucao) {
            $pedacos[] = [
                'url' => (string) ($instrucao['uploadUrl'] ?? ''),
                /*
                 * ⛔ **Os limites saem daqui, nunca do exemplo da
                 * documentação** (DEC-109). Ela manda `split -b 4194303` e
                 * devolve o intervalo `0`–`4194303`, que inclusive dá
                 * 4.194.304 bytes: os dois números não fecham. Seguir o exemplo
                 * deixaria cada pedaço um byte curto, e o erro só apareceria em
                 * arquivo grande, com o vídeo montado errado no fim.
                 */
                'de' => (int) ($instrucao['firstByte'] ?? 0),
                'ate' => (int) ($instrucao['lastByte'] ?? 0),
            ];
        }

        if ($pedacos === []) {
            return ResultadoPublicacao::tentarDepois('O LinkedIn não devolveu para onde enviar o vídeo.');
        }

        return [
            'urn' => $urn,
            // ⚠️ Vem string vazia no exemplo oficial, e mesmo assim é
            // obrigatório repetir o valor ao finalizar.
            'token' => (string) ($resposta->json('value.uploadToken') ?? ''),
            'pedacos' => $pedacos,
        ];
    }

    /**
     * Sobe cada pedaço e junta os recibos.
     *
     * @param  list<array{url: string, de: int, ate: int}>  $pedacos
     * @return list<string>|ResultadoPublicacao
     */
    private function subirPedacos(Midia $midia, array $pedacos, string $token): array|ResultadoPublicacao
    {
        $caminho = Storage::disk(config('midia.disco'))->path($midia->caminho);
        $arquivo = @fopen($caminho, 'rb');

        if ($arquivo === false) {
            return ResultadoPublicacao::recusado('Não foi possível ler o arquivo para enviar.');
        }

        $recibos = [];

        try {
            foreach ($pedacos as $pedaco) {
                fseek($arquivo, $pedaco['de']);
                $conteudo = (string) fread($arquivo, $pedaco['ate'] - $pedaco['de'] + 1);

                $resposta = Http::withHeaders(['Authorization' => "Bearer {$token}"])
                    ->withBody($conteudo, 'application/octet-stream')
                    ->timeout(120)
                    ->put($pedaco['url']);

                if (! $resposta->successful()) {
                    // ⚠️ `401` aqui é URL de envio vencida, não token ruim — e
                    // recomeçar é um envio novo, não uma repetição.
                    return $resposta->status() === 401
                        ? ResultadoPublicacao::recusado('O prazo de envio para o LinkedIn venceu. Envie de novo.')
                        : ResultadoPublicacao::tentarDepois('O LinkedIn não aceitou uma parte do vídeo.');
                }

                /*
                 * ⭐ **O recibo de cada parte, e a ORDEM importa** (DEC-110).
                 * Fora de ordem, o vídeo monta embaralhado — e nada na resposta
                 * avisa.
                 */
                $recibos[] = trim((string) $resposta->header('etag'), '"');
            }
        } finally {
            fclose($arquivo);
        }

        return $recibos;
    }

    /**
     * @param  list<string>  $recibos
     */
    private function finalizar(string $urn, string $tokenDeEnvio, array $recibos, string $token): ResultadoPublicacao
    {
        $resposta = Http::withHeaders($this->cabecalhos($token))->timeout(30)
            ->post(self::API.'/videos?action=finalizeUpload', [
                'finalizeUploadRequest' => [
                    'video' => $urn,
                    'uploadToken' => $tokenDeEnvio,
                    'uploadedPartIds' => $recibos,
                ],
            ]);

        if (! $resposta->successful()) {
            return $this->interpretar($resposta);
        }

        // ⭐ DEC-31: aceito ≠ no ar. O post ainda nem existe — ele nasce na
        // conciliação, quando o vídeo ficar disponível (DEC-107).
        return ResultadoPublicacao::aceito($urn);
    }

    /** A frase em português para o motivo que a rede deu. */
    private function motivo(string $codigo): string
    {
        foreach (self::MOTIVOS as $chave => $frase) {
            if (str_contains(strtoupper($codigo), $chave)) {
                return $frase;
            }
        }

        return 'O LinkedIn não conseguiu processar este vídeo.';
    }

    /**
     * ⭐ Passageiro ou não — **sem campo da rede para consultar** (DEC-114).
     *
     * ⚠️ Diferente da Meta, o LinkedIn não diz se o erro passa. A separação sai
     * do código HTTP, e a lista é curta o bastante para não virar adivinhação.
     */
    private function ehPassageiro(Response $resposta): bool
    {
        return in_array($resposta->status(), [409, 429, 500, 502, 503, 504], true)
            || str_contains((string) $resposta->json('code'), 'MEDIA_ASSET_WAITING_UPLOAD');
    }

    private function interpretar(Response $resposta): ResultadoPublicacao
    {
        /*
         * ⛔ **`401` não volta para a fila.** Aqui ele quer dizer token vencido,
         * e como não existe renovação em segundo plano (DEC-112), repetir só
         * queima tentativa contra algo que nunca passa sozinho.
         */
        if ($resposta->status() === 401) {
            return ResultadoPublicacao::recusado(
                'A conexão com o LinkedIn venceu. Reconecte a conta para publicar.'
            );
        }

        // ⚠️ Limite de uso é ESPERA, não falha (DEC-24 e DEC-113) — e ele é
        // contado em requisições, então chega antes do que parece.
        if ($resposta->status() === 429) {
            return ResultadoPublicacao::semCota(
                'A conta atingiu o limite de uso do LinkedIn por hoje. Vamos tentar de novo mais tarde.'
            );
        }

        $mensagem = $this->frase($resposta);

        return $this->ehPassageiro($resposta)
            ? ResultadoPublicacao::tentarDepois($mensagem)
            : ResultadoPublicacao::recusado($mensagem);
    }

    private function interpretarConciliacao(Response $resposta): ResultadoConciliacao
    {
        if ($this->ehPassageiro($resposta) || $resposta->status() === 401) {
            return ResultadoConciliacao::aindaProcessando();
        }

        return ResultadoConciliacao::recusado($this->frase($resposta));
    }

    private function frase(Response $resposta): string
    {
        return match ((string) $resposta->json('code')) {
            'EXPIRED_UPLOAD_URL' => 'O prazo de envio para o LinkedIn venceu. Envie de novo.',
            'ACCESS_DENIED' => 'O LinkedIn não autorizou esta publicação. Reconecte a conta e mantenha todas as permissões marcadas.',
            'FIELD_LENGTH_TOO_LONG' => 'O texto passou do limite que o LinkedIn aceita.',
            'MEDIA_ASSET_PROCESSING_FAILED' => 'O LinkedIn não conseguiu processar este vídeo.',
            default => (string) ($resposta->json('message') ?: 'O LinkedIn recusou o envio.'),
        };
    }

    /** @return array{0: string, 1: string} */
    private function partesDoHandle(string $handle): array
    {
        $partes = explode(self::SEPARADOR, $handle, 2);

        return [$partes[0], $partes[1] ?? ''];
    }

    private function enderecoDoPost(string $urn): string
    {
        return "https://www.linkedin.com/feed/update/{$urn}/";
    }

    /**
     * ⚠️ Os dois cabeçalhos são obrigatórios em TODA chamada da API versionada.
     * Sem eles a rede responde erro, e o erro não diz que faltou cabeçalho.
     */
    private function cabecalhos(string $token): array
    {
        return [
            'Authorization' => "Bearer {$token}",
            'LinkedIn-Version' => (string) config('services.linkedin.versao'),
            'X-Restli-Protocol-Version' => '2.0.0',
        ];
    }
}

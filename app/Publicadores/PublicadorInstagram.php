<?php

namespace App\Publicadores;

use App\Enums\Plataforma;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Services\ContaSocialService;
use App\Services\Meta\EnvioRetomavel;
use App\Services\Meta\ErroDaMeta;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Instagram — reels, em container.
 *
 * ```
 * container → POST /<ig>/media           { media_type: REELS, upload_type: resumable }
 * upload    → POST rupload.facebook.com/ig-api-upload/<container>
 * publicar  → POST /<ig>/media_publish   { creation_id: <container> }
 * ```
 *
 * ⛔ **Criar o container NÃO publica.** É a armadilha central desta rede: o
 * container é criado, o vídeo sobe, tudo responde sucesso — e o post não existe.
 * Só a chamada `media_publish` publica, e é nela que o limite diário é cobrado.
 *
 * ⚠️ O container **expira em 24 horas**. Se a fila atrasar além disso, o envio se
 * perde — e a mensagem precisa dizer isso, não "erro desconhecido".
 */
class PublicadorInstagram implements LeitorDeMetricas, Publicador
{
    private const API = 'https://graph.facebook.com/v25.0';

    public function plataforma(): Plataforma
    {
        return Plataforma::Instagram;
    }

    public function publicar(Destino $destino, Retomada $retomada): ResultadoPublicacao
    {
        $midia = $destino->publicacao->midia;

        if (! $midia->ehVideo()) {
            return ResultadoPublicacao::recusado(
                'O reel do Instagram é só de vídeo. Para imagem, use as outras redes.'
            );
        }

        $token = $destino->contaSocial->credencial?->access_token;

        if (! $token) {
            return ResultadoPublicacao::recusado(
                'A conexão com o Instagram não está mais válida. Reconecte a conta para publicar.'
            );
        }

        try {
            $conteudo = Storage::disk(config('midia.disco'))->get($midia->caminho);

            $container = $retomada->comecouAntes()
                ? $retomada->handle()
                : $this->criarContainer($token, $destino, $retomada);

            if ($container instanceof ResultadoPublicacao) {
                return $container;
            }

            $envio = $this->enviarArquivo($token, $container, $conteudo, $retomada->comecouAntes(), $destino);

            if ($envio instanceof ResultadoPublicacao) {
                return $envio;
            }

            // ⛔ Sem este passo nada é publicado, por mais que tudo antes tenha
            // respondido sucesso.
            return $this->publicarContainer($token, $destino, $container);
        } catch (ConnectionException) {
            return ResultadoPublicacao::tentarDepois('O Instagram não respondeu a tempo.');
        }
    }

    /**
     * ⭐ A prova (DEC-31) — e mais: o aviso de direito autoral.
     *
     * O container carrega `copyright_check_status`, ou seja, **a rede dizendo se
     * o áudio do corte vai derrubar o vídeo**. É a mesma tese do produto aplicada
     * antes do fato, e nenhum concorrente mostra isso.
     */
    public function conciliar(Destino $destino): ResultadoConciliacao
    {
        $token = $destino->contaSocial->credencial?->access_token;

        if (! $token || ! $destino->identificador_externo) {
            return ResultadoConciliacao::aindaProcessando();
        }

        try {
            $resposta = Http::withToken($token)->timeout(30)
                ->get(self::API.'/'.$destino->identificador_externo, [
                    // `media_product_type`, NÃO `media_type`: um reel publicado
                    // responde `VIDEO` no segundo, o que faria a conciliação
                    // dizer "vídeo comum" para todo reel.
                    'fields' => 'permalink,media_product_type',
                ]);
        } catch (ConnectionException) {
            return ResultadoConciliacao::aindaProcessando();
        }

        if (! $resposta->successful()) {
            $erro = ErroDaMeta::de($resposta);

            return $erro->passageiro
                ? ResultadoConciliacao::aindaProcessando()
                : ResultadoConciliacao::recusado($erro->mensagem);
        }

        $link = $resposta->json('permalink');

        // Sem link não há prova, e sem prova não é publicado (DEC-31).
        return $link
            ? ResultadoConciliacao::noAr($link)
            : ResultadoConciliacao::aindaProcessando();
    }

    /**
     * Passo 1: cria o container e guarda o identificador ANTES de subir bytes.
     *
     * @return string|ResultadoPublicacao o container, ou o motivo da recusa
     */
    private function criarContainer(
        string $token,
        Destino $destino,
        Retomada $retomada,
    ): string|ResultadoPublicacao {
        $opcoes = $destino->opcoes ?? [];

        $resposta = Http::withToken($token)->timeout(30)
            ->post(self::API.'/'.$destino->contaSocial->identificador_externo.'/media', array_filter([
                'media_type' => 'REELS',
                // Sem isto a Meta tentaria BAIXAR o vídeo de uma URL pública —
                // e o arquivo do cliente não fica exposto na internet.
                'upload_type' => 'resumable',
                // ⛔ SEM CORTAR. Quem recusa antes é o `EnvioDePublicacao`.
                /*
                 * ⛔ **Título junto** — o Reels não tem campo de título, e sem
                 * isto o que a pessoa escreveu ali desaparece sem aviso.
                 */
                'caption' => trim(($destino->titulo() ?? '').' '.$destino->textoFinal()),
                // Declaração de conteúdo feito por IA — escolha da pessoa, nunca
                // padrão nosso escondido no código.
                'is_ai_generated' => ($opcoes['feito_com_ia'] ?? false) ? 'true' : null,
            ], fn ($valor) => $valor !== null && $valor !== ''));

        if (! $resposta->successful()) {
            return $this->interpretar($resposta, $destino);
        }

        $container = (string) $resposta->json('id');

        if ($container === '') {
            return ResultadoPublicacao::tentarDepois('O Instagram não devolveu o identificador do envio.');
        }

        $retomada->guardar($container);

        return $container;
    }

    private function enviarArquivo(
        string $token,
        string $container,
        string $conteudo,
        bool $retomando,
        Destino $destino,
    ): true|ResultadoPublicacao {
        $envio = EnvioRetomavel::paraInstagram();

        $deslocamento = $retomando ? $envio->jaRecebidos($token, $container) : 0;

        $resposta = $envio->enviar($token, $container, $conteudo, $deslocamento);

        if (! $resposta->successful() || $resposta->json('success') === false) {
            return $this->interpretar($resposta, $destino);
        }

        return true;
    }

    /**
     * Passo 3: **este** é o que publica.
     *
     * ⚠️ Se o container ainda estiver processando, a Meta responde `2207027` —
     * que não é falha, é "espere". Devolver para a fila é o certo; recusar
     * derrubaria uma publicação que ia dar certo.
     */
    private function publicarContainer(
        string $token,
        Destino $destino,
        string $container,
    ): ResultadoPublicacao {
        $resposta = Http::withToken($token)->timeout(60)
            ->post(self::API.'/'.$destino->contaSocial->identificador_externo.'/media_publish', [
                'creation_id' => $container,
            ]);

        if (! $resposta->successful()) {
            return $this->interpretar($resposta, $destino);
        }

        $midia = (string) $resposta->json('id');

        if ($midia === '') {
            return ResultadoPublicacao::tentarDepois('O Instagram não confirmou a publicação.');
        }

        // ⭐ DEC-31: o identificador muda aqui — o container morre e nasce a
        // mídia. É a mídia que a conciliação relê para provar que está no ar.
        return ResultadoPublicacao::aceito($midia);
    }

    private function interpretar(Response $resposta, ?Destino $destino = null): ResultadoPublicacao
    {
        $erro = ErroDaMeta::de($resposta);

        if ($erro->limiteDiario) {
            return ResultadoPublicacao::semCota($erro->mensagem);
        }

        /*
         * ⭐ **Autorização morta acende o semáforo** (DEC-159).
         *
         * ⛔ Sem isto a conta ficava **verde e "Conectada"** na tela enquanto
         * recusava toda publicação — e o semáforo do token é justamente o que
         * este produto diz fazer melhor que os outros (DEC-32).
         *
         * ⚠️ Aqui a conta do Instagram publica com o token **da Página**: quando
         * ele morre, quem fica em pé é o Instagram, e é a conta dele que precisa
         * ficar vermelha na tela — senão a pessoa reconecta o Facebook e acha
         * que resolveu.
         */
        if ($erro->credencialMorreu && $destino) {
            app(ContaSocialService::class)->marcarComProblema($destino->contaSocial, $erro->mensagem);
        }

        return $erro->passageiro
            ? ResultadoPublicacao::tentarDepois($erro->mensagem, (string) $erro->subcodigo)
            : ResultadoPublicacao::recusado($erro->mensagem, (string) $erro->subcodigo);
    }

    /**
     * ⭐ Seguidores da conta profissional.
     *
     * ⚠️ `followers_count` vem no PRÓPRIO objeto da conta, não em `insights` —
     * e por isso não custa a permissão de leitura de métrica.
     */
    public function metricasDaConta(ContaSocial $conta): ?MetricasDaConta
    {
        $token = $conta->credencial?->access_token;

        if (! $token) {
            return null;
        }

        try {
            $resposta = Http::timeout(20)->get(self::API.'/'.$conta->identificador_externo, [
                'fields' => 'followers_count',
                'access_token' => $token,
            ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $resposta->successful()) {
            return null;
        }

        $seguidores = $resposta->json('followers_count');

        return new MetricasDaConta(seguidores: is_numeric($seguidores) ? (int) $seguidores : null);
    }

    /**
     * ⭐ Os contadores do Reels publicado.
     *
     * ⚠️ **Duas chamadas, e é de propósito.** Curtida e comentário vêm no objeto
     * da mídia (`like_count`, `comments_count`) e **não custam permissão**;
     * visualização e compartilhamento só existem em `insights`, que exige
     * `instagram_manage_insights` (DEC-143).
     *
     * ⛔ Assim, quem recusar a permissão de leitura **continua vendo curtida e
     * comentário** — em vez de a tela inteira ficar vazia.
     *
     * ⚠️ E `views` é o nome atual: a Meta aposentou `impressions`, `plays` e
     * `video_views` e juntou os três num só. Pedir os antigos devolve erro.
     */
    public function metricasDoPost(Destino $destino): ?MetricasDoPost
    {
        $token = $destino->contaSocial->credencial?->access_token;

        if (! $token || ! $destino->identificador_externo) {
            return null;
        }

        $basico = $this->lerMidia($destino->identificador_externo, $token);

        if ($basico === null) {
            return null;
        }

        $insights = $this->lerInsights($destino->identificador_externo, $token);

        return new MetricasDoPost(
            visualizacoes: $insights['views'] ?? null,
            curtidas: $basico['like_count'] ?? null,
            comentarios: $basico['comments_count'] ?? null,
            compartilhamentos: $insights['shares'] ?? null,
        );
    }

    /**
     * Curtida e comentário — sem custo de permissão.
     *
     * @return array<string, int>|null `null` = não deu para ler agora
     */
    private function lerMidia(string $id, string $token): ?array
    {
        try {
            $resposta = Http::timeout(20)->get(self::API.'/'.$id, [
                'fields' => 'like_count,comments_count',
                'access_token' => $token,
            ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $resposta->successful()) {
            return null;
        }

        return array_filter(
            ['like_count' => $resposta->json('like_count'), 'comments_count' => $resposta->json('comments_count')],
            fn ($valor) => is_numeric($valor)
        );
    }

    /**
     * Visualização e compartilhamento — exigem `instagram_manage_insights`.
     *
     * ⚠️ Devolve lista vazia quando a permissão não foi concedida, e **isso não
     * é erro**: é a diferença entre "esta conta não autorizou" e "a rede caiu".
     * Quem recusou continua vendo curtida e comentário.
     *
     * @return array<string, int>
     */
    private function lerInsights(string $id, string $token): array
    {
        try {
            $resposta = Http::timeout(20)->get(self::API.'/'.$id.'/insights', [
                'metric' => 'views,shares',
                'access_token' => $token,
            ]);
        } catch (ConnectionException) {
            return [];
        }

        if (! $resposta->successful()) {
            return [];
        }

        $numeros = [];

        foreach ((array) $resposta->json('data', []) as $item) {
            $nome = $item['name'] ?? null;
            $valor = $item['values'][0]['value'] ?? null;

            if (is_string($nome) && is_numeric($valor)) {
                $numeros[$nome] = (int) $valor;
            }
        }

        return $numeros;
    }
}

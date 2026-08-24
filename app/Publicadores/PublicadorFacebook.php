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
 * Facebook — reels de Página, em três fases.
 *
 * ```
 * start   → POST /<pagina>/video_reels  { upload_phase: start }   → video_id
 * upload  → POST rupload.facebook.com/video-upload/<video_id>
 * finish  → POST /<pagina>/video_reels  { upload_phase: finish }  → publica
 * ```
 *
 * ⚠️ **Só publica em Página.** Não existe endpoint para perfil pessoal — não é
 * questão de permissão, o caminho não existe. Quem tem só perfil não tem o que
 * conectar, e a tela precisa dizer isso antes de a pessoa tentar.
 *
 * ⛔ **90 segundos** é o teto do reel aqui — o mais curto de todas as redes. Um
 * corte de 2 minutos publica no Instagram e é recusado aqui, então a recusa vem
 * da `EspecificacaoDaRede`, antes de qualquer byte subir.
 */
class PublicadorFacebook implements LeitorDeMetricas, Publicador
{
    private const API = 'https://graph.facebook.com/v25.0';

    public function plataforma(): Plataforma
    {
        return Plataforma::Facebook;
    }

    public function publicar(Destino $destino, Retomada $retomada): ResultadoPublicacao
    {
        $midia = $destino->publicacao->midia;

        if (! $midia->ehVideo()) {
            return ResultadoPublicacao::recusado(
                'O reel do Facebook é só de vídeo. Para imagem, use as outras redes.'
            );
        }

        $token = $destino->contaSocial->credencial?->access_token;

        if (! $token) {
            return ResultadoPublicacao::recusado(
                'A conexão com o Facebook não está mais válida. Reconecte a Página para publicar.'
            );
        }

        try {
            $conteudo = Storage::disk(config('midia.disco'))->get($midia->caminho);

            // Retoma o envio anterior — é o que impede o mesmo vídeo de subir
            // duas vezes quando a fila reentrega o job.
            $video = $retomada->comecouAntes()
                ? $retomada->handle()
                : $this->iniciarSessao($token, $destino, $retomada);

            if ($video instanceof ResultadoPublicacao) {
                return $video;
            }

            $envio = $this->enviarArquivo($token, $video, $conteudo, $retomada->comecouAntes(), $destino);

            if ($envio instanceof ResultadoPublicacao) {
                return $envio;
            }

            return $this->publicarReel($token, $destino, $video);
        } catch (ConnectionException) {
            return ResultadoPublicacao::tentarDepois('O Facebook não respondeu a tempo.');
        }
    }

    /**
     * ⭐ A prova (DEC-31).
     *
     * ⚠️ O status vem separado por fase, e conciliar pela fase errada marcaria
     * publicado cedo demais: `processing_phase: completed` significa que a Meta
     * terminou de processar o arquivo — **não** que o post está no ar. Só
     * `publishing_phase.publish_status == published` é publicação.
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
                    // ⚠️ SÓ campo documentado. O Graph API não devolve nulo para
                    // campo que não existe: **derruba a chamada inteira** com o
                    // erro 100 ("Tried accessing nonexisting field"). Pedir um
                    // `permalink_url` — que não está na lista do nó Video —
                    // faria TODA conciliação falhar, e cada publicação do
                    // Facebook seria marcada como falha sem ter falhado.
                    'fields' => 'status',
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

        $publicacao = $resposta->json('status.publishing_phase') ?? [];
        $processamento = $resposta->json('status.processing_phase') ?? [];

        // Erro em qualquer uma das fases é falha — mas a mensagem vem da fase
        // que falhou, senão a pessoa lê "erro" sem saber onde.
        if (($processamento['status'] ?? null) === 'error') {
            return ResultadoConciliacao::recusado(
                $processamento['error']['message'] ?? 'O Facebook não conseguiu processar o vídeo.'
            );
        }

        return match ($publicacao['publish_status'] ?? null) {
            // O endereço é montado, não pedido: o nó Video não documenta campo
            // de link, e pedir um inexistente derrubaria a chamada (erro 100).
            'published' => ResultadoConciliacao::noAr(
                'https://www.facebook.com/reel/'.$destino->identificador_externo
            ),
            'error' => ResultadoConciliacao::recusado(
                $publicacao['error']['message'] ?? 'O Facebook recusou a publicação.'
            ),
            // `draft` e `scheduled` são estados legítimos que NÃO são publicação.
            // Marcar publicado aqui seria mentir com a resposta certa na mão.
            'draft' => ResultadoConciliacao::recusado(
                'O vídeo subiu como rascunho e não foi publicado. Publique pelo Facebook.'
            ),
            default => ResultadoConciliacao::aindaProcessando(),
        };
    }

    /**
     * Fase 1: abre a sessão e guarda o `video_id` ANTES de qualquer byte subir.
     *
     * @return string|ResultadoPublicacao o `video_id`, ou o motivo da recusa
     */
    private function iniciarSessao(
        string $token,
        Destino $destino,
        Retomada $retomada,
    ): string|ResultadoPublicacao {
        $resposta = Http::withToken($token)->timeout(30)
            ->post(self::API.'/'.$destino->contaSocial->identificador_externo.'/video_reels', [
                'upload_phase' => 'start',
            ]);

        if (! $resposta->successful()) {
            return $this->interpretar($resposta, $destino);
        }

        $video = (string) $resposta->json('video_id');

        if ($video === '') {
            return ResultadoPublicacao::tentarDepois('O Facebook não devolveu o identificador do envio.');
        }

        // ⚠️ Guardar ANTES de enviar: sem isso, um timeout de rede faz a próxima
        // tentativa recomeçar do zero e o mesmo vídeo sobe duas vezes.
        $retomada->guardar($video);

        return $video;
    }

    private function enviarArquivo(
        string $token,
        string $video,
        string $conteudo,
        bool $retomando,
        Destino $destino,
    ): true|ResultadoPublicacao {
        $envio = EnvioRetomavel::paraFacebook();

        // Só pergunta quanto já subiu quando REALMENTE está retomando. Numa
        // sessão nova a resposta é sempre zero, e a viagem é desperdiçada.
        $deslocamento = $retomando ? $envio->jaRecebidos($token, $video) : 0;

        $resposta = $envio->enviar($token, $video, $conteudo, $deslocamento);

        if (! $resposta->successful()) {
            return $this->interpretar($resposta, $destino);
        }

        return true;
    }

    /** Fase 3: é esta chamada que publica de verdade. */
    private function publicarReel(string $token, Destino $destino, string $video): ResultadoPublicacao
    {
        $opcoes = $destino->opcoes ?? [];
        $agendado = $opcoes['publicar_em'] ?? null;

        $resposta = Http::withToken($token)->timeout(60)
            ->post(self::API.'/'.$destino->contaSocial->identificador_externo.'/video_reels', array_filter([
                'video_id' => $video,
                // ⚠️ `finish`. A documentação se contradiz — o texto ao lado diz
                // `complete`, mas o exemplo executável usa `finish`, e é o
                // exemplo que a Meta testa.
                'upload_phase' => 'finish',
                'video_state' => $agendado !== null ? 'SCHEDULED' : 'PUBLISHED',
                // ⛔ SEM CORTAR. Quem recusa antes é o `EnvioDePublicacao`,
                // dizendo quanto sobra — a política proíbe alterar o que a
                // pessoa escreveu sem consentimento.
                'description' => $destino->textoFinal(),
                'title' => $destino->titulo(),
                'scheduled_publish_time' => $agendado !== null
                    ? strtotime((string) $agendado)
                    : null,
            ], fn ($valor) => $valor !== null && $valor !== ''));

        if (! $resposta->successful()) {
            return $this->interpretar($resposta, $destino);
        }

        // ⭐ DEC-31: aceito ≠ no ar. A confirmação é da conciliação, não daqui.
        return ResultadoPublicacao::aceito($video);
    }

    private function interpretar(Response $resposta, ?Destino $destino = null): ResultadoPublicacao
    {
        $erro = ErroDaMeta::de($resposta);

        // ⚠️ Limite diário é ESPERA, não falha (DEC-24). Tratar como erro
        // queimaria as 3 tentativas contra uma cota que só volta amanhã.
        if ($erro->limiteDiario) {
            return ResultadoPublicacao::semCota($erro->mensagem);
        }

        /*
         * ⭐ **Autorização morta acende o semáforo** (DEC-159).
         *
         * ⛔ Sem isto a conta ficava **verde e "Conectada"** na tela enquanto
         * recusava toda publicação — e o semáforo do token é justamente o que
         * este produto diz fazer melhor que os outros (DEC-32).
         */
        if ($erro->credencialMorreu && $destino) {
            app(ContaSocialService::class)->marcarComProblema($destino->contaSocial, $erro->mensagem);
        }

        // ⭐ A rede diz se vale tentar de novo, no `is_transient`. No YouTube
        // tivemos que deduzir isso do código HTTP — e erramos duas vezes.
        return $erro->passageiro
            ? ResultadoPublicacao::tentarDepois($erro->mensagem, (string) $erro->subcodigo)
            : ResultadoPublicacao::recusado($erro->mensagem, (string) $erro->subcodigo);
    }

    /**
     * ⭐ Seguidores da Página.
     *
     * ⚠️ `followers_count` vem no objeto da Página e **não custa permissão de
     * métrica** — diferente das visualizações do vídeo.
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
     * ⭐ Os contadores do vídeo publicado na Página.
     *
     * ⚠️ **Duas chamadas, pela mesma razão do Instagram:** curtida e comentário
     * vêm no objeto do vídeo e não custam permissão; a visualização vive em
     * `video_insights`, que exige `read_insights` (DEC-143).
     *
     * ⛔ **A visualização do reel não é "quem viu"**: `blue_reels_play_count` é
     * *quantas vezes o reel começou a tocar depois de aparecer para alguém*.
     * Uma pessoa que passou por ele três vezes conta três. É por isso que somar
     * este número com o do TikTok e o do YouTube só serve para sentir tamanho,
     * nunca para comparar redes (DEC-146).
     */
    public function metricasDoPost(Destino $destino): ?MetricasDoPost
    {
        $token = $destino->contaSocial->credencial?->access_token;

        if (! $token || ! $destino->identificador_externo) {
            return null;
        }

        $basico = $this->lerVideo($destino->identificador_externo, $token);

        if ($basico === null) {
            return null;
        }

        return new MetricasDoPost(
            visualizacoes: $this->lerVisualizacoes($destino->identificador_externo, $token),
            curtidas: $basico['curtidas'],
            comentarios: $basico['comentarios'],
            // ⚠️ O Facebook nao publica compartilhamento de video por aqui:
            // `null`, nunca zero (DEC-95).
            compartilhamentos: null,
        );
    }

    /**
     * @return array{curtidas: ?int, comentarios: ?int}|null `null` = nao deu para ler agora
     */
    private function lerVideo(string $id, string $token): ?array
    {
        try {
            $resposta = Http::timeout(20)->get(self::API.'/'.$id, [
                'fields' => 'likes.summary(true).limit(0),comments.summary(true).limit(0)',
                'access_token' => $token,
            ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $resposta->successful()) {
            return null;
        }

        $curtidas = $resposta->json('likes.summary.total_count');
        $comentarios = $resposta->json('comments.summary.total_count');

        return [
            'curtidas' => is_numeric($curtidas) ? (int) $curtidas : null,
            'comentarios' => is_numeric($comentarios) ? (int) $comentarios : null,
        ];
    }

    /**
     * ⛔ **As métricas de REEL, que não são as de vídeo** (DEC-157).
     *
     * ⚠️ Aqui pedia-se `total_video_views` — e ele **não existe para reel**. A
     * referência do `video_insights` separa a lista em duas: *Video metrics*,
     * onde `total_video_views` mora, e *Reels metrics*, que usa outros nomes
     * (`blue_reels_play_count`, `fb_reels_total_plays`). Publicamos por
     * `/video_reels`, ou seja, **tudo o que publicamos é reel** — e a chamada
     * voltava sem o número, sempre, calada.
     *
     * ⛔ O efeito era o pior possível para este produto: a tela dizia "sem
     * leitura" para o Facebook **para sempre**, e isso parece a rede não ter
     * respondido, e não um campo pedido pelo nome errado.
     *
     * ⚠️ Dois nomes porque medem coisas diferentes: `blue_reels_play_count` é
     * quantas vezes o reel **começou a tocar**; `fb_reels_total_plays` conta
     * **reprises** junto. O primeiro é o mais próximo de "quantos viram", e o
     * segundo só entra se o primeiro não vier.
     */
    private const METRICAS_DO_REEL = ['blue_reels_play_count', 'fb_reels_total_plays'];

    /**
     * A visualização — exige `read_insights`.
     *
     * ⚠️ Devolve `null` quando a permissão não foi concedida, e **isso não é
     * erro**: quem recusou continua vendo curtida e comentário.
     */
    private function lerVisualizacoes(string $id, string $token): ?int
    {
        try {
            $resposta = Http::timeout(20)->get(self::API.'/'.$id.'/video_insights', [
                'metric' => implode(',', self::METRICAS_DO_REEL),
                'access_token' => $token,
            ]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $resposta->successful()) {
            return null;
        }

        $lidas = [];

        foreach ((array) $resposta->json('data', []) as $item) {
            $nome = $item['name'] ?? null;
            $valor = $item['values'][0]['value'] ?? null;

            if (is_string($nome) && is_numeric($valor)) {
                $lidas[$nome] = (int) $valor;
            }
        }

        // ⚠️ Na ordem da constante, não na ordem em que a Meta respondeu: a
        // preferência é uma decisão nossa sobre o que significa "visualização".
        foreach (self::METRICAS_DO_REEL as $metrica) {
            if (isset($lidas[$metrica])) {
                return $lidas[$metrica];
            }
        }

        return null;
    }
}

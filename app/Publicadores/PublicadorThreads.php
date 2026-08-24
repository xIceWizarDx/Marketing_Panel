<?php

namespace App\Publicadores;

use App\Enums\Plataforma;
use App\Http\Controllers\MidiaTemporariaController;
use App\Models\Destino;
use App\Support\FalhaDeConexao;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Threads — contêiner e publicação, em dois passos.
 *
 * ```
 * contêiner → POST /<threads-user-id>/threads          { media_type, video_url, text }
 * publicar  → POST /<threads-user-id>/threads_publish  { creation_id }
 * ```
 *
 * ⛔ **Criar o contêiner NÃO publica.** É a mesma armadilha do Instagram: o
 * contêiner é criado, tudo responde sucesso — e o post não existe. Só o
 * `threads_publish` publica.
 *
 * ⭐ **E aqui a rede VEM BUSCAR o arquivo.** Não existe envio: `video_url` recebe
 * um endereço que a Meta acessa sozinha. É por isso que a URL temporária existe
 * (DEC-100) e por isso que o Threads não funciona sem servidor alcançável pela
 * internet (DEC-101).
 *
 * ⚠️ **O segundo passo não acontece agora.** A documentação pede ~30 segundos
 * entre criar e publicar, e dormir esse tempo seguraria um worker: uma fila de
 * dez publicações viraria cinco minutos de nada acontecendo. O destino vai para
 * `processando` e o passo dois acontece na conciliação (DEC-103).
 */
class PublicadorThreads implements Publicador
{
    private const API = 'https://graph.threads.net/v1.0';

    /**
     * Erros que a rede devolve no contêiner — **todos permanentes**.
     *
     * ⚠️ A documentação não lista **nenhum** erro passageiro: são recusas de
     * conteúdo e falhas de codificação. Devolver para a fila com o mesmo arquivo
     * daria o mesmo resultado três vezes, e a pessoa esperaria por nada.
     *
     * ⛔ **As chaves são PEDAÇOS, não os códigos inteiros — e isso é de
     * propósito.** A documentação oficial escreve `INVALID_ASPEC_RATIO`, sem o
     * `T`, e em outra leitura da mesma página escreve `FAILED_FRAME_RATE` no
     * lugar de `INVALID_FRAME_RATE`. Casar a palavra inteira faria a recusa mais
     * comum de todas — a proporção do vídeo — cair no genérico *"o Threads
     * recusou este post"*, que não diz o que arrumar. Casar pelo pedaço estável
     * sobrevive ao dia em que a Meta consertar o erro de digitação.
     */
    private const ERROS = [
        'ASPEC' => 'O Threads recusou a proporção do vídeo.',
        'BIT_RATE' => 'A taxa de bits do vídeo está fora do que o Threads aceita.',
        'DURATION' => 'A duração do vídeo está fora do que o Threads aceita.',
        'FRAME_RATE' => 'A taxa de quadros do vídeo está fora do que o Threads aceita (23 a 60 por segundo).',
        'AUDIO_CHANNEL_LAYOUT' => 'A disposição dos canais de áudio não é aceita pelo Threads.',
        'AUDIO_CHANNELS' => 'O áudio precisa ter um ou dois canais.',
        'PROCESSING_VIDEO' => 'O Threads não conseguiu processar este vídeo.',
        'PROCESSING_AUDIO' => 'O Threads não conseguiu processar o áudio deste vídeo.',
    ];

    public function plataforma(): Plataforma
    {
        return Plataforma::Threads;
    }

    public function publicar(Destino $destino, Retomada $retomada): ResultadoPublicacao
    {
        $midia = $destino->publicacao->midia;
        $conta = $destino->contaSocial;
        $token = $conta->credencial?->access_token;

        if (! $token) {
            return ResultadoPublicacao::recusado(
                'A conexão com o Threads não está mais válida. Reconecte a conta para publicar.'
            );
        }

        /*
         * ⛔ Sem endereço público não há o que tentar: a Meta vem BUSCAR o
         * arquivo, e de `localhost` ela nunca vai buscar nada. Recusar aqui, com
         * a frase certa, é melhor que enviar e receber `FAILED_DOWNLOADING_VIDEO`
         * quinze minutos depois — que é o mesmo erro de vídeo corrompido, e
         * mandaria a pessoa procurar defeito no arquivo dela.
         */
        if (! $this->alcancavelPelaInternet()) {
            return ResultadoPublicacao::recusado(
                'O Threads busca o vídeo no nosso endereço, e este servidor ainda não tem um endereço público.'
            );
        }

        /*
         * ⛔ **`textoFinal()`, não a legenda crua.** Ele é quem junta legenda e
         * hashtags — e respeita o texto próprio daquele destino.
         *
         * ⚠️ Montar isso à mão aqui, como estava, jogava as **hashtags fora**:
         * a pessoa escrevia, a tela contava, e nada chegava na rede. Bluesky,
         * Facebook e Instagram sempre usaram este helper; só aqui e no TikTok
         * ele tinha sido reescrito.
         *
         * ⚠️ O título vai colado porque esta rede **não tem campo de título** —
         * e é por isso que ele divide o mesmo orçamento de texto.
         */
        $legenda = trim(($destino->titulo() ?? '').' '.$destino->textoFinal());

        try {
            $resposta = Http::asForm()->timeout(30)->post(self::API."/{$conta->identificador_externo}/threads", [
                'media_type' => $midia->ehVideo() ? 'VIDEO' : 'IMAGE',
                // ⭐ O endereço nasce AGORA e vive minutos: ele é do envio, não
                // do arquivo (DEC-100).
                $midia->ehVideo() ? 'video_url' : 'image_url' => MidiaTemporariaController::enderecoDe($midia),
                'text' => $legenda,
                'access_token' => $token,
            ]);
        } catch (ConnectionException $erro) {
            return ResultadoPublicacao::tentarDepois(FalhaDeConexao::explicar($erro, 'Threads'));
        }

        if (! $resposta->successful() || ! $resposta->json('id')) {
            return $this->interpretar($resposta, $conta->identificador_externo, $token);
        }

        /*
         * ⭐ O id do contêiner é o `handle_externo` — é ele que impede publicar
         * duas vezes. Guardado antes de qualquer outra coisa, um retry encontra
         * o contêiner que já existe em vez de criar outro.
         */
        return ResultadoPublicacao::aceito((string) $resposta->json('id'));
    }

    /**
     * ⭐ A prova (DEC-31) — **e o segundo passo da publicação**.
     *
     * ⚠️ Este método faz duas coisas de propósito: o Threads publica em dois
     * tempos, e o segundo só pode acontecer depois de a rede terminar de
     * processar. Fazê-lo aqui é o que permite esperar sem segurar worker
     * (DEC-103) — a conciliação já roda em ciclos.
     */
    public function conciliar(Destino $destino): ResultadoConciliacao
    {
        $conta = $destino->contaSocial;
        $token = $conta->credencial?->access_token;

        if (! $token || ! $destino->handle_externo) {
            return ResultadoConciliacao::recusado('Não há como conferir este post no Threads.');
        }

        // Já publicado: só relemos para confirmar que continua no ar.
        if ($destino->identificador_externo) {
            return $this->conferirPublicado($destino, $token);
        }

        try {
            $resposta = Http::timeout(20)->get(self::API.'/'.$destino->handle_externo, [
                'fields' => 'status,error_message',
                'access_token' => $token,
            ]);
        } catch (ConnectionException) {
            return ResultadoConciliacao::aindaProcessando();
        }

        if (! $resposta->successful()) {
            return ResultadoConciliacao::aindaProcessando();
        }

        return match ((string) $resposta->json('status')) {
            'FINISHED' => $this->publicarContainer($destino, $token),
            // ⚠️ `EXPIRED` não é erro de vídeo: o contêiner morre em 24 h sem
            // ser publicado. Dizer "falhou" mandaria a pessoa procurar defeito
            // no arquivo.
            'EXPIRED' => ResultadoConciliacao::recusado(
                'O envio para o Threads passou de 24 horas sem ser publicado e expirou. Envie de novo.'
            ),
            'ERROR' => ResultadoConciliacao::recusado($this->motivo((string) $resposta->json('error_message'))),
            'PUBLISHED' => ResultadoConciliacao::noAr($this->montarUrl($destino->handle_externo)),
            default => ResultadoConciliacao::aindaProcessando(),
        };
    }

    /** O segundo passo: o que de fato publica. */
    private function publicarContainer(Destino $destino, string $token): ResultadoConciliacao
    {
        try {
            $resposta = Http::asForm()->timeout(30)
                ->post(self::API."/{$destino->contaSocial->identificador_externo}/threads_publish", [
                    'creation_id' => $destino->handle_externo,
                    'access_token' => $token,
                ]);
        } catch (ConnectionException) {
            return ResultadoConciliacao::aindaProcessando();
        }

        if (! $resposta->successful() || ! $resposta->json('id')) {
            return ResultadoConciliacao::aindaProcessando();
        }

        $id = (string) $resposta->json('id');

        // ⚠️ O id do POST publicado é OUTRO, diferente do contêiner: é ele que
        // vira `identificador_externo` e o endereço da prova.
        $destino->forceFill(['identificador_externo' => $id])->save();

        return ResultadoConciliacao::noAr($this->montarUrl($id));
    }

    /** Já publicado — confere se continua no ar (moderação apaga depois). */
    private function conferirPublicado(Destino $destino, string $token): ResultadoConciliacao
    {
        try {
            $resposta = Http::timeout(20)->get(self::API.'/'.$destino->identificador_externo, [
                'fields' => 'id,permalink',
                'access_token' => $token,
            ]);
        } catch (ConnectionException) {
            return ResultadoConciliacao::aindaProcessando();
        }

        if ($resposta->successful() && $resposta->json('id')) {
            return ResultadoConciliacao::noAr(
                (string) ($resposta->json('permalink') ?: $this->montarUrl((string) $destino->identificador_externo))
            );
        }

        // Sumiu depois de publicado: a moderação removeu.
        return $resposta->status() === 404
            ? ResultadoConciliacao::recusado('O post não está mais no Threads.')
            : ResultadoConciliacao::aindaProcessando();
    }

    /** A frase em português para o código que a rede devolveu. */
    private function motivo(string $codigo): string
    {
        /*
         * ⛔ **`FAILED_DOWNLOADING_VIDEO` é o erro DESTA arquitetura, não do
         * arquivo.** Ele aparece quando a Meta não conseguiu buscar a mídia no
         * endereço que demos — URL vencida, servidor inalcançável ou arquivo já
         * liberado. Traduzi-lo como "vídeo com problema" mandaria a pessoa
         * reexportar um arquivo que está perfeito.
         */
        if ($codigo === 'FAILED_DOWNLOADING_VIDEO') {
            return 'O Threads não conseguiu buscar o vídeo no nosso servidor a tempo. Tente enviar de novo.';
        }

        foreach (self::ERROS as $chave => $frase) {
            if (str_contains($codigo, $chave)) {
                return $frase;
            }
        }

        return 'O Threads recusou este post.';
    }

    /** Erro na criação do contêiner. */
    private function interpretar(Response $resposta, string $conta, string $token): ResultadoPublicacao
    {
        $mensagem = (string) ($resposta->json('error.error_user_msg')
            ?: $resposta->json('error.message')
            ?: 'O Threads recusou o envio.');

        /*
         * ⭐ **Cota estourada é ESPERA, não falha (DEC-24).** A rede não devolve
         * código próprio para isso — quem sabe é o endpoint de cota, e por isso
         * ele só é consultado AQUI, depois de a recusa acontecer: uma chamada a
         * mais no caminho do erro, nenhuma no caminho normal.
         *
         * ⚠️ Sem isto a publicação de número 251 do dia seria marcada como
         * falha permanente e queimaria as três tentativas contra um limite que
         * só volta amanhã.
         */
        if ($this->cotaEstourada($conta, $token)) {
            return ResultadoPublicacao::semCota(
                'A conta atingiu o limite de publicações do dia no Threads (250 a cada 24 horas).'
            );
        }

        /*
         * ⚠️ `is_transient` é a própria rede dizendo se vale tentar de novo — e
         * é melhor que adivinhar pelo código HTTP, que foi o que tivemos que
         * fazer no YouTube.
         */
        return $resposta->json('error.is_transient') === true || $resposta->serverError()
            ? ResultadoPublicacao::tentarDepois($mensagem)
            : ResultadoPublicacao::recusado($mensagem);
    }

    /**
     * A conta já gastou as 250 publicações da janela de 24 h?
     *
     * ⛔ **Na dúvida devolve `false`.** Se a consulta da cota falhar, o erro que
     * a pessoa vê tem que continuar sendo o que a rede deu — inventar "limite do
     * dia" a partir de uma chamada que nem respondeu esconderia o motivo real.
     */
    private function cotaEstourada(string $conta, string $token): bool
    {
        try {
            $resposta = Http::timeout(10)->get(self::API."/{$conta}/threads_publishing_limit", [
                'fields' => 'quota_usage,config',
                'access_token' => $token,
            ]);
        } catch (ConnectionException) {
            return false;
        }

        if (! $resposta->successful()) {
            return false;
        }

        // ⚠️ Aceita as duas formas: a Graph API embrulha em `data[0]`, e a
        // documentação do Threads mostra os campos soltos. Ler só uma delas
        // daria "cota nunca estourada" para sempre, sem erro nenhum aparecer.
        $cota = $resposta->json('data.0') ?: ($resposta->json() ?? []);
        $usado = $cota['quota_usage'] ?? null;
        $teto = $cota['config']['quota_total'] ?? null;

        return is_numeric($usado) && is_numeric($teto) && (int) $usado >= (int) $teto;
    }

    private function montarUrl(string $id): string
    {
        return "https://www.threads.net/t/{$id}";
    }

    /** Mesma régua do início da conexão: a rede precisa nos alcançar. */
    private function alcancavelPelaInternet(): bool
    {
        $maquina = parse_url((string) config('app.url'), PHP_URL_HOST) ?: '';

        return $maquina !== ''
            && ! in_array($maquina, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)
            && ! preg_match('/\.(test|local|localhost)$/i', $maquina);
    }
}

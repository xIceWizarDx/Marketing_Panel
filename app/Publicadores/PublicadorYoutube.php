<?php

namespace App\Publicadores;

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Models\Destino;
use App\Services\TokenDoGoogle;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * YouTube — a rede mais exigente, e por isso a segunda a ser feita.
 *
 * Ela estressa tudo o que o motor tem: envio em pedaços com retomada,
 * processamento assíncrono, cota diária e dois portões de aprovação separados.
 * Se o contrato do `Publicador` aguenta o YouTube, aguenta as outras.
 *
 * **Fluxo do envio retomável:**
 *   1. abre a sessão → o YouTube devolve um endereço no cabeçalho `Location`
 *   2. **guarda esse endereço** (é o handle anti-duplicidade)
 *   3. manda o arquivo em pedaços para o endereço
 *   4. o último pedaço devolve o vídeo criado
 *
 * ⚠️ **Antes da auditoria de compliance, TODO vídeo enviado fica travado como
 * privado** — não importa o que a gente peça. São dois processos separados: a
 * verificação do OAuth e a auditoria do projeto. A tela precisa dizer isso, em
 * vez de mostrar um link que só o dono abre.
 */
class PublicadorYoutube implements Publicador
{
    private const UPLOAD = 'https://www.googleapis.com/upload/youtube/v3/videos';

    private const API = 'https://www.googleapis.com/youtube/v3/videos';

    /** 8 MB por pedaço: múltiplo de 256 KB, como o Google exige. */
    private const PEDACO = 8 * 1024 * 1024;

    /** Sentinela: erro passageiro na conferência, a sessão continua valendo. */
    private const TENTAR_DEPOIS = -1;

    public function __construct(private readonly TokenDoGoogle $tokens) {}

    public function plataforma(): Plataforma
    {
        return Plataforma::Youtube;
    }

    public function publicar(Destino $destino, Retomada $retomada): ResultadoPublicacao
    {
        $midia = $destino->publicacao->midia;

        if (! $midia->ehVideo()) {
            return ResultadoPublicacao::recusado('O YouTube não recebe imagem por aqui — só vídeo.');
        }

        $token = $this->tokens->valido($destino->contaSocial);

        if (! $token) {
            return ResultadoPublicacao::recusado(
                'A conexão com o YouTube não está mais válida. Reconecte a conta para publicar.'
            );
        }

        $titulo = trim((string) $destino->titulo());

        if ($titulo === '') {
            // O YouTube exige título. Barrar aqui evita gastar o upload inteiro
            // para receber 400 no fim.
            return ResultadoPublicacao::recusado(
                'O YouTube exige um título. Escreva um antes de publicar nessa conta.'
            );
        }

        try {
            $caminho = Storage::disk(config('midia.disco'))->path($midia->caminho);
            $tamanho = $midia->tamanho_bytes;

            // Retoma o envio anterior se houver — é o que impede o mesmo vídeo
            // de subir duas vezes quando a fila reentrega o job.
            $retomando = $retomada->comecouAntes();

            $sessao = $retomando
                ? $retomada->handle()
                : $this->abrirSessao($token, $destino, $titulo, $tamanho, $retomada);

            // A abertura devolve o motivo quando falha — cota estourada não pode
            // virar retentativa genérica: queimaria as 3 tentativas contra uma
            // cota que só volta no dia seguinte.
            if ($sessao instanceof ResultadoPublicacao) {
                return $sessao;
            }

            return $this->enviarArquivo($token, $sessao, $caminho, $tamanho, $retomada, $retomando);
        } catch (ConnectionException) {
            return ResultadoPublicacao::tentarDepois('O YouTube não respondeu a tempo.');
        }
    }

    /**
     * ⭐ A prova (DEC-31): pergunta ao YouTube se o vídeo terminou de processar.
     *
     * `uploadStatus` é o que importa — o vídeo pode ter subido e ser **rejeitado
     * depois**, por direito autoral ou política. É exatamente o caso que os
     * concorrentes marcam como "publicado".
     */
    public function conciliar(Destino $destino): ResultadoConciliacao
    {
        if (! $destino->identificador_externo) {
            return ResultadoConciliacao::recusado('Não há como conferir este vídeo no YouTube.');
        }

        $token = $this->tokens->valido($destino->contaSocial);

        if (! $token) {
            return ResultadoConciliacao::aindaProcessando();
        }

        try {
            $resposta = Http::withToken($token)->get(self::API, [
                // `contentDetails` traz `definition` (hd|sd) — ⭐ a prova de
                // degradacao pela propria rede — e `hasCustomThumbnail`.
                'part' => 'status,processingDetails,contentDetails',
                'id' => $destino->identificador_externo,
            ]);
        } catch (ConnectionException) {
            return ResultadoConciliacao::aindaProcessando();
        }

        if (! $resposta->successful()) {
            return ResultadoConciliacao::aindaProcessando();
        }

        $itens = $resposta->json('items', []);

        if ($itens === []) {
            // Sumiu da conta: foi removido depois de aceito.
            return ResultadoConciliacao::recusado('O vídeo não está mais no canal.');
        }

        $status = $itens[0]['status'] ?? [];

        // ⚠️ Conta encerrada ou suspensa NAO e sobre o video: e sobre a CONTA.
        // Insistir nos proximos envios seria inutil, e a pessoa precisa saber.
        if ($this->contaMorreu($status)) {
            $this->marcarContaComProblema($destino, $this->motivoDaRecusa($status));

            return ResultadoConciliacao::recusado($this->motivoDaRecusa($status));
        }

        return match ($status['uploadStatus'] ?? 'uploaded') {
            'processed' => ResultadoConciliacao::noAr(
                $this->montarUrl($destino->identificador_externo),
                // ⭐ `definition` é a rede dizendo se entregou em alta ou baixa.
                // Enviamos 1080×1920; se voltar `sd`, o YouTube está admitindo
                // que degradou — e nenhum concorrente mostra isso.
                $itens[0]['contentDetails']['definition'] ?? null,
            ),
            'rejected' => ResultadoConciliacao::recusado($this->motivoDaRecusa($status)),
            'failed' => ResultadoConciliacao::recusado($this->motivoDaFalha($status)),
            'deleted' => ResultadoConciliacao::recusado('O vídeo foi removido do canal.'),
            // `uploaded` segue esperando: o processamento pode demorar, e marcar
            // publicado agora seria mentir.
            default => ResultadoConciliacao::aindaProcessando(),
        };
    }

    /** @param array<string, mixed> $status */
    private function contaMorreu(array $status): bool
    {
        return in_array(
            $status['rejectionReason'] ?? '',
            ['uploaderAccountClosed', 'uploaderAccountSuspended'],
            true
        );
    }

    private function marcarContaComProblema(Destino $destino, string $motivo): void
    {
        $destino->contaSocial->forceFill([
            'status' => StatusConta::Erro,
            'status_detalhe' => $motivo,
        ])->save();
    }

    /**
     * Abre a sessão e guarda o endereço ANTES de qualquer byte subir.
     *
     * @return string|ResultadoPublicacao o endereço, ou o motivo da recusa
     */
    private function abrirSessao(
        string $token,
        Destino $destino,
        string $titulo,
        int $tamanho,
        Retomada $retomada,
    ): string|ResultadoPublicacao {
        $opcoes = $destino->opcoes ?? [];
        $agendado = $opcoes['publicar_em'] ?? null;

        // ⚠️ A spec: `publishAt` "só pode ser definido se a privacidade for
        // **private**". Agendar E pedir público é combinação que a API recusa —
        // e agendar SIGNIFICA privado até a hora marcada, então não há conflito
        // real: só a ordem certa dos campos.
        $visibilidade = $agendado !== null ? 'private' : ($opcoes['visibilidade'] ?? 'private');

        $resposta = Http::withToken($token)
            ->withHeaders([
                'X-Upload-Content-Length' => (string) $tamanho,
                'X-Upload-Content-Type' => $destino->publicacao->midia->mime_type,
                // A documentacao pede o charset explicito na abertura da sessao.
                'Content-Type' => 'application/json; charset=UTF-8',
            ])
            ->withQueryParameters([
                'uploadType' => 'resumable',
                'part' => 'snippet,status',
                // ⭐ O YouTube pode corrigir brilho e estabilizar o video. Isso
                // contraria a promessa central (DEC-33) e a spec NAO declara o
                // padrao — entao mandamos `false` explicito, sempre.
                'autoLevels' => 'false',
                'stabilize' => 'false',
                // Vem LIGADO por padrao: publicar varios cortes seguidos
                // notificaria os inscritos a cada um.
                'notifySubscribers' => ($opcoes['notificar_inscritos'] ?? false) ? 'true' : 'false',
            ])
            ->post(self::UPLOAD, [
                'snippet' => [
                    // ⛔ SEM CORTAR. A politica proibe modificar o que a pessoa
                    // escreveu sem consentimento; quem recusa antes e o
                    // `EnvioDePublicacao`, com mensagem explicando quanto sobra.
                    'title' => $titulo,
                    'description' => (string) $destino->textoFinal(),
                    'tags' => $destino->hashtags(),
                    'categoryId' => $opcoes['categoria'] ?? '22',
                ],
                'status' => array_filter([
                    // ⚠️ Antes da auditoria de compliance, o YouTube ignora isto
                    // e trava como privado de qualquer jeito.
                    'privacyStatus' => $visibilidade,
                    // Declaracao legal (COPPA) — escolha da pessoa, nunca padrao
                    // nosso escondido no codigo.
                    'selfDeclaredMadeForKids' => (bool) ($opcoes['para_criancas'] ?? false),
                    // Obrigatorio declarar conteudo gerado ou alterado por IA.
                    'containsSyntheticMedia' => (bool) ($opcoes['feito_com_ia'] ?? false),
                    // O YouTube publica sozinho na hora marcada.
                    'publishAt' => $agendado,
                ], fn ($valor) => $valor !== null),
            ]);

        if (! $resposta->successful()) {
            return $this->interpretarErro($resposta);
        }

        $sessao = $resposta->header('Location');

        if ($sessao === '') {
            return ResultadoPublicacao::tentarDepois('O YouTube não devolveu o endereço de envio.');
        }

        // ⚠️ Guarda ANTES de enviar. Se o processo morrer no meio do upload, a
        // próxima tentativa retoma daqui em vez de recomeçar — e publicação não
        // tem desfazer.
        $retomada->guardar($sessao);

        return $sessao;
    }

    /** Manda o arquivo em pedaços, retomando de onde parou. */
    private function enviarArquivo(
        string $token,
        string $sessao,
        string $caminho,
        int $tamanho,
        Retomada $retomada,
        bool $retomando,
    ): ResultadoPublicacao {
        // Sessão recém-aberta tem zero bytes: perguntar seria uma ida à rede
        // desperdiçada em todo envio.
        $inicio = $retomando ? $this->quantoJaSubiu($token, $sessao, $tamanho) : 0;

        if ($inicio === null) {
            // 404: a sessão venceu. Limpar o handle faz a próxima tentativa
            // abrir uma sessão nova.
            $retomada->limpar();

            return ResultadoPublicacao::tentarDepois('A sessão de envio do YouTube venceu. Vamos recomeçar.');
        }

        if ($inicio === self::TENTAR_DEPOIS) {
            // Erro passageiro: o endereço da sessão CONTINUA valendo. Recomeçar
            // aqui jogaria fora tudo que já subiu.
            return ResultadoPublicacao::tentarDepois('O YouTube não respondeu à conferência do envio.');
        }

        $arquivo = fopen($caminho, 'rb');

        if ($arquivo === false) {
            return ResultadoPublicacao::recusado('Não foi possível ler o arquivo para enviar.');
        }

        try {
            fseek($arquivo, $inicio);

            while ($inicio < $tamanho) {
                $pedaco = fread($arquivo, self::PEDACO);
                $fim = $inicio + strlen($pedaco) - 1;

                $resposta = Http::withHeaders([
                    'Content-Range' => "bytes {$inicio}-{$fim}/{$tamanho}",
                ])->withBody($pedaco, 'application/octet-stream')->put($sessao);

                // 308 = recebeu o pedaço e quer o próximo.
                if ($resposta->status() === 308) {
                    $inicio = $fim + 1;

                    continue;
                }

                if ($resposta->successful()) {
                    $retomada->limpar();

                    return ResultadoPublicacao::aceito(
                        $resposta->json('id'),
                        $this->montarUrl($resposta->json('id'))
                    );
                }

                return $this->interpretarErro($resposta);
            }

            return ResultadoPublicacao::tentarDepois('O envio terminou sem o YouTube confirmar o vídeo.');
        } finally {
            fclose($arquivo);
        }
    }

    /**
     * Pergunta ao YouTube quantos bytes ele já tem.
     *
     * Retorna null quando a sessão não vale mais.
     */
    private function quantoJaSubiu(string $token, string $sessao, int $tamanho): ?int
    {
        $resposta = Http::withToken($token)
            ->withHeaders(['Content-Range' => "bytes */{$tamanho}"])
            ->put($sessao);

        if ($resposta->successful()) {
            // Já estava completo — nada a reenviar.
            return $tamanho;
        }

        // ⚠️ SÓ 404 significa sessão expirada. Em 5xx o endereço continua
        // valendo, e recomeçar do zero seria jogar fora o que já subiu.
        if ($resposta->status() === 404) {
            return null;
        }

        if ($resposta->status() !== 308) {
            // Outro erro: preserva a sessão e tenta de novo depois.
            return self::TENTAR_DEPOIS;
        }

        $recebido = $resposta->header('Range');

        // Sem `Range` o YouTube ainda não tem byte nenhum.
        return $recebido === '' ? 0 : ((int) str($recebido)->afterLast('-')->toString()) + 1;
    }

    /**
     * Os erros do próprio ENVIO, traduzidos.
     *
     * ⚠️ Diferentes dos motivos de recusa: aqueles chegam depois, quando o
     * YouTube já moderou o vídeo. Estes acontecem na hora, e a referência
     * oficial do `videos.insert` lista **14** — nenhum deles estava tratado, o
     * que dava mensagem genérica justamente onde há o que fazer.
     *
     * (Ficam de fora `invalidRecordingDetails`, `invalidVideoGameRating`,
     * `invalidFilename` e `defaultLanguageNotSet`: são de campos que nunca
     * enviamos. Se aparecerem, o erro cru do YouTube é mais honesto que uma
     * tradução nossa inventada.)
     */
    private const ERROS_DO_ENVIO = [
        'invalidTitle' => 'O YouTube recusou o título. Ele não pode ficar vazio nem usar os sinais < e >.',
        'invalidDescription' => 'O YouTube recusou a legenda. Ela não pode usar os sinais < e >.',
        'invalidTags' => 'O YouTube recusou as hashtags. Alguma tem caractere que ele não aceita.',
        'invalidCategoryId' => 'A categoria escolhida não existe mais no YouTube.',
        'invalidPublishAt' => 'A data de agendamento não serve: ela precisa estar no futuro.',
        'forbiddenPrivacySetting' => 'O YouTube não permitiu essa visibilidade para este canal.',
        'forbiddenLicenseSetting' => 'O YouTube não permitiu essa licença para este canal.',
        'invalidVideoMetadata' => 'O YouTube recusou os dados do vídeo. Revise título, legenda e hashtags.',
        'mediaBodyRequired' => 'O envio chegou sem o arquivo de vídeo. Vamos tentar de novo.',
    ];

    private function interpretarErro(Response $resposta): ResultadoPublicacao
    {
        $codigo = (string) $resposta->status();
        $motivo = $resposta->json('error.errors.0.reason', '');

        return match (true) {
            // ⚠️ Cota diária estourada não é erro: é espera. São 100 uploads por
            // dia no PROJETO inteiro, somando todos os clientes (DEC-24).
            in_array($motivo, ['quotaExceeded', 'uploadLimitExceeded'], true) => ResultadoPublicacao::semCota(
                'A cota diária de envios do YouTube acabou. Publicamos assim que ela virar.'
            ),
            $resposta->status() === 401 => ResultadoPublicacao::recusado(
                'A conexão com o YouTube expirou. Reconecte a conta.', $codigo
            ),
            $resposta->status() === 403 => ResultadoPublicacao::recusado(
                'O YouTube recusou: esta conta não tem permissão para enviar vídeos.', $codigo
            ),
            $resposta->status() === 429 || $resposta->serverError() => ResultadoPublicacao::tentarDepois(
                'O YouTube pediu para tentar de novo mais tarde.', $codigo
            ),
            // O arquivo não chegou: é falha de transporte, não do conteúdo —
            // recusar de vez descartaria um envio que a próxima tentativa faz.
            $motivo === 'mediaBodyRequired' => ResultadoPublicacao::tentarDepois(
                self::ERROS_DO_ENVIO['mediaBodyRequired'], $motivo
            ),
            // ⭐ O motivo é mais preciso que o código HTTP: `invalidTitle` diz o
            // que arrumar, "400" não diz nada. O código guardado passa a ser o
            // motivo, não o status — é o que serve para investigar depois.
            isset(self::ERROS_DO_ENVIO[$motivo]) => ResultadoPublicacao::recusado(
                self::ERROS_DO_ENVIO[$motivo], $motivo
            ),
            default => ResultadoPublicacao::recusado(
                $resposta->json('error.message') ?? 'O YouTube recusou o envio.', $codigo
            ),
        };
    }

    /**
     * Os 10 motivos de recusa documentados, em português.
     *
     * @param  array<string, mixed>  $status
     */
    private function motivoDaRecusa(array $status): string
    {
        return match ($status['rejectionReason'] ?? '') {
            'copyright' => 'O YouTube bloqueou o vídeo por direito autoral.',
            'duplicate' => 'Este vídeo já existe no canal.',
            'inappropriate' => 'O YouTube considerou o conteúdo impróprio.',
            'length' => 'O vídeo passa da duração permitida para esta conta.',
            'claim' => 'O vídeo recebeu uma reivindicação de direitos de terceiro.',
            'trademark' => 'O YouTube bloqueou o vídeo por marca registrada.',
            'termsOfUse' => 'O YouTube entendeu que o vídeo fere os termos de uso.',
            'legal' => 'O YouTube bloqueou o vídeo por questão jurídica.',
            // ⚠️ Estes dois sao sobre a CONTA, nao sobre o video.
            'uploaderAccountClosed' => 'Este canal do YouTube não existe mais. Conecte outra conta.',
            'uploaderAccountSuspended' => 'Este canal do YouTube está suspenso. Resolva com o YouTube antes de publicar.',
            default => 'O YouTube recusou o vídeo depois de recebê-lo.',
        };
    }

    /**
     * Os 6 motivos de falha de processamento, em português.
     *
     * @param  array<string, mixed>  $status
     */
    private function motivoDaFalha(array $status): string
    {
        return match ($status['failureReason'] ?? '') {
            'codec' => 'O YouTube não reconheceu o codec do vídeo ou do áudio.',
            'conversion' => 'O YouTube não conseguiu converter este arquivo.',
            'emptyFile' => 'O arquivo chegou vazio ao YouTube.',
            'invalidFile' => 'O arquivo parece corrompido.',
            'tooSmall' => 'O arquivo é menor que o mínimo aceito pelo YouTube.',
            'uploadAborted' => 'O envio foi interrompido antes de terminar.',
            default => 'O YouTube não conseguiu processar este vídeo.',
        };
    }

    private function montarUrl(string $id): string
    {
        return "https://www.youtube.com/watch?v={$id}";
    }
}

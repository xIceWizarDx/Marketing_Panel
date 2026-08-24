<?php

namespace App\Services\Meta;

use Illuminate\Http\Client\Response;

/**
 * Traduz o erro da Meta — e diz se vale tentar de novo.
 *
 * ⭐ Diferente do YouTube, aqui a rede **informa** se o erro é passageiro, no
 * campo `is_transient`. Lá tivemos que deduzir isso do código HTTP e erramos
 * duas vezes (5xx tratado como sessão vencida, 500 matando a conta). Aqui a
 * própria plataforma responde a pergunta, e ignorar isso seria adivinhar tendo
 * a resposta na mão.
 *
 * O que identifica o erro de verdade é o **subcódigo**, não o código: `100` vale
 * para uma dúzia de causas diferentes, e `2207026` para exatamente uma.
 */
readonly class ErroDaMeta
{
    private function __construct(
        public string $mensagem,
        public bool $passageiro,
        /** Limite diário estourado — é espera, não falha (DEC-24). */
        public bool $limiteDiario,
        /**
         * ⭐ **A conexão morreu — só reconectar resolve** (DEC-159).
         *
         * ⛔ Isto não é "tentar de novo depois": nenhuma tentativa conserta um
         * token revogado. É o que acende o semáforo vermelho (DEC-32) e põe a
         * conta em "Precisa de você" — sem isso, ela fica **verde e conectada**
         * na tela enquanto recusa toda publicação.
         */
        public bool $credencialMorreu,
        /** Identificador que o suporte da Meta pede. */
        public ?string $rastreio,
        public ?int $codigo,
        public ?int $subcodigo,
    ) {}

    /**
     * Subcódigos traduzidos.
     *
     * Só os que a pessoa pode entender ou resolver. Para o resto, a Meta manda
     * um `error_user_msg` escrito para o usuário final — melhor que um código
     * seco, mesmo em inglês.
     */
    private const TRADUZIDOS = [
        2207003 => 'A rede demorou demais para buscar o arquivo. Vamos tentar de novo.',
        2207020 => 'O envio expirou antes de ser publicado. Vamos recomeçar.',
        2207026 => 'A rede não aceita este formato de vídeo. Use MP4 ou MOV.',
        2207009 => 'A proporção do vídeo não é aceita. Envie em 9:16.',
        2207050 => 'A conta está com restrição. Entre no aplicativo da rede e confira os avisos.',
        2207051 => 'A rede limitou a atividade desta conta por suspeita de spam.',
        2207052 => 'A rede não conseguiu baixar o arquivo.',
        2207053 => 'O envio falhou por um erro da rede. Vamos tentar de novo.',
        2207032 => 'A rede não conseguiu preparar o envio. Vamos tentar de novo.',
        2207001 => 'A rede teve um erro interno. Vamos tentar de novo.',
        2207006 => 'A rede não encontrou o envio. Vamos recomeçar.',
        2207023 => 'Tipo de mídia não reconhecido pela rede.',
        2207004 => 'A imagem passa de 8 MB, que é o teto da rede.',
        2207005 => 'A rede só aceita imagem em JPEG.',
    ];

    /**
     * ⚠️ Estes NÃO são falhas.
     *
     * `2207027` é "ainda processando" e `2207008` é "o envio ainda não apareceu
     * do outro lado" — os dois se resolvem esperando. Tratar como erro derrubaria
     * publicações que iam dar certo; foi exatamente o engano que quase entrou no
     * YouTube com o 5xx.
     */
    private const SO_ESPERAR = [2207027, 2207008];

    /** Limite diário de publicações da conta. */
    private const LIMITE_DIARIO = 2207042;

    /**
     * ⛔ **A autorização morreu — e nenhuma tentativa conserta** (DEC-159).
     *
     * `190` é o `OAuthException` da Meta: token revogado, senha trocada,
     * aplicativo removido pela pessoa, papel perdido na Página. Os subcódigos
     * separam o motivo, e todos terminam no mesmo lugar — **reconectar**.
     *
     * ⚠️ O token de Página **não expira por tempo**, e foi isso que fez este
     * caso passar despercebido: sem data de vencimento, parecia que não havia o
     * que vigiar. Ele não vence, mas morre.
     */
    private const CODIGO_DE_AUTORIZACAO = 190;

    /** @var list<int> */
    private const SUBCODIGOS_DE_MORTE = [
        458, // a pessoa removeu o aplicativo
        459, // conta em verificação (checkpoint)
        460, // senha trocada
        463, // o token expirou
        464, // sessão não confirmada
        467, // token inválido
    ];

    public static function de(Response $resposta): self
    {
        $erro = $resposta->json('error') ?? [];
        $subcodigo = isset($erro['error_subcode']) ? (int) $erro['error_subcode'] : null;
        $codigo = isset($erro['code']) ? (int) $erro['code'] : null;

        $morreu = $codigo === self::CODIGO_DE_AUTORIZACAO
            || ($subcodigo !== null && in_array($subcodigo, self::SUBCODIGOS_DE_MORTE, true));

        return new self(
            mensagem: $morreu
                ? 'A conexão com esta conta não vale mais. Reconecte para voltar a publicar.'
                : self::traduzir($erro, $subcodigo),
            /*
             * ⛔ Autorização morta NUNCA é passageira, mesmo que a Meta diga que
             * é: `is_transient` fala do servidor dela, não da nossa credencial.
             * Insistir aqui queima as três tentativas contra algo que só um
             * humano resolve.
             */
            passageiro: ! $morreu && self::ehPassageiro($erro, $subcodigo, $resposta->status()),
            limiteDiario: $subcodigo === self::LIMITE_DIARIO,
            credencialMorreu: $morreu,
            // Sem ele, um "não conseguimos rastrear" na hora de abrir chamado.
            rastreio: $erro['fbtrace_id'] ?? null,
            codigo: $codigo,
            subcodigo: $subcodigo,
        );
    }

    /** @param array<string, mixed> $erro */
    private static function traduzir(array $erro, ?int $subcodigo): string
    {
        if ($subcodigo === self::LIMITE_DIARIO) {
            return 'A conta atingiu o limite de publicações do dia nesta rede.';
        }

        if ($subcodigo !== null && isset(self::TRADUZIDOS[$subcodigo])) {
            return self::TRADUZIDOS[$subcodigo];
        }

        // A Meta escreve `error_user_msg` para o usuário final ler. Em inglês é
        // pior que a nossa tradução, mas é muito melhor que um número.
        return $erro['error_user_msg']
            ?? $erro['message']
            ?? 'A rede recusou a publicação e não explicou o motivo.';
    }

    /** @param array<string, mixed> $erro */
    private static function ehPassageiro(array $erro, ?int $subcodigo, int $status): bool
    {
        if ($subcodigo !== null && in_array($subcodigo, self::SO_ESPERAR, true)) {
            return true;
        }

        // ⭐ A rede respondendo diretamente. Quando ela diz, ela manda.
        if (isset($erro['is_transient'])) {
            return (bool) $erro['is_transient'];
        }

        // Sem resposta dela, o palpite conservador: erro do servidor passa,
        // erro do pedido não passa — repetir 20 vezes um pedido inválido só
        // queima a cota da conta.
        return $status >= 500 || $status === 429;
    }
}

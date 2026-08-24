<?php

namespace App\Support\Tiktok;

/**
 * O que o TikTok responde sobre a conta antes de publicar (DEC-117).
 *
 * ⛔ Perguntar é **obrigatório** pela documentação — *"your app must invoke the
 * API and use the latest creator information returned"* — e é enforçado:
 * privacidade fora de `privacy_level_options` devolve
 * `privacy_level_option_mismatch`.
 *
 * ⭐ **E aqui aparece algo que não existia em nenhuma outra rede: o limite de
 * duração é da CONTA, não da plataforma.** Contas novas têm teto menor. O
 * `EspecificacaoDaRede` guarda o máximo possível; o real só se sabe
 * perguntando.
 */
readonly class FichaDoCriador
{
    /**
     * @param  list<string>  $privacidadesPermitidas
     */
    private function __construct(
        public array $privacidadesPermitidas,
        public bool $comentarioDesligado,
        public bool $duetoDesligado,
        public bool $stitchDesligado,
        public ?int $duracaoMaximaSegundos,
        public ?string $apelido,
    ) {}

    /** @param array<string, mixed> $dados */
    public static function daResposta(array $dados): self
    {
        return new self(
            privacidadesPermitidas: array_values(array_filter(
                (array) ($dados['privacy_level_options'] ?? []),
                fn ($valor) => is_string($valor) && $valor !== ''
            )),
            comentarioDesligado: (bool) ($dados['comment_disabled'] ?? false),
            duetoDesligado: (bool) ($dados['duet_disabled'] ?? false),
            stitchDesligado: (bool) ($dados['stitch_disabled'] ?? false),
            duracaoMaximaSegundos: isset($dados['max_video_post_duration_sec'])
                ? (int) $dados['max_video_post_duration_sec']
                : null,
            apelido: $dados['creator_nickname'] ?? null,
        );
    }

    /**
     * A privacidade a mandar no envio.
     *
     * ⛔ **Enquanto o aplicativo não for auditado, é `SELF_ONLY` e ponto**
     * (DEC-116). Qualquer outra coisa devolve
     * `unaudited_client_can_only_post_to_private_accounts`, e oferecer escolha
     * seria oferecer um botão que sempre falha.
     *
     * ⚠️ Depois da auditoria, a escolha ainda respeita o que a conta permite:
     * a lista vem da rede, e mandar fora dela é recusa na hora.
     */
    public function privacidade(): string
    {
        if (! config('services.tiktok.auditado', false)) {
            return 'SELF_ONLY';
        }

        foreach (['PUBLIC_TO_EVERYONE', 'FOLLOWER_OF_CREATOR', 'MUTUAL_FOLLOW_FRIENDS', 'SELF_ONLY'] as $nivel) {
            if (in_array($nivel, $this->privacidadesPermitidas, true)) {
                return $nivel;
            }
        }

        // Lista vazia é resposta estranha da rede — e privado é o único palpite
        // que não expõe nada sem querer.
        return 'SELF_ONLY';
    }

    /**
     * A frase de recusa por duração, ou `null` se cabe.
     *
     * ⚠️ Conferido **antes** de subir um byte: descobrir isso depois do envio
     * inteiro gastaria a cota da pessoa para nada, e a cota daqui é curta.
     *
     * ⛔ Duração desconhecida **não** recusa: laudo faltando não é motivo para
     * bloquear uma publicação que provavelmente cabe. A rede é quem decide, e o
     * `duration_check_failed` já tem frase.
     */
    public function recusaPorDuracao(?int $segundos): ?string
    {
        if ($segundos === null || $this->duracaoMaximaSegundos === null || $this->duracaoMaximaSegundos <= 0) {
            return null;
        }

        if ($segundos <= $this->duracaoMaximaSegundos) {
            return null;
        }

        $minutos = intdiv($this->duracaoMaximaSegundos, 60);
        $limite = $minutos >= 1
            ? "{$minutos} minuto".($minutos > 1 ? 's' : '')
            : "{$this->duracaoMaximaSegundos} segundos";

        return "Esta conta do TikTok aceita vídeo de até {$limite}, e o seu tem ".
            self::emPalavras($segundos).'. Corte antes de publicar.';
    }

    private static function emPalavras(int $segundos): string
    {
        if ($segundos < 60) {
            return "{$segundos} segundos";
        }

        $minutos = intdiv($segundos, 60);
        $resto = $segundos % 60;

        return $resto === 0
            ? "{$minutos} minuto".($minutos > 1 ? 's' : '')
            : "{$minutos}min{$resto}s";
    }
}

<?php

namespace App\Support\Midia;

/**
 * Uma linha do laudo: o que foi conferido e o que dá pra fazer a respeito.
 *
 * Nunca só "erro". A pessoa precisa saber **o que** está fora e **o que vai
 * acontecer** — foi exatamente a falta disso que fez o suporte de um concorrente
 * mandar o cliente redimensionar todos os vídeos por engano.
 */
readonly class Achado
{
    private function __construct(
        public NivelDoAchado $nivel,
        public string $mensagem,
        /** O que o sistema fará. Null = nada a fazer. */
        public ?string $providencia = null,
    ) {}

    public static function ok(string $mensagem): self
    {
        return new self(NivelDoAchado::Ok, $mensagem);
    }

    /** Publica, mas com ressalva — ou publica depois de o sistema ajustar. */
    public static function atencao(string $mensagem, ?string $providencia = null): self
    {
        return new self(NivelDoAchado::Atencao, $mensagem, $providencia);
    }

    /** Não publica nesta rede. */
    public static function erro(string $mensagem, ?string $providencia = null): self
    {
        return new self(NivelDoAchado::Erro, $mensagem, $providencia);
    }

    public function paraArray(): array
    {
        return [
            'nivel' => $this->nivel->value,
            'mensagem' => $this->mensagem,
            'providencia' => $this->providencia,
        ];
    }
}

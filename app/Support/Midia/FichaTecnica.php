<?php

namespace App\Support\Midia;

/**
 * O que o `ffprobe` leu do arquivo — fatos, sem julgamento.
 *
 * Julgar é trabalho da `EspecificacaoDaRede`; aqui só se registra o que está no
 * arquivo. Separado de propósito: as regras das redes mudam sozinhas, e mudar
 * regra não pode obrigar a mexer em quem lê o arquivo.
 */
readonly class FichaTecnica
{
    public function __construct(
        public ?string $formato = null,
        public ?float $duracaoSegundos = null,
        public ?int $largura = null,
        public ?int $altura = null,
        public ?string $codecVideo = null,
        public ?string $codecAudio = null,
        public ?int $taxaAmostragemAudio = null,
        public ?int $canaisAudio = null,
        public ?float $fps = null,
        public ?int $bitrate = null,
        public ?int $tamanhoBytes = null,
    ) {}

    public function temAudio(): bool
    {
        return $this->codecAudio !== null;
    }

    public function proporcao(): ?float
    {
        if (! $this->largura || ! $this->altura) {
            return null;
        }

        return round($this->largura / $this->altura, 4);
    }

    /** 9:16 com folga de 1% — gravação de celular raramente bate exato. */
    public function ehVertical(): bool
    {
        $proporcao = $this->proporcao();

        return $proporcao !== null && abs($proporcao - (9 / 16)) < 0.01;
    }

    public function paraArray(): array
    {
        return [
            'formato' => $this->formato,
            'duracao_segundos' => $this->duracaoSegundos,
            'largura' => $this->largura,
            'altura' => $this->altura,
            'codec_video' => $this->codecVideo,
            'codec_audio' => $this->codecAudio,
            'taxa_amostragem_audio' => $this->taxaAmostragemAudio,
            'canais_audio' => $this->canaisAudio,
            'fps' => $this->fps,
            'bitrate' => $this->bitrate,
            'tamanho_bytes' => $this->tamanhoBytes,
        ];
    }
}

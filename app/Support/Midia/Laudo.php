<?php

namespace App\Support\Midia;

use App\Enums\Plataforma;

/**
 * O laudo completo: o que o arquivo é + o que cada rede fará com ele.
 *
 * É o diferencial nº 1 do produto (DEC-32/33): dizer **antes** de agendar, em
 * português, o que vai acontecer — em vez de o cliente descobrir depois que o
 * vídeo foi degradado, ou ouvir "é limitação da API".
 */
readonly class Laudo
{
    public function __construct(
        public FichaTecnica $ficha,
        /** @var array<string, list<Achado>> chave = valor da Plataforma */
        public array $porRede,
        /** Preenchido quando o ffprobe não está disponível no servidor. */
        public ?string $indisponivelPorque = null,
    ) {}

    /** Laudo que não pôde ser feito — a mídia continua utilizável. */
    public static function indisponivel(string $motivo): self
    {
        return new self(new FichaTecnica, [], $motivo);
    }

    public function disponivel(): bool
    {
        return $this->indisponivelPorque === null;
    }

    /** Redes em que o arquivo publica sem impedimento. */
    public function redesQueAceitam(): array
    {
        return array_keys(array_filter(
            $this->porRede,
            fn (array $achados) => ! $this->temErro($achados)
        ));
    }

    public function aceitoEm(Plataforma $plataforma): bool
    {
        return ! $this->temErro($this->porRede[$plataforma->value] ?? []);
    }

    /** @param list<Achado> $achados */
    private function temErro(array $achados): bool
    {
        foreach ($achados as $achado) {
            if ($achado->nivel === NivelDoAchado::Erro) {
                return true;
            }
        }

        return false;
    }

    public function paraArray(): array
    {
        return [
            'disponivel' => $this->disponivel(),
            'indisponivel_porque' => $this->indisponivelPorque,
            'ficha' => $this->ficha->paraArray(),
            'por_rede' => array_map(
                fn (array $achados) => array_map(fn (Achado $a) => $a->paraArray(), $achados),
                $this->porRede
            ),
        ];
    }
}

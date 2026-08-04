<?php

namespace App\Models;

use App\Enums\TipoMidia;
use App\Models\Concerns\PertenceAoUsuario;
use Database\Factories\MidiaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Midia extends Model
{
    /** @use HasFactory<MidiaFactory> */
    use HasFactory, HasUlids, PertenceAoUsuario;

    protected $table = 'midias';

    protected $fillable = [
        'tipo',
        'nome_original',
        'caminho',
        'miniatura',
        'assinatura',
        'arquivo_removido_em',
        'mime_type',
        'tamanho_bytes',
        'duracao_segundos',
        'largura',
        'altura',
        'laudo',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoMidia::class,
            'tamanho_bytes' => 'integer',
            'duracao_segundos' => 'integer',
            'largura' => 'integer',
            'altura' => 'integer',
            'laudo' => 'array',
            'arquivo_removido_em' => 'datetime',
        ];
    }

    /**
     * O arquivo original ainda está aqui?
     *
     * ⚠️ Pergunta que muda o que a tela oferece: sem arquivo não há prévia,
     * não há download e não há publicar de novo — só reenviar. O registro
     * continua inteiro (DEC-56).
     */
    public function temArquivo(): bool
    {
        return $this->arquivo_removido_em === null;
    }

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function ehVideo(): bool
    {
        return $this->tipo === TipoMidia::Video;
    }

    /**
     * Proporção largura÷altura.
     *
     * Derivado nunca vira coluna: uma fonte por fato. Se fosse gravado, bastaria
     * alguém corrigir a resolução e esquecer a proporção pra ficarem em desacordo.
     */
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

    /** Tamanho legível pra tela ("12,4 MB"). */
    public function tamanhoLegivel(): string
    {
        $mb = $this->tamanho_bytes / 1024 / 1024;

        return $mb < 1
            ? number_format($this->tamanho_bytes / 1024, 0, ',', '.').' KB'
            : number_format($mb, 1, ',', '.').' MB';
    }

    /** Duração legível pra tela ("1:23"). */
    public function duracaoLegivel(): ?string
    {
        if ($this->duracao_segundos === null) {
            return null;
        }

        return sprintf('%d:%02d', intdiv($this->duracao_segundos, 60), $this->duracao_segundos % 60);
    }
}

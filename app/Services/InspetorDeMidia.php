<?php

namespace App\Services;

use App\Enums\TipoMidia;
use App\Support\Midia\EspecificacaoDaRede;
use App\Support\Midia\FichaTecnica;
use App\Support\Midia\Laudo;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Lê o arquivo com o `ffprobe` e monta o laudo.
 *
 * ⚠️ `ffprobe` é programa do SISTEMA, não vem no `composer install`. Ver README.
 *
 * Regra de ouro: **a falta dele degrada, nunca quebra.** Sem `ffprobe` o upload
 * continua funcionando e a tela avisa que o laudo está indisponível — o cliente
 * nunca vê erro técnico por causa de uma ferramenta que não é problema dele.
 */
class InspetorDeMidia
{
    public function disponivel(): bool
    {
        try {
            $processo = new Process([config('midia.ffprobe'), '-version']);
            $processo->setTimeout(5);
            $processo->run();

            return $processo->isSuccessful();
        } catch (\Throwable) {
            return false;
        }
    }

    /** Ficha técnica crua, sem julgamento. Null = não deu pra ler. */
    public function inspecionar(string $caminhoAbsoluto): ?FichaTecnica
    {
        $bruto = $this->rodarFfprobe($caminhoAbsoluto);

        if ($bruto === null) {
            return null;
        }

        $formato = $bruto['format'] ?? [];
        $video = $this->primeiraTrilha($bruto, 'video');
        $audio = $this->primeiraTrilha($bruto, 'audio');

        return new FichaTecnica(
            formato: $formato['format_name'] ?? null,
            duracaoSegundos: isset($formato['duration']) ? (float) $formato['duration'] : null,
            largura: isset($video['width']) ? (int) $video['width'] : null,
            altura: isset($video['height']) ? (int) $video['height'] : null,
            codecVideo: $video['codec_name'] ?? null,
            codecAudio: $audio['codec_name'] ?? null,
            taxaAmostragemAudio: isset($audio['sample_rate']) ? (int) $audio['sample_rate'] : null,
            canaisAudio: isset($audio['channels']) ? (int) $audio['channels'] : null,
            fps: $this->calcularFps($video['avg_frame_rate'] ?? null),
            bitrate: isset($formato['bit_rate']) ? (int) $formato['bit_rate'] : null,
            tamanhoBytes: isset($formato['size']) ? (int) $formato['size'] : null,
        );
    }

    /** Ficha + veredito de cada rede. */
    public function laudar(string $caminhoAbsoluto, TipoMidia $tipo): Laudo
    {
        if (! $this->disponivel()) {
            return Laudo::indisponivel(
                'A ferramenta de análise de vídeo não está disponível no servidor.'
            );
        }

        $ficha = $this->inspecionar($caminhoAbsoluto);

        if ($ficha === null) {
            return Laudo::indisponivel('Não foi possível ler este arquivo.');
        }

        $porRede = [];

        foreach (EspecificacaoDaRede::todas() as $rede) {
            $porRede[$rede->plataforma->value] = $rede->conferir($ficha, $tipo === TipoMidia::Video);
        }

        return new Laudo($ficha, $porRede);
    }

    private function rodarFfprobe(string $caminho): ?array
    {
        try {
            $processo = new Process([
                config('midia.ffprobe'),
                '-v', 'error',
                '-print_format', 'json',
                '-show_format',
                '-show_streams',
                $caminho,
            ]);

            // Sem teto, um arquivo corrompido pendura o processo e trava a
            // requisição inteira até o timeout do servidor web.
            $processo->setTimeout(config('midia.tempo_limite_inspecao'));
            $processo->run();

            if (! $processo->isSuccessful()) {
                return null;
            }

            return json_decode($processo->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        } catch (ProcessTimedOutException $e) {
            Log::warning('ffprobe estourou o tempo limite', ['caminho' => basename($caminho)]);

            return null;
        } catch (\Throwable $e) {
            // Só o nome do arquivo no log: o caminho completo entrega a estrutura
            // de pastas do servidor.
            Log::warning('ffprobe falhou', [
                'caminho' => basename($caminho),
                'erro' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function primeiraTrilha(array $bruto, string $tipo): array
    {
        foreach ($bruto['streams'] ?? [] as $trilha) {
            if (($trilha['codec_type'] ?? null) === $tipo) {
                return $trilha;
            }
        }

        return [];
    }

    /** O ffprobe devolve fps como fração ("30000/1001"). */
    private function calcularFps(?string $fracao): ?float
    {
        if ($fracao === null || ! str_contains($fracao, '/')) {
            return null;
        }

        [$numerador, $denominador] = array_map('intval', explode('/', $fracao, 2));

        return $denominador > 0 ? round($numerador / $denominador, 2) : null;
    }
}

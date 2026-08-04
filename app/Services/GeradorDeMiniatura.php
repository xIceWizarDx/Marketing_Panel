<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Tira um quadro do vídeo para o histórico ter cara.
 *
 * ⭐ Sem isso, três vídeos baixados do WhatsApp na mesma tarde viram três linhas
 * idênticas na lista de publicações — e ninguém reconhece o que publicou.
 *
 * A miniatura pesa ~40 KB contra ~20 MB do vídeo. É essa diferença que permite
 * guardá-la para sempre, mesmo depois de o arquivo original sair: o histórico
 * continua reconhecível sem o produto virar acervo.
 */
class GeradorDeMiniatura
{
    /**
     * De onde tirar o quadro.
     *
     * ⚠️ Nunca o instante zero: vídeo costuma abrir em preto, e uma lista de
     * retângulos pretos não resolve nada. 1 segundo já pegou conteúdo, e é curto
     * o bastante para caber em vídeo de 3 segundos.
     */
    private const SEGUNDO_DO_QUADRO = 1;

    /** Suficiente para a grade e para telas de alta densidade. */
    private const LARGURA = 480;

    /** Um quadro é rápido. Passou disso, algo está errado. */
    private const TEMPO_LIMITE = 20;

    /**
     * Gera e devolve o caminho da miniatura, ou `null` se não deu.
     *
     * ⚠️ **Nunca lança.** Mídia sem miniatura é aceitável; envio recusado por
     * causa de uma imagem de apoio, não. Se o ffmpeg faltar no servidor, a
     * pessoa continua publicando — só perde o conforto de reconhecer pelo olho.
     */
    public function gerar(string $caminhoDaMidia, string $disco): ?string
    {
        $origem = Storage::disk($disco)->path($caminhoDaMidia);

        // Ao lado do vídeo, na mesma pasta do dono: o isolamento por cliente
        // vale para a miniatura igual (DEC-50).
        $destino = dirname($caminhoDaMidia).'/'.Str::ulid().'.jpg';
        $saida = Storage::disk($disco)->path($destino);

        Storage::disk($disco)->makeDirectory(dirname($destino));

        try {
            $processo = new Process([
                config('midia.ffmpeg'),
                '-ss', (string) self::SEGUNDO_DO_QUADRO,
                '-i', $origem,
                // Um quadro só.
                '-frames:v', '1',
                // Reduz mantendo a proporção — `-1` deixa o ffmpeg calcular a
                // altura. Forçar as duas medidas distorceria vídeo vertical.
                '-vf', 'scale='.self::LARGURA.':-1',
                '-q:v', '4',
                '-y',
                $saida,
            ]);

            $processo->setTimeout(self::TEMPO_LIMITE);
            $processo->mustRun();
        } catch (ProcessFailedException|ProcessTimedOutException $erro) {
            // ⚠️ Só o nome do arquivo no log: o caminho completo entrega a
            // estrutura de pastas, que inclui o identificador do dono.
            Log::warning('Não consegui gerar a miniatura', [
                'arquivo' => basename($caminhoDaMidia),
                'motivo' => Str::limit($erro->getMessage(), 200),
            ]);

            return null;
        }

        // O ffmpeg pode sair com sucesso e não escrever nada (vídeo mais curto
        // que o instante pedido, por exemplo). Sem arquivo, não há miniatura.
        if (! Storage::disk($disco)->exists($destino)) {
            return null;
        }

        return $destino;
    }
}

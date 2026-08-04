<?php

namespace App\Console\Commands;

use App\Support\Midia\LimiteDeEnvio;
use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Diz, em uma linha, se o servidor consegue inspecionar video.
 *
 * Existe porque `ffprobe`/`ffmpeg` NAO vem com o projeto: sao programas do
 * sistema. Sem este comando, a falta deles so apareceria quando alguem tentasse
 * subir um video — e o erro seria "comando nao encontrado", que nao ajuda em
 * nada quem esta configurando o servidor.
 *
 * Rodar depois de todo deploy em maquina nova.
 */
class VerificarFerramentasDeMidia extends Command
{
    protected $signature = 'midia:verificar';

    protected $description = 'Confere se ffprobe e ffmpeg estao instalados e acessiveis';

    public function handle(): int
    {
        $this->info('Ferramentas de mídia');
        $this->newLine();

        $ffprobe = $this->conferir('ffprobe', config('midia.ffprobe'), 'lê o arquivo e monta o laudo');
        $ffmpeg = $this->conferir('ffmpeg', config('midia.ffmpeg'), 'recodifica quando a rede exige');

        $this->newLine();

        $envioOk = $this->conferirTamanhoDeEnvio();

        $this->newLine();

        if ($ffprobe && $ffmpeg && $envioOk) {
            $this->info('✔ Tudo certo. O módulo de mídia funciona por completo.');

            return self::SUCCESS;
        }

        if (! $envioOk) {
            $this->warn('Para o servidor aceitar o tamanho que o produto promete:');
            $this->line('  1. No php.ini, suba as duas — `post_max_size` precisa ser MAIOR:');
            $this->line('       upload_max_filesize = '.config('midia.tamanho_maximo_mb').'M');
            $this->line('       post_max_size       = '.(config('midia.tamanho_maximo_mb') + 20).'M');
            $this->line('  2. Reinicie o servidor (php artisan serve) e o worker da fila');
            $this->newLine();
        }

        if ($ffprobe && $ffmpeg) {
            return self::FAILURE;
        }

        $this->warn('Como resolver:');
        $this->line('  1. Baixe o FFmpeg em https://ffmpeg.org/download.html');
        $this->line('     (Windows: pacote "essentials"; Linux: apt install ffmpeg)');
        $this->line('  2. Descubra o caminho: '.(PHP_OS_FAMILY === 'Windows' ? 'where ffprobe' : 'which ffprobe'));
        $this->line('  3. Escreva no .env: FFPROBE_CAMINHO e FFMPEG_CAMINHO');
        $this->line('  4. php artisan config:clear && php artisan midia:verificar');

        return self::FAILURE;
    }

    /**
     * O servidor aceita o tamanho que a tela promete?
     *
     * ⚠️ Sem esta conferência, o desencontro só aparece no meio de um envio de
     * 300 MB — com a mensagem "não conseguimos receber o arquivo", que não diz
     * onde está o problema. É o tipo de coisa que reaparece em toda máquina
     * nova, incluindo o servidor.
     */
    private function conferirTamanhoDeEnvio(): bool
    {
        $desejado = (int) config('midia.tamanho_maximo_mb');
        $real = LimiteDeEnvio::megabytes();

        if (! LimiteDeEnvio::phpEstaSegurando()) {
            $this->line("  ✔ <fg=green>envio de arquivo</> — aceita até {$real} MB");

            return true;
        }

        $this->line("  ✘ <fg=red>envio de arquivo</> — o produto promete {$desejado} MB, mas este servidor corta em {$real} MB");
        $this->line('      <fg=gray>upload_max_filesize='.ini_get('upload_max_filesize').'  post_max_size='.ini_get('post_max_size').'</>');

        return false;
    }

    private function conferir(string $nome, string $caminho, string $paraQue): bool
    {
        try {
            $processo = new Process([$caminho, '-version']);
            $processo->setTimeout(10);
            $processo->mustRun();

            // A primeira linha traz a versao; o resto e ruido de compilacao.
            $versao = strtok($processo->getOutput(), "\n");

            $this->line("  ✔ <fg=green>{$nome}</> — {$paraQue}");
            $this->line("      <fg=gray>{$versao}</>");

            return true;
        } catch (ProcessFailedException|\Throwable $e) {
            $this->line("  ✘ <fg=red>{$nome}</> não encontrado — {$paraQue}");
            $this->line("      <fg=gray>procurei em: {$caminho}</>");

            return false;
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Midia;
use App\Services\MidiaService;
use App\Support\ContextoDoUsuario;
use App\Support\Midia\LiberacaoDeArquivo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * A rede de segurança da limpeza.
 *
 * ⚠️ **Quem libera o arquivo publicado é o MOTOR**, no instante em que a
 * publicação termina (DEC-59). Este comando existe para os dois casos que o
 * motor não alcança:
 *
 *   1. **abandono** — enviou e desistiu no meio, sem nunca publicar. O arquivo
 *      ficaria para sempre esperando um serviço que não vem;
 *   2. **o que escapou** — falha de disco na hora da publicação, processo morto
 *      no meio. Sem isto, o arquivo sobreviveria calado.
 *
 * ⛔ **O registro nunca é apagado.** Some só o arquivo pesado (~20 MB); ficam a
 * miniatura (~40 KB), o laudo, os links e a prova.
 */
class LiberarArquivosCumpridos extends Command
{
    protected $signature = 'midias:liberar
        {--dias= : sobrescreve o prazo do abandono}
        {--simular : mostra o que sairia, sem apagar nada}';

    protected $description = 'Rede de segurança: remove arquivo abandonado e o que escapou do motor';

    public function handle(MidiaService $midias): int
    {
        // ⚠️ `??`, não `?:`. Com `?:`, passar `--dias=0` cairia no padrão,
        // porque zero é falso em PHP — e zero é justamente o valor que se usa
        // para conferir o comando à mão.
        // `--dias` sobrescreve o prazo do ABANDONO. A fonte única lê da config,
        // então o comando ajusta a config para esta execução em vez de
        // recalcular por fora — assim as duas nunca discordam.
        if ($this->option('dias') !== null) {
            config(['midia.limpar_abandonado_em_dias' => (int) $this->option('dias')]);
        }

        $limite = now();
        $simulando = (bool) $this->option('simular');

        // `semEscopo`: é manutenção da plataforma, varre todos os clientes.
        $candidatas = ContextoDoUsuario::semEscopo(
            fn () => Midia::query()->whereNull('arquivo_removido_em')->get()
        );

        $liberadas = 0;
        $bytes = 0;

        foreach ($candidatas as $midia) {
            $liberaEm = LiberacaoDeArquivo::em($midia);

            // `null` = ainda tem função. Data no futuro = envio recente que
            // ainda pode virar publicação.
            if (! $liberaEm || $liberaEm->gt($limite)) {
                continue;
            }

            $motivo = LiberacaoDeArquivo::motivo($midia);

            $bytes += (int) $midia->tamanho_bytes;
            $liberadas++;

            $this->line("  <fg=green>−</> {$midia->nome_original} <fg=gray>({$motivo})</>");

            if (! $simulando) {
                $this->liberar($midia, $midias->disco());
            }
        }

        $this->newLine();

        if ($liberadas === 0) {
            $this->info('Nenhum arquivo cumpriu a função ainda.');

            return self::SUCCESS;
        }

        $mb = round($bytes / 1024 / 1024, 1);

        $this->info($simulando
            ? "{$liberadas} arquivo(s) sairiam, liberando {$mb} MB. Nada foi apagado."
            : "{$liberadas} arquivo(s) liberado(s), {$mb} MB. Os registros continuam.");

        return self::SUCCESS;
    }

    private function liberar(Midia $midia, string $disco): void
    {
        // ⚠️ A miniatura FICA. É ela que mantém o histórico reconhecível depois
        // que o vídeo sai — e custa 500× menos.
        Storage::disk($disco)->delete($midia->caminho);

        // `forceFill`: manutenção da plataforma, sem sessão de cliente.
        $midia->forceFill(['arquivo_removido_em' => now()])->save();
    }
}

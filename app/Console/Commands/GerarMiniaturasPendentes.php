<?php

namespace App\Console\Commands;

use App\Enums\TipoMidia;
use App\Models\Midia;
use App\Services\GeradorDeMiniatura;
use App\Services\MidiaService;
use App\Support\ContextoDoUsuario;
use Illuminate\Console\Command;

/**
 * Gera a miniatura das mídias que subiram antes de o recurso existir.
 *
 * Sem isto, quem já usava o painel continuaria com a lista de ícones cinza
 * para sempre — e o problema que a miniatura resolve é exatamente o de quem já
 * tem arquivo acumulado.
 *
 * Roda quantas vezes for preciso: só toca no que ainda não tem.
 */
class GerarMiniaturasPendentes extends Command
{
    protected $signature = 'midias:miniaturas {--forcar : refaz até as que já têm}';

    protected $description = 'Gera a miniatura dos vídeos que ainda não têm';

    public function handle(GeradorDeMiniatura $gerador, MidiaService $midias): int
    {
        // `semEscopo`: é manutenção da plataforma, varre todos os clientes.
        $pendentes = ContextoDoUsuario::semEscopo(fn () => Midia::query()
            ->where('tipo', TipoMidia::Video)
            ->when(! $this->option('forcar'), fn ($q) => $q->whereNull('miniatura'))
            ->get());

        if ($pendentes->isEmpty()) {
            $this->info('Nenhum vídeo esperando miniatura.');

            return self::SUCCESS;
        }

        $feitas = 0;
        $falhas = 0;

        foreach ($pendentes as $midia) {
            $caminho = $gerador->gerar($midia->caminho, $midias->disco());

            if (! $caminho) {
                $falhas++;
                $this->line("  <fg=yellow>✕</> {$midia->nome_original}");

                continue;
            }

            // `forceFill`: a coluna é de manutenção, e passar pelo escopo do dono
            // exigiria uma sessão que o comando não tem.
            $midia->forceFill(['miniatura' => $caminho])->save();
            $feitas++;
            $this->line("  <fg=green>✔</> {$midia->nome_original}");
        }

        $this->newLine();
        $this->info("{$feitas} miniatura(s) gerada(s).");

        if ($falhas > 0) {
            // Não é erro do comando: vídeo curto ou corrompido não gera quadro, e
            // mídia sem miniatura continua publicável.
            $this->warn("{$falhas} não deram — a lista mostra o ícone nesses casos.");
        }

        return self::SUCCESS;
    }
}

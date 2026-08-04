<?php

namespace App\Console\Commands;

use App\Enums\StatusDestino;
use App\Models\Destino;
use App\Services\PublicacaoService;
use App\Support\ContextoDoUsuario;
use Illuminate\Console\Command;

/**
 * O WATCHDOG — resgata destino que ficou preso.
 *
 * Worker morto, deploy no meio do envio, servidor reiniciado: o destino fica
 * `enviando` para sempre e a tela mostra "Enviando…" eternamente. Ninguém
 * reclama, ninguém percebe, e o post nunca sai.
 *
 * Roda de minuto em minuto e devolve à fila o que está parado tempo demais.
 */
class ResgatarDestinosOrfaos extends Command
{
    protected $signature = 'motor:resgatar-orfaos {--minutos=20 : Tempo parado para considerar preso}';

    protected $description = 'Devolve à fila os destinos que travaram no meio do envio';

    public function handle(PublicacaoService $motor): int
    {
        $limite = now()->subMinutes((int) $this->option('minutos'));

        // `semEscopo`: o watchdog é da plataforma, varre todos os clientes.
        $presos = ContextoDoUsuario::semEscopo(fn () => Destino::query()
            ->whereIn('status', [StatusDestino::Enviando, StatusDestino::Processando])
            ->where('updated_at', '<', $limite)
            ->with('publicacao')
            ->get());

        if ($presos->isEmpty()) {
            $this->info('Nenhum destino preso.');

            return self::SUCCESS;
        }

        foreach ($presos as $destino) {
            ContextoDoUsuario::definir($destino->publicacao->usuario_id);

            // Devolve à fila respeitando o teto de tentativas — o próprio
            // serviço decide quando parar de insistir e marcar falha.
            $motor->devolverParaFila(
                $destino,
                'O envio ficou parado e foi retomado automaticamente.'
            );

            $this->line("  resgatado: {$destino->ulid}");
        }

        ContextoDoUsuario::limpar();

        $this->info("{$presos->count()} destino(s) devolvido(s) à fila.");

        return self::SUCCESS;
    }
}

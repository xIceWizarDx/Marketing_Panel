<?php

namespace App\Jobs;

use App\Enums\StatusDestino;
use App\Models\Destino;
use App\Publicadores\RegistroDePublicadores;
use App\Services\PublicacaoService;
use App\Support\ContextoDoUsuario;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * ⭐ A CONCILIAÇÃO — o diferencial do produto vira código aqui.
 *
 * Vai à rede e pergunta: *o post existe mesmo?* Só quando a resposta é sim, com
 * link, o destino vira `publicado`.
 *
 * É a razão de existir do produto. HTTP 200 no envio não é publicação: TikTok e
 * YouTube moderam depois de aceitar, e nenhum concorrente relê o post. É por
 * isso que os painéis deles dizem "publicado" para coisa que nunca subiu.
 */
class ConciliarDestinoJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 1;

    /** Quantas vezes perguntamos antes de desistir de esperar. */
    private const MAX_CONSULTAS = 20;

    public function __construct(
        public readonly int $destinoId,
        public readonly int $consulta = 1,
    ) {}

    public function handle(
        PublicacaoService $motor,
        RegistroDePublicadores $publicadores,
    ): void {
        $destino = ContextoDoUsuario::semEscopo(
            fn () => Destino::with('publicacao', 'contaSocial.credencial')->find($this->destinoId)
        );

        if (! $destino) {
            return;
        }

        ContextoDoUsuario::definir($destino->publicacao->usuario_id);

        // Idempotência: só concilia o que está aguardando confirmação.
        if ($destino->status !== StatusDestino::Processando) {
            return;
        }

        $resultado = $publicadores->para($destino->contaSocial->plataforma)->conciliar($destino);

        if ($resultado->noAr) {
            // ⭐ Aqui, e só aqui, nasce a prova — com a qualidade que a rede
            // diz ter entregado.
            $motor->marcarPublicado($destino, $resultado->url, qualidade: $resultado->qualidade);

            return;
        }

        if (! $resultado->aindaProcessando) {
            $motor->marcarFalha($destino, $resultado->erro ?? 'A rede não confirmou a publicação.');

            return;
        }

        if ($this->consulta >= self::MAX_CONSULTAS) {
            // Nunca marcar publicado por cansaço. Se não deu pra confirmar,
            // o honesto é dizer que não deu — com o motivo.
            $motor->marcarFalha(
                $destino,
                'A rede aceitou o envio mas não confirmou a publicação. Confira direto no aplicativo.'
            );

            return;
        }

        // Espera progressiva: 1min, 2min, 3min... A moderação demora o quanto
        // demorar, e insistir de segundo em segundo só gasta limite de taxa.
        self::dispatch($this->destinoId, $this->consulta + 1)
            ->delay(now()->addMinutes($this->consulta));
    }

    public function viaQueue(): string
    {
        return 'conciliacao';
    }
}

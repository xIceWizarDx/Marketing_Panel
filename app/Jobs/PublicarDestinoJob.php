<?php

namespace App\Jobs;

use App\Enums\StatusDestino;
use App\Models\Destino;
use App\Publicadores\RegistroDePublicadores;
use App\Publicadores\Retomada;
use App\Services\PublicacaoService;
use App\Support\ContextoDoUsuario;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Envia UM destino para UMA rede.
 *
 * Um job por destino (nunca um por publicação): assim a falha do TikTok não
 * impede o Bluesky de subir, e cada rede pode ter fila e ritmo próprios.
 */
class PublicarDestinoJob implements ShouldQueue
{
    use Queueable;

    /**
     * Maior que o maior upload possível.
     *
     * ⚠️ O padrão de 60s somado ao `retry_after` da fila é a receita clássica do
     * post duplicado: a fila re-entrega o job achando que morreu, enquanto o
     * primeiro ainda está enviando 300 MB.
     */
    public int $timeout = 900;

    /** Retentativa é decidida pelo serviço, não pelo mecanismo da fila. */
    public int $tries = 1;

    public function __construct(public readonly int $destinoId) {}

    public function handle(
        PublicacaoService $motor,
        RegistroDePublicadores $publicadores,
    ): void {
        // ⚠️ O worker NÃO tem sessão. Sem esta linha, toda consulta a dado de
        // cliente estouraria — que é exatamente o que o guardião de isolamento
        // garante. O `usuario_id` vem do banco, não de contexto ambiente.
        $destino = ContextoDoUsuario::semEscopo(
            fn () => Destino::with('publicacao', 'contaSocial.credencial')->find($this->destinoId)
        );

        if (! $destino) {
            return;
        }

        ContextoDoUsuario::definir($destino->publicacao->usuario_id);

        // ⭐ IDEMPOTÊNCIA: a fila pode entregar o mesmo job duas vezes. Só
        // trabalha quem está esperando na fila — destino já enviado, publicado
        // ou falhado é ignorado em silêncio.
        if ($destino->status !== StatusDestino::Pendente) {
            return;
        }

        if (! $destino->contaSocial->podePublicar()) {
            $motor->marcarFalha(
                $destino,
                "A conta {$destino->contaSocial->nome_exibicao} não está conectada. Reconecte para publicar."
            );

            return;
        }

        $motor->marcarEnviando($destino);

        $destino = $destino->fresh();

        $resultado = $publicadores
            ->para($destino->contaSocial->plataforma)
            ->publicar($destino, new Retomada($destino, $motor));

        $destino = $destino->fresh();

        match (true) {
            $resultado->aceito => $motor->marcarProcessando($destino, $resultado->identificadorExterno),
            $resultado->semCota => $motor->aguardarJanela($destino, $resultado->erro),
            $resultado->transitorio => $this->reenfileirar($motor, $destino, $resultado->erro),
            default => $motor->marcarFalha($destino, $resultado->erro, $resultado->codigo),
        };

        // A rede aceitou → agora é preciso CONFERIR. Só a conciliação pode
        // dizer que está publicado (DEC-31).
        if ($resultado->aceito) {
            ConciliarDestinoJob::dispatch($this->destinoId)->delay(now()->addSeconds(30));
        }
    }

    /**
     * ⚠️ Devolver à fila **e realmente voltar para a fila**.
     *
     * `devolverParaFila` só muda o estado para `pendente`; sem este despacho o
     * destino ficava parado para sempre, esperando um job que nunca vinha. O teto
     * de 3 tentativas nunca era alcançado porque a segunda nunca acontecia — a
     * retentativa inteira era decoração.
     *
     * Espera crescente (1min, 2min, 3min): insistir de segundo em segundo contra
     * uma rede instável só queima o limite de taxa da conta.
     */
    private function reenfileirar(PublicacaoService $motor, Destino $destino, ?string $motivo): void
    {
        $motor->devolverParaFila($destino, $motivo);

        // O serviço decide quando desistir. Se ele marcou falha, acabou aqui.
        if ($destino->fresh()?->status !== StatusDestino::Pendente) {
            return;
        }

        self::dispatch($this->destinoId)
            ->delay(now()->addMinutes(max(1, $destino->fresh()->tentativas_feitas)));
    }

    /** Fila por plataforma: uma rede lenta não segura a fila das outras. */
    public function viaQueue(): string
    {
        return 'publicacao';
    }
}

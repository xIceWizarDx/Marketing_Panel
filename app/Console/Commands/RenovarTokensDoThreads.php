<?php

namespace App\Console\Commands;

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Models\ContaSocial;
use App\Support\ContextoDoUsuario;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * ⭐ Renova o token do Threads — e este comando **impede a conta de morrer**.
 *
 * ⚠️ É a única rede do produto com morte definitiva por inatividade. O token
 * vale 60 dias e renova por mais 60, **mas só dentro de uma janela**: precisa ter
 * pelo menos 24 horas de idade e não estar vencido. Passou dos 60 sem renovar,
 * não existe renovação — só reconectar, e a pessoa descobre isso quando a
 * publicação falhar.
 *
 * ⛔ Por isso ele mexe **muito antes** do prazo, e não em cima dele: com folga,
 * uma semana inteira de servidor desligado ainda cabe dentro da janela. Esperar
 * o dia do vencimento é apostar que nada dá errado no único dia em que dar errado
 * custa a conta.
 */
class RenovarTokensDoThreads extends Command
{
    protected $signature = 'threads:renovar {--dias=15 : Renovar tokens que vencem em menos de N dias}';

    protected $description = 'Renova os tokens do Threads antes de eles vencerem de vez';

    private const RENOVAR = 'https://graph.threads.net/refresh_access_token';

    /**
     * A idade mínima que a rede exige, com um pouco de folga.
     *
     * ⚠️ A documentação diz "pelo menos 24 horas". Pedimos 25 porque o relógio
     * do servidor e o da rede não são o mesmo, e um token de 23h59 seria
     * recusado sem motivo aparente.
     */
    private const HORAS_MINIMAS = 25;

    public function handle(): int
    {
        $limite = now()->addDays((int) $this->option('dias'));

        $contas = ContextoDoUsuario::semEscopo(fn () => ContaSocial::query()
            ->where('plataforma', Plataforma::Threads)
            ->whereIn('status', [StatusConta::Ativa, StatusConta::Expirada])
            ->with('credencial')
            ->get());

        $renovados = 0;
        $perdidos = 0;

        foreach ($contas as $conta) {
            $resultado = $this->renovar($conta);

            $resultado === true ? $renovados++ : ($resultado === false ? $perdidos++ : null);
        }

        $this->info("{$renovados} token(s) renovado(s), {$perdidos} sem renovação possível.");

        return self::SUCCESS;
    }

    /** `true` renovou · `false` perdeu · `null` não era hora ainda. */
    private function renovar(ContaSocial $conta): ?bool
    {
        $credencial = $conta->credencial;

        if (! $credencial || ! $credencial->expira_em) {
            return null;
        }

        /*
         * ⛔ Vencido não renova, e isso não é falha passageira: a conta morreu.
         *
         * Marcar como expirada é o que faz o semáforo (DEC-32) contar a verdade
         * em vez de a pessoa descobrir na próxima publicação.
         */
        if ($credencial->expira_em->isPast()) {
            $conta->forceFill([
                'status' => StatusConta::Expirada,
                'status_detalhe' => 'A autorização do Threads venceu e não pode mais ser renovada. Conecte de novo.',
            ])->save();

            $this->line("  ✗ {$conta->nome_exibicao} — autorização vencida, precisa reconectar");

            return false;
        }

        // ⚠️ Cedo demais: a rede recusa token com menos de 24 horas.
        if ($credencial->created_at?->diffInHours(now()) < self::HORAS_MINIMAS) {
            return null;
        }

        if ($credencial->expira_em->isAfter(now()->addDays((int) $this->option('dias')))) {
            return null;
        }

        try {
            $resposta = Http::timeout(20)->get(self::RENOVAR, [
                'grant_type' => 'th_refresh_token',
                'access_token' => $credencial->access_token,
            ]);
        } catch (ConnectionException) {
            // ⛔ Rede fora do ar não é autorização perdida: não mexe na conta.
            // A folga de 15 dias existe para isto — amanhã tenta de novo.
            $this->line("  · {$conta->nome_exibicao} — o Threads não respondeu, tentamos de novo");

            return null;
        }

        if (! $resposta->successful() || ! $resposta->json('access_token')) {
            /*
             * ⚠️ Também não marca a conta. A janela ainda tem dias, e uma
             * resposta ruim hoje pode ser cota, manutenção ou erro do servidor
             * deles. Quem marca é o vencimento, lá em cima — esse é fato.
             */
            $this->line("  · {$conta->nome_exibicao} — a renovação não foi aceita agora");

            return null;
        }

        $credencial->forceFill([
            'access_token' => (string) $resposta->json('access_token'),
            'expira_em' => now()->addSeconds((int) $resposta->json('expires_in', 0)),
        ])->save();

        $conta->forceFill(['status' => StatusConta::Ativa, 'status_detalhe' => null])->save();

        $this->line("  ✓ {$conta->nome_exibicao}");

        return true;
    }
}

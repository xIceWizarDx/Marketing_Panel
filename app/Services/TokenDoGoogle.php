<?php

namespace App\Services;

use App\Enums\StatusConta;
use App\Models\ContaSocial;
use App\Models\Credencial;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Mantém o acesso ao Google válido.
 *
 * O token do Google dura 1 hora; o de renovação dura até ser revogado. Este
 * serviço troca um pelo outro quando necessário.
 *
 * ⚠️ **A renovação é serializada com trava.** Sem isso, dois jobs publicando ao
 * mesmo tempo renovam em paralelo — e o Google invalida o token da primeira
 * chamada, derrubando o outro job com "credencial inválida" do nada.
 */
class TokenDoGoogle
{
    private const URL_TOKEN = 'https://oauth2.googleapis.com/token';

    /** Renova alguns minutos antes de vencer: relógio do servidor não é exato. */
    private const FOLGA_SEGUNDOS = 120;

    /** Token válido para usar agora, ou null se a conexão morreu. */
    public function valido(ContaSocial $conta): ?string
    {
        $credencial = $conta->credencial;

        if (! $credencial) {
            return null;
        }

        if (! $this->precisaRenovar($credencial)) {
            return $credencial->access_token;
        }

        // A trava é por conta: contas diferentes renovam em paralelo sem se
        // atrapalhar. 15s é bem mais que a chamada leva.
        return Cache::lock("google:token:{$conta->id}", 15)->block(20, function () use ($conta) {
            // Relê depois de pegar a trava: outro processo pode ter renovado
            // enquanto este esperava, e renovar de novo invalidaria o dele.
            $credencial = $conta->credencial()->first();

            if (! $credencial || ! $this->precisaRenovar($credencial)) {
                return $credencial?->access_token;
            }

            return $this->renovar($conta, $credencial);
        });
    }

    private function precisaRenovar(Credencial $credencial): bool
    {
        return $credencial->expira_em === null
            || $credencial->expira_em->isBefore(now()->addSeconds(self::FOLGA_SEGUNDOS));
    }

    private function renovar(ContaSocial $conta, Credencial $credencial): ?string
    {
        if (! $credencial->refresh_token) {
            $this->marcarExpirada($conta, 'A autorização do Google não pode ser renovada. Reconecte a conta.');

            return null;
        }

        try {
            $resposta = Http::asForm()->timeout(15)->post(self::URL_TOKEN, [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $credencial->refresh_token,
                'grant_type' => 'refresh_token',
            ]);
        } catch (ConnectionException) {
            // Rede fora do ar não é conexão revogada: não marca a conta como
            // quebrada, só falha esta tentativa.
            return null;
        }

        // ⚠️ Falhar NAO e ser revogado. Um 500 do Google e passageiro; marcar a
        // conta como morta por causa dele obrigaria a pessoa a reconectar sem
        // necessidade — e o proximo job funcionaria normalmente.
        // So `invalid_grant` significa que a autorizacao acabou de verdade.
        if ($resposta->json('error') === 'invalid_grant') {
            $this->marcarExpirada($conta, $this->explicarInvalidGrant());

            return null;
        }

        if (! $resposta->successful()) {
            return null;
        }

        $credencial->forceFill([
            'access_token' => $resposta->json('access_token'),
            // O Google só devolve refresh_token novo quando ele muda. Sobrescrever
            // com null apagaria o que ainda vale.
            'refresh_token' => $resposta->json('refresh_token') ?? $credencial->refresh_token,
            'expira_em' => now()->addSeconds((int) $resposta->json('expires_in', 3600)),
        ])->save();

        return $credencial->access_token;
    }

    /**
     * ⚠️ A causa mais provável, e a menos óbvia: **modo de Testes**.
     *
     * Enquanto a tela de permissão do Google estiver como "Testes", a
     * autorização de longo prazo **expira em 7 dias** — a conexão simplesmente
     * para, sem aviso e sem nada ter mudado aqui. Dizer só "reconecte" faria a
     * pessoa reconectar toda semana sem entender por quê.
     */
    private function explicarInvalidGrant(): string
    {
        $emTestes = 'A autorização do Google venceu. Isso é esperado enquanto o aplicativo estiver '.
            'em modo de Testes no Google Cloud: nesse modo o Google encerra o acesso a cada 7 dias. '.
            'Reconecte a conta para continuar publicando.';

        $publicado = 'O Google encerrou o acesso desta conta. Reconecte para voltar a publicar.';

        return config('services.google.em_testes', true) ? $emTestes : $publicado;
    }

    private function marcarExpirada(ContaSocial $conta, string $motivo): void
    {
        $conta->forceFill([
            'status' => StatusConta::Expirada,
            'status_detalhe' => $motivo,
        ])->save();
    }
}

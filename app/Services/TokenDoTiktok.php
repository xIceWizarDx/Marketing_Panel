<?php

namespace App\Services;

use App\Enums\StatusConta;
use App\Models\ContaSocial;
use App\Models\Credencial;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Mantém o acesso ao TikTok válido.
 *
 * ⛔ **O token do TikTok vive 24 HORAS** — o prazo mais curto de todas as redes
 * do painel, por larga margem. Por isso a renovação acontece **na hora de
 * usar** (DEC-118), não só num comando de madrugada: vídeo agendado, fila
 * parada ou worker que dormiu encontrariam um token morto.
 *
 * ⛔ **E o `refresh_token` GIRA.** A documentação avisa: *"The returned
 * `refresh_token` may be different than the one passed in the payload."*
 * Guardar o antigo dá uma conexão que funciona hoje, funciona amanhã, e um dia
 * para sem ninguém ter mexido em nada — o pior tipo de defeito, porque não tem
 * evento para investigar (DEC-119).
 */
class TokenDoTiktok
{
    public const TOKEN = 'https://open.tiktokapis.com/v2/oauth/token/';

    /**
     * ⚠️ Folga grande de propósito: **uma hora**.
     *
     * Com token de 1 hora (Google) uma folga de 2 minutos serve. Com token de
     * 24 horas, renovar só nos últimos minutos deixaria um envio longo — vídeo
     * grande, em pedaços sequenciais — começar válido e terminar vencido.
     */
    private const FOLGA_SEGUNDOS = 3600;

    /** Token válido para usar agora, ou `null` se a conexão morreu. */
    public function valido(ContaSocial $conta): ?string
    {
        $credencial = $conta->credencial;

        if (! $credencial) {
            return null;
        }

        if (! $this->precisaRenovar($credencial)) {
            return $credencial->access_token;
        }

        /*
         * ⚠️ A trava é por conta, e existe pelo mesmo motivo do Google: dois
         * jobs publicando ao mesmo tempo renovariam em paralelo, e o token da
         * primeira chamada morre — derrubando o outro job com "credencial
         * inválida" do nada.
         */
        return Cache::lock("tiktok:token:{$conta->id}", 15)->block(20, function () use ($conta) {
            // Relê depois de pegar a trava: outro processo pode ter renovado
            // enquanto este esperava.
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
            $this->marcarExpirada($conta, 'A autorização do TikTok não pode ser renovada. Reconecte a conta.');

            return null;
        }

        try {
            $resposta = Http::asForm()->timeout(15)->post(self::TOKEN, [
                'client_key' => config('services.tiktok.client_key'),
                'client_secret' => config('services.tiktok.client_secret'),
                'grant_type' => 'refresh_token',
                'refresh_token' => $credencial->refresh_token,
            ]);
        } catch (ConnectionException) {
            // Rede fora do ar não é autorização revogada: falha esta tentativa
            // e não encosta no estado da conta.
            return null;
        }

        /*
         * ⚠️ Só a autorização revogada mata a conta. Um 5xx do TikTok é
         * passageiro; marcar a conta como morta por causa dele obrigaria a
         * pessoa a reconectar sem necessidade — e a próxima tentativa
         * funcionaria normalmente. Mesma lição que o Google já custou.
         */
        if (in_array((string) $resposta->json('error'), ['invalid_grant', 'invalid_request'], true)
            && ! $resposta->json('access_token')) {
            $this->marcarExpirada($conta, 'A autorização do TikTok acabou. Reconecte a conta para voltar a publicar.');

            return null;
        }

        if (! $resposta->successful() || ! $resposta->json('access_token')) {
            return null;
        }

        $credencial->forceFill([
            'access_token' => $resposta->json('access_token'),
            /*
             * ⛔ **O girado é gravado** (DEC-119). Ao contrário do Google, que
             * só devolve token de renovação novo quando ele muda, aqui a
             * documentação avisa que ele *pode* vir diferente — e continuar
             * guardando o antigo quebraria a conexão num dia qualquer.
             */
            'refresh_token' => $resposta->json('refresh_token') ?: $credencial->refresh_token,
            'expira_em' => now()->addSeconds((int) $resposta->json('expires_in', 86400)),
        ])->save();

        return $credencial->access_token;
    }

    private function marcarExpirada(ContaSocial $conta, string $motivo): void
    {
        $conta->forceFill([
            'status' => StatusConta::Expirada,
            'status_detalhe' => $motivo,
        ])->save();
    }
}

<?php

namespace App\Services;

use App\Enums\StatusConta;
use App\Models\ContaSocial;
use App\Models\Credencial;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Mantém o acesso ao X válido.
 *
 * ⛔ **O token do X vive 2 HORAS** — mais curto que o do TikTok (24 h) e muito
 * mais curto que os 60 dias do LinkedIn. Renovar aqui é parte de publicar, não
 * rotina de madrugada (DEC-130).
 *
 * ⛔ **E sem `offline.access` não existe token de renovação nenhum.** Uma conexão
 * feita sem esse escopo funciona por duas horas e morre — sem nada ter mudado.
 */
class TokenDoX
{
    /**
     * ⚠️ Vinte minutos de folga num token de duas horas.
     *
     * O envio de um vídeo em pedaços de 1 MB pode levar minutos, e o post só
     * nasce depois de a rede terminar de processar: começar válido e terminar
     * vencido é caminho real aqui.
     */
    private const FOLGA_SEGUNDOS = 1200;

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

        // ⚠️ Trava por conta, pelo mesmo motivo do Google e do TikTok: dois jobs
        // renovando em paralelo derrubam um ao outro.
        return Cache::lock("x:token:{$conta->id}", 15)->block(20, function () use ($conta) {
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
            /*
             * ⚠️ Aqui isto quase sempre quer dizer uma coisa só: a conexão foi
             * feita **sem `offline.access`**. A frase diz o que fazer, porque
             * "reconecte" sozinho faria a pessoa repetir o mesmo erro.
             */
            $this->marcarExpirada(
                $conta,
                'A conexão com o X não pode ser renovada. Reconecte a conta mantendo todas as permissões marcadas.'
            );

            return null;
        }

        try {
            $resposta = Http::asForm()
                ->withBasicAuth((string) config('services.x.client_id'), (string) config('services.x.client_secret'))
                ->timeout(15)
                ->post(ConexaoComX::TOKEN, [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $credencial->refresh_token,
                    'client_id' => config('services.x.client_id'),
                ]);
        } catch (ConnectionException) {
            // Rede fora do ar não é autorização revogada.
            return null;
        }

        /*
         * ⚠️ Só a autorização revogada mata a conta. Um 5xx do X é passageiro;
         * marcar a conta como morta por causa dele obrigaria a pessoa a
         * reconectar sem necessidade. Mesma lição que o Google já custou.
         */
        if ($resposta->status() === 400 && ! $resposta->json('access_token')) {
            $this->marcarExpirada($conta, 'A autorização do X acabou. Reconecte a conta para voltar a publicar.');

            return null;
        }

        if (! $resposta->successful() || ! $resposta->json('access_token')) {
            return null;
        }

        $credencial->forceFill([
            'access_token' => $resposta->json('access_token'),
            // ⚠️ Mesmo cuidado do TikTok: se vier um novo, é ele que vale;
            // sobrescrever com vazio apagaria o que ainda serve.
            'refresh_token' => $resposta->json('refresh_token') ?: $credencial->refresh_token,
            'expira_em' => now()->addSeconds((int) $resposta->json('expires_in', 7200)),
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

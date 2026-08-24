<?php

namespace App\Console\Commands;

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Models\ContaSocial;
use App\Services\TokenDoGoogle;
use App\Support\ContextoDoUsuario;
use App\Support\ErroDoGoogle;
use App\Support\RegistroDeSeguranca;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Reconfere as contas do YouTube — exigência das Políticas do Desenvolvedor.
 *
 * A política manda duas coisas, e uma consulta resolve as duas:
 *   1. dado do YouTube guardado por no máximo **30 dias** sem ser atualizado;
 *   2. verificar **a cada 30 dias** se a autorização ainda vale.
 *
 * De brinde, alimenta o semáforo (DEC-32): se o acesso caiu, a pessoa fica
 * sabendo **antes** de tentar publicar, não no meio de uma publicação.
 */
class ReconferirContasDoYoutube extends Command
{
    protected $signature = 'youtube:reconferir {--dias=25 : Reconferir contas sem atualização há mais de N dias}';

    protected $description = 'Atualiza os dados e confirma a autorização das contas do YouTube (exigência da política)';

    public function handle(TokenDoGoogle $tokens): int
    {
        // 25 e não 30 de propósito: a folga cobre o dia em que o agendador não
        // rodar. Estourar o prazo é descumprir a política.
        $limite = now()->subDays((int) $this->option('dias'));

        $contas = ContextoDoUsuario::semEscopo(fn () => ContaSocial::query()
            ->where('plataforma', Plataforma::Youtube)
            ->whereIn('status', [StatusConta::Ativa, StatusConta::Expirada])
            ->where('updated_at', '<', $limite)
            ->with('credencial')
            ->get());

        if ($contas->isEmpty()) {
            $this->info('Nenhuma conta do YouTube para reconferir.');

            return self::SUCCESS;
        }

        $atualizadas = 0;
        $perdidas = 0;

        foreach ($contas as $conta) {
            $this->reconferir($conta, $tokens) ? $atualizadas++ : $perdidas++;
        }

        $this->info("{$atualizadas} conta(s) reconferida(s), {$perdidas} sem autorização.");

        return self::SUCCESS;
    }

    private function reconferir(ContaSocial $conta, TokenDoGoogle $tokens): bool
    {
        $token = $tokens->valido($conta);

        if (! $token) {
            // `valido()` já marcou a conta como expirada e explicou o motivo.
            $this->line("  ✗ {$conta->nome_exibicao} — autorização perdida");

            return false;
        }

        try {
            $resposta = Http::withToken($token)->timeout(20)->get(
                'https://www.googleapis.com/youtube/v3/channels',
                ['part' => 'snippet', 'mine' => 'true']
            );
        } catch (ConnectionException) {
            // Rede fora do ar não é autorização revogada: não mexe na conta.
            $this->line("  · {$conta->nome_exibicao} — sem resposta, tentamos de novo");

            return false;
        }

        /*
         * ⛔ **Falha que passa não marca conta** (DEC-98).
         *
         * Antes, qualquer resposta fora do 2xx virava lista vazia — e lista
         * vazia marca a conta como `Erro`, o que **bloqueia publicar** até
         * alguém reconectar na mão. Ou seja: um 403 de cota ou um 500 do Google,
         * que somem sozinhos, desligavam a publicação de todo mundo do YouTube.
         *
         * ⚠️ Isto fica mais caro com a leitura de métrica, que consome a mesma
         * cota: mais leitura por dia é mais chance de encostar no teto.
         */
        if (ErroDoGoogle::ehPassageiro($resposta)) {
            $this->line("  · {$conta->nome_exibicao} — o YouTube não respondeu agora, tentamos de novo");

            return false;
        }

        $itens = $resposta->successful() ? $resposta->json('items', []) : [];

        if ($itens === []) {
            $conta->forceFill([
                'status' => StatusConta::Erro,
                'status_detalhe' => 'O canal não está mais acessível. Reconecte a conta.',
            ])->save();

            RegistroDeSeguranca::registrar('rede_sem_autorizacao', [
                'plataforma' => Plataforma::Youtube->value,
                'conta_ulid' => $conta->ulid,
            ]);

            $this->line("  ✗ {$conta->nome_exibicao} — canal inacessível");

            return false;
        }

        // Atualizar o dado É a exigência: guardado sem atualizar por mais de 30
        // dias, ele precisaria ser apagado.
        $conta->forceFill([
            'nome_exibicao' => $itens[0]['snippet']['title'],
            'avatar_url' => $itens[0]['snippet']['thumbnails']['default']['url'] ?? null,
            'status' => StatusConta::Ativa,
            'status_detalhe' => null,
        ])->save();

        $this->line("  ✓ {$conta->nome_exibicao}");

        return true;
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\StatusConta;
use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\LeituraDeMetrica;
use App\Publicadores\LeitorDeMetricas;
use App\Publicadores\MetricasDoPost;
use App\Publicadores\RegistroDePublicadores;
use App\Support\ContextoDoUsuario;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

/**
 * Lê, uma vez por dia, os contadores que cada rede publica.
 *
 * ⛔ **Nunca dentro da requisição da tela** (DEC-93 §4.4). A tela mostra o que
 * está guardado, com a data da leitura. Chamar a rede no meio do carregamento
 * faria a página inteira travar no dia em que a rede estivesse lenta — e o
 * número não vale isso.
 *
 * ⚠️ **Sem sessão aqui.** O escopo de dono lança exceção quando não há dono
 * definido, de propósito; por isso cada conta é lida com
 * `ContextoDoUsuario::definir()` antes de qualquer consulta.
 */
class AtualizarMetricas extends Command
{
    protected $signature = 'metricas:atualizar {--dias=30 : Atualizar posts publicados nos últimos N dias}';

    protected $description = 'Lê os contadores que cada rede publica sobre as contas e os posts';

    /**
     * Post velho não muda mais.
     *
     * ⚠️ É isto que impede a leitura de crescer sem limite: sem recorte, cada dia
     * a mais de uso soma um dia a mais de chamadas, para sempre — e no YouTube
     * elas saem da **mesma cota** que publica.
     */
    private const DIAS_PADRAO = 30;

    public function handle(RegistroDePublicadores $registro): int
    {
        $desde = now()->subDays((int) ($this->option('dias') ?: self::DIAS_PADRAO));

        // ⚠️ Contas de TODOS os donos: é comando, não tela. `semEscopo` é
        // explícito de propósito — quem lê o código vê que a trava foi aberta
        // aqui e não por acidente.
        $contas = ContextoDoUsuario::semEscopo(fn () => ContaSocial::query()
            ->whereIn('status', [StatusConta::Ativa, StatusConta::Expirada])
            ->with('credencial')
            ->get());

        $lidas = 0;
        $posts = 0;

        foreach ($contas as $conta) {
            $leitor = $registro->leitorDe($conta->plataforma);

            // Rede sem leitor é o caso normal, não erro: das nove pesquisadas,
            // sete estão bloqueadas por aprovação, dinheiro ou contrato.
            if (! $leitor) {
                continue;
            }

            /*
             * ⛔ **Uma conta que falha não derruba as outras.**
             *
             * Sem isto, a primeira conta com autorização vencida encerraria o
             * comando e as contas seguintes ficariam com número velho sem
             * ninguém saber. Este é o mesmo defeito que a Fase 0 consertou na
             * reconferência, agora prevenido antes de existir (DEC-98).
             */
            try {
                ContextoDoUsuario::definir($conta->usuario_id);

                $lidas += $this->atualizarConta($conta, $leitor) ? 1 : 0;
                $posts += $this->atualizarPostsDaConta($conta, $leitor, $desde);
            } catch (Throwable $erro) {
                $this->line("  · {$conta->nome_exibicao} — não deu para ler agora: {$erro->getMessage()}");
            } finally {
                ContextoDoUsuario::esquecer();
            }
        }

        $this->info("{$lidas} conta(s) e {$posts} post(s) atualizados.");

        return self::SUCCESS;
    }

    /**
     * ⚠️ `forceFill`, não `update`: métrica é escrita por máquina e **não está
     * no `fillable`** de propósito — nada vindo de requisição pode encostar
     * nela.
     */
    private function atualizarConta(ContaSocial $conta, LeitorDeMetricas $leitor): bool
    {
        $metricas = $leitor->metricasDaConta($conta);

        // `null` é "não deu para ler agora" — o número guardado continua valendo,
        // com a data antiga. Apagar seria trocar um dado velho por nenhum dado.
        if ($metricas === null) {
            return false;
        }

        $conta->forceFill([
            'seguidores' => $metricas->seguidores,
            'metricas_lidas_em' => now(),
        ])->save();

        return true;
    }

    /**
     * Só o que está NO AR e é recente.
     *
     * ⚠️ Post que falhou não tem contador — não existe na rede. E post que
     * ainda está processando também não: pedir o número dele é pedir por algo
     * que a rede ainda não criou.
     */
    private function atualizarPostsDaConta(ContaSocial $conta, LeitorDeMetricas $leitor, Carbon $desde): int
    {
        $destinos = Destino::query()
            ->where('conta_social_id', $conta->id)
            ->where('status', StatusDestino::Publicado)
            ->where('publicado_em', '>=', $desde)
            ->get();

        $atualizados = 0;

        foreach ($destinos as $destino) {
            $metricas = $leitor->metricasDoPost($destino);

            if ($metricas === null || ! $metricas->temAlgum()) {
                continue;
            }

            $destino->forceFill([
                'visualizacoes' => $metricas->visualizacoes,
                'curtidas' => $metricas->curtidas,
                'comentarios' => $metricas->comentarios,
                'compartilhamentos' => $metricas->compartilhamentos,
                'metricas_lidas_em' => now(),
            ])->save();

            $this->guardarODia($destino, $metricas);

            $atualizados++;
        }

        return $atualizados;
    }

    /**
     * ⭐ **A linha do dia** (DEC-144) — o que dá passado ao número.
     *
     * ⚠️ A chave é `destino + dia`, não o instante: rodar o comando duas vezes
     * no mesmo dia **atualiza** a linha, não cria um segundo ponto. O que
     * interessa é a série diária, não quantas vezes perguntamos.
     *
     * ⛔ Isto **não substitui** as colunas do destino. Elas continuam sendo o
     * "agora", que a tela lê sem varrer histórico; aqui mora o "ontem", que só
     * a curva usa.
     */
    private function guardarODia(Destino $destino, MetricasDoPost $metricas): void
    {
        LeituraDeMetrica::updateOrCreate(
            ['destino_id' => $destino->id, 'dia' => now()->toDateString()],
            [
                'visualizacoes' => $metricas->visualizacoes,
                'curtidas' => $metricas->curtidas,
                'comentarios' => $metricas->comentarios,
                'compartilhamentos' => $metricas->compartilhamentos,
            ]
        );
    }
}

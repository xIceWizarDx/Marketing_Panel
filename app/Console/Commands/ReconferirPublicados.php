<?php

namespace App\Console\Commands;

use App\Enums\StatusDestino;
use App\Models\Destino;
use App\Publicadores\RegistroDePublicadores;
use App\Services\PublicacaoService;
use App\Support\ContextoDoUsuario;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * ⭐ **A prova deixa de expirar em três horas e meia** (DEC-145).
 *
 * ⛔ O produto inteiro se apoia em *"provamos que publicou"*. Mas
 * `ConciliarDestinoJob` pergunta 20 vezes, com espera crescente — cerca de
 * **3h30** — e depois **para para sempre**.
 *
 * ⚠️ **Moderação de rede não trabalha nesse relógio.** Um vídeo derrubado no dia
 * seguinte continuava marcado como "No ar", com a mesma confiança de sempre — e
 * a crítica que fazemos aos concorrentes passava a valer para nós a partir da
 * quarta hora.
 *
 * ⭐ Este comando relê o que está **publicado** e rebaixa o que sumiu. É a
 * diferença entre *"conferimos uma vez"* e **"conferimos e continuamos
 * conferindo"** — que é a única parte da promessa difícil de copiar, porque o
 * custo dela é proporcional ao acervo vivo.
 */
class ReconferirPublicados extends Command
{
    protected $signature = 'publicacoes:reconferir
        {--dias=30 : Reconferir posts publicados nos últimos N dias}
        {--limite=500 : Teto de posts por passada}';

    protected $description = 'Relê os posts já publicados e rebaixa os que saíram do ar';

    public function __construct(
        private readonly RegistroDePublicadores $publicadores,
        private readonly PublicacaoService $motor,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $desde = now()->subDays((int) $this->option('dias'));

        /*
         * ⛔ **Janela e teto, os dois.** Reler o acervo inteiro todo dia seria
         * caro em cota — e no X é caro em dinheiro: cada releitura custa
         * US$ 0,001 (DEC-127). Barato não é de graça.
         *
         * ⚠️ E os mais antigos primeiro: um post de 29 dias é o que está prestes
         * a sair da janela, então é a última chance de conferir.
         */
        $destinos = ContextoDoUsuario::semEscopo(fn () => Destino::query()
            ->where('status', StatusDestino::Publicado)
            ->where('publicado_em', '>=', $desde)
            ->with(['publicacao', 'contaSocial.credencial'])
            ->orderBy('publicado_em')
            ->limit((int) $this->option('limite'))
            ->get());

        $conferidos = 0;
        $sairam = 0;

        foreach ($destinos as $destino) {
            $resultado = $this->reconferir($destino);

            if ($resultado === null) {
                continue;
            }

            $conferidos++;

            if ($resultado === false) {
                $sairam++;
            }
        }

        $this->info("Reconferidos: {$conferidos}. Saíram do ar: {$sairam}.");

        return self::SUCCESS;
    }

    /**
     * @return bool|null `true` = continua no ar, `false` = saiu, `null` = não deu para conferir
     */
    private function reconferir(Destino $destino): ?bool
    {
        /*
         * ⚠️ O contexto do dono é definido POR DESTINO, dentro do laço: o
         * comando roda sem sessão e percorre contas de todos os clientes. Sem
         * isso, o escopo de um vazaria para o seguinte.
         */
        ContextoDoUsuario::definir($destino->publicacao->usuario_id);

        try {
            $publicador = $this->publicadores->para($destino->contaSocial->plataforma);
        } catch (RuntimeException) {
            return null;
        }

        $resultado = $publicador->conciliar($destino);

        if ($resultado->noAr) {
            /*
             * ⭐ Continua no ar — e a data da conferência é atualizada. É ela
             * que a tela mostra: "conferido há 2 horas" vale mais que "no ar"
             * sem data.
             */
            $destino->forceFill(['reconferido_em' => now()])->save();

            return true;
        }

        /*
         * ⚠️ **Só a recusa rebaixa.** "Ainda processando" aqui quer dizer que a
         * rede não respondeu direito agora — e derrubar um post por causa de uma
         * instabilidade seria mentir na direção oposta.
         */
        if ($resultado->aindaProcessando) {
            return null;
        }

        /*
         * ⛔ **O post saiu do ar.** Isso não é falha de publicação — ele SUBIU, e
         * foi removido depois. A frase precisa dizer isso, porque "falhou"
         * mandaria a pessoa publicar de novo sem entender o que houve.
         */
        $this->motor->marcarRemovido(
            $destino,
            $resultado->erro ?? 'Este post não está mais no ar. Ele foi publicado e removido depois.'
        );

        return false;
    }
}

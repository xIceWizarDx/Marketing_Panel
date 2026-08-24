<?php

namespace App\Console\Commands;

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Models\ContaSocial;
use App\Services\TokenDoX;
use App\Support\ContextoDoUsuario;
use Illuminate\Console\Command;

/**
 * Renova o token do X — **como rede de segurança, não como mecanismo
 * principal**.
 *
 * ⚠️ O token daqui vive **2 horas**, o prazo mais curto do painel. Um comando
 * de madrugada não daria conta sozinho: vídeo agendado, fila parada ou worker
 * que dormiu encontrariam token morto no meio do dia. Por isso quem realmente
 * mantém a conexão viva é o `TokenDoX`, chamado **na hora de publicar**
 * (DEC-130).
 *
 * ⭐ O que este comando resolve é o outro lado: conta que fica **sem publicar**.
 * Se a pessoa tiver revogado o acesso nas configurações do X, o semáforo mostra
 * isso **antes** de ela tentar publicar — e não depois, com o vídeo na mão.
 *
 * ⚠️ A documentação do X **não declara prazo para o token de renovação**, e por
 * isso este comando não promete nada sobre ele: o que ele faz é passar por aqui
 * todo dia e deixar o estado da conta visível.
 */
class RenovarTokensDoX extends Command
{
    protected $signature = 'x:renovar';

    protected $description = 'Mantém vivos os tokens do X das contas que não publicam há tempo';

    public function __construct(
        private readonly TokenDoX $tokens,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        /*
         * ⚠️ Só as `Ativa`. Conta já marcada como vencida não volta sozinha —
         * a autorização acabou, e insistir todo dia contra ela seria bater numa
         * porta fechada e ainda gastar o limite de uso da rede.
         */
        $contas = ContextoDoUsuario::semEscopo(fn () => ContaSocial::query()
            ->where('plataforma', Plataforma::X)
            ->where('status', StatusConta::Ativa)
            ->with('credencial')
            ->get());

        $renovados = 0;
        $perdidos = 0;

        foreach ($contas as $conta) {
            // ⭐ Mesmo caminho de quando se publica: uma regra só decide quando
            // renovar, e ela não é reescrita aqui.
            $token = $this->tokens->valido($conta);

            if ($token === null) {
                $perdidos++;

                continue;
            }

            $renovados++;
        }

        $this->info("X: {$renovados} conta(s) com acesso válido, {$perdidos} precisando reconectar.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\ContaSocial;
use App\Models\Publicacao;
use App\Models\Usuario;
use App\Services\GrupoService;
use App\Support\ContextoDoUsuario;
use Illuminate\Console\Command;

/**
 * Dá grupo a quem já usava o painel antes de o grupo existir.
 *
 * ⚠️ **É a única escrita em massa do projeto que atravessa clientes**, e por
 * isso é onde um deslize custa caro: bastaria um `UPDATE` sem filtro de dono
 * para as contas de todo mundo caírem no grupo do primeiro cliente do laço.
 *
 * ⛔ Duas regras que não se negociam aqui:
 *   1. **Itera USUÁRIOS**, nunca "linhas sem grupo" — cliente que ainda não
 *      conectou nada também precisa de grupo, e ele não aparece em consulta
 *      nenhuma de `contas_sociais`.
 *   2. **Nunca escreve dentro de `semEscopo`.** O dono entra no contexto a cada
 *      cliente, e é o próprio escopo que aplica o filtro — em vez de confiar
 *      num `where` escrito à mão que alguém pode apagar depois.
 */
class GarantirGrupoPrincipal extends Command
{
    protected $signature = 'grupos:garantir-principal {--simular : Só mostra o que faria}';

    protected $description = 'Garante que todo usuário tenha um grupo, e liga a ele os canais e publicações soltos';

    public function handle(GrupoService $grupos): int
    {
        $simular = (bool) $this->option('simular');

        // A LEITURA da lista é o único lugar que enxerga todo mundo.
        $usuarios = ContextoDoUsuario::semEscopo(fn () => Usuario::orderBy('id')->get());

        $gruposCriados = 0;
        $canais = 0;
        $publicacoes = 0;

        foreach ($usuarios as $usuario) {
            ContextoDoUsuario::definir($usuario);

            try {
                $antes = $grupos->contaDe($usuario);
                $grupo = $simular ? null : $grupos->garantirPrincipal($usuario);

                if ($antes === 0) {
                    $gruposCriados++;
                }

                // A partir daqui o escopo do dono já filtra sozinho — é ele que
                // impede este laço alcançar a conta do cliente seguinte.
                $semGrupo = ContaSocial::query()->whereNull('grupo_id');
                $canais += $simular ? $semGrupo->count() : $semGrupo->update(['grupo_id' => $grupo->id]);

                $publicacoesSoltas = Publicacao::query()->whereNull('grupo_id');
                $publicacoes += $simular
                    ? $publicacoesSoltas->count()
                    : $publicacoesSoltas->update(['grupo_id' => $grupo->id]);
            } finally {
                ContextoDoUsuario::esquecer();
            }
        }

        $this->info(($simular ? '[simulação] ' : '').
            "{$gruposCriados} grupo(s) criado(s) · {$canais} canal(is) ligado(s) · {$publicacoes} publicação(ões) ligada(s)");

        return $this->conferir($simular);
    }

    /**
     * ⭐ Sai com erro se sobrou alguém sem grupo.
     *
     * ⚠️ O deploy tem que parar **aqui**, não na migration que aperta a coluna:
     * o SQLite não tem transação de DDL, e uma migration que falha no meio
     * deixa tabela temporária órfã e nunca mais passa.
     */
    private function conferir(bool $simular): int
    {
        if ($simular) {
            return self::SUCCESS;
        }

        [$semGrupo, $canais, $publicacoes] = ContextoDoUsuario::semEscopo(fn () => [
            Usuario::query()->whereDoesntHave('grupos')->count(),
            ContaSocial::query()->whereNull('grupo_id')->count(),
            Publicacao::query()->whereNull('grupo_id')->count(),
        ]);

        if ($semGrupo === 0 && $canais === 0 && $publicacoes === 0) {
            $this->info('Está tudo com grupo.');

            return self::SUCCESS;
        }

        $this->error("Sobrou sem grupo — usuários: {$semGrupo} · canais: {$canais} · publicações: {$publicacoes}");

        return self::FAILURE;
    }
}

<?php

namespace App\Support\Conexao;

use App\Enums\Plataforma;
use App\Models\ContaSocial;
use App\Support\GrupoCorrente;
use Illuminate\Validation\ValidationException;

/**
 * ⛔ **Um canal pertence a UM grupo só** (DEC-70) — e reconectar não muda isso.
 *
 * ⚠️ Este guarda existe por um acidente silencioso e caro: conectar o mesmo canal
 * estando dentro de outro grupo respondia **"conectado com sucesso"** e não
 * mostrava nada, porque o registro continuava vivo no grupo antigo. A pessoa
 * autorizava no site da rede, voltava, lia que deu certo — e o grupo continuava
 * vazio. Nada avisava, e o banco estava certo.
 *
 * O mecanismo é a UNIQUE `(usuario_id, plataforma, identificador_externo)`, que
 * **não inclui o grupo de propósito**: é ela que impede o mesmo canal de existir
 * em dois grupos. O `updateOrCreate` da conexão a encontrava, atualizava nome e
 * situação, e deixava o `grupo_id` como estava.
 *
 * ⛔ **A correção não é mover o canal por conta própria.** Mover leva o canal
 * para longe do grupo onde o histórico dele nasceu, e isso é ação explícita, com
 * janela própria e aviso do que fica para trás (DEC-77). Fazer isso escondido,
 * durante um "conectar", é exatamente o acidente que o grupo existe para evitar.
 */
class CanalDeUmGrupoSo
{
    /**
     * Recusa quando o canal já vive em OUTRO grupo deste mesmo dono.
     *
     * ⚠️ Reconectar no MESMO grupo continua passando: é o caminho normal de
     * renovar autorização vencida, e travar isso quebraria o conserto do
     * semáforo (DEC-32).
     *
     * @param  string  $campo  onde a mensagem aparece no formulário
     *
     * @throws ValidationException
     */
    public static function garantir(Plataforma $rede, string $identificadorExterno, string $campo): void
    {
        $existente = ContaSocial::query()
            ->where('plataforma', $rede)
            ->where('identificador_externo', $identificadorExterno)
            // ⚠️ O grupo vem com `withTrashed`: canal preso num grupo excluído
            // precisa de uma frase que explique, não de um nome em branco.
            ->with(['grupo' => fn ($consulta) => $consulta->withTrashed()])
            ->first();

        if (! $existente || $existente->grupo_id === GrupoCorrente::id()) {
            return;
        }

        $onde = $existente->grupo?->nome;

        throw ValidationException::withMessages([
            $campo => $onde
                ? "«{$existente->nome_exibicao}» já está conectado no grupo «{$onde}». ".
                    'Um canal publica por um grupo só — para trazê-lo para cá, use "Levar para outro grupo" na janela da rede.'
                : "«{$existente->nome_exibicao}» já está conectado em outro grupo. ".
                    'Um canal publica por um grupo só.',
        ]);
    }
}

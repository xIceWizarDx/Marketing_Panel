<?php

namespace App\Models;

use App\Enums\StatusConta;
use App\Models\Concerns\PertenceAoUsuario;
use Database\Factories\GrupoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A rede de canais de uma linha de conteúdo (DEC-69).
 *
 * ⭐ **O grupo É seus canais.** Não é pasta vazia que depois se enche: sem
 * canal, ele não tem o que ser. É por isso que só dá para excluir grupo sem
 * rede conectada, e que a tela de grupo vazio precisa dizer outra coisa que não
 * "você ainda não conectou nada".
 *
 * ⛔ **Grupo NÃO tem Global Scope de grupo** (DEC-74). Ele usa
 * `PertenceAoUsuario` — isso é o escopo de **dono**, que é segurança. Filtrar
 * por grupo é sempre explícito, na consulta da tela: job, comando e conciliação
 * não têm grupo corrente, e um scope que lançasse aí derrubaria o motor.
 *
 * ⚠️ **Nunca chamar `Grupo::withoutGlobalScopes()`.** Aqui há DOIS scopes — o de
 * dono e o de excluído — e esse método derruba os dois de uma vez, o que
 * significa "enxergo grupo de qualquer cliente E grupo já excluído". Onde
 * precisar furar só o de dono, usar
 * `withoutGlobalScope(EscopoDoUsuario::class)` com o `where('usuario_id', ...)`
 * escrito na mão.
 *
 * ⛔ **O `SoftDeletes` é assunto do banco, não da tela.** A linha sobrevive para
 * auditoria; a interface diz "excluir" e não promete volta. Prometer recuperação
 * criaria uma expectativa que tela nenhuma cumpre.
 */
class Grupo extends Model
{
    /** @use HasFactory<GrupoFactory> */
    use HasFactory, HasUlids, PertenceAoUsuario, SoftDeletes;

    protected $table = 'grupos';

    /** ⛔ `usuario_id` fora: quem carimba é a trait, nunca o formulário. */
    protected $fillable = ['nome'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /** O id sequencial nunca sai do servidor. */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /** @return HasMany<ContaSocial, $this> */
    public function contasSociais(): HasMany
    {
        return $this->hasMany(ContaSocial::class);
    }

    /** @return HasMany<Publicacao, $this> */
    public function publicacoes(): HasMany
    {
        return $this->hasMany(Publicacao::class);
    }

    /**
     * Ainda há rede conectada aqui dentro?
     *
     * ⚠️ Conta **desconectada não conta**. A linha dela sobrevive porque o
     * histórico aponta para ela, mas quem desconectou já não a vê em lugar
     * nenhum — nem na grade de redes. Segurar a exclusão por causa dela
     * deixaria a pessoa presa num grupo que, para ela, está vazio.
     */
    public function temRedeConectada(): bool
    {
        return $this->contasSociais()
            ->where('status', '!=', StatusConta::Desconectada)
            ->exists();
    }
}

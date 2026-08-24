<?php

namespace App\Services;

use App\Models\ContaSocial;
use App\Models\Grupo;
use App\Models\Scopes\EscopoDoUsuario;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Tudo que cria, muda e exclui grupo — **e o escritor único de
 * `contas_sociais.grupo_id`**.
 *
 * ⚠️ Escritor único porque mover um canal de grupo é a operação que mais
 * facilmente deixa o banco incoerente: basta alguém escrever a coluna direto
 * num controller para uma conta acabar num grupo de outro dono.
 */
class GrupoService
{
    /** O nome do grupo que nasce sozinho — nunca aparece como escolha. */
    private const NOME_INICIAL = 'Principal';

    /**
     * O grupo que este cliente usa quando ainda não escolheu nenhum.
     *
     * ⚠️ Não usa `firstOrCreate` com o nome na chave de busca: quem renomeou
     * "Principal" para "Notícias" ganharia um segundo grupo do nada.
     *
     * ⛔ **Nunca `Grupo::withoutGlobalScopes()`.** Ele derruba o escopo de dono
     * **e** o de arquivado de uma vez — e aí um grupo que a pessoa arquivou
     * voltaria a ser eleito o principal dela.
     */
    public function garantirPrincipal(Usuario $usuario): Grupo
    {
        $existente = Grupo::withoutGlobalScope(EscopoDoUsuario::class)
            ->where('usuario_id', $usuario->id)
            ->oldest('id')
            ->first();

        if ($existente) {
            return $existente;
        }

        /*
         * ⚠️ Dono atribuído na mão, fora do `$fillable`.
         *
         * O carimbo automático da trait olha o contexto da requisição, e este
         * método também roda no comando de migração e no cadastro — onde ainda
         * não há sessão nenhuma. E `usuario_id` continua fora do `$fillable` de
         * propósito: dono nunca deve poder chegar por formulário.
         */
        $grupo = new Grupo(['nome' => self::NOME_INICIAL]);
        $grupo->usuario_id = $usuario->id;
        $grupo->save();

        return $grupo;
    }

    /** Quantos grupos este dono já tem — usado pelo comando de migração. */
    public function contaDe(Usuario $usuario): int
    {
        return Grupo::withoutGlobalScope(EscopoDoUsuario::class)
            ->where('usuario_id', $usuario->id)
            ->count();
    }

    public function criar(string $nome): Grupo
    {
        return Grupo::create(['nome' => $nome]);
    }

    public function renomear(Grupo $grupo, string $nome): Grupo
    {
        $grupo->forceFill(['nome' => $nome])->save();

        return $grupo;
    }

    /**
     * ⭐ As hashtags que este grupo já traz escritas ao compor (DEC-152).
     *
     * ⛔ **Não alcança o que já foi publicado.** Publicação guarda o texto que
     * de fato subiu, e mexer nele aqui reescreveria história — o produto todo
     * existe para provar o que aconteceu, não para ajustar depois.
     *
     * @param  list<string>|null  $hashtags
     */
    public function definirHashtags(Grupo $grupo, ?array $hashtags): Grupo
    {
        $grupo->forceFill(['hashtags' => $hashtags])->save();

        return $grupo;
    }

    /**
     * ⛔ Só exclui grupo **sem rede conectada**, e nunca o último (DEC-76).
     *
     * ⚠️ Por baixo é *soft delete*, e isso é **assunto do banco** — serve para
     * auditoria, não para a pessoa. A tela diz "excluir" e não promete volta:
     * prometer recuperação criaria uma expectativa que nenhuma tela cumpre.
     *
     * @throws ValidationException
     */
    public function excluir(Grupo $grupo): void
    {
        if ($grupo->temRedeConectada()) {
            throw ValidationException::withMessages([
                'grupo' => 'Este grupo ainda tem redes conectadas. Desconecte ou mova as redes antes de excluí-lo.',
            ]);
        }

        if (Grupo::count() <= 1) {
            throw ValidationException::withMessages([
                'grupo' => 'Você precisa de pelo menos um grupo — é onde suas redes ficam.',
            ]);
        }

        $grupo->delete();
    }

    /**
     * Move um canal de grupo (DEC-77).
     *
     * ⭐ **O histórico NÃO vai junto.** As publicações que já saíram continuam
     * apontando para o grupo de onde saíram — é o que impede o número de um
     * grupo mudar sozinho quando alguém reorganiza os canais (DEC-75).
     */
    public function moverCanal(ContaSocial $conta, Grupo $destino): void
    {
        DB::transaction(function () use ($conta, $destino) {
            $conta->forceFill(['grupo_id' => $destino->id])->save();
        });
    }
}

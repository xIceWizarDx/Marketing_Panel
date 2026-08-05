<?php

namespace App\Support;

use App\Models\Grupo;
use App\Services\GrupoService;

/**
 * Em qual grupo a pessoa está olhando agora — **e só isso**.
 *
 * ⚠️ **Não confundir com `ContextoDoUsuario`.** Aquele é segurança: sem dono
 * definido ele **lança**, e é o que impede um cliente alcançar dado do outro.
 * Este aqui é preferência de visualização: ele **nunca lança** e **nunca decide
 * o que pode ser publicado** (DEC-74).
 *
 * A verdade sobre o grupo de uma publicação vem das **contas escolhidas**, não
 * daqui (DEC-73). Se este objeto estiver "errado" — aba velha, sessão de ontem —
 * o pior que acontece é a pessoa ver a lista de outro grupo; publicar no grupo
 * errado continua impossível.
 *
 * ⛔ Vive na SESSÃO e nunca na URL (DEC-72): é preferência, não recorte
 * compartilhável.
 */
class GrupoCorrente
{
    private const CHAVE = 'grupo.corrente';

    /** Memória por requisição — a mesma pergunta é feita várias vezes por tela. */
    private static ?Grupo $resolvido = null;

    /**
     * O grupo em foco, resolvido e consertado se preciso.
     *
     * ⭐ **Nunca devolve `null` para quem está autenticado.** Cliente sem grupo é
     * um beco sem saída silencioso: as telas não dão erro, elas mentem — e
     * conectar a primeira rede estouraria depois do consentimento do Google,
     * quando não há mais como voltar atrás.
     */
    public static function grupo(): ?Grupo
    {
        if (self::$resolvido !== null) {
            return self::$resolvido;
        }

        $usuario = auth()->user();

        if (! $usuario) {
            return null;
        }

        // O escopo de dono já filtra: ULID de outro cliente simplesmente não
        // é encontrado, e cai no primeiro grupo do dono de verdade.
        $ulid = session(self::CHAVE);

        $grupo = $ulid ? Grupo::where('ulid', $ulid)->first() : null;

        // Sessão apontando para grupo excluído ou alheio: reescreve em vez de
        // deixar a tela vazia sem explicação.
        $grupo ??= Grupo::oldest('id')->first() ?? app(GrupoService::class)->garantirPrincipal($usuario);

        if (session(self::CHAVE) !== $grupo->ulid) {
            session([self::CHAVE => $grupo->ulid]);
        }

        return self::$resolvido = $grupo;
    }

    /** O id que os filtros de tela usam, ou `null` para visitante. */
    public static function id(): ?int
    {
        return self::grupo()?->id;
    }

    /** Troca o foco. Escritor único desta chave de sessão. */
    public static function definir(Grupo $grupo): void
    {
        session([self::CHAVE => $grupo->ulid]);
        self::$resolvido = $grupo;
    }

    /**
     * Esquece o foco.
     *
     * ⚠️ Chamado ao entrar e sair da impersonação: `session()->regenerate()`
     * troca o id do cookie mas **preserva o conteúdo**, então sem isto o admin
     * herdaria o grupo do cliente que estava atendendo — e vice-versa.
     */
    public static function esquecer(): void
    {
        session()->forget(self::CHAVE);
        self::$resolvido = null;
    }

    /** Só para os testes: zera a memória entre um caso e outro. */
    public static function limpar(): void
    {
        self::$resolvido = null;
    }
}

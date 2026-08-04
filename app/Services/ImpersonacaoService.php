<?php

namespace App\Services;

use App\Models\LogImpersonacao;
use App\Models\Usuario;
use App\Support\RegistroDeSeguranca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Impersonacao (DEC-04): o administrador entra na conta do cliente para dar
 * suporte, vendo exatamente a mesma tela que ele.
 *
 * ESCRITOR UNICO: so esta classe mexe nas chaves de sessao da impersonacao.
 * Nenhum controller, middleware ou view faz isso por conta propria — foi o
 * espalhamento desse tipo de estado que gerou brechas em projeto anterior.
 */
class ImpersonacaoService
{
    /** Id do admin de verdade, guardado enquanto a impersonacao dura. */
    public const CHAVE_ADMIN = 'impersonacao.admin_id';

    /** Id do registro em logs_impersonacao, pra saber qual linha fechar na saida. */
    public const CHAVE_LOG = 'impersonacao.log_id';

    public function ativa(Request $request): bool
    {
        return $request->session()->has(self::CHAVE_ADMIN);
    }

    /** O admin responsavel pela impersonacao — null quando ninguem esta impersonando. */
    public function administrador(Request $request): ?Usuario
    {
        $id = $request->session()->get(self::CHAVE_ADMIN);

        return $id ? Usuario::find($id) : null;
    }

    /**
     * @throws ValidationException
     */
    public function iniciar(Request $request, Usuario $alvo): void
    {
        $admin = $request->user();

        if (! $admin?->ehAdmin()) {
            abort(404);
        }

        // So se impersona cliente. Entrar na conta de outro operador (admin,
        // e amanha comercial ou suporte) daria a quem entrou os poderes dele —
        // inclusive impersonar um terceiro.
        if ($alvo->papel->ehOperador()) {
            throw ValidationException::withMessages([
                'usuario' => 'Só é possível impersonar a conta de um cliente.',
            ]);
        }

        if ($alvo->is($admin)) {
            throw ValidationException::withMessages([
                'usuario' => 'Você já está na sua própria conta.',
            ]);
        }

        // Impersonacao aninhada guardaria o admin errado na saida e deixaria a
        // sessao presa na conta do cliente.
        if ($this->ativa($request)) {
            throw ValidationException::withMessages([
                'usuario' => 'Saia da impersonação atual antes de iniciar outra.',
            ]);
        }

        $log = LogImpersonacao::create([
            'admin_id' => $admin->id,
            'usuario_id' => $alvo->id,
            // Copiados agora de proposito: se a conta for apagada depois, o
            // registro do acesso continua rastreavel sem guardar dado pessoal.
            'admin_ulid' => $admin->ulid,
            'usuario_ulid' => $alvo->ulid,
            'iniciada_em' => now(),
            'ip' => $request->ip(),
            'agente_usuario' => $request->userAgent(),
        ]);

        // Grava o log ANTES de trocar de usuario: se algo falhar no meio, sobra
        // um registro aberto (visivel na auditoria) em vez de um acesso sem rastro.
        Auth::login($alvo);
        $request->session()->regenerate();

        $request->session()->put(self::CHAVE_ADMIN, $admin->id);
        $request->session()->put(self::CHAVE_LOG, $log->id);

        RegistroDeSeguranca::registrar('impersonacao_iniciada', [
            'alvo_ulid' => $alvo->ulid,
        ], $request);
    }

    /** Devolve o admin para a propria conta. */
    public function encerrar(Request $request): ?Usuario
    {
        $admin = $this->administrador($request);
        $logId = $request->session()->get(self::CHAVE_LOG);

        if ($logId) {
            LogImpersonacao::whereKey($logId)->whereNull('finalizada_em')->update(['finalizada_em' => now()]);
        }

        $request->session()->forget([self::CHAVE_ADMIN, self::CHAVE_LOG]);

        if (! $admin) {
            // Sessao inconsistente (log sem admin): derruba tudo em vez de
            // deixar alguem preso na conta de outro.
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return null;
        }

        Auth::login($admin);
        $request->session()->regenerate();

        RegistroDeSeguranca::registrar('impersonacao_encerrada', [
            'log_id' => $logId,
        ], $request);

        return $admin;
    }
}

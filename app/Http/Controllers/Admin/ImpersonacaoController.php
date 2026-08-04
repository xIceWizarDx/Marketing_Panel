<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\ImpersonacaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImpersonacaoController extends Controller
{
    public function __construct(private readonly ImpersonacaoService $impersonacao) {}

    /** Entra na conta do cliente. */
    public function iniciar(Request $request, string $ulid): RedirectResponse
    {
        // Resolvido a mao pelo ULID (nao por route-binding) porque a rota expoe
        // o ULID, nunca o id sequencial.
        $alvo = Usuario::where('ulid', $ulid)->firstOrFail();

        $this->impersonacao->iniciar($request, $alvo);

        return redirect()
            ->route($alvo->papel->rotaInicial())
            ->with('aviso', "Você está vendo o painel de {$alvo->nome}.");
    }

    /** Volta para a conta do administrador. */
    public function encerrar(Request $request): RedirectResponse
    {
        if (! $this->impersonacao->ativa($request)) {
            return redirect()->route('entrar');
        }

        $admin = $this->impersonacao->encerrar($request);

        if (! $admin) {
            return redirect()->route('entrar');
        }

        return redirect()
            ->route($admin->papel->rotaInicial())
            ->with('sucesso', 'Você voltou para a sua conta.');
    }
}

<?php

namespace App\Http\Controllers\Acesso;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pede a senha de novo antes de uma acao sensivel (impersonar, desconectar rede,
 * apagar a conta) — mesmo com a sessao ja aberta.
 */
class ConfirmarSenhaController extends Controller
{
    public function mostrar(): Response
    {
        return Inertia::render('acesso/confirmar-senha');
    }

    public function salvar(Request $request): RedirectResponse
    {
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route($request->user()->papel->rotaInicial()));
    }
}

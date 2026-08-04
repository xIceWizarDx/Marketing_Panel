<?php

namespace App\Http\Controllers\Acesso;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Reenvia o email de confirmacao (limitado a 6 por minuto na rota). */
class ReenviarVerificacaoEmailController extends Controller
{
    public function salvar(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route($request->user()->papel->rotaInicial()));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('aviso', 'link-de-verificacao-enviado');
    }
}

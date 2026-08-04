<?php

namespace App\Http\Controllers\Acesso;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Tela "confirme seu email" pra quem entrou mas ainda nao confirmou. */
class AvisoVerificacaoEmailController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->intended(route($request->user()->papel->rotaInicial()))
            : Inertia::render('acesso/verificar-email', [
                'aviso' => $request->session()->get('aviso'),
            ]);
    }
}

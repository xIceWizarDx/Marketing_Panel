<?php

namespace App\Http\Controllers\Acesso;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class EsqueciSenhaController extends Controller
{
    /** Tela que pede o email pra enviar o link de redefinicao. */
    public function criar(Request $request): Response
    {
        return Inertia::render('acesso/esqueci-a-senha', [
            'aviso' => $request->session()->get('aviso'),
        ]);
    }

    /** Dispara o email com o link. */
    public function salvar(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        Password::sendResetLink($request->only('email'));

        // Resposta sempre igual, exista ou nao a conta: senao vira consulta de
        // "esse email esta cadastrado aqui?" pra qualquer um.
        return back()->with('aviso', 'Se existir uma conta com esse email, o link de redefinicao foi enviado.');
    }
}

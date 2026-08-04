<?php

namespace App\Http\Controllers\Acesso;

use App\Http\Controllers\Controller;
use App\Http\Requests\Acesso\EntrarRequest;
use App\Support\RegistroDeSeguranca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class SessaoController extends Controller
{
    /** Tela de entrada. */
    public function criar(Request $request): Response
    {
        return Inertia::render('acesso/entrar', [
            'podeRedefinirSenha' => Route::has('senha.solicitar'),
            'aviso' => $request->session()->get('aviso'),
        ]);
    }

    /** Confere as credenciais e abre a sessao. */
    public function salvar(EntrarRequest $request): RedirectResponse
    {
        $request->autenticar();

        $request->session()->regenerate();

        RegistroDeSeguranca::registrar('entrou', request: $request);

        // O papel decide onde a pessoa cai — fonte unica do redirecionamento (enum Papel).
        return redirect()->intended(route($request->user()->papel->rotaInicial()));
    }

    /** Encerra a sessao. */
    public function remover(Request $request): RedirectResponse
    {
        RegistroDeSeguranca::registrar('saiu', request: $request);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

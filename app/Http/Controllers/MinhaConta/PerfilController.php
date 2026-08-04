<?php

namespace App\Http\Controllers\MinhaConta;

use App\Http\Controllers\Controller;
use App\Http\Requests\MinhaConta\AtualizarPerfilRequest;
use App\Support\RegistroDeSeguranca;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PerfilController extends Controller
{
    public function editar(Request $request): Response
    {
        return Inertia::render('minha-conta/perfil', [
            'precisaVerificarEmail' => $request->user() instanceof MustVerifyEmail,
            'aviso' => $request->session()->get('aviso'),
        ]);
    }

    public function atualizar(AtualizarPerfilRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        // Trocou de email? Volta a valer como nao confirmado — senao daria pra
        // apontar a conta pra um email que a pessoa nao controla.
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('minha-conta.perfil.editar');
    }

    public function remover(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $usuario = $request->user();

        RegistroDeSeguranca::registrar('apagou_a_propria_conta', request: $request);

        Auth::logout();

        $usuario->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

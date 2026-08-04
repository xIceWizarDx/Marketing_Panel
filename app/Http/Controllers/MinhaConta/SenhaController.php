<?php

namespace App\Http\Controllers\MinhaConta;

use App\Http\Controllers\Controller;
use App\Support\RegistroDeSeguranca;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SenhaController extends Controller
{
    public function editar(Request $request): Response
    {
        return Inertia::render('minha-conta/senha', [
            'precisaVerificarEmail' => $request->user() instanceof MustVerifyEmail,
            'aviso' => $request->session()->get('aviso'),
        ]);
    }

    public function atualizar(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            // `current_password` e a regra nativa do Laravel: exige a senha atual
            // antes de trocar, pra sessao roubada nao virar conta roubada.
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($dados['password']),
            // Token novo derruba o "continuar conectado" nos OUTROS aparelhos.
            // Sem isto, trocar a senha não adiantaria no caso que mais importa:
            // notebook perdido continua entrando pelo cookie de lembrar.
            'remember_token' => Str::random(60),
        ])->save();

        // A sessão atual precisa do cookie novo, senão quem trocou a senha
        // também seria desconectado no próximo acesso.
        Auth::guard('web')->login($request->user(), remember: true);

        RegistroDeSeguranca::registrar('trocou_a_senha', request: $request);

        return back()->with('sucesso', 'Senha alterada. Os outros aparelhos foram desconectados.');
    }
}

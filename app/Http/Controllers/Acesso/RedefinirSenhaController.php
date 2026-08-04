<?php

namespace App\Http\Controllers\Acesso;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RedefinirSenhaController extends Controller
{
    /** Tela de nova senha (chegou pelo link do email). */
    public function criar(Request $request): Response
    {
        return Inertia::render('acesso/redefinir-senha', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Grava a nova senha.
     *
     * @throws ValidationException
     */
    public function salvar(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $resultado = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($usuario) use ($request) {
                $usuario->forceFill([
                    'password' => Hash::make($request->password),
                    // Token novo derruba o "lembrar de mim" em outros aparelhos:
                    // se a senha vazou, quem estava logado perde o acesso.
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($usuario));
            }
        );

        if ($resultado == Password::PasswordReset) {
            return to_route('entrar')->with('aviso', __($resultado));
        }

        throw ValidationException::withMessages([
            'email' => [__($resultado)],
        ]);
    }
}

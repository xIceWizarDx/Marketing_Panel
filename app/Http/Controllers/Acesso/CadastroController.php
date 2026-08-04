<?php

namespace App\Http\Controllers\Acesso;

use App\Enums\Papel;
use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CadastroController extends Controller
{
    /** Tela de criacao de conta. */
    public function criar(): Response
    {
        return Inertia::render('acesso/cadastrar');
    }

    /**
     * Cria a conta.
     *
     * @throws ValidationException
     */
    public function salvar(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:usuarios,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Quem se cadastra sozinho e sempre cliente. Admin so nasce por seeder ou
        // promocao feita por outro admin — nunca por dado vindo do formulario.
        $usuario = Usuario::create([
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'password' => Hash::make($dados['password']),
            'papel' => Papel::Cliente,
        ]);

        event(new Registered($usuario));

        Auth::login($usuario);

        return to_route($usuario->papel->rotaInicial());
    }
}

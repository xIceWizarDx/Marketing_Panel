<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Papel;
use App\Http\Controllers\Controller;
use App\Models\Midia;
use App\Models\Usuario;
use App\Support\ContextoDoUsuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClienteController extends Controller
{
    public function listar(Request $request): Response
    {
        $busca = trim((string) $request->query('busca'));

        $clientes = Usuario::query()
            ->where('papel', Papel::Cliente)
            ->when($busca !== '', fn ($consulta) => $consulta->where(
                fn ($q) => $q->where('nome', 'like', "%{$busca}%")->orWhere('email', 'like', "%{$busca}%")
            ))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through($this->paraTela(...));

        return Inertia::render('admin/clientes', [
            'clientes' => $clientes,
            'busca' => $busca,
        ]);
    }

    public function alternarAtivo(Request $request, string $ulid): RedirectResponse
    {
        $cliente = Usuario::where('ulid', $ulid)->where('papel', Papel::Cliente)->firstOrFail();

        $cliente->update(['ativo' => ! $cliente->ativo]);

        return back()->with(
            'sucesso',
            $cliente->ativo
                ? "{$cliente->nome} voltou a ter acesso."
                : "{$cliente->nome} não consegue mais entrar."
        );
    }

    private function paraTela(Usuario $cliente): array
    {
        return [
            'ulid' => $cliente->ulid,
            'nome' => $cliente->nome,
            'email' => $cliente->email,
            'ativo' => $cliente->ativo,
            'emailVerificado' => $cliente->hasVerifiedEmail(),
            'criadoEm' => $cliente->created_at?->toIso8601String(),
            // Conta as mídias DESTE cliente: sem `semEscopo` a consulta usaria o
            // admin como dono e voltaria vazia.
            'midias' => ContextoDoUsuario::semEscopo(
                fn () => Midia::where('usuario_id', $cliente->id)->count()
            ),
        ];
    }
}

<?php

namespace App\Http\Middleware;

use App\Enums\Papel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deixa passar so quem tem o papel exigido pela rota.
 *
 * Uso: ->middleware('papel:admin')
 *
 * Regra de ouro: a autorizacao vive AQUI e na Policy — nunca num `if` espalhado
 * pela view. Se a checagem some do controller, o middleware ainda barra.
 */
class VerificarPapel
{
    public function handle(Request $request, Closure $next, string ...$papeisAceitos): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            abort(403);
        }

        // Impersonando: quem manda e o papel de QUEM ESTA SENDO VISTO, nao o do
        // admin — senao o admin impersonando um cliente ainda entraria em /admin.
        $papel = $usuario->papel;

        foreach ($papeisAceitos as $aceito) {
            if ($papel === Papel::from($aceito)) {
                return $next($request);
            }
        }

        // 404 e nao 403: quem nao pode entrar nem descobre que a area existe.
        abort(404);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabecalhos de seguranca em toda resposta.
 *
 * Sao instrucoes ao navegador para fechar ataques que o codigo do servidor nao
 * consegue impedir sozinho. As plataformas conferem isto na auditoria do app
 * (Meta, TikTok, YouTube) — e ligar depois exige testar o site inteiro de novo,
 * porque um cabecalho apertado quebra recurso que ja estava no ar.
 */
class CabecalhosDeSeguranca
{
    public function handle(Request $request, Closure $next): Response
    {
        $resposta = $next($request);

        // Impede o navegador de "adivinhar" o tipo do arquivo: um .txt enviado
        // por alguem nao pode ser interpretado como script.
        $resposta->headers->set('X-Content-Type-Options', 'nosniff');

        // Nosso site nao pode ser embutido em iframe de terceiro — fecha
        // clickjacking (botao invisivel por cima do nosso).
        $resposta->headers->set('X-Frame-Options', 'DENY');

        // O link que sai daqui nao entrega o caminho completo da pagina de
        // origem — o ULID da rota nao vaza para o site de destino.
        $resposta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Nenhuma tela nossa usa camera, microfone ou localizacao.
        $resposta->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // HSTS: depois da primeira visita, o navegador so aceita HTTPS neste
        // dominio. So em producao — em `localhost` travaria o desenvolvimento.
        if (app()->isProduction()) {
            $resposta->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $resposta;
    }
}

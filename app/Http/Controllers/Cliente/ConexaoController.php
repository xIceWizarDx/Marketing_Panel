<?php

namespace App\Http\Controllers\Cliente;

use App\Enums\Plataforma;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\ConectarBlueskyRequest;
use App\Models\ContaSocial;
use App\Services\ConexaoComYoutube;
use App\Services\ContaSocialService;
use App\Services\Meta\ConexaoComMeta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Conectar e desconectar rede — **só ações**.
 *
 * ⛔ Não há tela de conexões (DEC-63). O estado das redes mora na Visão geral,
 * que é a primeira tela que a pessoa abre: "como está tudo?" é a pergunta da
 * porta de entrada, não de uma página separada. Por isso todo retorno daqui
 * volta para `painel`, inclusive os de OAuth — é onde a resposta aparece.
 */
class ConexaoController extends Controller
{
    public function __construct(
        private readonly ContaSocialService $contas,
    ) {}

    public function conectarBluesky(ConectarBlueskyRequest $request): RedirectResponse
    {
        $conta = $this->contas->conectarBluesky(
            $request->string('identificador')->toString(),
            $request->string('senha_de_aplicativo')->toString(),
        );

        return to_route('painel')->with('sucesso', "{$conta->nome_exibicao} está conectada.");
    }

    /** Manda a pessoa autorizar no Google. */
    /**
     * ⭐ Uma conexão, duas redes.
     *
     * A conta do Instagram fica pendurada numa Página do Facebook, e o login é o
     * mesmo. Pedir duas autorizações seria pedir duas vezes a mesma coisa — e
     * deixar as duas metades saírem de sincronia.
     */
    public function iniciarMeta(Request $request, ConexaoComMeta $meta): RedirectResponse
    {
        if (! config('services.meta.client_id')) {
            return back()->with('erro', 'A conexão com o Facebook ainda não está configurada neste servidor.');
        }

        $estado = Str::random(40);
        $request->session()->put('meta.estado', $estado);

        return redirect()->away($meta->enderecoDeAutorizacao($estado));
    }

    public function retornoMeta(Request $request, ConexaoComMeta $meta): RedirectResponse
    {
        $esperado = $request->session()->pull('meta.estado');

        if (! $esperado || ! hash_equals($esperado, (string) $request->query('state'))) {
            return to_route('painel')->with('erro', 'A autorização não pôde ser confirmada. Tente conectar de novo.');
        }

        if ($request->query('error')) {
            return to_route('painel')->with('aviso', 'Você cancelou a conexão com o Facebook.');
        }

        // Mesma lição do YouTube (R-6): o retorno é um GET vindo de fora, e sem
        // este `try` as mensagens em português seriam escritas e jogadas fora.
        try {
            $contas = $meta->conectar((string) $request->query('code'));
        } catch (ValidationException $e) {
            return to_route('painel')->with('erro', $e->validator->errors()->first('meta'));
        }

        $paginas = collect($contas)->where('plataforma', Plataforma::Facebook)->count();
        $instagram = collect($contas)->where('plataforma', Plataforma::Instagram)->count();

        return to_route('painel')->with('sucesso', $this->resumoDaConexao($paginas, $instagram));
    }

    /** Contar em vez de dizer "conectado": a pessoa precisa saber o que entrou. */
    private function resumoDaConexao(int $paginas, int $instagram): string
    {
        $texto = $paginas === 1 ? '1 Página do Facebook conectada' : "{$paginas} Páginas do Facebook conectadas";

        return match (true) {
            $instagram === 0 => $texto.'. Nenhuma conta do Instagram estava vinculada a elas.',
            $instagram === 1 => $texto.', e 1 conta do Instagram vinculada.',
            default => $texto.", e {$instagram} contas do Instagram vinculadas.",
        };
    }

    public function iniciarYoutube(Request $request, ConexaoComYoutube $youtube): RedirectResponse
    {
        if (! config('services.google.client_id')) {
            return back()->with('erro', 'A conexão com o YouTube ainda não está configurada neste servidor.');
        }

        // `state` assinado na sessão: sem ele, alguém poderia forjar o retorno
        // do Google e conectar um canal na conta de outra pessoa (CSRF).
        $estado = Str::random(40);
        $request->session()->put('youtube.estado', $estado);

        return redirect()->away($youtube->enderecoDeAutorizacao($estado));
    }

    /** O Google devolve a pessoa aqui, com o código. */
    public function retornoYoutube(Request $request, ConexaoComYoutube $youtube): RedirectResponse
    {
        $esperado = $request->session()->pull('youtube.estado');

        if (! $esperado || ! hash_equals($esperado, (string) $request->query('state'))) {
            return to_route('painel')->with('erro', 'A autorização não pôde ser confirmada. Tente conectar de novo.');
        }

        if ($request->query('error')) {
            // A pessoa clicou em "cancelar" na tela do Google — não é erro.
            return to_route('painel')->with('aviso', 'Você cancelou a conexão com o YouTube.');
        }

        // ⚠️ Sem este `try`, as mensagens do serviço não chegavam à tela: o
        // retorno do Google é um GET vindo de FORA, então a página anterior é o
        // site do Google. O redirecionamento automático de erro de validação
        // mandaria a pessoa de volta para lá — ela veria a tela de conexões
        // sem explicação nenhuma do que houve.
        try {
            $conta = $youtube->conectar((string) $request->query('code'));
        } catch (ValidationException $e) {
            return to_route('painel')->with('erro', $e->validator->errors()->first('youtube'));
        }

        return to_route('painel')->with(
            'sucesso',
            "O canal {$conta->nome_exibicao} está conectado. Enquanto a auditoria do YouTube não sair, os vídeos sobem como privados."
        );
    }

    public function desconectar(string $ulid): RedirectResponse
    {
        $conta = ContaSocial::where('ulid', $ulid)->firstOrFail();

        // O nome vem do serviço: depois de desconectar, o dado do titular já
        // foi apagado da linha (exigência da política).
        $nome = $this->contas->desconectar($conta);

        return to_route('painel')
            ->with('sucesso', "{$nome} foi desconectada. O histórico do que já foi publicado continua aqui.");
    }
}

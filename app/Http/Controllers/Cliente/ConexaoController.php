<?php

namespace App\Http\Controllers\Cliente;

use App\Enums\Plataforma;
use App\Enums\StatusDestino;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\ConectarBlueskyRequest;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Publicadores\RegistroDePublicadores;
use App\Services\ConexaoComYoutube;
use App\Services\ContaSocialService;
use App\Services\Meta\ConexaoComMeta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ConexaoController extends Controller
{
    public function __construct(
        private readonly ContaSocialService $contas,
        private readonly RegistroDePublicadores $publicadores,
    ) {}

    public function listar(): Response
    {
        $contas = ContaSocial::query()
            ->with('credencial:id,conta_social_id,expira_em')
            ->latest()
            ->get()
            ->groupBy(fn (ContaSocial $c) => $c->plataforma->value);

        // Uma carta por REDE, não uma lista solta de contas: assim dá pra ver
        // num relance o que está ligado, o que falta ligar e o que ainda não
        // existe — em vez de a pessoa ter que deduzir pela ausência.
        $numeros = $this->numerosPorRede();

        $redes = array_map(fn (Plataforma $plataforma) => [
            'valor' => $plataforma->value,
            'rotulo' => $plataforma->rotulo(),
            // `podeConectar`, não `disponivel`: o publicador do YouTube existe,
            // mas sem a credencial do Google Cloud o botão levaria a um erro.
            // Botão que falha é pior que botão ausente.
            'disponivel' => $this->publicadores->podeConectar($plataforma),
            // Publicador escrito, faltando só a configuração do servidor — a
            // tela diz isso em vez de fingir que a rede não existe.
            'faltaConfigurar' => $this->publicadores->disponivel($plataforma)
                && ! $this->publicadores->podeConectar($plataforma),
            // "Aguardando aprovação" e "em estudo" são coisas diferentes:
            // uma tem caminho definido, a outra é ideia. Dizer o mesmo das duas
            // seria prometer o que ninguém decidiu.
            'situacao' => $plataforma->situacao()->value,
            'situacaoRotulo' => $plataforma->situacao()->rotulo(),
            // ⭐ O número que importa é o de posts CONFIRMADOS na rede — não o
            // de envios feitos. Contar envio seria contar promessa (DEC-31).
            'publicados' => $numeros[$plataforma->value]['publicados'] ?? 0,
            // A contrapartida honesta: o que a rede recusou aparece do lado.
            'falhas' => $numeros[$plataforma->value]['falhas'] ?? 0,
            // Nem sucesso nem falha — ainda em curso, e dizer isso evita a
            // pergunta "cadê meu vídeo?".
            'andando' => $numeros[$plataforma->value]['andando'] ?? 0,
            'contas' => $contas->get($plataforma->value, collect())->map($this->paraTela(...))->values(),
        ], Plataforma::cases());

        return Inertia::render('cliente/conexoes', [
            'redes' => $redes,
            'totalConectado' => $contas->flatten()->filter->podePublicar()->count(),
        ]);
    }

    public function conectarBluesky(ConectarBlueskyRequest $request): RedirectResponse
    {
        $conta = $this->contas->conectarBluesky(
            $request->string('identificador')->toString(),
            $request->string('senha_de_aplicativo')->toString(),
        );

        return to_route('conexoes')->with('sucesso', "{$conta->nome_exibicao} está conectada.");
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
            return to_route('conexoes')->with('erro', 'A autorização não pôde ser confirmada. Tente conectar de novo.');
        }

        if ($request->query('error')) {
            return to_route('conexoes')->with('aviso', 'Você cancelou a conexão com o Facebook.');
        }

        // Mesma lição do YouTube (R-6): o retorno é um GET vindo de fora, e sem
        // este `try` as mensagens em português seriam escritas e jogadas fora.
        try {
            $contas = $meta->conectar((string) $request->query('code'));
        } catch (ValidationException $e) {
            return to_route('conexoes')->with('erro', $e->validator->errors()->first('meta'));
        }

        $paginas = collect($contas)->where('plataforma', Plataforma::Facebook)->count();
        $instagram = collect($contas)->where('plataforma', Plataforma::Instagram)->count();

        return to_route('conexoes')->with('sucesso', $this->resumoDaConexao($paginas, $instagram));
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
            return to_route('conexoes')->with('erro', 'A autorização não pôde ser confirmada. Tente conectar de novo.');
        }

        if ($request->query('error')) {
            // A pessoa clicou em "cancelar" na tela do Google — não é erro.
            return to_route('conexoes')->with('aviso', 'Você cancelou a conexão com o YouTube.');
        }

        // ⚠️ Sem este `try`, as mensagens do serviço não chegavam à tela: o
        // retorno do Google é um GET vindo de FORA, então a página anterior é o
        // site do Google. O redirecionamento automático de erro de validação
        // mandaria a pessoa de volta para lá — ela veria a tela de conexões
        // sem explicação nenhuma do que houve.
        try {
            $conta = $youtube->conectar((string) $request->query('code'));
        } catch (ValidationException $e) {
            return to_route('conexoes')->with('erro', $e->validator->errors()->first('youtube'));
        }

        return to_route('conexoes')->with(
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

        return to_route('conexoes')
            ->with('sucesso', "{$nome} foi desconectada. O histórico do que já foi publicado continua aqui.");
    }

    /**
     * Quantos posts estão comprovadamente no ar, por rede.
     *
     * Conta só `publicado` — que só existe depois de a conciliação reler o post
     * na rede. Contar `enviado` seria contar promessa, não entrega.
     *
     * @return array<string, int>
     */
    /**
     * Os três números de cada rede, numa consulta só.
     *
     * ⭐ Mostrar **só o que deu certo** é exatamente o que os concorrentes fazem,
     * e é a razão de o painel deles mentir. Aqui a falha aparece do lado do
     * acerto, no mesmo tamanho.
     *
     * @return array<string, array{publicados: int, falhas: int, andando: int}>
     */
    private function numerosPorRede(): array
    {
        $contagem = Destino::query()
            ->join('contas_sociais', 'contas_sociais.id', '=', 'destinos.conta_social_id')
            // O escopo do dono não alcança `destinos` (a tabela não tem dono
            // próprio), então o filtro vem pela conta — que tem.
            ->whereIn('contas_sociais.id', ContaSocial::query()->select('id'))
            ->groupBy('contas_sociais.plataforma', 'destinos.status')
            ->get([
                'contas_sociais.plataforma',
                'destinos.status',
                DB::raw('count(*) as total'),
            ]);

        $numeros = [];

        foreach ($contagem as $linha) {
            $rede = $linha->plataforma instanceof Plataforma
                ? $linha->plataforma->value
                : (string) $linha->plataforma;

            $status = $linha->status instanceof StatusDestino
                ? $linha->status
                : StatusDestino::from((string) $linha->status);

            $numeros[$rede] ??= ['publicados' => 0, 'falhas' => 0, 'andando' => 0];

            // ⚠️ `match` sem `default`: status novo obriga a decidir em qual
            // coluna ele entra, em vez de sumir de todas em silêncio.
            $coluna = match ($status) {
                StatusDestino::Publicado => 'publicados',
                StatusDestino::Falhou => 'falhas',
                StatusDestino::Pendente,
                StatusDestino::Enviando,
                StatusDestino::Processando,
                StatusDestino::AguardandoJanela => 'andando',
            };

            $numeros[$rede][$coluna] += (int) $linha->total;
        }

        return $numeros;
    }

    private function paraTela(ContaSocial $conta): array
    {
        return [
            'ulid' => $conta->ulid,
            'plataforma' => $conta->plataforma->value,
            'plataformaRotulo' => $conta->plataforma->rotulo(),
            'nome' => $conta->nome_exibicao,
            'status' => $conta->status->value,
            'statusRotulo' => $conta->status->rotulo(),
            // ⭐ O semaforo do DEC-32.
            'cor' => $conta->status->cor(),
            'detalhe' => $conta->status_detalhe,
            'podePublicar' => $conta->podePublicar(),
            // ⛔ NUNCA o token — nem para o admin impersonando.
            'venceEm' => $conta->credencial?->expira_em?->toIso8601String(),
            'venceEmBreve' => (bool) $conta->credencial?->venceEmBreve(),
        ];
    }
}

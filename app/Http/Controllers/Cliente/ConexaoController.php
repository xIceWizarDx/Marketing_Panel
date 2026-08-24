<?php

namespace App\Http\Controllers\Cliente;

use App\Enums\Plataforma;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\ConectarBlueskyRequest;
use App\Models\ContaSocial;
use App\Services\ConexaoComDiscord;
use App\Services\ConexaoComLinkedin;
use App\Services\ConexaoComMastodon;
use App\Services\ConexaoComPinterest;
use App\Services\ConexaoComThreads;
use App\Services\ConexaoComTiktok;
use App\Services\ConexaoComX;
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
        $this->folgaParaAsChamadasDaRede();

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
        $this->folgaParaAsChamadasDaRede();

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

    /**
     * Manda a pessoa autorizar no Threads.
     *
     * ⚠️ **Não é o Login do Facebook.** A janela é em `threads.net`, com escopos
     * `threads_*` — conectar o Instagram não acende o Threads (DEC-99).
     */
    public function iniciarThreads(Request $request, ConexaoComThreads $threads): RedirectResponse
    {
        if (! config('services.threads.client_id')) {
            return back()->with('erro', 'A conexão com o Threads ainda não está configurada neste servidor.');
        }

        /*
         * ⛔ Sem endereço público, o Threads não publica — e o problema aparece
         * só no fim, depois de a pessoa autorizar (DEC-101).
         *
         * A rede não recebe o arquivo: ela recebe uma URL e vem BUSCAR a mídia.
         * De `localhost` ela nunca vai buscar nada. Recusar aqui é dizer a
         * verdade antes; deixar passar seria uma conexão que conecta e nunca
         * publica.
         */
        if (! $this->alcancavelPelaInternet()) {
            return back()->with(
                'erro',
                'O Threads busca o vídeo no nosso endereço, e este servidor ainda não tem um endereço público. '.
                    'A conexão só funciona depois que o painel estiver publicado na internet.'
            );
        }

        $estado = Str::random(40);
        $request->session()->put('threads.estado', $estado);

        return redirect()->away($threads->enderecoDeAutorizacao($estado));
    }

    /** O Threads devolve a pessoa aqui, com o código. */
    public function retornoThreads(Request $request, ConexaoComThreads $threads): RedirectResponse
    {
        $this->folgaParaAsChamadasDaRede();

        $esperado = $request->session()->pull('threads.estado');

        if (! $esperado || ! hash_equals($esperado, (string) $request->query('state'))) {
            return to_route('painel')->with('erro', 'A autorização não pôde ser confirmada. Tente conectar de novo.');
        }

        if ($request->query('error')) {
            return to_route('painel')->with('aviso', 'Você cancelou a conexão com o Threads.');
        }

        // ⚠️ Mesmo motivo do YouTube: o retorno é um GET vindo de FORA, então o
        // redirecionamento automático de erro mandaria a pessoa de volta para a
        // tela da rede, sem explicação nenhuma.
        try {
            $conta = $threads->conectar((string) $request->query('code'));
        } catch (ValidationException $e) {
            return to_route('painel')->with('erro', $e->validator->errors()->first('threads'));
        }

        return to_route('painel')->with(
            'sucesso',
            "O perfil {$conta->nome_exibicao} está conectado no Threads."
        );
    }

    /**
     * Manda a pessoa autorizar no LinkedIn.
     *
     * ⚠️ Aqui a conexão tem **prazo de validade de verdade**: o token vive 60
     * dias e não existe renovação em segundo plano (DEC-112). Quando vencer, é
     * por esta mesma porta que a pessoa passa de novo.
     */
    public function iniciarLinkedin(Request $request, ConexaoComLinkedin $linkedin): RedirectResponse
    {
        if (! config('services.linkedin.client_id')) {
            return back()->with('erro', 'A conexão com o LinkedIn ainda não está configurada neste servidor.');
        }

        $estado = Str::random(40);
        $request->session()->put('linkedin.estado', $estado);

        return redirect()->away($linkedin->enderecoDeAutorizacao($estado));
    }

    /** O LinkedIn devolve a pessoa aqui, com o código. */
    public function retornoLinkedin(Request $request, ConexaoComLinkedin $linkedin): RedirectResponse
    {
        $this->folgaParaAsChamadasDaRede();

        $esperado = $request->session()->pull('linkedin.estado');

        if (! $esperado || ! hash_equals($esperado, (string) $request->query('state'))) {
            return to_route('painel')->with('erro', 'A autorização não pôde ser confirmada. Tente conectar de novo.');
        }

        /*
         * ⚠️ A rede separa os dois jeitos de desistir: `user_cancelled_login` é
         * não ter entrado na conta, `user_cancelled_authorize` é ter recusado as
         * permissões. Para quem usa o painel, os dois são a mesma coisa — a
         * conexão não aconteceu — e uma frase só diz isso sem jargão.
         */
        if ($request->query('error')) {
            return to_route('painel')->with('aviso', 'Você cancelou a conexão com o LinkedIn.');
        }

        try {
            $conta = $linkedin->conectar((string) $request->query('code'));
        } catch (ValidationException $e) {
            return to_route('painel')->with('erro', $e->validator->errors()->first('linkedin'));
        }

        return to_route('painel')->with(
            'sucesso',
            "O perfil {$conta->nome_exibicao} está conectado no LinkedIn."
        );
    }

    /**
     * Manda a pessoa autorizar no TikTok.
     *
     * ⚠️ Enquanto a auditoria do TikTok não sair, o post sobe **privado** — e a
     * pessoa fica sabendo disso ao conectar, não depois de publicar (DEC-116).
     */
    public function iniciarTiktok(Request $request, ConexaoComTiktok $tiktok): RedirectResponse
    {
        if (! config('services.tiktok.client_key')) {
            return back()->with('erro', 'A conexão com o TikTok ainda não está configurada neste servidor.');
        }

        $estado = Str::random(40);
        $request->session()->put('tiktok.estado', $estado);

        return redirect()->away($tiktok->enderecoDeAutorizacao($estado));
    }

    /** O TikTok devolve a pessoa aqui, com o código e os escopos aprovados. */
    public function retornoTiktok(Request $request, ConexaoComTiktok $tiktok): RedirectResponse
    {
        $this->folgaParaAsChamadasDaRede();

        $esperado = $request->session()->pull('tiktok.estado');

        if (! $esperado || ! hash_equals($esperado, (string) $request->query('state'))) {
            return to_route('painel')->with('erro', 'A autorização não pôde ser confirmada. Tente conectar de novo.');
        }

        if ($request->query('error')) {
            return to_route('painel')->with('aviso', 'Você cancelou a conexão com o TikTok.');
        }

        try {
            $conta = $tiktok->conectar(
                (string) $request->query('code'),
                // ⭐ `scopes`, no PLURAL: é aqui que o TikTok diz o que a pessoa
                // de fato aprovou. Ler `scope` devolveria vazio.
                ConexaoComTiktok::separar((string) $request->query('scopes', ''))
            );
        } catch (ValidationException $e) {
            return to_route('painel')->with('erro', $e->validator->errors()->first('tiktok'));
        }

        return to_route('painel')->with(
            'sucesso',
            config('services.tiktok.auditado', false)
                ? "A conta {$conta->nome_exibicao} está conectada no TikTok."
                : "A conta {$conta->nome_exibicao} está conectada. Enquanto a auditoria do TikTok não sair, os vídeos sobem como privados."
        );
    }

    /**
     * Manda a pessoa autorizar no X.
     *
     * ⛔ Primeira rede com PKCE: além do `state`, o **segredo da ida** precisa
     * sobreviver até a volta (DEC-129). Sem ele a troca falha sem recuperação.
     */
    public function iniciarX(Request $request, ConexaoComX $x): RedirectResponse
    {
        if (! config('services.x.client_id')) {
            return back()->with('erro', 'A conexão com o X ainda não está configurada neste servidor.');
        }

        $estado = Str::random(40);
        $pkce = ConexaoComX::segredoDeIda();

        $request->session()->put('x.estado', $estado);
        $request->session()->put('x.verificador', $pkce['verificador']);

        return redirect()->away($x->enderecoDeAutorizacao($estado, $pkce['desafio']));
    }

    /** O X devolve a pessoa aqui, com o código — que vive 30 segundos. */
    public function retornoX(Request $request, ConexaoComX $x): RedirectResponse
    {
        $this->folgaParaAsChamadasDaRede();

        $esperado = $request->session()->pull('x.estado');
        $verificador = (string) $request->session()->pull('x.verificador');

        if (! $esperado || ! hash_equals($esperado, (string) $request->query('state'))) {
            return to_route('painel')->with('erro', 'A autorização não pôde ser confirmada. Tente conectar de novo.');
        }

        if ($request->query('error')) {
            return to_route('painel')->with('aviso', 'Você cancelou a conexão com o X.');
        }

        if ($verificador === '') {
            /*
             * ⚠️ Sessão perdida no meio do caminho — outro navegador, cookie
             * expirado. Sem o segredo da ida a troca é impossível, e dizer isso
             * é melhor que deixar a rede devolver um erro genérico.
             */
            return to_route('painel')->with('erro', 'A conexão com o X expirou no meio do caminho. Tente de novo.');
        }

        try {
            $conta = $x->conectar((string) $request->query('code'), $verificador);
        } catch (ValidationException $e) {
            return to_route('painel')->with('erro', $e->validator->errors()->first('x'));
        }

        return to_route('painel')->with(
            'sucesso',
            "A conta @{$conta->nome_exibicao} está conectada no X. Lembre-se: cada publicação aqui consome crédito da API."
        );
    }

    /** Manda a pessoa autorizar no Pinterest. */
    public function iniciarPinterest(Request $request, ConexaoComPinterest $pinterest): RedirectResponse
    {
        if (! config('services.pinterest.client_id')) {
            return back()->with('erro', 'A conexão com o Pinterest ainda não está configurada neste servidor.');
        }

        $estado = Str::random(40);
        $request->session()->put('pinterest.estado', $estado);

        return redirect()->away($pinterest->enderecoDeAutorizacao($estado));
    }

    /**
     * O Pinterest devolve a pessoa aqui.
     *
     * ⚠️ Traz **um canal por quadro** (DEC-134), então o retorno conta quantos
     * entraram — dizer só "conectado" esconderia que vieram sete.
     */
    public function retornoPinterest(Request $request, ConexaoComPinterest $pinterest): RedirectResponse
    {
        $this->folgaParaAsChamadasDaRede();

        $esperado = $request->session()->pull('pinterest.estado');

        if (! $esperado || ! hash_equals($esperado, (string) $request->query('state'))) {
            return to_route('painel')->with('erro', 'A autorização não pôde ser confirmada. Tente conectar de novo.');
        }

        if ($request->query('error')) {
            return to_route('painel')->with('aviso', 'Você cancelou a conexão com o Pinterest.');
        }

        try {
            $contas = $pinterest->conectar((string) $request->query('code'));
        } catch (ValidationException $e) {
            return to_route('painel')->with('erro', $e->validator->errors()->first('pinterest'));
        }

        $quantos = count($contas);

        return to_route('painel')->with(
            'sucesso',
            $quantos === 1
                ? "O quadro {$contas[0]->nome_exibicao} está conectado no Pinterest."
                : "{$quantos} quadros do Pinterest estão conectados. Escolha em qual publicar na hora de publicar."
        );
    }

    /**
     * Manda a pessoa autorizar no servidor Mastodon DELA.
     *
     * ⛔ Aqui a porta e POST porque leva um formulario: nao existe "o Mastodon"
     * para onde mandar todo mundo (DEC-138). O aplicativo e registrado naquele
     * servidor na hora, e o par de credenciais vive so o tempo da autorizacao.
     */
    public function iniciarMastodon(Request $request, ConexaoComMastodon $mastodon): RedirectResponse
    {
        $servidor = ConexaoComMastodon::normalizarServidor((string) $request->input('servidor'));

        if ($servidor === '') {
            return back()->with('erro', 'Digite o endereço do seu servidor Mastodon — por exemplo, mastodon.social.');
        }

        try {
            $aplicativo = $mastodon->registrarAplicativo($servidor);
        } catch (ValidationException $e) {
            return back()->with('erro', $e->validator->errors()->first('servidor'));
        }

        $estado = Str::random(40);

        $request->session()->put('mastodon.estado', $estado);
        $request->session()->put('mastodon.servidor', $servidor);
        // ⚠️ O par vive so o tempo da autorizacao: depois do token ele nao serve
        // para mais nada, e guardar segredo sem uso e superficie a toa.
        $request->session()->put('mastodon.aplicativo', $aplicativo);

        return redirect()->away($mastodon->enderecoDeAutorizacao($servidor, $aplicativo['client_id'], $estado));
    }

    /** O servidor Mastodon devolve a pessoa aqui. */
    public function retornoMastodon(Request $request, ConexaoComMastodon $mastodon): RedirectResponse
    {
        $this->folgaParaAsChamadasDaRede();

        $esperado = $request->session()->pull('mastodon.estado');
        $servidor = (string) $request->session()->pull('mastodon.servidor');
        $aplicativo = (array) $request->session()->pull('mastodon.aplicativo');

        if (! $esperado || ! hash_equals($esperado, (string) $request->query('state'))) {
            return to_route('painel')->with('erro', 'A autorização não pôde ser confirmada. Tente conectar de novo.');
        }

        if ($request->query('error')) {
            return to_route('painel')->with('aviso', 'Você cancelou a conexão com o Mastodon.');
        }

        if ($servidor === '' || ! isset($aplicativo['client_id'], $aplicativo['client_secret'])) {
            return to_route('painel')->with('erro', 'A conexão com o Mastodon expirou no meio do caminho. Tente de novo.');
        }

        try {
            $conta = $mastodon->conectar($servidor, (string) $request->query('code'), [
                'client_id' => (string) $aplicativo['client_id'],
                'client_secret' => (string) $aplicativo['client_secret'],
            ]);
        } catch (ValidationException $e) {
            return to_route('painel')->with('erro', $e->validator->errors()->first('mastodon'));
        }

        return to_route('painel')->with('sucesso', "A conta {$conta->nome_exibicao} está conectada.");
    }

    /**
     * Conecta um canal do Discord pelo endereço do webhook.
     *
     * ⭐ Uma chamada só: nao ha ida ao site da rede, nem volta. A pessoa cria o
     * webhook no canal dela e cola o endereço (DEC-141).
     */
    public function conectarDiscord(Request $request, ConexaoComDiscord $discord): RedirectResponse
    {
        try {
            $conta = $discord->conectar((string) $request->input('endereco'));
        } catch (ValidationException $e) {
            return back()->with('erro', $e->validator->errors()->first('endereco'));
        }

        return to_route('painel')->with(
            'sucesso',
            "O canal {$conta->nome_exibicao} está conectado no Discord."
        );
    }

    /**
     * ⭐ **Tempo para o retorno da autorização respirar.**
     *
     * ⚠️ Conectar não é uma chamada: são **quatro em sequência** — trocar o
     * código por token, trocar por token longo, conferir as permissões
     * concedidas e listar as contas. Cada uma espera até 20 segundos, e o PHP
     * web corta a requisição em 30.
     *
     * ⛔ E o corte é **caro**, não só chato: o `state` da sessão já foi
     * consumido quando o tempo estoura, então a tentativa seguinte falha com
     * *"a autorização não pôde ser confirmada"* — um erro que não tem nada a ver
     * com a causa e manda a pessoa procurar no lugar errado. Foi exatamente o
     * que aconteceu no primeiro teste real com a Meta.
     *
     * ⚠️ Isto não substitui os tempos limite de cada chamada: eles continuam
     * sendo o que impede uma rede muda de segurar a requisição para sempre.
     * Aqui é só a soma deles caber.
     */
    private function folgaParaAsChamadasDaRede(): void
    {
        set_time_limit(120);
    }

    /**
     * O painel tem endereço que a internet alcança?
     *
     * ⚠️ Conferido pelo `APP_URL`, que é o endereço que o próprio painel usa
     * para montar links — é ele que vai parar dentro da URL da mídia.
     */
    private function alcancavelPelaInternet(): bool
    {
        $endereco = (string) config('app.url');
        $maquina = parse_url($endereco, PHP_URL_HOST) ?: '';

        return $maquina !== ''
            && ! in_array($maquina, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)
            // `.test` e `.local` são domínios de desenvolvimento: existem só na
            // máquina de quem programa.
            && ! preg_match('/\.(test|local|localhost)$/i', $maquina);
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

<?php

namespace App\Services\Meta;

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Models\ContaSocial;
use App\Support\FalhaDeConexao;
use App\Support\RegistroDeSeguranca;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Conecta Facebook e Instagram — **de uma vez só**.
 *
 * As duas redes são a mesma API por baixo: a conta do Instagram fica pendurada
 * numa Página do Facebook. Pedir dois logins seria pedir duas vezes a mesma
 * autorização e ainda deixar as duas metades saírem de sincronia.
 *
 * ⭐ O token da Página **não expira** (o do usuário dura ~60 dias). Por isso a
 * troca pelo token longo acontece **na conexão**, nunca depois: a documentação é
 * explícita que "não é possível usar um token expirado para pedir um longo".
 */
class ConexaoComMeta
{
    private const AUTORIZAR = 'https://www.facebook.com/v25.0/dialog/oauth';

    private const API = 'https://graph.facebook.com/v25.0';

    /**
     * ⭐ Escopo mínimo, mesma régua do YouTube (DEC-41).
     *
     * `pages_manage_posts` publica; `instagram_content_publish` idem; os de
     * leitura são o que a conciliação usa para **reler o post e provar que
     * existe**. Não pedimos nada de anúncios, mensagens ou dados de seguidores.
     */
    private const ESCOPOS = [
        'pages_show_list',
        'pages_read_engagement',
        'pages_manage_posts',
        'instagram_basic',
        'instagram_content_publish',
        /*
         * ⭐ **As duas de LEITURA de número** (DEC-143) — e elas não contrariam
         * o escopo mínimo (DEC-41): o mínimo é o mínimo **para o que o produto
         * faz**, e responder "funcionou?" passou a ser parte disso.
         *
         * ⛔ Continuamos sem pedir permissão de **apagar** nem de **alterar**. A
         * conta de quem usa segue intocável.
         *
         * ⚠️ E recusar estas duas **não quebra nada**: a conferência de escopo
         * só exige as de publicar. Quem negar continua publicando e provando —
         * só não vê número, e a tela diz isso em vez de mostrar zero.
         */
        'read_insights',
        'instagram_manage_insights',
        /*
         * ⛔ **Sem ela, `/me/accounts` volta VAZIO para quem usa o Business
         * Suite** (DEC-164) — e volta `200`, sem erro, sem aviso.
         *
         * ⚠️ A Meta mudou isso na v19 (janeiro de 2024): *"business_management
         * é obrigatória para todas as versões da API"*. Vale para Página que
         * pertence a um **portfólio empresarial** — e basta a pessoa vincular
         * um Instagram pelo Business Suite para a Página dela virar isso.
         *
         * ⛔ **Foi o defeito que custou o dia inteiro**, e ele é invisível por
         * construção: a Página existe, a pessoa é administradora, todas as
         * outras permissões são concedidas, e a lista chega vazia. Nada em
         * lugar nenhum diz "faltou esta permissão".
         *
         * ⚠️ **Cuidado no lançamento:** esta é notoriamente difícil de aprovar
         * na análise ("caso de uso pouco claro"). Precisa de vídeo mostrando a
         * tela onde ela é usada. Sem aprovação, cliente com Página de negócio
         * não conecta — e hoje isso é a maioria.
         */
        'business_management',
    ];

    /** Sem estes não há produto: são os que publicam. */
    private const ESCOPOS_ESSENCIAIS = ['pages_manage_posts', 'instagram_content_publish'];

    /**
     * As permissões de Página que autorizam publicar.
     *
     * ⚠️ Existem **duas nomenclaturas**: Páginas antigas devolvem
     * `CREATE_CONTENT`, e as da experiência nova — hoje o padrão — devolvem
     * `PROFILE_PLUS_CREATE_CONTENT`. Conferir só a primeira rejeitaria a Página
     * que a pessoa acabou de criar, dizendo que ela não tem permissão na
     * própria Página.
     */
    private const TAREFAS_DE_PUBLICACAO = [
        'CREATE_CONTENT',
        'MANAGE',
        'PROFILE_PLUS_CREATE_CONTENT',
        'PROFILE_PLUS_FULL_CONTROL',
    ];

    /**
     * ⭐ **`config_id`, e NÃO `scope`** — o app é Login para Empresas (DEC-162).
     *
     * ⛔ Este foi o defeito que custou uma investigação inteira. O endereço era
     * montado com `scope`, que é o parâmetro do **login clássico**. Com um app
     * configurado como *Login do Facebook para Empresas*, a Meta aceita a
     * autorização, concede **todas as permissões**… e **não anexa ativo
     * nenhum**. Nenhuma Página, nenhum Instagram.
     *
     * ⚠️ **E falha em silêncio, de um jeito cruel:** `/me/permissions` responde
     * que está tudo concedido, a integração aparece "Ativa" no Facebook com
     * todos os interruptores azuis, e `/me/accounts` volta `{"data":[]}`. Três
     * telas dizendo "deu certo" e nenhuma Página. Quem descobriu foi o
     * `debug_token`, no `granular_scopes` **sem `target_ids`** — a única
     * resposta da Meta que sabe distinguir *"autorizou o aplicativo"* de
     * *"autorizou o aplicativo NAQUELA Página"*.
     *
     * ⚠️ A seleção de ativos mora **na configuração**, do lado da Meta. Sem
     * dizer qual configuração é, não existe passo de escolher Página — e é por
     * isso que a tela nunca listava nenhuma.
     *
     * ⭐ A documentação é literal: *"embora `scope` ainda possa ser incluído,
     * recomendamos que você não o use"*. Mandar os dois é pedir para a Meta
     * decidir entre duas fontes de verdade.
     */
    public function enderecoDeAutorizacao(string $estado): string
    {
        $configuracao = config('services.meta.config_id');

        return self::AUTORIZAR.'?'.http_build_query(array_filter([
            'client_id' => config('services.meta.client_id'),
            'redirect_uri' => config('services.meta.redirect'),
            'response_type' => 'code',
            /*
             * ⚠️ Um OU outro, nunca os dois. O `scope` sobrevive só para o app
             * que ainda não tem configuração — e esse app não seleciona ativo,
             * então quem cair aqui vai cair de novo no mesmo buraco.
             */
            'config_id' => $configuracao,
            /*
             * ⛔ **Obrigatório junto do `config_id`** (DEC-162).
             *
             * ⚠️ Com configuração, o padrão da Meta é devolver **token** na
             * própria URL. Nós queremos `code`, para trocar no servidor — e
             * pedir `response_type=code` **sem** este parâmetro derruba o
             * diálogo com um *"Sorry, something went wrong"* seco, do lado
             * deles, sem dizer o que faltou.
             */
            'override_default_response_type' => $configuracao ? 'true' : null,
            'scope' => $configuracao ? null : implode(',', self::ESCOPOS),
            'state' => $estado,
        ], fn ($valor) => $valor !== null && $valor !== ''));
    }

    /**
     * @return list<ContaSocial> uma conta por Página, mais as do Instagram ligadas
     *
     * @throws ValidationException
     */
    public function conectar(string $codigo): array
    {
        $tokenLongo = $this->trocarPorTokenLongo($this->trocarCodigoPorToken($codigo));

        // ⚠️ A lição R-2 do YouTube: a tela do Facebook deixa recusar permissão
        // uma a uma. Sem conferir, a conta ficaria conectada e verde no painel
        // e falharia só no primeiro vídeo.
        $this->conferirPermissoes($tokenLongo);

        $paginas = $this->buscarPaginas($tokenLongo);

        return DB::transaction(function () use ($paginas) {
            $contas = [];

            foreach ($paginas as $pagina) {
                $contas[] = $this->guardarPagina($pagina);

                if ($pagina['instagram']) {
                    $contas[] = $this->guardarInstagram($pagina);
                }
            }

            RegistroDeSeguranca::registrar('rede_conectada', [
                'plataforma' => Plataforma::Facebook->value,
                'quantidade' => count($contas),
            ]);

            return $contas;
        });
    }

    /** @param array{id: string, nome: string, token: string, avatar: ?string, instagram: ?array{id: string, usuario: string, avatar: ?string}} $pagina */
    private function guardarPagina(array $pagina): ContaSocial
    {
        $conta = ContaSocial::updateOrCreate(
            ['plataforma' => Plataforma::Facebook, 'identificador_externo' => $pagina['id']],
            [
                'nome_exibicao' => $pagina['nome'],
                'avatar_url' => $pagina['avatar'],
                'status' => StatusConta::Ativa,
                'status_detalhe' => null,
            ]
        );

        $conta->credencial()->updateOrCreate([], [
            'access_token' => $pagina['token'],
            // ⭐ Token de Página não expira: não há o que renovar, e guardar um
            // `refresh_token` falso só criaria trabalho para o renovador.
            'refresh_token' => null,
            'expira_em' => null,
            'escopos' => self::ESCOPOS,
        ]);

        return $conta;
    }

    /** @param array{id: string, nome: string, token: string, instagram: ?array{id: string, usuario: string, avatar: ?string}} $pagina */
    private function guardarInstagram(array $pagina): ContaSocial
    {
        $conta = ContaSocial::updateOrCreate(
            ['plataforma' => Plataforma::Instagram, 'identificador_externo' => $pagina['instagram']['id']],
            [
                'nome_exibicao' => '@'.$pagina['instagram']['usuario'],
                'avatar_url' => $pagina['instagram']['avatar'],
                'status' => StatusConta::Ativa,
                'status_detalhe' => null,
            ]
        );

        // O Instagram publica com o token da PÁGINA, não com um token próprio.
        $conta->credencial()->updateOrCreate([], [
            'access_token' => $pagina['token'],
            'refresh_token' => null,
            'expira_em' => null,
            'escopos' => self::ESCOPOS,
        ]);

        return $conta;
    }

    /** @throws ValidationException */
    private function trocarCodigoPorToken(string $codigo): string
    {
        try {
            $resposta = Http::timeout(20)->get(self::API.'/oauth/access_token', [
                'client_id' => config('services.meta.client_id'),
                'client_secret' => config('services.meta.client_secret'),
                'redirect_uri' => config('services.meta.redirect'),
                'code' => $codigo,
            ]);
        } catch (ConnectionException $erro) {
            throw ValidationException::withMessages([
                'meta' => FalhaDeConexao::explicar($erro, 'Facebook'),
            ]);
        }

        if (! $resposta->successful() || ! $resposta->json('access_token')) {
            throw ValidationException::withMessages([
                'meta' => 'O Facebook não confirmou a autorização. Tente conectar de novo.',
            ]);
        }

        return $resposta->json('access_token');
    }

    /**
     * Curto (1-2 h) → longo (~60 dias).
     *
     * ⚠️ Tem que ser AGORA. Um token expirado não pode ser trocado, então adiar
     * isto significa perder a conexão sem conserto.
     *
     * @throws ValidationException
     */
    private function trocarPorTokenLongo(string $curto): string
    {
        $resposta = Http::timeout(20)->get(self::API.'/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => config('services.meta.client_id'),
            'client_secret' => config('services.meta.client_secret'),
            'fb_exchange_token' => $curto,
        ]);

        if (! $resposta->successful() || ! $resposta->json('access_token')) {
            throw ValidationException::withMessages([
                'meta' => 'O Facebook não liberou o acesso de longo prazo. Tente conectar de novo.',
            ]);
        }

        return $resposta->json('access_token');
    }

    /**
     * Confere o que foi de fato CONCEDIDO.
     *
     * O Facebook não devolve os escopos na troca do código — quem responde isso
     * é `/me/permissions`, com `status` `granted` ou `declined` por permissão.
     *
     * @throws ValidationException
     */
    private function conferirPermissoes(string $token): void
    {
        $resposta = Http::withToken($token)->timeout(20)->get(self::API.'/me/permissions');

        if (! $resposta->successful()) {
            // Não dá para afirmar que faltou permissão sem ter conseguido ler.
            // Barrar aqui seria inventar um problema; o erro real aparece na
            // busca das Páginas, logo em seguida.
            return;
        }

        $faltando = array_values(array_diff(
            self::ESCOPOS_ESSENCIAIS,
            $this->concedidas($resposta->json('data', []))
        ));

        if ($faltando === []) {
            return;
        }

        throw ValidationException::withMessages([
            'meta' => count($faltando) === count(self::ESCOPOS_ESSENCIAIS)
                ? 'As permissões de publicar não foram concedidas. Conecte de novo e '.
                    'mantenha marcadas as opções de publicar no Facebook e no Instagram.'
                : 'Faltou uma permissão de publicação. Conecte de novo e mantenha marcadas '.
                    'TODAS as opções — sem elas não conseguimos publicar por você.',
        ]);
    }

    /**
     * As permissões com `status: granted` — as outras vêm como `declined`.
     *
     * @param  array<int, array<string, mixed>>  $resposta  o `data` de `/me/permissions`
     * @return list<string>
     */
    private function concedidas(array $resposta): array
    {
        return array_values(array_column(
            array_filter($resposta, fn (array $p) => ($p['status'] ?? null) === 'granted'),
            'permission'
        ));
    }

    /** @param array<string, mixed> $pagina */
    private function podePublicar(array $pagina): bool
    {
        return array_intersect(self::TAREFAS_DE_PUBLICACAO, $pagina['tasks'] ?? []) !== [];
    }

    /**
     * As Páginas que a pessoa administra, com a conta do Instagram de cada uma.
     *
     * @return list<array{id: string, nome: string, token: string, avatar: ?string, instagram: ?array{id: string, usuario: string, avatar: ?string}}>
     *
     * @throws ValidationException
     */
    private function buscarPaginas(string $token): array
    {
        // ⚠️ Com tempo limite, como as outras três. Sem ele, uma resposta que
        // nunca chega segura a requisição inteira até o servidor desistir — e
        // quem está esperando é a pessoa que acabou de autorizar.
        /*
         * ⛔ **SEM o Instagram embutido aqui** (DEC-160).
         *
         * ⚠️ Esta chamada já pediu `instagram_business_account{...}` aninhado,
         * numa viagem só. A documentação descreve **duas chamadas separadas**:
         * primeiro `/me/accounts` sozinho, depois
         * `/{pagina}?fields=instagram_business_account`. Não era otimização —
         * era um caminho que a Meta não documenta.
         */
        $resposta = Http::withToken($token)->timeout(20)->get(self::API.'/me/accounts', [
            'fields' => 'id,name,access_token,tasks,picture{url}',
            'limit' => 100,
        ]);

        if (! $resposta->successful()) {
            $erro = ErroDaMeta::de($resposta);

            throw ValidationException::withMessages(['meta' => $erro->mensagem]);
        }

        $paginas = $resposta->json('data', []);

        if ($paginas === []) {
            /*
             * ⭐ **Lista vazia é registrada com a resposta crua** (DEC-156).
             *
             * ⛔ Aqui a Meta responde `200` com `data: []` — e "deu certo, mas
             * não veio nada" é o silêncio mais caro que existe: sem registro,
             * a única pista para quem investiga é a frase que a pessoa leu na
             * tela, que é justamente a nossa suposição sobre o que aconteceu.
             *
             * ⚠️ A lista está vazia, então **não há token de Página aqui** — é
             * seguro guardar. Nunca registrar a resposta cheia.
             */
            $permissoes = Http::withToken($token)->timeout(10)
                ->get(self::API.'/me/permissions')->json('data', []);

            Log::warning('A Meta devolveu nenhuma Página', [
                'corpo' => $resposta->json(),
                'permissoes' => $permissoes,
                /*
                 * ⭐ **A sonda que decide** (DEC-161): `granular_scopes` diz
                 * **quais Páginas** concederam cada permissão, uma a uma.
                 *
                 * ⛔ `/me/permissions` só responde *"pages_show_list foi
                 * concedida"* — e foi, sete vezes. Ele não diz **para qual
                 * Página**, e é aí que mora a diferença entre "a pessoa
                 * autorizou o app" e "a pessoa autorizou o app **naquela
                 * Página**". Duas investigações já morreram nessa distinção.
                 *
                 * ⚠️ `input_token` é o token da pessoa e `access_token` é o do
                 * aplicativo (`id|segredo`). Nada disso é registrado: só o que
                 * volta, que não tem credencial nenhuma.
                 */
                'concessoes' => $this->concessoesGranulares($token),
            ]);

            /*
             * ⛔ **A causa NUMERADA primeiro, quando dá para saber qual é**
             * (DEC-164).
             *
             * ⚠️ Página que pertence a um portfólio empresarial **não aparece**
             * em `/me/accounts` sem `business_management` — e a Meta responde
             * `200` com lista vazia, sem erro e sem aviso. Basta a pessoa ter
             * vinculado um Instagram pelo Business Suite para a Página dela
             * virar isso.
             *
             * ⛔ Mandar "marque a Página" para quem **não tem como marcá-la**
             * é o pior conselho possível: ela refaz a autorização em laço,
             * procurando um passo que a Meta não vai oferecer.
             */
            if (! in_array('business_management', $this->concedidas($permissoes), true)) {
                throw ValidationException::withMessages([
                    'meta' => 'A sua Página pertence a um portfólio empresarial e o Facebook não a '.
                        'entrega sem uma permissão que este aplicativo ainda não tem. Não é problema '.
                        'da sua conta nem da Página — quem resolve é o suporte.',
                ]);
            }

            /*
             * ⚠️ **Duas causas caem aqui, e só uma delas é "não tem Página"**
             * (DEC-150): a tela da Meta pergunta quais Páginas liberar, e quem
             * passa por ela sem marcar nenhuma recebe esta mesma lista vazia.
             *
             * ⛔ Afirmar "você não tem Página" para quem tem três é dizer uma
             * inverdade — e manda a pessoa criar a quarta em vez de refazer a
             * autorização, que é o que resolve. A mensagem cobre as duas, e a
             * mais provável vem primeiro.
             */
            throw ValidationException::withMessages([
                'meta' => 'O Facebook não liberou nenhuma Página. Se você tem uma, conecte de novo e '.
                    'marque a Página no passo em que a Meta pergunta quais usar. Se ainda não tem, crie '.
                    'uma Página — o Facebook e o Instagram só publicam por Página, nunca no perfil pessoal.',
            ]);
        }

        // ⚠️ Escopo CONCEDIDO, não pedido — a lição R-2 do YouTube. Aqui a
        // Meta responde por Página, em `tasks`: sem permissão de criar conteúdo,
        // aquela Página apareceria conectada e falharia no primeiro vídeo.
        $comPermissao = array_values(array_filter($paginas, $this->podePublicar(...)));

        if ($comPermissao === []) {
            throw ValidationException::withMessages([
                'meta' => 'Você não tem permissão para publicar em nenhuma dessas Páginas. '.
                    'Peça a função de Editor (ou superior) ao administrador da Página.',
            ]);
        }

        return array_map(fn (array $p) => [
            'id' => (string) $p['id'],
            'nome' => $p['name'],
            'token' => $p['access_token'],
            'avatar' => $p['picture']['data']['url'] ?? null,
            // ⭐ Segunda chamada, com o token DA PÁGINA — o caminho documentado.
            'instagram' => $this->instagramDaPagina((string) $p['id'], $p['access_token']),
        ], $comPermissao);
    }

    /**
     * ⭐ **Quais Páginas concederam cada permissão** (DEC-161).
     *
     * ⛔ Isto responde a pergunta que `/me/permissions` **não** responde. Ele diz
     * *"`pages_show_list` foi concedida"* — e foi. Não diz **para qual Página**,
     * e a diferença entre "autorizou o aplicativo" e "autorizou o aplicativo
     * **naquela Página**" é onde duas investigações já morreram.
     *
     * ⚠️ Só serve para o registro, e por isso **nunca lança**: uma sonda que
     * derruba a conexão que estava sendo investigada não é sonda, é um segundo
     * defeito.
     *
     * @return array<string, mixed>
     */
    private function concessoesGranulares(string $token): array
    {
        $id = config('services.meta.client_id');
        $segredo = config('services.meta.client_secret');

        if (! $id || ! $segredo) {
            return ['erro' => 'sem credencial do aplicativo para consultar'];
        }

        try {
            $resposta = Http::timeout(10)->get(self::API.'/debug_token', [
                'input_token' => $token,
                // ⚠️ Token do APLICATIVO, não da pessoa: `id|segredo`.
                'access_token' => $id.'|'.$segredo,
            ]);
        } catch (ConnectionException) {
            return ['erro' => 'não respondeu'];
        }

        $dados = $resposta->json('data') ?? [];

        // ⚠️ Só o que interessa, e nada que seja credencial.
        return [
            'granular_scopes' => $dados['granular_scopes'] ?? null,
            'scopes' => $dados['scopes'] ?? null,
            'type' => $dados['type'] ?? null,
            'is_valid' => $dados['is_valid'] ?? null,
            'erro' => $resposta->json('error.message'),
        ];
    }

    /**
     * A conta do Instagram ligada a esta Página — ou `null`.
     *
     * ⭐ **Chamada própria, com o token da Página** (DEC-160), como a
     * documentação descreve. Pedir isto aninhado no `/me/accounts` derrubava a
     * lista inteira em silêncio.
     *
     * ⛔ **Falhar aqui NÃO derruba a conexão da Página.** O Instagram é
     * acréscimo; a Página é o que a pessoa veio conectar. Perder as duas porque
     * a segunda viagem tropeçou seria transformar um acréscimo em requisito.
     *
     * @return array{id: string, usuario: string, avatar: ?string}|null
     */
    private function instagramDaPagina(string $pagina, string $tokenDaPagina): ?array
    {
        try {
            $resposta = Http::withToken($tokenDaPagina)->timeout(20)->get(self::API.'/'.$pagina, [
                'fields' => 'instagram_business_account{id,username,profile_picture_url}',
            ]);
        } catch (ConnectionException) {
            return null;
        }

        $conta = $resposta->successful() ? $resposta->json('instagram_business_account') : null;

        if (! is_array($conta) || ! isset($conta['id'])) {
            return null;
        }

        return [
            'id' => (string) $conta['id'],
            'usuario' => $conta['username'] ?? 'instagram',
            'avatar' => $conta['profile_picture_url'] ?? null,
        ];
    }
}

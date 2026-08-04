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

    public function enderecoDeAutorizacao(string $estado): string
    {
        return self::AUTORIZAR.'?'.http_build_query([
            'client_id' => config('services.meta.client_id'),
            'redirect_uri' => config('services.meta.redirect'),
            'response_type' => 'code',
            'scope' => implode(',', self::ESCOPOS),
            'state' => $estado,
        ]);
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

        $concedidas = array_column(
            array_filter(
                $resposta->json('data', []),
                fn (array $p) => ($p['status'] ?? null) === 'granted'
            ),
            'permission'
        );

        $faltando = array_values(array_diff(self::ESCOPOS_ESSENCIAIS, $concedidas));

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
        $resposta = Http::withToken($token)->get(self::API.'/me/accounts', [
            // Numa chamada só: a Página, o token dela e o Instagram ligado.
            'fields' => 'id,name,access_token,tasks,picture{url},'.
                'instagram_business_account{id,username,profile_picture_url}',
            'limit' => 100,
        ]);

        if (! $resposta->successful()) {
            $erro = ErroDaMeta::de($resposta);

            throw ValidationException::withMessages(['meta' => $erro->mensagem]);
        }

        $paginas = $resposta->json('data', []);

        if ($paginas === []) {
            // ⚠️ Não é erro nosso e não adianta tentar de novo: o Facebook só
            // publica em Página. Sem dizer isso, a pessoa conclui que quebrou.
            throw ValidationException::withMessages([
                'meta' => 'Nenhuma Página do Facebook foi encontrada nesta conta. '.
                    'O Facebook e o Instagram só publicam por Página — crie uma e conecte de novo.',
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
            'instagram' => isset($p['instagram_business_account'])
                ? [
                    'id' => (string) $p['instagram_business_account']['id'],
                    'usuario' => $p['instagram_business_account']['username'] ?? 'instagram',
                    'avatar' => $p['instagram_business_account']['profile_picture_url'] ?? null,
                ]
                : null,
        ], $comPermissao);
    }
}

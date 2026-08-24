<?php

namespace App\Services;

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Models\ContaSocial;
use App\Support\Conexao\CanalDeUmGrupoSo;
use App\Support\FalhaDeConexao;
use App\Support\RegistroDeSeguranca;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Conecta uma conta do TikTok.
 *
 * ⚠️ **O token daqui vive 24 HORAS** — o prazo mais curto de todas as redes do
 * painel, por larga margem. O `refresh_token` vale 365 dias, então a conexão
 * não morre; mas renovar deixa de ser manutenção de madrugada e vira parte do
 * caminho de publicar (DEC-118).
 *
 * ⛔ E o `refresh_token` **gira**: a documentação avisa que o devolvido pode ser
 * diferente do enviado. Guardar o antigo dá uma conexão que funciona hoje,
 * funciona amanhã, e um dia para sem ninguém ter mexido em nada (DEC-119).
 */
class ConexaoComTiktok
{
    private const AUTORIZAR = 'https://www.tiktok.com/v2/auth/authorize/';

    /**
     * ⚠️ `user.info.basic` acompanha por necessidade: sem ele não há nome nem
     * avatar para mostrar, e a conta apareceria sem cara na tela.
     *
     * ⛔ `video.upload` fica de fora de propósito. Ele é o **outro** fluxo — o
     * vídeo vai para a caixa de entrada do criador e ele termina de postar
     * dentro do aplicativo do TikTok. Sem publicação nossa não há post para
     * reler, e a promessa do produto cai.
     */
    private const ESCOPOS = [
        'user.info.basic',
        'video.publish',
        /*
         * ⭐ Ler os números dos próprios vídeos (DEC-143) — `view_count`,
         * `like_count`, `comment_count`, `share_count`.
         *
         * ⚠️ O nome engana: `video.list` **não** dá acesso a vídeo de terceiro.
         * Ele lê os vídeos públicos **da conta autorizada**, que são justamente
         * os que nós publicamos.
         */
        'video.list',
        /*
         * ⛔ **`follower_count` mora aqui, e NÃO em `user.info.basic`**
         * (DEC-168).
         *
         * ⚠️ A referência divide os campos em três escopos, e o nome do
         * primeiro engana: `user.info.basic` dá `open_id`, `display_name` e
         * avatar — só identidade. Número de seguidor está em
         * **`user.info.stats`**, junto de `following_count` e `likes_count`.
         *
         * ⛔ Sem ele, `metricasDaConta()` voltava `null` **para sempre**, e a
         * tela dizia "sem leitura" — indistinguível de rede que não respondeu.
         * É a mesma família de defeito silencioso que a auditoria da Meta achou
         * em `total_video_views` (DEC-157): campo pedido com a chave errada,
         * resposta vazia, nenhum erro em lugar nenhum.
         */
        'user.info.stats',
    ];

    /** Sem este, a conta conecta e não publica — a conexão não teria função. */
    private const ESCOPO_ESSENCIAL = 'video.publish';

    public function enderecoDeAutorizacao(string $estado): string
    {
        return self::AUTORIZAR.'?'.http_build_query([
            // ⚠️ É `client_key`, NÃO `client_id`. O nome é diferente de toda
            // outra rede, e mandar o errado devolve um erro que não diz qual
            // parâmetro faltou.
            'client_key' => config('services.tiktok.client_key'),
            'response_type' => 'code',
            'scope' => implode(',', self::ESCOPOS),
            'redirect_uri' => config('services.tiktok.redirect'),
            'state' => $estado,
        ]);
    }

    /**
     * @param  list<string>  $escoposConcedidos  o campo `scopes` do retorno
     *
     * @throws ValidationException
     */
    public function conectar(string $codigo, array $escoposConcedidos = []): ContaSocial
    {
        /*
         * ⭐ Os escopos CONCEDIDOS chegam no **retorno da autorização**, no
         * campo `scopes` — plural, e separado por vírgula.
         *
         * ⚠️ A resposta do token traz `scope` (singular). Os dois existem, e
         * ler só um deixaria a conferência dependendo de qual a rede preencheu.
         */
        $token = $this->trocarCodigoPorToken($codigo);
        $concedidos = $escoposConcedidos !== [] ? $escoposConcedidos : $token['escopos'];

        if ($concedidos !== [] && ! in_array(self::ESCOPO_ESSENCIAL, $concedidos, true)) {
            throw ValidationException::withMessages([
                'tiktok' => 'Faltou autorizar a permissão de publicar. Conecte de novo e mantenha todas as opções marcadas.',
            ]);
        }

        $perfil = $this->buscarPerfil($token['access_token'], $token['open_id']);

        return DB::transaction(function () use ($token, $perfil, $concedidos) {
            // ⛔ O canal já pode viver em outro grupo — gravar aqui atualizaria
            // o registro de lá e responderia "conectado" sem nada aparecer.
            CanalDeUmGrupoSo::garantir(Plataforma::Tiktok, $token['open_id'], 'tiktok');

            $conta = ContaSocial::updateOrCreate(
                [
                    'plataforma' => Plataforma::Tiktok,
                    'identificador_externo' => $token['open_id'],
                ],
                [
                    'nome_exibicao' => $perfil['nome'],
                    'avatar_url' => $perfil['avatar'],
                    'status' => StatusConta::Ativa,
                    'status_detalhe' => null,
                ]
            );

            $conta->credencial()->updateOrCreate([], [
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'],
                // ⚠️ 24 horas. O `expira_em` daqui é curto de verdade, e é o
                // publicador que olha para ele antes de subir (DEC-118).
                'expira_em' => now()->addSeconds($token['expires_in']),
                'escopos' => $concedidos,
            ]);

            RegistroDeSeguranca::registrar('rede_conectada', [
                'plataforma' => Plataforma::Tiktok->value,
                'conta_ulid' => $conta->ulid,
            ]);

            return $conta;
        });
    }

    /**
     * @return array{access_token: string, refresh_token: ?string, expires_in: int, open_id: string, escopos: list<string>}
     *
     * @throws ValidationException
     */
    private function trocarCodigoPorToken(string $codigo): array
    {
        try {
            $resposta = Http::asForm()->timeout(20)->post(TokenDoTiktok::TOKEN, [
                'client_key' => config('services.tiktok.client_key'),
                'client_secret' => config('services.tiktok.client_secret'),
                // ⚠️ A documentação pede o código **decodificado de URL**.
                'code' => urldecode($codigo),
                'grant_type' => 'authorization_code',
                'redirect_uri' => config('services.tiktok.redirect'),
            ]);
        } catch (ConnectionException $erro) {
            throw $this->naoRespondeu($erro);
        }

        if (! $resposta->successful() || ! $resposta->json('access_token') || ! $resposta->json('open_id')) {
            throw ValidationException::withMessages([
                'tiktok' => 'Não conseguimos concluir a autorização com o TikTok. Tente de novo.',
            ]);
        }

        return [
            'access_token' => (string) $resposta->json('access_token'),
            'refresh_token' => $resposta->json('refresh_token'),
            'expires_in' => (int) $resposta->json('expires_in', 0),
            /*
             * ⚠️ O `open_id` é **por aplicativo**: o mesmo criador tem outro em
             * outro app. Não é identificador público do TikTok e não serve para
             * montar endereço de perfil.
             */
            'open_id' => (string) $resposta->json('open_id'),
            'escopos' => self::separar((string) $resposta->json('scope', '')),
        ];
    }

    /**
     * Nome e avatar da conta.
     *
     * ⚠️ Falha aqui **não** derruba a conexão: o token já é válido e a conta já
     * publica. Sem o nome, ela aparece pelo identificador — feio, mas honesto,
     * e melhor que recusar uma autorização que deu certo.
     *
     * @return array{nome: string, avatar: ?string}
     */
    private function buscarPerfil(string $token, string $openId): array
    {
        try {
            $resposta = Http::withToken($token)->timeout(20)
                ->get('https://open.tiktokapis.com/v2/user/info/', [
                    'fields' => 'open_id,display_name,avatar_url',
                ]);
        } catch (ConnectionException) {
            return ['nome' => 'TikTok', 'avatar' => null];
        }

        return [
            'nome' => (string) ($resposta->json('data.user.display_name') ?: 'TikTok'),
            'avatar' => $resposta->json('data.user.avatar_url'),
        ];
    }

    /** @return list<string> */
    public static function separar(string $lista): array
    {
        return array_values(array_filter(preg_split('/[\s,]+/', $lista) ?: []));
    }

    private function naoRespondeu(ConnectionException $erro): ValidationException
    {
        return ValidationException::withMessages([
            'tiktok' => FalhaDeConexao::explicar($erro, 'TikTok'),
        ]);
    }
}

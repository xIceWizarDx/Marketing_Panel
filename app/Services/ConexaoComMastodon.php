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
 * Conecta uma conta do Mastodon.
 *
 * ⛔ **O Mastodon não é um serviço: é um protocolo.** Não existe "o Mastodon"
 * para integrar — existem milhares de servidores independentes, cada um com
 * endereço, regras e **aplicativo próprios**. Por isso a pessoa precisa dizer
 * onde a conta dela mora antes de qualquer outra coisa (DEC-138).
 *
 * ⭐ **E por isso mesmo aqui não há portal para cadastrar nada:** o próprio
 * protocolo permite registrar o aplicativo por API, sem autenticação nenhuma
 * (DEC-139). É a única rede do painel que conecta sem ninguém precisar criar
 * conta de desenvolvedor em lugar algum.
 */
class ConexaoComMastodon
{
    /**
     * ⚠️ Três escopos, e cada um tem função:
     *
     * - `write:statuses` publica;
     * - `write:media` sobe o vídeo — separado, como no X;
     * - `read:accounts` diz de quem é a conta.
     *
     * ⛔ **`read` inteiro fica de fora**: ele daria acesso à linha do tempo, às
     * notificações e às mensagens diretas da pessoa. Pedir isso para publicar um
     * vídeo seria pedir muito mais do que precisamos.
     */
    private const ESCOPOS = ['write:statuses', 'write:media', 'read:accounts'];

    /**
     * Registra o aplicativo NAQUELE servidor.
     *
     * ⭐ Sem autenticação — é assim que o protocolo funciona. Cada servidor
     * emite um par de credenciais próprio para o nosso painel.
     *
     * ⚠️ O par vive só o tempo da autorização: depois do token, ele não serve
     * para mais nada nesta rede (o token do Mastodon não vence), e guardar
     * segredo que não tem uso é aumentar a superfície à toa.
     *
     * @return array{client_id: string, client_secret: string}
     *
     * @throws ValidationException
     */
    public function registrarAplicativo(string $servidor): array
    {
        $servidor = self::normalizarServidor($servidor);

        try {
            $resposta = Http::timeout(20)->asForm()->post("https://{$servidor}/api/v1/apps", [
                'client_name' => config('app.name'),
                'redirect_uris' => config('services.mastodon.redirect'),
                'scopes' => implode(' ', self::ESCOPOS),
                'website' => config('app.url'),
            ]);
        } catch (ConnectionException $erro) {
            throw ValidationException::withMessages([
                'servidor' => FalhaDeConexao::explicar($erro, $servidor),
            ]);
        }

        if (! $resposta->successful() || ! $resposta->json('client_id')) {
            /*
             * ⚠️ A causa mais provável é endereço errado — e é o que a frase
             * diz. Falar em "aplicativo" aqui não ajudaria ninguém: quem digitou
             * `mastodon.social.com` por engano precisa saber que foi isso.
             */
            throw ValidationException::withMessages([
                'servidor' => "Não encontramos um Mastodon em «{$servidor}». Confira o endereço do servidor e tente de novo.",
            ]);
        }

        return [
            'client_id' => (string) $resposta->json('client_id'),
            'client_secret' => (string) $resposta->json('client_secret'),
        ];
    }

    public function enderecoDeAutorizacao(string $servidor, string $clientId, string $estado): string
    {
        return 'https://'.self::normalizarServidor($servidor).'/oauth/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => config('services.mastodon.redirect'),
            'scope' => implode(' ', self::ESCOPOS),
            'state' => $estado,
        ]);
    }

    /**
     * @param  array{client_id: string, client_secret: string}  $aplicativo
     *
     * @throws ValidationException
     */
    public function conectar(string $servidor, string $codigo, array $aplicativo): ContaSocial
    {
        $servidor = self::normalizarServidor($servidor);
        $token = $this->trocarCodigoPorToken($servidor, $codigo, $aplicativo);
        $perfil = $this->buscarPerfil($servidor, $token['access_token']);

        return DB::transaction(function () use ($servidor, $token, $perfil) {
            /*
             * ⚠️ O identificador é do servidor: dois servidores podem usar o
             * mesmo número para contas diferentes. Por isso ele carrega o
             * endereço junto — sem isso, conectar `@ana@a.social` e
             * `@joao@b.social` com o mesmo id daria uma conta só.
             */
            $identificador = "{$servidor}:{$perfil['id']}";

            CanalDeUmGrupoSo::garantir(Plataforma::Mastodon, $identificador, 'mastodon');

            $conta = ContaSocial::updateOrCreate(
                [
                    'plataforma' => Plataforma::Mastodon,
                    'identificador_externo' => $identificador,
                ],
                [
                    'servidor' => $servidor,
                    // ⭐ O identificador que a pessoa reconhece: `@alguem@casa.social`.
                    'nome_exibicao' => "@{$perfil['usuario']}@{$servidor}",
                    'avatar_url' => $perfil['avatar'],
                    'status' => StatusConta::Ativa,
                    'status_detalhe' => null,
                ]
            );

            $conta->credencial()->updateOrCreate([], [
                'access_token' => $token['access_token'],
                /*
                 * ⛔ Sem token de renovação, e **sem prazo** — o Mastodon emite
                 * token que vale até ser revogado.
                 *
                 * ⚠️ `expira_em` nulo aqui quer dizer "não vence", não "não
                 * sabemos": é o único caso do painel em que a ausência de prazo
                 * é a verdade, e não uma informação faltando.
                 */
                'refresh_token' => null,
                'expira_em' => null,
                'escopos' => $token['escopos'],
            ]);

            RegistroDeSeguranca::registrar('rede_conectada', [
                'plataforma' => Plataforma::Mastodon->value,
                'conta_ulid' => $conta->ulid,
            ]);

            return $conta;
        });
    }

    /**
     * ⭐ O endereço do servidor, limpo.
     *
     * ⚠️ A pessoa vai digitar de tudo: `https://masto.social/`, `@masto.social`,
     * `masto.social/@alguem`. Recusar essas formas seria fazer birra com quem
     * acertou o servidor e errou a digitação.
     */
    public static function normalizarServidor(string $bruto): string
    {
        $limpo = trim($bruto);
        $limpo = preg_replace('~^https?://~i', '', $limpo) ?? $limpo;
        $limpo = ltrim($limpo, '@');
        $limpo = explode('/', $limpo)[0];

        return strtolower(trim($limpo));
    }

    /**
     * @param  array{client_id: string, client_secret: string}  $aplicativo
     * @return array{access_token: string, escopos: list<string>}
     *
     * @throws ValidationException
     */
    private function trocarCodigoPorToken(string $servidor, string $codigo, array $aplicativo): array
    {
        try {
            $resposta = Http::asForm()->timeout(20)->post("https://{$servidor}/oauth/token", [
                'grant_type' => 'authorization_code',
                'code' => $codigo,
                'client_id' => $aplicativo['client_id'],
                'client_secret' => $aplicativo['client_secret'],
                'redirect_uri' => config('services.mastodon.redirect'),
                'scope' => implode(' ', self::ESCOPOS),
            ]);
        } catch (ConnectionException $erro) {
            throw ValidationException::withMessages([
                'mastodon' => FalhaDeConexao::explicar($erro, $servidor),
            ]);
        }

        if (! $resposta->successful() || ! $resposta->json('access_token')) {
            throw ValidationException::withMessages([
                'mastodon' => "Não conseguimos concluir a autorização em «{$servidor}». Tente de novo.",
            ]);
        }

        return [
            'access_token' => (string) $resposta->json('access_token'),
            'escopos' => array_values(array_filter(
                preg_split('/[\s,]+/', (string) $resposta->json('scope', '')) ?: []
            )),
        ];
    }

    /**
     * @return array{id: string, usuario: string, avatar: ?string}
     *
     * @throws ValidationException
     */
    private function buscarPerfil(string $servidor, string $token): array
    {
        try {
            $resposta = Http::withToken($token)->timeout(20)
                ->get("https://{$servidor}/api/v1/accounts/verify_credentials");
        } catch (ConnectionException $erro) {
            throw ValidationException::withMessages([
                'mastodon' => FalhaDeConexao::explicar($erro, $servidor),
            ]);
        }

        if (! $resposta->successful() || ! $resposta->json('id')) {
            throw ValidationException::withMessages([
                'mastodon' => "Não conseguimos ler o perfil em «{$servidor}». Tente conectar de novo.",
            ]);
        }

        return [
            'id' => (string) $resposta->json('id'),
            'usuario' => (string) ($resposta->json('username') ?: 'conta'),
            'avatar' => $resposta->json('avatar'),
        ];
    }
}

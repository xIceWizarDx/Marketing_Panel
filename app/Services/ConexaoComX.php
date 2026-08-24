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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Conecta uma conta do X.
 *
 * ⛔ **Primeira rede do painel com PKCE obrigatório** (DEC-129): o segredo nasce
 * na ida e é exigido na volta. As outras guardam só o `state`; aqui são dois, e
 * esquecer o segundo só aparece na hora de conectar de verdade.
 *
 * ⛔ **E o código de autorização vive 30 SEGUNDOS** (DEC-128) — uma ordem de
 * grandeza abaixo de qualquer outra rede. Por isso a troca é a primeira coisa
 * que acontece aqui, antes de ler perfil, conferir grupo ou tocar no banco.
 */
class ConexaoComX
{
    private const AUTORIZAR = 'https://x.com/i/oauth2/authorize';

    public const TOKEN = 'https://api.x.com/2/oauth2/token';

    private const PERFIL = 'https://api.x.com/2/users/me';

    /**
     * ⚠️ Cinco escopos, e **nenhum é dispensável**:
     *
     * - `tweet.write` publica;
     * - `media.write` sobe o vídeo — **é separado**, e esquecer dá conta que
     *   conecta, texto que subiria e vídeo que não (DEC-131);
     * - `tweet.read` relê o post, que é a prova (DEC-31);
     * - `users.read` diz quem é a conta e dá o nome de usuário do endereço;
     * - `offline.access` é o que faz existir token de renovação. Sem ele a
     *   conexão morre em duas horas sem nada ter mudado.
     */
    private const ESCOPOS = ['tweet.read', 'tweet.write', 'users.read', 'media.write', 'offline.access'];

    /** Sem estes dois, a conta conecta e não publica vídeo. */
    private const ESCOPOS_ESSENCIAIS = ['tweet.write', 'media.write'];

    /**
     * O par do PKCE: o segredo que fica com a gente e o desafio que vai na URL.
     *
     * @return array{verificador: string, desafio: string}
     */
    public static function segredoDeIda(): array
    {
        $verificador = Str::random(64);

        return [
            'verificador' => $verificador,
            // ⚠️ base64url: sem `=`, com `-` e `_`. O base64 comum é recusado.
            'desafio' => rtrim(strtr(base64_encode(hash('sha256', $verificador, true)), '+/', '-_'), '='),
        ];
    }

    public function enderecoDeAutorizacao(string $estado, string $desafio): string
    {
        return self::AUTORIZAR.'?'.http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.x.client_id'),
            'redirect_uri' => config('services.x.redirect'),
            // ⚠️ Separados por ESPAÇO — o `http_build_query` cuida de codificar.
            'scope' => implode(' ', self::ESCOPOS),
            'state' => $estado,
            'code_challenge' => $desafio,
            'code_challenge_method' => 'S256',
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function conectar(string $codigo, string $verificador): ContaSocial
    {
        /*
         * ⛔ **PRIMEIRA coisa** (DEC-128). O código vive 30 segundos: qualquer
         * consulta antes disto pode consumir a janela e queimar a autorização —
         * e o erro que apareceria é o genérico "a autorização não pôde ser
         * confirmada", que manda a pessoa procurar no lugar errado.
         */
        $token = $this->trocarCodigoPorToken($codigo, $verificador);

        $faltando = array_diff(self::ESCOPOS_ESSENCIAIS, $token['escopos']);

        if ($token['escopos'] !== [] && $faltando !== []) {
            throw ValidationException::withMessages([
                'x' => 'Faltou autorizar a permissão de publicar ou a de enviar vídeo. '.
                    'Conecte de novo e mantenha todas as opções marcadas.',
            ]);
        }

        $perfil = $this->buscarPerfil($token['access_token']);

        return DB::transaction(function () use ($token, $perfil) {
            // ⛔ O canal já pode viver em outro grupo — gravar aqui atualizaria
            // o registro de lá e responderia "conectado" sem nada aparecer.
            CanalDeUmGrupoSo::garantir(Plataforma::X, $perfil['id'], 'x');

            $conta = ContaSocial::updateOrCreate(
                [
                    'plataforma' => Plataforma::X,
                    'identificador_externo' => $perfil['id'],
                ],
                [
                    // ⭐ O nome de usuário, não o de exibição: é ele que monta o
                    // endereço do post (`x.com/{username}/status/{id}`), e é o
                    // que a pessoa reconhece.
                    'nome_exibicao' => $perfil['usuario'],
                    'avatar_url' => $perfil['avatar'],
                    'status' => StatusConta::Ativa,
                    'status_detalhe' => null,
                ]
            );

            $conta->credencial()->updateOrCreate([], [
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'],
                // ⚠️ Duas horas. É o publicador que olha para isto antes de
                // subir qualquer coisa (DEC-130).
                'expira_em' => now()->addSeconds($token['expires_in']),
                'escopos' => $token['escopos'],
            ]);

            RegistroDeSeguranca::registrar('rede_conectada', [
                'plataforma' => Plataforma::X->value,
                'conta_ulid' => $conta->ulid,
            ]);

            return $conta;
        });
    }

    /**
     * @return array{access_token: string, refresh_token: ?string, expires_in: int, escopos: list<string>}
     *
     * @throws ValidationException
     */
    private function trocarCodigoPorToken(string $codigo, string $verificador): array
    {
        try {
            $resposta = Http::asForm()
                // ⚠️ Aplicativo confidencial se identifica pelo cabeçalho
                // `Authorization`, e o `client_id` vai junto no corpo porque a
                // documentação pede nos dois lugares.
                ->withBasicAuth((string) config('services.x.client_id'), (string) config('services.x.client_secret'))
                ->timeout(20)
                ->post(self::TOKEN, [
                    'grant_type' => 'authorization_code',
                    'code' => $codigo,
                    'code_verifier' => $verificador,
                    'client_id' => config('services.x.client_id'),
                    'redirect_uri' => config('services.x.redirect'),
                ]);
        } catch (ConnectionException $erro) {
            throw $this->naoRespondeu($erro);
        }

        if (! $resposta->successful() || ! $resposta->json('access_token')) {
            throw ValidationException::withMessages([
                'x' => 'Não conseguimos concluir a autorização com o X. Tente de novo — o código de '.
                    'autorização deles vale poucos segundos.',
            ]);
        }

        return [
            'access_token' => (string) $resposta->json('access_token'),
            'refresh_token' => $resposta->json('refresh_token'),
            'expires_in' => (int) $resposta->json('expires_in', 7200),
            // ⚠️ Separados por espaço aqui também.
            'escopos' => self::separar((string) $resposta->json('scope', '')),
        ];
    }

    /**
     * Quem é a conta.
     *
     * @return array{id: string, usuario: string, avatar: ?string}
     *
     * @throws ValidationException
     */
    private function buscarPerfil(string $token): array
    {
        try {
            $resposta = Http::withToken($token)->timeout(20)
                ->get(self::PERFIL, ['user.fields' => 'profile_image_url,username,name']);
        } catch (ConnectionException $erro) {
            throw $this->naoRespondeu($erro);
        }

        if (! $resposta->successful() || ! $resposta->json('data.id')) {
            throw ValidationException::withMessages([
                'x' => 'Não conseguimos ler o perfil do X. Confira se a conta está ativa e tente de novo.',
            ]);
        }

        return [
            'id' => (string) $resposta->json('data.id'),
            'usuario' => (string) ($resposta->json('data.username') ?: 'X'),
            'avatar' => $resposta->json('data.profile_image_url'),
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
            'x' => FalhaDeConexao::explicar($erro, 'X'),
        ]);
    }
}

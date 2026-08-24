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
 * Conecta uma conta do Threads.
 *
 * ⛔ **Não reaproveita nada do Facebook e do Instagram**, e isso é decisão, não
 * preguiça (DEC-99). O Threads é da Meta e mora no mesmo aplicativo, mas a
 * janela de autorização é em `threads.net`, o servidor é `graph.threads.net`, os
 * escopos são `threads_*` e o modelo de token é outro. Conectar o Instagram
 * **não acende** o Threads.
 *
 * ⚠️ **O token daqui morre de vez.** Ele vale 60 dias e pode ser renovado —
 * mas só entre as 24 horas de idade e o vencimento. Passou dos 60 sem renovar,
 * não existe renovação: só reconectar. É a única rede do produto com morte
 * definitiva por inatividade, e é por isso que a renovação entra no comando
 * diário (DEC-102).
 */
class ConexaoComThreads
{
    private const AUTORIZAR = 'https://threads.net/oauth/authorize';

    private const TOKEN = 'https://graph.threads.net/oauth/access_token';

    private const TROCA_LONGA = 'https://graph.threads.net/access_token';

    private const PERFIL = 'https://graph.threads.net/v1.0/me';

    /**
     * ⚠️ `threads_basic` é exigido em **todo** endpoint, inclusive nos de
     * publicação — pedir só o de publicar deixa a conexão inútil.
     *
     * ⛔ Não pedimos `threads_manage_replies` nem `threads_read_replies`: o
     * produto publica, não conversa. Escopo pedido e não usado é permissão que
     * a pessoa concede à toa e que a análise do aplicativo vai cobrar.
     */
    private const ESCOPOS = ['threads_basic', 'threads_content_publish'];

    /** Sem este, a conta conecta e não publica — a conexão não teria função. */
    private const ESCOPO_ESSENCIAL = 'threads_content_publish';

    public function enderecoDeAutorizacao(string $estado): string
    {
        return self::AUTORIZAR.'?'.http_build_query([
            'client_id' => config('services.threads.client_id'),
            'redirect_uri' => config('services.threads.redirect'),
            'response_type' => 'code',
            // ⚠️ A documentação aceita vírgula ou espaço; a vírgula é o que
            // aparece nos exemplos executáveis.
            'scope' => implode(',', self::ESCOPOS),
            'state' => $estado,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function conectar(string $codigo): ContaSocial
    {
        $curto = $this->trocarCodigoPorToken($codigo);
        $longo = $this->trocarPorTokenLongo($curto['access_token']);
        $perfil = $this->buscarPerfil($longo['access_token']);

        return DB::transaction(function () use ($longo, $perfil, $curto) {
            // ⛔ O canal já pode viver em outro grupo — gravar aqui atualizaria
            // o registro de lá e responderia "conectado" sem nada aparecer.
            CanalDeUmGrupoSo::garantir(Plataforma::Threads, $perfil['id'], 'threads');

            $conta = ContaSocial::updateOrCreate(
                [
                    'plataforma' => Plataforma::Threads,
                    'identificador_externo' => $perfil['id'],
                ],
                [
                    'nome_exibicao' => $perfil['nome'],
                    'avatar_url' => $perfil['avatar'],
                    'status' => StatusConta::Ativa,
                    'status_detalhe' => null,
                ]
            );

            $conta->credencial()->updateOrCreate([], [
                'access_token' => $longo['access_token'],
                /*
                 * ⛔ **Não existe token de renovação aqui.** O Threads renova o
                 * próprio token longo, apresentando ele mesmo — não há um
                 * segundo segredo guardado.
                 *
                 * ⚠️ Isso muda o significado de `expira_em` nesta rede: no
                 * YouTube ele é o prazo de um token que se renova sozinho e o
                 * `refresh_token` é o que importa; aqui ele é **o prazo de
                 * morte da conta**. Passou, e só reconectando.
                 */
                'refresh_token' => null,
                'expira_em' => now()->addSeconds((int) ($longo['expires_in'] ?? 0)),
                // ⚠️ Os escopos CONCEDIDOS, nunca os pedidos: a tela de
                // autorização deixa desmarcar permissão.
                'escopos' => $curto['escopos'],
            ]);

            RegistroDeSeguranca::registrar('rede_conectada', [
                'plataforma' => Plataforma::Threads->value,
                'conta_ulid' => $conta->ulid,
            ]);

            return $conta;
        });
    }

    /**
     * Código → token curto (1 hora).
     *
     * @return array{access_token: string, escopos: list<string>}
     *
     * @throws ValidationException
     */
    private function trocarCodigoPorToken(string $codigo): array
    {
        try {
            $resposta = Http::asForm()->timeout(20)->post(self::TOKEN, [
                'client_id' => config('services.threads.client_id'),
                'client_secret' => config('services.threads.client_secret'),
                'code' => $codigo,
                'grant_type' => 'authorization_code',
                'redirect_uri' => config('services.threads.redirect'),
            ]);
        } catch (ConnectionException $erro) {
            throw $this->naoRespondeu($erro);
        }

        if (! $resposta->successful() || ! $resposta->json('access_token')) {
            throw ValidationException::withMessages([
                'threads' => 'Não conseguimos concluir a autorização com o Threads. Tente de novo.',
            ]);
        }

        /*
         * ⭐ A conferência do escopo concedido, com a mesma régua do YouTube.
         *
         * ⚠️ A resposta traz `permissions` (uma lista), não a string `scope` do
         * padrão OAuth. Ler o campo errado devolveria lista vazia e recusaria
         * toda conexão válida.
         */
        $concedidos = (array) ($resposta->json('permissions') ?? []);

        if ($concedidos !== [] && ! in_array(self::ESCOPO_ESSENCIAL, $concedidos, true)) {
            throw ValidationException::withMessages([
                'threads' => 'Faltou autorizar a permissão de publicar. Conecte de novo e mantenha todas as opções marcadas.',
            ]);
        }

        return [
            'access_token' => (string) $resposta->json('access_token'),
            'escopos' => array_values($concedidos),
        ];
    }

    /**
     * ⭐ Token curto → longo, **na hora da conexão**.
     *
     * ⚠️ O curto vive **uma hora**. Guardá-lo seria uma conta que morre antes do
     * fim do expediente, e a troca depois é impossível: token vencido não vira
     * token longo.
     *
     * @return array{access_token: string, expires_in: int}
     *
     * @throws ValidationException
     */
    private function trocarPorTokenLongo(string $tokenCurto): array
    {
        try {
            $resposta = Http::timeout(20)->get(self::TROCA_LONGA, [
                'grant_type' => 'th_exchange_token',
                'client_secret' => config('services.threads.client_secret'),
                'access_token' => $tokenCurto,
            ]);
        } catch (ConnectionException $erro) {
            throw $this->naoRespondeu($erro);
        }

        if (! $resposta->successful() || ! $resposta->json('access_token')) {
            throw ValidationException::withMessages([
                'threads' => 'A autorização foi concedida, mas não conseguimos guardá-la. Tente conectar de novo.',
            ]);
        }

        return [
            'access_token' => (string) $resposta->json('access_token'),
            'expires_in' => (int) $resposta->json('expires_in', 0),
        ];
    }

    /**
     * Quem é a conta.
     *
     * ⚠️ O `id` daqui é o mesmo usado no endereço de publicação
     * (`POST /{threads-user-id}/threads`) — é ele que vira o
     * `identificador_externo`, e não o nome de usuário, que a pessoa troca.
     *
     * @return array{id: string, nome: string, avatar: ?string}
     *
     * @throws ValidationException
     */
    private function buscarPerfil(string $token): array
    {
        try {
            $resposta = Http::timeout(20)->get(self::PERFIL, [
                'fields' => 'id,username,threads_profile_picture_url',
                'access_token' => $token,
            ]);
        } catch (ConnectionException $erro) {
            throw $this->naoRespondeu($erro);
        }

        if (! $resposta->successful() || ! $resposta->json('id')) {
            throw ValidationException::withMessages([
                'threads' => 'Não conseguimos ler o perfil do Threads. Confira se a conta está ativa e tente de novo.',
            ]);
        }

        return [
            'id' => (string) $resposta->json('id'),
            'nome' => (string) ($resposta->json('username') ?: 'Threads'),
            'avatar' => $resposta->json('threads_profile_picture_url'),
        ];
    }

    /**
     * A rede nao respondeu — e a mensagem diz de qual problema se trata.
     *
     * ⚠️ Falha de certificado do servidor chega como a MESMA excecao de "a
     * internet oscilou", e tratar as duas igual manda a pessoa tentar de novo
     * para sempre contra algo que nunca passa sozinho. Quem separa isso e o
     * `FalhaDeConexao`, e ele vale para toda rede — inclusive esta.
     */
    private function naoRespondeu(ConnectionException $erro): ValidationException
    {
        return ValidationException::withMessages([
            'threads' => FalhaDeConexao::explicar($erro, 'Threads'),
        ]);
    }
}

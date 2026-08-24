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
 * Conecta o Pinterest — **um canal por QUADRO** (DEC-134).
 *
 * ⭐ Nenhuma outra rede tem esse "para onde": no YouTube o vídeo vai para o
 * canal, no X para o perfil. Aqui a conta tem N quadros, e todo Pin **precisa**
 * escolher um.
 *
 * ⚠️ O painel já sabe essa forma: `ConexaoComMeta` cria um canal por Página do
 * Facebook. Aqui é igual — a pessoa escolhe o quadro do mesmo jeito que escolhe
 * entre duas Páginas.
 */
class ConexaoComPinterest
{
    private const AUTORIZAR = 'https://www.pinterest.com/oauth/';

    public const API = 'https://api.pinterest.com/v5';

    /**
     * ⚠️ Quatro escopos, e cada um tem função:
     *
     * - `boards:read` acha os quadros — sem ele não há para onde publicar;
     * - `pins:write` fixa o Pin;
     * - `pins:read` **relê o Pin, que é a prova** (DEC-31);
     * - `user_accounts:read` diz de quem é a conta.
     *
     * ⛔ Fora, de propósito: `boards:write` (criar quadro na conta de alguém não
     * é nosso papel) e os `*_secret` (publicar onde ninguém vê contraria a
     * promessa do produto).
     */
    private const ESCOPOS = ['boards:read', 'pins:read', 'pins:write', 'user_accounts:read'];

    private const ESCOPO_ESSENCIAL = 'pins:write';

    public function enderecoDeAutorizacao(string $estado): string
    {
        return self::AUTORIZAR.'?'.http_build_query([
            'client_id' => config('services.pinterest.client_id'),
            'redirect_uri' => config('services.pinterest.redirect'),
            'response_type' => 'code',
            'scope' => implode(',', self::ESCOPOS),
            'state' => $estado,
        ]);
    }

    /**
     * @return list<ContaSocial> um canal por quadro
     *
     * @throws ValidationException
     */
    public function conectar(string $codigo): array
    {
        $token = $this->trocarCodigoPorToken($codigo);

        if ($token['escopos'] !== [] && ! in_array(self::ESCOPO_ESSENCIAL, $token['escopos'], true)) {
            throw ValidationException::withMessages([
                'pinterest' => 'Faltou autorizar a permissão de publicar. Conecte de novo e mantenha todas as opções marcadas.',
            ]);
        }

        $quadros = $this->buscarQuadros($token['access_token']);

        if ($quadros === []) {
            /*
             * ⛔ Conta sem quadro não tem para onde publicar — e dizer isso aqui
             * é melhor que conectar e a pessoa descobrir na hora de publicar,
             * com o vídeo já escolhido.
             */
            throw ValidationException::withMessages([
                'pinterest' => 'Esta conta do Pinterest não tem nenhum quadro público. '.
                    'Crie um quadro no Pinterest e conecte de novo — é nele que os Pins vão aparecer.',
            ]);
        }

        return DB::transaction(function () use ($token, $quadros) {
            $contas = [];

            foreach ($quadros as $quadro) {
                CanalDeUmGrupoSo::garantir(Plataforma::Pinterest, $quadro['id'], 'pinterest');

                $conta = ContaSocial::updateOrCreate(
                    [
                        'plataforma' => Plataforma::Pinterest,
                        // ⭐ O QUADRO é o identificador externo, não a conta: é
                        // ele o destino de verdade de um Pin.
                        'identificador_externo' => $quadro['id'],
                    ],
                    [
                        'nome_exibicao' => $quadro['nome'],
                        'avatar_url' => null,
                        'status' => StatusConta::Ativa,
                        'status_detalhe' => null,
                    ]
                );

                $conta->credencial()->updateOrCreate([], [
                    'access_token' => $token['access_token'],
                    'refresh_token' => $token['refresh_token'],
                    'expira_em' => $token['expires_in'] > 0 ? now()->addSeconds($token['expires_in']) : null,
                    'escopos' => $token['escopos'],
                ]);

                RegistroDeSeguranca::registrar('rede_conectada', [
                    'plataforma' => Plataforma::Pinterest->value,
                    'conta_ulid' => $conta->ulid,
                ]);

                $contas[] = $conta;
            }

            return $contas;
        });
    }

    /**
     * @return array{access_token: string, refresh_token: ?string, expires_in: int, escopos: list<string>}
     *
     * @throws ValidationException
     */
    private function trocarCodigoPorToken(string $codigo): array
    {
        try {
            $resposta = Http::asForm()
                // O Pinterest identifica o aplicativo pelo cabeçalho, como o X.
                ->withBasicAuth(
                    (string) config('services.pinterest.client_id'),
                    (string) config('services.pinterest.client_secret')
                )
                ->timeout(20)
                ->post(self::API.'/oauth/token', [
                    'grant_type' => 'authorization_code',
                    'code' => $codigo,
                    'redirect_uri' => config('services.pinterest.redirect'),
                ]);
        } catch (ConnectionException $erro) {
            throw $this->naoRespondeu($erro);
        }

        if (! $resposta->successful() || ! $resposta->json('access_token')) {
            throw ValidationException::withMessages([
                'pinterest' => 'Não conseguimos concluir a autorização com o Pinterest. Tente de novo.',
            ]);
        }

        return [
            'access_token' => (string) $resposta->json('access_token'),
            'refresh_token' => $resposta->json('refresh_token'),
            'expires_in' => (int) $resposta->json('expires_in', 0),
            'escopos' => array_values(array_filter(
                preg_split('/[\s,]+/', (string) $resposta->json('scope', '')) ?: []
            )),
        ];
    }

    /**
     * Os quadros da conta — cada um vira um canal do painel.
     *
     * @return list<array{id: string, nome: string}>
     *
     * @throws ValidationException
     */
    private function buscarQuadros(string $token): array
    {
        try {
            $resposta = Http::withToken($token)->timeout(20)
                ->get(self::API.'/boards', ['page_size' => 100]);
        } catch (ConnectionException $erro) {
            throw $this->naoRespondeu($erro);
        }

        if (! $resposta->successful()) {
            throw ValidationException::withMessages([
                'pinterest' => 'Não conseguimos ler os quadros do Pinterest. Confira se a conta está ativa e tente de novo.',
            ]);
        }

        $quadros = [];

        foreach ((array) $resposta->json('items', []) as $item) {
            $id = (string) ($item['id'] ?? '');

            if ($id === '') {
                continue;
            }

            $quadros[] = ['id' => $id, 'nome' => (string) ($item['name'] ?? 'Quadro')];
        }

        return $quadros;
    }

    private function naoRespondeu(ConnectionException $erro): ValidationException
    {
        return ValidationException::withMessages([
            'pinterest' => FalhaDeConexao::explicar($erro, 'Pinterest'),
        ]);
    }
}

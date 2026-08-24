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
 * Conecta uma conta do LinkedIn.
 *
 * ⛔ **O token daqui não se renova sozinho, e isso não é limitação do nosso
 * código** (DEC-112). A documentação é literal: *"Programmatic refresh tokens
 * are available for a limited set of partners."* Sem ser parceiro aprovado, a
 * renovação passa pelo navegador da pessoa.
 *
 * ⚠️ Por isso `expira_em` significa aqui **a data em que a pessoa vai precisar
 * reconectar** — não o prazo de um trabalho nosso de madrugada. Não existe
 * `RenovarTokensDoLinkedin`, e escrever um seria pior que não ter: a conexão
 * morreria em silêncio com um serviço verde dizendo que está tudo bem.
 */
class ConexaoComLinkedin
{
    private const AUTORIZAR = 'https://www.linkedin.com/oauth/v2/authorization';

    private const TOKEN = 'https://www.linkedin.com/oauth/v2/accessToken';

    /** O perfil sai do OpenID Connect — o `r_liteprofile` antigo saiu de cena. */
    private const PERFIL = 'https://api.linkedin.com/v2/userinfo';

    /**
     * ⚠️ As TRÊS permissões abertas a qualquer desenvolvedor são `profile`,
     * `email` e `w_member_social`. Pedimos duas.
     *
     * ⛔ `email` fica de fora: o produto não manda e-mail em nome de ninguém, e
     * escopo pedido e não usado é permissão que a pessoa concede à toa.
     *
     * ⚠️ `openid` acompanha `profile` — sem ele o `userinfo` não responde, e o
     * URN da pessoa sai de lá.
     */
    private const ESCOPOS = ['openid', 'profile', 'w_member_social'];

    /** Sem este, a conta conecta e não publica — a conexão não teria função. */
    private const ESCOPO_ESSENCIAL = 'w_member_social';

    public function enderecoDeAutorizacao(string $estado): string
    {
        return self::AUTORIZAR.'?'.http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.linkedin.client_id'),
            'redirect_uri' => config('services.linkedin.redirect'),
            'state' => $estado,
            // ⚠️ Separado por ESPAÇO, ao contrário da Meta, que usa vírgula.
            'scope' => implode(' ', self::ESCOPOS),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function conectar(string $codigo): ContaSocial
    {
        $token = $this->trocarCodigoPorToken($codigo);
        $perfil = $this->buscarPerfil($token['access_token']);

        return DB::transaction(function () use ($token, $perfil) {
            // ⛔ O canal já pode viver em outro grupo — gravar aqui atualizaria
            // o registro de lá e responderia "conectado" sem nada aparecer.
            CanalDeUmGrupoSo::garantir(Plataforma::Linkedin, $perfil['id'], 'linkedin');

            $conta = ContaSocial::updateOrCreate(
                [
                    'plataforma' => Plataforma::Linkedin,
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
                'access_token' => $token['access_token'],
                /*
                 * ⛔ **Guardar o `refresh_token` daqui seria guardar uma
                 * promessa falsa.** Ele só vem para parceiros aprovados; para
                 * todo mundo, a resposta nem traz o campo. Um serviço que
                 * tentasse usá-lo falharia calado toda madrugada.
                 */
                'refresh_token' => null,
                'expira_em' => now()->addSeconds((int) ($token['expires_in'] ?? 0)),
                // ⚠️ Os escopos CONCEDIDOS, nunca os pedidos: a tela de
                // autorização deixa desmarcar permissão.
                'escopos' => $token['escopos'],
            ]);

            RegistroDeSeguranca::registrar('rede_conectada', [
                'plataforma' => Plataforma::Linkedin->value,
                'conta_ulid' => $conta->ulid,
            ]);

            return $conta;
        });
    }

    /**
     * ⭐ O URN que a API exige, montado num lugar só.
     *
     * `author` e `owner` não aceitam o identificador cru: querem
     * `urn:li:person:{sub}`. Montar isso espalhado pelo publicador é como se
     * perde um `urn:li:` no meio e a rede devolve `INVALID_URN_TYPE`.
     */
    public static function urnDaPessoa(string $identificadorExterno): string
    {
        return str_starts_with($identificadorExterno, 'urn:li:')
            ? $identificadorExterno
            : "urn:li:person:{$identificadorExterno}";
    }

    /**
     * Código → token de 60 dias.
     *
     * @return array{access_token: string, expires_in: int, escopos: list<string>}
     *
     * @throws ValidationException
     */
    private function trocarCodigoPorToken(string $codigo): array
    {
        try {
            $resposta = Http::asForm()->timeout(20)->post(self::TOKEN, [
                'grant_type' => 'authorization_code',
                'code' => $codigo,
                'client_id' => config('services.linkedin.client_id'),
                'client_secret' => config('services.linkedin.client_secret'),
                // ⚠️ Obrigatório também aqui, e tem que ser o MESMO do passo
                // anterior — diferente, a rede recusa com `invalid_redirect_uri`.
                'redirect_uri' => config('services.linkedin.redirect'),
            ]);
        } catch (ConnectionException $erro) {
            throw $this->naoRespondeu($erro);
        }

        if (! $resposta->successful() || ! $resposta->json('access_token')) {
            throw ValidationException::withMessages([
                'linkedin' => 'Não conseguimos concluir a autorização com o LinkedIn. Tente de novo.',
            ]);
        }

        /*
         * ⭐ Escopos CONCEDIDOS, com a mesma régua das outras redes.
         *
         * ⚠️ Aqui eles vêm como uma string separada por espaço — não é lista
         * como no Threads nem `scope` com vírgula. Ler no formato errado
         * devolveria lista vazia e recusaria toda conexão válida.
         */
        $concedidos = array_values(array_filter(
            preg_split('/[\s,]+/', (string) $resposta->json('scope', '')) ?: []
        ));

        if ($concedidos !== [] && ! in_array(self::ESCOPO_ESSENCIAL, $concedidos, true)) {
            throw ValidationException::withMessages([
                'linkedin' => 'Faltou autorizar a permissão de publicar. Conecte de novo e mantenha todas as opções marcadas.',
            ]);
        }

        return [
            'access_token' => (string) $resposta->json('access_token'),
            'expires_in' => (int) $resposta->json('expires_in', 0),
            'escopos' => $concedidos,
        ];
    }

    /**
     * Quem é a conta.
     *
     * ⭐ O `sub` é o que vira `identificador_externo` — e é dele que sai o
     * `urn:li:person:{sub}` de toda chamada de publicação.
     *
     * @return array{id: string, nome: string, avatar: ?string}
     *
     * @throws ValidationException
     */
    private function buscarPerfil(string $token): array
    {
        try {
            $resposta = Http::withToken($token)->timeout(20)->get(self::PERFIL);
        } catch (ConnectionException $erro) {
            throw $this->naoRespondeu($erro);
        }

        if (! $resposta->successful() || ! $resposta->json('sub')) {
            throw ValidationException::withMessages([
                'linkedin' => 'Não conseguimos ler o perfil do LinkedIn. Confira se a conta está ativa e tente de novo.',
            ]);
        }

        return [
            'id' => (string) $resposta->json('sub'),
            'nome' => (string) ($resposta->json('name') ?: 'LinkedIn'),
            'avatar' => $resposta->json('picture'),
        ];
    }

    /**
     * A rede nao respondeu — e a mensagem diz de qual problema se trata.
     *
     * ⚠️ Falha de certificado do servidor chega como a MESMA excecao de "a
     * internet oscilou", e tratar as duas igual manda a pessoa tentar de novo
     * para sempre contra algo que nunca passa sozinho.
     */
    private function naoRespondeu(ConnectionException $erro): ValidationException
    {
        return ValidationException::withMessages([
            'linkedin' => FalhaDeConexao::explicar($erro, 'LinkedIn'),
        ]);
    }
}

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
 * Conecta um canal do YouTube.
 *
 * Diferente do Bluesky (senha de aplicativo), aqui é OAuth: a pessoa autoriza
 * no site do Google e volta com um código. A senha dela nunca passa por aqui.
 */
class ConexaoComYoutube
{
    private const AUTORIZAR = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN = 'https://oauth2.googleapis.com/token';

    private const CANAIS = 'https://www.googleapis.com/youtube/v3/channels';

    /**
     * ⭐ DEC-41 — escopo mínimo.
     *
     * `youtube.upload` envia; `youtube.readonly` lê o canal e confere se o vídeo
     * subiu (a conciliação). **Não pedimos `youtube.force-ssl`**, que permitiria
     * apagar vídeos — é o medo documentado nas entrevistas, e não precisamos
     * disso para nada.
     */
    private const ESCOPOS = [
        'https://www.googleapis.com/auth/youtube.upload',
        'https://www.googleapis.com/auth/youtube.readonly',
    ];

    /**
     * O escopo sem o qual nao existe produto.
     *
     * O de leitura tambem importa (e a conciliacao), mas a falta dele degrada;
     * a falta deste impede.
     */
    private const ESCOPO_ESSENCIAL = 'https://www.googleapis.com/auth/youtube.upload';

    /** Para onde mandar a pessoa autorizar. */
    public function enderecoDeAutorizacao(string $estado): string
    {
        return self::AUTORIZAR.'?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'response_type' => 'code',
            'scope' => implode(' ', self::ESCOPOS),
            // `offline` + `consent` garantem o token de renovação. Sem eles, o
            // Google só manda o refresh na PRIMEIRA autorização — e quem
            // reconectar depois fica sem, quebrando em uma hora.
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $estado,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function conectar(string $codigo): ContaSocial
    {
        $token = $this->trocarCodigoPorToken($codigo);
        $canal = $this->buscarCanal($token['access_token']);

        return DB::transaction(function () use ($token, $canal) {
            // ⛔ Antes de gravar: o canal já pode viver em outro grupo, e aí
            // gravar aqui atualizaria o registro de lá e responderia "conectado"
            // sem nada aparecer neste grupo.
            CanalDeUmGrupoSo::garantir(Plataforma::Youtube, $canal['id'], 'youtube');

            $conta = ContaSocial::updateOrCreate(
                [
                    'plataforma' => Plataforma::Youtube,
                    'identificador_externo' => $canal['id'],
                ],
                [
                    'nome_exibicao' => $canal['nome'],
                    'avatar_url' => $canal['avatar'],
                    'status' => StatusConta::Ativa,
                    'status_detalhe' => null,
                ]
            );

            $conta->credencial()->updateOrCreate([], [
                'access_token' => $token['access_token'],
                // Sem o de renovação, a conexão morre em uma hora e ninguém
                // entende por quê. Melhor recusar agora, com instrução clara.
                'refresh_token' => $token['refresh_token'],
                'expira_em' => now()->addSeconds((int) ($token['expires_in'] ?? 3600)),
                // ⚠️ Os escopos CONCEDIDOS, nunca os pedidos. A tela do Google
                // deixa a pessoa desmarcar permissões, e a documentação é
                // literal: "seu aplicativo precisa verificar quais escopos foram
                // de fato concedidos". Gravar o que pedimos seria guardar uma
                // suposição no banco e descobrir a verdade só na hora do erro.
                'escopos' => $token['escopos'],
            ]);

            RegistroDeSeguranca::registrar('rede_conectada', [
                'plataforma' => Plataforma::Youtube->value,
                'conta_ulid' => $conta->ulid,
            ]);

            return $conta;
        });
    }

    /**
     * @return array{access_token: string, refresh_token: string, expires_in: int, escopos: list<string>}
     *
     * @throws ValidationException
     */
    private function trocarCodigoPorToken(string $codigo): array
    {
        try {
            $resposta = Http::asForm()->timeout(20)->post(self::TOKEN, [
                'code' => $codigo,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => config('services.google.redirect'),
                'grant_type' => 'authorization_code',
            ]);
        } catch (ConnectionException $erro) {
            // ⚠️ Falha de certificado NAO passa sozinha: dizer "tente de novo"
            // mandaria a pessoa insistir para sempre contra um `php.ini`.
            throw ValidationException::withMessages([
                'youtube' => FalhaDeConexao::explicar($erro, 'Google'),
            ]);
        }

        if (! $resposta->successful()) {
            throw ValidationException::withMessages([
                'youtube' => 'O Google não confirmou a autorização. Tente conectar de novo.',
            ]);
        }

        // ⚠️ A pessoa pode ter desmarcado permissões na tela do Google. Sem o
        // envio não há produto — e é melhor dizer isso agora do que deixar a
        // conta conectada e falhar no primeiro vídeo.
        $concedidos = array_values(array_filter(explode(' ', (string) $resposta->json('scope'))));

        if (! in_array(self::ESCOPO_ESSENCIAL, $concedidos, true)) {
            throw ValidationException::withMessages([
                'youtube' => 'A permissão de enviar vídeos não foi concedida. '.
                    'Conecte de novo e mantenha marcada a opção de enviar vídeos para o seu canal.',
            ]);
        }

        if (! $resposta->json('refresh_token')) {
            // Acontece quando a conta já autorizou antes e o Google não reenvia
            // o refresh. Sem ele, a conexão vira abóbora em uma hora.
            throw ValidationException::withMessages([
                'youtube' => 'O Google não devolveu a autorização de longo prazo. '.
                    'Remova o acesso deste aplicativo em myaccount.google.com/permissions e conecte de novo.',
            ]);
        }

        return [
            'access_token' => $resposta->json('access_token'),
            'refresh_token' => $resposta->json('refresh_token'),
            'expires_in' => (int) $resposta->json('expires_in', 3600),
            'escopos' => $concedidos,
        ];
    }

    /**
     * @return array{id: string, nome: string, avatar: string|null}
     *
     * @throws ValidationException
     */
    private function buscarCanal(string $token): array
    {
        $resposta = Http::withToken($token)->get(self::CANAIS, [
            'part' => 'snippet',
            'mine' => 'true',
        ]);

        // ⚠️ Falha da chamada NAO e "nao tem canal". Tratar as duas juntas
        // mandava a pessoa criar um canal que ela ja tem, por causa de uma
        // quota estourada ou da API desligada no projeto do Google Cloud.
        if (! $resposta->successful()) {
            throw ValidationException::withMessages([
                'youtube' => match ($resposta->status()) {
                    403 => 'O Google recusou a leitura do canal. Confira se a YouTube Data API v3 '.
                        'está ativada no projeto do Google Cloud e se a cota do dia não acabou.',
                    401 => 'A autorização do Google não foi aceita. Tente conectar de novo.',
                    default => 'Não conseguimos ler seu canal no YouTube agora. Tente de novo em instantes.',
                },
            ]);
        }

        $itens = $resposta->json('items', []);

        if ($itens === []) {
            // Conta Google sem canal criado — acontece bastante e a mensagem
            // precisa dizer o que fazer, não só que deu errado.
            throw ValidationException::withMessages([
                'youtube' => 'Essa conta do Google não tem um canal no YouTube. Crie o canal e conecte de novo.',
            ]);
        }

        return [
            'id' => $itens[0]['id'],
            'nome' => $itens[0]['snippet']['title'],
            'avatar' => $itens[0]['snippet']['thumbnails']['default']['url'] ?? null,
        ];
    }
}

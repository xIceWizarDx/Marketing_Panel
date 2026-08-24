<?php

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Models\ContaSocial;
use App\Models\Grupo;
use App\Publicadores\RegistroDePublicadores;
use App\Services\ConexaoComTiktok;
use App\Services\GrupoService;
use App\Services\TokenDoTiktok;
use App\Support\ContextoDoUsuario;
use App\Support\GrupoCorrente;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/*
| Guardiao da CONEXAO COM O TIKTOK (plano 23, DEC-118 e DEC-119).
|
| ⛔ Duas armadilhas moram aqui: o token vive 24 HORAS — o prazo mais curto do
| painel — e o `refresh_token` GIRA a cada renovacao.
*/

beforeEach(function () {
    ContextoDoUsuario::limpar();
    GrupoCorrente::esquecer();

    config([
        'services.tiktok.client_key' => 'chave-de-teste',
        'services.tiktok.client_secret' => 'segredo-de-teste',
        'services.tiktok.redirect' => 'https://painel.test/conexoes/tiktok/retorno',
    ]);
});

afterEach(function () {
    ContextoDoUsuario::limpar();
    GrupoCorrente::esquecer();
});

function donoDoTiktok(): void
{
    ContextoDoUsuario::definir(cliente());
    GrupoCorrente::definir(Grupo::firstOrFail());
}

/**
 * O caminho feliz — e a resposta da RENOVACAO no mesmo lugar.
 *
 * ⚠️ Conectar e renovar batem na MESMA URL, e o que os separa e o `grant_type`.
 * Chamar `Http::fake()` de novo depois NAO substitui o registro anterior: o
 * primeiro que casa vence, e a renovacao receberia a resposta da conexao. Foi
 * exatamente o que aconteceu na primeira escrita deste arquivo, e quatro testes
 * falharam apontando para o lugar errado.
 */
function tiktokAutorizando(?callable $aoRenovar = null, array $trocas = []): void
{
    Http::fake(array_merge([
        'open.tiktokapis.com/v2/oauth/token/' => function ($requisicao) use ($aoRenovar) {
            if (($requisicao['grant_type'] ?? '') === 'refresh_token') {
                return $aoRenovar
                    ? $aoRenovar($requisicao)
                    : Http::response(['access_token' => 'act.renovado', 'expires_in' => 86400]);
            }

            return Http::response([
                'access_token' => 'act.novo',
                'expires_in' => 86400,
                'open_id' => 'open-id-123',
                'refresh_expires_in' => 31536000,
                'refresh_token' => 'rft.primeiro',
                'scope' => 'user.info.basic,video.publish',
                'token_type' => 'Bearer',
            ]);
        },
        'open.tiktokapis.com/v2/user/info/*' => Http::response([
            'data' => ['user' => ['open_id' => 'open-id-123', 'display_name' => 'Gabriel', 'avatar_url' => 'https://x.test/a.jpg']],
        ]),
    ], $trocas));
}

describe('a janela de autorização', function () {
    it('⛔ manda `client_key`, NÃO `client_id`', function () {
        // ⚠️ O nome e diferente de toda outra rede, e mandar o errado devolve um
        // erro que nao diz qual parametro faltou.
        $endereco = app(ConexaoComTiktok::class)->enderecoDeAutorizacao('estado-123');

        expect($endereco)->toStartWith('https://www.tiktok.com/v2/auth/authorize/')
            ->and($endereco)->toContain('client_key=')
            ->and($endereco)->not->toContain('client_id=');
    });

    it('pede os escopos separados por VÍRGULA', function () {
        expect(urldecode(app(ConexaoComTiktok::class)->enderecoDeAutorizacao('e')))
            ->toContain('scope=user.info.basic,video.publish');
    });

    it('⛔ NÃO pede `video.upload` — é o outro fluxo, e ele quebra a promessa', function () {
        /*
         * ⛔ Com `video.upload` o video vai para a caixa de entrada do criador e
         * ele termina de postar DENTRO do aplicativo do TikTok. Sem publicacao
         * nossa nao ha post para reler, e a promessa do produto cai.
         */
        expect(app(ConexaoComTiktok::class)->enderecoDeAutorizacao('e'))
            ->not->toContain('video.upload');
    });
});

describe('⭐ os escopos aprovados vêm do retorno, no plural', function () {
    it('usa o `scopes` do retorno da autorização quando ele existe', function () {
        // ⚠️ `scopes`, nao `scope`. Ler o campo errado devolveria vazio e
        // recusaria toda conexao valida.
        donoDoTiktok();
        tiktokAutorizando();

        $conta = app(ConexaoComTiktok::class)->conectar('codigo', ['user.info.basic', 'video.publish']);

        expect($conta->credencial->escopos)->toBe(['user.info.basic', 'video.publish']);
    });

    it('⛔ sem a permissão de publicar, a conexão é recusada', function () {
        donoDoTiktok();
        tiktokAutorizando();

        expect(fn () => app(ConexaoComTiktok::class)->conectar('codigo', ['user.info.basic']))
            ->toThrow(ValidationException::class);
    });
});

describe('⛔ o token de 24 HORAS (DEC-118)', function () {
    it('⭐ guarda os dois tokens, e o prazo é mesmo de um dia', function () {
        donoDoTiktok();
        tiktokAutorizando();

        $conta = app(ConexaoComTiktok::class)->conectar('codigo');

        expect($conta->identificador_externo)->toBe('open-id-123')
            ->and($conta->nome_exibicao)->toBe('Gabriel')
            ->and($conta->status)->toBe(StatusConta::Ativa)
            ->and($conta->credencial->refresh_token)->toBe('rft.primeiro')
            ->and($conta->credencial->expira_em->diffInHours(now()->addDay()))->toBeLessThan(1);
    });

    it('⭐ renova quando falta menos de uma hora — não espera vencer', function () {
        /*
         * ⚠️ Um envio de video grande sobe em pedacos SEQUENCIAIS. Renovar so
         * nos ultimos minutos deixaria o envio comecar valido e terminar
         * vencido — caminho real aqui, nao teorico.
         */
        donoDoTiktok();
        tiktokAutorizando();
        $conta = app(ConexaoComTiktok::class)->conectar('codigo');

        $conta->credencial->forceFill(['expira_em' => now()->addMinutes(30)])->save();

        expect(app(TokenDoTiktok::class)->valido($conta->fresh('credencial')))->toBe('act.renovado');
    });

    it('token com folga NÃO é renovado à toa', function () {
        donoDoTiktok();
        tiktokAutorizando();
        $conta = app(ConexaoComTiktok::class)->conectar('codigo');

        Http::fake();

        expect(app(TokenDoTiktok::class)->valido($conta->fresh('credencial')))->toBe('act.novo');
        Http::assertNothingSent();
    });
});

describe('⛔ o `refresh_token` GIRA (DEC-119)', function () {
    it('⭐ o novo é GRAVADO — guardar o antigo quebraria a conexão um dia qualquer', function () {
        /*
         * ⛔ A documentacao avisa: "The returned refresh_token may be different
         * than the one passed in the payload". Continuar guardando o antigo da
         * uma conexao que funciona hoje, funciona amanha, e um dia para sem
         * ninguem ter mexido em nada — o pior tipo de defeito, porque nao tem
         * evento para investigar.
         */
        donoDoTiktok();
        tiktokAutorizando(fn () => Http::response([
            'access_token' => 'act.renovado', 'expires_in' => 86400, 'refresh_token' => 'rft.segundo',
        ]));
        $conta = app(ConexaoComTiktok::class)->conectar('codigo');
        $conta->credencial->forceFill(['expira_em' => now()->subMinute()])->save();

        app(TokenDoTiktok::class)->valido($conta->fresh('credencial'));

        expect($conta->fresh('credencial')->credencial->refresh_token)->toBe('rft.segundo');
    });

    it('e quando a rede não devolve um novo, o antigo é mantido', function () {
        // Sobrescrever com vazio apagaria o que ainda vale.
        donoDoTiktok();
        tiktokAutorizando();
        $conta = app(ConexaoComTiktok::class)->conectar('codigo');
        $conta->credencial->forceFill(['expira_em' => now()->subMinute()])->save();

        app(TokenDoTiktok::class)->valido($conta->fresh('credencial'));

        expect($conta->fresh('credencial')->credencial->refresh_token)->toBe('rft.primeiro');
    });

    it('⛔ rede fora do ar NÃO mata a conta — só falha esta tentativa', function () {
        // Marcar a conta como morta por um 5xx obrigaria a pessoa a reconectar
        // sem necessidade. Mesma licao que o Google ja custou.
        donoDoTiktok();
        tiktokAutorizando(fn () => Http::response([], 500));
        $conta = app(ConexaoComTiktok::class)->conectar('codigo');
        $conta->credencial->forceFill(['expira_em' => now()->subMinute()])->save();

        expect(app(TokenDoTiktok::class)->valido($conta->fresh('credencial')))->toBeNull()
            ->and($conta->fresh()->status)->toBe(StatusConta::Ativa);
    });

    it('⭐ autorização revogada, aí sim, marca a conta como vencida', function () {
        donoDoTiktok();
        tiktokAutorizando(fn () => Http::response([
            'error' => 'invalid_grant', 'error_description' => 'Refresh token is invalid',
        ], 400));
        $conta = app(ConexaoComTiktok::class)->conectar('codigo');
        $conta->credencial->forceFill(['expira_em' => now()->subMinute()])->save();

        app(TokenDoTiktok::class)->valido($conta->fresh('credencial'));

        expect($conta->fresh()->status)->toBe(StatusConta::Expirada)
            ->and($conta->fresh()->status_detalhe)->toContain('Reconecte');
    });
});

describe('⛔ um canal mora num grupo só', function () {
    it('conectar a mesma conta em outro grupo é recusado', function () {
        donoDoTiktok();
        tiktokAutorizando();

        $conta = app(ConexaoComTiktok::class)->conectar('codigo');
        $primeiro = $conta->grupo_id;

        GrupoCorrente::definir(app(GrupoService::class)->criar('Notícias'));

        expect(fn () => app(ConexaoComTiktok::class)->conectar('codigo'))
            ->toThrow(ValidationException::class);

        expect(ContaSocial::query()->where('identificador_externo', 'open-id-123')->first()->grupo_id)
            ->toBe($primeiro);
    });
});

describe('o botão de conectar', function () {
    it('só aparece com a credencial configurada', function () {
        expect(app(RegistroDePublicadores::class)->podeConectar(Plataforma::Tiktok))->toBeTrue();

        config(['services.tiktok.client_key' => null]);

        expect(app(RegistroDePublicadores::class)->podeConectar(Plataforma::Tiktok))->toBeFalse();
    });

    it('⚠️ NÃO exige endereço público — o arquivo SOBE, a rede não vem buscar', function () {
        // O `PULL_FROM_URL` existe, mas exige verificar a posse do dominio no
        // portal — passo manual por servidor, e o painel muda de endereco.
        config(['app.url' => 'http://localhost:8000']);

        expect(app(RegistroDePublicadores::class)->podeConectar(Plataforma::Tiktok))->toBeTrue();
    });

    it('as rotas existem e são separadas', function () {
        expect(Route::has('conexoes.tiktok'))->toBeTrue()
            ->and(Route::has('conexoes.tiktok.retorno'))->toBeTrue()
            ->and(route('conexoes.tiktok'))->not->toBe(route('conexoes.tiktok.retorno'));
    });
});

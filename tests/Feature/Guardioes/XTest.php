<?php

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Models\ContaSocial;
use App\Models\Grupo;
use App\Publicadores\RegistroDePublicadores;
use App\Services\ConexaoComX;
use App\Services\GrupoService;
use App\Services\TokenDoX;
use App\Support\ContextoDoUsuario;
use App\Support\GrupoCorrente;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/*
| Guardiao da CONEXAO COM O X (plano 24, DEC-128..131).
|
| ⛔ Tres coisas que so existem aqui: PKCE obrigatorio, codigo de autorizacao que
| vive 30 SEGUNDOS, e um token de 2 HORAS que morre de vez sem `offline.access`.
*/

beforeEach(function () {
    ContextoDoUsuario::limpar();
    GrupoCorrente::esquecer();

    config([
        'services.x.client_id' => 'id-de-teste',
        'services.x.client_secret' => 'segredo-de-teste',
        'services.x.redirect' => 'https://painel.test/conexoes/x/retorno',
    ]);
});

afterEach(function () {
    ContextoDoUsuario::limpar();
    GrupoCorrente::esquecer();
});

function donoDoX(): void
{
    ContextoDoUsuario::definir(cliente());
    GrupoCorrente::definir(Grupo::firstOrFail());
}

function xAutorizando(?callable $aoRenovar = null, array $trocas = []): void
{
    Http::fake(array_merge([
        // ⚠️ Conectar e renovar batem na MESMA URL: o que separa e o `grant_type`.
        'api.x.com/2/oauth2/token' => function ($requisicao) use ($aoRenovar) {
            if (($requisicao['grant_type'] ?? '') === 'refresh_token') {
                return $aoRenovar
                    ? $aoRenovar($requisicao)
                    : Http::response(['access_token' => 'act.renovado', 'expires_in' => 7200]);
            }

            return Http::response([
                'access_token' => 'act.novo',
                'refresh_token' => 'rft.primeiro',
                'expires_in' => 7200,
                'scope' => 'tweet.read tweet.write users.read media.write offline.access',
                'token_type' => 'bearer',
            ]);
        },
        'api.x.com/2/users/me*' => Http::response([
            'data' => ['id' => '4444', 'username' => 'gabriel', 'name' => 'Gabriel', 'profile_image_url' => 'https://x.test/a.jpg'],
        ]),
    ], $trocas));
}

describe('⛔ PKCE (DEC-129)', function () {
    it('⭐ o desafio é o SHA-256 do segredo, em base64url', function () {
        // ⚠️ base64url: sem `=`, com `-` e `_`. O base64 comum e recusado.
        $pkce = ConexaoComX::segredoDeIda();

        $esperado = rtrim(strtr(base64_encode(hash('sha256', $pkce['verificador'], true)), '+/', '-_'), '=');

        expect($pkce['desafio'])->toBe($esperado)
            ->and($pkce['desafio'])->not->toContain('=')
            ->and($pkce['desafio'])->not->toContain('+')
            ->and($pkce['desafio'])->not->toContain('/');
    });

    it('cada autorização gera um segredo novo', function () {
        expect(ConexaoComX::segredoDeIda()['verificador'])
            ->not->toBe(ConexaoComX::segredoDeIda()['verificador']);
    });

    it('⭐ o endereço leva o desafio e o método `S256`', function () {
        $endereco = app(ConexaoComX::class)->enderecoDeAutorizacao('estado-1', 'desafio-1');

        expect($endereco)->toStartWith('https://x.com/i/oauth2/authorize')
            ->and($endereco)->toContain('code_challenge=desafio-1')
            ->and($endereco)->toContain('code_challenge_method=S256');
    });

    it('⛔ o segredo é MANDADO na troca — sem ele a troca falha sem recuperação', function () {
        donoDoX();
        xAutorizando();

        app(ConexaoComX::class)->conectar('codigo', 'segredo-da-ida');

        Http::assertSent(fn ($r) => ! str_contains($r->url(), 'oauth2/token')
            || $r['code_verifier'] === 'segredo-da-ida');
    });
});

describe('⛔ os escopos', function () {
    it('⭐ pede `media.write` — é separado, e esquecer dá conta que não sobe vídeo', function () {
        // ⚠️ O sintoma engana: a conta conecta, o texto subiria, e o video nao.
        $endereco = urldecode(app(ConexaoComX::class)->enderecoDeAutorizacao('e', 'd'));

        expect($endereco)->toContain('media.write')
            ->and($endereco)->toContain('tweet.write')
            ->and($endereco)->toContain('tweet.read');
    });

    it('⭐ pede `offline.access` — sem ele NÃO existe token de renovação', function () {
        // Uma conexao sem esse escopo funciona por duas horas e morre.
        expect(urldecode(app(ConexaoComX::class)->enderecoDeAutorizacao('e', 'd')))
            ->toContain('offline.access');
    });

    it('⛔ sem `media.write` concedido, a conexão é recusada', function () {
        donoDoX();
        xAutorizando(trocas: ['api.x.com/2/oauth2/token' => Http::response([
            'access_token' => 'act', 'expires_in' => 7200,
            'scope' => 'tweet.read tweet.write users.read offline.access',
        ])]);

        expect(fn () => app(ConexaoComX::class)->conectar('codigo', 'v'))
            ->toThrow(ValidationException::class);
    });
});

describe('⛔ o token de 2 HORAS (DEC-130)', function () {
    it('⭐ guarda os dois tokens, e o prazo é mesmo de duas horas', function () {
        donoDoX();
        xAutorizando();

        $conta = app(ConexaoComX::class)->conectar('codigo', 'v');

        expect($conta->identificador_externo)->toBe('4444')
            // ⭐ O nome de USUARIO: e ele que monta o endereco do post.
            ->and($conta->nome_exibicao)->toBe('gabriel')
            ->and($conta->status)->toBe(StatusConta::Ativa)
            ->and($conta->credencial->refresh_token)->toBe('rft.primeiro')
            ->and($conta->credencial->expira_em->diffInMinutes(now()->addHours(2)))->toBeLessThan(2);
    });

    it('⭐ renova quando falta menos de vinte minutos', function () {
        // ⚠️ O envio em pedacos de 1 MB leva minutos, e o post so nasce depois:
        // comecar valido e terminar vencido e caminho real aqui.
        donoDoX();
        xAutorizando();
        $conta = app(ConexaoComX::class)->conectar('codigo', 'v');

        $conta->credencial->forceFill(['expira_em' => now()->addMinutes(10)])->save();

        expect(app(TokenDoX::class)->valido($conta->fresh('credencial')))->toBe('act.renovado');
    });

    it('token com folga NÃO é renovado à toa', function () {
        donoDoX();
        xAutorizando();
        $conta = app(ConexaoComX::class)->conectar('codigo', 'v');

        Http::fake();

        expect(app(TokenDoX::class)->valido($conta->fresh('credencial')))->toBe('act.novo');
        Http::assertNothingSent();
    });

    it('⭐ sem token de renovação, a frase manda manter as permissões marcadas', function () {
        /*
         * ⚠️ Aqui isto quase sempre quer dizer uma coisa so: a conexao foi feita
         * SEM `offline.access`. "Reconecte" sozinho faria a pessoa repetir o
         * mesmo erro.
         */
        donoDoX();
        xAutorizando();
        $conta = app(ConexaoComX::class)->conectar('codigo', 'v');
        $conta->credencial->forceFill(['refresh_token' => null, 'expira_em' => now()->subMinute()])->save();

        expect(app(TokenDoX::class)->valido($conta->fresh('credencial')))->toBeNull()
            ->and($conta->fresh()->status_detalhe)->toContain('permissões');
    });

    it('⛔ rede fora do ar NÃO mata a conta', function () {
        donoDoX();
        xAutorizando(fn () => Http::response([], 500));
        $conta = app(ConexaoComX::class)->conectar('codigo', 'v');
        $conta->credencial->forceFill(['expira_em' => now()->subMinute()])->save();

        expect(app(TokenDoX::class)->valido($conta->fresh('credencial')))->toBeNull()
            ->and($conta->fresh()->status)->toBe(StatusConta::Ativa);
    });

    it('autorização revogada marca a conta como vencida', function () {
        donoDoX();
        xAutorizando(fn () => Http::response(['error' => 'invalid_grant'], 400));
        $conta = app(ConexaoComX::class)->conectar('codigo', 'v');
        $conta->credencial->forceFill(['expira_em' => now()->subMinute()])->save();

        app(TokenDoX::class)->valido($conta->fresh('credencial'));

        expect($conta->fresh()->status)->toBe(StatusConta::Expirada);
    });
});

describe('⛔ um canal mora num grupo só', function () {
    it('conectar a mesma conta em outro grupo é recusado', function () {
        donoDoX();
        xAutorizando();

        $primeiro = app(ConexaoComX::class)->conectar('codigo', 'v')->grupo_id;

        GrupoCorrente::definir(app(GrupoService::class)->criar('Notícias'));

        expect(fn () => app(ConexaoComX::class)->conectar('codigo', 'v'))
            ->toThrow(ValidationException::class);

        expect(ContaSocial::query()->where('identificador_externo', '4444')->first()->grupo_id)
            ->toBe($primeiro);
    });
});

describe('o botão de conectar', function () {
    it('só aparece com a credencial configurada', function () {
        expect(app(RegistroDePublicadores::class)->podeConectar(Plataforma::X))->toBeTrue();

        config(['services.x.client_id' => null]);

        expect(app(RegistroDePublicadores::class)->podeConectar(Plataforma::X))->toBeFalse();
    });

    it('as rotas existem e são separadas', function () {
        expect(Route::has('conexoes.x'))->toBeTrue()
            ->and(Route::has('conexoes.x.retorno'))->toBeTrue()
            ->and(route('conexoes.x'))->not->toBe(route('conexoes.x.retorno'));
    });
});

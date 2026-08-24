<?php

use App\Enums\Plataforma;
use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Grupo;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Publicadores\PublicadorMastodon;
use App\Publicadores\RegistroDePublicadores;
use App\Publicadores\Retomada;
use App\Services\ConexaoComMastodon;
use App\Services\PublicacaoService;
use App\Support\ContextoDoUsuario;
use App\Support\GrupoCorrente;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/*
| Guardiao do MASTODON (plano 26, DEC-138..140).
|
| ⛔ Aqui nao existe "o Mastodon": existem milhares de servidores independentes.
| ⭐ E e a PRIMEIRA rede do painel que aceita chave de idempotencia.
*/

beforeEach(function () {
    ContextoDoUsuario::limpar();
    GrupoCorrente::esquecer();
    Storage::fake('local');
    config(['services.mastodon.redirect' => 'https://painel.test/conexoes/mastodon/retorno']);
});

afterEach(function () {
    ContextoDoUsuario::limpar();
    GrupoCorrente::esquecer();
});

describe('⛔ cada conta mora num SERVIDOR (DEC-138)', function () {
    it('⭐ o endereço digitado é limpo — a pessoa escreve de tudo', function () {
        /*
         * ⚠️ Recusar `https://masto.social/` ou `@masto.social` seria fazer
         * birra com quem acertou o servidor e errou a digitacao.
         */
        foreach ([
            'https://masto.social' => 'masto.social',
            'http://Masto.Social/' => 'masto.social',
            '@masto.social' => 'masto.social',
            'masto.social/@alguem' => 'masto.social',
            '  masto.social  ' => 'masto.social',
        ] as $bruto => $esperado) {
            expect(ConexaoComMastodon::normalizarServidor($bruto))->toBe($esperado, $bruto);
        }
    });

    it('⭐ o aplicativo é registrado NAQUELE servidor, sem autenticação', function () {
        /*
         * ⭐ E o que faz esta ser a unica rede do painel que conecta sem ninguem
         * criar conta de desenvolvedor em lugar algum (DEC-139).
         */
        Http::fake(['masto.social/api/v1/apps' => Http::response([
            'client_id' => 'cid', 'client_secret' => 'cs',
        ])]);

        $app = app(ConexaoComMastodon::class)->registrarAplicativo('masto.social');

        expect($app)->toBe(['client_id' => 'cid', 'client_secret' => 'cs']);

        Http::assertSent(fn ($r) => ! $r->hasHeader('Authorization'));
    });

    it('⛔ endereço que não é um Mastodon vira frase sobre o ENDEREÇO', function () {
        // Falar em "aplicativo" nao ajudaria quem digitou `mastodon.social.com`.
        Http::fake(['naoexiste.test/*' => Http::response([], 404)]);

        expect(fn () => app(ConexaoComMastodon::class)->registrarAplicativo('naoexiste.test'))
            ->toThrow(ValidationException::class);
    });

    it('⭐ o identificador carrega o servidor — dois servidores repetem números', function () {
        /*
         * ⛔ Sem isso, conectar `@ana@a.social` e `@joao@b.social` que tenham o
         * mesmo id no servidor delas daria UMA conta so no painel.
         */
        ContextoDoUsuario::definir(cliente());
        GrupoCorrente::definir(Grupo::firstOrFail());

        Http::fake([
            'masto.social/oauth/token' => Http::response(['access_token' => 'tok', 'scope' => 'write:statuses write:media read:accounts']),
            'masto.social/api/v1/accounts/verify_credentials' => Http::response(['id' => '7', 'username' => 'gabriel']),
        ]);

        $conta = app(ConexaoComMastodon::class)->conectar('masto.social', 'codigo', [
            'client_id' => 'cid', 'client_secret' => 'cs',
        ]);

        expect($conta->identificador_externo)->toBe('masto.social:7')
            ->and($conta->servidor)->toBe('masto.social')
            // ⭐ O identificador que a pessoa reconhece.
            ->and($conta->nome_exibicao)->toBe('@gabriel@masto.social')
            // ⛔ Token do Mastodon NAO vence: `null` aqui e a verdade, nao
            // informacao faltando.
            ->and($conta->credencial->expira_em)->toBeNull()
            ->and($conta->credencial->refresh_token)->toBeNull();
    });

    it('⛔ NÃO pede `read` inteiro — só o que publica precisa', function () {
        // `read` daria linha do tempo, notificacoes e mensagens diretas.
        $endereco = urldecode(app(ConexaoComMastodon::class)->enderecoDeAutorizacao('masto.social', 'cid', 'e'));

        expect($endereco)->toStartWith('https://masto.social/oauth/authorize')
            ->and($endereco)->toContain('write:statuses')
            ->and($endereco)->toContain('write:media')
            ->and($endereco)->toContain('read:accounts')
            ->and($endereco)->not->toContain('scope=read ');
    });

    it('⭐ conecta sem credencial nossa nenhuma', function () {
        expect(app(RegistroDePublicadores::class)->podeConectar(Plataforma::Mastodon))->toBeTrue();
    });
});

describe('⭐ a chave de idempotência muda a regra (DEC-140)', function () {
    it('⭐ o post leva `Idempotency-Key`, e ela é o ulid do destino', function () {
        /*
         * ⭐ No LinkedIn, no X e no Pinterest um tempo esgotado nos obriga a
         * PARAR e avisar, porque repetir criaria um segundo post. Aqui a chave
         * vale uma hora e repetir devolve o MESMO post.
         */
        $destino = destinoNoMastodon();
        $destino->forceFill(['handle_externo' => 'media-1'])->save();

        Http::fake([
            'masto.social/api/v1/media/*' => Http::response(['id' => 'media-1'], 200),
            'masto.social/api/v1/statuses' => Http::response(['id' => 'st-9', 'url' => 'https://masto.social/@g/st-9']),
        ]);

        $recarregado = $destino->fresh(['publicacao', 'contaSocial.credencial']);
        $resultado = app(PublicadorMastodon::class)->conciliar($recarregado);

        expect($resultado->noAr)->toBeTrue()
            ->and($resultado->url)->toBe('https://masto.social/@g/st-9');

        Http::assertSent(fn ($r) => ! str_contains($r->url(), '/statuses')
            || $r->header('Idempotency-Key') === [$recarregado->ulid]);
    });

    it('⭐ e por isso timeout ao publicar VOLTA para a fila — sem risco de duplicar', function () {
        // ⚠️ O contrario das outras redes, e de proposito: aqui a rede garante.
        $destino = destinoNoMastodon();
        $destino->forceFill(['handle_externo' => 'media-1'])->save();

        Http::fake([
            'masto.social/api/v1/media/*' => Http::response(['id' => 'media-1'], 200),
            'masto.social/api/v1/statuses' => fn () => throw new ConnectionException('timed out'),
        ]);

        expect(app(PublicadorMastodon::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']))->aindaProcessando)
            ->toBeTrue();
    });
});

describe('⛔ 206 é sucesso e NÃO quer dizer pronto', function () {
    it('⭐ mídia em 206 continua processando — publicar ali daria post sem vídeo', function () {
        /*
         * ⛔ 206 e um codigo de SUCESSO. Um motor que trate `successful()` como
         * pronto publicaria um post sem video, e o post ficaria la, vazio.
         */
        $destino = destinoNoMastodon();
        $destino->forceFill(['handle_externo' => 'media-1'])->save();

        Http::fake(['masto.social/api/v1/media/*' => Http::response(['id' => 'media-1'], 206)]);

        expect(app(PublicadorMastodon::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']))->aindaProcessando)
            ->toBeTrue();

        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/statuses'));
    });
});

describe('⛔ o limite é do SERVIDOR, não da rede', function () {
    it('⭐ a recusa nomeia o servidor', function () {
        /*
         * ⚠️ Um Mastodon aceita video de 40 MB, o vizinho aceita 200 MB. "O
         * Mastodon recusou" mandaria a pessoa procurar uma regra geral que nao
         * existe.
         */
        $destino = destinoNoMastodon();

        Http::fake(['masto.social/api/v2/media' => Http::response(['error' => 'File too large'], 422)]);

        $erro = app(PublicadorMastodon::class)->publicar($destino, retomadaNoMastodon($destino))->erro;

        expect($erro)->toContain('File too large');
    });

    it('⭐ e o publicador monta a URL a partir do servidor da CONTA', function () {
        // Nao existe endereco fixo neste publicador.
        $destino = destinoNoMastodon();

        Http::fake(['masto.social/api/v2/media' => Http::response(['id' => 'media-1'], 202)]);

        app(PublicadorMastodon::class)->publicar($destino, retomadaNoMastodon($destino));

        Http::assertSent(fn ($r) => str_starts_with($r->url(), 'https://masto.social/'));
    });
});

/** Um destino do Mastodon pronto para publicar. */
function destinoNoMastodon(): Destino
{
    $dono = cliente();
    ContextoDoUsuario::definir($dono);

    $midia = Midia::factory()->doUsuario($dono)->create(['tamanho_bytes' => 1024]);
    Storage::disk('local')->put($midia->caminho, str_repeat('v', 1024));

    $criada = Publicacao::factory()->doUsuario($dono)->enviada()->create([
        'midia_id' => $midia->id,
        'titulo' => null,
        'legenda' => 'Olha isso',
    ]);

    $conta = ContaSocial::factory()->doUsuario($dono)->doGrupo(Grupo::firstOrFail())
        ->daPlataforma(Plataforma::Mastodon)->comCredencial('tok')
        ->create([
            'identificador_externo' => 'masto.social:7',
            'servidor' => 'masto.social',
            'nome_exibicao' => '@gabriel@masto.social',
        ]);

    $destino = Destino::factory()->create([
        'publicacao_id' => $criada->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Enviando,
    ]);

    return $destino->fresh(['publicacao.midia', 'contaSocial.credencial']);
}

function retomadaNoMastodon(Destino $destino): Retomada
{
    return new Retomada($destino, app(PublicacaoService::class));
}

describe('⛔ nem todo "não é 200" é espera', function () {
    it('⭐ mídia que sumiu é FALHA, não três horas de espera', function () {
        /*
         * ⛔ Devolver "ainda processando" para tudo fazia a conciliacao insistir
         * vinte vezes contra um erro definitivo — e o desfecho, tres horas
         * depois, era a frase generica "a rede aceitou mas nao confirmou", que
         * nao diz nada sobre a causa.
         */
        $destino = destinoNoMastodon();
        $destino->forceFill(['handle_externo' => 'media-1'])->save();

        Http::fake(['masto.social/api/v1/media/*' => Http::response([], 404)]);

        $resultado = app(PublicadorMastodon::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']));

        expect($resultado->aindaProcessando)->toBeFalse()
            ->and($resultado->erro)->toContain('não encontra mais');
    });

    it('⭐ e token revogado manda RECONECTAR', function () {
        $destino = destinoNoMastodon();
        $destino->forceFill(['handle_externo' => 'media-1'])->save();

        Http::fake(['masto.social/api/v1/media/*' => Http::response([], 401)]);

        expect(app(PublicadorMastodon::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']))->erro)
            ->toContain('Reconecte');
    });
});

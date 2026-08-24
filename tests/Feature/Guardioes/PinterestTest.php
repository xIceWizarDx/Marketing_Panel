<?php

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Grupo;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Publicadores\PublicadorPinterest;
use App\Publicadores\RegistroDePublicadores;
use App\Publicadores\Retomada;
use App\Services\ConexaoComPinterest;
use App\Services\PublicacaoService;
use App\Support\ContextoDoUsuario;
use App\Support\GrupoCorrente;
use App\Support\Midia\EspecificacaoDaRede;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/*
| Guardiao do PINTEREST (plano 25, DEC-134..137).
|
| ⭐ Duas coisas so existem aqui: o Pin mora num QUADRO, e o arquivo sobe para a
| AMAZON — nao para o Pinterest.
*/

beforeEach(function () {
    ContextoDoUsuario::limpar();
    GrupoCorrente::esquecer();
    Storage::fake('local');

    config([
        'services.pinterest.client_id' => 'id-de-teste',
        'services.pinterest.client_secret' => 'segredo-de-teste',
        'services.pinterest.redirect' => 'https://painel.test/conexoes/pinterest/retorno',
    ]);
});

afterEach(function () {
    ContextoDoUsuario::limpar();
    GrupoCorrente::esquecer();
});

function pinterestRespondendo(array $trocas = []): void
{
    Http::fake(array_merge([
        'api.pinterest.com/v5/oauth/token' => Http::response([
            'access_token' => 'act.novo',
            'refresh_token' => 'rft.novo',
            'expires_in' => 2592000,
            'scope' => 'boards:read,pins:read,pins:write,user_accounts:read',
        ]),
        'api.pinterest.com/v5/boards*' => Http::response(['items' => [
            ['id' => '111', 'name' => 'Cortes'],
            ['id' => '222', 'name' => 'Receitas'],
        ]]),
    ], $trocas));
}

describe('⭐ um canal por QUADRO (DEC-134)', function () {
    it('conectar traz um canal para cada quadro, não um só', function () {
        /*
         * ⛔ Nenhuma outra rede tem esse "para onde": no YouTube o video vai
         * para o canal, no X para o perfil. Aqui a conta tem N quadros, e todo
         * Pin PRECISA escolher um.
         */
        ContextoDoUsuario::definir(cliente());
        GrupoCorrente::definir(Grupo::firstOrFail());
        pinterestRespondendo();

        $contas = app(ConexaoComPinterest::class)->conectar('codigo');

        expect($contas)->toHaveCount(2)
            // ⭐ O QUADRO e o identificador externo, nao a conta.
            ->and($contas[0]->identificador_externo)->toBe('111')
            ->and($contas[0]->nome_exibicao)->toBe('Cortes')
            ->and($contas[1]->identificador_externo)->toBe('222')
            ->and($contas[0]->status)->toBe(StatusConta::Ativa);
    });

    it('⛔ conta sem quadro é recusada NA CONEXÃO, não na hora de publicar', function () {
        // Deixar conectar seria a pessoa descobrir que nao ha para onde
        // publicar com o video ja escolhido.
        ContextoDoUsuario::definir(cliente());
        GrupoCorrente::definir(Grupo::firstOrFail());
        pinterestRespondendo(['api.pinterest.com/v5/boards*' => Http::response(['items' => []])]);

        expect(fn () => app(ConexaoComPinterest::class)->conectar('codigo'))
            ->toThrow(ValidationException::class);
    });

    it('⛔ NÃO pede permissão de quadro secreto nem de criar quadro', function () {
        /*
         * ⚠️ Publicar em quadro secreto e publicar onde ninguem ve — contraria
         * a promessa. E criar quadro na conta de alguem nao e nosso papel.
         */
        $endereco = urldecode(app(ConexaoComPinterest::class)->enderecoDeAutorizacao('e'));

        expect($endereco)->toContain('boards:read')
            ->and($endereco)->toContain('pins:write')
            // ⭐ `pins:read` e a PROVA.
            ->and($endereco)->toContain('pins:read')
            ->and($endereco)->not->toContain('secret')
            ->and($endereco)->not->toContain('boards:write');
    });
});

describe('⛔ o arquivo sobe para a AMAZON (DEC-135)', function () {
    it('⭐ todos os parâmetros assinados vão ANTES do arquivo', function () {
        /*
         * ⛔ Formulario assinado do S3 ignora o que vier DEPOIS do campo
         * `file`. Mandar `key` ou `policy` no fim faz a Amazon recusar com um
         * erro de XML que nao menciona ordem nenhuma.
         */
        $destino = destinoNoPinterest();

        Http::fake([
            'api.pinterest.com/v5/media' => Http::response([
                'media_id' => 'media-1',
                'upload_url' => 'https://pinterest-media-upload.s3-accelerate.amazonaws.com/',
                'upload_parameters' => ['key' => 'uploads/1', 'policy' => 'abc', 'x-amz-signature' => 'sig'],
            ]),
            '*amazonaws.com*' => Http::response('', 204),
        ]);

        app(PublicadorPinterest::class)->publicar($destino, retomadaNoPinterest($destino));

        Http::assertSent(function ($r) {
            if (! str_contains($r->url(), 'amazonaws.com')) {
                return false;
            }

            $nomes = array_column($r->data(), 'name');

            // ⭐ `file` e o ULTIMO, e todos os assinados vieram antes.
            expect($nomes)->toBe(['key', 'policy', 'x-amz-signature', 'file']);

            return true;
        });
    });

    it('⛔ e o token do Pinterest NÃO vai junto para a Amazon', function () {
        // ⚠️ Quem autoriza la e a assinatura que veio nos parametros. Mandar o
        // nosso `Authorization` para a Amazon e pedir 403.
        $destino = destinoNoPinterest();

        Http::fake([
            'api.pinterest.com/v5/media' => Http::response([
                'media_id' => 'media-1',
                'upload_url' => 'https://pinterest-media-upload.s3-accelerate.amazonaws.com/',
                'upload_parameters' => ['key' => 'uploads/1'],
            ]),
            '*amazonaws.com*' => Http::response('', 204),
        ]);

        app(PublicadorPinterest::class)->publicar($destino, retomadaNoPinterest($destino));

        Http::assertSent(fn ($r) => ! str_contains($r->url(), 'amazonaws.com')
            || ! $r->hasHeader('Authorization'));
    });

    it('o envio termina sem fixar Pin nenhum', function () {
        $destino = destinoNoPinterest();

        Http::fake([
            'api.pinterest.com/v5/media' => Http::response([
                'media_id' => 'media-1', 'upload_url' => 'https://x.amazonaws.com/', 'upload_parameters' => [],
            ]),
            '*amazonaws.com*' => Http::response('', 204),
        ]);

        $resultado = app(PublicadorPinterest::class)->publicar($destino, retomadaNoPinterest($destino));

        expect($resultado->aceito)->toBeTrue()
            ->and($resultado->identificadorExterno)->toBe('media-1');

        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/v5/pins'));
    });
});

describe('⛔ o Pin nasce na conciliação, no quadro do canal', function () {
    it('⭐ `succeeded` → fixa o Pin, e o `board_id` é o do CANAL', function () {
        $destino = destinoNoPinterest();
        $destino->forceFill(['handle_externo' => 'media-1'])->save();

        Http::fake([
            'api.pinterest.com/v5/media/*' => Http::response(['status' => 'succeeded']),
            'api.pinterest.com/v5/pins' => Http::response(['id' => 'pin-999']),
        ]);

        $resultado = app(PublicadorPinterest::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']));

        expect($resultado->noAr)->toBeTrue()
            ->and($resultado->url)->toBe('https://www.pinterest.com/pin/pin-999/');

        Http::assertSent(function ($r) {
            if (! str_contains($r->url(), '/v5/pins')) {
                return false;
            }

            expect($r['board_id'])->toBe('111')
                // ⭐ Titulo tem campo PROPRIO aqui, separado da descricao.
                ->and($r['title'])->toBe('Meu corte')
                ->and($r['description'])->toContain('Olha isso')
                // ⭐ A capa sai de um QUADRO do proprio video (DEC-136).
                ->and($r['media_source']['cover_image_key_frame_time'])->toBe(1)
                ->and($r['media_source']['source_type'])->toBe('video_id');

            return true;
        });
    });

    it('enquanto processa, não fixa e não falha', function () {
        $destino = destinoNoPinterest();
        $destino->forceFill(['handle_externo' => 'media-1'])->save();

        Http::fake(['api.pinterest.com/v5/media/*' => Http::response(['status' => 'processing'])]);

        expect(app(PublicadorPinterest::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']))->aindaProcessando)
            ->toBeTrue();
    });

    it('⭐ timeout ao fixar PARA e avisa, em vez de fixar duas vezes', function () {
        // Mesma razao do LinkedIn (DEC-125) e do X: nao e idempotente, e a
        // conciliacao roda vinte vezes.
        $destino = destinoNoPinterest();
        $destino->forceFill(['handle_externo' => 'media-1'])->save();

        Http::fake([
            'api.pinterest.com/v5/media/*' => Http::response(['status' => 'succeeded']),
            'api.pinterest.com/v5/pins' => fn () => throw new ConnectionException('timed out'),
        ]);

        $resultado = app(PublicadorPinterest::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']));

        expect($resultado->aindaProcessando)->toBeFalse()
            ->and($resultado->erro)->toContain('pode ter subido');
    });

    it('⭐ Pin já fixado é RELIDO — é a prova (DEC-31)', function () {
        $destino = destinoNoPinterest();
        $destino->forceFill(['handle_externo' => 'media-1', 'identificador_externo' => 'pin-999'])->save();

        Http::fake(['api.pinterest.com/v5/pins/pin-999' => Http::response(['id' => 'pin-999'])]);

        expect(app(PublicadorPinterest::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']))->noAr)
            ->toBeTrue();
    });

    it('Pin que sumiu é dito pelo que é', function () {
        $destino = destinoNoPinterest();
        $destino->forceFill(['handle_externo' => 'media-1', 'identificador_externo' => 'pin-999'])->save();

        Http::fake(['api.pinterest.com/v5/pins/pin-999' => Http::response([], 404)]);

        expect(app(PublicadorPinterest::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']))->erro)
            ->toContain('não está mais');
    });
});

describe('a especificação', function () {
    it('⭐ os limites de texto vêm da SPEC oficial', function () {
        // ⚠️ Titulo 100 e descricao 800 sao os unicos numeros que a fonte
        // declara. Duracao e tamanho sao do perfil canonico, nao dela.
        $spec = EspecificacaoDaRede::de(Plataforma::Pinterest);

        expect($spec->texto->titulo)->toBe(100)
            ->and($spec->texto->legenda)->toBe(800)
            // ⛔ Tem campo de titulo proprio: NAO soma na legenda.
            ->and($spec->texto->tituloEntraNaLegenda)->toBeFalse();
    });

    it('o botão só aparece com a credencial configurada', function () {
        expect(app(RegistroDePublicadores::class)->podeConectar(Plataforma::Pinterest))->toBeTrue();

        config(['services.pinterest.client_id' => null]);

        expect(app(RegistroDePublicadores::class)->podeConectar(Plataforma::Pinterest))->toBeFalse();
    });
});

/** Um destino do Pinterest pronto para publicar. */
function destinoNoPinterest(): Destino
{
    $dono = cliente();
    ContextoDoUsuario::definir($dono);

    $midia = Midia::factory()->doUsuario($dono)->create(['tamanho_bytes' => 1024]);
    Storage::disk('local')->put($midia->caminho, str_repeat('v', 1024));

    $criada = Publicacao::factory()->doUsuario($dono)->enviada()->create([
        'midia_id' => $midia->id,
        'titulo' => 'Meu corte',
        'legenda' => 'Olha isso',
    ]);

    $conta = ContaSocial::factory()->doUsuario($dono)->doGrupo(Grupo::firstOrFail())
        ->daPlataforma(Plataforma::Pinterest)->comCredencial('act.novo')
        // ⭐ O identificador externo e o QUADRO.
        ->create(['identificador_externo' => '111', 'nome_exibicao' => 'Cortes']);

    $destino = Destino::factory()->create([
        'publicacao_id' => $criada->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Enviando,
    ]);

    return $destino->fresh(['publicacao.midia', 'contaSocial.credencial']);
}

function retomadaNoPinterest(Destino $destino): Retomada
{
    return new Retomada($destino, app(PublicacaoService::class));
}

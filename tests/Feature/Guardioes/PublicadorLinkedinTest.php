<?php

use App\Enums\Plataforma;
use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Grupo;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Publicadores\PublicadorLinkedin;
use App\Publicadores\Retomada;
use App\Services\PublicacaoService;
use App\Support\ContextoDoUsuario;
use App\Support\Midia\EspecificacaoDaRede;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/*
| Guardiao do PUBLICADOR DO LINKEDIN (plano 22).
|
| ⚠️ Esta rede tem tres particularidades que mudam o motor, e cada teste aqui
| trava uma delas: o arquivo sobe em PEDACOS com recibo, o identificador do post
| vem no CABECALHO, e a prova da DEC-31 nao existe inteira (DEC-106).
*/

beforeEach(function () {
    ContextoDoUsuario::limpar();
    Storage::fake('local');
});

afterEach(fn () => ContextoDoUsuario::limpar());

/** Um destino do LinkedIn pronto para publicar. */
function destinoNoLinkedin(int $bytes = 1024): Destino
{
    $dono = cliente();
    ContextoDoUsuario::definir($dono);

    $midia = Midia::factory()->doUsuario($dono)->create(['tamanho_bytes' => $bytes]);
    Storage::disk('local')->put($midia->caminho, str_repeat('v', $bytes));

    $criada = Publicacao::factory()->doUsuario($dono)->enviada()->create([
        'midia_id' => $midia->id,
        'titulo' => 'Meu corte',
        'legenda' => 'Olha isso',
    ]);

    $conta = ContaSocial::factory()->doUsuario($dono)->doGrupo(Grupo::firstOrFail())
        ->daPlataforma(Plataforma::Linkedin)->comCredencial('token-de-60-dias')
        ->create(['identificador_externo' => 'ABC123']);

    $destino = Destino::factory()->create([
        'publicacao_id' => $criada->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Enviando,
    ]);

    // ⚠️ O contexto FICA definido: o publicador roda dentro de um job que ja o
    // definiu (0.M).
    return $destino->fresh(['publicacao.midia', 'contaSocial.credencial']);
}

function retomadaNoLinkedin(Destino $destino): Retomada
{
    return new Retomada($destino, app(PublicacaoService::class));
}

/** A resposta de `initializeUpload` com N pedacos de 4 MiB. */
function inicioDoEnvio(int $pedacos = 1, int $ultimoByte = 1023): array
{
    $instrucoes = [];

    for ($i = 0; $i < $pedacos; $i++) {
        $instrucoes[] = [
            'uploadUrl' => "https://www.linkedin.com/dms-uploads/parte-{$i}",
            'firstByte' => $i * 4194304,
            'lastByte' => $i === $pedacos - 1 ? $ultimoByte : (($i + 1) * 4194304) - 1,
        ];
    }

    return ['value' => [
        'video' => 'urn:li:video:C5505AQH',
        'uploadToken' => '',
        'uploadInstructions' => $instrucoes,
        'uploadUrlsExpireAt' => 1633234498985,
    ]];
}

describe('⭐ o envio em pedaços, com recibo (DEC-109 e DEC-110)', function () {
    it('⛔ o tamanho do pedaço sai do `firstByte`/`lastByte` da rede, não do exemplo da documentação', function () {
        /*
         * ⛔ A documentacao manda `split -b 4194303` e devolve o intervalo
         * `0`-`4194303`, que INCLUSIVE da 4.194.304 bytes. Os dois nao fecham.
         * Seguir o exemplo deixaria cada pedaco um byte curto, e o erro so
         * apareceria em arquivo grande, com o video montado errado no fim.
         */
        $destino = destinoNoLinkedin(bytes: 100);

        Http::fake([
            'api.linkedin.com/rest/videos?action=initializeUpload' => Http::response(
                ['value' => [
                    'video' => 'urn:li:video:C5505AQH',
                    'uploadToken' => '',
                    // A rede pediu do byte 10 ao 49 — quarenta bytes, nem um a mais.
                    'uploadInstructions' => [
                        ['uploadUrl' => 'https://www.linkedin.com/dms-uploads/p0', 'firstByte' => 10, 'lastByte' => 49],
                    ],
                ]]
            ),
            'www.linkedin.com/dms-uploads/*' => Http::response('', 200, ['etag' => '"recibo-0"']),
            'api.linkedin.com/rest/videos?action=finalizeUpload' => Http::response([], 200),
        ]);

        app(PublicadorLinkedin::class)->publicar($destino, retomadaNoLinkedin($destino));

        Http::assertSent(function ($requisicao) {
            if (! str_contains($requisicao->url(), 'dms-uploads')) {
                return false;
            }

            expect(strlen($requisicao->body()))->toBe(40);

            return true;
        });
    });

    it('⭐ os recibos vão na ORDEM dos pedaços — fora de ordem o vídeo monta embaralhado', function () {
        $destino = destinoNoLinkedin(bytes: 8388608 + 10);

        $recibo = 0;

        Http::fake([
            'api.linkedin.com/rest/videos?action=initializeUpload' => Http::response(
                inicioDoEnvio(pedacos: 3, ultimoByte: 8388617)
            ),
            'www.linkedin.com/dms-uploads/*' => function () use (&$recibo) {
                return Http::response('', 200, ['etag' => '"recibo-'.$recibo++.'"']);
            },
            'api.linkedin.com/rest/videos?action=finalizeUpload' => Http::response([], 200),
        ]);

        app(PublicadorLinkedin::class)->publicar($destino, retomadaNoLinkedin($destino));

        Http::assertSent(function ($requisicao) {
            if (! str_contains($requisicao->url(), 'finalizeUpload')) {
                return false;
            }

            // ⛔ Nada na resposta avisa se a ordem trocou. So este teste avisa.
            expect($requisicao['finalizeUploadRequest']['uploadedPartIds'])
                ->toBe(['recibo-0', 'recibo-1', 'recibo-2']);

            return true;
        });
    });

    it('as aspas do ETag não entram no recibo', function () {
        // `etag: "abc"` com aspas vira `abc` — mandar com aspas faz a rede
        // recusar a montagem, e a mensagem nao diz por que.
        $destino = destinoNoLinkedin();

        Http::fake([
            'api.linkedin.com/rest/videos?action=initializeUpload' => Http::response(inicioDoEnvio()),
            'www.linkedin.com/dms-uploads/*' => Http::response('', 200, ['etag' => '"com-aspas"']),
            'api.linkedin.com/rest/videos?action=finalizeUpload' => Http::response([], 200),
        ]);

        app(PublicadorLinkedin::class)->publicar($destino, retomadaNoLinkedin($destino));

        Http::assertSent(fn ($r) => ! str_contains($r->url(), 'finalizeUpload')
            || $r['finalizeUploadRequest']['uploadedPartIds'] === ['com-aspas']);
    });

    it('⭐ o URN do vídeo é guardado ANTES do primeiro byte (DEC-108)', function () {
        // Se o processo morrer no meio do envio, e ele que impede a proxima
        // tentativa de criar um SEGUNDO video.
        $destino = destinoNoLinkedin();

        Http::fake([
            'api.linkedin.com/rest/videos?action=initializeUpload' => Http::response(inicioDoEnvio()),
            'www.linkedin.com/dms-uploads/*' => Http::response('', 500),
        ]);

        app(PublicadorLinkedin::class)->publicar($destino, retomadaNoLinkedin($destino));

        // ⛔ O envio FALHOU — e mesmo assim o URN esta guardado.
        expect($destino->fresh()->handle_externo)->toContain('urn:li:video:C5505AQH');
    });

    it('⭐ recomeçar não reenvia o que já subiu — a cota é contada em requisições (DEC-113)', function () {
        /*
         * ⚠️ 150 requisicoes por dia por pessoa, e uma publicacao gasta varias.
         * Um reenvio cego de um video de 40 MB queimaria 12 delas para refazer
         * o que ja estava feito.
         */
        $destino = destinoNoLinkedin();
        $destino->forceFill(['handle_externo' => 'urn:li:video:C5505AQH|'])->save();

        Http::fake([
            'api.linkedin.com/rest/videos/*' => Http::response(['status' => 'PROCESSING']),
        ]);

        $resultado = app(PublicadorLinkedin::class)->publicar(
            $destino->fresh(['publicacao.midia', 'contaSocial.credencial']),
            retomadaNoLinkedin($destino)
        );

        expect($resultado->aceito)->toBeTrue()
            ->and($resultado->identificadorExterno)->toBe('urn:li:video:C5505AQH');

        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'dms-uploads'));
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'initializeUpload'));
    });
});

describe('⛔ o post nasce na CONCILIAÇÃO, e o id vem do cabeçalho', function () {
    it('o envio termina sem criar post nenhum (DEC-107)', function () {
        // Criar o post antes de o video ficar `AVAILABLE` devolve
        // `MEDIA_ASSET_WAITING_UPLOAD` — e esperar dormindo seguraria um worker.
        $destino = destinoNoLinkedin();

        Http::fake([
            'api.linkedin.com/rest/videos?action=initializeUpload' => Http::response(inicioDoEnvio()),
            'www.linkedin.com/dms-uploads/*' => Http::response('', 200, ['etag' => '"r0"']),
            'api.linkedin.com/rest/videos?action=finalizeUpload' => Http::response([], 200),
        ]);

        $resultado = app(PublicadorLinkedin::class)->publicar($destino, retomadaNoLinkedin($destino));

        expect($resultado->aceito)->toBeTrue()
            ->and($resultado->identificadorExterno)->toBe('urn:li:video:C5505AQH');

        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/rest/posts'));
    });

    it('⛔ o identificador do post vem do CABEÇALHO `x-restli-id`, e o corpo vem VAZIO (DEC-111)', function () {
        /*
         * ⛔ Procurar o id no JSON acharia `null`, o motor concluiria que
         * falhou — com o post ja publicado — e na passada seguinte publicaria de
         * novo. Publicacao nao tem desfazer.
         */
        $destino = destinoNoLinkedin();
        $destino->forceFill(['handle_externo' => 'urn:li:video:C5505AQH|'])->save();

        Http::fake([
            'api.linkedin.com/rest/videos/*' => Http::response(['status' => 'AVAILABLE']),
            'api.linkedin.com/rest/posts' => Http::response('', 201, [
                'x-restli-id' => 'urn:li:share:6844785523593134080',
            ]),
        ]);

        $resultado = app(PublicadorLinkedin::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']));

        expect($resultado->noAr)->toBeTrue()
            ->and($resultado->url)->toBe('https://www.linkedin.com/feed/update/urn:li:share:6844785523593134080/')
            ->and($destino->fresh()->identificador_externo)->toBe('urn:li:share:6844785523593134080');
    });

    it('enquanto o vídeo processa, não cria post e não falha', function () {
        $destino = destinoNoLinkedin();
        $destino->forceFill(['handle_externo' => 'urn:li:video:C5505AQH|'])->save();

        Http::fake(['api.linkedin.com/rest/videos/*' => Http::response(['status' => 'PROCESSING'])]);

        $resultado = app(PublicadorLinkedin::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']));

        expect($resultado->aindaProcessando)->toBeTrue();
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/rest/posts'));
    });

    it('⚠️ `WAITING_UPLOAD` é pressa nossa, não falha da rede', function () {
        // Tratar como erro mandaria a pessoa reenviar um video que estava
        // subindo bem.
        $destino = destinoNoLinkedin();
        $destino->forceFill(['handle_externo' => 'urn:li:video:C5505AQH|'])->save();

        Http::fake(['api.linkedin.com/rest/videos/*' => Http::response(['status' => 'WAITING_UPLOAD'])]);

        expect(app(PublicadorLinkedin::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']))->aindaProcessando)
            ->toBeTrue();
    });

    it('⛔ post já criado NÃO é relido — a rede não deixa (DEC-106)', function () {
        /*
         * ⚠️ Reler exige `r_member_social`, restrita a aprovados. Fingir uma
         * conferencia que nao aconteceu seria a mentira que o produto existe
         * para nao contar.
         */
        $destino = destinoNoLinkedin();
        $destino->forceFill([
            'handle_externo' => 'urn:li:video:C5505AQH|',
            'identificador_externo' => 'urn:li:share:99',
        ])->save();

        Http::fake();

        $resultado = app(PublicadorLinkedin::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']));

        expect($resultado->noAr)->toBeTrue()
            ->and($resultado->url)->toContain('urn:li:share:99');

        // ⛔ NENHUMA requisicao — nem para tentar.
        Http::assertNothingSent();
    });

    it('o vídeo que falhou vira frase em português, com o motivo da rede', function () {
        $destino = destinoNoLinkedin();
        $destino->forceFill(['handle_externo' => 'urn:li:video:C5505AQH|'])->save();

        Http::fake(['api.linkedin.com/rest/videos/*' => Http::response([
            'status' => 'PROCESSING_FAILED',
            'processingFailureReason' => 'INVALID_ASPECT_RATIO',
        ])]);

        expect(app(PublicadorLinkedin::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']))->erro)
            ->toContain('proporção');
    });
});

describe('⛔ o token de 60 dias que não se renova sozinho (DEC-112)', function () {
    it('⭐ `401` NÃO volta para a fila — manda reconectar', function () {
        /*
         * ⛔ Sem renovacao em segundo plano, repetir so queima tentativa contra
         * algo que nunca passa sozinho. O `401` aqui quer dizer token vencido.
         */
        $destino = destinoNoLinkedin();

        Http::fake([
            'api.linkedin.com/rest/videos?action=initializeUpload' => Http::response(['code' => 'EMPTY_ACCESS_TOKEN'], 401),
        ]);

        $resultado = app(PublicadorLinkedin::class)->publicar($destino, retomadaNoLinkedin($destino));

        expect($resultado->transitorio)->toBeFalse()
            ->and($resultado->erro)->toContain('Reconecte');
    });

    it('sem credencial, recusa e não tenta a rede', function () {
        $destino = destinoNoLinkedin();
        $destino->contaSocial->credencial->delete();

        Http::fake();

        $recarregado = $destino->fresh(['publicacao.midia', 'contaSocial.credencial']);

        expect(app(PublicadorLinkedin::class)->publicar($recarregado, retomadaNoLinkedin($recarregado))->erro)
            ->toContain('Reconecte');

        Http::assertNothingSent();
    });
});

describe('⛔ o limite é medido em REQUISIÇÕES (DEC-113)', function () {
    it('⭐ `429` vira espera, não falha', function () {
        // Uma publicacao gasta 1 inicializar + N pedacos + 1 finalizar +
        // 1 conferir + 1 postar. O teto de 150 chega antes do que parece.
        $destino = destinoNoLinkedin();

        Http::fake([
            'api.linkedin.com/rest/videos?action=initializeUpload' => Http::response(['code' => 'TOO_MANY_REQUESTS'], 429),
        ]);

        $resultado = app(PublicadorLinkedin::class)->publicar($destino, retomadaNoLinkedin($destino));

        expect($resultado->semCota)->toBeTrue()
            ->and($resultado->transitorio)->toBeFalse();
    });

    it('⭐ sem campo de "é passageiro", a separação sai do código HTTP (DEC-114)', function () {
        // Diferente da Meta, o LinkedIn nao diz se o erro passa.
        $destino = destinoNoLinkedin();

        Http::fake([
            'api.linkedin.com/rest/videos?action=initializeUpload' => Http::response(['code' => 'CONFLICT'], 409),
        ]);

        expect(app(PublicadorLinkedin::class)->publicar($destino, retomadaNoLinkedin($destino))->transitorio)->toBeTrue();
    });

    it('erro de pedido inválido é FALHA, e não volta para a fila', function () {
        $destino = destinoNoLinkedin();

        Http::fake([
            'api.linkedin.com/rest/videos?action=initializeUpload' => Http::response(
                ['code' => 'INVALID_URN_TYPE', 'message' => 'value must be a person URN'], 400
            ),
        ]);

        $resultado = app(PublicadorLinkedin::class)->publicar($destino, retomadaNoLinkedin($destino));

        expect($resultado->transitorio)->toBeFalse()
            ->and($resultado->semCota)->toBeFalse();
    });
});

describe('os dois cabeçalhos obrigatórios', function () {
    it('⛔ toda chamada da API versionada leva `LinkedIn-Version` e `X-Restli-Protocol-Version`', function () {
        // Sem eles a rede responde erro, e o erro NAO diz que faltou cabecalho.
        $destino = destinoNoLinkedin();

        Http::fake([
            'api.linkedin.com/rest/videos?action=initializeUpload' => Http::response(inicioDoEnvio()),
            'www.linkedin.com/dms-uploads/*' => Http::response('', 200, ['etag' => '"r0"']),
            'api.linkedin.com/rest/videos?action=finalizeUpload' => Http::response([], 200),
        ]);

        app(PublicadorLinkedin::class)->publicar($destino, retomadaNoLinkedin($destino));

        Http::assertSent(function ($requisicao) {
            if (! str_contains($requisicao->url(), 'api.linkedin.com')) {
                return false;
            }

            expect($requisicao->hasHeader('LinkedIn-Version'))->toBeTrue()
                ->and($requisicao->header('X-Restli-Protocol-Version'))->toBe(['2.0.0']);

            return true;
        });
    });
});

describe('a especificação da rede', function () {
    it('⛔ só MP4 — mandar MOV falharia DEPOIS do envio inteiro', function () {
        // O MOV que o Threads e o Instagram aceitam falha aqui no
        // processamento, com o motivo generico de "nao conseguimos processar".
        $spec = EspecificacaoDaRede::de(Plataforma::Linkedin);

        expect($spec->conferirContainer('video/quicktime'))->not->toBeNull()
            ->and($spec->conferirContainer('video/mp4'))->toBeNull();
    });
});

describe('⛔ a tela diz QUAL é o grau de certeza (DEC-106)', function () {
    it('⭐ o LinkedIn carrega a ressalva; as redes que relemos, não', function () {
        /*
         * ⚠️ Mostrar o link do LinkedIn com a mesma cara do link do YouTube
         * seria afirmar uma conferencia que nao aconteceu — e mentir sobre o
         * grau de certeza e exatamente o defeito que o produto critica.
         */
        expect(Plataforma::Linkedin->notaDaProva())->not->toBeNull()
            ->and(Plataforma::Linkedin->notaDaProva())->toContain('não foi conferido');

        // ⛔ Rede sem frase aqui e rede onde a conferencia acontece de verdade.
        expect(Plataforma::Youtube->notaDaProva())->toBeNull()
            ->and(Plataforma::Bluesky->notaDaProva())->toBeNull()
            ->and(Plataforma::Threads->notaDaProva())->toBeNull()
            ->and(Plataforma::Facebook->notaDaProva())->toBeNull()
            ->and(Plataforma::Instagram->notaDaProva())->toBeNull();
    });

    it('⛔ a ressalva não usa eufemismo — diz que NÃO foi conferido', function () {
        // "Pode levar um tempo" ou "aguardando confirmacao" fariam a pessoa
        // esperar por uma conferencia que nunca vem.
        $frase = (string) Plataforma::Linkedin->notaDaProva();

        expect($frase)->not->toContain('aguard')
            ->and($frase)->not->toContain('em breve')
            ->and($frase)->toContain('LinkedIn');
    });
});

describe('⛔ criar post é irreversível — tempo esgotado NÃO se repete (DEC-125)', function () {
    it('⭐ timeout ao criar o post PARA e avisa, em vez de tentar de novo', function () {
        /*
         * ⛔ Criar post não é idempotente e o LinkedIn não aceita chave de
         * repetição. Um tempo esgotado DEPOIS de a rede ter recebido o pedido
         * significa post publicado e resposta perdida — e a conciliação roda até
         * vinte vezes. "Ainda processando" aqui criaria um segundo post, um
         * terceiro, um quarto.
         *
         * ⛔ E não dá para conferir antes de criar: reler post exige
         * `r_member_social`, que é restrita (DEC-106).
         */
        $destino = destinoNoLinkedin();
        $destino->forceFill(['handle_externo' => 'urn:li:video:C5505AQH|'])->save();

        Http::fake([
            'api.linkedin.com/rest/videos/*' => Http::response(['status' => 'AVAILABLE']),
            'api.linkedin.com/rest/posts' => fn () => throw new ConnectionException('timed out'),
        ]);

        $resultado = app(PublicadorLinkedin::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']));

        // ⛔ NAO volta para a fila.
        expect($resultado->aindaProcessando)->toBeFalse()
            ->and($resultado->noAr)->toBeFalse()
            // ⚠️ E a frase diz que pode ter subido, em vez de afirmar que falhou.
            ->and($resultado->erro)->toContain('pode ter subido');
    });

    it('⚠️ já o vídeo que não respondeu continua esperando — ali repetir é seguro', function () {
        // Ler o status do video nao muda nada do lado de la: repetir custa uma
        // requisicao e nao cria nada.
        $destino = destinoNoLinkedin();
        $destino->forceFill(['handle_externo' => 'urn:li:video:C5505AQH|'])->save();

        Http::fake([
            'api.linkedin.com/rest/videos/*' => fn () => throw new ConnectionException('timed out'),
        ]);

        expect(app(PublicadorLinkedin::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']))->aindaProcessando)
            ->toBeTrue();
    });
});

describe('⛔ o texto que sobe é o texto INTEIRO', function () {
    it('⭐ as hashtags chegam no LinkedIn — elas iam sendo jogadas fora', function () {
        /*
         * ⛔ Aqui o defeito passou despercebido por um motivo específico: o
         * título TEM campo próprio nesta rede (`content.media.title`), e a
         * atenção ficou nele. A legenda era montada à mão e as hashtags ficavam
         * de fora.
         */
        $destino = destinoNoLinkedin();
        $destino->publicacao->forceFill(['hashtags' => ['corte', 'shorts']])->save();
        $destino->forceFill(['handle_externo' => 'urn:li:video:C5505AQH|'])->save();

        Http::fake([
            'api.linkedin.com/rest/videos/*' => Http::response(['status' => 'AVAILABLE']),
            'api.linkedin.com/rest/posts' => Http::response('', 201, ['x-restli-id' => 'urn:li:share:1']),
        ]);

        app(PublicadorLinkedin::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']));

        Http::assertSent(function ($r) {
            if (! str_contains($r->url(), '/rest/posts')) {
                return false;
            }

            expect($r['commentary'])->toContain('#corte')
                ->and($r['commentary'])->toContain('#shorts');

            return true;
        });
    });
});

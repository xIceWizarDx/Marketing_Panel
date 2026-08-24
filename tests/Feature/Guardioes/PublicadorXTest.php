<?php

use App\Enums\Plataforma;
use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Grupo;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Publicadores\PublicadorX;
use App\Publicadores\Retomada;
use App\Services\PublicacaoService;
use App\Support\ContextoDoUsuario;
use App\Support\Midia\EspecificacaoDaRede;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/*
| Guardiao do PUBLICADOR DO X (plano 24, DEC-126..132).
|
| ⛔ Aqui cada chamada custa dinheiro — e uma escolha de texto muda o custo em
| treze vezes. Testar isto sem rede e o unico jeito de nao gastar credito.
*/

beforeEach(function () {
    ContextoDoUsuario::limpar();
    Storage::fake('local');
    config([
        'services.x.client_id' => 'id-de-teste',
        'services.x.client_secret' => 'segredo-de-teste',
    ]);
});

afterEach(fn () => ContextoDoUsuario::limpar());

function destinoNoX(int $bytes = 1024): Destino
{
    $dono = cliente();
    ContextoDoUsuario::definir($dono);

    $midia = Midia::factory()->doUsuario($dono)->create(['tamanho_bytes' => $bytes]);
    Storage::disk('local')->put($midia->caminho, str_repeat('v', $bytes));

    $criada = Publicacao::factory()->doUsuario($dono)->enviada()->create([
        'midia_id' => $midia->id,
        'titulo' => null,
        'legenda' => 'Olha isso',
    ]);

    $conta = ContaSocial::factory()->doUsuario($dono)->doGrupo(Grupo::firstOrFail())
        ->daPlataforma(Plataforma::X)->comCredencial('token-de-2h')
        ->create(['identificador_externo' => '4444', 'nome_exibicao' => 'gabriel']);

    // ⚠️ Folga: o publicador renova quando falta menos de 20 minutos, e sem isso
    // todo teste bateria na renovacao antes de qualquer outra coisa.
    $conta->credencial->forceFill([
        'refresh_token' => 'rft-antigo',
        'expira_em' => now()->addHours(2),
    ])->save();

    $destino = Destino::factory()->create([
        'publicacao_id' => $criada->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Enviando,
    ]);

    return $destino->fresh(['publicacao.midia', 'contaSocial.credencial']);
}

function retomadaNoX(Destino $destino): Retomada
{
    return new Retomada($destino, app(PublicacaoService::class));
}

function xRespondendo(array $trocas = []): void
{
    Http::fake(array_merge([
        'api.x.com/2/media/upload*' => Http::response(['data' => ['id' => 'media-1', 'media_key' => 'k']]),
        'api.x.com/2/tweets' => Http::response(['data' => ['id' => 'post-999', 'text' => 'Olha isso']]),
    ], $trocas));
}

describe('⭐ o envio em pedaços NUMERADOS', function () {
    it('⛔ a ordem é dita pelo `segment_index`, não por faixa de bytes', function () {
        /*
         * ⚠️ Quatro redes, quatro convencoes: YouTube e TikTok usam faixa de
         * bytes, o LinkedIn usa a ordem dos recibos, e o X usa um NUMERO por
         * pedaco. Nenhuma empresta codigo para a outra.
         */
        $destino = destinoNoX(bytes: 3 * 1024 * 1024 + 10);
        xRespondendo();

        app(PublicadorX::class)->publicar($destino, retomadaNoX($destino));

        $indices = [];

        Http::assertSent(function ($r) use (&$indices) {
            // ⚠️ `data()` devolve as PARTES do multipart. Ler o corpo cru daria
            // falso negativo silencioso: ali o conteúdo é um fluxo.
            $partes = collect($r->data())->keyBy('name');

            if (($partes['command']['contents'] ?? null) === 'APPEND') {
                $indices[] = $partes['segment_index']['contents'] ?? null;
            }

            return true;
        });

        // 3 MB + 10 bytes em pedacos de 1 MB = quatro pedacos, numerados de 0 a 3.
        expect($indices)->toBe(['0', '1', '2', '3']);
    });

    it('⭐ o `media_id` é guardado ANTES do primeiro byte', function () {
        // Se o processo morrer no meio, e ele que impede um segundo envio — e
        // aqui cada requisicao a mais custa dinheiro.
        $destino = destinoNoX();

        xRespondendo(['api.x.com/2/media/upload*' => Http::sequence()
            ->push(['data' => ['id' => 'media-1']])
            ->push([], 500)]);

        app(PublicadorX::class)->publicar($destino, retomadaNoX($destino));

        expect($destino->fresh()->handle_externo)->toBe('media-1');
    });

    it('⭐ envio que já aconteceu não sobe de novo', function () {
        $destino = destinoNoX();
        $destino->forceFill(['handle_externo' => 'media-1'])->save();

        Http::fake(['api.x.com/2/media/upload*' => Http::response([
            'data' => ['processing_info' => ['state' => 'in_progress']],
        ])]);

        $recarregado = $destino->fresh(['publicacao.midia', 'contaSocial.credencial']);
        $resultado = app(PublicadorX::class)->publicar($recarregado, retomadaNoX($recarregado));

        expect($resultado->aceito)->toBeTrue()
            ->and($resultado->identificadorExterno)->toBe('media-1');

        Http::assertNotSent(fn ($r) => str_contains((string) $r->body(), 'INIT'));
    });

    it('⛔ o envio termina sem criar post nenhum — post custa dinheiro', function () {
        $destino = destinoNoX();
        xRespondendo();

        $resultado = app(PublicadorX::class)->publicar($destino, retomadaNoX($destino));

        expect($resultado->aceito)->toBeTrue();
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/2/tweets'));
    });
});

describe('⛔ o post só nasce quando o vídeo está pronto', function () {
    it('⭐ `succeeded` → cria o post, e o id vem do CORPO', function () {
        // ⭐ Diferente do LinkedIn, que devolve no cabecalho.
        $destino = destinoNoX();
        $destino->forceFill(['handle_externo' => 'media-1'])->save();

        Http::fake([
            'api.x.com/2/media/upload*' => Http::response(['data' => ['processing_info' => ['state' => 'succeeded']]]),
            'api.x.com/2/tweets' => Http::response(['data' => ['id' => 'post-999']]),
        ]);

        $resultado = app(PublicadorX::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']));

        expect($resultado->noAr)->toBeTrue()
            ->and($resultado->url)->toBe('https://x.com/gabriel/status/post-999')
            ->and($destino->fresh()->identificador_externo)->toBe('post-999');
    });

    it('enquanto processa, não cria post e não falha', function () {
        $destino = destinoNoX();
        $destino->forceFill(['handle_externo' => 'media-1'])->save();

        Http::fake(['api.x.com/2/media/upload*' => Http::response([
            'data' => ['processing_info' => ['state' => 'in_progress']],
        ])]);

        $resultado = app(PublicadorX::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']));

        expect($resultado->aindaProcessando)->toBeTrue();
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/2/tweets'));
    });

    it('vídeo que falhou vira frase em português, com o motivo da rede', function () {
        $destino = destinoNoX();
        $destino->forceFill(['handle_externo' => 'media-1'])->save();

        Http::fake(['api.x.com/2/media/upload*' => Http::response([
            'data' => ['processing_info' => ['state' => 'failed', 'error' => ['message' => 'InvalidMedia']]],
        ])]);

        expect(app(PublicadorX::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']))->erro)
            ->toContain('não conseguiu processar');
    });
});

describe('⛔ criar post é irreversível — e cobrado', function () {
    it('⭐ timeout ao criar o post PARA e avisa, em vez de tentar de novo', function () {
        /*
         * ⛔ Mesma razao do LinkedIn (DEC-125), com um agravante: aqui cada
         * tentativa e COBRADA. A conciliacao roda vinte vezes — repetir criaria
         * vinte posts e vinte cobrancas.
         */
        $destino = destinoNoX();
        $destino->forceFill(['handle_externo' => 'media-1'])->save();

        Http::fake([
            'api.x.com/2/media/upload*' => Http::response(['data' => ['processing_info' => ['state' => 'succeeded']]]),
            'api.x.com/2/tweets' => fn () => throw new ConnectionException('timed out'),
        ]);

        $resultado = app(PublicadorX::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']));

        expect($resultado->aindaProcessando)->toBeFalse()
            ->and($resultado->erro)->toContain('pode ter subido');
    });

    it('⭐ `402` é CRÉDITO ACABADO — a frase não manda mexer no vídeo', function () {
        /*
         * ⛔ Exclusivo desta rede: o que falta e dinheiro no console do X, nao
         * qualidade de video. Dizer "o X recusou" mandaria a pessoa reexportar
         * um arquivo perfeito.
         */
        $destino = destinoNoX();
        $destino->forceFill(['handle_externo' => 'media-1'])->save();

        Http::fake([
            'api.x.com/2/media/upload*' => Http::response(['data' => ['processing_info' => ['state' => 'succeeded']]]),
            'api.x.com/2/tweets' => Http::response(['title' => 'Payment required'], 402),
        ]);

        $erro = app(PublicadorX::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']))->erro;

        expect($erro)->toContain('créditos')
            ->and($erro)->not->toContain('vídeo');
    });
});

describe('⭐ a prova custa US$ 0,001, e é relida', function () {
    it('post já publicado é RELIDO na rede — é a promessa (DEC-31)', function () {
        $destino = destinoNoX();
        $destino->forceFill(['handle_externo' => 'media-1', 'identificador_externo' => 'post-999'])->save();

        Http::fake(['api.x.com/2/tweets/post-999*' => Http::response(['data' => ['id' => 'post-999']])]);

        $resultado = app(PublicadorX::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']));

        expect($resultado->noAr)->toBeTrue();
        Http::assertSent(fn ($r) => str_contains($r->url(), '/2/tweets/post-999'));
    });

    it('⭐ post que sumiu depois de publicado é dito pelo que é', function () {
        // ⚠️ E exatamente o caso que nenhum concorrente pega, porque nenhum rele
        // o post.
        $destino = destinoNoX();
        $destino->forceFill(['handle_externo' => 'media-1', 'identificador_externo' => 'post-999'])->save();

        Http::fake(['api.x.com/2/tweets/post-999*' => Http::response([], 404)]);

        expect(app(PublicadorX::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']))->erro)
            ->toContain('não está mais no X');
    });
});

describe('⛔ os erros do envio, ditos pelo que são', function () {
    it('⭐ `403` no envio aponta para a permissão de mídia, não para o vídeo', function () {
        // ⚠️ `media.write` e escopo SEPARADO, e o sintoma de esquecer engana: a
        // conta conecta, o texto subiria, e o video nao (DEC-131).
        $destino = destinoNoX();

        xRespondendo(['api.x.com/2/media/upload*' => Http::response([], 403)]);

        expect(app(PublicadorX::class)->publicar($destino, retomadaNoX($destino))->erro)
            ->toContain('enviar mídia');
    });

    it('`401` manda reconectar e não volta para a fila', function () {
        $destino = destinoNoX();

        xRespondendo(['api.x.com/2/media/upload*' => Http::response([], 401)]);

        $resultado = app(PublicadorX::class)->publicar($destino, retomadaNoX($destino));

        expect($resultado->transitorio)->toBeFalse()
            ->and($resultado->erro)->toContain('Reconecte');
    });

    it('`429` vira espera, não falha (DEC-24)', function () {
        $destino = destinoNoX();

        xRespondendo(['api.x.com/2/media/upload*' => Http::response([], 429)]);

        expect(app(PublicadorX::class)->publicar($destino, retomadaNoX($destino))->semCota)->toBeTrue();
    });
});

describe('a especificação da rede', function () {
    it('⛔ os limites têm procedência — nenhum foi inventado (DEC-132)', function () {
        /*
         * ⚠️ A documentacao do X nao declara tamanho, duracao, proporcao nem
         * limite de texto. Os numeros aqui vem do perfil canonico do produto e
         * da doc 10, e isso esta escrito no proprio codigo.
         */
        $spec = EspecificacaoDaRede::de(Plataforma::X);

        expect($spec->texto->legenda)->toBe(280)
            ->and($spec->conferirContainer('video/quicktime'))->not->toBeNull()
            ->and($spec->conferirContainer('video/mp4'))->toBeNull();
    });
});

describe('⛔ o texto que sobe é o texto INTEIRO', function () {
    it('⭐ o título vai junto — o X não tem campo separado', function () {
        /*
         * ⛔ Mandar so `textoFinal()` deixava o titulo de fora sem avisar
         * ninguem. E o mesmo defeito que Threads e TikTok ja tiveram, e ele
         * reapareceu na rede seguinte — por isso agora tem guardiao nas tres.
         */
        $destino = destinoNoX();
        $destino->publicacao->forceFill(['titulo' => 'Meu corte', 'hashtags' => ['humor']])->save();
        $destino->forceFill(['handle_externo' => 'media-1'])->save();

        Http::fake([
            'api.x.com/2/media/upload*' => Http::response(['data' => ['processing_info' => ['state' => 'succeeded']]]),
            'api.x.com/2/tweets' => Http::response(['data' => ['id' => 'post-999']]),
        ]);

        app(PublicadorX::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']));

        Http::assertSent(function ($r) {
            if (! str_contains($r->url(), '/2/tweets')) {
                return false;
            }

            expect($r['text'])->toContain('Meu corte')
                ->and($r['text'])->toContain('Olha isso')
                ->and($r['text'])->toContain('#humor');

            return true;
        });
    });

    it('⭐ e os 280 caracteres são de tudo junto, não só da legenda', function () {
        // Conferir separado deixaria passar o que a rede vai recusar — e aqui a
        // recusa acontece DEPOIS de o video inteiro ter subido, e do gasto.
        $spec = EspecificacaoDaRede::de(Plataforma::X);

        expect($spec->conferirTextos(str_repeat('t', 150), str_repeat('a', 129)))->toBeEmpty();

        $achados = $spec->conferirTextos(str_repeat('t', 150), str_repeat('a', 130));

        expect($achados)->not->toBeEmpty()
            ->and($achados[0]->mensagem)->toContain('juntos');
    });
});

describe('⛔ crédito acabado também no envio', function () {
    it('⭐ `402` no envio da mídia fala de crédito, não de vídeo', function () {
        // ⚠️ O 402 nao acontece so na criacao do post: a frase generica mandaria
        // a pessoa reexportar um arquivo perfeito.
        $destino = destinoNoX();

        xRespondendo(['api.x.com/2/media/upload*' => Http::response([], 402)]);

        $erro = app(PublicadorX::class)->publicar($destino, retomadaNoX($destino))->erro;

        expect($erro)->toContain('créditos')
            ->and($erro)->not->toContain('vídeo');
    });
});

<?php

use App\Enums\Plataforma;
use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Grupo;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Publicadores\PublicadorThreads;
use App\Publicadores\Retomada;
use App\Services\PublicacaoService;
use App\Support\ContextoDoUsuario;
use App\Support\Midia\EspecificacaoDaRede;
use App\Support\Midia\Medida;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/*
| Guardiao do PUBLICADOR DO THREADS (plano 21, Fase 4).
|
| ⚠️ Esta rede publica em DOIS TEMPOS e nao recebe arquivo: ela vem BUSCAR a
| midia num endereco nosso. Os dois fatos mudam o motor, e cada teste aqui trava
| um deles.
*/

beforeEach(function () {
    ContextoDoUsuario::limpar();
    Storage::fake('local');
    // ⚠️ Endereco publico: sem ele o publicador recusa antes de tentar, e todo
    // teste de publicacao passaria pelo motivo errado.
    config(['app.url' => 'https://painel-de-teste.exemplo']);
});

afterEach(fn () => ContextoDoUsuario::limpar());

/** Um destino do Threads pronto para publicar. */
function destinoNoThreads(array $publicacao = []): Destino
{
    $dono = cliente();
    ContextoDoUsuario::definir($dono);

    $midia = Midia::factory()->doUsuario($dono)->create(['tamanho_bytes' => 1024]);
    Storage::disk('local')->put($midia->caminho, str_repeat('v', 1024));

    $criada = Publicacao::factory()->doUsuario($dono)->enviada()->create(array_merge([
        'midia_id' => $midia->id,
        'titulo' => 'Meu corte',
        'legenda' => 'Olha isso',
    ], $publicacao));

    $conta = ContaSocial::factory()->doUsuario($dono)->doGrupo(Grupo::firstOrFail())
        ->daPlataforma(Plataforma::Threads)->comCredencial('token-longo')
        ->create(['identificador_externo' => '17841400000000000']);

    $destino = Destino::factory()->create([
        'publicacao_id' => $criada->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Enviando,
    ]);

    // ⚠️ O contexto FICA definido: o publicador roda dentro de um job que ja o
    // definiu (0.M). Limpar aqui faria o escopo de dono lancar na primeira
    // releitura, e o teste falharia pelo motivo errado.
    return $destino->fresh(['publicacao.midia', 'contaSocial.credencial']);
}

/**
 * O caderninho de retomada deste destino.
 *
 * ⚠️ O Threads nao sobe arquivo em pedacos, entao nao usa retomada — mas o
 * contrato do publicador pede o caderninho, e ele precisa ser o REAL: um
 * dublê esconderia o dia em que o publicador comecar a gravar handle por ali.
 */
function retomadaDe(Destino $destino): Retomada
{
    return new Retomada($destino, app(PublicacaoService::class));
}

describe('⭐ a rede vem BUSCAR o arquivo (DEC-100)', function () {
    it('manda uma URL, não o arquivo — e a URL é assinada e temporária', function () {
        $destino = destinoNoThreads();

        Http::fake(['graph.threads.net/*' => Http::response(['id' => 'container-1'])]);

        app(PublicadorThreads::class)->publicar($destino, retomadaDe($destino));

        Http::assertSent(function ($requisicao) {
            $url = (string) ($requisicao['video_url'] ?? '');

            // ⛔ Endereco sem assinatura seria o arquivo de qualquer cliente
            // para quem soubesse montar a URL.
            expect($url)->toContain('midia-temporaria')
                ->and($url)->toContain('signature=')
                ->and($url)->toContain('expires=');

            return true;
        });
    });

    it('⛔ sem endereço público, RECUSA antes de tentar', function () {
        /*
         * ⚠️ De `localhost` a Meta nunca busca nada. Enviar assim devolveria
         * `FAILED_DOWNLOADING_VIDEO` quinze minutos depois — o MESMO erro de
         * video corrompido — e mandaria a pessoa reexportar um arquivo perfeito.
         */
        config(['app.url' => 'http://localhost:8000']);
        $destino = destinoNoThreads();

        Http::fake();

        $resultado = app(PublicadorThreads::class)->publicar($destino, retomadaDe($destino));

        expect($resultado->erro)->toContain('endereço público');
        Http::assertNothingSent();
    });
});

describe('⛔ criar o contêiner NÃO publica (DEC-103)', function () {
    it('o envio devolve o id do contêiner e para por aí', function () {
        // ⚠️ A armadilha central: o container e criado, tudo responde sucesso, e
        // o post NAO existe. So o `threads_publish` publica.
        $destino = destinoNoThreads();

        Http::fake(['graph.threads.net/*' => Http::response(['id' => 'container-1'])]);

        $resultado = app(PublicadorThreads::class)->publicar($destino, retomadaDe($destino));

        expect($resultado->identificadorExterno)->toBe('container-1');

        // ⛔ NENHUMA chamada de publicar saiu no envio.
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'threads_publish'));
    });

    it('⭐ o segundo passo acontece na conciliação, quando a rede diz FINISHED', function () {
        // Esperar 30s segurando um worker faria uma fila de dez publicacoes
        // virar cinco minutos de nada acontecendo.
        $destino = destinoNoThreads();
        $destino->forceFill(['handle_externo' => 'container-1'])->save();

        Http::fake([
            'graph.threads.net/v1.0/container-1*' => Http::response(['status' => 'FINISHED']),
            'graph.threads.net/*/threads_publish' => Http::response(['id' => 'post-999']),
        ]);

        $resultado = app(PublicadorThreads::class)->conciliar($destino->fresh(['contaSocial.credencial']));

        expect($resultado->noAr)->toBeTrue()
            ->and($resultado->url)->toContain('post-999')
            // ⚠️ O id do POST e OUTRO, diferente do container — e e ele que
            // vira a prova.
            ->and($destino->fresh()->identificador_externo)->toBe('post-999');
    });

    it('enquanto está IN_PROGRESS, não publica e não falha', function () {
        $destino = destinoNoThreads();
        $destino->forceFill(['handle_externo' => 'container-1'])->save();

        Http::fake(['graph.threads.net/*' => Http::response(['status' => 'IN_PROGRESS'])]);

        $resultado = app(PublicadorThreads::class)->conciliar($destino->fresh(['contaSocial.credencial']));

        expect($resultado->aindaProcessando)->toBeTrue();
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'threads_publish'));
    });
});

describe('⛔ os erros da rede, ditos pelo que são', function () {
    it('⭐ FAILED_DOWNLOADING_VIDEO é problema NOSSO, e a frase não culpa o vídeo', function () {
        /*
         * ⛔ Este e o erro desta arquitetura: a Meta nao conseguiu BUSCAR a
         * midia — URL vencida, servidor inalcancavel, arquivo ja liberado.
         * Traduzir como "video com problema" mandaria a pessoa reexportar um
         * arquivo que esta perfeito.
         */
        $destino = destinoNoThreads();
        $destino->forceFill(['handle_externo' => 'container-1'])->save();

        Http::fake(['graph.threads.net/*' => Http::response([
            'status' => 'ERROR',
            'error_message' => 'FAILED_DOWNLOADING_VIDEO',
        ])]);

        $resultado = app(PublicadorThreads::class)->conciliar($destino->fresh(['contaSocial.credencial']));

        expect($resultado->erro)->toContain('buscar o vídeo no nosso servidor')
            ->and($resultado->erro)->not->toContain('recusou');
    });

    it('EXPIRED diz que o prazo acabou, não que o vídeo é ruim', function () {
        // O container morre em 24 h sem ser publicado. Dizer "falhou" mandaria a
        // pessoa procurar defeito no arquivo.
        $destino = destinoNoThreads();
        $destino->forceFill(['handle_externo' => 'container-1'])->save();

        Http::fake(['graph.threads.net/*' => Http::response(['status' => 'EXPIRED'])]);

        expect(app(PublicadorThreads::class)->conciliar($destino->fresh(['contaSocial.credencial']))->erro)
            ->toContain('24 horas');
    });

    it('⛔ `INVALID_ASPEC_RATIO` — o código da rede vem SEM o `T`, e a frase tem que sair mesmo assim', function () {
        /*
         * ⛔ A documentacao oficial escreve `INVALID_ASPEC_RATIO`, sem o `T`, e
         * noutra leitura da mesma pagina troca `INVALID_FRAME_RATE` por
         * `FAILED_FRAME_RATE`. Casar a palavra inteira faria a recusa MAIS COMUM
         * de todas — a proporcao do video — cair no generico "o Threads recusou
         * este post", que nao diz o que arrumar.
         */
        $destino = destinoNoThreads();
        $destino->forceFill(['handle_externo' => 'container-1'])->save();

        Http::fake(['graph.threads.net/*' => Http::response([
            'status' => 'ERROR',
            'error_message' => 'INVALID_ASPEC_RATIO',
        ])]);

        expect(app(PublicadorThreads::class)->conciliar($destino->fresh(['contaSocial.credencial']))->erro)
            ->toContain('proporção');
    });

    it('e a grafia consertada da rede também é entendida', function () {
        // O dia em que a Meta corrigir o erro de digitacao nao pode virar
        // regressao aqui.
        $destino = destinoNoThreads();
        $destino->forceFill(['handle_externo' => 'container-1'])->save();

        Http::fake(['graph.threads.net/*' => Http::response([
            'status' => 'ERROR',
            'error_message' => 'INVALID_ASPECT_RATIO',
        ])]);

        expect(app(PublicadorThreads::class)->conciliar($destino->fresh(['contaSocial.credencial']))->erro)
            ->toContain('proporção');
    });

    it('erro de conteúdo vira frase em português, e NÃO volta para a fila', function () {
        // ⚠️ A documentacao nao lista NENHUM erro passageiro: tentar de novo com
        // o mesmo arquivo da o mesmo resultado tres vezes.
        $destino = destinoNoThreads();
        $destino->forceFill(['handle_externo' => 'container-1'])->save();

        Http::fake(['graph.threads.net/*' => Http::response([
            'status' => 'ERROR',
            'error_message' => 'INVALID_FRAME_RATE',
        ])]);

        $resultado = app(PublicadorThreads::class)->conciliar($destino->fresh(['contaSocial.credencial']));

        expect($resultado->erro)->toContain('taxa de quadros')
            ->and($resultado->aindaProcessando)->toBeFalse();
    });

    it('⭐ `is_transient` da rede decide se volta para a fila', function () {
        // A propria Meta diz se o erro passa — melhor que adivinhar pelo codigo
        // HTTP, que foi o que tivemos que fazer no YouTube.
        $destino = destinoNoThreads();

        Http::fake(['graph.threads.net/*' => Http::response([
            'error' => ['message' => 'Please retry', 'is_transient' => true],
        ], 400)]);

        expect(app(PublicadorThreads::class)->publicar($destino, retomadaDe($destino))->transitorio)->toBeTrue();
    });
});

describe('⛔ o limite de 250 por dia é ESPERA, não falha (DEC-24)', function () {
    it('⭐ cota estourada vira `semCota`, e não queima as três tentativas', function () {
        /*
         * ⚠️ A rede NAO devolve codigo proprio para cota — quem sabe e o
         * endpoint de limite. Sem esta consulta, a publicacao de numero 251 do
         * dia seria marcada como falha permanente contra um limite que so volta
         * amanha.
         */
        $destino = destinoNoThreads();

        Http::fake([
            'graph.threads.net/*/threads_publishing_limit*' => Http::response([
                'data' => [['quota_usage' => 250, 'config' => ['quota_total' => 250, 'quota_duration' => 86400]]],
            ]),
            'graph.threads.net/*' => Http::response(['error' => ['message' => 'Application request limit reached']], 400),
        ]);

        $resultado = app(PublicadorThreads::class)->publicar($destino, retomadaDe($destino));

        expect($resultado->semCota)->toBeTrue()
            ->and($resultado->erro)->toContain('limite de publicações do dia');
    });

    it('⛔ se a consulta da cota falhar, o motivo continua sendo o que a rede deu', function () {
        // Inventar "limite do dia" a partir de uma chamada que nem respondeu
        // esconderia o motivo real do erro.
        $destino = destinoNoThreads();

        Http::fake([
            'graph.threads.net/*/threads_publishing_limit*' => Http::response([], 500),
            'graph.threads.net/*' => Http::response(['error' => ['message' => 'Invalid parameter']], 400),
        ]);

        $resultado = app(PublicadorThreads::class)->publicar($destino, retomadaDe($destino));

        expect($resultado->semCota)->toBeFalse()
            ->and($resultado->erro)->toContain('Invalid parameter');
    });

    it('com cota sobrando, nem toca no assunto', function () {
        $destino = destinoNoThreads();

        Http::fake([
            'graph.threads.net/*/threads_publishing_limit*' => Http::response([
                'data' => [['quota_usage' => 3, 'config' => ['quota_total' => 250]]],
            ]),
            'graph.threads.net/*' => Http::response(['error' => ['message' => 'Invalid parameter']], 400),
        ]);

        expect(app(PublicadorThreads::class)->publicar($destino, retomadaDe($destino))->semCota)->toBeFalse();
    });
});

describe('a legenda', function () {
    it('⛔ é medida em BYTES, não em caracteres (DEC-104)', function () {
        /*
         * A documentacao e literal: "emojis sao contados como o numero de bytes
         * UTF-8". Dez emojis comem 40 bytes — uma legenda de 480 "caracteres"
         * estoura os 500 sem parecer.
         */
        $spec = EspecificacaoDaRede::de(Plataforma::Threads);

        expect($spec->texto->medidaDaLegenda)->toBe(Medida::Bytes)
            ->and($spec->texto->legenda)->toBe(500);

        // Prova a diferenca: 200 emojis sao 200 caracteres e 800 bytes.
        $emojis = str_repeat('🙂', 200);
        expect(Medida::Caracteres->contar($emojis))->toBe(200)
            ->and(Medida::Bytes->contar($emojis))->toBe(800);
    });

    it('⭐ 480 "caracteres" com emoji são RECUSADOS antes de subir (4.8)', function () {
        /*
         * ⛔ Contada por caractere, esta legenda cabe folgada nos 500. Contada
         * como a rede conta, ela passa dos 700 bytes e seria recusada NO AR —
         * depois de o video ter subido, com o destino ja em `enviando`.
         */
        $legenda = str_repeat('a', 380).str_repeat('🙂', 100);

        expect(mb_strlen($legenda))->toBe(480);

        $achados = EspecificacaoDaRede::de(Plataforma::Threads)->conferirTextos(null, $legenda);

        expect($achados)->not->toBeEmpty()
            ->and($achados[0]->mensagem)->toContain('legenda');
    });

    it('⛔ o título CONTA no mesmo orçamento — aqui ele sobe colado na legenda', function () {
        /*
         * ⛔ O Threads nao tem campo de titulo: o publicador manda
         * `titulo . ' ' . legenda` num campo so. Conferir os dois SEPARADO
         * deixava passar o que a rede ia recusar — e a recusa acontece depois
         * de o video inteiro ter subido.
         *
         * ⚠️ 200 + 400 = 601 bytes com o espaco. Cada um cabe sozinho nos 500;
         * juntos, nao.
         */
        $spec = EspecificacaoDaRede::de(Plataforma::Threads);

        expect($spec->conferirTextos(null, str_repeat('a', 400)))->toBeEmpty()
            ->and($spec->conferirTextos(str_repeat('t', 200), null))->toBeEmpty();

        $achados = $spec->conferirTextos(str_repeat('t', 200), str_repeat('a', 400));

        expect($achados)->not->toBeEmpty()
            ->and($achados[0]->mensagem)->toContain('601');
    });
});

describe('sem credencial', function () {
    it('recusa com frase que diz o que fazer, e não tenta a rede', function () {
        $destino = destinoNoThreads();
        $destino->contaSocial->credencial->delete();

        Http::fake();

        $recarregado = $destino->fresh(['publicacao.midia', 'contaSocial.credencial']);

        $resultado = app(PublicadorThreads::class)->publicar($recarregado, retomadaDe($recarregado));

        expect($resultado->erro)->toContain('Reconecte');
        Http::assertNothingSent();
    });
});

describe('⛔ o texto que sobe é o texto INTEIRO', function () {
    it('⭐ as hashtags chegam na rede — elas iam sendo jogadas fora', function () {
        /*
         * ⛔ Este publicador montava a legenda à mão e ignorava as hashtags: a
         * pessoa escrevia, a tela contava, e nada chegava. Bluesky, Facebook e
         * Instagram sempre usaram `Destino::textoFinal()`; só aqui e no TikTok
         * ele tinha sido reescrito.
         */
        $destino = destinoNoThreads(['hashtags' => ['corte', 'shorts']]);

        Http::fake(['graph.threads.net/*' => Http::response(['id' => 'container-1'])]);

        app(PublicadorThreads::class)->publicar($destino, retomadaDe($destino));

        Http::assertSent(function ($requisicao) {
            $texto = (string) ($requisicao['text'] ?? '');

            return str_contains($texto, '#corte') && str_contains($texto, '#shorts');
        });
    });

    it('⭐ e o texto próprio daquele destino tem preferência sobre o da publicação', function () {
        // `legenda_override` existe para escrever diferente por rede. Montar o
        // texto à mão sem passar por ele tornaria o campo decorativo.
        $destino = destinoNoThreads();
        $destino->forceFill(['legenda_override' => 'só para o Threads'])->save();

        Http::fake(['graph.threads.net/*' => Http::response(['id' => 'container-1'])]);

        $recarregado = $destino->fresh(['publicacao.midia', 'contaSocial.credencial']);
        app(PublicadorThreads::class)->publicar($recarregado, retomadaDe($recarregado));

        Http::assertSent(fn ($r) => str_contains((string) ($r['text'] ?? ''), 'só para o Threads'));
    });
});

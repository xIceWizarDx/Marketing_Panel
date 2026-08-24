<?php

use App\Console\Commands\AtualizarMetricas;
use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Publicacao;
use App\Publicadores\PublicadorBluesky;
use App\Publicadores\PublicadorYoutube;
use App\Publicadores\RegistroDePublicadores;
use App\Support\ContextoDoUsuario;
use Illuminate\Support\Facades\Http;

/*
| Guardiao das METRICAS (plano 17).
|
| ⭐ O arquivo inteiro gira em torno de UMA regra: `null` nao e zero.
|
| Quatro coisas diferentes virariam `0` se ninguem as separasse — a rede nao tem
| esse numero, o dono escondeu, a rede ainda nao calculou, nos ainda nao lemos.
| Escrever `0` em qualquer um dos quatro e afirmar um fato que ninguem verificou,
| que e exatamente o defeito que este produto existe para nao ter (DEC-95).
*/

beforeEach(fn () => ContextoDoUsuario::limpar());
afterEach(fn () => ContextoDoUsuario::limpar());

/** Uma conta pronta para ler metrica, com o dono ja no contexto. */
function contaParaMetrica(Plataforma $rede): ContaSocial
{
    $dono = cliente();
    ContextoDoUsuario::definir($dono);

    return ContaSocial::factory()
        ->doUsuario($dono)
        ->daPlataforma($rede)
        ->comCredencial('token-valido')
        ->create(['nome_exibicao' => 'conta.de.teste']);
}

/** Um post ja no ar naquela conta. */
function postNoAr(ContaSocial $conta, string $identificador): Destino
{
    $publicacao = Publicacao::factory()->doUsuario($conta->usuario)->enviada()->create([
        'grupo_id' => $conta->grupo_id,
    ]);

    return Destino::factory()->create([
        'publicacao_id' => $publicacao->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Publicado,
        'identificador_externo' => $identificador,
        'url_publicada' => 'https://exemplo.test/p/1',
        'publicado_em' => now()->subDay(),
    ]);
}

describe('⛔ falha que passa nao pode derrubar a publicacao (DEC-98)', function () {
    it('cota estourada NAO marca a conta — ela continua podendo publicar', function () {
        /*
         * ⚠️ Este e o teste mais caro do arquivo.
         *
         * Antes, qualquer resposta fora do 2xx virava lista vazia — e lista
         * vazia marcava a conta como Erro, o que BLOQUEIA publicar ate alguem
         * reconectar na mao. Um 403 de cota do Google, que some sozinho a
         * meia-noite, desligava a publicacao de todo mundo do YouTube.
         *
         * Ler metrica consome a MESMA cota que publicar: sem esta trava, usar
         * mais a leitura aumenta a chance de o dia ruim do Google virar apagao.
         */
        $conta = contaParaMetrica(Plataforma::Youtube);
        $conta->forceFill(['updated_at' => now()->subDays(40)])->save();

        Http::fake([
            'googleapis.com/youtube/v3/channels*' => Http::response([
                'error' => ['errors' => [['reason' => 'quotaExceeded']], 'code' => 403],
            ], 403),
        ]);

        ContextoDoUsuario::limpar();
        $this->artisan('youtube:reconferir')->assertSuccessful();

        expect($conta->fresh()->status)->toBe(StatusConta::Ativa)
            ->and($conta->fresh()->status_detalhe)->toBeNull();
    });

    it('servidor do Google fora do ar tambem NAO marca a conta', function () {
        $conta = contaParaMetrica(Plataforma::Youtube);
        $conta->forceFill(['updated_at' => now()->subDays(40)])->save();

        Http::fake(['googleapis.com/youtube/v3/channels*' => Http::response('', 500)]);

        ContextoDoUsuario::limpar();
        $this->artisan('youtube:reconferir')->assertSuccessful();

        expect($conta->fresh()->status)->toBe(StatusConta::Ativa);
    });

    it('⭐ mas acesso negado de verdade CONTINUA marcando', function () {
        // A trava nao pode virar cegueira: conta que perdeu o acesso mesmo
        // precisa aparecer no semaforo (DEC-32), ou a pessoa so descobre no dia
        // em que a publicacao falhar.
        $conta = contaParaMetrica(Plataforma::Youtube);
        $conta->forceFill(['updated_at' => now()->subDays(40)])->save();

        Http::fake([
            'googleapis.com/youtube/v3/channels*' => Http::response([
                'error' => ['errors' => [['reason' => 'forbidden']], 'code' => 403],
            ], 403),
        ]);

        ContextoDoUsuario::limpar();
        $this->artisan('youtube:reconferir')->assertSuccessful();

        expect($conta->fresh()->status)->toBe(StatusConta::Erro);
    });
});

describe('⛔ null nunca vira zero (DEC-95)', function () {
    it('inscritos ocultos no YouTube viram null, e nao 0', function () {
        // ⚠️ Quando a pessoa esconde o numero, o YouTube OMITE o campo e marca
        // `hiddenSubscriberCount`. Escrever 0 ai diria "voce nao tem inscritos"
        // para quem tem dez mil.
        $conta = contaParaMetrica(Plataforma::Youtube);

        Http::fake([
            'googleapis.com/youtube/v3/channels*' => Http::response([
                'items' => [['statistics' => ['hiddenSubscriberCount' => true, 'viewCount' => '4000']]],
            ]),
        ]);

        $metricas = app(PublicadorYoutube::class)->metricasDaConta($conta);

        expect($metricas)->not->toBeNull()
            ->and($metricas->seguidores)->toBeNull();
    });

    it('inscritos visiveis chegam como numero', function () {
        $conta = contaParaMetrica(Plataforma::Youtube);

        Http::fake([
            'googleapis.com/youtube/v3/channels*' => Http::response([
                // ⚠️ A API devolve inteiro longo como TEXTO.
                'items' => [['statistics' => ['subscriberCount' => '1230', 'viewCount' => '98000']]],
            ]),
        ]);

        expect(app(PublicadorYoutube::class)->metricasDaConta($conta)->seguidores)->toBe(1230);
    });

    it('⛔ favoriteCount do YouTube NAO entra — ele vale sempre 0 desde 2015', function () {
        // Guardar esse zero seria guardar uma mentira: a propria documentacao diz
        // que o campo foi descontinuado e o valor e fixo.
        $conta = contaParaMetrica(Plataforma::Youtube);
        $destino = postNoAr($conta, 'video-abc');

        Http::fake([
            'googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [['statistics' => [
                    'viewCount' => '1240', 'likeCount' => '34', 'commentCount' => '5', 'favoriteCount' => '0',
                ]]],
            ]),
        ]);

        $metricas = app(PublicadorYoutube::class)->metricasDoPost($destino);

        expect($metricas->visualizacoes)->toBe(1240)
            ->and($metricas->curtidas)->toBe(34)
            ->and($metricas->comentarios)->toBe(5)
            // O YouTube nao publica compartilhamento na API — e inventar um
            // numero e o oposto do que este produto faz.
            ->and($metricas->compartilhamentos)->toBeNull();
    });

    it('⭐ visualizacao no Bluesky e SEMPRE null — o protocolo nao tem esse campo', function () {
        /*
         * ⛔ Nao e falta de permissao nem plano pago: o lexicon
         * `app.bsky.feed.defs` simplesmente nao define contador de visualizacao.
         * Um 0 aqui afirmaria que ninguem viu, quando o certo e que ninguem
         * conta — e esse 0 nunca sairia do lugar.
         */
        $conta = contaParaMetrica(Plataforma::Bluesky);
        $destino = postNoAr($conta, 'at://did:plc:abc/app.bsky.feed.post/xyz');

        Http::fake([
            'bsky.social/xrpc/com.atproto.server.createSession' => Http::response([
                'accessJwt' => 'jwt', 'did' => 'did:plc:abc',
            ]),
            'bsky.social/xrpc/app.bsky.feed.getPosts*' => Http::response([
                'posts' => [['likeCount' => 7, 'replyCount' => 2, 'repostCount' => 1]],
            ]),
        ]);

        $metricas = app(PublicadorBluesky::class)->metricasDoPost($destino);

        expect($metricas->visualizacoes)->toBeNull()
            ->and($metricas->curtidas)->toBe(7)
            ->and($metricas->comentarios)->toBe(2)
            ->and($metricas->compartilhamentos)->toBe(1);
    });

    it('contador ausente no Bluesky vira null — todos eles sao opcionais no lexicon', function () {
        // Num protocolo federado, o indice que ainda nao alcancou o post nao
        // manda o campo. "Ainda nao indexou" nao e "ninguem curtiu".
        $conta = contaParaMetrica(Plataforma::Bluesky);
        $destino = postNoAr($conta, 'at://did:plc:abc/app.bsky.feed.post/xyz');

        Http::fake([
            'bsky.social/xrpc/com.atproto.server.createSession' => Http::response([
                'accessJwt' => 'jwt', 'did' => 'did:plc:abc',
            ]),
            'bsky.social/xrpc/app.bsky.feed.getPosts*' => Http::response(['posts' => [['likeCount' => 3]]]),
        ]);

        $metricas = app(PublicadorBluesky::class)->metricasDoPost($destino);

        expect($metricas->curtidas)->toBe(3)
            ->and($metricas->comentarios)->toBeNull()
            ->and($metricas->compartilhamentos)->toBeNull();
    });
});

describe('o leitor nunca mexe no status da conta (DEC-96)', function () {
    it('resposta ruim devolve null e deixa a conta em paz', function () {
        // ⛔ Marcar conta e assunto da reconferencia diaria, que sabe distinguir
        // cota estourada de autorizacao perdida. Um contador que nao chegou nao
        // pode desligar a publicacao de ninguem.
        $conta = contaParaMetrica(Plataforma::Youtube);

        Http::fake(['googleapis.com/youtube/v3/channels*' => Http::response('', 403)]);

        expect(app(PublicadorYoutube::class)->metricasDaConta($conta))->toBeNull()
            ->and($conta->fresh()->status)->toBe(StatusConta::Ativa);
    });
});

describe('o registro entrega leitor so para quem tem', function () {
    it('⭐ as quatro redes do escopo respondem; rede sem leitor devolve null sem estourar', function () {
        /*
         * ⚠️ Rede sem metrica continua sendo caso NORMAL — nao e erro de
         * programacao, e por isso `leitorDe()` devolve `null` em vez de lancar.
         *
         * ⭐ O que mudou: as quatro redes do escopo do produto (doc 32) passaram
         * a responder "funcionou?". Sem as tres novas, a comparacao entre redes
         * seria um grafico de YouTube sozinho.
         */
        $registro = app(RegistroDePublicadores::class);

        foreach ([Plataforma::Youtube, Plataforma::Instagram, Plataforma::Facebook, Plataforma::Tiktok] as $rede) {
            expect($registro->leitorDe($rede))->not->toBeNull($rede->rotulo());
        }

        expect($registro->leitorDe(Plataforma::Bluesky))->not->toBeNull()
            // Rede com publicador e sem leitor: o caso comum, e ele nao estoura.
            ->and($registro->leitorDe(Plataforma::Pinterest))->toBeNull()
            ->and($registro->leitorDe(Plataforma::Discord))->toBeNull();
    });
});

describe('o comando metricas:atualizar', function () {
    it('⛔ nao mistura o numero de um dono com o de outro', function () {
        // O comando roda sem sessao e percorre contas de todos os donos. Se o
        // contexto vazasse entre uma conta e a seguinte, o numero de um cliente
        // seria gravado no outro.
        $contaA = contaParaMetrica(Plataforma::Bluesky);
        ContextoDoUsuario::limpar();
        $contaB = contaParaMetrica(Plataforma::Bluesky);
        ContextoDoUsuario::limpar();

        Http::fake([
            'bsky.social/xrpc/com.atproto.server.createSession' => Http::response([
                'accessJwt' => 'jwt', 'did' => 'did:plc:abc',
            ]),
            'bsky.social/xrpc/app.bsky.actor.getProfile*' => Http::response(['followersCount' => 42]),
        ]);

        $this->artisan('metricas:atualizar')->assertSuccessful();

        expect($contaA->fresh()->seguidores)->toBe(42)
            ->and($contaA->fresh()->usuario_id)->toBe($contaA->usuario_id)
            ->and($contaB->fresh()->seguidores)->toBe(42)
            ->and($contaB->fresh()->usuario_id)->not->toBe($contaA->usuario_id);
    });

    it('⭐ conta que falha nao impede as seguintes de atualizar', function () {
        // Sem isto, a primeira conta com autorizacao vencida encerraria o
        // comando e as demais ficariam com numero velho sem ninguem saber.
        contaParaMetrica(Plataforma::Youtube);
        ContextoDoUsuario::limpar();
        $bluesky = contaParaMetrica(Plataforma::Bluesky);
        ContextoDoUsuario::limpar();

        Http::fake([
            'googleapis.com/*' => Http::response('', 500),
            'bsky.social/xrpc/com.atproto.server.createSession' => Http::response([
                'accessJwt' => 'jwt', 'did' => 'did:plc:abc',
            ]),
            'bsky.social/xrpc/app.bsky.actor.getProfile*' => Http::response(['followersCount' => 9]),
        ]);

        $this->artisan('metricas:atualizar')->assertSuccessful();

        expect($bluesky->fresh()->seguidores)->toBe(9);
    });

    it('⛔ nao lê post antigo — a leitura tem que parar de crescer', function () {
        // No YouTube ela sai da MESMA cota que publica. Sem recorte, cada dia de
        // uso soma um dia de chamadas, para sempre.
        $conta = contaParaMetrica(Plataforma::Bluesky);
        $antigo = postNoAr($conta, 'at://did:plc:abc/app.bsky.feed.post/velho');
        $antigo->forceFill(['publicado_em' => now()->subDays(90)])->save();
        ContextoDoUsuario::limpar();

        Http::fake([
            'bsky.social/xrpc/com.atproto.server.createSession' => Http::response([
                'accessJwt' => 'jwt', 'did' => 'did:plc:abc',
            ]),
            'bsky.social/xrpc/app.bsky.actor.getProfile*' => Http::response(['followersCount' => 9]),
            'bsky.social/xrpc/app.bsky.feed.getPosts*' => Http::response(['posts' => [['likeCount' => 99]]]),
        ]);

        $this->artisan('metricas:atualizar')->assertSuccessful();

        expect($antigo->fresh()->curtidas)->toBeNull()
            ->and($antigo->fresh()->metricas_lidas_em)->toBeNull();
    });

    it('grava a data da leitura, que e o que a tela mostra', function () {
        $conta = contaParaMetrica(Plataforma::Bluesky);
        ContextoDoUsuario::limpar();

        Http::fake([
            'bsky.social/xrpc/com.atproto.server.createSession' => Http::response([
                'accessJwt' => 'jwt', 'did' => 'did:plc:abc',
            ]),
            'bsky.social/xrpc/app.bsky.actor.getProfile*' => Http::response(['followersCount' => 15]),
        ]);

        $this->artisan('metricas:atualizar')->assertSuccessful();

        expect($conta->fresh()->metricas_lidas_em)->not->toBeNull();
    });

    it('⛔ leitura que falha NAO apaga o numero que ja estava guardado', function () {
        // Trocar um dado velho por nenhum dado e piorar: a tela diz "lido ha 3
        // dias", que e informacao, em vez de nao dizer nada.
        $conta = contaParaMetrica(Plataforma::Bluesky);
        $conta->forceFill(['seguidores' => 77, 'metricas_lidas_em' => now()->subDays(3)])->save();
        ContextoDoUsuario::limpar();

        Http::fake(['bsky.social/*' => Http::response('', 500)]);

        $this->artisan('metricas:atualizar')->assertSuccessful();

        expect($conta->fresh()->seguidores)->toBe(77);
    });
});

describe('as frases que substituem o numero (DEC-94)', function () {
    it('o Bluesky diz que nao conta visualizacao, e o YouTube nao diz nada disso', function () {
        expect(Plataforma::Bluesky->notaDoPost())->toContain('não conta visualizações')
            ->and(Plataforma::Youtube->notaDoPost())->toBeNull();
    });

    it('o YouTube avisa do arredondamento, porque senao o numero parece errado', function () {
        // Quem tem 1.234 inscritos ve 1.230 aqui e 1.234 no YouTube Studio. Sem
        // a frase, a conclusao e que o nosso numero esta errado.
        expect(Plataforma::Youtube->notaDosSeguidores())->toContain('arredonda')
            ->and(Plataforma::Bluesky->notaDosSeguidores())->toBeNull();
    });

    it('rede sem frase cadastrada devolve null, e nao a chave de tradução', function () {
        // ⚠️ `__()` devolve a propria chave quando nao acha a traducao. Sem o
        // tratamento, a tela mostraria "rotulos.nota_de_metrica.post.tiktok".
        expect(Plataforma::Tiktok->notaDoPost())->toBeNull()
            ->and(Plataforma::Tiktok->notaDosSeguidores())->toBeNull();
    });
});

describe('o grafico de comparacao entre posts (DEC-94)', function () {
    /** Um post com metrica ja lida. */
    function postComMetrica(ContaSocial $conta, string $titulo, array $numeros): Destino
    {
        $publicacao = Publicacao::factory()->doUsuario($conta->usuario)->enviada()->create([
            'grupo_id' => $conta->grupo_id,
            'titulo' => $titulo,
        ]);

        $destino = Destino::factory()->create([
            'publicacao_id' => $publicacao->id,
            'conta_social_id' => $conta->id,
            'status' => StatusDestino::Publicado,
            'identificador_externo' => 'post-'.$titulo,
            'url_publicada' => 'https://exemplo.test/p/'.$titulo,
            'publicado_em' => now()->subDay(),
        ]);

        $destino->forceFill($numeros + ['metricas_lidas_em' => now()])->save();

        return $destino;
    }

    it('⭐ cada rede compara na medida DELA — e nunca na da outra', function () {
        /*
         * ⛔ O coracao do DEC-94. O YouTube ordena por visualizacao; o Bluesky
         * NAO TEM visualizacao e ordena por curtida. Uma tabela com coluna igual
         * para as duas obrigaria a inventar valor para a celula que nao existe.
         */
        $youtube = contaParaMetrica(Plataforma::Youtube);
        postComMetrica($youtube, 'yt-grande', ['visualizacoes' => 900, 'curtidas' => 1]);
        postComMetrica($youtube, 'yt-pequeno', ['visualizacoes' => 100, 'curtidas' => 90]);
        $dono = $youtube->usuario;

        $bluesky = ContaSocial::factory()->doUsuario($dono)->daPlataforma(Plataforma::Bluesky)
            ->comCredencial('x')->create(['grupo_id' => $youtube->grupo_id]);
        postComMetrica($bluesky, 'bs-a', ['visualizacoes' => null, 'curtidas' => 7]);
        postComMetrica($bluesky, 'bs-b', ['visualizacoes' => null, 'curtidas' => 3]);

        ContextoDoUsuario::limpar();

        $this->actingAs($dono)
            ->get('/publicacoes')
            ->assertInertia(fn ($p) => $p->where('comparativo', function ($comparativo) {
                $porRede = collect($comparativo)->keyBy('rede');

                expect($porRede)->toHaveCount(2)
                    ->and($porRede['youtube']['medida'])->toBe('visualizações')
                    ->and($porRede['bluesky']['medida'])->toBe('curtidas')
                    // A ordem segue a medida daquela rede: no YouTube o de 900
                    // views vem primeiro, ainda que tenha 1 curtida.
                    ->and($porRede['youtube']['barras'][0]['valor'])->toBe(900)
                    ->and($porRede['bluesky']['barras'][0]['valor'])->toBe(7);

                return true;
            }));
    });

    it('⛔ post sozinho na rede NAO vira grafico — comparacao exige dois', function () {
        // Uma barra de 100% ao lado de nada nao informa. Que existe um post, a
        // lista logo abaixo ja diz.
        $conta = contaParaMetrica(Plataforma::Bluesky);
        postComMetrica($conta, 'unico', ['curtidas' => 5]);
        $dono = $conta->usuario;
        ContextoDoUsuario::limpar();

        $this->actingAs($dono)
            ->get('/publicacoes')
            ->assertInertia(fn ($p) => $p->where('comparativo', []));
    });

    it('⭐ zero em tudo vira ESTADO com frase, nao grafico vazio', function () {
        /*
         * ⚠️ No YouTube isto e o esperado hoje: enquanto a auditoria nao passa,
         * todo video sobe privado, e video privado nao recebe visualizacao. Sem
         * a frase, a tela pareceria quebrada justamente quando esta certa.
         */
        $conta = contaParaMetrica(Plataforma::Youtube);
        postComMetrica($conta, 'a', ['visualizacoes' => 0]);
        postComMetrica($conta, 'b', ['visualizacoes' => 0]);
        $dono = $conta->usuario;
        ContextoDoUsuario::limpar();

        $this->actingAs($dono)
            ->get('/publicacoes')
            ->assertInertia(fn ($p) => $p->where('comparativo', function ($comparativo) {
                expect($comparativo[0]['tudoZerado'])->toBeTrue()
                    ->and($comparativo[0]['notaDeZero'])->toContain('privado');

                return true;
            }));
    });

    it('⛔ post sem leitura fica de fora — ausencia nao vira barra de zero', function () {
        $conta = contaParaMetrica(Plataforma::Bluesky);
        postComMetrica($conta, 'lido-1', ['curtidas' => 4]);
        postComMetrica($conta, 'lido-2', ['curtidas' => 2]);
        // Este nunca foi lido: nao tem numero, e numero que nao existe nao entra.
        postNoAr($conta, 'sem-leitura');
        $dono = $conta->usuario;
        ContextoDoUsuario::limpar();

        $this->actingAs($dono)
            ->get('/publicacoes')
            ->assertInertia(fn ($p) => $p->where('comparativo', function ($comparativo) {
                expect($comparativo[0]['barras'])->toHaveCount(2);

                return true;
            }));
    });

    it('⛔ nao mostra post de outro dono', function () {
        $meu = contaParaMetrica(Plataforma::Bluesky);
        postComMetrica($meu, 'meu-1', ['curtidas' => 4]);
        postComMetrica($meu, 'meu-2', ['curtidas' => 2]);
        $dono = $meu->usuario;
        ContextoDoUsuario::limpar();

        $alheio = contaParaMetrica(Plataforma::Bluesky);
        postComMetrica($alheio, 'alheio-1', ['curtidas' => 999]);
        postComMetrica($alheio, 'alheio-2', ['curtidas' => 888]);
        ContextoDoUsuario::limpar();

        $this->actingAs($dono)
            ->get('/publicacoes')
            ->assertInertia(fn ($p) => $p->where('comparativo', function ($comparativo) {
                $valores = collect($comparativo[0]['barras'])->pluck('valor')->all();

                expect($valores)->toBe([4, 2]);

                return true;
            }));
    });

    it('rede sem medida de comparacao nao entra', function () {
        // ⚠️ `metricaDeComparacao()` devolve null para quem nao tem numero
        // comparavel — e silencio nao promete.
        expect(Plataforma::Youtube->metricaDeComparacao())->toBe('visualizacoes')
            ->and(Plataforma::Bluesky->metricaDeComparacao())->toBe('curtidas')
            ->and(Plataforma::Tiktok->metricaDeComparacao())->toBeNull();
    });
});

describe('a tela recebe o que precisa', function () {
    it('a conta leva seguidores, a ressalva e a data — e nunca o token', function () {
        $conta = contaParaMetrica(Plataforma::Youtube);
        $conta->forceFill(['seguidores' => 1230, 'metricas_lidas_em' => now()])->save();
        $dono = $conta->usuario;
        ContextoDoUsuario::limpar();

        $this->actingAs($dono)
            ->get('/painel')
            ->assertInertia(fn ($p) => $p->where('redes', function ($redes) {
                $youtube = collect($redes)->firstWhere('valor', 'youtube');
                $conta = $youtube['contas'][0];

                expect($conta['seguidores'])->toBe(1230)
                    ->and($conta['metricasLidas'])->toContain('lido hoje')
                    ->and($conta['seguidoresNota'])->toContain('arredonda')
                    ->and($conta)->not->toHaveKey('access_token');

                return true;
            }));
    });

    it('⛔ o AtualizarMetricas nao e chamado por nenhuma tela', function () {
        // Chamar a rede no meio do carregamento faria a pagina travar no dia em
        // que a rede estivesse lenta — e o numero nao vale isso.
        $usados = collect(glob(app_path('Http/Controllers/**/*.php'), GLOB_BRACE))
            ->merge(glob(app_path('Http/Controllers/*.php')))
            ->filter(fn ($arquivo) => str_contains((string) file_get_contents($arquivo), AtualizarMetricas::class));

        expect($usados)->toBeEmpty();
    });
});

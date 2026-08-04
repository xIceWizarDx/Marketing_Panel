<?php

use App\Enums\Plataforma;
use App\Enums\StatusDestino;
use App\Jobs\ConciliarDestinoJob;
use App\Jobs\PublicarDestinoJob;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Publicadores\RegistroDePublicadores;
use App\Services\PublicacaoService;
use App\Support\ContextoDoUsuario;
use App\Support\Midia\EspecificacaoDaRede;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/*
| Guardiao da META — Facebook e Instagram.
|
| Uma conexao acende as duas redes: a conta do Instagram fica pendurada numa
| Pagina do Facebook. Por isso o guardiao e um so.
|
| ⚠️ Cada teste trava um achado da documentacao oficial. Os que mais importam:
|   - criar container NAO publica (o post nao existiria)
|   - `processing_phase: completed` NAO e publicado
|   - `is_transient` decide retentativa — a rede responde, nao adivinhamos
*/

beforeEach(function () {
    ContextoDoUsuario::limpar();
    Storage::fake('local');
    Queue::fake();
    config([
        'services.meta.client_id' => 'app-de-teste',
        'services.meta.client_secret' => 'segredo-de-teste',
    ]);
});

afterEach(fn () => ContextoDoUsuario::limpar());

function destinoNaMeta(Plataforma $rede, array $opcoes = [], array $publicacao = []): Destino
{
    $dono = cliente();
    ContextoDoUsuario::definir($dono);

    $midia = Midia::factory()->doUsuario($dono)->create(['tamanho_bytes' => 1024]);

    $criada = Publicacao::factory()->doUsuario($dono)->enviada()->create(array_merge([
        'midia_id' => $midia->id,
        'titulo' => 'Meu corte',
        'legenda' => 'Olha isso',
        'hashtags' => ['corte'],
    ], $publicacao));

    Storage::disk('local')->put($midia->caminho, str_repeat('v', 1024));

    $conta = ContaSocial::factory()
        ->doUsuario($dono)
        ->daPlataforma($rede)
        ->comCredencial('token-da-pagina')
        ->create(['identificador_externo' => '777', 'nome_exibicao' => 'Página de Teste']);

    $destino = Destino::factory()->create([
        'publicacao_id' => $criada->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Pendente,
        'opcoes' => $opcoes ?: null,
    ]);

    ContextoDoUsuario::limpar();

    return $destino;
}

function enviarNaMeta(Destino $destino): void
{
    (new PublicarDestinoJob($destino->id))->handle(
        app(PublicacaoService::class),
        app(RegistroDePublicadores::class),
    );
}

function conciliarNaMeta(Destino $destino): void
{
    (new ConciliarDestinoJob($destino->id))->handle(
        app(PublicacaoService::class),
        app(RegistroDePublicadores::class),
    );
}

describe('a conexão — uma só para as duas redes', function () {
    /** O retorno completo do Facebook: token curto, token longo, Páginas. */
    function metaOk(array $sobrescrever = []): void
    {
        Http::fake(array_merge([
            // ⚠️ Confere o que foi CONCEDIDO — a tela do Facebook deixa recusar
            // permissao uma a uma (licao R-2 do YouTube).
            'graph.facebook.com/*/me/permissions*' => Http::response(['data' => [
                ['permission' => 'pages_manage_posts', 'status' => 'granted'],
                ['permission' => 'instagram_content_publish', 'status' => 'granted'],
                ['permission' => 'pages_read_engagement', 'status' => 'granted'],
            ]]),
            'graph.facebook.com/*/oauth/access_token*' => Http::sequence()
                ->push(['access_token' => 'token-curto'])
                ->push(['access_token' => 'token-longo-60-dias']),
            'graph.facebook.com/*/me/accounts*' => Http::response(['data' => [[
                'id' => '777',
                'name' => 'Página de Teste',
                'access_token' => 'token-da-pagina-nao-expira',
                'tasks' => ['CREATE_CONTENT', 'MANAGE'],
                'picture' => ['data' => ['url' => 'https://scontent.xx/p.jpg']],
                'instagram_business_account' => [
                    'id' => '888',
                    'username' => 'canal_teste',
                    'profile_picture_url' => 'https://scontent.xx/i.jpg',
                ],
            ]]]),
        ], $sobrescrever));
    }

    it('⭐ uma autorização conecta a Página E o Instagram vinculado', function () {
        $dono = cliente();
        metaOk();

        $inicio = $this->actingAs($dono)->get('/conexoes/meta');
        $inicio->assertRedirect();

        $endereco = $inicio->headers->get('Location');
        expect($endereco)->toContain('facebook.com')
            ->toContain('pages_manage_posts')
            ->toContain('instagram_content_publish')
            // ⛔ Escopo mínimo (DEC-41): nada de anúncios nem de mensagens.
            ->not->toContain('ads_management')
            ->not->toContain('pages_messaging');

        parse_str(parse_url($endereco, PHP_URL_QUERY), $query);

        $this->actingAs($dono)
            ->get('/conexoes/meta/retorno?code=codigo&state='.$query['state'])
            ->assertRedirect(route('painel'))
            ->assertSessionHas('sucesso');

        ContextoDoUsuario::definir($dono);

        expect(ContaSocial::where('plataforma', Plataforma::Facebook)->count())->toBe(1)
            ->and(ContaSocial::where('plataforma', Plataforma::Instagram)->count())->toBe(1);

        $instagram = ContaSocial::where('plataforma', Plataforma::Instagram)->first();
        expect($instagram->nome_exibicao)->toBe('@canal_teste')
            ->and($instagram->identificador_externo)->toBe('888')
            // O Instagram publica com o token da PÁGINA, não com um próprio.
            ->and($instagram->credencial->access_token)->toBe('token-da-pagina-nao-expira');
    });

    it('⭐ guarda o token LONGO, não o curto', function () {
        $dono = cliente();
        metaOk();

        $this->actingAs($dono)->get('/conexoes/meta');
        $this->actingAs($dono)->get('/conexoes/meta/retorno?code=c&state='.session('meta.estado'));

        // ⚠️ A troca tem que ser na conexão: token expirado não pode ser
        // trocado depois, e aí a conexão se perde sem conserto.
        Http::assertSent(fn ($req) => str_contains($req->url(), 'grant_type=fb_exchange_token'));
    });

    it('não deixa o segredo legível no banco', function () {
        $dono = cliente();
        metaOk();

        $this->actingAs($dono)->get('/conexoes/meta');
        $this->actingAs($dono)->get('/conexoes/meta/retorno?code=c&state='.session('meta.estado'));

        ContextoDoUsuario::definir($dono);
        $conta = ContaSocial::where('plataforma', Plataforma::Facebook)->first();
        $bruto = DB::table('credenciais')->where('conta_social_id', $conta->id)->first();

        expect($bruto->access_token)->not->toContain('token-da-pagina-nao-expira');
    });

    it('explica que sem Página não há publicação', function () {
        $dono = cliente();
        metaOk(['graph.facebook.com/*/me/accounts*' => Http::response(['data' => []])]);

        $this->actingAs($dono)->get('/conexoes/meta');

        $this->actingAs($dono)
            ->get('/conexoes/meta/retorno?code=c&state='.session('meta.estado'))
            ->assertSessionHas('erro', fn ($m) => str_contains($m, 'Página'));
    });

    it('⚠️ ignora Página onde a pessoa não pode publicar', function () {
        // A permissão vem por PÁGINA, em `tasks`. Sem `CREATE_CONTENT`, a Página
        // apareceria conectada e falharia no primeiro vídeo — a lição R-2.
        $dono = cliente();
        metaOk(['graph.facebook.com/*/me/accounts*' => Http::response(['data' => [[
            'id' => '999', 'name' => 'Só Leitura', 'access_token' => 't',
            'tasks' => ['ANALYZE'],
        ]]])]);

        $this->actingAs($dono)->get('/conexoes/meta');

        $this->actingAs($dono)
            ->get('/conexoes/meta/retorno?code=c&state='.session('meta.estado'))
            ->assertSessionHas('erro', fn ($m) => str_contains($m, 'Editor'));

        ContextoDoUsuario::definir($dono);
        expect(ContaSocial::count())->toBe(0);
    });

    it('⚠️ aceita Página da experiência NOVA (PROFILE_PLUS_*)', function () {
        // Existem duas nomenclaturas de permissão. Páginas antigas devolvem
        // `CREATE_CONTENT`; as novas — hoje o padrão — devolvem
        // `PROFILE_PLUS_CREATE_CONTENT`. Conferir só a primeira rejeitaria a
        // Página que a pessoa acabou de criar, na própria Página dela.
        $dono = cliente();
        metaOk(['graph.facebook.com/*/me/accounts*' => Http::response(['data' => [[
            'id' => '777',
            'name' => 'Página Nova',
            'access_token' => 'token-da-pagina',
            'tasks' => ['PROFILE_PLUS_CREATE_CONTENT'],
        ]]])]);

        $this->actingAs($dono)->get('/conexoes/meta');

        $this->actingAs($dono)
            ->get('/conexoes/meta/retorno?code=c&state='.session('meta.estado'))
            ->assertSessionHas('sucesso');

        ContextoDoUsuario::definir($dono);
        expect(ContaSocial::where('plataforma', Plataforma::Facebook)->count())->toBe(1);
    });

    it('⭐ recusa quando a permissão de publicar foi desmarcada', function () {
        // O Facebook não devolve os escopos na troca do código: quem responde é
        // `/me/permissions`, com `granted` ou `declined` por permissão.
        $dono = cliente();
        metaOk(['graph.facebook.com/*/me/permissions*' => Http::response(['data' => [
            ['permission' => 'pages_manage_posts', 'status' => 'declined'],
            ['permission' => 'instagram_content_publish', 'status' => 'declined'],
        ]])]);

        $this->actingAs($dono)->get('/conexoes/meta');

        $this->actingAs($dono)
            ->get('/conexoes/meta/retorno?code=c&state='.session('meta.estado'))
            ->assertSessionHas('erro', fn ($m) => str_contains($m, 'publicar'));

        ContextoDoUsuario::definir($dono);
        expect(ContaSocial::count())->toBe(0);
    });

    it('recusa o retorno sem o `state` da sessão (CSRF)', function () {
        metaOk();

        $this->actingAs(cliente())
            ->get('/conexoes/meta/retorno?code=x&state=forjado')
            ->assertSessionHas('erro');

        ContextoDoUsuario::definir(cliente());
        expect(ContaSocial::count())->toBe(0);
    });
});

describe('o Facebook — três fases', function () {
    function facebookOk(mixed $conferencia = null): void
    {
        Http::fake([
            'rupload.facebook.com/*' => Http::response(['success' => true]),
            'graph.facebook.com/*/777/video_reels' => Http::sequence()
                ->push(['video_id' => 'video-123', 'upload_url' => 'https://rupload.facebook.com/video-upload/video-123'])
                ->push(['success' => true]),
            'graph.facebook.com/*/video-123*' => $conferencia ?? Http::response([
                'status' => [
                    'processing_phase' => ['status' => 'completed'],
                    'publishing_phase' => ['status' => 'completed', 'publish_status' => 'published'],
                ],
            ]),
        ]);
    }

    it('abre a sessão, envia e fica aguardando confirmação', function () {
        $destino = destinoNaMeta(Plataforma::Facebook);
        facebookOk();

        enviarNaMeta($destino);

        // ⭐ DEC-31: aceito ≠ está no ar.
        expect($destino->fresh())
            ->status->toBe(StatusDestino::Processando)
            ->identificador_externo->toBe('video-123')
            ->url_publicada->toBeNull();
    });

    it('⭐ guarda o identificador ANTES de enviar o arquivo', function () {
        $destino = destinoNaMeta(Plataforma::Facebook);

        Http::fake([
            'graph.facebook.com/*/777/video_reels' => Http::response(['video_id' => 'video-123']),
            'rupload.facebook.com/*' => Http::response([], 500),
        ]);

        enviarNaMeta($destino);

        // Sem isso, a próxima tentativa recomeça e o mesmo vídeo sobe duas vezes.
        expect($destino->fresh()->handle_externo)->toBe('video-123');
    });

    it('⚠️ usa `finish`, não `complete`', function () {
        // A documentação se contradiz; o exemplo executável manda.
        $destino = destinoNaMeta(Plataforma::Facebook);
        facebookOk();

        enviarNaMeta($destino);

        Http::assertSent(fn ($req) => str_contains($req->url(), 'video_reels')
            && ($req->data()['upload_phase'] ?? null) === 'finish');
    });

    it('⛔ NÃO corta o texto da pessoa', function () {
        $destino = destinoNaMeta(Plataforma::Facebook, publicacao: ['legenda' => str_repeat('a', 3000)]);
        facebookOk();

        enviarNaMeta($destino);

        Http::assertSent(function ($req) {
            if (! str_contains($req->url(), 'video_reels')) {
                return false;
            }

            return ! isset($req->data()['description'])
                || strlen($req->data()['description']) >= 3000;
        });
    });

    it('⭐ `processing_phase: completed` NÃO é publicado', function () {
        // A armadilha: a Meta terminou de processar o arquivo, mas o post ainda
        // não está no ar. Conciliar pela fase errada marcaria publicado cedo.
        $destino = destinoNaMeta(Plataforma::Facebook);
        facebookOk(Http::response(['status' => [
            'processing_phase' => ['status' => 'completed'],
            'publishing_phase' => ['status' => 'in_progress'],
        ]]));

        enviarNaMeta($destino);
        conciliarNaMeta($destino->fresh());

        expect($destino->fresh())
            ->status->toBe(StatusDestino::Processando)
            ->url_publicada->toBeNull();
    });

    it('só marca publicado com `publish_status: published` e link', function () {
        $destino = destinoNaMeta(Plataforma::Facebook);
        facebookOk();

        enviarNaMeta($destino);
        conciliarNaMeta($destino->fresh());

        expect($destino->fresh())
            ->status->toBe(StatusDestino::Publicado)
            ->url_publicada->toBe('https://www.facebook.com/reel/video-123');
    });

    it('rascunho não é publicação', function () {
        $destino = destinoNaMeta(Plataforma::Facebook);
        facebookOk(Http::response(['status' => [
            'publishing_phase' => ['status' => 'completed', 'publish_status' => 'draft'],
        ]]));

        enviarNaMeta($destino);
        conciliarNaMeta($destino->fresh());

        expect($destino->fresh())->status->toBe(StatusDestino::Falhou);
    });
});

describe('o Instagram — container e publicação', function () {
    function instagramOk(mixed $conferencia = null): void
    {
        Http::fake([
            'rupload.facebook.com/*' => Http::response(['success' => true]),
            'graph.facebook.com/*/777/media' => Http::response(['id' => 'container-abc']),
            'graph.facebook.com/*/777/media_publish' => Http::response(['id' => 'midia-xyz']),
            'graph.facebook.com/*/midia-xyz*' => $conferencia ?? Http::response([
                'permalink' => 'https://www.instagram.com/reel/xyz/',
                'media_product_type' => 'REELS',
            ]),
        ]);
    }

    it('⛔ criar o container NÃO publica — o segundo passo é obrigatório', function () {
        $destino = destinoNaMeta(Plataforma::Instagram);
        instagramOk();

        enviarNaMeta($destino);

        // O identificador tem que ser o da MÍDIA, não o do container: o
        // container morre, e é a mídia que a conciliação relê.
        expect($destino->fresh()->identificador_externo)->toBe('midia-xyz');

        Http::assertSent(fn ($req) => str_contains($req->url(), 'media_publish')
            && ($req->data()['creation_id'] ?? null) === 'container-abc');
    });

    it('⭐ guarda o container ANTES de enviar o arquivo', function () {
        $destino = destinoNaMeta(Plataforma::Instagram);

        Http::fake([
            'graph.facebook.com/*/777/media' => Http::response(['id' => 'container-abc']),
            'rupload.facebook.com/*' => Http::response([], 500),
        ]);

        enviarNaMeta($destino);

        expect($destino->fresh()->handle_externo)->toBe('container-abc');
    });

    it('pede envio direto, não busca por URL pública', function () {
        // Sem `upload_type=resumable` a Meta tentaria BAIXAR o vídeo de uma URL
        // pública — e o arquivo do cliente não fica exposto na internet.
        $destino = destinoNaMeta(Plataforma::Instagram);
        instagramOk();

        enviarNaMeta($destino);

        Http::assertSent(fn ($req) => str_contains($req->url(), '/777/media')
            && ($req->data()['upload_type'] ?? null) === 'resumable'
            && ($req->data()['media_type'] ?? null) === 'REELS');
    });

    it('declara conteúdo feito por IA quando a pessoa marca', function () {
        $destino = destinoNaMeta(Plataforma::Instagram, opcoes: ['feito_com_ia' => true]);
        instagramOk();

        enviarNaMeta($destino);

        Http::assertSent(fn ($req) => str_contains($req->url(), '/777/media')
            && ($req->data()['is_ai_generated'] ?? null) === 'true');
    });

    it('marca publicado com o link do post', function () {
        $destino = destinoNaMeta(Plataforma::Instagram);
        instagramOk();

        enviarNaMeta($destino);
        conciliarNaMeta($destino->fresh());

        expect($destino->fresh())
            ->status->toBe(StatusDestino::Publicado)
            ->url_publicada->toBe('https://www.instagram.com/reel/xyz/');
    });

    it('sem link não marca publicado', function () {
        // Sem prova não é publicação (DEC-31).
        $destino = destinoNaMeta(Plataforma::Instagram);
        instagramOk(Http::response(['media_product_type' => 'REELS']));

        enviarNaMeta($destino);
        conciliarNaMeta($destino->fresh());

        expect($destino->fresh())->status->toBe(StatusDestino::Processando);
    });
});

describe('os erros da Meta', function () {
    it('⭐ `is_transient` decide a retentativa — a rede responde por nós', function () {
        $destino = destinoNaMeta(Plataforma::Facebook);

        Http::fake(['graph.facebook.com/*' => Http::response([
            'error' => ['code' => 1, 'error_subcode' => 2207001, 'is_transient' => true],
        ], 400)]);

        enviarNaMeta($destino);

        // Erro passageiro volta para a fila, não vira falha.
        expect($destino->fresh())->status->toBe(StatusDestino::Pendente);
    });

    it('erro definitivo não queima as tentativas', function () {
        $destino = destinoNaMeta(Plataforma::Facebook);

        Http::fake(['graph.facebook.com/*' => Http::response([
            'error' => ['code' => 352, 'error_subcode' => 2207026, 'is_transient' => false],
        ], 400)]);

        enviarNaMeta($destino);

        expect($destino->fresh())
            ->status->toBe(StatusDestino::Falhou)
            ->erro_mensagem->toContain('MP4');
    });

    it('⭐ limite diário é ESPERA, não falha (DEC-24)', function () {
        $destino = destinoNaMeta(Plataforma::Instagram);

        Http::fake([
            'graph.facebook.com/*/777/media' => Http::response(['id' => 'container-abc']),
            'rupload.facebook.com/*' => Http::response(['success' => true]),
            'graph.facebook.com/*/777/media_publish' => Http::response([
                'error' => ['code' => 9, 'error_subcode' => 2207042, 'is_transient' => false],
            ], 400),
        ]);

        enviarNaMeta($destino);

        // Tratar como erro queimaria as 3 tentativas contra uma cota que só
        // volta amanhã.
        expect($destino->fresh())
            ->status->toBe(StatusDestino::AguardandoJanela)
            ->erro_mensagem->toContain('limite de publicações do dia');
    });

    it('⚠️ "ainda processando" não é falha', function () {
        // `2207027` e `2207008` se resolvem esperando. Tratar como falha
        // derrubaria publicações que iam dar certo.
        $destino = destinoNaMeta(Plataforma::Instagram);

        Http::fake([
            'graph.facebook.com/*/777/media' => Http::response(['id' => 'container-abc']),
            'rupload.facebook.com/*' => Http::response(['success' => true]),
            'graph.facebook.com/*/777/media_publish' => Http::response([
                'error' => ['code' => 9007, 'error_subcode' => 2207027, 'is_transient' => false],
            ], 400),
        ]);

        enviarNaMeta($destino);

        expect($destino->fresh())->status->toBe(StatusDestino::Pendente);
    });
});

describe('os limites de cada rede', function () {
    it('⛔ 90s no Facebook, 180s no Instagram — o mesmo corte, redes diferentes', function () {
        $facebook = EspecificacaoDaRede::de(Plataforma::Facebook);
        $instagram = EspecificacaoDaRede::de(Plataforma::Instagram);

        // Um corte de 2 minutos publica no Instagram e é recusado no Facebook.
        // A recusa tem que vir ANTES de subir 300 MB.
        expect($facebook->duracaoMaxima)->toBe(90)
            ->and($instagram->duracaoMaxima)->toBeGreaterThan(120);
    });

    it('as duas aceitam MOV — vídeo de iPhone passa', function () {
        foreach ([Plataforma::Facebook, Plataforma::Instagram] as $rede) {
            expect(EspecificacaoDaRede::de($rede)->conferirContainer('video/quicktime'))
                ->toBeNull();
        }
    });

    it('recusa formato que a Meta não aceita, antes de enviar', function () {
        expect(EspecificacaoDaRede::de(Plataforma::Instagram)->conferirContainer('video/webm'))
            ->not->toBeNull();
    });
});

describe('a retentativa — que estava morta', function () {
    it('⚠️ devolver à fila REALMENTE reenfileira', function () {
        // Bug encontrado na revisão: `devolverParaFila` só mudava o estado para
        // `pendente`, e nenhum job era criado. O destino ficava parado para
        // sempre, esperando uma tentativa que nunca vinha — e o teto de 3
        // tentativas nunca era alcançado porque a segunda nunca acontecia.
        //
        // Valia para TODAS as redes, não só a Meta.
        $destino = destinoNaMeta(Plataforma::Facebook);
        Http::fake(['graph.facebook.com/*' => Http::response(
            ['error' => ['code' => 1, 'is_transient' => true]], 500)]);

        enviarNaMeta($destino);

        expect($destino->fresh()->status)->toBe(StatusDestino::Pendente);
        Queue::assertPushed(PublicarDestinoJob::class);
    });

    it('para de insistir quando as tentativas acabam', function () {
        $destino = destinoNaMeta(Plataforma::Facebook);
        ContextoDoUsuario::semEscopo(fn () => $destino->forceFill([
            'tentativas_feitas' => PublicacaoService::MAX_TENTATIVAS - 1,
        ])->save());

        Http::fake(['graph.facebook.com/*' => Http::response(
            ['error' => ['code' => 1, 'is_transient' => true]], 500)]);

        enviarNaMeta($destino);

        // Insistir para sempre seria pior que falhar: a pessoa nunca saberia.
        expect($destino->fresh()->status)->toBe(StatusDestino::Falhou);
        Queue::assertNotPushed(PublicarDestinoJob::class);
    });
});

describe('só campos documentados', function () {
    it('⚠️ não pede campo que não existe no nó Video', function () {
        // O Graph API NÃO devolve nulo para campo inexistente: derruba a chamada
        // inteira com o erro 100. Pedir um `permalink_url` — que não está na
        // lista de campos do nó Video — faria TODA conciliação do Facebook
        // falhar, e cada publicação seria marcada como falha sem ter falhado.
        $destino = destinoNaMeta(Plataforma::Facebook);
        facebookOk();

        enviarNaMeta($destino);
        conciliarNaMeta($destino->fresh());

        Http::assertSent(function ($req) {
            if (! str_contains($req->url(), 'video-123')) {
                return false;
            }

            return ! str_contains($req->url(), 'permalink');
        });
    });

    it('o Instagram pede `permalink`, que é campo documentado', function () {
        $destino = destinoNaMeta(Plataforma::Instagram);
        instagramOk();

        enviarNaMeta($destino);
        conciliarNaMeta($destino->fresh());

        // Documentado no nó Instagram Media, junto de `media_product_type`.
        Http::assertSent(fn ($req) => str_contains($req->url(), 'midia-xyz')
            && str_contains($req->url(), 'permalink'));
    });
});

describe('a retomada do envio', function () {
    it('⭐ o Facebook retoma pelo que a rede diz ter recebido', function () {
        $destino = destinoNaMeta(Plataforma::Facebook);
        ContextoDoUsuario::semEscopo(fn () => $destino->forceFill(['handle_externo' => 'video-123'])->save());

        Http::fake([
            // O caminho documentado é o Graph, não o rupload: `status` do vídeo
            // com `uploading_phase.bytes_transfered`.
            'graph.facebook.com/*/video-123*' => Http::response(['status' => [
                'uploading_phase' => ['status' => 'in_progress', 'bytes_transfered' => 512],
            ]]),
            'rupload.facebook.com/*' => Http::response(['success' => true]),
            'graph.facebook.com/*/777/video_reels' => Http::response(['success' => true]),
        ]);

        enviarNaMeta($destino);

        // Recomeçar do zero desperdiçaria o que já subiu — e em 300 MB isso dói.
        Http::assertSent(fn ($req) => str_contains($req->url(), 'rupload')
            && $req->header('offset') === ['512']);
    });

    it('⚠️ o Instagram recomeça: a documentação não descreve retomada', function () {
        // Continuar de um ponto adivinhado corromperia o arquivo, o que é bem
        // pior que reenviar.
        $destino = destinoNaMeta(Plataforma::Instagram);
        ContextoDoUsuario::semEscopo(fn () => $destino->forceFill(['handle_externo' => 'container-abc'])->save());

        instagramOk();
        enviarNaMeta($destino);

        Http::assertSent(fn ($req) => str_contains($req->url(), 'rupload')
            && $req->header('offset') === ['0']);
    });
});

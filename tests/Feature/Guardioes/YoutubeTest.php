<?php

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Enums\StatusDestino;
use App\Jobs\ConciliarDestinoJob;
use App\Jobs\PublicarDestinoJob;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Models\Usuario;
use App\Publicadores\RegistroDePublicadores;
use App\Services\ConexaoComYoutube;
use App\Services\PublicacaoService;
use App\Services\TokenDoGoogle;
use App\Support\ContextoDoUsuario;
use App\Support\FalhaDeConexao;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/*
| Guardiao do YOUTUBE.
|
| A rede mais exigente: envio em pedacos com retomada, processamento assincrono,
| cota diaria e dois portoes de aprovacao. Se o contrato do Publicador aguenta
| aqui, aguenta nas outras.
|
| ⚠️ Cada teste deste arquivo trava um achado da documentacao oficial — varios
| deles corrigem coisa que eu tinha escrito de memoria e estava errada.
*/

const SESSAO = 'https://upload.googleapis.com/sessao/abc123';

beforeEach(function () {
    ContextoDoUsuario::limpar();
    Storage::fake('local');
    Queue::fake();
    config([
        'services.google.client_id' => 'id-de-teste',
        'services.google.client_secret' => 'segredo-de-teste',
    ]);
});

afterEach(fn () => ContextoDoUsuario::limpar());

function destinoNoYoutube(array $publicacao = [], array $opcoes = []): Destino
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
        ->daPlataforma(Plataforma::Youtube)
        ->comCredencial('token-valido')
        ->create(['nome_exibicao' => 'Canal de Teste']);

    $destino = Destino::factory()->create([
        'publicacao_id' => $criada->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Pendente,
        'opcoes' => $opcoes ?: null,
    ]);

    ContextoDoUsuario::limpar();

    return $destino;
}

/** Uma conta do YouTube já conectada, sem passar pelo OAuth. */
function contaDoYoutube(Usuario $dono): ContaSocial
{
    return ContaSocial::factory()
        ->doUsuario($dono)
        ->daPlataforma(Plataforma::Youtube)
        ->comCredencial('token-valido')
        ->create(['nome_exibicao' => 'Canal de Teste']);
}

function enviarNoYoutube(Destino $destino): void
{
    (new PublicarDestinoJob($destino->id))->handle(
        app(PublicacaoService::class),
        app(RegistroDePublicadores::class),
    );
}

function conciliarNoYoutube(Destino $destino): void
{
    (new ConciliarDestinoJob($destino->id))->handle(
        app(PublicacaoService::class),
        app(RegistroDePublicadores::class),
    );
}

/** @param mixed $conferencia resposta do videos.list */
function youtube(mixed $conferencia = null): void
{
    Http::fake([
        '*googleapis.com/upload/youtube*' => Http::response([], 200, ['Location' => SESSAO]),
        // A documentação diz que o upload completo responde **201 Created**.
        SESSAO => Http::response(['id' => 'video-xyz'], 201),
        '*youtube/v3/videos*' => $conferencia ?? Http::response([
            'items' => [['status' => ['uploadStatus' => 'processed'], 'contentDetails' => ['definition' => 'hd']]],
        ]),
    ]);
}

describe('o envio em pedaços', function () {
    it('abre a sessão, envia e fica aguardando confirmação', function () {
        $destino = destinoNoYoutube();
        youtube();

        enviarNoYoutube($destino);

        // ⭐ DEC-31: aceitou ≠ está no ar.
        expect($destino->fresh())
            ->status->toBe(StatusDestino::Processando)
            ->identificador_externo->toBe('video-xyz')
            ->url_publicada->toBeNull();
    });

    it('⭐ guarda o endereço de retomada ANTES de enviar o arquivo', function () {
        $destino = destinoNoYoutube();

        Http::fake([
            '*googleapis.com/upload/youtube*' => Http::response([], 200, ['Location' => SESSAO]),
            SESSAO => Http::response([], 500),
        ]);

        enviarNoYoutube($destino);

        // Sem o endereço salvo, a próxima tentativa recomeçaria — e o mesmo
        // vídeo subiria duas vezes. Publicação não tem desfazer.
        expect($destino->fresh()->handle_externo)->toBe(SESSAO);
    });

    it('⭐ retoma de onde parou em vez de recomeçar', function () {
        $destino = destinoNoYoutube();
        ContextoDoUsuario::semEscopo(fn () => $destino->forceFill(['handle_externo' => SESSAO])->save());

        Http::fake([
            SESSAO => Http::sequence()
                // 1ª: "quanto já subiu?" → 512 dos 1024 bytes.
                ->push([], 308, ['Range' => 'bytes=0-511'])
                // 2ª: manda o resto e recebe o vídeo.
                ->push(['id' => 'video-retomado'], 201),
        ]);

        enviarNoYoutube($destino);

        expect($destino->fresh()->identificador_externo)->toBe('video-retomado');
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/upload/youtube'));
    });

    it('⭐ 404 é sessão vencida — recomeça', function () {
        $destino = destinoNoYoutube();
        ContextoDoUsuario::semEscopo(fn () => $destino->forceFill(['handle_externo' => SESSAO])->save());

        Http::fake([SESSAO => Http::response([], 404)]);

        enviarNoYoutube($destino);

        expect($destino->fresh())
            ->status->toBe(StatusDestino::Pendente)
            ->handle_externo->toBe('');
    });

    it('⭐ 5xx NÃO é sessão vencida — preserva o que já subiu', function () {
        $destino = destinoNoYoutube();
        ContextoDoUsuario::semEscopo(fn () => $destino->forceFill(['handle_externo' => SESSAO])->save());

        Http::fake([SESSAO => Http::response([], 503)]);

        enviarNoYoutube($destino);

        // Tratar 5xx como sessão morta jogaria fora o que já subiu — foi o
        // erro que eu tinha escrito de memória.
        expect($destino->fresh())
            ->status->toBe(StatusDestino::Pendente)
            ->handle_externo->toBe(SESSAO);
    });

    it('limpa o endereço quando o envio termina', function () {
        $destino = destinoNoYoutube();
        youtube();

        enviarNoYoutube($destino);

        expect($destino->fresh()->handle_externo)->toBe('');
    });
});

describe('o que mandamos para a API', function () {
    it('⛔ NÃO corta o texto da pessoa', function () {
        // 120 caracteres no título: quem recusa é o `EnvioDePublicacao`, antes.
        // O publicador manda inteiro — cortar violaria a política.
        $destino = destinoNoYoutube(['titulo' => str_repeat('a', 120)]);
        youtube();

        enviarNoYoutube($destino);

        Http::assertSent(function ($req) {
            return str_contains($req->url(), '/upload/youtube')
                && strlen($req->data()['snippet']['title']) === 120;
        });
    });

    it('⭐ desliga autoLevels e stabilize explicitamente', function () {
        $destino = destinoNoYoutube();
        youtube();

        enviarNoYoutube($destino);

        // O YouTube pode corrigir brilho e estabilizar. Isso contraria a
        // promessa central (DEC-33), e a spec não declara o padrão.
        Http::assertSent(fn ($req) => str_contains($req->url(), 'autoLevels=false')
            && str_contains($req->url(), 'stabilize=false'));
    });

    it('⭐ não notifica os inscritos por padrão', function () {
        $destino = destinoNoYoutube();
        youtube();

        enviarNoYoutube($destino);

        // Vem LIGADO na API. Publicar vários cortes seguidos notificaria a
        // cada um, e o cliente culparia a ferramenta.
        Http::assertSent(fn ($req) => str_contains($req->url(), 'notifySubscribers=false'));
    });

    it('notifica quando a pessoa pede', function () {
        $destino = destinoNoYoutube(opcoes: ['notificar_inscritos' => true]);
        youtube();

        enviarNoYoutube($destino);

        Http::assertSent(fn ($req) => str_contains($req->url(), 'notifySubscribers=true'));
    });

    it('declara conteúdo de IA e público infantil quando marcado', function () {
        $destino = destinoNoYoutube(opcoes: ['feito_com_ia' => true, 'para_criancas' => true]);
        youtube();

        enviarNoYoutube($destino);

        Http::assertSent(function ($req) {
            $status = $req->data()['status'] ?? [];

            return str_contains($req->url(), '/upload/youtube')
                && ($status['containsSyntheticMedia'] ?? null) === true
                && ($status['selfDeclaredMadeForKids'] ?? null) === true;
        });
    });

    it('⭐ agenda no próprio YouTube quando há data', function () {
        $quando = now()->addDay()->toIso8601ZuluString();
        $destino = destinoNoYoutube(opcoes: ['publicar_em' => $quando]);
        youtube();

        enviarNoYoutube($destino);

        // O YouTube publica sozinho na hora — mais confiável que agendador
        // nosso, que depende do servidor estar de pé.
        Http::assertSent(fn ($req) => str_contains($req->url(), '/upload/youtube')
            && ($req->data()['status']['publishAt'] ?? null) === $quando);
    });
});

describe('a conciliação — de onde vem a prova', function () {
    it('⭐ só marca publicado quando o YouTube diz que processou', function () {
        $destino = destinoNoYoutube();
        youtube();

        enviarNoYoutube($destino);
        conciliarNoYoutube($destino);

        expect($destino->fresh())
            ->status->toBe(StatusDestino::Publicado)
            ->url_publicada->toBe('https://www.youtube.com/watch?v=video-xyz');
    });

    it('espera enquanto está apenas "uploaded"', function () {
        $destino = destinoNoYoutube();
        youtube(Http::response(['items' => [['status' => ['uploadStatus' => 'uploaded']]]]));

        enviarNoYoutube($destino);
        conciliarNoYoutube($destino);

        expect($destino->fresh())
            ->status->toBe(StatusDestino::Processando)
            ->url_publicada->toBeNull();
    });

    it('traduz os 10 motivos de recusa', function (string $motivo, string $trecho) {
        $destino = destinoNoYoutube();
        youtube(Http::response([
            'items' => [['status' => ['uploadStatus' => 'rejected', 'rejectionReason' => $motivo]]],
        ]));

        enviarNoYoutube($destino);
        conciliarNoYoutube($destino);

        expect($destino->fresh())
            ->status->toBe(StatusDestino::Falhou)
            ->erro_mensagem->toContain($trecho);
    })->with([
        ['copyright', 'direito autoral'],
        ['duplicate', 'já existe'],
        ['inappropriate', 'impróprio'],
        ['length', 'duração'],
        ['claim', 'reivindicação'],
        ['trademark', 'marca registrada'],
        ['termsOfUse', 'termos de uso'],
        ['legal', 'jurídica'],
    ]);

    it('traduz os motivos de falha de processamento', function (string $motivo, string $trecho) {
        $destino = destinoNoYoutube();
        youtube(Http::response([
            'items' => [['status' => ['uploadStatus' => 'failed', 'failureReason' => $motivo]]],
        ]));

        enviarNoYoutube($destino);
        conciliarNoYoutube($destino);

        expect($destino->fresh()->erro_mensagem)->toContain($trecho);
    })->with([
        ['codec', 'codec'],
        ['invalidFile', 'corrompido'],
        ['emptyFile', 'vazio'],
        ['tooSmall', 'menor'],
        ['uploadAborted', 'interrompido'],
    ]);

    it('⭐ conta suspensa derruba a CONTA, não só o vídeo', function (string $motivo) {
        $destino = destinoNoYoutube();
        youtube(Http::response([
            'items' => [['status' => ['uploadStatus' => 'rejected', 'rejectionReason' => $motivo]]],
        ]));

        enviarNoYoutube($destino);
        conciliarNoYoutube($destino);

        // Não é sobre o vídeo: é sobre a conta. Insistir nos próximos envios
        // seria inútil, e a pessoa precisa saber para reconectar.
        $conta = ContextoDoUsuario::semEscopo(fn () => $destino->contaSocial()->first());

        expect($conta->status)->toBe(StatusConta::Erro)
            ->and($destino->fresh()->status)->toBe(StatusDestino::Falhou);
    })->with(['uploaderAccountClosed', 'uploaderAccountSuspended']);

    it('marca falha quando o vídeo sumiu do canal', function () {
        $destino = destinoNoYoutube();
        youtube(Http::response(['items' => []]));

        enviarNoYoutube($destino);
        conciliarNoYoutube($destino);

        expect($destino->fresh()->erro_mensagem)->toContain('não está mais no canal');
    });

    it('pede contentDetails — a prova de degradação vem dali', function () {
        $destino = destinoNoYoutube();
        youtube();

        enviarNoYoutube($destino);
        conciliarNoYoutube($destino);

        // `definition: hd|sd` é a rede admitindo se degradou o vídeo.
        Http::assertSent(fn ($req) => str_contains($req->url(), 'youtube/v3/videos')
            && str_contains($req->url(), 'contentDetails'));
    });
});

describe('erros do envio', function () {
    it('⭐ cota estourada é espera, não erro', function (string $motivo) {
        $destino = destinoNoYoutube();

        Http::fake(['*googleapis.com/upload/youtube*' => Http::response([
            'error' => ['errors' => [['reason' => $motivo]]],
        ], 403)]);

        enviarNoYoutube($destino);

        // São 100 envios/dia no PROJETO inteiro (DEC-24). Não é culpa do
        // conteúdo, e marcar "falhou" alarmaria à toa.
        expect($destino->fresh())
            ->status->toBe(StatusDestino::AguardandoJanela)
            ->erro_mensagem->toContain('cota diária');
    })->with(['quotaExceeded', 'uploadLimitExceeded']);

    it('exige título antes de gastar o upload', function () {
        $destino = destinoNoYoutube(['titulo' => null, 'legenda' => 'só a legenda']);
        Http::fake();

        enviarNoYoutube($destino);

        expect($destino->fresh()->erro_mensagem)->toContain('título');
        Http::assertNothingSent();
    });

    it('recusa imagem — o YouTube só recebe vídeo', function () {
        $dono = cliente();
        ContextoDoUsuario::definir($dono);
        $midia = Midia::factory()->doUsuario($dono)->imagem()->create();
        $publicacao = Publicacao::factory()->doUsuario($dono)->enviada()->create([
            'midia_id' => $midia->id, 'titulo' => 'Foto',
        ]);
        $conta = ContaSocial::factory()->doUsuario($dono)->daPlataforma(Plataforma::Youtube)
            ->comCredencial()->create();
        $destino = Destino::factory()->create([
            'publicacao_id' => $publicacao->id,
            'conta_social_id' => $conta->id,
            'status' => StatusDestino::Pendente,
        ]);
        ContextoDoUsuario::limpar();

        Http::fake();
        enviarNoYoutube($destino);

        expect($destino->fresh()->erro_mensagem)->toContain('só vídeo');
    });
});

describe('o token', function () {
    it('renova o vencido antes de publicar', function () {
        $destino = destinoNoYoutube();
        ContextoDoUsuario::semEscopo(fn () => $destino->contaSocial->credencial
            ->forceFill(['expira_em' => now()->subHour()])->save());

        Http::fake([
            '*oauth2.googleapis.com/token*' => Http::response(['access_token' => 'token-novinho', 'expires_in' => 3600]),
            '*googleapis.com/upload/youtube*' => Http::response([], 200, ['Location' => SESSAO]),
            SESSAO => Http::response(['id' => 'video-abc'], 201),
        ]);

        enviarNoYoutube($destino);

        expect($destino->fresh()->status)->toBe(StatusDestino::Processando);

        $credencial = ContextoDoUsuario::semEscopo(fn () => $destino->contaSocial->credencial()->first());
        expect($credencial->access_token)->toBe('token-novinho');
    });

    it('não apaga o refresh quando o Google não manda um novo', function () {
        $destino = destinoNoYoutube();
        ContextoDoUsuario::semEscopo(fn () => $destino->contaSocial->credencial
            ->forceFill(['expira_em' => now()->subHour(), 'refresh_token' => 'o-que-vale'])->save());

        Http::fake([
            // O Google só devolve refresh novo quando ele muda.
            '*oauth2.googleapis.com/token*' => Http::response(['access_token' => 'novo', 'expires_in' => 3600]),
            '*googleapis.com/upload/youtube*' => Http::response([], 200, ['Location' => SESSAO]),
            SESSAO => Http::response(['id' => 'v1'], 201),
        ]);

        enviarNoYoutube($destino);

        // Sobrescrever com null apagaria o que ainda vale, e a conexão morreria
        // em uma hora sem ninguém entender.
        $credencial = ContextoDoUsuario::semEscopo(fn () => $destino->contaSocial->credencial()->first());
        expect($credencial->refresh_token)->toBe('o-que-vale');
    });

    it('marca a conta como expirada quando o Google revoga', function () {
        $destino = destinoNoYoutube();
        ContextoDoUsuario::semEscopo(fn () => $destino->contaSocial->credencial
            ->forceFill(['expira_em' => now()->subHour()])->save());

        Http::fake(['*oauth2.googleapis.com/token*' => Http::response(['error' => 'invalid_grant'], 400)]);

        enviarNoYoutube($destino);

        $conta = ContextoDoUsuario::semEscopo(fn () => $destino->contaSocial()->first());
        expect($conta->status)->toBe(StatusConta::Expirada)
            ->and($destino->fresh()->status)->toBe(StatusDestino::Falhou);
    });
});

describe('o escopo que pedimos', function () {
    it('⭐ pede o mínimo — e NÃO pede poder de apagar', function () {
        $endereco = app(ConexaoComYoutube::class)->enderecoDeAutorizacao('abc');

        expect($endereco)->toContain('youtube.upload')
            ->toContain('youtube.readonly')
            // ⭐ DEC-41: `force-ssl` permitiria apagar vídeos do canal — é o
            // medo nº 1 documentado nas entrevistas.
            ->not->toContain('force-ssl')
            // Sem `offline` + `consent` o Google não devolve o token de
            // renovação, e a conexão morre em uma hora.
            ->toContain('access_type=offline')
            ->toContain('prompt=consent');
    });
});

/*
|--------------------------------------------------------------------------
| A CONEXAO (OAuth) — a ponta que so a revisao cobriu
|--------------------------------------------------------------------------
|
| Ate aqui os testes cuidavam do ENVIO. Mas nada disso roda se a conexao nao
| nascer certa — e o retorno do Google e um GET vindo de fora, com todas as
| armadilhas que isso traz.
*/

/** Respostas de um retorno do Google que deu certo. */
function googleOk(array $sobrescrever = []): void
{
    Http::fake(array_merge([
        'oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'token-novo',
            'refresh_token' => 'renovacao',
            'expires_in' => 3600,
            'scope' => 'https://www.googleapis.com/auth/youtube.upload '.
                'https://www.googleapis.com/auth/youtube.readonly',
        ]),
        'www.googleapis.com/youtube/v3/channels*' => Http::response([
            'items' => [[
                'id' => 'UC_canal_de_teste',
                'snippet' => ['title' => 'Canal do Gabriel', 'thumbnails' => ['default' => ['url' => 'https://i.ytimg.com/a.jpg']]],
            ]],
        ]),
    ], $sobrescrever));
}

it('conecta o canal de ponta a ponta e guarda a credencial cifrada', function () {
    $dono = cliente();
    googleOk();

    $inicio = $this->actingAs($dono)->get('/conexoes/youtube');
    $inicio->assertRedirect();

    $endereco = $inicio->headers->get('Location');
    expect($endereco)->toContain('accounts.google.com')
        ->toContain('access_type=offline')
        ->toContain('prompt=consent')
        // ⛔ DEC-41: o escopo que apagaria videos NUNCA pode aparecer aqui.
        ->not->toContain('force-ssl');

    parse_str(parse_url($endereco, PHP_URL_QUERY), $query);

    $this->actingAs($dono)
        ->get('/conexoes/youtube/retorno?code=codigo-do-google&state='.$query['state'])
        ->assertRedirect(route('conexoes'))
        ->assertSessionHas('sucesso');

    ContextoDoUsuario::definir($dono);
    $conta = ContaSocial::where('plataforma', Plataforma::Youtube)->firstOrFail();

    expect($conta->nome_exibicao)->toBe('Canal do Gabriel')
        ->and($conta->status)->toBe(StatusConta::Ativa)
        ->and($conta->credencial->refresh_token)->toBe('renovacao');

    // ⚠️ O segredo nao pode estar legivel no banco.
    $bruto = DB::table('credenciais')->where('conta_social_id', $conta->id)->first();
    expect($bruto->refresh_token)->not->toContain('renovacao');
});

it('recusa o retorno sem o `state` da sessao (CSRF)', function () {
    googleOk();

    $this->actingAs(cliente())
        ->get('/conexoes/youtube/retorno?code=x&state=forjado')
        ->assertRedirect(route('conexoes'))
        ->assertSessionHas('erro');

    ContextoDoUsuario::definir(cliente());
    expect(ContaSocial::count())->toBe(0);
});

it('recusa quando a pessoa desmarca a permissao de enviar videos', function () {
    // ⚠️ A tela do Google deixa desmarcar permissao. A doc e literal: o
    // aplicativo PRECISA conferir o que foi concedido.
    googleOk(['oauth2.googleapis.com/token' => Http::response([
        'access_token' => 't', 'refresh_token' => 'r', 'expires_in' => 3600,
        'scope' => 'https://www.googleapis.com/auth/youtube.readonly',
    ])]);

    $dono = cliente();
    $this->actingAs($dono)->get('/conexoes/youtube');
    $estado = session('youtube.estado');

    $resposta = $this->actingAs($dono)->get('/conexoes/youtube/retorno?code=x&state='.$estado);

    $resposta->assertSessionHas('erro', fn ($m) => str_contains($m, 'enviar vídeos'));

    ContextoDoUsuario::definir($dono);
    expect(ContaSocial::count())->toBe(0);
});

it('nao manda criar canal quando o erro foi de cota', function () {
    // Tratar 403 como "nao tem canal" mandava a pessoa criar um canal que ela ja
    // tem — por causa da API desligada no Google Cloud.
    googleOk(['www.googleapis.com/youtube/v3/channels*' => Http::response(['error' => ['code' => 403]], 403)]);

    $dono = cliente();
    $this->actingAs($dono)->get('/conexoes/youtube');

    $this->actingAs($dono)
        ->get('/conexoes/youtube/retorno?code=x&state='.session('youtube.estado'))
        ->assertSessionHas('erro', fn ($m) => str_contains($m, 'YouTube Data API v3'));
});

it('avisa que o modo de Testes derruba a conexao a cada 7 dias', function () {
    $dono = cliente();
    ContextoDoUsuario::definir($dono);
    $conta = contaDoYoutube($dono);
    $conta->credencial()->update(['expira_em' => now()->subHour()]);

    // ⚠️ So `invalid_grant` significa autorizacao encerrada.
    Http::fake(['oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400)]);

    expect(app(TokenDoGoogle::class)->valido($conta->fresh()))->toBeNull();

    $conta->refresh();
    expect($conta->status)->toBe(StatusConta::Expirada)
        ->and($conta->status_detalhe)->toContain('7 dias');
});

it('nao mata a conta por causa de um erro passageiro do Google', function () {
    $dono = cliente();
    ContextoDoUsuario::definir($dono);
    $conta = contaDoYoutube($dono);
    $conta->credencial()->update(['expira_em' => now()->subHour()]);

    // Um 500 e passageiro: marcar a conta como morta obrigaria a reconectar sem
    // necessidade, e o proximo job funcionaria normalmente.
    Http::fake(['oauth2.googleapis.com/token' => Http::response('', 500)]);

    expect(app(TokenDoGoogle::class)->valido($conta->fresh()))->toBeNull();
    expect($conta->fresh()->status)->toBe(StatusConta::Ativa);
});

it('agendar forca privado — a API recusa agendamento com video publico', function () {
    $destino = destinoNoYoutube(opcoes: [
        'publicar_em' => '2026-08-01T10:00:00Z',
        'visibilidade' => 'public',
    ]);
    youtube();
    enviarNoYoutube($destino);

    Http::assertSent(function ($req) {
        if (! str_contains($req->url(), '/upload/youtube')) {
            return false;
        }

        // A spec: `publishAt` so pode ser definido se a privacidade for privada.
        return $req->data()['status']['privacyStatus'] === 'private'
            && $req->data()['status']['publishAt'] === '2026-08-01T10:00:00Z';
    });
});

it('guarda a qualidade que a rede diz ter entregado', function () {
    $destino = destinoNoYoutube();

    // ⭐ Enviamos vertical 1080; `sd` é a plataforma admitindo que degradou.
    youtube(Http::response(['items' => [[
        'status' => ['uploadStatus' => 'processed', 'privacyStatus' => 'private'],
        'contentDetails' => ['definition' => 'sd'],
    ]]]));

    enviarNoYoutube($destino);
    conciliarNoYoutube($destino->fresh());

    expect($destino->fresh()->qualidade_entregue)->toBe('sd');
});

it('⚠️ o endereço de retorno bate exatamente com a rota real', function () {
    // O Google compara o endereço de retorno CARACTERE POR CARACTERE com o que
    // está cadastrado no console. Se `GOOGLE_REDIRECT_URI` e a rota real
    // divergirem — uma barra, uma porta, `localhost` virando `127.0.0.1` — o
    // login morre com `redirect_uri_mismatch`, que não explica nada.
    //
    // Divergir é fácil: basta mexer no `APP_URL` e esquecer do resto.
    expect(config('services.google.redirect'))->toBe(route('conexoes.youtube.retorno'));
})->skip(
    fn () => config('services.google.redirect') === null,
    'Sem endereço de retorno configurado.'
);

describe('os erros do envio, traduzidos', function () {
    /*
    | A referência oficial do `videos.insert` lista 14 motivos de erro — e
    | NENHUM estava tratado. A pessoa recebia mensagem genérica justamente nos
    | casos em que existe algo a fazer.
    */

    it('diz o que arrumar em vez de mostrar "400"', function () {
        $destino = destinoNoYoutube();

        Http::fake(['*googleapis.com/upload/youtube*' => Http::response([
            'error' => ['errors' => [['reason' => 'invalidTitle']], 'message' => 'Invalid title.'],
        ], 400)]);

        enviarNoYoutube($destino);

        expect($destino->fresh())
            ->status->toBe(StatusDestino::Falhou)
            ->erro_mensagem->toContain('título');
    });

    it('a categoria que sumiu tem mensagem própria', function () {
        $destino = destinoNoYoutube();

        Http::fake(['*googleapis.com/upload/youtube*' => Http::response([
            'error' => ['errors' => [['reason' => 'invalidCategoryId']]],
        ], 400)]);

        enviarNoYoutube($destino);

        expect($destino->fresh()->erro_mensagem)->toContain('categoria');
    });

    it('⚠️ arquivo que não chegou é retentativa, não recusa', function () {
        // `mediaBodyRequired` é falha de transporte, não do conteúdo: recusar de
        // vez descartaria um envio que a próxima tentativa faria.
        $destino = destinoNoYoutube();

        Http::fake(['*googleapis.com/upload/youtube*' => Http::response([
            'error' => ['errors' => [['reason' => 'mediaBodyRequired']]],
        ], 400)]);

        enviarNoYoutube($destino);

        expect($destino->fresh()->status)->toBe(StatusDestino::Pendente);
    });

    it('guarda o motivo, não o código HTTP', function () {
        // "400" não ajuda ninguém a investigar; `invalidTags` ajuda.
        $destino = destinoNoYoutube();

        Http::fake(['*googleapis.com/upload/youtube*' => Http::response([
            'error' => ['errors' => [['reason' => 'invalidTags']]],
        ], 400)]);

        enviarNoYoutube($destino);

        $tentativa = $destino->fresh()->tentativas()->latest('id')->first();
        expect($tentativa->codigo_resposta)->toBe('invalidTags');
    });
});

describe('quando o servidor não fala com a rede', function () {
    it('⚠️ falha de certificado NÃO manda "tente de novo"', function () {
        // Foi o que aconteceu no primeiro teste real: o PHP estava sem o pacote
        // de certificados, TODA chamada HTTPS falhava, e a tela mandava tentar
        // de novo — contra um problema que nunca passaria sozinho.
        $erro = new ConnectionException(
            'cURL error 60: SSL certificate problem: unable to get local issuer certificate'
        );

        $mensagem = FalhaDeConexao::explicar($erro, 'Google');

        expect($mensagem)->toContain('certificados')
            ->and($mensagem)->toContain('php.ini')
            ->and($mensagem)->not->toContain('Tente de novo');
    });

    it('oscilação de rede continua sendo "tente de novo"', function () {
        // Essa passa sozinha mesmo — insistir é o conselho certo.
        $erro = new ConnectionException('cURL error 28: Operation timed out');

        expect(FalhaDeConexao::explicar($erro, 'Google'))
            ->toContain('Tente de novo');
    });

    it('não culpa a conta da pessoa por defeito da instalação', function () {
        $erro = new ConnectionException('SSL certificate problem: self signed certificate');

        // Quem lê isso não tem o que fazer sozinho. Dizer "reconecte sua conta"
        // mandaria a pessoa mexer onde não há problema nenhum.
        expect(FalhaDeConexao::explicar($erro, 'Google'))
            ->toContain('não é problema da sua conta');
    });
});

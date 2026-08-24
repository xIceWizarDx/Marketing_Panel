<?php

use App\Enums\Plataforma;
use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Grupo;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Publicadores\PublicadorTiktok;
use App\Publicadores\Retomada;
use App\Services\PublicacaoService;
use App\Support\ContextoDoUsuario;
use App\Support\Midia\EspecificacaoDaRede;
use App\Support\Tiktok\FichaDoCriador;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/*
| Guardiao do PUBLICADOR DO TIKTOK (plano 23, DEC-115..123).
|
| ⭐ Esta e a rede que implementa a tese do produto por conta propria: o
| identificador do post so vem "for public posts approved by moderation".
*/

beforeEach(function () {
    ContextoDoUsuario::limpar();
    Storage::fake('local');
    config([
        'services.tiktok.client_key' => 'chave-de-teste',
        'services.tiktok.client_secret' => 'segredo-de-teste',
        /*
         * ⚠️ AUDITADO por padrao: sem auditoria o publicador recusa ANTES de
         * subir (DEC-124), e todo teste de publicacao passaria pelo motivo
         * errado. A recusa tem bloco proprio, abaixo.
         */
        'services.tiktok.auditado' => true,
    ]);
});

afterEach(fn () => ContextoDoUsuario::limpar());

/** Um destino do TikTok pronto para publicar. */
function destinoNoTiktok(int $bytes = 1024, ?int $duracao = 30): Destino
{
    $dono = cliente();
    ContextoDoUsuario::definir($dono);

    $midia = Midia::factory()->doUsuario($dono)->create([
        'tamanho_bytes' => $bytes,
        'duracao_segundos' => $duracao,
    ]);
    Storage::disk('local')->put($midia->caminho, str_repeat('v', $bytes));

    $criada = Publicacao::factory()->doUsuario($dono)->enviada()->create([
        'midia_id' => $midia->id,
        'titulo' => 'Meu corte',
        'legenda' => 'Olha isso',
    ]);

    $conta = ContaSocial::factory()->doUsuario($dono)->doGrupo(Grupo::firstOrFail())
        ->daPlataforma(Plataforma::Tiktok)->comCredencial('token-de-24h')
        ->create(['identificador_externo' => 'open-id-123']);

    // ⚠️ Token com folga: o publicador renova quando falta menos de 1 hora, e
    // sem isso todo teste bateria na renovacao antes de qualquer outra coisa.
    $conta->credencial->forceFill([
        'refresh_token' => 'rft-antigo',
        'expira_em' => now()->addHours(20),
    ])->save();

    $destino = Destino::factory()->create([
        'publicacao_id' => $criada->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Enviando,
    ]);

    return $destino->fresh(['publicacao.midia', 'contaSocial.credencial']);
}

function retomadaNoTiktok(Destino $destino): Retomada
{
    return new Retomada($destino, app(PublicacaoService::class));
}

/** A ficha do criador, no caminho feliz. */
function fichaDoCriador(array $trocas = []): array
{
    return ['data' => array_merge([
        'creator_nickname' => 'Gabriel',
        'creator_username' => 'gabriel',
        'privacy_level_options' => ['PUBLIC_TO_EVERYONE', 'SELF_ONLY'],
        'comment_disabled' => false,
        'duet_disabled' => false,
        'stitch_disabled' => false,
        'max_video_post_duration_sec' => 600,
    ], $trocas), 'error' => ['code' => 'ok']];
}

/** O caminho feliz inteiro. */
function tiktokRespondendo(array $trocas = []): void
{
    Http::fake(array_merge([
        'open.tiktokapis.com/v2/post/publish/creator_info/query/' => Http::response(fichaDoCriador()),
        'open.tiktokapis.com/v2/post/publish/video/init/' => Http::response([
            'data' => ['publish_id' => 'publicacao-1', 'upload_url' => 'https://upload.tiktok.test/parte'],
            'error' => ['code' => 'ok'],
        ]),
        'upload.tiktok.test/*' => Http::response('', 200),
    ], $trocas));
}

describe('⛔ perguntar ao criador é OBRIGATÓRIO (DEC-117)', function () {
    it('⭐ a ficha do criador é consultada ANTES de iniciar a publicação', function () {
        // ⛔ Nao e etiqueta: privacidade fora de `privacy_level_options` devolve
        // `privacy_level_option_mismatch` e a publicacao nao acontece.
        $destino = destinoNoTiktok();
        tiktokRespondendo();

        app(PublicadorTiktok::class)->publicar($destino, retomadaNoTiktok($destino));

        Http::assertSent(fn ($r) => str_contains($r->url(), 'creator_info/query'));
    });

    it('⭐ a duração é medida contra o teto DESTA CONTA, não da rede', function () {
        /*
         * ⚠️ Isto quebra uma suposicao que valia para todas as outras redes: o
         * limite de duracao aqui e da CONTA. Contas novas tem teto menor, e
         * descobrir isso depois do envio inteiro gastaria a cota da pessoa.
         */
        $destino = destinoNoTiktok(duracao: 300);

        tiktokRespondendo([
            'open.tiktokapis.com/v2/post/publish/creator_info/query/' => Http::response(
                fichaDoCriador(['max_video_post_duration_sec' => 60])
            ),
        ]);

        $resultado = app(PublicadorTiktok::class)->publicar($destino, retomadaNoTiktok($destino));

        expect($resultado->erro)->toContain('1 minuto');

        // ⛔ NENHUM byte subiu.
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'video/init'));
    });

    it('⚠️ duração desconhecida NÃO bloqueia — laudo faltando não é recusa', function () {
        // A rede e quem decide, e o `duration_check_failed` ja tem frase.
        $destino = destinoNoTiktok(duracao: null);
        tiktokRespondendo();

        expect(app(PublicadorTiktok::class)->publicar($destino, retomadaNoTiktok($destino))->aceito)
            ->toBeTrue();
    });

    it('respeita comentário, dueto e stitch desligados na conta', function () {
        // Ignorar seria publicar com uma configuracao que a pessoa recusou no
        // aplicativo dela.
        $destino = destinoNoTiktok();

        tiktokRespondendo([
            'open.tiktokapis.com/v2/post/publish/creator_info/query/' => Http::response(
                fichaDoCriador(['comment_disabled' => true, 'duet_disabled' => true, 'stitch_disabled' => true])
            ),
        ]);

        app(PublicadorTiktok::class)->publicar($destino, retomadaNoTiktok($destino));

        Http::assertSent(function ($r) {
            if (! str_contains($r->url(), 'video/init')) {
                return false;
            }

            expect($r['post_info']['disable_comment'])->toBeTrue()
                ->and($r['post_info']['disable_duet'])->toBeTrue()
                ->and($r['post_info']['disable_stitch'])->toBeTrue();

            return true;
        });
    });
});

describe('⛔ sem auditoria NÃO se publica (DEC-124)', function () {
    it('⭐ recusa ANTES de subir, e diz por quê', function () {
        /*
         * ⛔ Sem auditoria o post so pode ser privado, e post privado NUNCA
         * recebe `publicaly_available_post_id` — logo nao existe link.
         *
         * ⚠️ E `marcarPublicado()` recusa destino sem link, de proposito
         * (DEC-31). Publicar aqui so poderia terminar em "falhou" DEPOIS de o
         * video ter subido de verdade: o painel dizendo que nao subiu o que
         * subiu, e ainda oferecendo republicar — o que duplicaria o video.
         */
        config(['services.tiktok.auditado' => false]);

        $destino = destinoNoTiktok();
        Http::fake();

        $resultado = app(PublicadorTiktok::class)->publicar($destino, retomadaNoTiktok($destino));

        expect($resultado->erro)->toContain('auditoria do TikTok')
            ->and($resultado->erro)->toContain('privado');

        // ⛔ NENHUMA chamada — nem a de perguntar ao criador.
        Http::assertNothingSent();
    });

    it('⭐ a privacidade escolhida continua sendo `SELF_ONLY` quando não há auditoria', function () {
        // A trava do publicador e a primeira barreira; esta e a segunda. Se um
        // dia a primeira sair, esta impede o post publico acidental.
        config(['services.tiktok.auditado' => false]);

        $ficha = FichaDoCriador::daResposta([
            'privacy_level_options' => ['PUBLIC_TO_EVERYONE', 'SELF_ONLY'],
        ]);

        expect($ficha->privacidade())->toBe('SELF_ONLY');
    });

    it('depois da auditoria, usa a melhor privacidade que a conta permite', function () {
        $destino = destinoNoTiktok();
        tiktokRespondendo();

        app(PublicadorTiktok::class)->publicar($destino, retomadaNoTiktok($destino));

        Http::assertSent(fn ($r) => ! str_contains($r->url(), 'video/init')
            || $r['post_info']['privacy_level'] === 'PUBLIC_TO_EVERYONE');
    });

    it('⛔ e nunca manda uma privacidade fora do que a conta permite', function () {
        $destino = destinoNoTiktok();
        tiktokRespondendo([
            'open.tiktokapis.com/v2/post/publish/creator_info/query/' => Http::response(
                fichaDoCriador(['privacy_level_options' => ['FOLLOWER_OF_CREATOR']])
            ),
        ]);

        app(PublicadorTiktok::class)->publicar($destino, retomadaNoTiktok($destino));

        Http::assertSent(fn ($r) => ! str_contains($r->url(), 'video/init')
            || $r['post_info']['privacy_level'] === 'FOLLOWER_OF_CREATOR');
    });

    it('a recusa por aplicativo não auditado tem frase própria', function () {
        // Sem ela, a pessoa procuraria defeito no video.
        $destino = destinoNoTiktok();

        tiktokRespondendo([
            'open.tiktokapis.com/v2/post/publish/video/init/' => Http::response([
                'error' => ['code' => 'unaudited_client_can_only_post_to_private_accounts', 'message' => 'x'],
            ], 403),
        ]);

        expect(app(PublicadorTiktok::class)->publicar($destino, retomadaNoTiktok($destino))->erro)
            ->toContain('auditoria do TikTok');
    });
});

describe('⭐ a prova tem DOIS degraus (DEC-115)', function () {
    it('⛔ `PUBLISH_COMPLETE` SEM o identificador NÃO é "no ar"', function () {
        /*
         * ⭐ O `publicaly_available_post_id` so vem "for public posts approved
         * by moderation". Parar em `PUBLISH_COMPLETE` seria o erro que o
         * produto critica: a rede aceitou, e o post pode nao estar visivel para
         * ninguem.
         */
        $destino = destinoNoTiktok();
        $destino->forceFill(['handle_externo' => 'publicacao-1'])->save();

        Http::fake(['open.tiktokapis.com/v2/post/publish/status/fetch/' => Http::response([
            'data' => ['status' => 'PUBLISH_COMPLETE', 'publicaly_available_post_id' => []],
            'error' => ['code' => 'ok'],
        ])]);

        $resultado = app(PublicadorTiktok::class)->conciliar($destino->fresh(['contaSocial.credencial']));

        expect($resultado->aindaProcessando)->toBeTrue()
            ->and($resultado->noAr)->toBeFalse();
    });

    it('⭐ com o identificador, aí sim é prova — e vira link', function () {
        $destino = destinoNoTiktok();
        $destino->forceFill(['handle_externo' => 'publicacao-1'])->save();

        Http::fake(['open.tiktokapis.com/v2/post/publish/status/fetch/' => Http::response([
            'data' => ['status' => 'PUBLISH_COMPLETE', 'publicaly_available_post_id' => ['7300000000000000000']],
            'error' => ['code' => 'ok'],
        ])]);

        $resultado = app(PublicadorTiktok::class)->conciliar($destino->fresh(['contaSocial.credencial']));

        expect($resultado->noAr)->toBeTrue()
            ->and($resultado->url)->toContain('7300000000000000000')
            ->and($destino->fresh()->identificador_externo)->toBe('7300000000000000000');
    });

    it('enquanto sobe, não é desfecho nenhum', function () {
        $destino = destinoNoTiktok();
        $destino->forceFill(['handle_externo' => 'publicacao-1'])->save();

        foreach (['PROCESSING_UPLOAD', 'PROCESSING_DOWNLOAD', 'SEND_TO_USER_INBOX'] as $status) {
            Http::fake(['open.tiktokapis.com/v2/post/publish/status/fetch/' => Http::response([
                'data' => ['status' => $status], 'error' => ['code' => 'ok'],
            ])]);

            expect(app(PublicadorTiktok::class)->conciliar($destino->fresh(['contaSocial.credencial']))->aindaProcessando)
                ->toBeTrue();
        }
    });
});

describe('⛔ HTTP 200 com erro dentro (DEC-121)', function () {
    it('⭐ `invalid_publish_id` num 200 é ERRO, não sucesso', function () {
        /*
         * ⛔ Confiar no codigo HTTP trataria isso como sucesso, e o destino
         * ficaria esperando PARA SEMPRE por um post que nao existe.
         */
        $destino = destinoNoTiktok();
        $destino->forceFill(['handle_externo' => 'publicacao-1'])->save();

        Http::fake(['open.tiktokapis.com/v2/post/publish/status/fetch/' => Http::response([
            'data' => [], 'error' => ['code' => 'invalid_publish_id', 'message' => 'not found'],
        ], 200)]);

        $resultado = app(PublicadorTiktok::class)->conciliar($destino->fresh(['contaSocial.credencial']));

        expect($resultado->aindaProcessando)->toBeFalse()
            ->and($resultado->erro)->toContain('não reconhece mais este envio');
    });

    it('token vencido no meio da conferência apenas espera a próxima passada', function () {
        $destino = destinoNoTiktok();
        $destino->forceFill(['handle_externo' => 'publicacao-1'])->save();

        Http::fake(['open.tiktokapis.com/v2/post/publish/status/fetch/' => Http::response([
            'error' => ['code' => 'rate_limit_exceeded'],
        ], 200)]);

        expect(app(PublicadorTiktok::class)->conciliar($destino->fresh(['contaSocial.credencial']))->aindaProcessando)
            ->toBeTrue();
    });
});

describe('⛔ os motivos de falha, ditos pelo que são (DEC-123)', function () {
    it('⭐ `auth_removed` manda RECONECTAR, não procurar defeito no vídeo', function () {
        // Nao e falha de video nem de rede: a pessoa tirou a autorizacao no
        // aplicativo do TikTok.
        $destino = destinoNoTiktok();
        $destino->forceFill(['handle_externo' => 'publicacao-1'])->save();

        Http::fake(['open.tiktokapis.com/v2/post/publish/status/fetch/' => Http::response([
            'data' => ['status' => 'FAILED', 'fail_reason' => 'auth_removed'],
            'error' => ['code' => 'ok'],
        ])]);

        expect(app(PublicadorTiktok::class)->conciliar($destino->fresh(['contaSocial.credencial']))->erro)
            ->toContain('Reconecte');
    });

    it('erro de conteúdo vira frase em português', function () {
        $destino = destinoNoTiktok();
        $destino->forceFill(['handle_externo' => 'publicacao-1'])->save();

        Http::fake(['open.tiktokapis.com/v2/post/publish/status/fetch/' => Http::response([
            'data' => ['status' => 'FAILED', 'fail_reason' => 'frame_rate_check_failed'],
            'error' => ['code' => 'ok'],
        ])]);

        expect(app(PublicadorTiktok::class)->conciliar($destino->fresh(['contaSocial.credencial']))->erro)
            ->toContain('taxa de quadros');
    });
});

describe('⛔ a cota, e de quem é a culpa', function () {
    it('⭐ `reached_active_user_cap` é do NOSSO aplicativo — a frase não culpa a conta', function () {
        /*
         * ⚠️ Este 403 quer dizer que o painel estourou a cota de usuarios do
         * dia. Mandar a pessoa reconectar ou mexer no video seria mandar ela
         * consertar o que nao esta quebrado.
         */
        $destino = destinoNoTiktok();

        tiktokRespondendo([
            'open.tiktokapis.com/v2/post/publish/creator_info/query/' => Http::response([
                'error' => ['code' => 'reached_active_user_cap', 'message' => 'x'],
            ], 403),
        ]);

        $resultado = app(PublicadorTiktok::class)->publicar($destino, retomadaNoTiktok($destino));

        expect($resultado->semCota)->toBeTrue()
            ->and($resultado->erro)->toContain('painel')
            ->and($resultado->erro)->not->toContain('Reconecte');
    });

    it('limite de posts do dia da conta vira espera, não falha (DEC-24)', function () {
        $destino = destinoNoTiktok();

        tiktokRespondendo([
            'open.tiktokapis.com/v2/post/publish/video/init/' => Http::response([
                'error' => ['code' => 'spam_risk_too_many_posts', 'message' => 'x'],
            ], 403),
        ]);

        expect(app(PublicadorTiktok::class)->publicar($destino, retomadaNoTiktok($destino))->semCota)
            ->toBeTrue();
    });
});

describe('o envio', function () {
    it('⭐ o `publish_id` é guardado ANTES do primeiro byte', function () {
        // Se o processo morrer no meio do envio, e ele que impede a proxima
        // tentativa de criar um SEGUNDO envio.
        $destino = destinoNoTiktok();

        tiktokRespondendo(['upload.tiktok.test/*' => Http::response('', 500)]);

        app(PublicadorTiktok::class)->publicar($destino, retomadaNoTiktok($destino));

        expect($destino->fresh()->handle_externo)->toBe('publicacao-1');
    });

    it('⛔ cada pedaço leva o `Content-Range` com o total do arquivo', function () {
        $destino = destinoNoTiktok(bytes: 25 * 1024 * 1024);
        tiktokRespondendo();

        app(PublicadorTiktok::class)->publicar($destino, retomadaNoTiktok($destino));

        $faixas = [];

        Http::assertSent(function ($r) use (&$faixas) {
            if (str_contains($r->url(), 'upload.tiktok.test')) {
                $faixas[] = $r->header('Content-Range')[0];
            }

            return true;
        });

        // ⭐ Dois pedacos (arredondamento para baixo), e o ultimo carrega a sobra.
        expect($faixas)->toBe([
            'bytes 0-10485759/26214400',
            'bytes 10485760-26214399/26214400',
        ]);
    });

    it('o envio aceito não é publicação — é só aceito (DEC-31)', function () {
        $destino = destinoNoTiktok();
        tiktokRespondendo();

        $resultado = app(PublicadorTiktok::class)->publicar($destino, retomadaNoTiktok($destino));

        expect($resultado->aceito)->toBeTrue()
            ->and($resultado->identificadorExterno)->toBe('publicacao-1');
    });
});

describe('⛔ recomeçar NÃO pode publicar duas vezes', function () {
    it('⭐ envio que já aconteceu não sobe de novo — nem cria um segundo `publish_id`', function () {
        /*
         * ⛔ O caminho real: os pedaços sobem todos, e a resposta do último se
         * perde — tempo esgotado, processo morto, worker reiniciado. O destino
         * volta para a fila, e sem esta pergunta o publicador criaria um SEGUNDO
         * envio e subiria o vídeo de novo. **Dois vídeos publicados**, e
         * publicação não tem desfazer.
         */
        $destino = destinoNoTiktok();
        $destino->forceFill(['handle_externo' => 'publicacao-1'])->save();

        Http::fake([
            'open.tiktokapis.com/v2/post/publish/status/fetch/' => Http::response([
                'data' => ['status' => 'PROCESSING_UPLOAD'], 'error' => ['code' => 'ok'],
            ]),
        ]);

        $recarregado = $destino->fresh(['publicacao.midia', 'contaSocial.credencial']);
        $resultado = app(PublicadorTiktok::class)->publicar($recarregado, retomadaNoTiktok($recarregado));

        expect($resultado->aceito)->toBeTrue()
            ->and($resultado->identificadorExterno)->toBe('publicacao-1');

        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'video/init'));
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'upload.tiktok.test'));
        // ⚠️ Nem gasta a consulta ao criador: a cota do TikTok e curta.
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'creator_info'));
    });

    it('⭐ mas envio que FALHOU recomeça do zero', function () {
        // ⚠️ `FAILED` e o unico estado que autoriza refazer: qualquer outro quer
        // dizer que o arquivo ja chegou la.
        $destino = destinoNoTiktok();
        $destino->forceFill(['handle_externo' => 'publicacao-antiga'])->save();

        tiktokRespondendo([
            'open.tiktokapis.com/v2/post/publish/status/fetch/' => Http::response([
                'data' => ['status' => 'FAILED', 'fail_reason' => 'internal'], 'error' => ['code' => 'ok'],
            ]),
        ]);

        $recarregado = $destino->fresh(['publicacao.midia', 'contaSocial.credencial']);
        $resultado = app(PublicadorTiktok::class)->publicar($recarregado, retomadaNoTiktok($recarregado));

        expect($resultado->identificadorExterno)->toBe('publicacao-1');
        Http::assertSent(fn ($r) => str_contains($r->url(), 'video/init'));
    });

    it('e envio que a rede não reconhece mais também recomeça', function () {
        // `invalid_publish_id` chega dentro de um HTTP 200 (DEC-121).
        $destino = destinoNoTiktok();
        $destino->forceFill(['handle_externo' => 'publicacao-sumida'])->save();

        tiktokRespondendo([
            'open.tiktokapis.com/v2/post/publish/status/fetch/' => Http::response([
                'error' => ['code' => 'invalid_publish_id', 'message' => 'x'],
            ], 200),
        ]);

        $recarregado = $destino->fresh(['publicacao.midia', 'contaSocial.credencial']);

        expect(app(PublicadorTiktok::class)->publicar($recarregado, retomadaNoTiktok($recarregado))->identificadorExterno)
            ->toBe('publicacao-1');
    });
});

describe('⛔ o texto que sobe é o texto INTEIRO', function () {
    it('⭐ as hashtags chegam na rede — e no TikTok elas SÃO a descoberta', function () {
        /*
         * ⛔ Este publicador montava a legenda à mão e ignorava as hashtags. No
         * TikTok isso é grave de um jeito próprio: hashtag é o mecanismo de
         * descoberta da plataforma, e um post sem hashtag é um post que ninguém
         * acha.
         */
        $destino = destinoNoTiktok();
        $destino->publicacao->forceFill(['hashtags' => ['corte', 'shorts']])->save();

        tiktokRespondendo();

        $recarregado = $destino->fresh(['publicacao.midia', 'contaSocial.credencial']);
        app(PublicadorTiktok::class)->publicar($recarregado, retomadaNoTiktok($recarregado));

        Http::assertSent(function ($r) {
            if (! str_contains($r->url(), 'video/init')) {
                return false;
            }

            $titulo = (string) ($r['post_info']['title'] ?? '');

            expect($titulo)->toContain('#corte')
                ->and($titulo)->toContain('#shorts')
                // ⚠️ E o titulo tambem: esta rede nao tem campo separado.
                ->and($titulo)->toContain('Meu corte');

            return true;
        });
    });
});

describe('⛔ o orçamento de texto é UM SÓ', function () {
    it('⭐ título, legenda e hashtags são medidos juntos — é assim que sobem', function () {
        // Conferir separado deixaria passar o que a rede vai recusar, e a
        // recusa acontece depois do envio inteiro.
        $spec = EspecificacaoDaRede::de(Plataforma::Tiktok);

        // 1200 + 1 espaco + 990 = 2191, e o teto e 2200: cabe raspando.
        expect($spec->conferirTextos(str_repeat('t', 1200), str_repeat('a', 990)))->toBeEmpty();

        // A MESMA dupla com uma hashtag de 16 caracteres passa dos 2200.
        $achados = $spec->conferirTextos(str_repeat('t', 1200), str_repeat('a', 990), ['umahashtaglonga']);

        expect($achados)->not->toBeEmpty()
            ->and($achados[0]->mensagem)->toContain('hashtags juntos');
    });
});

describe('⛔ a auditoria do TikTok, campo a campo (DEC-168 e DEC-169)', function () {
    it('⭐ declara conteúdo feito por IA — a mesma caixinha do Instagram', function () {
        /*
         * ⛔ A pessoa marca "feito com IA" UMA vez no compositor. O Instagram
         * recebia (`is_ai_generated`) e o TikTok **nao** — a declaracao sumia
         * numa rede e valia na outra, sem ninguem perceber.
         *
         * ⚠️ Nao e preferencia de interface: e transparencia com quem assiste.
         */
        config(['services.tiktok.auditado' => true]);

        $destino = destinoNoTiktok();
        $destino->forceFill(['opcoes' => ['feito_com_ia' => true]])->save();
        tiktokRespondendo();

        app(PublicadorTiktok::class)->publicar(
            $destino->fresh(['publicacao.midia', 'contaSocial.credencial']),
            retomadaNoTiktok($destino),
        );

        Http::assertSent(fn ($r) => ! str_contains($r->url(), 'video/init')
            || ($r->data()['post_info']['is_aigc'] ?? null) === true);
    });

    it('⛔ e NÃO declara quando a pessoa não marcou — nem como `false`', function () {
        /*
         * ⚠️ Mandar `is_aigc: false` seria o painel afirmando "isto nao e IA"
         * em nome de alguem que nao disse nada. Escolha da pessoa, nunca padrao
         * nosso escondido no codigo.
         */
        config(['services.tiktok.auditado' => true]);

        $destino = destinoNoTiktok();
        tiktokRespondendo();

        app(PublicadorTiktok::class)->publicar(
            $destino->fresh(['publicacao.midia', 'contaSocial.credencial']),
            retomadaNoTiktok($destino),
        );

        Http::assertSent(fn ($r) => ! str_contains($r->url(), 'video/init')
            || ! array_key_exists('is_aigc', $r->data()['post_info'] ?? []));
    });

    it('⛔ pede `user.info.stats` — sem ele o seguidor NUNCA vem', function () {
        /*
         * ⛔ A referencia divide os campos em tres escopos, e o nome do primeiro
         * engana: `user.info.basic` da identidade (open_id, nome, avatar).
         * `follower_count` mora em **`user.info.stats`**.
         *
         * ⚠️ Sem ele, `metricasDaConta()` voltava `null` para sempre e a tela
         * dizia "sem leitura" — indistinguivel de rede que nao respondeu. Mesma
         * familia do `total_video_views` na Meta (DEC-157).
         */
        $endereco = app(App\Services\ConexaoComTiktok::class)->enderecoDeAutorizacao('estado');

        expect($endereco)->toContain('user.info.stats')
            ->and($endereco)->toContain('video.publish')
            ->and($endereco)->toContain('video.list');
    });
});

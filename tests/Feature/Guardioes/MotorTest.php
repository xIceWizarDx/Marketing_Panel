<?php

use App\Enums\StatusConta;
use App\Enums\StatusDestino;
use App\Enums\StatusPublicacao;
use App\Jobs\ConciliarDestinoJob;
use App\Jobs\PublicarDestinoJob;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Publicacao;
use App\Publicadores\RegistroDePublicadores;
use App\Services\PublicacaoService;
use App\Support\ContextoDoUsuario;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/*
| Guardiao do MOTOR.
|
| ⚠️ Estes testes rodam o job DE VERDADE — sem `Queue::fake()`. Foi justamente
| o `Queue::fake()` que escondeu, em projeto anterior, o bug do worker sem
| sessao: o job nunca executava, entao o escopo nunca era exercitado.
*/

beforeEach(function () {
    ContextoDoUsuario::limpar();
    Storage::fake('local');

    // Em producao a conciliacao e AGENDADA com atraso. Com a fila `sync` do
    // teste, ela rodaria em cadeia dentro do envio — comportamento que nao
    // existe de verdade. Quem quer conciliar, chama o job explicitamente.
    Queue::fake();
});

afterEach(fn () => ContextoDoUsuario::limpar());

function destinoProntoParaEnviar(): Destino
{
    $dono = cliente();
    ContextoDoUsuario::definir($dono);

    $publicacao = Publicacao::factory()->doUsuario($dono)->enviada()->create([
        'legenda' => 'Olha esse corte',
        'hashtags' => ['corte'],
    ]);

    Storage::disk('local')->put($publicacao->midia->caminho, 'conteudo-do-video');

    $conta = ContaSocial::factory()->doUsuario($dono)->comCredencial('senha-de-app')->create([
        'nome_exibicao' => 'ana.bsky.social',
    ]);

    $destino = Destino::factory()->create([
        'publicacao_id' => $publicacao->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Pendente,
    ]);

    ContextoDoUsuario::limpar();

    return $destino;
}

/**
 * Roda o job de verdade, sem passar pela fila.
 *
 * `dispatchSync` seria interceptado pelo `Queue::fake()`; chamar `handle()`
 * garante que o codigo do job realmente executa — que e o ponto destes testes.
 */
function enviar(Destino $destino): void
{
    (new PublicarDestinoJob($destino->id))->handle(
        app(PublicacaoService::class),
        app(RegistroDePublicadores::class),
    );
}

function conciliar(Destino $destino, int $consulta = 1): void
{
    (new ConciliarDestinoJob($destino->id, $consulta))->handle(
        app(PublicacaoService::class),
        app(RegistroDePublicadores::class),
    );
}

/**
 * Respostas do AT Protocol.
 *
 * ⚠️ `Http::fake()` chamado duas vezes ACUMULA, e o primeiro padrao a casar
 * vence. Por isso a resposta da conferencia entra aqui, de uma vez — refazer o
 * fake depois nao substituiria nada.
 */
function bluesky(mixed $conferencia = null, string $uri = 'at://did:plc:abc/app.bsky.feed.post/xyz789'): void
{
    Http::fake([
        '*com.atproto.server.createSession*' => Http::response(['accessJwt' => 'jwt', 'did' => 'did:plc:abc']),
        '*com.atproto.repo.uploadBlob*' => Http::response(['blob' => ['$type' => 'blob', 'ref' => ['$link' => 'b1']]]),
        '*com.atproto.repo.createRecord*' => Http::response(['uri' => $uri, 'cid' => 'cid1']),
        '*com.atproto.repo.getRecord*' => $conferencia ?? Http::response(['value' => ['text' => 'Olha esse corte']]),
    ]);
}

it('⭐ o job roda sem sessão e enxerga o dono certo', function () {
    $destino = destinoProntoParaEnviar();
    bluesky();

    // Sem `actingAs`: é exatamente a condição do worker. Se o escopo dependesse
    // de sessão, isto estouraria — e é esse o bug que o teste existe pra pegar.
    enviar($destino);

    expect($destino->fresh()->status)->toBe(StatusDestino::Processando);
});

it('⭐ NÃO marca publicado só porque a rede aceitou', function () {
    $destino = destinoProntoParaEnviar();
    bluesky();

    enviar($destino);

    // HTTP 200 no envio ≠ publicado. É a mentira que o produto não conta.
    expect($destino->fresh())
        ->status->toBe(StatusDestino::Processando)
        ->url_publicada->toBeNull()
        ->identificador_externo->not->toBeNull();

    // E deixa agendada a conferência.
    Queue::assertPushed(ConciliarDestinoJob::class);
});

it('⭐ só vira publicado depois de reler o post na rede', function () {
    $destino = destinoProntoParaEnviar();
    bluesky();

    enviar($destino);
    conciliar($destino);

    expect($destino->fresh())
        ->status->toBe(StatusDestino::Publicado)
        // ⭐ A PROVA.
        ->url_publicada->toBe('https://bsky.app/profile/ana.bsky.social/post/xyz789')
        ->publicado_em->not->toBeNull();

    expect($destino->publicacao->fresh()->status)->toBe(StatusPublicacao::Concluida);
});

it('⭐ marca falha quando o post não está mais lá', function () {
    $destino = destinoProntoParaEnviar();

    // A moderação apaga depois de aceitar — o caso que os concorrentes chamam
    // de "publicado" e o cliente só descobre semanas depois.
    bluesky(conferencia: Http::response(['error' => 'RecordNotFound'], 400));

    enviar($destino);
    conciliar($destino);

    expect($destino->fresh())
        ->status->toBe(StatusDestino::Falhou)
        ->url_publicada->toBeNull()
        ->erro_mensagem->toContain('não está mais no Bluesky');
});

it('espera e pergunta de novo enquanto a rede processa', function () {
    $destino = destinoProntoParaEnviar();
    bluesky(conferencia: Http::response([], 503));

    enviar($destino);
    conciliar($destino);

    expect($destino->fresh()->status)->toBe(StatusDestino::Processando);
    Queue::assertPushed(ConciliarDestinoJob::class);
});

it('⭐ ignora o job re-entregue — não publica duas vezes', function () {
    $destino = destinoProntoParaEnviar();
    bluesky();

    enviar($destino);
    $depoisDoPrimeiro = $destino->fresh()->identificador_externo;

    // A fila entregou o mesmo job de novo. Publicação não tem desfazer.
    enviar($destino);

    expect($destino->fresh())
        ->status->toBe(StatusDestino::Processando)
        ->identificador_externo->toBe($depoisDoPrimeiro)
        ->and($destino->tentativas()->count())->toBe(1);
});

it('devolve para a fila quando o Bluesky pede para esperar', function () {
    $destino = destinoProntoParaEnviar();

    Http::fake([
        '*com.atproto.server.createSession*' => Http::response(['accessJwt' => 'jwt', 'did' => 'did:plc:abc']),
        '*com.atproto.repo.uploadBlob*' => Http::response(['blob' => ['ref' => ['$link' => 'b1']]]),
        '*com.atproto.repo.createRecord*' => Http::response(['message' => 'Rate limit'], 429),
    ]);

    enviar($destino);

    // 429 é passageiro: volta pra fila, sem marcar falha e sem alarmar ninguém.
    expect($destino->fresh())
        ->status->toBe(StatusDestino::Pendente)
        ->tentativas_feitas->toBe(1)
        ->erro_mensagem->toBeNull();
});

it('recusa de vez quando a senha de aplicativo não vale mais', function () {
    $destino = destinoProntoParaEnviar();

    Http::fake(['*com.atproto.server.createSession*' => Http::response(['error' => 'Unauthorized'], 401)]);

    enviar($destino);

    expect($destino->fresh())
        ->status->toBe(StatusDestino::Falhou)
        ->erro_mensagem->toContain('senha de aplicativo');
});

it('não envia por conta desconectada', function () {
    $destino = destinoProntoParaEnviar();
    ContextoDoUsuario::semEscopo(fn () => $destino->contaSocial->forceFill([
        'status' => StatusConta::Expirada,
    ])->save());

    Http::fake();
    enviar($destino);

    expect($destino->fresh())
        ->status->toBe(StatusDestino::Falhou)
        ->erro_mensagem->toContain('não está conectada');

    Http::assertNothingSent();
});

it('recusa legenda maior que o limite do Bluesky antes de gastar upload', function () {
    $destino = destinoProntoParaEnviar();

    ContextoDoUsuario::semEscopo(fn () => $destino->publicacao->forceFill([
        'legenda' => str_repeat('a', 350),
        'hashtags' => [],
    ])->save());

    Http::fake();
    enviar($destino);

    expect($destino->fresh())
        ->status->toBe(StatusDestino::Falhou)
        ->erro_mensagem->toContain('300');
});

it('⭐ o watchdog resgata destino travado no envio', function () {
    $destino = destinoProntoParaEnviar();

    ContextoDoUsuario::semEscopo(fn () => ContextoDoUsuario::definir($destino->publicacao->usuario_id));
    app(PublicacaoService::class)->marcarEnviando($destino);
    ContextoDoUsuario::limpar();

    // Worker morreu no meio: sem watchdog a tela mostra "Enviando…" pra sempre.
    $destino->fresh()->forceFill(['updated_at' => now()->subHour()])->saveQuietly();

    $this->artisan('motor:resgatar-orfaos')->assertSuccessful();

    expect($destino->fresh()->status)->toBe(StatusDestino::Pendente);
});

it('o watchdog não mexe em quem acabou de começar', function () {
    $destino = destinoProntoParaEnviar();

    ContextoDoUsuario::semEscopo(fn () => ContextoDoUsuario::definir($destino->publicacao->usuario_id));
    app(PublicacaoService::class)->marcarEnviando($destino);
    ContextoDoUsuario::limpar();

    $this->artisan('motor:resgatar-orfaos')->assertSuccessful();

    expect($destino->fresh()->status)->toBe(StatusDestino::Enviando);
});

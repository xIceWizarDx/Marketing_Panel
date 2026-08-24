<?php

use App\Enums\StatusDestino;
use App\Jobs\PublicarDestinoJob;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Models\Usuario;
use App\Support\ContextoDoUsuario;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    ContextoDoUsuario::limpar();
    Queue::fake();
});

afterEach(fn () => ContextoDoUsuario::limpar());

/** @return array{0: Usuario, 1: Midia, 2: ContaSocial} */
function clienteComTudoPronto(): array
{
    $ana = cliente();
    ContextoDoUsuario::definir($ana);
    $midia = Midia::factory()->doUsuario($ana)->create();
    $conta = ContaSocial::factory()->doUsuario($ana)->comCredencial()->create();
    ContextoDoUsuario::limpar();

    return [$ana, $midia, $conta];
}

it('mostra o compositor', function () {
    $this->actingAs(cliente())
        ->get('/publicar')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('cliente/publicacoes'));
});

it('cria a publicacao com um destino por conta e dispara o motor', function () {
    [$ana, $midia, $conta] = clienteComTudoPronto();
    $outra = ContextoDoUsuario::semEscopo(
        fn () => ContaSocial::factory()->doUsuario($ana)->comCredencial()->create()
    );

    $this->actingAs($ana)
        ->post('/publicar', [
            'midia' => $midia->ulid,
            'contas' => [$conta->ulid, $outra->ulid],
            'legenda' => 'Olha esse corte',
            'hashtags' => ['corte'],
        ])
        ->assertRedirect('/publicacoes')
        ->assertSessionHasNoErrors();

    $publicacao = ContextoDoUsuario::semEscopo(fn () => Publicacao::firstOrFail());

    expect($publicacao->usuario_id)->toBe($ana->id)
        ->and($publicacao->enviada_em)->not->toBeNull()
        ->and(ContextoDoUsuario::semEscopo(fn () => Destino::count()))->toBe(2);

    // Um job por destino: falha de uma rede nao impede a outra.
    Queue::assertPushed(PublicarDestinoJob::class, 2);
});

it('recusa publicar em conta de outro cliente', function () {
    [$ana, $midia] = clienteComTudoPronto();
    $alheia = ContaSocial::factory()->doUsuario(cliente())->comCredencial()->create();

    $this->actingAs($ana)
        ->from('/publicar')
        ->post('/publicar', ['midia' => $midia->ulid, 'contas' => [$alheia->ulid], 'legenda' => 'oi'])
        ->assertSessionHasErrors('contas');

    Queue::assertNothingPushed();
});

it('recusa publicar midia de outro cliente', function () {
    [$ana, , $conta] = clienteComTudoPronto();
    $alheia = Midia::factory()->doUsuario(cliente())->create();

    $this->actingAs($ana)
        ->post('/publicar', ['midia' => $alheia->ulid, 'contas' => [$conta->ulid], 'legenda' => 'oi'])
        ->assertNotFound();
});

it('recusa conta desconectada ANTES de criar a publicacao', function () {
    [$ana, $midia] = clienteComTudoPronto();
    $quebrada = ContextoDoUsuario::semEscopo(
        fn () => ContaSocial::factory()->doUsuario($ana)->expirada()->create(['nome_exibicao' => 'ana.bsky.social'])
    );

    $this->actingAs($ana)
        ->from('/publicar')
        ->post('/publicar', ['midia' => $midia->ulid, 'contas' => [$quebrada->ulid], 'legenda' => 'oi'])
        ->assertSessionHasErrors('contas');

    // Deixar passar geraria um destino que ja nasce condenado.
    expect(ContextoDoUsuario::semEscopo(fn () => Publicacao::count()))->toBe(0);
    Queue::assertNothingPushed();
});

it('recusa legenda maior que o limite do Bluesky antes de enviar', function () {
    [$ana, $midia, $conta] = clienteComTudoPronto();

    $this->actingAs($ana)
        ->from('/publicar')
        ->post('/publicar', [
            'midia' => $midia->ulid,
            'contas' => [$conta->ulid],
            'legenda' => str_repeat('a', 320),
        ])
        ->assertSessionHasErrors('legenda');

    expect(ContextoDoUsuario::semEscopo(fn () => Publicacao::count()))->toBe(0);
});

it('exige pelo menos uma conta', function () {
    [$ana, $midia] = clienteComTudoPronto();

    $this->actingAs($ana)
        ->from('/publicar')
        ->post('/publicar', ['midia' => $midia->ulid, 'contas' => [], 'legenda' => 'oi'])
        ->assertSessionHasErrors('contas');
});

it('⭐ o # e LIMPO, nao recusado (DEC-152)', function () {
    /*
     * ⚠️ Isto ja foi recusa, e a recusa estava errada: o campo da tela separa
     * por `#`, mas colar uma lista pronta nao passa por ele — e negar por um
     * caractere que a pessoa nao escolheu escrever e recusa que ela nao tem
     * como entender.
     *
     * ⛔ E a limpeza vale para as DUAS portas: publicar e as hashtags do grupo.
     */
    [$ana, $midia, $conta] = clienteComTudoPronto();

    $this->actingAs($ana)
        ->from('/publicar')
        ->post('/publicar', [
            'midia' => $midia->ulid,
            'contas' => [$conta->ulid],
            'hashtags' => ['#corte'],
        ])
        ->assertSessionHasNoErrors();

    expect(Publicacao::firstOrFail()->hashtags)->toBe(['corte']);
});

it('recusa hashtag com espaco', function () {
    // ⚠️ Espaco continua recusado: `corte shorts` seria UMA hashtag que rede
    // nenhuma aceita — e a pessoa quis duas.
    [$ana, $midia, $conta] = clienteComTudoPronto();

    $this->actingAs($ana)
        ->from('/publicar')
        ->post('/publicar', [
            'midia' => $midia->ulid,
            'contas' => [$conta->ulid],
            'hashtags' => ['tem espaco'],
        ])
        ->assertSessionHasErrors('hashtags.0');
});

it('lista as publicacoes com o estado de cada destino', function () {
    [$ana, $midia, $conta] = clienteComTudoPronto();

    $this->actingAs($ana)->post('/publicar', [
        'midia' => $midia->ulid, 'contas' => [$conta->ulid], 'legenda' => 'oi',
    ]);

    $this->actingAs($ana)
        ->get('/publicacoes')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('cliente/publicacoes')
            ->has('publicacoes.data', 1)
            ->has('publicacoes.data.0.destinos', 1)
            // ⭐ Sem prova ainda: o link so aparece depois da conciliacao.
            ->where('publicacoes.data.0.destinos.0.url', null)
            ->where('publicacoes.data.0.destinos.0.status', 'pendente'));
});

it('nao lista publicacao de outro cliente', function () {
    Publicacao::factory()->doUsuario(cliente())->enviada()->create();

    $this->actingAs(cliente())
        ->get('/publicacoes')
        ->assertInertia(fn ($p) => $p->has('publicacoes.data', 0));
});

it('deixa tentar de novo um destino que falhou', function () {
    [$ana, $midia, $conta] = clienteComTudoPronto();
    ContextoDoUsuario::definir($ana);
    $publicacao = Publicacao::factory()->doUsuario($ana)->enviada()->create(['midia_id' => $midia->id]);
    $destino = Destino::factory()->create([
        'publicacao_id' => $publicacao->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Falhou,
        'tentativas_feitas' => 3,
    ]);
    ContextoDoUsuario::limpar();

    $this->actingAs($ana)
        ->from('/publicacoes')
        ->post("/publicacoes/destinos/{$destino->ulid}/tentar-de-novo")
        ->assertRedirect('/publicacoes');

    expect($destino->fresh())
        ->status->toBe(StatusDestino::Pendente)
        ->tentativas_feitas->toBe(0);

    Queue::assertPushed(PublicarDestinoJob::class);
});

it('nao deixa reprocessar destino de outro cliente', function () {
    $outro = cliente();
    ContextoDoUsuario::definir($outro);
    $publicacao = Publicacao::factory()->doUsuario($outro)->enviada()->create();
    $destino = Destino::factory()->create([
        'publicacao_id' => $publicacao->id,
        'conta_social_id' => ContaSocial::factory()->doUsuario($outro)->create()->id,
        'status' => StatusDestino::Falhou,
    ]);
    ContextoDoUsuario::limpar();

    $this->actingAs(cliente())
        ->post("/publicacoes/destinos/{$destino->ulid}/tentar-de-novo")
        ->assertNotFound();
});

it('barra o admin', function () {
    $this->actingAs(admin())->get('/publicar')->assertNotFound();
    $this->actingAs(admin())->get('/publicacoes')->assertNotFound();
});

it('⚠️ não repete o nome do dono quando ele tem uma conta só na rede', function () {
    // No painel dele, "Gabriel Ferreira de Moraes" em cada linha não informa
    // nada — ele sabe de quem é a conta. O que importa é a REDE.
    $dono = cliente();
    ContextoDoUsuario::definir($dono);

    $conta = ContaSocial::factory()->doUsuario($dono)->comCredencial()->create();
    $publicacao = Publicacao::factory()->doUsuario($dono)->enviada()->create();
    Destino::factory()->create([
        'publicacao_id' => $publicacao->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Publicado,
        'url_publicada' => 'https://bsky.app/post/x',
    ]);
    ContextoDoUsuario::limpar();

    $this->actingAs($dono)
        ->get('/publicacoes')
        ->assertInertia(fn ($p) => $p->where('publicacoes.data.0.destinos.0.conta', null));
});

it('mostra o nome da conta quando há mais de uma na mesma rede', function () {
    // Com dois canais, saber em qual subiu é exatamente o que importa.
    $dono = cliente();
    ContextoDoUsuario::definir($dono);

    $primeira = ContaSocial::factory()->doUsuario($dono)->comCredencial()
        ->create(['nome_exibicao' => 'Canal Um']);
    ContaSocial::factory()->doUsuario($dono)->comCredencial()
        ->create(['nome_exibicao' => 'Canal Dois']);

    $publicacao = Publicacao::factory()->doUsuario($dono)->enviada()->create();
    Destino::factory()->create([
        'publicacao_id' => $publicacao->id,
        'conta_social_id' => $primeira->id,
        'status' => StatusDestino::Publicado,
        'url_publicada' => 'https://bsky.app/post/x',
    ]);
    ContextoDoUsuario::limpar();

    $this->actingAs($dono)
        ->get('/publicacoes')
        ->assertInertia(fn ($p) => $p->where('publicacoes.data.0.destinos.0.conta', 'Canal Um'));
});

it('⛔ recusa ANTES de enviar quando a rede exige titulo (DEC-166)', function () {
    /*
     * ⛔ Isto ja subiu na fila e falhou la na frente: o publicador do YouTube
     * recusava por titulo vazio, o destino virava FALHOU, e o quadrado da rede
     * mostrava "nao foi" em vermelho — por uma coisa que dava para saber antes
     * de clicar.
     *
     * ⭐ Contar falha e placar. Impedir e produto.
     */
    [$ana, $midia, $conta] = clienteComTudoPronto();

    ContextoDoUsuario::definir($ana);
    $conta->forceFill(['plataforma' => App\Enums\Plataforma::Youtube])->save();
    ContextoDoUsuario::limpar();

    $this->actingAs($ana)
        ->from('/publicar')
        ->post('/publicar', [
            'midia' => $midia->ulid,
            'contas' => [$conta->ulid],
            'legenda' => 'sem titulo nenhum',
        ])
        ->assertSessionHasErrors('legenda');

    // ⛔ E nao criou destino nenhum: recusar antes significa NAO enfileirar.
    ContextoDoUsuario::definir($ana);
    expect(Destino::count())->toBe(0);
});

it('⭐ e com titulo passa normalmente', function () {
    [$ana, $midia, $conta] = clienteComTudoPronto();

    ContextoDoUsuario::definir($ana);
    $conta->forceFill(['plataforma' => App\Enums\Plataforma::Youtube])->save();
    ContextoDoUsuario::limpar();

    $this->actingAs($ana)->post('/publicar', [
        'midia' => $midia->ulid,
        'contas' => [$conta->ulid],
        'titulo' => 'Meu corte',
        'legenda' => 'olha isso',
    ])->assertSessionHasNoErrors();

    ContextoDoUsuario::definir($ana);
    expect(Destino::count())->toBe(1);
});

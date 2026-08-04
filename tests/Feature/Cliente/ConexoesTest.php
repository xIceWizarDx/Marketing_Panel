<?php

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Credencial;
use App\Models\Destino;
use App\Models\Publicacao;
use App\Support\ContextoDoUsuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

beforeEach(fn () => ContextoDoUsuario::limpar());
afterEach(fn () => ContextoDoUsuario::limpar());

function blueskyAceitaLogin(): void
{
    Http::fake(['*com.atproto.server.createSession*' => Http::response([
        'did' => 'did:plc:abc123',
        'handle' => 'ana.bsky.social',
        'accessJwt' => 'jwt',
    ])]);
}

it('⭐ as redes moram na VISAO GERAL, nao numa tela propria', function () {
    // DEC-63: Conexoes deixou de ser tela. Uma tela so para responder "como
    // esta tudo?" obrigava a passar por duas para ter certeza.
    $this->actingAs(cliente())
        ->get('/painel')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('cliente/visao-geral')->has('redes'));
});

it('⛔ a tela antiga de conexoes nao existe mais', function () {
    // Rota viva sem tela vira 500 latente; rota morta com link vivo vira 404 na
    // cara da pessoa. As duas saem juntas.
    expect(Route::has('conexoes'))->toBeFalse();

    $this->actingAs(cliente())->get('/conexoes')->assertNotFound();
});

it('conecta uma conta do Bluesky', function () {
    $ana = cliente();
    blueskyAceitaLogin();

    $this->actingAs($ana)
        ->post('/conexoes/bluesky', [
            'identificador' => '@ana.bsky.social',
            'senha_de_aplicativo' => 'abcd-efgh-ijkl-mnop',
        ])
        ->assertRedirect('/painel')
        ->assertSessionHasNoErrors();

    $conta = ContextoDoUsuario::semEscopo(fn () => ContaSocial::firstOrFail());

    expect($conta->usuario_id)->toBe($ana->id)
        ->and($conta->identificador_externo)->toBe('did:plc:abc123')
        ->and($conta->nome_exibicao)->toBe('ana.bsky.social')
        ->and($conta->status)->toBe(StatusConta::Ativa);
});

it('confere a senha antes de guardar', function () {
    Http::fake(['*com.atproto.server.createSession*' => Http::response(['error' => 'AuthFactorTokenRequired'], 401)]);

    $this->actingAs(cliente())
        ->from('/painel')
        ->post('/conexoes/bluesky', ['identificador' => 'ana.bsky.social', 'senha_de_aplicativo' => 'errada'])
        ->assertSessionHasErrors('identificador');

    // Senha errada guardada viraria conta que parece conectada e falha na hora
    // de publicar — o pior momento possível.
    expect(ContextoDoUsuario::semEscopo(fn () => ContaSocial::count()))->toBe(0);
});

it('guarda a senha criptografada e nunca a devolve para a tela', function () {
    $ana = cliente();
    blueskyAceitaLogin();

    $this->actingAs($ana)->post('/conexoes/bluesky', [
        'identificador' => 'ana.bsky.social',
        'senha_de_aplicativo' => 'segredo-abcd-1234',
    ]);

    // No banco, cru: o valor gravado não pode ser o texto original.
    $cru = ContextoDoUsuario::semEscopo(fn () => DB::table('credenciais')->value('access_token'));
    expect($cru)->not->toContain('segredo-abcd-1234');

    // Na serialização do model: escondido.
    $credencial = ContextoDoUsuario::semEscopo(fn () => Credencial::firstOrFail());
    expect($credencial->toArray())->not->toHaveKey('access_token')
        ->and($credencial->access_token)->toBe('segredo-abcd-1234');

    // E na tela: nem o valor, nem a chave.
    $html = $this->actingAs($ana)->get('/painel')->getContent();
    expect($html)->not->toContain('segredo-abcd-1234')
        ->and($html)->not->toContain('access_token');
});

it('reconectar a mesma conta troca a senha em vez de duplicar', function () {
    $ana = cliente();
    blueskyAceitaLogin();

    $this->actingAs($ana)->post('/conexoes/bluesky', ['identificador' => 'ana.bsky.social', 'senha_de_aplicativo' => 'primeira']);
    $this->actingAs($ana)->post('/conexoes/bluesky', ['identificador' => 'ana.bsky.social', 'senha_de_aplicativo' => 'segunda']);

    expect(ContextoDoUsuario::semEscopo(fn () => ContaSocial::count()))->toBe(1)
        ->and(ContextoDoUsuario::semEscopo(fn () => Credencial::firstOrFail()->access_token))->toBe('segunda');
});

it('desconectar apaga a credencial e PRESERVA a conta', function () {
    $ana = cliente();
    ContextoDoUsuario::definir($ana);
    $conta = ContaSocial::factory()->doUsuario($ana)->comCredencial()->create();
    ContextoDoUsuario::limpar();

    $this->actingAs($ana)->delete("/conexoes/{$conta->ulid}")->assertRedirect('/painel');

    // A linha sobrevive: o histórico de publicações aponta pra ela, e apagar
    // levaria junto a prova de entrega do que já foi publicado.
    expect(ContextoDoUsuario::semEscopo(fn () => ContaSocial::count()))->toBe(1)
        ->and($conta->fresh()->status)->toBe(StatusConta::Desconectada)
        ->and(ContextoDoUsuario::semEscopo(fn () => Credencial::count()))->toBe(0);
});

it('não deixa um cliente desconectar a conta do outro', function () {
    $conta = ContaSocial::factory()->doUsuario(cliente())->comCredencial()->create();

    $this->actingAs(cliente())
        ->delete("/conexoes/{$conta->ulid}")
        ->assertNotFound();

    expect($conta->fresh()->status)->toBe(StatusConta::Ativa);
});

it('não lista a conta de outro cliente', function () {
    ContaSocial::factory()->doUsuario(cliente())->create();

    $this->actingAs(cliente())
        ->get('/painel')
        ->assertInertia(fn ($p) => $p
            ->where('totalConectado', 0)
            ->where('redes', fn ($redes) => collect($redes)->every(fn ($r) => $r['contas'] === [])));
});

it('manda TODAS as redes — a tela é que mostra só as conectadas', function () {
    // ⚠️ Procurar pelo valor, nunca pela posição. A ordem da lista é decisão de
    // produto e já mudou uma vez; teste preso ao índice quebra junto sem que
    // nada de errado tenha acontecido.
    //
    // O servidor manda o catálogo inteiro porque ele vive no modal de conectar.
    // Quem filtra "só as minhas" é a tela: filtrar aqui deixaria o catálogo sem
    // o que oferecer.
    $rede = fn ($redes, string $valor) => collect($redes)->firstWhere('valor', $valor);

    $this->actingAs(cliente())
        ->get('/painel')
        ->assertInertia(fn ($p) => $p
            ->has('redes', count(Plataforma::cases()))
            ->where('redes', function ($redes) use ($rede) {
                // O Bluesky publica sem depender de aprovação nem de credencial
                // nossa — é o único que pode ser afirmado sempre.
                expect($rede($redes, 'bluesky')['disponivel'])->toBeTrue();

                // ⚠️ Rede SEM publicador escrito nunca mostra botão que falha.
                // Antes este teste usava o YouTube, e passava só porque faltava
                // a credencial no ambiente: no dia em que ela foi configurada,
                // quebrou sem nada estar errado.
                expect($rede($redes, 'pinterest')['disponivel'])->toBeFalse();

                return true;
            }));
});

it('agrupa a conta conectada dentro da carta da rede certa', function () {
    $ana = cliente();
    ContaSocial::factory()->doUsuario($ana)->comCredencial()->create(['nome_exibicao' => 'ana.bsky.social']);

    $this->actingAs($ana)
        ->get('/painel')
        ->assertInertia(fn ($p) => $p
            ->where('totalConectado', 1)
            ->where('redes', function ($redes) {
                $bluesky = collect($redes)->firstWhere('valor', 'bluesky');

                expect($bluesky['contas'][0]['nome'])->toBe('ana.bsky.social')
                    ->and($bluesky['contas'][0]['cor'])->toBe('ok');

                return true;
            }));
});

it('barra o admin', function () {
    $this->actingAs(admin())->get('/painel')->assertNotFound();
});

it('barra o visitante', function () {
    $this->get('/painel')->assertRedirect(route('entrar'));
});

it('conta como "no ar" só o que foi CONFIRMADO na rede', function () {
    $ana = cliente();
    ContextoDoUsuario::definir($ana);

    $conta = ContaSocial::factory()->doUsuario($ana)->comCredencial()->create();
    $publicacao = Publicacao::factory()->doUsuario($ana)->enviada()->create();

    // Um confirmado (tem link = tem prova) e dois ainda em andamento.
    Destino::factory()->create([
        'publicacao_id' => $publicacao->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Publicado,
        'url_publicada' => 'https://bsky.app/post/1',
    ]);

    $outra = Publicacao::factory()->doUsuario($ana)->enviada()->create();
    Destino::factory()->create([
        'publicacao_id' => $outra->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Processando,
    ]);

    ContextoDoUsuario::limpar();

    $this->actingAs($ana)
        ->get('/painel')
        // ⭐ DEC-31: contar envio seria contar promessa. So conta o confirmado.
        ->assertInertia(fn ($p) => $p->where('redes', function ($redes) {
            expect(collect($redes)->firstWhere('valor', 'bluesky')['publicados'])->toBe(1);

            return true;
        }));
});

it('nao soma no KPI o que subiu na conta de outro cliente', function () {
    $ana = cliente();
    $bruno = cliente();

    foreach ([$ana, $bruno] as $dono) {
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
    }

    // `destinos` nao tem dono proprio: o filtro vem pela conta. Se falhasse,
    // a Ana veria o numero do Bruno somado ao dela.
    $this->actingAs($ana)
        ->get('/painel')
        ->assertInertia(fn ($p) => $p->where('redes', function ($redes) {
            expect(collect($redes)->firstWhere('valor', 'bluesky')['publicados'])->toBe(1);

            return true;
        }));
});

it('⭐ mostra a falha do lado do acerto, não só o que deu certo', function () {
    // Painel que só mostra sucesso é o defeito que o produto combate. Os três
    // números contam o mesmo fato por inteiro.
    $ana = cliente();
    ContextoDoUsuario::definir($ana);

    $conta = ContaSocial::factory()->doUsuario($ana)->comCredencial()->create();

    $estados = [
        StatusDestino::Publicado,
        StatusDestino::Publicado,
        StatusDestino::Falhou,
        StatusDestino::Processando,
        StatusDestino::AguardandoJanela,
    ];

    // ⚠️ Uma publicação por destino: o banco tem chave única em
    // (publicacao_id, conta_social_id), justamente para o mesmo conteúdo não
    // subir duas vezes na mesma conta.
    foreach ($estados as $status) {
        Destino::factory()->create([
            'publicacao_id' => Publicacao::factory()->doUsuario($ana)->enviada()->create()->id,
            'conta_social_id' => $conta->id,
            'status' => $status,
            'url_publicada' => $status === StatusDestino::Publicado ? 'https://bsky.app/post/x' : null,
        ]);
    }

    ContextoDoUsuario::limpar();

    $this->actingAs($ana)
        ->get('/painel')
        ->assertInertia(fn ($p) => $p->where('redes', function ($redes) {
            $bluesky = collect($redes)->firstWhere('valor', 'bluesky');

            expect($bluesky['publicados'])->toBe(2)
                ->and($bluesky['falhas'])->toBe(1)
                // Pendente, enviando, processando e aguardando janela contam
                // juntos: para quem olha, todos significam "ainda não terminou".
                ->and($bluesky['andando'])->toBe(2);

            return true;
        }));
});

it('não soma a falha de um cliente no número de outro', function () {
    $ana = cliente();
    $bruno = cliente();

    ContextoDoUsuario::definir($bruno);
    $conta = ContaSocial::factory()->doUsuario($bruno)->comCredencial()->create();
    $publicacao = Publicacao::factory()->doUsuario($bruno)->enviada()->create();
    Destino::factory()->create([
        'publicacao_id' => $publicacao->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Falhou,
    ]);
    ContextoDoUsuario::limpar();

    $this->actingAs($ana)
        ->get('/painel')
        ->assertInertia(fn ($p) => $p->where('redes', function ($redes) {
            expect(collect($redes)->firstWhere('valor', 'bluesky')['falhas'])->toBe(0);

            return true;
        }));
});

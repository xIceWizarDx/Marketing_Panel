<?php

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Models\ContaSocial;
use App\Models\Grupo;
use App\Services\ConexaoComThreads;
use App\Services\GrupoService;
use App\Support\ContextoDoUsuario;
use App\Support\GrupoCorrente;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/*
| Guardiao do THREADS (plano 21, DEC-99..104).
|
| ⚠️ Cada teste aqui trava um achado da documentacao oficial — e varios deles
| contrariam o que a DEC-30 supunha: o Threads NAO e uma variacao do Instagram.
| Janela de autorizacao propria, servidor proprio, escopos proprios, e um token
| que morre de vez.
*/

beforeEach(function () {
    ContextoDoUsuario::limpar();
    GrupoCorrente::esquecer();

    config([
        'services.threads.client_id' => 'id-de-teste',
        'services.threads.client_secret' => 'segredo-de-teste',
        'services.threads.redirect' => 'https://painel.test/conexoes/threads/retorno',
    ]);
});

afterEach(function () {
    ContextoDoUsuario::limpar();
    GrupoCorrente::esquecer();
});

/** As tres respostas do caminho feliz: token curto, token longo e perfil. */
function threadsRespondendo(array $trocas = []): void
{
    Http::fake(array_merge([
        'graph.threads.net/oauth/access_token' => Http::response([
            'access_token' => 'curto-1h',
            'permissions' => ['threads_basic', 'threads_content_publish'],
        ]),
        'graph.threads.net/access_token*' => Http::response([
            'access_token' => 'longo-60-dias',
            'token_type' => 'bearer',
            'expires_in' => 5184000,
        ]),
        'graph.threads.net/v1.0/me*' => Http::response([
            'id' => '17841400000000000',
            'username' => 'meu.perfil',
            'threads_profile_picture_url' => 'https://exemplo.test/foto.jpg',
        ]),
    ], $trocas));
}

describe('⛔ o Threads NAO e o Instagram (DEC-99)', function () {
    it('⭐ a janela de autorização é em threads.net, não no Facebook', function () {
        $endereco = app(ConexaoComThreads::class)->enderecoDeAutorizacao('estado-123');

        expect($endereco)->toStartWith('https://threads.net/oauth/authorize')
            ->and($endereco)->not->toContain('facebook.com');
    });

    it('⭐ pede threads_basic ALÉM de publicar — sem ele a conexão é inútil', function () {
        // `threads_basic` e exigido em TODO endpoint, inclusive nos de publicacao.
        $endereco = app(ConexaoComThreads::class)->enderecoDeAutorizacao('estado-123');

        expect($endereco)->toContain('threads_basic')
            ->and($endereco)->toContain('threads_content_publish');
    });

    it('⛔ NÃO pede permissão de conversa — o produto publica, não responde', function () {
        // Escopo pedido e nao usado e permissao concedida a toa, e a analise do
        // aplicativo cobra por ela.
        $endereco = app(ConexaoComThreads::class)->enderecoDeAutorizacao('estado-123');

        expect($endereco)->not->toContain('threads_manage_replies')
            ->and($endereco)->not->toContain('threads_read_replies');
    });
});

describe('o token', function () {
    it('⭐ é trocado pelo LONGO na hora da conexão — o curto vive 1 hora', function () {
        /*
         * ⚠️ Guardar o token curto seria uma conta que morre antes do fim do
         * expediente. E a troca depois e impossivel: token vencido nao vira
         * token longo.
         */
        $ana = cliente();
        ContextoDoUsuario::definir($ana);
        GrupoCorrente::definir(Grupo::firstOrFail());

        threadsRespondendo();

        $conta = app(ConexaoComThreads::class)->conectar('codigo-do-retorno');

        expect($conta->credencial->access_token)->toBe('longo-60-dias')
            ->and($conta->credencial->access_token)->not->toBe('curto-1h');
    });

    it('⛔ NÃO guarda token de renovação — aqui ele não existe', function () {
        // O Threads renova o proprio token longo apresentando ele mesmo. Guardar
        // algo em `refresh_token` seria inventar um segredo que a rede nao deu.
        $ana = cliente();
        ContextoDoUsuario::definir($ana);
        GrupoCorrente::definir(Grupo::firstOrFail());

        threadsRespondendo();

        expect(app(ConexaoComThreads::class)->conectar('codigo')->credencial->refresh_token)->toBeNull();
    });

    it('⚠️ `expira_em` guarda o prazo de MORTE da conta, não de um token que se renova sozinho', function () {
        $ana = cliente();
        ContextoDoUsuario::definir($ana);
        GrupoCorrente::definir(Grupo::firstOrFail());

        threadsRespondendo();

        $conta = app(ConexaoComThreads::class)->conectar('codigo');

        // 5.184.000 segundos = 60 dias.
        expect(now()->diffInDays($conta->credencial->expira_em))->toBeGreaterThan(58);
    });
});

describe('⛔ o escopo CONCEDIDO, nunca o pedido', function () {
    it('recusa quando a pessoa desmarcou a permissão de publicar', function () {
        // A tela de autorizacao deixa desmarcar. Guardar o que PEDIMOS seria
        // guardar uma suposicao e descobrir a verdade na hora do erro.
        $ana = cliente();
        ContextoDoUsuario::definir($ana);
        GrupoCorrente::definir(Grupo::firstOrFail());

        threadsRespondendo([
            'graph.threads.net/oauth/access_token' => Http::response([
                'access_token' => 'curto-1h',
                'permissions' => ['threads_basic'],
            ]),
        ]);

        expect(fn () => app(ConexaoComThreads::class)->conectar('codigo'))
            ->toThrow(ValidationException::class);
    });

    it('⚠️ lê `permissions`, e não `scope` — o campo do padrão OAuth não existe aqui', function () {
        // Ler o campo errado devolveria lista vazia e recusaria toda conexao
        // valida. Este teste morre se alguem trocar o nome do campo.
        $ana = cliente();
        ContextoDoUsuario::definir($ana);
        GrupoCorrente::definir(Grupo::firstOrFail());

        threadsRespondendo();

        expect(app(ConexaoComThreads::class)->conectar('codigo')->credencial->escopos)
            ->toContain('threads_content_publish');
    });
});

describe('o identificador', function () {
    it('⭐ guarda o ID, não o nome de usuário — o nome a pessoa troca', function () {
        // ⚠️ O `id` daqui e o mesmo do endereco de publicacao
        // (`POST /{threads-user-id}/threads`). Guardar o username quebraria a
        // publicacao no dia em que a pessoa mudasse o @.
        $ana = cliente();
        ContextoDoUsuario::definir($ana);
        GrupoCorrente::definir(Grupo::firstOrFail());

        threadsRespondendo();

        $conta = app(ConexaoComThreads::class)->conectar('codigo');

        expect($conta->identificador_externo)->toBe('17841400000000000')
            ->and($conta->nome_exibicao)->toBe('meu.perfil');
    });

    it('⛔ a trava de canal em outro grupo vale aqui também', function () {
        $ana = cliente();
        ContextoDoUsuario::definir($ana);

        $noticias = Grupo::firstOrFail();
        ContaSocial::factory()->doUsuario($ana)->doGrupo($noticias)
            ->daPlataforma(Plataforma::Threads)->comCredencial()
            ->create(['identificador_externo' => '17841400000000000']);

        GrupoCorrente::definir(app(GrupoService::class)->criar('Outro'));
        threadsRespondendo();

        expect(fn () => app(ConexaoComThreads::class)->conectar('codigo'))
            ->toThrow(ValidationException::class);
    });
});

describe('⭐ a renovação — o que impede a conta de morrer (DEC-102)', function () {
    /** Uma conta do Threads com token de idade e vencimento controlados. */
    function contaDoThreads(int $idadeEmHoras, int $venceEmDias): ContaSocial
    {
        $ana = cliente();
        ContextoDoUsuario::definir($ana);

        $conta = ContaSocial::factory()->doUsuario($ana)->doGrupo(Grupo::firstOrFail())
            ->daPlataforma(Plataforma::Threads)->comCredencial('token-atual')->create();

        $conta->credencial->forceFill([
            'created_at' => now()->subHours($idadeEmHoras),
            'expira_em' => now()->addDays($venceEmDias),
        ])->save();

        ContextoDoUsuario::limpar();

        return $conta;
    }

    it('⛔ token com MENOS de 24 horas não é renovado — a rede recusaria', function () {
        $conta = contaDoThreads(idadeEmHoras: 2, venceEmDias: 1);

        Http::fake(['graph.threads.net/refresh_access_token*' => Http::response(['access_token' => 'novo'])]);

        $this->artisan('threads:renovar')->assertSuccessful();

        expect($conta->credencial->fresh()->access_token)->toBe('token-atual');
        Http::assertNothingSent();
    });

    it('⭐ token perto do vencimento É renovado, e o prazo recomeça', function () {
        $conta = contaDoThreads(idadeEmHoras: 48, venceEmDias: 10);

        Http::fake([
            'graph.threads.net/refresh_access_token*' => Http::response([
                'access_token' => 'renovado-por-mais-60',
                'expires_in' => 5184000,
            ]),
        ]);

        $this->artisan('threads:renovar')->assertSuccessful();

        $credencial = $conta->credencial->fresh();

        expect($credencial->access_token)->toBe('renovado-por-mais-60')
            ->and(now()->diffInDays($credencial->expira_em))->toBeGreaterThan(58);
    });

    it('⛔ token VENCIDO marca a conta — não há renovação possível, e a pessoa precisa saber', function () {
        /*
         * ⚠️ Este e o unico caso do produto em que a conta morre de vez. Deixar
         * em silencio faria a pessoa descobrir na proxima publicacao, e o
         * semaforo (DEC-32) existe justamente para isso nao acontecer.
         */
        $conta = contaDoThreads(idadeEmHoras: 100, venceEmDias: -1);

        $this->artisan('threads:renovar')->assertSuccessful();

        expect($conta->fresh()->status)->toBe(StatusConta::Expirada)
            ->and($conta->fresh()->status_detalhe)->toContain('Conecte de novo');
    });

    it('⛔ rede fora do ar NÃO marca a conta — a folga existe para isso', function () {
        // Um dia ruim do Threads nao pode custar a conta: ainda ha dias de
        // janela, e amanha o comando tenta de novo.
        $conta = contaDoThreads(idadeEmHoras: 48, venceEmDias: 10);

        Http::fake(['graph.threads.net/refresh_access_token*' => Http::response('', 500)]);

        $this->artisan('threads:renovar')->assertSuccessful();

        expect($conta->fresh()->status)->toBe(StatusConta::Ativa);
    });

    it('⛔ token longe do vencimento não é mexido à toa', function () {
        $conta = contaDoThreads(idadeEmHoras: 48, venceEmDias: 50);

        Http::fake(['graph.threads.net/refresh_access_token*' => Http::response(['access_token' => 'novo'])]);

        $this->artisan('threads:renovar')->assertSuccessful();

        expect($conta->credencial->fresh()->access_token)->toBe('token-atual');
        Http::assertNothingSent();
    });
});

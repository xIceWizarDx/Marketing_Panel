<?php

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Models\ContaSocial;
use App\Models\Grupo;
use App\Publicadores\RegistroDePublicadores;
use App\Services\ConexaoComLinkedin;
use App\Services\GrupoService;
use App\Support\ContextoDoUsuario;
use App\Support\GrupoCorrente;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/*
| Guardiao da CONEXAO COM O LINKEDIN (plano 22, DEC-106..114).
|
| ⚠️ Esta rede quebra duas suposicoes que valiam para todas as outras: o token
| NAO se renova sozinho, e a permissao de LER post nao existe no nivel aberto.
*/

beforeEach(function () {
    ContextoDoUsuario::limpar();
    GrupoCorrente::esquecer();

    config([
        'services.linkedin.client_id' => 'id-de-teste',
        'services.linkedin.client_secret' => 'segredo-de-teste',
        'services.linkedin.redirect' => 'https://painel.test/conexoes/linkedin/retorno',
    ]);
});

afterEach(function () {
    ContextoDoUsuario::limpar();
    GrupoCorrente::esquecer();
});

/** Um dono e um grupo em foco — o servico grava dentro do escopo dos dois. */
function donoDoLinkedin(): void
{
    ContextoDoUsuario::definir(cliente());
    GrupoCorrente::definir(Grupo::firstOrFail());
}

/** As duas respostas do caminho feliz: token e perfil. */
function linkedinRespondendo(array $trocas = []): void
{
    Http::fake(array_merge([
        'www.linkedin.com/oauth/v2/accessToken' => Http::response([
            'access_token' => 'token-de-60-dias',
            'expires_in' => 5184000,
            'scope' => 'openid profile w_member_social',
        ]),
        'api.linkedin.com/v2/userinfo' => Http::response([
            'sub' => 'ABC123',
            'name' => 'Gabriel',
            'picture' => 'https://exemplo.test/foto.jpg',
        ]),
    ], $trocas));
}

describe('a janela de autorização', function () {
    it('pede as permissões abertas, separadas por ESPAÇO', function () {
        // ⚠️ Espaco, nao virgula — a Meta usa virgula, e trocar aqui faria a
        // rede ler tudo como um escopo so, inexistente.
        $endereco = app(ConexaoComLinkedin::class)->enderecoDeAutorizacao('estado-123');

        expect($endereco)->toStartWith('https://www.linkedin.com/oauth/v2/authorization')
            ->and(urldecode($endereco))->toContain('scope=openid profile w_member_social')
            ->and($endereco)->toContain('state=estado-123');
    });

    it('⛔ NÃO pede e-mail — escopo pedido e não usado é permissão concedida à toa', function () {
        expect(urldecode(app(ConexaoComLinkedin::class)->enderecoDeAutorizacao('e')))
            ->not->toContain('email');
    });
});

describe('⭐ os escopos CONCEDIDOS, não os pedidos', function () {
    it('lê a lista separada por espaço que a rede devolve', function () {
        /*
         * ⚠️ Aqui os escopos vem como STRING separada por espaco — nao e lista
         * como no Threads. Ler no formato errado devolveria lista vazia e
         * recusaria toda conexao valida.
         */
        donoDoLinkedin();
        linkedinRespondendo();

        $conta = app(ConexaoComLinkedin::class)->conectar('codigo');

        expect($conta->credencial->escopos)->toBe(['openid', 'profile', 'w_member_social']);
    });

    it('⛔ sem a permissão de publicar, a conexão é recusada', function () {
        // Conta que conecta e nao publica e pior que conexao que falha: ela
        // parece que funciona ate a hora de publicar.
        donoDoLinkedin();
        linkedinRespondendo([
            'www.linkedin.com/oauth/v2/accessToken' => Http::response([
                'access_token' => 'token',
                'expires_in' => 5184000,
                'scope' => 'openid profile',
            ]),
        ]);

        expect(fn () => app(ConexaoComLinkedin::class)->conectar('codigo'))
            ->toThrow(ValidationException::class);
    });
});

describe('⛔ o token de 60 dias que não se renova sozinho (DEC-112)', function () {
    it('⭐ NÃO guarda token de renovação — ele só existe para parceiros aprovados', function () {
        /*
         * ⛔ Guardar um `refresh_token` que a rede nao mandou seria guardar uma
         * promessa falsa: um servico que tentasse usa-lo falharia calado toda
         * madrugada, e a conexao morreria em silencio.
         */
        donoDoLinkedin();
        linkedinRespondendo();

        $conta = app(ConexaoComLinkedin::class)->conectar('codigo');

        expect($conta->credencial->refresh_token)->toBeNull()
            // ⚠️ E `expira_em` aqui significa A DATA DE RECONECTAR, nao o prazo
            // de um trabalho nosso.
            ->and($conta->credencial->expira_em->diffInDays(now()->addDays(60)))->toBeLessThan(1);
    });

    it('⛔ não existe comando de renovação do LinkedIn', function () {
        // Um comando que "renova" e nao renova seria pior que nao ter: um
        // servico verde dizendo que esta tudo bem enquanto a conta morre.
        $comandos = array_keys(app(Kernel::class)->all());

        expect(collect($comandos)->filter(fn ($c) => str_contains($c, 'linkedin') && str_contains($c, 'renovar')))
            ->toBeEmpty();
    });
});

describe('o perfil', function () {
    it('⭐ o `sub` do OpenID Connect é o identificador, e dele sai o URN da pessoa', function () {
        // `author` e `owner` nao aceitam o identificador cru: querem
        // `urn:li:person:{sub}`. Perder o prefixo devolve `INVALID_URN_TYPE`.
        donoDoLinkedin();
        linkedinRespondendo();

        $conta = app(ConexaoComLinkedin::class)->conectar('codigo');

        expect($conta->identificador_externo)->toBe('ABC123')
            ->and($conta->nome_exibicao)->toBe('Gabriel')
            ->and($conta->status)->toBe(StatusConta::Ativa)
            ->and(ConexaoComLinkedin::urnDaPessoa($conta->identificador_externo))
            ->toBe('urn:li:person:ABC123');
    });

    it('o URN já pronto não é embrulhado duas vezes', function () {
        expect(ConexaoComLinkedin::urnDaPessoa('urn:li:person:ABC123'))->toBe('urn:li:person:ABC123');
    });

    it('perfil que não responde vira frase em português, não conexão quebrada', function () {
        donoDoLinkedin();
        linkedinRespondendo(['api.linkedin.com/v2/userinfo' => Http::response([], 401)]);

        expect(fn () => app(ConexaoComLinkedin::class)->conectar('codigo'))
            ->toThrow(ValidationException::class);
    });
});

describe('⛔ um canal mora num grupo só', function () {
    it('conectar o mesmo perfil em outro grupo é recusado, com o nome do grupo', function () {
        // ⚠️ Sem isto o `updateOrCreate` acharia a linha do OUTRO grupo, mudaria
        // ela, e responderia "conectado" sem nada aparecer na tela.
        donoDoLinkedin();
        linkedinRespondendo();

        $conta = app(ConexaoComLinkedin::class)->conectar('codigo');
        $primeiro = $conta->grupo_id;

        $outro = app(GrupoService::class)->criar('Notícias');
        GrupoCorrente::definir($outro);

        expect(fn () => app(ConexaoComLinkedin::class)->conectar('codigo'))
            ->toThrow(ValidationException::class);

        expect(ContaSocial::query()->where('identificador_externo', 'ABC123')->first()->grupo_id)
            ->toBe($primeiro);
    });
});

describe('o botão de conectar', function () {
    it('⭐ só aparece quando a credencial do LinkedIn está configurada', function () {
        // Botao que leva a erro e pior que botao ausente.
        expect(app(RegistroDePublicadores::class)->podeConectar(Plataforma::Linkedin))->toBeTrue();

        config(['services.linkedin.client_id' => null]);

        expect(app(RegistroDePublicadores::class)->podeConectar(Plataforma::Linkedin))->toBeFalse();
    });

    it('⚠️ NÃO exige endereço público — aqui a mídia SOBE, a rede não vem buscar', function () {
        /*
         * ⚠️ Diferente do Threads (DEC-101): la a rede vem BUSCAR o arquivo e
         * sem endereco publico a conexao conecta e nunca publica. Aqui o arquivo
         * sobe em pedacos, entao servidor local basta para conectar.
         */
        config(['app.url' => 'http://localhost:8000']);

        expect(app(RegistroDePublicadores::class)->podeConectar(Plataforma::Linkedin))->toBeTrue()
            ->and(app(RegistroDePublicadores::class)->podeConectar(Plataforma::Threads))->toBeFalse();
    });
});

describe('as rotas', function () {
    it('a porta de autorização e a de retorno existem, e são separadas', function () {
        expect(Route::has('conexoes.linkedin'))->toBeTrue()
            ->and(Route::has('conexoes.linkedin.retorno'))->toBeTrue()
            ->and(route('conexoes.linkedin'))->not->toBe(route('conexoes.linkedin.retorno'));
    });

    it('⛔ visitante não passa pela porta de conexão', function () {
        $this->get(route('conexoes.linkedin'))->assertRedirect();
    });
});

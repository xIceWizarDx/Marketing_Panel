<?php

use App\Models\LogImpersonacao;
use App\Services\ImpersonacaoService;

/*
| Guardiao de IMPERSONACAO (DEC-04).
|
| E o recurso mais perigoso do sistema: um administrador vendo o painel de
| outra pessoa. Cada teste aqui trava uma forma de isso dar errado.
*/

/** Marca a senha como confirmada — o middleware `password.confirm` exige. */
function comSenhaConfirmada(): void
{
    session(['auth.password_confirmed_at' => time()]);
}

it('deixa o administrador entrar na conta do cliente', function () {
    $admin = admin();
    $alvo = cliente();

    comSenhaConfirmada();

    $this->actingAs($admin)
        ->post("/admin/impersonar/{$alvo->ulid}")
        ->assertRedirect(route('painel'));

    $this->assertAuthenticatedAs($alvo);
    expect(session(ImpersonacaoService::CHAVE_ADMIN))->toBe($admin->id);
});

it('registra quem entrou, em quem e de onde', function () {
    $admin = admin();
    $alvo = cliente();

    comSenhaConfirmada();
    $this->actingAs($admin)->post("/admin/impersonar/{$alvo->ulid}");

    $log = LogImpersonacao::firstOrFail();

    expect($log->admin_id)->toBe($admin->id)
        ->and($log->usuario_id)->toBe($alvo->id)
        ->and($log->iniciada_em)->not->toBeNull()
        ->and($log->finalizada_em)->toBeNull()
        ->and($log->ip)->not->toBeNull();
});

it('fecha o registro quando o administrador sai', function () {
    $admin = admin();
    $alvo = cliente();

    comSenhaConfirmada();
    $this->actingAs($admin)->post("/admin/impersonar/{$alvo->ulid}");

    $this->post('/sair-da-impersonacao')->assertRedirect(route('admin.painel'));

    $this->assertAuthenticatedAs($admin);

    expect(LogImpersonacao::firstOrFail()->finalizada_em)->not->toBeNull()
        ->and(session()->has(ImpersonacaoService::CHAVE_ADMIN))->toBeFalse();
});

it('impede o cliente de impersonar qualquer pessoa', function () {
    $alvo = cliente();

    comSenhaConfirmada();

    $this->actingAs(cliente())
        ->post("/admin/impersonar/{$alvo->ulid}")
        ->assertNotFound();

    expect(LogImpersonacao::count())->toBe(0);
});

it('impede impersonar outro administrador', function () {
    $outroAdmin = admin();

    comSenhaConfirmada();

    $this->actingAs(admin())
        ->from('/admin/painel')
        ->post("/admin/impersonar/{$outroAdmin->ulid}")
        ->assertSessionHasErrors('usuario');

    expect(LogImpersonacao::count())->toBe(0);
});

it('impede impersonacao dentro de impersonacao', function () {
    $admin = admin();
    $primeiro = cliente();
    $segundo = cliente();

    comSenhaConfirmada();
    $this->actingAs($admin)->post("/admin/impersonar/{$primeiro->ulid}");

    // Ja impersonando, o papel da sessao e `cliente` — a rota de admin some.
    comSenhaConfirmada();
    $this->post("/admin/impersonar/{$segundo->ulid}")->assertNotFound();

    $this->assertAuthenticatedAs($primeiro);
    expect(LogImpersonacao::count())->toBe(1);
});

it('trata o administrador impersonando como cliente, nao como administrador', function () {
    $admin = admin();
    $alvo = cliente();

    comSenhaConfirmada();
    $this->actingAs($admin)->post("/admin/impersonar/{$alvo->ulid}");

    // Se o papel continuasse `admin`, o administrador veria o painel do admin
    // com a sessao do cliente — mistura que abriria brecha de dado.
    $this->get('/admin/painel')->assertNotFound();
    $this->get('/painel')->assertOk();
});

it('exige confirmacao de senha antes de impersonar', function () {
    $alvo = cliente();

    // Sem `comSenhaConfirmada()`: sessao aberta esquecida no computador nao pode
    // virar acesso ao dado de um cliente.
    $this->actingAs(admin())
        ->post("/admin/impersonar/{$alvo->ulid}")
        ->assertRedirect(route('senha.confirmar'));

    expect(LogImpersonacao::count())->toBe(0);
});

it('mostra a tarja de suporte enquanto a impersonacao dura', function () {
    $admin = admin(['nome' => 'Ana Administradora']);
    $alvo = cliente(['nome' => 'Bruno Cliente']);

    comSenhaConfirmada();
    $this->actingAs($admin)->post("/admin/impersonar/{$alvo->ulid}");

    $this->get('/painel')->assertInertia(fn ($pagina) => $pagina
        ->where('impersonacao.adminNome', 'Ana Administradora')
        ->where('impersonacao.usuarioNome', 'Bruno Cliente'));
});

it('nao mostra a tarja fora da impersonacao', function () {
    $this->actingAs(cliente())
        ->get('/painel')
        ->assertInertia(fn ($pagina) => $pagina->where('impersonacao', null));
});

it('nao aceita ulid inexistente', function () {
    comSenhaConfirmada();

    $this->actingAs(admin())
        ->post('/admin/impersonar/01aaaaaaaaaaaaaaaaaaaaaaaa')
        ->assertNotFound();
});

it('usa o ulid na rota, nunca o id sequencial', function () {
    $admin = admin();
    $alvo = cliente();

    comSenhaConfirmada();

    // O id sequencial nao pode servir de chave — senao daria pra varrer contas
    // testando 1, 2, 3...
    $this->actingAs($admin)
        ->post("/admin/impersonar/{$alvo->id}")
        ->assertNotFound();
});

it('deixa o cliente apagar a conta mesmo depois de ter recebido suporte', function () {
    $admin = admin();
    $alvo = cliente();

    comSenhaConfirmada();
    $this->actingAs($admin)->post("/admin/impersonar/{$alvo->ulid}");
    $this->post('/sair-da-impersonacao');

    // Com FK `restrict` no log, isto estourava 500: quem recebeu suporte uma
    // vez ficava impedido de exercer o direito de eliminacao (LGPD art. 18).
    $this->actingAs($alvo)
        ->delete('/minha-conta/perfil', ['password' => senhaDaFactory()])
        ->assertRedirect('/');

    expect($alvo->fresh())->toBeNull();
});

it('preserva o registro do acesso depois de a conta ser apagada', function () {
    $admin = admin();
    $alvo = cliente();
    $ulidDoAlvo = $alvo->ulid;

    comSenhaConfirmada();
    $this->actingAs($admin)->post("/admin/impersonar/{$alvo->ulid}");
    $this->post('/sair-da-impersonacao');

    $this->actingAs($alvo)->delete('/minha-conta/perfil', ['password' => senhaDaFactory()]);

    $log = LogImpersonacao::firstOrFail();

    // A pessoa some; o evento fica — e ainda da pra correlacionar pelo apelido.
    expect($log->usuario_id)->toBeNull()
        ->and($log->usuario_ulid)->toBe($ulidDoAlvo)
        ->and($log->admin_id)->toBe($admin->id)
        ->and($log->iniciada_em)->not->toBeNull()
        ->and($log->ip)->not->toBeNull();
});

it('barra o visitante na rota de sair da impersonacao', function () {
    $this->post('/sair-da-impersonacao')->assertRedirect(route('entrar'));
});

it('nao quebra quando alguem chama sair sem estar impersonando', function () {
    $this->actingAs(cliente())
        ->post('/sair-da-impersonacao')
        ->assertRedirect(route('entrar'));
});

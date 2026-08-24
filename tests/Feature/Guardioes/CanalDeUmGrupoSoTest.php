<?php

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Models\ContaSocial;
use App\Models\Grupo;
use App\Services\ContaSocialService;
use App\Services\GrupoService;
use App\Support\ContextoDoUsuario;
use App\Support\GrupoCorrente;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/*
| Guardiao do "UM CANAL, UM GRUPO SO" (DEC-70) na hora de CONECTAR.
|
| ⛔ O defeito que estes testes travam era silencioso e caro: conectar o mesmo
| canal estando dentro de OUTRO grupo respondia "conectado com sucesso" e nao
| mostrava nada. O `updateOrCreate` achava o registro pela chave unica — que nao
| inclui o grupo, de proposito — atualizava nome e situacao, e deixava o
| `grupo_id` como estava. A pessoa autorizava na rede, voltava, lia que deu
| certo, e o grupo continuava vazio.
*/

beforeEach(function () {
    ContextoDoUsuario::limpar();
    GrupoCorrente::esquecer();
});

afterEach(function () {
    ContextoDoUsuario::limpar();
    GrupoCorrente::esquecer();
});

/** A sessao que o Bluesky devolve quando a senha de aplicativo confere. */
function blueskyRespondendo(string $did = 'did:plc:canal-unico'): void
{
    Http::fake([
        'bsky.social/xrpc/com.atproto.server.createSession' => Http::response([
            'accessJwt' => 'jwt',
            'refreshJwt' => 'refresh',
            'did' => $did,
            'handle' => 'canal.bsky.social',
        ]),
    ]);
}

it('⛔ recusa conectar um canal que ja vive em OUTRO grupo, e diz onde ele esta', function () {
    $ana = cliente();
    ContextoDoUsuario::definir($ana);

    $noticias = Grupo::firstOrFail();
    $noticias->forceFill(['nome' => 'Notícias'])->save();

    // O canal ja mora em Notícias.
    ContaSocial::factory()
        ->doUsuario($ana)
        ->doGrupo($noticias)
        ->daPlataforma(Plataforma::Bluesky)
        ->comCredencial()
        ->create(['identificador_externo' => 'did:plc:canal-unico', 'nome_exibicao' => 'canal.bsky.social']);

    // A pessoa entra em outro grupo e tenta conectar o MESMO canal.
    $outro = app(GrupoService::class)->criar('Teste');
    GrupoCorrente::definir($outro);

    blueskyRespondendo();

    expect(fn () => app(ContaSocialService::class)->conectarBluesky('canal.bsky.social', 'senha-de-app'))
        ->toThrow(ValidationException::class);

    // ⭐ E o canal NAO se mexeu: recusar nao pode virar mover pela metade.
    $conta = ContaSocial::withoutGlobalScopes()->where('identificador_externo', 'did:plc:canal-unico')->firstOrFail();
    expect($conta->grupo_id)->toBe($noticias->id);
});

it('a mensagem nomeia o canal e o grupo — sem isso a pessoa nao sabe onde procurar', function () {
    $ana = cliente();
    ContextoDoUsuario::definir($ana);

    $noticias = Grupo::firstOrFail();
    $noticias->forceFill(['nome' => 'Notícias'])->save();

    ContaSocial::factory()
        ->doUsuario($ana)
        ->doGrupo($noticias)
        ->daPlataforma(Plataforma::Bluesky)
        ->comCredencial()
        ->create(['identificador_externo' => 'did:plc:canal-unico', 'nome_exibicao' => 'canal.bsky.social']);

    GrupoCorrente::definir(app(GrupoService::class)->criar('Teste'));
    blueskyRespondendo();

    try {
        app(ContaSocialService::class)->conectarBluesky('canal.bsky.social', 'senha-de-app');
        $this->fail('deveria ter recusado');
    } catch (ValidationException $e) {
        $mensagem = $e->validator->errors()->first('identificador');

        expect($mensagem)->toContain('canal.bsky.social')
            ->and($mensagem)->toContain('Notícias');
    }
});

it('⭐ reconectar no MESMO grupo continua passando — e o token é trocado', function () {
    // ⚠️ Este e o caminho normal de renovar autorizacao vencida. Travar isso
    // quebraria o conserto do semaforo (DEC-32), que e o diferencial da tela.
    $ana = cliente();
    ContextoDoUsuario::definir($ana);

    $grupo = Grupo::firstOrFail();
    GrupoCorrente::definir($grupo);

    ContaSocial::factory()
        ->doUsuario($ana)
        ->doGrupo($grupo)
        ->daPlataforma(Plataforma::Bluesky)
        ->comCredencial('senha-velha')
        ->create(['identificador_externo' => 'did:plc:canal-unico', 'status' => StatusConta::Expirada]);

    blueskyRespondendo();

    $conta = app(ContaSocialService::class)->conectarBluesky('canal.bsky.social', 'senha-nova');

    expect($conta->status)->toBe(StatusConta::Ativa)
        ->and($conta->grupo_id)->toBe($grupo->id)
        ->and($conta->credencial->access_token)->toBe('senha-nova');
});

it('⛔ canal de OUTRO DONO nao atrapalha: a trava é por dono, não global', function () {
    // O escopo de dono ja filtra a consulta do guarda. Sem isso, o canal de um
    // cliente impediria outro cliente de conectar o proprio canal homonimo — e
    // pior, a mensagem revelaria o nome de um grupo alheio.
    $bruno = cliente();
    ContextoDoUsuario::definir($bruno);
    ContaSocial::factory()
        ->doUsuario($bruno)
        ->doGrupo(Grupo::firstOrFail())
        ->daPlataforma(Plataforma::Bluesky)
        ->comCredencial()
        ->create(['identificador_externo' => 'did:plc:canal-unico']);
    ContextoDoUsuario::limpar();

    $ana = cliente();
    ContextoDoUsuario::definir($ana);
    GrupoCorrente::definir(Grupo::firstOrFail());

    blueskyRespondendo();

    $conta = app(ContaSocialService::class)->conectarBluesky('canal.bsky.social', 'senha-de-app');

    expect($conta->usuario_id)->toBe($ana->id);
});

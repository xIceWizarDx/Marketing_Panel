<?php

use App\Enums\Plataforma;
use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Grupo;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Publicadores\PublicadorDiscord;
use App\Publicadores\Retomada;
use App\Services\ConexaoComDiscord;
use App\Services\PublicacaoService;
use App\Support\ContextoDoUsuario;
use App\Support\GrupoCorrente;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/*
| Guardiao do DISCORD (plano 27, DEC-141 e DEC-142).
|
| ⭐ A conexao mais simples do painel: nao ha OAuth, nem aplicativo, nem portal.
| ⛔ E o unico lugar onde o ENDERECO e a credencial.
*/

beforeEach(function () {
    ContextoDoUsuario::limpar();
    GrupoCorrente::esquecer();
    Storage::fake('local');
});

afterEach(function () {
    ContextoDoUsuario::limpar();
    GrupoCorrente::esquecer();
});

describe('⛔ o endereço É a credencial (DEC-141)', function () {
    it('⭐ o endereço é PARTIDO: identificador na conta, segredo na credencial', function () {
        /*
         * ⛔ Quem tem o endereco inteiro, publica. Guardar ele inteiro num campo
         * visivel seria deixar a senha na tela.
         */
        ContextoDoUsuario::definir(cliente());
        GrupoCorrente::definir(Grupo::firstOrFail());

        Http::fake(['discord.com/api/v10/webhooks/*' => Http::response([
            'id' => '999', 'name' => 'Avisos', 'channel_id' => '555', 'guild_id' => '777',
        ])]);

        $conta = app(ConexaoComDiscord::class)
            ->conectar('https://discord.com/api/webhooks/999/segredo-abc');

        expect($conta->identificador_externo)->toBe('999')
            ->and($conta->credencial->access_token)->toBe('segredo-abc')
            // ⚠️ O nome diz ONDE o video vai cair.
            ->and($conta->nome_exibicao)->toContain('Avisos')
            // ⛔ Webhook nao vence: vale ate alguem apaga-lo no Discord.
            ->and($conta->credencial->expira_em)->toBeNull()
            /*
             * ⭐ O SERVIDOR e guardado aqui porque so o webhook o devolve — a
             * mensagem, nao. Sem ele nao haveria de onde tirar depois.
             */
            ->and($conta->servidor)->toBe('777');
    });

    it('aceita as formas que o Discord entrega, e recusa o resto', function () {
        expect(ConexaoComDiscord::partir('https://discord.com/api/webhooks/1/abc'))
            ->toBe(['id' => '1', 'token' => 'abc'])
            ->and(ConexaoComDiscord::partir('https://discordapp.com/api/webhooks/2/xyz-_1'))
            ->toBe(['id' => '2', 'token' => 'xyz-_1'])
            ->and(ConexaoComDiscord::partir('https://discord.com/api/v10/webhooks/3/tok'))
            ->toBe(['id' => '3', 'token' => 'tok']);

        foreach ([
            'https://discord.com/channels/1/2',
            'discord.com/api/webhooks/1/abc',
            'https://example.com/api/webhooks/1/abc',
            'qualquer coisa',
        ] as $errado) {
            expect(ConexaoComDiscord::partir($errado))->toBeNull($errado);
        }
    });

    it('⭐ o webhook é conferido NA CONEXÃO — endereço errado some no vazio', function () {
        /*
         * ⚠️ Sem conferir, a pessoa conectaria um endereco errado, publicaria, e
         * a publicacao sumiria sem erro nenhum.
         */
        ContextoDoUsuario::definir(cliente());
        GrupoCorrente::definir(Grupo::firstOrFail());

        Http::fake(['discord.com/api/v10/webhooks/*' => Http::response([], 404)]);

        expect(fn () => app(ConexaoComDiscord::class)->conectar('https://discord.com/api/webhooks/1/abc'))
            ->toThrow(ValidationException::class);
    });

    it('⛔ endereço que não é webhook vira frase que ensina onde copiar', function () {
        expect(fn () => app(ConexaoComDiscord::class)->conectar('https://discord.com/channels/1/2'))
            ->toThrow(ValidationException::class);
    });
});

describe('⛔ `wait=true` é obrigatório (DEC-142)', function () {
    it('⭐ sem ele o Discord responde 204 e a falha vira SILÊNCIO', function () {
        /*
         * ⛔ A documentacao e literal: "unconfirmed messages don't generate
         * errors". Sem `wait=true` a publicacao poderia falhar em silencio e o
         * painel diria que deu certo — exatamente o que o produto existe para
         * nao fazer.
         */
        $destino = destinoNoDiscord();

        Http::fake(['discord.com/api/v10/webhooks/*' => Http::response(['id' => 'msg-1'])]);

        app(PublicadorDiscord::class)->publicar($destino, retomadaNoDiscord($destino));

        Http::assertSent(fn ($r) => str_contains($r->url(), 'wait=true'));
    });

    it('⭐ o identificador da mensagem é guardado — a mensagem JÁ existe', function () {
        // ⚠️ Aqui nao ha segundo passo: se o processo morrer sem guardar, a
        // tentativa seguinte publica uma SEGUNDA mensagem.
        $destino = destinoNoDiscord();

        Http::fake(['discord.com/api/v10/webhooks/*' => Http::response(['id' => 'msg-1'])]);

        $resultado = app(PublicadorDiscord::class)->publicar($destino, retomadaNoDiscord($destino));

        expect($resultado->aceito)->toBeTrue()
            ->and($destino->fresh()->handle_externo)->toBe('msg-1');
    });

    it('e uma segunda tentativa não publica de novo', function () {
        $destino = destinoNoDiscord();
        $destino->forceFill(['handle_externo' => 'msg-1'])->save();

        Http::fake();

        $recarregado = $destino->fresh(['publicacao.midia', 'contaSocial.credencial']);

        expect(app(PublicadorDiscord::class)->publicar($recarregado, retomadaNoDiscord($recarregado))->identificadorExterno)
            ->toBe('msg-1');

        Http::assertNothingSent();
    });
});

describe('⭐ a prova é reler a mensagem (DEC-31)', function () {
    it('mensagem que continua no canal vira link', function () {
        $destino = destinoNoDiscord();
        $destino->forceFill(['handle_externo' => 'msg-1'])->save();

        Http::fake(['discord.com/api/v10/webhooks/*/messages/msg-1' => Http::response([
            'id' => 'msg-1', 'channel_id' => '555',
        ])]);

        $resultado = app(PublicadorDiscord::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']));

        /*
         * ⛔ O endereco de uma mensagem tem TRES partes:
         * `channels/{servidor}/{canal}/{mensagem}`. Faltando o servidor, o link
         * ia para `channels/@me` — conversa privada, um link de prova que nao
         * prova nada.
         */
        expect($resultado->noAr)->toBeTrue()
            ->and($resultado->url)->toBe('https://discord.com/channels/777/555/msg-1');
    });

    it('⭐ mensagem apagada no canal é dita pelo que é — só quem relê descobre', function () {
        $destino = destinoNoDiscord();
        $destino->forceFill(['handle_externo' => 'msg-1'])->save();

        Http::fake(['discord.com/api/v10/webhooks/*/messages/msg-1' => Http::response([], 404)]);

        expect(app(PublicadorDiscord::class)->conciliar($destino->fresh(['publicacao', 'contaSocial.credencial']))->erro)
            ->toContain('não está mais');
    });
});

describe('⛔ os erros, ditos pelo que são', function () {
    it('⭐ webhook apagado manda CRIAR OUTRO, não tentar de novo', function () {
        // ⚠️ 401, 403 e 404 dizem a mesma coisa aqui na pratica.
        foreach ([401, 403, 404] as $codigo) {
            $destino = destinoNoDiscord();

            Http::fake(['discord.com/api/v10/webhooks/*' => Http::response([], $codigo)]);

            $resultado = app(PublicadorDiscord::class)->publicar($destino, retomadaNoDiscord($destino));

            expect($resultado->erro)->toContain('crie outro')
                ->and($resultado->transitorio)->toBeFalse();
        }
    });

    it('⭐ `413` fala do SERVIDOR, porque o teto depende do impulsionamento', function () {
        // ⚠️ Nao existe numero unico do Discord: o teto sobe com o nivel de
        // impulsionamento do servidor.
        $destino = destinoNoDiscord();

        Http::fake(['discord.com/api/v10/webhooks/*' => Http::response([], 413)]);

        expect(app(PublicadorDiscord::class)->publicar($destino, retomadaNoDiscord($destino))->erro)
            ->toContain('impulsionamento');
    });
});

/** Um destino do Discord pronto para publicar. */
function destinoNoDiscord(): Destino
{
    $dono = cliente();
    ContextoDoUsuario::definir($dono);

    $midia = Midia::factory()->doUsuario($dono)->create(['tamanho_bytes' => 1024]);
    Storage::disk('local')->put($midia->caminho, str_repeat('v', 1024));

    $criada = Publicacao::factory()->doUsuario($dono)->enviada()->create([
        'midia_id' => $midia->id,
        'titulo' => null,
        'legenda' => 'Olha isso',
    ]);

    $conta = ContaSocial::factory()->doUsuario($dono)->doGrupo(Grupo::firstOrFail())
        ->daPlataforma(Plataforma::Discord)->comCredencial('segredo-abc')
        ->create([
            'identificador_externo' => '999',
            // ⚠️ Guardado na conexao: sem ele o link da prova ia para conversa
            // privada.
            'servidor' => '777',
            'nome_exibicao' => 'Avisos',
        ]);

    $destino = Destino::factory()->create([
        'publicacao_id' => $criada->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Enviando,
    ]);

    return $destino->fresh(['publicacao.midia', 'contaSocial.credencial']);
}

function retomadaNoDiscord(Destino $destino): Retomada
{
    return new Retomada($destino, app(PublicacaoService::class));
}

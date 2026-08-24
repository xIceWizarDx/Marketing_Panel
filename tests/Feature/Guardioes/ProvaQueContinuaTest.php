<?php

use App\Enums\Plataforma;
use App\Enums\StatusDestino;
use App\Enums\StatusPublicacao;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Grupo;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Services\PublicacaoService;
use App\Support\ContextoDoUsuario;
use Illuminate\Support\Facades\Http;

/*
| Guardiao da PROVA QUE CONTINUA (plano 32, DEC-145).
|
| ⛔ O produto se apoia em "provamos que publicou". A conciliacao perguntava 20
| vezes — cerca de 3h30 — e parava para sempre. Moderacao de rede nao trabalha
| nesse relogio: um video derrubado no dia seguinte continuava marcado "No ar".
*/

beforeEach(fn () => ContextoDoUsuario::limpar());
afterEach(fn () => ContextoDoUsuario::limpar());

describe('⭐ o post que saiu do ar é rebaixado', function () {
    it('⛔ post removido depois de publicado deixa de dizer "No ar"', function () {
        /*
         * ⚠️ E o caso que o produto existe para pegar, e o unico jeito de pegar
         * e voltar a perguntar depois — dias depois, nao horas.
         */
        $destino = destinoPublicado(Plataforma::Bluesky);

        /*
         * ⚠️ No Bluesky quem diz "sumiu" e o **400** (`RecordNotFound`), nao o
         * 404 — e essa diferenca e por rede. Usar o codigo errado aqui faria o
         * teste passar por engano no dia em que o publicador mudasse.
         */
        Http::fake([
            '*createSession*' => Http::response(['accessJwt' => 'j', 'did' => 'did:x']),
            '*' => Http::response(['error' => 'RecordNotFound'], 400),
        ]);

        $this->artisan('publicacoes:reconferir')->assertSuccessful();

        /*
         * ⭐ `Removido`, nao `Falhou` (DEC-148). As duas alternativas mentem:
         * deixar "Publicado" diz que continua no ar; dizer "Falhou" diz que
         * nunca subiu. Ele subiu — e saiu.
         */
        expect($destino->fresh()->status)->toBe(StatusDestino::Removido);
    });

    it('⭐ e a frase diz que ele SUBIU e foi removido — não que falhou', function () {
        /*
         * ⛔ "Falhou" mandaria a pessoa publicar de novo sem entender o que
         * houve. O post subiu; foi a rede que tirou.
         */
        $destino = destinoPublicado(Plataforma::Bluesky);

        Http::fake([
            '*createSession*' => Http::response(['accessJwt' => 'j', 'did' => 'did:x']),
            '*' => Http::response(['error' => 'RecordNotFound'], 400),
        ]);

        $this->artisan('publicacoes:reconferir')->assertSuccessful();

        expect($destino->fresh()->erro_mensagem)->toContain('não está mais');
    });

    it('⛔ instabilidade da rede NÃO derruba o post', function () {
        /*
         * ⚠️ Rebaixar por causa de um 500 seria mentir na direcao oposta —
         * dizer que saiu do ar o que continua la.
         */
        $destino = destinoPublicado(Plataforma::Bluesky);

        Http::fake([
            '*createSession*' => Http::response(['accessJwt' => 'j', 'did' => 'did:x']),
            '*' => Http::response([], 500),
        ]);

        $this->artisan('publicacoes:reconferir')->assertSuccessful();

        expect($destino->fresh()->status)->toBe(StatusDestino::Publicado);
    });

    it('⭐ post que continua no ar ganha a DATA da conferência', function () {
        /*
         * ⚠️ "No ar" sem data e afirmacao sem prazo — e afirmacao sem prazo
         * envelhece em silencio. Com a data, a tela pode dizer "conferido hoje".
         */
        $destino = destinoPublicado(Plataforma::Bluesky);

        Http::fake([
            '*createSession*' => Http::response(['accessJwt' => 'j', 'did' => 'did:x']),
            '*' => Http::response(['uri' => 'at://x', 'value' => ['text' => 'oi']]),
        ]);

        $this->artisan('publicacoes:reconferir')->assertSuccessful();

        expect($destino->fresh()->reconferido_em)->not->toBeNull()
            ->and($destino->fresh()->status)->toBe(StatusDestino::Publicado);
    });
});

describe('⛔ a reconferência é barata de propósito', function () {
    it('não toca em post fora da janela de dias', function () {
        /*
         * ⚠️ Reler o acervo inteiro todo dia seria caro em cota — e no X e caro
         * em dinheiro: cada releitura custa US$ 0,001 (DEC-127).
         */
        $antigo = destinoPublicado(Plataforma::Bluesky);
        $antigo->forceFill(['publicado_em' => now()->subDays(60)])->save();

        Http::fake();

        $this->artisan('publicacoes:reconferir', ['--dias' => 30])->assertSuccessful();

        expect($antigo->fresh()->status)->toBe(StatusDestino::Publicado);
        Http::assertNothingSent();
    });

    it('respeita o teto por passada', function () {
        Http::fake([
            '*createSession*' => Http::response(['accessJwt' => 'j', 'did' => 'did:x']),
            '*' => Http::response(['uri' => 'at://x', 'value' => []]),
        ]);

        $primeiro = destinoPublicado(Plataforma::Bluesky);
        $segundo = destinoPublicado(Plataforma::Bluesky);
        // ⚠️ O comando pega os MAIS ANTIGOS primeiro: e o que esta prestes a
        // sair da janela, entao e a ultima chance de conferir.
        $primeiro->forceFill(['publicado_em' => now()->subDays(20)])->save();

        $this->artisan('publicacoes:reconferir', ['--limite' => 1])->assertSuccessful();

        // ⭐ Um conferido, um intocado — o teto valeu.
        expect($primeiro->fresh()->reconferido_em)->not->toBeNull()
            ->and($segundo->fresh()->reconferido_em)->toBeNull();
    });

    it('⛔ não confere o que ainda não foi publicado', function () {
        $destino = destinoPublicado(Plataforma::Bluesky);
        $destino->forceFill(['status' => StatusDestino::Processando])->save();

        Http::fake();

        $this->artisan('publicacoes:reconferir')->assertSuccessful();

        Http::assertNothingSent();
    });
});

/** Um destino já publicado, dentro da janela. */
function destinoPublicado(Plataforma $rede): Destino
{
    $dono = cliente();
    ContextoDoUsuario::definir($dono);

    $midia = Midia::factory()->doUsuario($dono)->create();
    $publicacao = Publicacao::factory()->doUsuario($dono)->enviada()->create(['midia_id' => $midia->id]);

    $conta = ContaSocial::factory()->doUsuario($dono)->doGrupo(Grupo::firstOrFail())
        ->daPlataforma($rede)->comCredencial('token')->create();

    $destino = Destino::factory()->create([
        'publicacao_id' => $publicacao->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Publicado,
        'identificador_externo' => 'post-1',
        'handle_externo' => 'post-1',
        'url_publicada' => 'https://exemplo.test/post',
        'publicado_em' => now()->subDay(),
    ]);

    ContextoDoUsuario::limpar();

    return $destino;
}

describe('⛔ reconferir NUNCA pode republicar', function () {
    it('⭐ em toda rede, conciliar um post JÁ publicado não cria nada', function () {
        /*
         * ⛔ Em sete dos onze publicadores, `conciliar()` **publica** — é lá que
         * o post nasce (Threads, LinkedIn, X, Pinterest, Mastodon…). E a
         * reconferência chama exatamente esse método, agora em destinos que já
         * estão no ar.
         *
         * ⚠️ Hoje nenhum republica — mas por motivos DIFERENTES: uns têm guarda
         * explícita no identificador, outros são leitura de ponta a ponta. Isso
         * é segurança acidental, e segurança acidental some numa refatoração.
         *
         * ⛔ Este teste torna a garantia explícita: nenhuma chamada de CRIAÇÃO
         * pode sair. Rede nova que publique na conciliação sem guarda quebra
         * aqui — que é onde tem que quebrar.
         */
        /*
         * ⚠️ **Caminho E método, os dois.** Ler um post do X é
         * `GET /2/tweets/{id}` e criar é `POST /2/tweets` — o caminho sozinho
         * não distingue, e olhar só para ele reprovaria a leitura, que é
         * justamente o que queremos que aconteça.
         */
        $criacoes = [
            '/threads_publish',             // Threads
            '/rest/posts',                  // LinkedIn
            '/2/tweets',                    // X
            '/v5/pins',                     // Pinterest
            '/api/v1/statuses',             // Mastodon
            '/media_publish',               // Instagram
            'createRecord',                 // Bluesky
            '/post/publish/video/init',     // TikTok
        ];

        foreach ([
            Plataforma::Threads, Plataforma::Linkedin, Plataforma::X,
            Plataforma::Pinterest, Plataforma::Mastodon, Plataforma::Instagram,
            Plataforma::Bluesky, Plataforma::Tiktok,
        ] as $rede) {
            $destino = destinoPublicado($rede);

            // A rede responde qualquer coisa: o que importa é o que NÓS mandamos.
            Http::fake(['*' => Http::response(['id' => 'x', 'data' => ['status' => 'PUBLISH_COMPLETE']])]);

            $this->artisan('publicacoes:reconferir')->assertSuccessful();

            foreach ($criacoes as $caminho) {
                Http::assertNotSent(
                    fn ($r) => $r->method() !== 'GET' && str_contains($r->url(), $caminho),
                    "Reconferir {$rede->rotulo()} disparou uma criação em {$caminho}."
                );
            }
        }
    });
});

describe('⛔ o estado novo não pode travar a publicação (DEC-148)', function () {
    it('⭐ publicação com um destino removido NÃO volta para "Publicando…"', function () {
        /*
         * ⛔ Defeito real encontrado na revisão: `deduzirStatus` contava só
         * `Publicado` e `Falhou`. Um destino `Removido` não era nem um nem
         * outro, a soma nunca fechava, e a publicação inteira ficava em
         * **"Publicando…" para sempre** — espera eterna por algo terminado.
         */
        $destino = destinoPublicado(Plataforma::Bluesky);

        Http::fake([
            '*createSession*' => Http::response(['accessJwt' => 'j', 'did' => 'did:x']),
            '*' => Http::response(['error' => 'RecordNotFound'], 400),
        ]);

        $this->artisan('publicacoes:reconferir')->assertSuccessful();

        $publicacao = $destino->fresh()->publicacao;

        expect($publicacao->status)->not->toBe(StatusPublicacao::Processando)
            // ⭐ Todos os destinos saíram do ar: a publicação falhou como um todo.
            ->and($publicacao->status)->toBe(StatusPublicacao::Falhou);
    });

    it('⭐ e com um no ar e outro removido, é "publicada com falhas"', function () {
        // ⚠️ No nivel da PUBLICACAO, "nao subiu" e "subiu e saiu" sao a mesma
        // coisa: algo precisa de atencao. A diferenca mora no destino.
        $removido = destinoPublicado(Plataforma::Bluesky);
        $removido->forceFill(['status' => StatusDestino::Removido])->save();

        /*
         * ⚠️ Ler a publicacao JA exige contexto — o escopo de dono lanca em vez
         * de filtrar em silencio. Por isso o dono e buscado sem escopo, e so
         * depois vira contexto.
         */
        $dono = ContextoDoUsuario::semEscopo(fn () => $removido->publicacao->usuario);
        ContextoDoUsuario::definir($dono);

        // ⚠️ Outra CONTA: o destino é único por publicação + conta.
        $outraConta = ContaSocial::factory()
            ->doUsuario($dono)
            ->doGrupo(Grupo::firstOrFail())
            ->daPlataforma(Plataforma::Youtube)->comCredencial('t')->create();

        $noAr = Destino::factory()->create([
            'publicacao_id' => $removido->publicacao_id,
            'conta_social_id' => $outraConta->id,
            'status' => StatusDestino::Publicado,
            'url_publicada' => 'https://exemplo.test/outro',
            'publicado_em' => now(),
        ]);

        ContextoDoUsuario::definir($removido->publicacao->usuario_id);
        app(PublicacaoService::class)->recalcularStatus($removido->publicacao_id);

        expect($removido->fresh()->publicacao->status)
            ->toBe(StatusPublicacao::ConcluidaComFalhas)
            ->and($noAr->fresh()->status)->toBe(StatusDestino::Publicado);
    });
});

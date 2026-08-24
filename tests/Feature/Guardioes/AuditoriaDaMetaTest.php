<?php

use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Grupo;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Publicadores\PublicadorFacebook;
use App\Publicadores\PublicadorInstagram;
use App\Publicadores\Retomada;
use App\Services\PublicacaoService;
use App\Support\ContextoDoUsuario;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/*
| Guardioes da AUDITORIA DA META contra a documentacao oficial (2026-08-11).
|
| ⛔ Tres achados de campo, todos SILENCIOSOS — nenhum aparecia como erro:
|   1. metrica de REEL pedida com nome de metrica de VIDEO (DEC-157);
|   2. retomada do Instagram desligada por engano (DEC-158);
|   3. autorizacao morta sem acender o semaforo (DEC-159).
*/

beforeEach(function () {
    ContextoDoUsuario::limpar();
    Storage::fake('local');
});

afterEach(fn () => ContextoDoUsuario::limpar());

/** Uma conta da rede pedida, com credencial. */
function contaMeta(Plataforma $rede): ContaSocial
{
    $dono = cliente();
    ContextoDoUsuario::definir($dono);

    return ContaSocial::factory()->doUsuario($dono)->doGrupo(Grupo::firstOrFail())
        ->daPlataforma($rede)->comCredencial('token-vivo')
        ->create(['identificador_externo' => '999']);
}

/** Um destino pronto para publicar naquela conta. */
function destinoMeta(ContaSocial $conta): Destino
{
    $midia = Midia::factory()->doUsuario($conta->usuario)->create();
    $publicacao = Publicacao::factory()->doUsuario($conta->usuario)->enviada()
        ->create(['midia_id' => $midia->id]);

    return Destino::factory()->create([
        'publicacao_id' => $publicacao->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Enviando,
    ]);
}

describe('⛔ a métrica do REEL não é a métrica do VÍDEO (DEC-157)', function () {
    it('⛔ NUNCA pede `total_video_views` — ela não existe para reel', function () {
        /*
         * ⚠️ A referencia do `video_insights` separa a lista em duas: *Video
         * metrics*, onde `total_video_views` mora, e *Reels metrics*, com
         * outros nomes. Publicamos por `/video_reels` — tudo que publicamos e
         * reel — e a chamada voltava sem o numero, sempre, calada.
         *
         * ⛔ O efeito era o pior possivel para este produto: "sem leitura" no
         * Facebook PARA SEMPRE, o que parece rede que nao respondeu.
         */
        $conta = contaMeta(Plataforma::Facebook);
        $destino = destinoMeta($conta);
        $destino->forceFill(['identificador_externo' => 'v1', 'status' => StatusDestino::Publicado])->save();

        Http::fake([
            '*video_insights*' => Http::response(['data' => [
                ['name' => 'blue_reels_play_count', 'values' => [['value' => 900]]],
            ]]),
            '*' => Http::response(['likes' => ['summary' => ['total_count' => 3]]]),
        ]);

        $metricas = app(PublicadorFacebook::class)->metricasDoPost($destino->fresh(['contaSocial.credencial']));

        expect($metricas?->visualizacoes)->toBe(900);

        Http::assertSent(fn ($r) => ! str_contains($r->url(), 'total_video_views'));
    });

    it('⭐ e cai para `fb_reels_total_plays` quando a primeira não vem', function () {
        // ⚠️ Ordem NOSSA, nao a da resposta: qual delas significa "visualizacao"
        // e decisao de produto, e a que conta reprise e a segunda escolha.
        $conta = contaMeta(Plataforma::Facebook);
        $destino = destinoMeta($conta);
        $destino->forceFill(['identificador_externo' => 'v1', 'status' => StatusDestino::Publicado])->save();

        Http::fake([
            '*video_insights*' => Http::response(['data' => [
                ['name' => 'fb_reels_total_plays', 'values' => [['value' => 120]]],
            ]]),
            '*' => Http::response([]),
        ]);

        expect(app(PublicadorFacebook::class)->metricasDoPost($destino->fresh(['contaSocial.credencial']))?->visualizacoes)
            ->toBe(120);
    });
});

describe('⭐ o Instagram TAMBÉM sabe retomar (DEC-158)', function () {
    it('⭐ pergunta quanto já subiu em `video_status`, e continua de lá', function () {
        /*
         * ⛔ Isto devolvia `0` sempre, com um comentario dizendo que o Instagram
         * nao documentava retomada — e documenta, no guia de *resumable
         * uploads*, no campo `video_status`. O preco do engano era um video
         * inteiro reenviado a cada tropeco de rede.
         */
        $envio = App\Services\Meta\EnvioRetomavel::paraInstagram();

        Http::fake(['*' => Http::response([
            'video_status' => ['uploading_phase' => ['bytes_transferred' => 50002]],
        ])]);

        expect($envio->jaRecebidos('token', 'container-1'))->toBe(50002);

        // ⚠️ E pergunta pelo campo CERTO: `status` derrubaria a chamada com o
        // erro 100, porque o container do Instagram não tem esse campo.
        Http::assertSent(fn ($r) => str_contains($r->url(), 'fields=video_status'));
    });

    it('⛔ lê as DUAS grafias — a Meta escreve o mesmo campo de dois jeitos', function () {
        // ⚠️ `bytes_transfered` com um `r` no exemplo do Facebook,
        // `bytes_transferred` no do Instagram. Ler so uma devolve 0 em silencio
        // — e 0 aqui nao parece defeito, parece envio novo.
        Http::fake(['*' => Http::response([
            'status' => ['uploading_phase' => ['bytes_transfered' => 777]],
        ])]);

        expect(App\Services\Meta\EnvioRetomavel::paraFacebook()->jaRecebidos('token', 'v1'))->toBe(777);
    });
});

describe('⛔ autorização morta ACENDE o semáforo (DEC-159)', function () {
    it('⛔ conta não fica «Conectada» depois que a Meta diz que o token morreu', function () {
        /*
         * ⛔ O semaforo do token e o diferencial declarado do produto (DEC-32).
         * Sem isto, a conta ficava VERDE na tela enquanto recusava toda
         * publicacao — e o token de Pagina nao vence por tempo, o que fez este
         * caso passar despercebido. Ele nao vence, mas morre.
         */
        $conta = contaMeta(Plataforma::Facebook);
        $destino = destinoMeta($conta);

        Http::fake(['*' => Http::response([
            'error' => ['code' => 190, 'error_subcode' => 460, 'message' => 'Password changed'],
        ], 400)]);

        $resultado = app(PublicadorFacebook::class)->publicar(
            $destino->fresh(['contaSocial.credencial', 'publicacao.midia']),
            new Retomada($destino, app(PublicacaoService::class)),
        );

        expect($resultado->aceito)->toBeFalse()
            // ⚠️ NUNCA "tentar depois": nenhuma tentativa conserta token revogado.
            ->and($resultado->transitorio)->toBeFalse()
            ->and($conta->fresh()->status)->toBe(StatusConta::Erro);
    });

    it('⛔ e no Instagram é a conta DELE que fica vermelha, não a da Página', function () {
        /*
         * ⚠️ O Instagram publica com o token DA PAGINA. Quando ele morre, quem
         * fica em pe e o Instagram — e e a conta dele que precisa ficar
         * vermelha, senao a pessoa reconecta o Facebook e acha que resolveu.
         */
        $conta = contaMeta(Plataforma::Instagram);
        $destino = destinoMeta($conta);

        Http::fake(['*' => Http::response([
            'error' => ['code' => 190, 'message' => 'Invalid OAuth access token'],
        ], 400)]);

        app(PublicadorInstagram::class)->publicar(
            $destino->fresh(['contaSocial.credencial', 'publicacao.midia']),
            new Retomada($destino, app(PublicacaoService::class)),
        );

        expect($conta->fresh()->status)->toBe(StatusConta::Erro);
    });

    it('⭐ mas erro comum NÃO derruba a conta — só a publicação', function () {
        // ⛔ O contraponto: sem ele, a correcao acima transformaria qualquer
        // tropeco em "reconecte sua conta", que e o alarme falso mais caro que
        // existe neste produto.
        $conta = contaMeta(Plataforma::Facebook);
        $destino = destinoMeta($conta);

        Http::fake(['*' => Http::response([
            'error' => ['code' => 100, 'error_subcode' => 2207026, 'message' => 'Bad format'],
        ], 400)]);

        app(PublicadorFacebook::class)->publicar(
            $destino->fresh(['contaSocial.credencial', 'publicacao.midia']),
            new Retomada($destino, app(PublicacaoService::class)),
        );

        expect($conta->fresh()->status)->toBe(StatusConta::Ativa);
    });
});

describe('⛔ `business_management` e a lista de Páginas vazia (DEC-164)', function () {
    it('⛔ o escopo pedido inclui `business_management`', function () {
        /*
         * ⛔ **Sem ela, `/me/accounts` volta VAZIO** para quem usa o Meta
         * Business Suite — e volta `200`, sem erro e sem aviso. A Meta tornou
         * isso obrigatorio na v19 (janeiro de 2024) para Pagina que pertence a
         * portfolio empresarial.
         *
         * ⚠️ E basta a pessoa vincular um Instagram pelo Business Suite para a
         * Pagina dela virar isso. Foi exatamente o que aconteceu em campo: a
         * conexao funcionou dia 10, a Pagina virou de negocio dia 11, e nunca
         * mais veio Pagina nenhuma.
         */
        config(['services.meta.config_id' => null]);

        expect(app(App\Services\Meta\ConexaoComMeta::class)->enderecoDeAutorizacao('e'))
            ->toContain('business_management');
    });

    it('⛔ e sem ela a mensagem NÃO manda a pessoa marcar a Página', function () {
        /*
         * ⛔ Mandar "marque a Pagina" para quem nao tem como marca-la e o pior
         * conselho possivel: ela refaz a autorizacao em laco, procurando um
         * passo que a Meta nao vai oferecer. Custou um dia inteiro.
         */
        config(['services.meta.client_id' => 'app', 'services.meta.client_secret' => 'segredo']);

        Http::fake([
            '*/me/permissions*' => Http::response(['data' => [
                ['permission' => 'pages_show_list', 'status' => 'granted'],
                ['permission' => 'pages_manage_posts', 'status' => 'granted'],
                ['permission' => 'instagram_content_publish', 'status' => 'granted'],
            ]]),
            '*/me/accounts*' => Http::response(['data' => []]),
            '*/debug_token*' => Http::response(['data' => ['granular_scopes' => []]]),
            '*/oauth/access_token*' => Http::response(['access_token' => 'token']),
        ]);

        try {
            app(App\Services\Meta\ConexaoComMeta::class)->conectar('codigo');
            $this->fail('deveria ter recusado');
        } catch (Illuminate\Validation\ValidationException $e) {
            $mensagem = $e->errors()['meta'][0];

            expect($mensagem)->toContain('portfólio empresarial')
                ->and($mensagem)->not->toContain('marque a Página');
        }
    });
});

<?php

use App\Enums\Plataforma;
use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Grupo;
use App\Models\LeituraDeMetrica;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Publicadores\PublicadorFacebook;
use App\Publicadores\PublicadorInstagram;
use App\Publicadores\PublicadorTiktok;
use App\Support\ContextoDoUsuario;
use Illuminate\Support\Facades\Http;

/*
| Guardiao das METRICAS DO ESCOPO (plano 32, DEC-143..147).
|
| ⛔ Tres coisas moram aqui, e as tres ja custaram decisao:
|   1. metrica custa PERMISSAO nova, e recusar nao pode apagar a tela inteira;
|   2. o numero passou a ter ONTEM;
|   3. `null` nunca vira zero.
*/

beforeEach(fn () => ContextoDoUsuario::limpar());
afterEach(fn () => ContextoDoUsuario::limpar());

describe('⛔ recusar a permissão de métrica não apaga a tela (DEC-143)', function () {
    it('⭐ Instagram sem `instagram_manage_insights` ainda mostra curtida e comentário', function () {
        /*
         * ⛔ Curtida e comentario vem no objeto da midia e NAO custam permissao;
         * visualizacao e compartilhamento so existem em `insights`.
         *
         * ⚠️ Se as duas chamadas fossem uma so, quem recusasse a permissao
         * ficaria sem NUMERO NENHUM — e a tela inteira apareceria vazia por
         * causa de uma permissao opcional.
         */
        $destino = destinoComMetrica(Plataforma::Instagram, 'midia-1');

        Http::fake([
            'graph.facebook.com/*/insights*' => Http::response(['error' => ['message' => 'permission']], 403),
            'graph.facebook.com/*' => Http::response(['like_count' => 12, 'comments_count' => 3]),
        ]);

        $metricas = app(PublicadorInstagram::class)->metricasDoPost($destino);

        expect($metricas->curtidas)->toBe(12)
            ->and($metricas->comentarios)->toBe(3)
            // ⛔ `null`, NUNCA zero: nao lemos, e isso nao e "teve zero".
            ->and($metricas->visualizacoes)->toBeNull()
            ->and($metricas->compartilhamentos)->toBeNull();
    });

    it('⭐ e o mesmo vale no Facebook', function () {
        $destino = destinoComMetrica(Plataforma::Facebook, 'video-1');

        Http::fake([
            'graph.facebook.com/*/video_insights*' => Http::response([], 403),
            'graph.facebook.com/*' => Http::response([
                'likes' => ['summary' => ['total_count' => 7]],
                'comments' => ['summary' => ['total_count' => 2]],
            ]),
        ]);

        $metricas = app(PublicadorFacebook::class)->metricasDoPost($destino);

        expect($metricas->curtidas)->toBe(7)
            ->and($metricas->comentarios)->toBe(2)
            ->and($metricas->visualizacoes)->toBeNull();
    });

    it('⛔ rede fora do ar devolve `null` INTEIRO — que é diferente de tudo vazio', function () {
        /*
         * ⚠️ `null` no lugar do objeto quer dizer "nao deu para ler agora";
         * objeto com campos nulos quer dizer "li, e esta rede nao publica isso".
         * O comando diario so registra leitura no segundo caso.
         */
        $destino = destinoComMetrica(Plataforma::Instagram, 'midia-1');

        Http::fake(['graph.facebook.com/*' => Http::response([], 500)]);

        expect(app(PublicadorInstagram::class)->metricasDoPost($destino))->toBeNull();
    });
});

describe('⭐ o TikTok lê pelo id do POST, não pelo do envio', function () {
    it('usa o identificador que só chega depois da moderação (DEC-115)', function () {
        /*
         * ⚠️ O `publicaly_available_post_id` so aparece quando a moderacao
         * aprova. Ler pelo id do ENVIO devolveria vazio para sempre.
         */
        $destino = destinoComMetrica(Plataforma::Tiktok, '7300000000000000000');
        $destino->contaSocial->credencial->forceFill(['expira_em' => now()->addHours(20)])->save();

        Http::fake(['open.tiktokapis.com/v2/video/query/*' => Http::response([
            'data' => ['videos' => [[
                'id' => '7300000000000000000',
                'view_count' => 1000, 'like_count' => 50, 'comment_count' => 4, 'share_count' => 9,
            ]]],
            'error' => ['code' => 'ok'],
        ])]);

        $metricas = app(PublicadorTiktok::class)->metricasDoPost($destino->fresh(['contaSocial.credencial']));

        expect($metricas->visualizacoes)->toBe(1000)
            ->and($metricas->compartilhamentos)->toBe(9);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'video/query')
            && $r['filters']['video_ids'] === ['7300000000000000000']);
    });

    it('⛔ erro dentro do HTTP 200 é erro, não leitura (DEC-121)', function () {
        $destino = destinoComMetrica(Plataforma::Tiktok, '7300000000000000000');
        $destino->contaSocial->credencial->forceFill(['expira_em' => now()->addHours(20)])->save();

        Http::fake(['open.tiktokapis.com/v2/video/query/*' => Http::response([
            'error' => ['code' => 'scope_not_authorized'],
        ], 200)]);

        expect(app(PublicadorTiktok::class)->metricasDoPost($destino->fresh(['contaSocial.credencial'])))
            ->toBeNull();
    });
});

describe('⭐ o número passa a ter ontem (DEC-144)', function () {
    it('a leitura do dia é gravada além do "agora"', function () {
        $destino = destinoComMetrica(Plataforma::Instagram, 'midia-1');

        Http::fake([
            'graph.facebook.com/*/insights*' => Http::response(['data' => [
                ['name' => 'views', 'values' => [['value' => 500]]],
                ['name' => 'shares', 'values' => [['value' => 8]]],
            ]]),
            'graph.facebook.com/*' => Http::response(['like_count' => 30, 'comments_count' => 5]),
        ]);

        $this->artisan('metricas:atualizar')->assertSuccessful();

        $linha = LeituraDeMetrica::where('destino_id', $destino->id)->first();

        expect($linha)->not->toBeNull()
            ->and($linha->visualizacoes)->toBe(500)
            ->and($linha->curtidas)->toBe(30)
            // ⭐ E o "agora" continua no destino, para a tela nao varrer historico.
            ->and($destino->fresh()->visualizacoes)->toBe(500);
    });

    it('⛔ rodar duas vezes no mesmo dia ATUALIZA a linha, não cria um segundo ponto', function () {
        /*
         * ⚠️ A serie e diaria. Duas leituras no mesmo dia virariam dois pontos
         * no grafico — e um deles seria mentira sobre o dia seguinte.
         */
        $destino = destinoComMetrica(Plataforma::Instagram, 'midia-1');

        Http::fake([
            'graph.facebook.com/*/insights*' => Http::response(['data' => [
                ['name' => 'views', 'values' => [['value' => 100]]],
            ]]),
            'graph.facebook.com/*' => Http::response(['like_count' => 1, 'comments_count' => 0]),
        ]);

        $this->artisan('metricas:atualizar')->assertSuccessful();
        $this->artisan('metricas:atualizar')->assertSuccessful();

        expect(LeituraDeMetrica::where('destino_id', $destino->id)->count())->toBe(1);
    });
});

/** Um destino publicado, pronto para ter métrica lida. */
function destinoComMetrica(Plataforma $rede, string $identificador): Destino
{
    $dono = cliente();
    ContextoDoUsuario::definir($dono);

    $midia = Midia::factory()->doUsuario($dono)->create();
    $publicacao = Publicacao::factory()->doUsuario($dono)->enviada()->create(['midia_id' => $midia->id]);

    $conta = ContaSocial::factory()->doUsuario($dono)->doGrupo(Grupo::firstOrFail())
        ->daPlataforma($rede)->comCredencial('token')->create();

    return Destino::factory()->create([
        'publicacao_id' => $publicacao->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Publicado,
        'identificador_externo' => $identificador,
        'url_publicada' => 'https://exemplo.test/post',
        'publicado_em' => now()->subDay(),
    ]);
}

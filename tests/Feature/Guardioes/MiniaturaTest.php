<?php

use App\Enums\TipoMidia;
use App\Models\Midia;
use App\Services\GeradorDeMiniatura;
use App\Services\MidiaService;
use App\Support\ContextoDoUsuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
| A miniatura — o que faz o historico ser reconhecivel.
|
| Sem ela, tres videos do WhatsApp da mesma tarde viram tres linhas identicas,
| e publicar o errado nao tem desfazer.
*/

beforeEach(fn () => ContextoDoUsuario::limpar());
afterEach(fn () => ContextoDoUsuario::limpar());

it('⛔ envio NÃO falha quando não dá para gerar a miniatura', function () {
    // ⚠️ A trava mais importante desta fase. Mídia sem miniatura é aceitável;
    // envio recusado por causa de uma imagem de apoio, não. Num servidor sem
    // ffmpeg, a pessoa continua publicando.
    Storage::fake('local');

    $this->mock(GeradorDeMiniatura::class)
        ->shouldReceive('gerar')->andReturn(null);

    ContextoDoUsuario::definir(cliente());

    $midia = app(MidiaService::class)->guardar(
        UploadedFile::fake()->create('corte.mp4', 64, 'video/mp4'),
        TipoMidia::Video,
    );

    expect($midia->exists)->toBeTrue()
        ->and($midia->miniatura)->toBeNull();
});

it('não gera miniatura de imagem — ela já é a própria', function () {
    Storage::fake('local');

    $this->mock(GeradorDeMiniatura::class)->shouldNotReceive('gerar');

    ContextoDoUsuario::definir(cliente());

    app(MidiaService::class)->guardar(
        UploadedFile::fake()->create('foto.jpg', 32, 'image/jpeg'),
        TipoMidia::Imagem,
    );
});

it('⚠️ a miniatura fica na pasta do dono, como o vídeo', function () {
    Storage::fake('local');

    $this->mock(GeradorDeMiniatura::class)
        ->shouldReceive('gerar')
        ->andReturnUsing(fn (string $caminho) => dirname($caminho).'/mini.jpg');

    $dono = cliente();
    ContextoDoUsuario::definir($dono);

    $midia = app(MidiaService::class)->guardar(
        UploadedFile::fake()->create('corte.mp4', 64, 'video/mp4'),
        TipoMidia::Video,
    );

    // O isolamento por cliente (DEC-50) vale para a miniatura igual.
    expect($midia->miniatura)->toContain($dono->ulid);
});

it('⚠️ apagar a mídia leva a miniatura junto', function () {
    Storage::fake('local');
    $dono = cliente();
    ContextoDoUsuario::definir($dono);

    $midia = Midia::factory()->doUsuario($dono)->create([
        'caminho' => 'midias/x/video.mp4',
        'miniatura' => 'midias/x/mini.jpg',
    ]);

    Storage::disk('local')->put('midias/x/video.mp4', 'v');
    Storage::disk('local')->put('midias/x/mini.jpg', 'i');

    app(MidiaService::class)->remover($midia);

    // Esquecer a miniatura deixaria um arquivo sem dono no disco: invisível na
    // tela e fora do alcance de qualquer limpeza.
    Storage::disk('local')->assertMissing('midias/x/video.mp4');
    Storage::disk('local')->assertMissing('midias/x/mini.jpg');
});

it('⛔ um cliente não baixa a miniatura de outro', function () {
    $ana = cliente();
    $doBruno = Midia::factory()->doUsuario(cliente())->create(['miniatura' => 'midias/y/mini.jpg']);

    // Miniatura também é conteúdo do cliente — o escopo vale igual ao do vídeo.
    $this->actingAs($ana)
        ->get(route('midias.miniatura', $doBruno->ulid))
        ->assertNotFound();
});

it('responde 404 para mídia sem miniatura, em vez de imagem quebrada', function () {
    $dono = cliente();
    $midia = Midia::factory()->doUsuario($dono)->create(['miniatura' => null]);

    $this->actingAs($dono)
        ->get(route('midias.miniatura', $midia->ulid))
        ->assertNotFound();
});

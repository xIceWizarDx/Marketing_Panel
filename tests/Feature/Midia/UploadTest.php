<?php

use App\Models\Midia;
use App\Services\InspetorDeMidia;
use App\Support\ContextoDoUsuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    ContextoDoUsuario::limpar();
    Storage::fake('local');
});

afterEach(fn () => ContextoDoUsuario::limpar());

/** Vídeo de verdade — o `mimetypes` lê o conteúdo, então arquivo falso não passa. */
function videoReal(string $nome = 'meu-corte.mp4'): UploadedFile
{
    return new UploadedFile(base_path('tests/Fixtures/vertical-ok.mp4'), $nome, 'video/mp4', null, true);
}

it('guarda o arquivo e registra a mídia', function () {
    $ana = cliente();

    $this->actingAs($ana)
        ->post('/midias', ['tipo' => 'video', 'arquivo' => videoReal()])
        ->assertRedirect(route('publicar'))
        ->assertSessionHasNoErrors();

    $midia = ContextoDoUsuario::semEscopo(fn () => Midia::firstOrFail());

    expect($midia->usuario_id)->toBe($ana->id)
        ->and($midia->nome_original)->toBe('meu-corte.mp4')
        ->and($midia->mime_type)->toBe('video/mp4')
        ->and($midia->largura)->toBe(1080)
        ->and($midia->altura)->toBe(1920);

    Storage::disk('local')->assertExists($midia->caminho);
})->skip(fn () => ! app(InspetorDeMidia::class)->disponivel(), 'ffprobe não instalado');

it('nunca usa o nome enviado como caminho no disco', function () {
    $this->actingAs(cliente())
        ->post('/midias', ['tipo' => 'video', 'arquivo' => videoReal('../../../invadir.mp4')]);

    $midia = ContextoDoUsuario::semEscopo(fn () => Midia::firstOrFail());

    // O nome vira só texto de exibição; o caminho é gerado por nós.
    expect($midia->caminho)->not->toContain('..')
        ->not->toContain('invadir')
        ->and($midia->caminho)->toStartWith('midias/');
});

it('recusa arquivo cujo conteúdo não é o que a extensão diz', function () {
    // `.mp4` por fora, texto por dentro — a checagem por extensão deixaria passar.
    $falso = UploadedFile::fake()->createWithContent('disfarcado.mp4', 'isto aqui é texto puro');

    $this->actingAs(cliente())
        ->from(route('publicar'))
        ->post('/midias', ['tipo' => 'video', 'arquivo' => $falso])
        ->assertSessionHasErrors('arquivo');

    expect(ContextoDoUsuario::semEscopo(fn () => Midia::count()))->toBe(0);
});

it('recusa imagem quando o tipo escolhido é vídeo', function () {
    $this->actingAs(cliente())
        ->from(route('publicar'))
        ->post('/midias', ['tipo' => 'video', 'arquivo' => UploadedFile::fake()->image('foto.jpg')])
        ->assertSessionHasErrors('arquivo');
});

it('recusa arquivo acima do limite', function () {
    $enorme = UploadedFile::fake()->create('gigante.mp4', (config('midia.tamanho_maximo_mb') + 1) * 1024, 'video/mp4');

    $this->actingAs(cliente())
        ->from(route('publicar'))
        ->post('/midias', ['tipo' => 'video', 'arquivo' => $enorme])
        ->assertSessionHasErrors('arquivo');
});

it('guarda o arquivo fora da raiz pública', function () {
    $this->actingAs(cliente())->post('/midias', ['tipo' => 'video', 'arquivo' => videoReal()]);

    $midia = ContextoDoUsuario::semEscopo(fn () => Midia::firstOrFail());

    // O disco `local` é storage/app/private — nenhuma URL alcança direto.
    expect(config('filesystems.disks.local.root'))->toContain('private')
        ->and(file_exists(public_path($midia->caminho)))->toBeFalse();
});

it('não deixa um cliente baixar o arquivo do outro', function () {
    $this->actingAs(cliente())->post('/midias', ['tipo' => 'video', 'arquivo' => videoReal()]);
    $doOutro = ContextoDoUsuario::semEscopo(fn () => Midia::firstOrFail());

    // 404 e não 403: 403 confirmaria que aquele identificador existe.
    $this->actingAs(cliente())
        ->get("/midias/{$doOutro->ulid}/arquivo")
        ->assertNotFound();
});

it('deixa o dono baixar o próprio arquivo', function () {
    $ana = cliente();
    $this->actingAs($ana)->post('/midias', ['tipo' => 'video', 'arquivo' => videoReal()]);
    $midia = ContextoDoUsuario::semEscopo(fn () => Midia::firstOrFail());

    $this->actingAs($ana)
        ->get("/midias/{$midia->ulid}/arquivo")
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('barra o admin nas rotas de mídia do cliente', function () {
    $this->actingAs(admin())->get(route('publicar'))->assertNotFound();
});

it('barra o visitante', function () {
    $this->get(route('publicar'))->assertRedirect(route('entrar'));
});

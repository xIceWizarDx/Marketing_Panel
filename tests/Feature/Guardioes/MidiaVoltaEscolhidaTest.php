<?php

use App\Enums\TipoMidia;
use App\Models\Midia;
use App\Support\ContextoDoUsuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
| Guardiao do CONTRATO entre o envio e o compositor.
|
| ⛔ Este arquivo nasceu de um defeito de campo: o video subia inteiro, chegava
| a 100%, a barra sumia — e nada aparecia na tela. O arquivo estava salvo no
| banco o tempo todo.
|
| ⚠️ A causa era de FORMATO, nao de logica: o servidor manda `midiaEnviada`
| como OBJETO, e a tela lia como se fosse o ULID em texto. O objeto inteiro ia
| parar onde so cabe o ULID, a comparacao nunca batia, e o arquivo ficava
| invisivel.
|
| ⭐ Por isso o guardiao mira a FORMA do que sai do servidor: se ela mudar de
| novo, quebra aqui — e nao na mao de quem esta publicando.
*/

beforeEach(function () {
    ContextoDoUsuario::limpar();
    Storage::fake('local');
});

afterEach(fn () => ContextoDoUsuario::limpar());

/** Um MP4 vertical de verdade — a validação lê o conteúdo, não o nome. */
function videoDeVerdade(): UploadedFile
{
    return new UploadedFile(base_path('tests/Fixtures/vertical-ok.mp4'), 'corte.mp4', 'video/mp4', null, true);
}

describe('⛔ o arquivo enviado volta ESCOLHIDO', function () {
    it('⭐ `midiaEnviada` é um objeto com `ulid` — nunca o ULID solto', function () {
        $dono = cliente();

        $this->actingAs($dono)
            ->post(route('midias.salvar'), [
                'tipo' => TipoMidia::Video->value,
                // ⚠️ Vídeo de verdade: a regra `mimetypes` lê o CONTEÚDO, então
                // arquivo de mentira com nome `.mp4` é recusado antes de salvar.
                'arquivo' => videoDeVerdade(),
            ])
            ->assertRedirect();

        ContextoDoUsuario::definir($dono);
        $midia = Midia::query()->firstOrFail();
        ContextoDoUsuario::limpar();

        /*
         * ⚠️ `where` no campo de dentro é o ponto do teste: `has('midiaEnviada')`
         * passaria com o ULID em texto — que é exatamente o que estava quebrado.
         */
        $this->actingAs($dono)
            ->get(route('publicar'))
            ->assertInertia(fn ($p) => $p->where('compositor.midiaEnviada.ulid', $midia->ulid));
    });

    it('⛔ sem envio, `midiaEnviada` é nulo — o compositor não tem acervo', function () {
        /*
         * ⛔ DEC-60: o único arquivo que aparece é o que acabou de subir, nesta
         * mesma composição. Buscar "a última mídia do usuário" transformaria o
         * compositor num acervo pela porta dos fundos.
         */
        $this->actingAs(cliente())
            ->get(route('publicar'))
            ->assertInertia(fn ($p) => $p->where('compositor.midiaEnviada', null));
    });

    it('⛔ recusa de arquivo vem com mensagem no campo `arquivo`', function () {
        /*
         * ⚠️ A tela mostra `erros.arquivo`. Se a recusa mudasse de campo, ela
         * voltaria a sumir em silêncio — que é o pior desfecho possível: quem
         * enviou conclui que o produto comeu o vídeo.
         */
        $this->actingAs(cliente())
            ->post(route('midias.salvar'), [
                'tipo' => TipoMidia::Video->value,
                'arquivo' => UploadedFile::fake()->createWithContent('planilha.mp4', 'isto-nao-e-video'),
            ])
            ->assertSessionHasErrors('arquivo');
    });
});

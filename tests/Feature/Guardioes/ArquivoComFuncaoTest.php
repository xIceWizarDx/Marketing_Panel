<?php

use App\Enums\StatusDestino;
use App\Enums\TipoMidia;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Services\EnvioDePublicacao;
use App\Services\MidiaService;
use App\Support\ContextoDoUsuario;
use App\Support\Midia\LiberacaoDeArquivo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/*
| ⭐ O ARQUIVO VIVE ENQUANTO TEM FUNCAO (DEC-59).
|
| O produto nao guarda acervo. O video existe enquanto tem servico a fazer;
| cumprido o servico, sai NA HORA — sem carencia, sem prazo. Fica o registro:
| miniatura, laudo, links e prova.
|
| ⛔ A trava mais importante deste arquivo: destino PENDENTE segura o video,
| sempre. Liberar por tempo quebraria todo agendamento.
*/

beforeEach(function () {
    ContextoDoUsuario::limpar();
    Storage::fake('local');
});

afterEach(fn () => ContextoDoUsuario::limpar());

function midiaEnviada($dono, string $conteudo = 'conteudo-do-video'): Midia
{
    ContextoDoUsuario::definir($dono);

    return app(MidiaService::class)->guardar(
        UploadedFile::fake()->createWithContent('corte.mp4', $conteudo),
        TipoMidia::Video,
    );
}

/** Um destino no estado pedido, apontando para a mídia. */
function destinoDe(Midia $midia, $dono, StatusDestino $status): Destino
{
    $conta = ContaSocial::factory()->doUsuario($dono)->comCredencial()->create();
    $publicacao = Publicacao::factory()->doUsuario($dono)->enviada()->create(['midia_id' => $midia->id]);

    return Destino::factory()->create([
        'publicacao_id' => $publicacao->id,
        'conta_social_id' => $conta->id,
        'status' => $status,
        'url_publicada' => $status === StatusDestino::Publicado ? 'https://bsky.app/p/x' : null,
    ]);
}

describe('a assinatura do conteúdo', function () {
    it('⭐ reencontra o mesmo vídeo mesmo renomeado', function () {
        $dono = cliente();
        ContextoDoUsuario::definir($dono);

        $servico = app(MidiaService::class);
        $bytes = 'exatamente-o-mesmo-conteudo';

        $primeira = $servico->guardar(UploadedFile::fake()->createWithContent('original.mp4', $bytes), TipoMidia::Video);
        $segunda = $servico->guardar(UploadedFile::fake()->createWithContent('renomeado.mp4', $bytes), TipoMidia::Video);

        // Mesmo registro: duplicar criaria duas verdades sobre o mesmo arquivo.
        expect($segunda->id)->toBe($primeira->id)
            ->and(Midia::count())->toBe(1);
    });

    it('conteúdo diferente é mídia diferente', function () {
        ContextoDoUsuario::definir(cliente());

        $servico = app(MidiaService::class);
        $servico->guardar(UploadedFile::fake()->createWithContent('a.mp4', 'video-um'), TipoMidia::Video);
        $servico->guardar(UploadedFile::fake()->createWithContent('b.mp4', 'video-dois'), TipoMidia::Video);

        expect(Midia::count())->toBe(2);
    });

    it('⛔ arquivo idêntico de OUTRO cliente é outro registro', function () {
        // Reaproveitar cruzaria dado entre contas — a única coisa que este
        // projeto não pode errar.
        $bytes = 'mesmo-arquivo-dois-clientes';

        $daAna = midiaEnviada(cliente(), $bytes);
        ContextoDoUsuario::limpar();
        $doBruno = midiaEnviada(cliente(), $bytes);

        expect($doBruno->id)->not->toBe($daAna->id)
            ->and($doBruno->usuario_id)->not->toBe($daAna->usuario_id);
    });
});

describe('a liberação do arquivo', function () {
    it('⛔ destino PENDENTE segura o arquivo, por mais velho que seja', function () {
        // A trava que impede quebrar agendamento: uma publicação marcada para
        // daqui a um mês precisa do vídeo daqui a um mês.
        $dono = cliente();
        $midia = midiaEnviada($dono);
        destinoDe($midia, $dono, StatusDestino::Pendente);

        Midia::withoutGlobalScopes()->whereKey($midia->id)->update(['created_at' => now()->subYear()]);
        ContextoDoUsuario::limpar();

        $this->artisan('midias:liberar', ['--dias' => 0])->assertSuccessful();

        expect(Midia::withoutGlobalScopes()->find($midia->id)->temArquivo())->toBeTrue();
        Storage::disk('local')->assertExists($midia->caminho);
    });

    it('libera quando todos os destinos terminaram', function () {
        $dono = cliente();
        $midia = midiaEnviada($dono);
        destinoDe($midia, $dono, StatusDestino::Publicado);
        ContextoDoUsuario::limpar();

        $this->artisan('midias:liberar', ['--dias' => 0])->assertSuccessful();

        expect(Midia::withoutGlobalScopes()->find($midia->id)->temArquivo())->toBeFalse();
        Storage::disk('local')->assertMissing($midia->caminho);
    });

    it('⭐ a MINIATURA fica — é ela que mantém o histórico reconhecível', function () {
        $dono = cliente();
        $midia = midiaEnviada($dono);

        Midia::withoutGlobalScopes()->whereKey($midia->id)->update(['miniatura' => 'midias/x/mini.jpg']);
        Storage::disk('local')->put('midias/x/mini.jpg', 'imagem');

        destinoDe($midia, $dono, StatusDestino::Publicado);
        ContextoDoUsuario::limpar();

        $this->artisan('midias:liberar', ['--dias' => 0]);

        Storage::disk('local')->assertExists('midias/x/mini.jpg');
    });

    it('⛔ o REGISTRO nunca é apagado — só o arquivo pesado', function () {
        $dono = cliente();
        $midia = midiaEnviada($dono);
        destinoDe($midia, $dono, StatusDestino::Publicado);
        ContextoDoUsuario::limpar();

        $this->artisan('midias:liberar', ['--dias' => 0]);

        // O produto nunca foi depósito: o que fica é histórico — laudo, links
        // e prova continuam.
        expect(Midia::withoutGlobalScopes()->find($midia->id))->not->toBeNull();
    });

    it('libera mídia que nunca foi publicada', function () {
        // Arquivo que ninguém usou é custo sem contrapartida nenhuma.
        $midia = midiaEnviada(cliente());
        ContextoDoUsuario::limpar();

        $this->artisan('midias:liberar', ['--dias' => 0]);

        expect(Midia::withoutGlobalScopes()->find($midia->id)->temArquivo())->toBeFalse();
    });

    it('simular não apaga nada', function () {
        $midia = midiaEnviada(cliente());
        ContextoDoUsuario::limpar();

        $this->artisan('midias:liberar', ['--dias' => 0, '--simular' => true]);

        Storage::disk('local')->assertExists($midia->caminho);
        expect(Midia::withoutGlobalScopes()->find($midia->id)->temArquivo())->toBeTrue();
    });
});

describe('o reenvio depois da liberação', function () {
    it('⭐ devolve o arquivo ao MESMO registro, com o histórico', function () {
        $dono = cliente();
        $bytes = 'video-que-vai-e-volta';

        $midia = midiaEnviada($dono, $bytes);
        destinoDe($midia, $dono, StatusDestino::Publicado);
        ContextoDoUsuario::limpar();

        $this->artisan('midias:liberar', ['--dias' => 0]);

        ContextoDoUsuario::definir($dono);

        $devolvida = app(MidiaService::class)->guardar(
            UploadedFile::fake()->createWithContent('mesmo-video.mp4', $bytes),
            TipoMidia::Video,
        );

        expect($devolvida->id)->toBe($midia->id)
            ->and($devolvida->temArquivo())->toBeTrue()
            // O histórico continua: a publicação anterior segue apontando aqui.
            ->and(Publicacao::where('midia_id', $midia->id)->count())->toBe(1);

        Storage::disk('local')->assertExists($midia->caminho);
    });
});

describe('a trava do servidor', function () {
    it('⛔ recusa publicar mídia cujo arquivo já saiu', function () {
        // A tela desabilita por conveniência; o serviço recusa por regra. Sem
        // esta trava, um pedido montado à mão publicaria o nada.
        $dono = cliente();
        $midia = midiaEnviada($dono);
        ContextoDoUsuario::limpar();

        $this->artisan('midias:liberar', ['--dias' => 0]);

        ContextoDoUsuario::definir($dono);
        $conta = ContaSocial::factory()->doUsuario($dono)->comCredencial()->create();

        expect(fn () => app(EnvioDePublicacao::class)->enviar(
            midiaUlid: $midia->fresh()->ulid,
            contasUlid: [$conta->ulid],
            titulo: 'teste',
        ))->toThrow(ValidationException::class);
    });
});

describe('a regra mora num lugar só', function () {
    it('⛔ rascunho segura o arquivo', function () {
        // Publicação criada e ainda não enviada continua esperando o vídeo.
        // Ela não tem destino nenhum, então a conta ingênua a trataria como
        // "nunca publicada" e liberaria o arquivo por baixo dela.
        $dono = cliente();
        $midia = midiaEnviada($dono);

        Publicacao::factory()->doUsuario($dono)->create([
            'midia_id' => $midia->id,
            'enviada_em' => null,
        ]);

        ContextoDoUsuario::limpar();

        $this->artisan('midias:liberar', ['--dias' => 0]);

        expect(Midia::withoutGlobalScopes()->find($midia->id)->temArquivo())->toBeTrue();
        Storage::disk('local')->assertExists($midia->caminho);
    });

    it('⭐ publicação concluída libera NA HORA, sem somar dias', function () {
        // A conta antiga somava dias de carência aqui. Esperar guardaria um
        // vídeo que já não tem função nenhuma.
        $dono = cliente();
        $midia = midiaEnviada($dono);
        $destino = destinoDe($midia, $dono, StatusDestino::Publicado);

        ContextoDoUsuario::limpar();

        expect(LiberacaoDeArquivo::em($midia->fresh()))
            ->not->toBeNull()
            ->and(LiberacaoDeArquivo::em($midia->fresh())->timestamp)
            ->toBe($destino->fresh()->updated_at->timestamp);
    });
});

describe('⛔ o compositor não sugere nada (DEC-60)', function () {
    it('não manda lista de mídias nenhuma para a tela', function () {
        // A faixa de vídeos anteriores era um acervo pequeno com outro nome:
        // sugeria arquivo guardado justamente onde a promessa é não guardar.
        $dono = cliente();
        midiaEnviada($dono);
        ContextoDoUsuario::limpar();

        $this->actingAs($dono)
            ->get(route('publicar'))
            ->assertInertia(fn ($p) => $p->missing('compositor.midias')
                ->where('compositor.midiaEnviada', null));
    });

    it('⭐ o arquivo aparece só depois de enviado, nesta mesma composição', function () {
        $dono = cliente();
        ContextoDoUsuario::limpar();

        $this->actingAs($dono)
            ->post(route('midias.salvar'), [
                'tipo' => 'video',
                'arquivo' => UploadedFile::fake()->create('corte.mp4', 64, 'video/mp4'),
            ])
            ->assertSessionHas('midiaEnviada');

        $this->actingAs($dono)
            ->get(route('publicar'))
            ->assertInertia(fn ($p) => $p->where(
                'compositor.midiaEnviada.ulid',
                Midia::withoutGlobalScopes()->latest('id')->first()->ulid,
            ));
    });

    it('recarregar a tela volta a mostrar só a área de envio', function () {
        // O arquivo vem por flash: sem ele, nada é sugerido — nem o que a
        // própria pessoa enviou minutos antes.
        $dono = cliente();
        midiaEnviada($dono);
        ContextoDoUsuario::limpar();

        $this->actingAs($dono)
            ->get(route('publicar'))
            ->assertInertia(fn ($p) => $p->where('compositor.midiaEnviada', null));
    });
});

describe('⭐ republicar leva o TEXTO, não o arquivo (DEC-61)', function () {
    it('traz título, legenda e hashtags — e nenhuma mídia', function () {
        $dono = cliente();
        $midia = midiaEnviada($dono);
        $conta = ContaSocial::factory()->doUsuario($dono)->comCredencial()->create();

        $anterior = Publicacao::factory()->doUsuario($dono)->enviada()->create([
            'midia_id' => $midia->id,
            'titulo' => 'Corte da live',
            'legenda' => 'o melhor momento',
            'hashtags' => ['corte', 'shorts'],
        ]);

        Destino::factory()->create([
            'publicacao_id' => $anterior->id,
            'conta_social_id' => $conta->id,
            'status' => StatusDestino::Publicado,
            'url_publicada' => 'https://bsky.app/p/x',
        ]);

        ContextoDoUsuario::limpar();

        $this->actingAs($dono)
            ->get(route('publicar.de-novo', $anterior->ulid))
            ->assertInertia(fn ($p) => $p
                ->where('compositor.inicial.titulo', 'Corte da live')
                ->where('compositor.inicial.legenda', 'o melhor momento')
                ->where('compositor.inicial.hashtags', ['corte', 'shorts'])
                // ⚠️ Desmarcada, só listada: marcar sozinho repetiria o post.
                ->where('compositor.inicial.jaPublicadoEm', [$conta->ulid])
                // ⛔ O vídeo NÃO vem junto — ele já saiu.
                ->missing('compositor.inicial.midia'));
    });

    it('⭐ o botão continua valendo depois de o arquivo sair', function () {
        // Antes ele sumia junto com o vídeo, e a pessoa perdia o texto que
        // tinha escrito à mão — sendo que reenviar o arquivo resolve.
        $dono = cliente();
        $midia = midiaEnviada($dono);
        destinoDe($midia, $dono, StatusDestino::Publicado);
        ContextoDoUsuario::limpar();

        $this->artisan('midias:liberar', ['--dias' => 0]);

        $this->actingAs($dono)
            ->get(route('publicacoes'))
            ->assertInertia(fn ($p) => $p->where('publicacoes.data.0.podeRepublicar', true));
    });
});

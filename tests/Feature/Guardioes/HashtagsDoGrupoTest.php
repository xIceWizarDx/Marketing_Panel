<?php

use App\Models\Grupo;
use App\Models\Publicacao;
use App\Services\GrupoService;
use App\Support\ContextoDoUsuario;
use App\Support\GrupoCorrente;

/*
| Guardiao das HASHTAGS QUE JA VEM ESCRITAS (DEC-152).
|
| ⭐ Elas moram no GRUPO porque e ele que separa linhas de conteudo (DEC-69):
| quem tem um canal de noticias e um de novelas escreve `#noticias` cem vezes
| num, e nunca no outro.
|
| ⛔ E sao PONTO DE PARTIDA, nunca carimbo: o texto continua editavel, o que
| sobe e o que estiver escrito na hora de publicar, e o que ja foi publicado
| nao e tocado.
*/

beforeEach(function () {
    ContextoDoUsuario::limpar();
    GrupoCorrente::limpar();
});

afterEach(function () {
    ContextoDoUsuario::limpar();
    GrupoCorrente::limpar();
});

describe('⭐ o grupo guarda as hashtags', function () {
    it('⭐ guarda limpo — sem `#` e sem espaço', function () {
        /*
         * ⚠️ Guardar com `#` obrigaria cada rede a desfazer isso na hora de
         * publicar — e a que esquecesse mandaria `##noticias`.
         */
        $dono = cliente();
        ContextoDoUsuario::definir($dono);
        $grupo = Grupo::firstOrFail();
        ContextoDoUsuario::limpar();

        $this->actingAs($dono)
            ->patch(route('grupos.hashtags', $grupo->ulid), ['hashtags' => ['#noticias', 'jornalismo']])
            ->assertRedirect();

        ContextoDoUsuario::definir($dono);
        expect(Grupo::firstOrFail()->hashtags)->toBe(['noticias', 'jornalismo']);
    });

    it('⛔ lista vazia vira `null` — e não uma lista vazia', function () {
        /*
         * ⚠️ `[]` e `null` significando a mesma coisa e como nasce o `if` que
         * esquece um dos dois.
         */
        $dono = cliente();
        ContextoDoUsuario::definir($dono);
        Grupo::firstOrFail()->forceFill(['hashtags' => ['noticias']])->save();
        $grupo = Grupo::firstOrFail();
        ContextoDoUsuario::limpar();

        $this->actingAs($dono)
            ->patch(route('grupos.hashtags', $grupo->ulid), ['hashtags' => []])
            ->assertRedirect();

        ContextoDoUsuario::definir($dono);
        expect(Grupo::firstOrFail()->hashtags)->toBeNull();
    });

    it('⛔ recusa aqui o que o publicar recusaria', function () {
        /*
         * ⛔ Aceitar no grupo uma hashtag que o compositor recusa criaria a pior
         * sequencia possivel: salva, o painel diz que deu certo, e o erro so
         * aparece na hora de publicar — num campo que a pessoa nem escreveu.
         */
        $dono = cliente();
        ContextoDoUsuario::definir($dono);
        $grupo = Grupo::firstOrFail();
        ContextoDoUsuario::limpar();

        $this->actingAs($dono)
            ->patch(route('grupos.hashtags', $grupo->ulid), ['hashtags' => ['tem espaço']])
            ->assertSessionHasErrors('hashtags.0');
    });

    it('⛔ NUNCA muda o grupo de outro dono', function () {
        $meu = cliente();
        ContextoDoUsuario::definir($meu);
        $meuGrupo = Grupo::firstOrFail();
        ContextoDoUsuario::limpar();

        $alheio = cliente();
        ContextoDoUsuario::definir($alheio);
        $grupoAlheio = Grupo::firstOrFail();
        ContextoDoUsuario::limpar();

        // ⚠️ 404 e nao 403: confirmar que aquele ULID existe ja e informacao.
        $this->actingAs($meu)
            ->patch(route('grupos.hashtags', $grupoAlheio->ulid), ['hashtags' => ['invadida']])
            ->assertNotFound();

        ContextoDoUsuario::definir($alheio);
        expect(Grupo::firstOrFail()->hashtags)->toBeNull();
        ContextoDoUsuario::limpar();

        ContextoDoUsuario::definir($meu);
        expect(Grupo::whereKey($meuGrupo->id)->firstOrFail()->hashtags)->toBeNull();
    });
});

describe('⭐ e elas chegam ao compositor', function () {
    it('⭐ post novo já nasce com as hashtags deste grupo', function () {
        $dono = cliente();
        ContextoDoUsuario::definir($dono);
        Grupo::firstOrFail()->forceFill(['hashtags' => ['noticias']])->save();
        ContextoDoUsuario::limpar();

        $this->actingAs($dono)
            ->get(route('publicar'))
            ->assertInertia(fn ($p) => $p->where('compositor.hashtagsPadrao', ['noticias']));
    });

    it('⛔ cada grupo traz as SUAS — trocar de grupo troca o começo do texto', function () {
        /*
         * ⛔ E o ponto inteiro da decisao: `#noticias` no grupo de novelas seria
         * o mesmo acidente que o grupo existe para evitar (DEC-71), so que no
         * texto em vez de no destino.
         */
        $dono = cliente();
        ContextoDoUsuario::definir($dono);

        Grupo::firstOrFail()->forceFill(['hashtags' => ['noticias']])->save();
        $novelas = app(GrupoService::class)->criar('Novelas');
        $novelas->forceFill(['hashtags' => ['novela']])->save();
        ContextoDoUsuario::limpar();

        $this->actingAs($dono)->post(route('grupos.usar', $novelas->ulid));

        $this->actingAs($dono)
            ->get(route('publicar'))
            ->assertInertia(fn ($p) => $p->where('compositor.hashtagsPadrao', ['novela']));
    });

    it('⛔ ao REPUBLICAR vale o texto do post anterior, não o do grupo', function () {
        /*
         * ⚠️ Quem clicou em republicar veio reaproveitar aquele texto (DEC-61).
         * Trocar as hashtags dele pelas do grupo seria reescrever a intencao de
         * alguem no meio do gesto.
         */
        $dono = cliente();
        ContextoDoUsuario::definir($dono);

        Grupo::firstOrFail()->forceFill(['hashtags' => ['doGrupo']])->save();

        $publicacao = Publicacao::factory()->doUsuario($dono)->enviada()->create([
            'titulo' => 'Corte antigo',
            'legenda' => 'legenda antiga',
            'hashtags' => ['doPostAnterior'],
        ]);
        ContextoDoUsuario::limpar();

        $this->actingAs($dono)
            ->get(route('publicar.de-novo', $publicacao->ulid))
            ->assertInertia(fn ($p) => $p->where('compositor.inicial.hashtags', ['doPostAnterior']));
    });
});

/*
| ⭐ E o caminho da janela do grupo ate a janela da REDE (DEC-154).
|
| ⛔ Desconectar e mover moram na janela da rede, e devem morar num lugar so:
| e acao sem volta, e duas portas para ela e como nasce o "desconectei e
| continuou aparecendo". Entao a janela do grupo nao repete o gesto — ela leva
| ate ele.
*/
describe('⭐ da janela do grupo até a janela da rede (DEC-154)', function () {
    it('⭐ clicar na rede troca o grupo e manda abrir a janela dela', function () {
        $dono = cliente();
        ContextoDoUsuario::definir($dono);
        $grupo = Grupo::firstOrFail();
        ContextoDoUsuario::limpar();

        $this->actingAs($dono)
            ->post(route('grupos.usar', $grupo->ulid), ['rede' => 'facebook'])
            ->assertRedirect(route('painel'))
            ->assertSessionHas('abrirRede', 'facebook');
    });

    it('⛔ rede inventada não vira recado — modal que nunca abre parece defeito', function () {
        $dono = cliente();
        ContextoDoUsuario::definir($dono);
        $grupo = Grupo::firstOrFail();
        ContextoDoUsuario::limpar();

        $this->actingAs($dono)
            ->post(route('grupos.usar', $grupo->ulid), ['rede' => 'orkut'])
            ->assertSessionMissing('abrirRede');
    });

    it('⛔ e `conectar` continua tendo precedência — são intenções diferentes', function () {
        $dono = cliente();
        ContextoDoUsuario::definir($dono);
        $grupo = Grupo::firstOrFail();
        ContextoDoUsuario::limpar();

        $this->actingAs($dono)
            ->post(route('grupos.usar', $grupo->ulid), ['conectar' => true])
            ->assertSessionHas('abrirCatalogo', true)
            ->assertSessionMissing('abrirRede');
    });
});

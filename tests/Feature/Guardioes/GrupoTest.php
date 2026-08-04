<?php

use App\Enums\StatusConta;
use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Grupo;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Services\ContaSocialService;
use App\Services\EnvioDePublicacao;
use App\Services\GrupoService;
use App\Support\ContextoDoUsuario;
use App\Support\GrupoCorrente;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/*
| ⭐ O GRUPO — a rede de canais de uma linha de conteudo (DEC-69).
|
| Quem produz noticias e novelas tem dois trios de canais. Sem grupo, o
| compositor mostra os seis juntos e a unica coisa que separa um do outro e a
| atencao da pessoa na hora de marcar a caixinha — e publicacao nao desfaz.
|
| ⛔ A trava que sustenta tudo: o grupo de uma publicacao vem das CONTAS
| escolhidas, e o servidor recusa contas de grupos diferentes (DEC-73). A sessao
| so decide o que a tela mostra.
*/

beforeEach(function () {
    ContextoDoUsuario::limpar();
    GrupoCorrente::limpar();
    Storage::fake('local');
    Queue::fake();
});

afterEach(function () {
    ContextoDoUsuario::limpar();
    GrupoCorrente::limpar();
});

/** Um grupo novo do mesmo dono, com uma conta dentro. */
function grupoCom($dono, string $nome): Grupo
{
    ContextoDoUsuario::definir($dono);

    $grupo = app(GrupoService::class)->criar($nome);
    ContaSocial::factory()->doGrupo($grupo)->comCredencial()->create();

    ContextoDoUsuario::limpar();

    return $grupo;
}

describe('o nascimento', function () {
    it('⭐ todo usuário nasce com um grupo, pelas três portas', function () {
        // Cadastro, seeder e factory: o gatilho mora no model justamente porque
        // espalhar por três lugares garantiria esquecer um — e o esquecido
        // viraria um cliente sem grupo, que é beco sem saída silencioso.
        $ana = cliente();

        expect(Grupo::withoutGlobalScopes()->where('usuario_id', $ana->id)->count())->toBe(1);
    });

    it('não cria um segundo grupo para quem renomeou o primeiro', function () {
        // A conta antiga procurava pelo NOME: quem renomeasse "Principal" para
        // "Notícias" ganharia um grupo do nada na próxima chamada.
        $ana = cliente();
        ContextoDoUsuario::definir($ana);

        app(GrupoService::class)->renomear(Grupo::firstOrFail(), 'Notícias');
        app(GrupoService::class)->garantirPrincipal($ana);

        expect(Grupo::count())->toBe(1)
            ->and(Grupo::firstOrFail()->nome)->toBe('Notícias');
    });

    it('⛔ grupo de um cliente não aparece para outro', function () {
        $ana = cliente();
        ContextoDoUsuario::definir($ana);
        app(GrupoService::class)->criar('Novelas');
        ContextoDoUsuario::limpar();

        $bruno = cliente();

        $this->actingAs($bruno)
            ->get(route('painel'))
            ->assertInertia(fn ($p) => $p->has('grupos.lista', 1));
    });
});

describe('⛔ a trava do envio (DEC-73)', function () {
    it('⭐ recusa contas de grupos diferentes na mesma publicação', function () {
        // A trava que torna o acidente impossível, não só improvável. Ela
        // sobrevive a aba velha, POST montado à mão e defeito de interface.
        $ana = cliente();
        $noticias = grupoCom($ana, 'Notícias');
        $novelas = grupoCom($ana, 'Novelas');

        ContextoDoUsuario::definir($ana);
        // ⚠️ Midia da factory de proposito. Com arquivo falso o envio seria
        // recusado por FORMATO, e o guardiao passaria provando outra coisa.
        $midia = Midia::factory()->doUsuario($ana)->create();

        expect(fn () => app(EnvioDePublicacao::class)->enviar(
            midiaUlid: $midia->ulid,
            contasUlid: [
                $noticias->contasSociais()->first()->ulid,
                $novelas->contasSociais()->first()->ulid,
            ],
            titulo: 'teste',
        ))->toThrow(ValidationException::class);

        expect(Publicacao::count())->toBe(0);
    });

    it('⛔ recusa quando uma das contas pedidas não é do dono', function () {
        // Antes ela sumia EM SILÊNCIO: a pessoa escolhia 4 contas e recebia
        // "enviamos para 3". Filtrar calado é a implementação errada da DEC-73.
        $ana = cliente();
        $daAna = grupoCom($ana, 'Notícias');

        $bruno = cliente();
        $doBruno = grupoCom($bruno, 'Do Bruno');

        ContextoDoUsuario::definir($ana);
        // ⚠️ Midia da factory de proposito. Com arquivo falso o envio seria
        // recusado por FORMATO, e o guardiao passaria provando outra coisa.
        $midia = Midia::factory()->doUsuario($ana)->create();

        expect(fn () => app(EnvioDePublicacao::class)->enviar(
            midiaUlid: $midia->ulid,
            contasUlid: [
                $daAna->contasSociais()->first()->ulid,
                $doBruno->contasSociais()->withoutGlobalScopes()->first()->ulid,
            ],
            titulo: 'teste',
        ))->toThrow(ValidationException::class);
    });

    it('grava o grupo derivado das contas', function () {
        $ana = cliente();
        $noticias = grupoCom($ana, 'Notícias');

        ContextoDoUsuario::definir($ana);
        // ⚠️ Midia da factory: arquivo falso nao passa no laudo de formato, e a
        // recusa viria pelo motivo errado — o teste passaria mentindo.
        $midia = Midia::factory()->doUsuario($ana)->create();

        $publicacao = app(EnvioDePublicacao::class)->enviar(
            midiaUlid: $midia->ulid,
            contasUlid: [$noticias->contasSociais()->first()->ulid],
            titulo: 'teste',
        );

        expect($publicacao->grupo_id)->toBe($noticias->id);
    });
});

describe('⭐ o histórico não se mexe (DEC-75)', function () {
    it('mover o canal de grupo NÃO muda onde a publicação saiu', function () {
        // Se o número seguisse o canal, o total de Notícias mudaria sozinho no
        // dia em que alguém reorganizasse os canais — e número que muda
        // retroativamente não serve para decidir nada.
        $ana = cliente();
        $noticias = grupoCom($ana, 'Notícias');
        $novelas = grupoCom($ana, 'Novelas');

        ContextoDoUsuario::definir($ana);
        $conta = $noticias->contasSociais()->first();
        $midia = Midia::factory()->doUsuario($ana)->create();

        $publicacao = app(EnvioDePublicacao::class)->enviar(
            midiaUlid: $midia->ulid,
            contasUlid: [$conta->ulid],
            titulo: 'teste',
        );

        app(GrupoService::class)->moverCanal($conta, $novelas);

        expect($publicacao->fresh()->grupo_id)->toBe($noticias->id)
            ->and($conta->fresh()->grupo_id)->toBe($novelas->id);
    });
});

describe('⛔ arquivar (DEC-76)', function () {
    it('não arquiva grupo que ainda tem canal', function () {
        $ana = cliente();
        $noticias = grupoCom($ana, 'Notícias');

        ContextoDoUsuario::definir($ana);

        expect(fn () => app(GrupoService::class)->arquivar($noticias))
            ->toThrow(ValidationException::class);
    });

    it('⚠️ canal DESCONECTADO também segura', function () {
        // A linha da conta desconectada sobrevive porque o histórico aponta
        // para ela; arquivar por baixo deixaria esse histórico pendurado num
        // grupo que ninguém mais enxerga.
        $ana = cliente();
        $noticias = grupoCom($ana, 'Notícias');

        ContextoDoUsuario::definir($ana);
        app(ContaSocialService::class)->desconectar($noticias->contasSociais()->first());

        expect(fn () => app(GrupoService::class)->arquivar($noticias))
            ->toThrow(ValidationException::class);
    });

    it('não arquiva o ÚLTIMO grupo', function () {
        $ana = cliente();
        ContextoDoUsuario::definir($ana);

        expect(fn () => app(GrupoService::class)->arquivar(Grupo::firstOrFail()))
            ->toThrow(ValidationException::class);
    });

    it('arquiva grupo vazio quando há outro', function () {
        $ana = cliente();
        ContextoDoUsuario::definir($ana);

        $vazio = app(GrupoService::class)->criar('Vazio');
        app(GrupoService::class)->arquivar($vazio);

        expect(Grupo::count())->toBe(1)
            ->and(Grupo::withTrashed()->count())->toBe(2);
    });

    it('⭐ grupo arquivado nunca volta a ser eleito o principal', function () {
        // `withoutGlobalScopes()` derrubaria o escopo de dono E o de arquivado
        // de uma vez, e o grupo que a pessoa arquivou voltaria por baixo.
        $ana = cliente();
        ContextoDoUsuario::definir($ana);

        $primeiro = Grupo::firstOrFail();
        app(GrupoService::class)->criar('Novelas');
        app(GrupoService::class)->arquivar($primeiro);

        expect(app(GrupoService::class)->garantirPrincipal($ana)->nome)->toBe('Novelas');
    });
});

describe('as telas leem por grupo', function () {
    it('⭐ a lista de publicações mostra só o grupo em foco', function () {
        $ana = cliente();
        $noticias = grupoCom($ana, 'Notícias');
        $novelas = grupoCom($ana, 'Novelas');

        ContextoDoUsuario::definir($ana);
        Publicacao::factory()->doUsuario($ana)->enviada()->create(['grupo_id' => $noticias->id]);
        Publicacao::factory()->doUsuario($ana)->enviada()->create(['grupo_id' => $novelas->id]);
        ContextoDoUsuario::limpar();

        $this->actingAs($ana)
            ->withSession(['grupo.corrente' => $noticias->ulid])
            ->get(route('publicacoes'))
            ->assertInertia(fn ($p) => $p->has('publicacoes.data', 1)
                // ⚠️ A contagem da aba usa o MESMO filtro: lista filtrada com
                // contagem solta faria a aba dizer 2 e a lista mostrar 1.
                ->where('contagem.tudo', 1));
    });

    it('o compositor só oferece os canais do grupo em foco', function () {
        $ana = cliente();
        $noticias = grupoCom($ana, 'Notícias');
        grupoCom($ana, 'Novelas');

        $this->actingAs($ana)
            ->withSession(['grupo.corrente' => $noticias->ulid])
            ->get(route('publicar'))
            ->assertInertia(fn ($p) => $p->has('compositor.contas', 1));
    });

    it('⭐ o aviso de saúde IGNORA o filtro e diz onde o problema está', function () {
        // DEC-80: conta da outra ponta não pode morrer calada só porque a
        // pessoa está olhando outro grupo.
        $ana = cliente();
        $noticias = grupoCom($ana, 'Notícias');
        $novelas = grupoCom($ana, 'Novelas');

        ContextoDoUsuario::definir($ana);
        $quebrada = $novelas->contasSociais()->first();
        $quebrada->forceFill(['status' => StatusConta::Expirada])->save();
        ContextoDoUsuario::limpar();

        $this->actingAs($ana)
            ->withSession(['grupo.corrente' => $noticias->ulid])
            ->get(route('painel'))
            ->assertInertia(fn ($p) => $p->where('pendencias', function ($lista) {
                $aviso = collect($lista)->firstWhere('acao', 'Resolver');

                expect($aviso)->not->toBeNull()
                    ->and($aviso['texto'])->toContain('Novelas');

                return true;
            }));
    });

    it('⛔ sessão apontando para grupo de OUTRO cliente cai no grupo certo', function () {
        $ana = cliente();
        ContextoDoUsuario::definir($ana);
        $daAna = Grupo::firstOrFail();
        ContextoDoUsuario::limpar();

        $bruno = cliente();

        $this->actingAs($bruno)
            ->withSession(['grupo.corrente' => $daAna->ulid])
            ->get(route('painel'))
            ->assertInertia(fn ($p) => $p->where('grupos.atual.ulid', fn ($ulid) => $ulid !== $daAna->ulid));
    });
});

describe('o motor não conhece grupo (DEC-74)', function () {
    it('⭐ o job publica sem grupo nenhum no contexto', function () {
        // Um Global Scope de grupo derrubaria o motor inteiro: worker não tem
        // sessão, e portanto nunca tem grupo corrente.
        $ana = cliente();
        $noticias = grupoCom($ana, 'Notícias');

        ContextoDoUsuario::definir($ana);
        $publicacao = Publicacao::factory()->doUsuario($ana)->enviada()->create(['grupo_id' => $noticias->id]);
        $destino = Destino::factory()->create([
            'publicacao_id' => $publicacao->id,
            'conta_social_id' => $noticias->contasSociais()->first()->id,
            'status' => StatusDestino::Pendente,
        ]);

        ContextoDoUsuario::limpar();
        GrupoCorrente::limpar();

        // Sem sessão e sem grupo: o que o worker enxerga.
        ContextoDoUsuario::definir($ana);

        expect(Destino::withoutGlobalScopes()->find($destino->id))->not->toBeNull();
    });
});

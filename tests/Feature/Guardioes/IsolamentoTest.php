<?php

use App\Enums\TipoMidia;
use App\Exceptions\EscopoDeUsuarioIndefinido;
use App\Models\Midia;
use App\Services\MidiaService;
use App\Support\ContextoDoUsuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
| Guardiao de ISOLAMENTO — o teste mais importante do projeto.
|
| Um cliente nunca alcança o dado do outro. Se este arquivo ficar vermelho, nada
| mais importa: é vazamento de dado entre clientes.
*/

beforeEach(fn () => ContextoDoUsuario::limpar());
afterEach(fn () => ContextoDoUsuario::limpar());

it('mostra ao cliente apenas as próprias mídias', function () {
    $ana = cliente();
    $bruno = cliente();

    Midia::factory()->count(3)->doUsuario($ana)->create();
    Midia::factory()->count(2)->doUsuario($bruno)->create();

    $this->actingAs($ana);
    expect(Midia::count())->toBe(3);

    $this->actingAs($bruno);
    expect(Midia::count())->toBe(2);
});

it('esconde a mídia alheia até quando o id é conhecido', function () {
    $ana = cliente();
    $doBruno = Midia::factory()->doUsuario(cliente())->create();

    $this->actingAs($ana);

    // Nem sabendo o ULID exato — o escopo age antes do `where`.
    expect(Midia::find($doBruno->id))->toBeNull()
        ->and(Midia::where('ulid', $doBruno->ulid)->first())->toBeNull();
});

it('impede apagar e alterar mídia alheia', function () {
    $ana = cliente();
    $doBruno = Midia::factory()->doUsuario(cliente())->create();

    $this->actingAs($ana);

    expect(Midia::whereKey($doBruno->id)->delete())->toBe(0)
        ->and(Midia::whereKey($doBruno->id)->update(['nome_original' => 'invadido']))->toBe(0);

    ContextoDoUsuario::semEscopo(function () use ($doBruno) {
        expect(Midia::find($doBruno->id))->not->toBeNull()
            ->and(Midia::find($doBruno->id)->nome_original)->not->toBe('invadido');
    });
});

it('carimba o dono sozinho ao criar', function () {
    $ana = cliente();
    $this->actingAs($ana);

    $midia = Midia::create([
        'tipo' => 'video',
        'nome_original' => 'teste.mp4',
        'caminho' => 'midias/teste.mp4',
        'mime_type' => 'video/mp4',
        'tamanho_bytes' => 1024,
    ]);

    expect($midia->usuario_id)->toBe($ana->id);
});

/*
|--------------------------------------------------------------------------
| A armadilha do worker da fila
|--------------------------------------------------------------------------
| O worker NÃO tem sessão. Sem tratamento, o escopo viraria
| `WHERE usuario_id IS NULL` — o job rodaria, não acharia nada, não daria erro,
| e ninguém perceberia. `Queue::fake()` esconderia isso em todos os testes,
| porque o job nunca chegaria a executar.
*/

it('recusa consultar dado de cliente sem dono definido', function () {
    Midia::factory()->doUsuario(cliente())->create();

    // Sem sessão e sem contexto: em vez de devolver lista vazia em silêncio,
    // estoura — que é o comportamento que faz o bug aparecer no teste.
    expect(fn () => Midia::count())->toThrow(EscopoDeUsuarioIndefinido::class);
});

it('recusa criar dado de cliente sem dono definido', function () {
    expect(fn () => Midia::create([
        'tipo' => 'video',
        'nome_original' => 'orfa.mp4',
        'caminho' => 'midias/orfa.mp4',
        'mime_type' => 'video/mp4',
        'tamanho_bytes' => 1024,
    ]))->toThrow(EscopoDeUsuarioIndefinido::class);
});

it('deixa um job sem sessão trabalhar declarando o dono', function () {
    $ana = cliente();
    Midia::factory()->count(2)->doUsuario($ana)->create();
    Midia::factory()->count(5)->doUsuario(cliente())->create();

    // É assim que todo job que toca dado de cliente começa.
    ContextoDoUsuario::definir($ana);

    expect(Midia::count())->toBe(2);
});

it('devolve o contexto ao estado anterior mesmo com erro no meio', function () {
    $ana = cliente();
    $this->actingAs($ana);

    try {
        ContextoDoUsuario::semEscopo(function () {
            throw new RuntimeException('falhou no meio');
        });
    } catch (RuntimeException) {
        // esperado
    }

    // Se o `finally` não existisse, a aplicação inteira seguiria sem isolamento
    // pelo resto da requisição — vazamento silencioso.
    expect(ContextoDoUsuario::estaSemEscopo())->toBeFalse();
});

it('enxerga todos os clientes só quando alguém pede de propósito', function () {
    Midia::factory()->count(3)->doUsuario(cliente())->create();
    Midia::factory()->count(4)->doUsuario(cliente())->create();

    expect(ContextoDoUsuario::semEscopo(fn () => Midia::count()))->toBe(7);
});

it('mostra o painel do cliente durante impersonação, não o do admin', function () {
    $ana = cliente();
    $alvo = cliente();

    Midia::factory()->count(2)->doUsuario($alvo)->create();
    Midia::factory()->doUsuario($ana)->create();

    $admin = admin();
    session(['auth.password_confirmed_at' => time()]);
    $this->actingAs($admin)->post("/admin/impersonar/{$alvo->ulid}");

    // Impersonando, o dono efetivo é o cliente — o admin vê o que ele vê.
    expect(Midia::count())->toBe(2);
});

describe('a mídia no disco', function () {
    it('⭐ cada cliente tem a própria pasta — arquivo nunca encosta no de outro', function () {
        // Sem pasta por dono, o isolamento existiria SÓ no banco: um bug de
        // caminho alcançaria o vídeo de outro cliente.
        Storage::fake('local');

        $pastas = [];

        foreach ([cliente(), cliente()] as $dono) {
            ContextoDoUsuario::definir($dono);

            $midia = app(MidiaService::class)->guardar(
                UploadedFile::fake()->create('corte.mp4', 64, 'video/mp4'),
                TipoMidia::Video,
            );

            // ⚠️ ULID, nunca o `id`: o caminho pode vazar num log, e o
            // sequencial entrega quantos clientes existem.
            expect($midia->caminho)->toContain($dono->ulid);

            $pastas[] = $dono->ulid;
            ContextoDoUsuario::limpar();
        }

        expect($pastas[0])->not->toBe($pastas[1]);
    });

    it('recusa guardar mídia sem saber de quem é', function () {
        Storage::fake('local');
        ContextoDoUsuario::limpar();

        // Arquivo em pasta genérica seria órfão: dado pessoal que ninguém
        // enxerga e que a exclusão da conta não alcança (LGPD).
        expect(fn () => app(MidiaService::class)->guardar(
            UploadedFile::fake()->create('corte.mp4', 64, 'video/mp4'),
            TipoMidia::Video,
        ))->toThrow(EscopoDeUsuarioIndefinido::class);
    });
});

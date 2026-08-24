<?php

use App\Http\Controllers\MidiaTemporariaController;
use App\Models\Midia;
use App\Support\ContextoDoUsuario;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/*
| Guardiao da URL TEMPORARIA da midia (DEC-100).
|
| ⭐ Este e o unico endereco do produto que serve arquivo SEM SESSAO — ele existe
| porque o Threads nao aceita envio de arquivo: ele recebe uma URL e vai buscar a
| midia, e quem busca e um servidor da Meta, que nunca tera login aqui.
|
| ⛔ Por isso a assinatura e a trava INTEIRA. Nao ha dono conferido, nao ha
| escopo, nao ha middleware de papel. Se o endereco for adivinhavel, ele e o
| arquivo de qualquer cliente — e estes testes sao o que impede isso de mudar sem
| ninguem perceber.
*/

beforeEach(function () {
    ContextoDoUsuario::limpar();
    Storage::fake('local');
});

afterEach(fn () => ContextoDoUsuario::limpar());

/** Uma midia com arquivo de verdade no disco falso. */
function midiaNoDisco(): Midia
{
    $dono = cliente();
    ContextoDoUsuario::definir($dono);

    $midia = Midia::factory()->doUsuario($dono)->create();
    Storage::disk('local')->put($midia->caminho, 'conteudo-do-video');

    ContextoDoUsuario::limpar();

    return $midia;
}

it('⭐ o endereço assinado entrega o arquivo, mesmo sem ninguém logado', function () {
    // ⚠️ Sem sessao de proposito: e assim que a Meta chega. Se este teste
    // precisar de `actingAs`, a integracao inteira nao funciona.
    $midia = midiaNoDisco();

    $this->get(MidiaTemporariaController::enderecoDe($midia))
        ->assertOk()
        ->assertHeader('Content-Type', 'video/mp4');
});

it('⛔ sem assinatura NENHUMA, o endereço é recusado', function () {
    // Este e o teste mais importante do arquivo: e ele que impede o endereco de
    // virar "arquivo de qualquer cliente para quem souber montar a URL".
    $midia = midiaNoDisco();

    $this->get(route('midias.temporaria', $midia->ulid))->assertForbidden();
});

it('⛔ assinatura VENCIDA é recusada', function () {
    $midia = midiaNoDisco();

    $endereco = URL::temporarySignedRoute('midias.temporaria', now()->addMinutes(15), ['ulid' => $midia->ulid]);

    // O relogio anda: o endereco tem que morrer sozinho, sem ninguem revogar.
    $this->travel(16)->minutes();

    $this->get($endereco)->assertForbidden();
});

it('⛔ assinatura de OUTRO arquivo não serve para este', function () {
    // Trocar o ulid na URL mantendo a assinatura e a tentativa obvia. A
    // assinatura cobre o endereco inteiro, entao ela quebra.
    $meu = midiaNoDisco();
    $alheio = midiaNoDisco();

    $endereco = MidiaTemporariaController::enderecoDe($meu);
    $adulterado = str_replace($meu->ulid, $alheio->ulid, $endereco);

    $this->get($adulterado)->assertForbidden();
});

it('⭐ serve arquivo de QUALQUER dono — quem autoriza é a assinatura, não o escopo', function () {
    /*
     * ⚠️ Isto parece um furo e nao e: nao existe usuario na sessao para o escopo
     * de dono usar, e com ele a consulta LANCARIA excecao — o endereco nunca
     * funcionaria. O que separa um dono do outro aqui e o fato de o endereco ser
     * imprevisivel e curto, nao uma consulta.
     *
     * O teste existe para deixar isso ESCRITO: se alguem "consertar" pondo
     * escopo aqui, a integracao para de funcionar e este teste explica por que.
     */
    $deOutroCliente = midiaNoDisco();

    $this->get(MidiaTemporariaController::enderecoDe($deOutroCliente))->assertOk();
});

it('⛔ não entrega o nome que a pessoa deu ao arquivo', function () {
    // O nome original e dado do cliente, e este endereco e publico por
    // construcao. Os bytes saem; o nome nao.
    $midia = midiaNoDisco();

    $resposta = $this->get(MidiaTemporariaController::enderecoDe($midia));

    expect($resposta->headers->get('Content-Disposition'))->not->toContain($midia->nome_original);
});

it('⛔ manda não guardar cópia em lugar nenhum', function () {
    // Endereco que expira em 15 minutos nao pode deixar copia em intermediario.
    $midia = midiaNoDisco();

    $this->get(MidiaTemporariaController::enderecoDe($midia))
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('⚠️ ulid que não existe devolve 404, e não 403 — 403 confirmaria que existe', function () {
    $endereco = URL::temporarySignedRoute('midias.temporaria', now()->addMinutes(15), ['ulid' => 'nao-existe']);

    $this->get($endereco)->assertNotFound();
});

it('⛔ o endereço NÃO é guardado no banco — ele nasce a cada envio', function () {
    /*
     * URL assinada guardada em banco e URL permanente com outro nome: ela
     * sobrevive ao envio, e o prazo curto deixa de significar alguma coisa.
     */
    $midia = midiaNoDisco();

    $colunas = collect(Schema::getColumnListing('midias'));

    expect($colunas->filter(fn ($c) => str_contains($c, 'url') || str_contains($c, 'assinad')))
        ->toBeEmpty();

    // E duas chamadas seguidas produzem enderecos DIFERENTES (o prazo muda).
    $primeiro = MidiaTemporariaController::enderecoDe($midia);
    $this->travel(1)->minutes();
    $segundo = MidiaTemporariaController::enderecoDe($midia);

    expect($primeiro)->not->toBe($segundo);
});

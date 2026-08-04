<?php

use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Publicacao;
use App\Support\ContextoDoUsuario;

/*
| A porta de entrada.
|
| Antes era um texto fixo dizendo "vamos comecar conectando uma rede", sem
| controller nenhum: do segundo dia em diante, a unica pagina que nao sabia de
| nada.
*/

beforeEach(fn () => ContextoDoUsuario::limpar());
afterEach(fn () => ContextoDoUsuario::limpar());

it('conta os três lados, não só o que deu certo', function () {
    $dono = cliente();
    ContextoDoUsuario::definir($dono);

    $conta = ContaSocial::factory()->doUsuario($dono)->comCredencial()->create();

    foreach ([StatusDestino::Publicado, StatusDestino::Falhou, StatusDestino::Processando] as $status) {
        Destino::factory()->create([
            'publicacao_id' => Publicacao::factory()->doUsuario($dono)->enviada()->create()->id,
            'conta_social_id' => $conta->id,
            'status' => $status,
            'url_publicada' => $status === StatusDestino::Publicado ? 'https://bsky.app/p/x' : null,
        ]);
    }

    ContextoDoUsuario::limpar();

    $this->actingAs($dono)
        ->get('/painel')
        ->assertInertia(fn ($p) => $p
            ->where('numeros.noAr', 1)
            ->where('numeros.falharam', 1)
            ->where('numeros.andando', 1));
});

it('⭐ o bloco "precisa de você" SOME quando não há nada', function () {
    // Um aviso que aparece sempre treina a pessoa a ignorá-lo — e no dia em que
    // houver problema de verdade, ela não vai olhar.
    $dono = cliente();
    ContextoDoUsuario::definir($dono);
    ContaSocial::factory()->doUsuario($dono)->comCredencial()->create();
    ContextoDoUsuario::limpar();

    $this->actingAs($dono)
        ->get('/painel')
        ->assertInertia(fn ($p) => $p->where('pendencias', []));
});

it('avisa quando uma publicação não subiu', function () {
    $dono = cliente();
    ContextoDoUsuario::definir($dono);

    $conta = ContaSocial::factory()->doUsuario($dono)->comCredencial()->create();
    Destino::factory()->create([
        'publicacao_id' => Publicacao::factory()->doUsuario($dono)->enviada()->create()->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Falhou,
    ]);
    ContextoDoUsuario::limpar();

    $this->actingAs($dono)
        ->get('/painel')
        ->assertInertia(fn ($p) => $p->where('pendencias', fn ($lista) => collect($lista)
            ->contains(fn ($x) => str_contains($x['texto'], 'não subiu'))));
});

it('os primeiros passos vão sendo marcados', function () {
    // ⚠️ São DOIS passos agora: enviar deixou de ser etapa própria, porque o
    // arquivo entra dentro do compositor. Manter três descreveria um caminho
    // que não existe mais.
    $dono = cliente();
    ContextoDoUsuario::definir($dono);
    ContaSocial::factory()->doUsuario($dono)->comCredencial()->create();
    ContextoDoUsuario::limpar();

    $this->actingAs($dono)
        ->get('/painel')
        ->assertInertia(fn ($p) => $p
            ->has('primeirosPassos', 2)
            ->where('primeirosPassos.0.feito', true)
            // Ainda não publicou: o passo continua aberto, com o caminho.
            ->where('primeirosPassos.1.feito', false));
});

it('⛔ não soma o número de outro cliente', function () {
    $ana = cliente();
    $bruno = cliente();

    ContextoDoUsuario::definir($bruno);
    $conta = ContaSocial::factory()->doUsuario($bruno)->comCredencial()->create();
    Destino::factory()->create([
        'publicacao_id' => Publicacao::factory()->doUsuario($bruno)->enviada()->create()->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Publicado,
        'url_publicada' => 'https://bsky.app/p/x',
    ]);
    ContextoDoUsuario::limpar();

    $this->actingAs($ana)
        ->get('/painel')
        ->assertInertia(fn ($p) => $p
            ->where('numeros.noAr', 0)
            // ⛔ Publicação não mora aqui (DEC-68): só o número dela.
            ->missing('ultimas'));
});

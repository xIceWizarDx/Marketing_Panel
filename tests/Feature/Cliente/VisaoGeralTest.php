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

describe('⛔ o aviso de vencimento (o que NÃO pode virar ruído)', function () {
    it('⭐ NÃO avisa quando a autorização se renova sozinha', function () {
        // O bug: `expira_em` do YouTube guarda o token de ACESSO, que dura 1
        // hora e é renovado sozinho. Comparado com "vence nos próximos 7 dias"
        // dava verdadeiro SEMPRE — o aviso aparecia para todo mundo, o tempo
        // todo, sem significar nada. Alerta que nunca desliga ensina a pessoa
        // a ignorar alertas, inclusive os de verdade.
        $dono = cliente();
        ContextoDoUsuario::definir($dono);

        $conta = ContaSocial::factory()->doUsuario($dono)->comCredencial()->create();
        $conta->credencial->forceFill([
            'refresh_token' => 'ainda-da-para-renovar',
            'expira_em' => now()->addHour(),
        ])->save();

        ContextoDoUsuario::limpar();

        $this->actingAs($dono)
            ->get('/painel')
            ->assertInertia(fn ($p) => $p->where('pendencias', []));
    });

    it('avisa quando NÃO há como renovar e a data está chegando', function () {
        $dono = cliente();
        ContextoDoUsuario::definir($dono);

        $conta = ContaSocial::factory()->doUsuario($dono)->comCredencial()->create();
        $conta->credencial->forceFill([
            'refresh_token' => null,
            'expira_em' => now()->addDays(2),
        ])->save();

        ContextoDoUsuario::limpar();

        $this->actingAs($dono)
            ->get('/painel')
            ->assertInertia(fn ($p) => $p->where('pendencias', function ($lista) {
                $aviso = collect($lista)->firstWhere('acao', 'Reconectar');

                // ⚠️ Dizer de QUAL rede. "A conexão de Fulano está para vencer"
                // nomeia o canal e mais nada — e nome de canal costuma ser nome
                // de pessoa, o que faz o aviso parecer outra coisa.
                expect($aviso)->not->toBeNull()
                    ->and($aviso['texto'])->toContain('Bluesky')
                    // Sem âncora suja na barra de endereço: abre aqui mesmo.
                    ->and($aviso['url'])->toBeNull()
                    ->and($aviso['rede'])->toBe('bluesky');

                return true;
            }));
    });
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

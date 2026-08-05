<?php

use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Grupo;
use App\Models\Publicacao;
use App\Services\GrupoService;
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
            ->where('numeros.naoSubiram', 1)
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

describe('⭐ a tela ve TODOS os grupos (DEC-88)', function () {
    it('o total soma os grupos, e nao so o que esta em foco', function () {
        // Ela se chama "geral" e mostrava um grupo so. Somar tudo e o que
        // torna o nome verdadeiro — e o que evita uma segunda tela dizendo a
        // mesma coisa com outro recorte.
        $ana = cliente();
        ContextoDoUsuario::definir($ana);

        $noticias = Grupo::firstOrFail();
        $novelas = app(GrupoService::class)->criar('Novelas');

        foreach ([$noticias, $novelas] as $grupo) {
            $conta = ContaSocial::factory()->doGrupo($grupo)->comCredencial()->create();
            Destino::factory()->create([
                'publicacao_id' => Publicacao::factory()->doUsuario($ana)->enviada()->create(['grupo_id' => $grupo->id])->id,
                'conta_social_id' => $conta->id,
                'status' => StatusDestino::Publicado,
                'url_publicada' => 'https://bsky.app/p/x',
            ]);
        }

        ContextoDoUsuario::limpar();

        $this->actingAs($ana)
            ->get('/painel')
            ->assertInertia(fn ($p) => $p
                ->where('numeros.noAr', 2)
                // Uma entrada por grupo, SEMPRE — a tela precisa da linha
                // mesmo quando o grupo esta zerado.
                ->has('resumoDosGrupos', 2)
                ->where('resumoDosGrupos.0.noAr', 1)
                ->where('resumoDosGrupos.1.noAr', 1));
    });

    it('com UM grupo a lista vem com uma entrada — a secao e que nao renderiza', function () {
        $ana = cliente();

        $this->actingAs($ana)
            ->get('/painel')
            ->assertInertia(fn ($p) => $p->has('resumoDosGrupos', 1));
    });

    it('⛔ nao ve grupo de outro cliente', function () {
        // A consulta nova ve todos os GRUPOS, e continua nao vendo os de outro
        // dono: o escopo aplicado no join cru e a unica trava ali.
        $ana = cliente();
        ContextoDoUsuario::definir($ana);
        $daAna = Grupo::firstOrFail();
        $conta = ContaSocial::factory()->doGrupo($daAna)->comCredencial()->create();
        Destino::factory()->create([
            'publicacao_id' => Publicacao::factory()->doUsuario($ana)->enviada()->create(['grupo_id' => $daAna->id])->id,
            'conta_social_id' => $conta->id,
            'status' => StatusDestino::Publicado,
            'url_publicada' => 'https://bsky.app/p/x',
        ]);
        ContextoDoUsuario::limpar();

        $bruno = cliente();

        $this->actingAs($bruno)
            ->get('/painel')
            ->assertInertia(fn ($p) => $p
                ->where('numeros.noAr', 0)
                ->has('resumoDosGrupos', 1)
                ->where('resumoDosGrupos.0.noAr', 0));
    });

    it('⛔ o aviso conta POSTS, nao publicacoes (DEC-90)', function () {
        // Uma publicacao vira um post por canal. O aviso contava destinos e
        // escrevia "publicacoes" — dois numeros para o mesmo fato, e o da aba
        // de Publicacoes era outro.
        $ana = cliente();
        ContextoDoUsuario::definir($ana);

        $grupo = Grupo::firstOrFail();
        $publicacao = Publicacao::factory()->doUsuario($ana)->enviada()->create(['grupo_id' => $grupo->id]);

        foreach (range(1, 2) as $i) {
            Destino::factory()->create([
                'publicacao_id' => $publicacao->id,
                'conta_social_id' => ContaSocial::factory()->doGrupo($grupo)->comCredencial()->create()->id,
                'status' => StatusDestino::Falhou,
            ]);
        }

        ContextoDoUsuario::limpar();

        $this->actingAs($ana)
            ->get('/painel')
            ->assertInertia(fn ($p) => $p->where('pendencias', function ($lista) {
                $aviso = collect($lista)->firstWhere('tom', 'erro');

                // Uma publicacao, dois canais, dois posts que nao subiram.
                expect($aviso['texto'])->toBe('2 posts não subiram.')
                    ->and($aviso['texto'])->not->toContain('publicaç');

                return true;
            }));
    });

    it('a cadencia vem pronta do servidor — a tela nao formata data', function () {
        $ana = cliente();
        ContextoDoUsuario::definir($ana);
        ContaSocial::factory()->doGrupo(Grupo::firstOrFail())->comCredencial()->create();
        ContextoDoUsuario::limpar();

        $this->actingAs($ana)
            ->get('/painel')
            ->assertInertia(fn ($p) => $p->where('resumoDosGrupos.0.cadencia', '1 canal · ainda não publicou'));
    });

    it('grupo sem canal diz isso, e nao "ainda nao publicou"', function () {
        // Dizer as duas coisas seria dizer o obvio: sem canal, claro que nao
        // publicou.
        $ana = cliente();

        $this->actingAs($ana)
            ->get('/painel')
            ->assertInertia(fn ($p) => $p->where('resumoDosGrupos.0.cadencia', 'sem canal conectado'));
    });
});

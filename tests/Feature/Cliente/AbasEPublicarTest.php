<?php

use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Support\ContextoDoUsuario;

beforeEach(fn () => ContextoDoUsuario::limpar());
afterEach(fn () => ContextoDoUsuario::limpar());

/** Uma publicação com um destino no estado pedido. */
function publicacaoCom(StatusDestino $status, $dono): void
{
    ContextoDoUsuario::definir($dono);

    $conta = ContaSocial::factory()->doUsuario($dono)->comCredencial()->create();

    Destino::factory()->create([
        'publicacao_id' => Publicacao::factory()->doUsuario($dono)->enviada()->create()->id,
        'conta_social_id' => $conta->id,
        'status' => $status,
        'url_publicada' => $status === StatusDestino::Publicado ? 'https://bsky.app/p/x' : null,
    ]);

    ContextoDoUsuario::limpar();
}

describe('as abas das publicações', function () {
    it('⭐ o número na aba responde sem ninguém clicar', function () {
        $dono = cliente();
        publicacaoCom(StatusDestino::Publicado, $dono);
        publicacaoCom(StatusDestino::Falhou, $dono);
        publicacaoCom(StatusDestino::Processando, $dono);

        $this->actingAs($dono)
            ->get('/publicacoes')
            ->assertInertia(fn ($p) => $p
                ->where('contagem.tudo', 3)
                ->where('contagem.no_ar', 1)
                ->where('contagem.falharam', 1)
                ->where('contagem.andando', 1));
    });

    it('a aba filtra de verdade, e vive na URL', function () {
        $dono = cliente();
        publicacaoCom(StatusDestino::Publicado, $dono);
        publicacaoCom(StatusDestino::Falhou, $dono);

        $this->actingAs($dono)
            ->get('/publicacoes?aba=falharam')
            ->assertInertia(fn ($p) => $p
                ->where('aba', 'falharam')
                ->has('publicacoes.data', 1));
    });

    it('⚠️ aba inventada cai em "tudo", não estoura', function () {
        $dono = cliente();
        publicacaoCom(StatusDestino::Publicado, $dono);

        $this->actingAs($dono)
            ->get('/publicacoes?aba=xpto')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->where('aba', 'tudo'));
    });

    it('"em andamento" é por exclusão — pega até estado que ninguém previu', function () {
        // Listar os estados um a um faria um estado novo sumir de todas as abas
        // em silêncio.
        $dono = cliente();
        publicacaoCom(StatusDestino::AguardandoJanela, $dono);

        $this->actingAs($dono)
            ->get('/publicacoes?aba=andando')
            ->assertInertia(fn ($p) => $p->has('publicacoes.data', 1));
    });
});

describe('os limites que a tela mostra', function () {
    it('⭐ manda os limites REAIS de cada rede, da mesma fonte do servidor', function () {
        $dono = cliente();
        ContextoDoUsuario::definir($dono);
        ContaSocial::factory()->doUsuario($dono)->comCredencial()->create();
        Midia::factory()->doUsuario($dono)->create();
        ContextoDoUsuario::limpar();

        // ⚠️ Agora vem dentro de `compositor`: publicar deixou de ser tela e
        // virou ação aberta por cima da lista.
        $this->actingAs($dono)
            ->get('/publicar')
            ->assertInertia(fn ($p) => $p
                // O YouTube corta o título em 100 — não nos 255 que o campo
                // deixava digitar.
                ->where('compositor.limites.youtube.titulo', 100)
                ->where('compositor.limites.youtube.legenda', 5000)
                // ⚠️ Como a rede CONTA, não só quanto aceita.
                ->where('compositor.limites.youtube.medidaDaLegenda', 'bytes')
                ->where('compositor.limites.bluesky.legenda', 300)
                ->where('compositor.limites.bluesky.medidaDaLegenda', 'grafemas'));
    });
});

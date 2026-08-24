<?php

use App\Enums\Plataforma;
use App\Enums\StatusDestino;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Grupo;
use App\Models\Midia;
use App\Models\Publicacao;
use App\Models\Usuario;
use App\Publicadores\RegistroDePublicadores;
use App\Support\AlcanceSomado;
use App\Support\ContextoDoUsuario;
use App\Support\MediaPorRede;

/*
| Guardiao do TOTAL e da COMPARACAO (plano 32, DEC-146 e DEC-147).
|
| ⛔ Duas regras moram aqui, e as duas ja custaram decisao:
|   1. o total serve para SENTIR TAMANHO, nunca para comparar redes;
|   2. a comparacao honesta e de cada rede contra ELA MESMA.
*/

beforeEach(fn () => ContextoDoUsuario::limpar());
afterEach(fn () => ContextoDoUsuario::limpar());

describe('⛔ o total soma, e diz o que a soma não é (DEC-146)', function () {
    it('⭐ soma as redes que responderam', function () {
        $dono = cliente();
        ContextoDoUsuario::definir($dono);

        postoComNumero($dono, Plataforma::Youtube, 100);
        postoComNumero($dono, Plataforma::Bluesky, 50);

        $alcance = AlcanceSomado::doDono(app(RegistroDePublicadores::class));

        expect($alcance->visualizacoes)->toBe(150)
            ->and($alcance->redesQueResponderam)->toBe(2);
    });

    it('⛔ sem leitura nenhuma o total é `null` — nunca zero', function () {
        /*
         * ⚠️ Zero afirmaria que ninguem viu. O certo e que ninguem leu ainda —
         * e a tela nao desenha o bloco (DEC-95).
         */
        $dono = cliente();
        ContextoDoUsuario::definir($dono);

        postoComNumero($dono, Plataforma::Youtube, null);

        expect(AlcanceSomado::doDono(app(RegistroDePublicadores::class))->visualizacoes)->toBeNull();
    });

    it('⭐ a frase avisa quando falta rede — e cala quando todas responderam', function () {
        /*
         * ⛔ Sem esse aviso, uma rede que nao respondeu hoje vira QUEDA de
         * desempenho que nao aconteceu.
         *
         * ⚠️ E dizer "3 de 3" toda vez seria ruido — ruido e o que faz a pessoa
         * parar de ler o aviso no dia em que ele importa.
         */
        $dono = cliente();
        ContextoDoUsuario::definir($dono);

        postoComNumero($dono, Plataforma::Youtube, 100);
        postoComNumero($dono, Plataforma::Bluesky, null);

        expect(AlcanceSomado::doDono(app(RegistroDePublicadores::class))->fraseDasRedes())
            ->toContain('1 de 2');

        ContextoDoUsuario::limpar();

        $outro = cliente();
        ContextoDoUsuario::definir($outro);
        postoComNumero($outro, Plataforma::Youtube, 10);

        expect(AlcanceSomado::doDono(app(RegistroDePublicadores::class))->fraseDasRedes())->toBeNull();
    });

    it('⛔ rede SEM leitor não entra no denominador', function () {
        /*
         * ⚠️ "Podia responder" e ter LEITOR — nao e ter post. Contar rede sem
         * leitor faria a frase dizer "1 de 3" para sempre, como se duas redes
         * estivessem em silencio por defeito.
         */
        $dono = cliente();
        ContextoDoUsuario::definir($dono);

        postoComNumero($dono, Plataforma::Youtube, 100);
        // O Pinterest tem publicador e NAO tem leitor de metrica.
        postoComNumero($dono, Plataforma::Pinterest, null);

        expect(AlcanceSomado::doDono(app(RegistroDePublicadores::class))->redesQuePodiamResponder)->toBe(1);
    });

    it('⛔ NUNCA soma o número de outro dono', function () {
        /*
         * ⛔ `Destino` nao tem escopo de dono — quem tem sao `ContaSocial` e
         * `Publicacao`. A primeira versao desta soma varria o banco inteiro, e
         * quem barrou foi o escopo da conta, quebrando por acaso.
         */
        $meu = cliente();
        ContextoDoUsuario::definir($meu);
        postoComNumero($meu, Plataforma::Youtube, 10);
        ContextoDoUsuario::limpar();

        $alheio = cliente();
        ContextoDoUsuario::definir($alheio);
        postoComNumero($alheio, Plataforma::Youtube, 9999);
        ContextoDoUsuario::limpar();

        ContextoDoUsuario::definir($meu);

        expect(AlcanceSomado::doDono(app(RegistroDePublicadores::class))->visualizacoes)->toBe(10);
    });
});

describe('⭐ a comparação é da rede contra ELA MESMA (DEC-147)', function () {
    it('⛔ com menos de três posts, cala — não inventa tendência', function () {
        /*
         * ⚠️ Com dois posts, qualquer um esta "acima" ou "abaixo" da media por
         * acaso. Afirmar tendencia ali seria inventar significado.
         */
        $dono = cliente();
        ContextoDoUsuario::definir($dono);

        $a = postoComNumero($dono, Plataforma::Youtube, 100);
        postoComNumero($dono, Plataforma::Youtube, 200);

        expect(MediaPorRede::doDono()->comparar($a->fresh(['contaSocial'])))->toBeNull();
    });

    it('⭐ com base, diz acima, abaixo ou na média', function () {
        $dono = cliente();
        ContextoDoUsuario::definir($dono);

        $baixo = postoComNumero($dono, Plataforma::Youtube, 10);
        $alto = postoComNumero($dono, Plataforma::Youtube, 1000);
        postoComNumero($dono, Plataforma::Youtube, 100);

        $medias = MediaPorRede::doDono();

        expect($medias->comparar($alto->fresh(['contaSocial'])))->toContain('acima')
            ->and($medias->comparar($baixo->fresh(['contaSocial'])))->toContain('abaixo');
    });

    it('⭐ e há uma faixa do meio chamada "na média"', function () {
        /*
         * ⚠️ Sem ela, um post 2% acima viraria "acima da media" — e a palavra
         * perderia o sentido justamente para quem confia nela.
         */
        $dono = cliente();
        ContextoDoUsuario::definir($dono);

        postoComNumero($dono, Plataforma::Youtube, 100);
        postoComNumero($dono, Plataforma::Youtube, 100);
        $meio = postoComNumero($dono, Plataforma::Youtube, 102);

        expect(MediaPorRede::doDono()->comparar($meio->fresh(['contaSocial'])))->toContain('na média');
    });

    it('⛔ e a média NUNCA vem do dono errado', function () {
        $meu = cliente();
        ContextoDoUsuario::definir($meu);
        $meuPost = postoComNumero($meu, Plataforma::Youtube, 100);
        postoComNumero($meu, Plataforma::Youtube, 100);
        postoComNumero($meu, Plataforma::Youtube, 100);
        ContextoDoUsuario::limpar();

        $alheio = cliente();
        ContextoDoUsuario::definir($alheio);
        postoComNumero($alheio, Plataforma::Youtube, 999999);
        postoComNumero($alheio, Plataforma::Youtube, 999999);
        postoComNumero($alheio, Plataforma::Youtube, 999999);
        ContextoDoUsuario::limpar();

        ContextoDoUsuario::definir($meu);

        // ⭐ Se a media viesse do banco inteiro, o meu post de 100 seria
        // "abaixo" — quando ele está exatamente na média DELE.
        expect(MediaPorRede::doDono()->comparar($meuPost->fresh(['contaSocial'])))->toContain('na média');
    });
});

/** Um post publicado com (ou sem) número lido. */
function postoComNumero(Usuario $dono, Plataforma $rede, ?int $visualizacoes): Destino
{
    $midia = Midia::factory()->doUsuario($dono)->create();
    $publicacao = Publicacao::factory()->doUsuario($dono)->enviada()->create(['midia_id' => $midia->id]);

    $conta = ContaSocial::factory()->doUsuario($dono)->doGrupo(Grupo::firstOrFail())
        ->daPlataforma($rede)->comCredencial('token')->create();

    return Destino::factory()->create([
        'publicacao_id' => $publicacao->id,
        'conta_social_id' => $conta->id,
        'status' => StatusDestino::Publicado,
        'url_publicada' => 'https://exemplo.test/post',
        'publicado_em' => now(),
        'visualizacoes' => $visualizacoes,
    ]);
}

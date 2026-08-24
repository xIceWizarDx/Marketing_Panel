<?php

use App\Enums\Plataforma;
use App\Enums\TipoMidia;
use App\Services\InspetorDeMidia;
use App\Support\Midia\Achado;
use App\Support\Midia\EspecificacaoDaRede;
use App\Support\Midia\FichaTecnica;
use App\Support\Midia\NivelDoAchado;

/*
| O LAUDO DE MÍDIA — o diferencial nº 1 (DEC-32/33).
|
| Dividido em dois: as REGRAS (rápidas, sem tocar em arquivo) e a LEITURA de um
| vídeo de verdade pelo ffprobe. Só a segunda depende da ferramenta instalada.
*/

function fichaDeVideo(array $ajustes = []): FichaTecnica
{
    return new FichaTecnica(...array_merge([
        'formato' => 'mov,mp4,m4a',
        'duracaoSegundos' => 30.0,
        'largura' => 1080,
        'altura' => 1920,
        'codecVideo' => 'h264',
        'codecAudio' => 'aac',
        'fps' => 30.0,
        'tamanhoBytes' => 20 * 1024 * 1024,
    ], $ajustes));
}

/** @param list<Achado> $achados */
function temErro(array $achados): bool
{
    return collect($achados)->contains(fn ($a) => $a->nivel === NivelDoAchado::Erro);
}

describe('as regras de cada rede', function () {
    it('aprova o vídeo do perfil canônico — e diz QUAIS redes não cabem nele', function () {
        /*
         * ⭐ O perfil canonico (doc 07 §6) e o video de 20 MB que o produto
         * assume: 1080x1920, 30 s, h264/aac.
         *
         * ⛔ **Algumas redes tem teto MENOR que isso**, e nao e defeito nosso —
         * e regra delas. Enumerar aqui, com o motivo, e o que impede duas
         * coisas: alguem "consertar" o limite achando que e engano, e alguem
         * ligar rede nova com teto apertado sem perceber.
         */
        $naoCabemNoCanonico = [
            // ⚠️ Teto de 10 MB no servidor sem impulsionamento — o piso do
            // Discord, e o unico numero que vale para qualquer servidor.
            Plataforma::Discord,
        ];

        foreach (EspecificacaoDaRede::todas() as $rede) {
            $recusa = temErro($rede->conferir(fichaDeVideo(), true));

            expect($recusa)->toBe(
                in_array($rede->plataforma, $naoCabemNoCanonico, true),
                "{$rede->plataforma->value} nao se comportou como o esperado com o video canonico."
            );
        }
    });

    it('diz que o vídeo passa intacto quando o codec é aceito', function () {
        $achados = EspecificacaoDaRede::de(Plataforma::Instagram)->conferir(fichaDeVideo(), true);

        // ⭐ DEC-33: é o oposto do que os concorrentes fazem (recodificar tudo).
        expect(collect($achados)->contains(fn ($a) => str_contains($a->mensagem, 'passa intacto')))
            ->toBeTrue();
    });

    it('aceita HEVC do iPhone', function () {
        // Buffer e Metricool recusam; a Meta aceita — recusar seria inventar um
        // limite que não existe.
        $achados = EspecificacaoDaRede::de(Plataforma::Instagram)
            ->conferir(fichaDeVideo(['codecVideo' => 'hevc']), true);

        expect(temErro($achados))->toBeFalse();
    });

    it('barra no Facebook o vídeo de 91 a 180 segundos, e só nele', function () {
        $ficha = fichaDeVideo(['duracaoSegundos' => 120.0]);

        expect(temErro(EspecificacaoDaRede::de(Plataforma::Facebook)->conferir($ficha, true)))->toBeTrue()
            ->and(temErro(EspecificacaoDaRede::de(Plataforma::Youtube)->conferir($ficha, true)))->toBeFalse()
            ->and(temErro(EspecificacaoDaRede::de(Plataforma::Instagram)->conferir($ficha, true)))->toBeFalse()
            ->and(temErro(EspecificacaoDaRede::de(Plataforma::Tiktok)->conferir($ficha, true)))->toBeFalse();
    });

    it('recusa vídeo curto demais', function () {
        $achados = EspecificacaoDaRede::de(Plataforma::Tiktok)
            ->conferir(fichaDeVideo(['duracaoSegundos' => 1.0]), true);

        expect(temErro($achados))->toBeTrue();
    });

    it('avisa sobre vídeo deitado sem recusar', function () {
        $achados = EspecificacaoDaRede::de(Plataforma::Tiktok)
            ->conferir(fichaDeVideo(['largura' => 1920, 'altura' => 1080]), true);

        // Atenção, não erro: a rede publica, mas corta. Quem decide é a pessoa.
        expect(temErro($achados))->toBeFalse()
            ->and(collect($achados)->contains(fn ($a) => $a->nivel === NivelDoAchado::Atencao))->toBeTrue();
    });

    it('promete recodificar só o áudio quando o formato não serve', function () {
        $achados = EspecificacaoDaRede::de(Plataforma::Instagram)
            ->conferir(fichaDeVideo(['codecAudio' => 'opus']), true);

        $achado = collect($achados)->first(fn ($a) => str_contains($a->mensagem, 'Áudio em opus'));

        expect($achado)->not->toBeNull()
            ->and($achado->nivel)->toBe(NivelDoAchado::Atencao)
            ->and($achado->providencia)->toContain('imagem fica intacta');
    });

    it('avisa quando o vídeo não tem áudio', function () {
        $achados = EspecificacaoDaRede::de(Plataforma::Youtube)
            ->conferir(fichaDeVideo(['codecAudio' => null]), true);

        expect(temErro($achados))->toBeFalse()
            ->and(collect($achados)->contains(fn ($a) => str_contains($a->mensagem, 'não tem áudio')))->toBeTrue();
    });

    it('recusa arquivo acima do teto da rede', function () {
        $achados = EspecificacaoDaRede::de(Plataforma::Instagram)
            ->conferir(fichaDeVideo(['tamanhoBytes' => 400 * 1024 * 1024]), true);

        expect(temErro($achados))->toBeTrue();
    });

    it('⛔ o laudo de imagem concorda com o PUBLICADOR, rede por rede', function () {
        /*
         * ⛔ `aceitaImagem` nao e "a plataforma suporta imagem": e "o PAINEL
         * publica imagem aqui" — a propria frase do laudo diz "nao publica
         * imagem POR AQUI".
         *
         * ⚠️ Quatro redes declaravam `true` enquanto o publicador delas recusava
         * imagem na primeira linha. A pessoa via "formato aceito" no laudo e
         * recebia "o X recebe video por aqui" na hora de publicar.
         *
         * ⛔ **Ao ligar uma rede nova, esta lista tem que crescer junto** — e e
         * de proposito que ela quebre se ninguem lembrar.
         */
        $publicamImagem = [
            Plataforma::Bluesky,
            Plataforma::Threads,
            // ⭐ O Mastodon aceita qualquer arquivo que o servidor aceite: o
            // publicador nao filtra por tipo, e o laudo diz o mesmo.
            Plataforma::Mastodon,
            // ⭐ O Discord tambem nao filtra: e um anexo de mensagem.
            Plataforma::Discord,
        ];

        $imagem = new FichaTecnica(largura: 1080, altura: 1350, tamanhoBytes: 2 * 1024 * 1024);

        foreach (Plataforma::comEspecificacao() as $rede) {
            $recusa = temErro(EspecificacaoDaRede::de($rede)->conferir($imagem, false));

            expect($recusa)->toBe(
                ! in_array($rede, $publicamImagem, true),
                "O laudo de imagem do {$rede->rotulo()} nao bate com o publicador dele."
            );
        }
    });

    it('sempre explica o que será feito quando aponta um problema', function () {
        // Achado sem providência é o que o cliente odeia: "deu erro" e nada mais.
        $achados = EspecificacaoDaRede::de(Plataforma::Facebook)
            ->conferir(fichaDeVideo(['duracaoSegundos' => 120.0, 'codecAudio' => 'opus']), true);

        foreach ($achados as $achado) {
            if ($achado->nivel !== NivelDoAchado::Ok) {
                expect($achado->providencia)->not->toBeEmpty(
                    "O achado \"{$achado->mensagem}\" não diz o que fazer."
                );
            }
        }
    });
});

describe('a leitura do arquivo pelo ffprobe', function () {
    beforeEach(function () {
        if (! app(InspetorDeMidia::class)->disponivel()) {
            $this->markTestSkipped('ffprobe não instalado — veja o README.');
        }
    });

    it('lê a ficha técnica de um vídeo de verdade', function () {
        $ficha = app(InspetorDeMidia::class)->inspecionar(base_path('tests/Fixtures/vertical-ok.mp4'));

        expect($ficha->largura)->toBe(1080)
            ->and($ficha->altura)->toBe(1920)
            ->and($ficha->codecVideo)->toBe('h264')
            ->and($ficha->codecAudio)->toBe('aac')
            ->and($ficha->fps)->toBe(30.0)
            ->and(round($ficha->duracaoSegundos))->toBe(5.0)
            ->and($ficha->ehVertical())->toBeTrue();
    });

    it('monta o laudo de todas as redes a partir do arquivo', function () {
        $laudo = app(InspetorDeMidia::class)
            ->laudar(base_path('tests/Fixtures/vertical-ok.mp4'), TipoMidia::Video);

        // Conta a partir do enum: rede nova entra no laudo sem o teste
        // envelhecer. So as que tem regra pesquisada — rede em estudo fica fora.
        $total = count(Plataforma::comEspecificacao());

        expect($laudo->disponivel())->toBeTrue()
            ->and($laudo->porRede)->toHaveCount($total)
            ->and($laudo->redesQueAceitam())->toHaveCount($total);
    });

    it('reconhece vídeo deitado no arquivo real', function () {
        $ficha = app(InspetorDeMidia::class)->inspecionar(base_path('tests/Fixtures/deitado-curto.mp4'));

        expect($ficha->ehVertical())->toBeFalse()
            ->and($ficha->temAudio())->toBeFalse();
    });
});

describe('quando o ffprobe não existe', function () {
    it('devolve laudo indisponível em vez de quebrar', function () {
        // O cliente não pode ver erro técnico por causa de ferramenta de servidor.
        config(['midia.ffprobe' => 'ffprobe-que-nao-existe']);

        $laudo = app(InspetorDeMidia::class)->laudar('/qualquer/caminho.mp4', TipoMidia::Video);

        expect($laudo->disponivel())->toBeFalse()
            ->and($laudo->indisponivelPorque)->toContain('não está disponível no servidor');
    });

    it('devolve laudo indisponível para arquivo ilegível', function () {
        $laudo = app(InspetorDeMidia::class)
            ->laudar(base_path('composer.json'), TipoMidia::Video);

        expect($laudo->disponivel())->toBeFalse();
    })->skip(fn () => ! app(InspetorDeMidia::class)->disponivel(), 'ffprobe não instalado');
});

describe('quais redes entram no laudo', function () {
    it('so julga arquivo contra rede com regra pesquisada', function () {
        $comRegra = collect(EspecificacaoDaRede::todas())->pluck('plataforma.value');

        expect($comRegra)->toContain('bluesky', 'linkedin', 'youtube', 'instagram', 'facebook', 'threads', 'tiktok')
            // Rede em estudo ficaria como "aceita"/"nao aceita" sem ninguem ter
            // conferido nada — o laudo perderia o que o torna util.
            ->not->toContain('pinterest', 'x', 'mastodon', 'snapchat');
    });

    it('recusa montar regra para rede em estudo', function () {
        expect(fn () => EspecificacaoDaRede::de(Plataforma::Snapchat))
            ->toThrow(InvalidArgumentException::class);
    });
});

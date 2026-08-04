<?php

use App\Enums\StatusDestino;
use App\Enums\StatusPublicacao;
use App\Exceptions\TransicaoInvalida;
use App\Models\ContaSocial;
use App\Models\Destino;
use App\Models\Publicacao;
use App\Services\PublicacaoService;
use App\Support\ContextoDoUsuario;
use Illuminate\Database\QueryException;

/*
| Guardiao da MAQUINA DE ESTADOS.
|
| ⭐ DEC-31: o produto vende status honesto. Se um destino puder virar
| "publicado" sem alguem ter relido o post na rede, o produto vira exatamente o
| que a gente critica nos concorrentes. Este arquivo torna isso impossivel.
*/

beforeEach(fn () => ContextoDoUsuario::limpar());
afterEach(fn () => ContextoDoUsuario::limpar());

function destinoNoEstado(StatusDestino $status = StatusDestino::Pendente): Destino
{
    $dono = cliente();
    $publicacao = Publicacao::factory()->doUsuario($dono)->enviada()->create();
    $conta = ContaSocial::factory()->doUsuario($dono)->create();

    $destino = Destino::factory()->create([
        'publicacao_id' => $publicacao->id,
        'conta_social_id' => $conta->id,
        'status' => $status,
    ]);

    // Igual ao que o job faz antes de trabalhar: o motor roda sem sessao.
    ContextoDoUsuario::definir($dono);

    return $destino;
}

function motor(): PublicacaoService
{
    return app(PublicacaoService::class);
}

it('percorre o caminho feliz na ordem certa', function () {
    $destino = destinoNoEstado();

    motor()->marcarEnviando($destino);
    expect($destino->fresh()->status)->toBe(StatusDestino::Enviando);

    motor()->marcarProcessando($destino->fresh(), 'post-123');
    expect($destino->fresh()->status)->toBe(StatusDestino::Processando);

    motor()->marcarPublicado($destino->fresh(), 'https://bsky.app/post/123');
    expect($destino->fresh())
        ->status->toBe(StatusDestino::Publicado)
        ->url_publicada->toBe('https://bsky.app/post/123')
        ->publicado_em->not->toBeNull();
});

it('⭐ NUNCA deixa pular da fila direto para publicado', function () {
    // É a mentira que o produto existe para não contar: dizer que publicou sem
    // ter enviado nem conferido.
    $destino = destinoNoEstado(StatusDestino::Pendente);

    expect(fn () => motor()->marcarPublicado($destino, 'https://exemplo/1'))
        ->toThrow(TransicaoInvalida::class);

    expect($destino->fresh()->status)->toBe(StatusDestino::Pendente);
});

it('⭐ NUNCA deixa marcar publicado sem o link da prova', function () {
    $destino = destinoNoEstado(StatusDestino::Processando);

    expect(fn () => motor()->marcarPublicado($destino, '   '))
        ->toThrow(InvalidArgumentException::class);

    expect($destino->fresh()->status)->toBe(StatusDestino::Processando);
});

it('trata publicado como definitivo', function () {
    $destino = destinoNoEstado(StatusDestino::Processando);
    motor()->marcarPublicado($destino, 'https://bsky.app/post/1');

    expect(fn () => motor()->marcarFalha($destino->fresh(), 'tarde demais'))
        ->toThrow(TransicaoInvalida::class);
});

it('não deixa reenviar um destino que já está enviando', function () {
    // Fila re-entregou o job? O serviço recusa. Sem isso, o mesmo vídeo subiria
    // duas vezes — e publicação não tem desfazer.
    $destino = destinoNoEstado(StatusDestino::Enviando);

    expect(fn () => motor()->marcarEnviando($destino))->toThrow(TransicaoInvalida::class);
    expect($destino->tentativas()->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Retry ≠ falha final
|--------------------------------------------------------------------------
*/

it('devolve para a fila em falha transitória, sem marcar falha', function () {
    $destino = destinoNoEstado(StatusDestino::Enviando);

    motor()->devolverParaFila($destino, 'a rede demorou demais');

    expect($destino->fresh())
        ->status->toBe(StatusDestino::Pendente)
        ->tentativas_feitas->toBe(1)
        // Nada de mensagem de erro: ainda vai tentar de novo, e a tela não pode
        // mostrar "falhou" enquanto isso.
        ->erro_mensagem->toBeNull();
});

it('desiste só na última tentativa', function () {
    $destino = destinoNoEstado(StatusDestino::Enviando);

    // Duas primeiras voltam pra fila...
    motor()->devolverParaFila($destino, 'timeout');
    motor()->marcarEnviando($destino->fresh());
    motor()->devolverParaFila($destino->fresh(), 'timeout');

    expect($destino->fresh()->status)->toBe(StatusDestino::Pendente);

    // ...a terceira vira falha final.
    motor()->marcarEnviando($destino->fresh());
    motor()->devolverParaFila($destino->fresh(), 'timeout');

    expect($destino->fresh())
        ->status->toBe(StatusDestino::Falhou)
        ->erro_mensagem->not->toBeNull();
});

it('não mexe no agregado durante uma retentativa', function () {
    $destino = destinoNoEstado(StatusDestino::Enviando);
    $publicacao = $destino->publicacao;
    $publicacao->forceFill(['status' => StatusPublicacao::Processando])->save();

    motor()->devolverParaFila($destino, 'timeout');

    // Se o agregado fosse recalculado a cada tentativa, o status oscilaria na
    // tela e a notificação de falha dispararia à toa.
    expect($publicacao->fresh()->status)->toBe(StatusPublicacao::Processando);
});

/*
|--------------------------------------------------------------------------
| O agregado da publicação
|--------------------------------------------------------------------------
*/

it('deriva o estado da publicação a partir dos destinos', function () {
    $dono = cliente();
    ContextoDoUsuario::definir($dono);
    $publicacao = Publicacao::factory()->doUsuario($dono)->enviada()->create();

    $destinos = collect(range(1, 3))->map(fn () => Destino::factory()->create([
        'publicacao_id' => $publicacao->id,
        'conta_social_id' => ContaSocial::factory()->doUsuario($dono)->create()->id,
        'status' => StatusDestino::Processando,
    ]));

    // Um só terminou: ainda está publicando.
    motor()->marcarPublicado($destinos[0], 'https://exemplo/1');
    expect($publicacao->fresh()->status)->toBe(StatusPublicacao::Processando);

    // Todos terminaram, um falhou: publicada com falhas.
    motor()->marcarPublicado($destinos[1], 'https://exemplo/2');
    motor()->marcarFalha($destinos[2], 'a rede recusou');
    expect($publicacao->fresh()->status)->toBe(StatusPublicacao::ConcluidaComFalhas);
});

it('marca a publicação como concluída quando todos os destinos sobem', function () {
    $dono = cliente();
    ContextoDoUsuario::definir($dono);
    $publicacao = Publicacao::factory()->doUsuario($dono)->enviada()->create();

    collect(range(1, 2))->each(function ($i) use ($publicacao, $dono) {
        $destino = Destino::factory()->create([
            'publicacao_id' => $publicacao->id,
            'conta_social_id' => ContaSocial::factory()->doUsuario($dono)->create()->id,
            'status' => StatusDestino::Processando,
        ]);
        motor()->marcarPublicado($destino, "https://exemplo/{$i}");
    });

    expect($publicacao->fresh()->status)->toBe(StatusPublicacao::Concluida);
});

it('marca a publicação como falhou quando nenhum destino sobe', function () {
    $dono = cliente();
    ContextoDoUsuario::definir($dono);
    $publicacao = Publicacao::factory()->doUsuario($dono)->enviada()->create();

    collect(range(1, 2))->each(function () use ($publicacao, $dono) {
        $destino = Destino::factory()->create([
            'publicacao_id' => $publicacao->id,
            'conta_social_id' => ContaSocial::factory()->doUsuario($dono)->create()->id,
            'status' => StatusDestino::Processando,
        ]);
        motor()->marcarFalha($destino, 'recusado');
    });

    expect($publicacao->fresh()->status)->toBe(StatusPublicacao::Falhou);
});

it('volta a publicar quando um destino falho é reprocessado', function () {
    $destino = destinoNoEstado(StatusDestino::Processando);
    motor()->marcarFalha($destino, 'recusado');

    expect($destino->publicacao->fresh()->status)->toBe(StatusPublicacao::Falhou);

    motor()->reprocessar($destino->fresh());

    expect($destino->fresh())
        ->status->toBe(StatusDestino::Pendente)
        ->tentativas_feitas->toBe(0)
        ->erro_mensagem->toBeNull()
        ->and($destino->publicacao->fresh()->status)->toBe(StatusPublicacao::Processando);
});

/*
|--------------------------------------------------------------------------
| Anti-double-post e quota
|--------------------------------------------------------------------------
*/

it('guarda o handle de retomada antes do envio', function () {
    $destino = destinoNoEstado(StatusDestino::Enviando);

    motor()->guardarHandle($destino, 'sessao-de-upload-abc');

    // Sem este handle, uma re-entrega da fila publicaria o mesmo vídeo de novo —
    // e não há como desfazer uma publicação.
    expect($destino->fresh()->handle_externo)->toBe('sessao-de-upload-abc');
});

it('não permite dois destinos para a mesma conta na mesma publicação', function () {
    $dono = cliente();
    ContextoDoUsuario::definir($dono);
    $publicacao = Publicacao::factory()->doUsuario($dono)->enviada()->create();
    $conta = ContaSocial::factory()->doUsuario($dono)->create();

    Destino::factory()->create(['publicacao_id' => $publicacao->id, 'conta_social_id' => $conta->id]);

    expect(fn () => Destino::factory()->create([
        'publicacao_id' => $publicacao->id,
        'conta_social_id' => $conta->id,
    ]))->toThrow(QueryException::class);
});

it('espera a janela quando a quota da rede acaba', function () {
    $destino = destinoNoEstado(StatusDestino::Pendente);

    motor()->aguardarJanela($destino, 'A cota diária do YouTube acabou. Publica amanhã.');
    expect($destino->fresh()->status)->toBe(StatusDestino::AguardandoJanela);

    motor()->liberarDaJanela($destino->fresh());
    expect($destino->fresh())
        ->status->toBe(StatusDestino::Pendente)
        ->erro_mensagem->toBeNull();
});

it('registra cada tentativa com início e fim', function () {
    $destino = destinoNoEstado();

    motor()->marcarEnviando($destino);
    expect($destino->tentativas()->count())->toBe(1)
        ->and($destino->tentativas()->first()->emAberto())->toBeTrue();

    motor()->marcarProcessando($destino->fresh());
    motor()->marcarPublicado($destino->fresh(), 'https://exemplo/1');

    $tentativa = $destino->tentativas()->first();
    expect($tentativa->emAberto())->toBeFalse()
        ->and($tentativa->sucesso)->toBeTrue();
});

it('mantém a lista de transições coerente com o enum', function () {
    // Trava a máquina inteira: mexer numa transição sem pensar quebra aqui.
    expect(StatusDestino::Publicado->transicoesPermitidas())->toBe([])
        ->and(StatusDestino::Publicado->ehTerminal())->toBeTrue()
        ->and(StatusDestino::Falhou->ehTerminal())->toBeTrue()
        ->and(StatusDestino::Pendente->podeIrPara(StatusDestino::Publicado))->toBeFalse()
        ->and(StatusDestino::Enviando->podeIrPara(StatusDestino::Publicado))->toBeFalse()
        ->and(StatusDestino::Processando->podeIrPara(StatusDestino::Publicado))->toBeTrue();
});

<?php

use App\Http\Controllers\Cliente\ConexaoController;
use App\Http\Controllers\Cliente\GrupoController;
use App\Http\Controllers\Cliente\MidiaController;
use App\Http\Controllers\Cliente\PublicacaoController;
use App\Http\Controllers\Cliente\VisaoGeralController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('boas-vindas'))->name('inicio');

// Painel do cliente — conectar redes, subir midia, publicar.
Route::middleware(['auth', 'papel:cliente'])->group(function () {
    Route::get('painel', VisaoGeralController::class)->name('painel');

    // O envio vive dentro do compositor: publicar e enviar sao um gesto so.
    Route::post('midias', [MidiaController::class, 'salvar'])->name('midias.salvar');
    Route::get('midias/{ulid}/arquivo', [MidiaController::class, 'baixar'])->name('midias.arquivo');
    // Separada do arquivo: a grade pede todas as miniaturas de uma vez, e
    // baixar o vídeo inteiro para desenhar um quadradinho seria absurdo.
    Route::get('midias/{ulid}/miniatura', [MidiaController::class, 'miniatura'])->name('midias.miniatura');

    /*
     * ⭐ O grupo nao tem tela: e MODO, e modo se muda de onde a pessoa esta.
     * Por isso tudo aqui e acao e volta com `back()`.
     *
     * ⛔ Trocar de grupo e POST: com GET, o prefetch do navegador trocaria o
     * modo sozinho — e a proxima publicacao sairia no lugar errado.
     */
    Route::post('grupos', [GrupoController::class, 'criar'])->name('grupos.criar');
    Route::patch('grupos/{ulid}', [GrupoController::class, 'renomear'])->name('grupos.renomear');
    Route::delete('grupos/{ulid}', [GrupoController::class, 'arquivar'])->name('grupos.arquivar');
    Route::post('grupos/{ulid}/usar', [GrupoController::class, 'usar'])->name('grupos.usar');
    Route::patch('conexoes/{ulid}/grupo', [GrupoController::class, 'moverCanal'])->name('conexoes.grupo');

    Route::post('conexoes/bluesky', [ConexaoController::class, 'conectarBluesky'])->name('conexoes.bluesky');
    Route::get('conexoes/youtube', [ConexaoController::class, 'iniciarYoutube'])->name('conexoes.youtube');
    Route::get('conexoes/youtube/retorno', [ConexaoController::class, 'retornoYoutube'])->name('conexoes.youtube.retorno');

    // Uma conexão só acende Facebook e Instagram: a conta do Instagram fica
    // pendurada numa Página do Facebook, e o login é o mesmo.
    Route::get('conexoes/meta', [ConexaoController::class, 'iniciarMeta'])->name('conexoes.meta');
    Route::get('conexoes/meta/retorno', [ConexaoController::class, 'retornoMeta'])->name('conexoes.meta.retorno');
    Route::delete('conexoes/{ulid}', [ConexaoController::class, 'desconectar'])->name('conexoes.desconectar');

    // ⭐ O compositor abre por cima da lista, mas por rota de verdade:
    // atualizar a pagina reabre no mesmo ponto, e voltar fecha o modal.
    Route::get('publicar', [PublicacaoController::class, 'compor'])->name('publicar');
    Route::get('publicar/{publicacao}', [PublicacaoController::class, 'compor'])->name('publicar.de-novo');
    Route::post('publicar', [PublicacaoController::class, 'enviar'])->name('publicar.enviar');

    Route::get('publicacoes', [PublicacaoController::class, 'listar'])->name('publicacoes');
    Route::post('publicacoes/destinos/{ulid}/tentar-de-novo', [PublicacaoController::class, 'reprocessar'])
        ->name('publicacoes.reprocessar');
});

require __DIR__.'/admin.php';
require __DIR__.'/minha-conta.php';
require __DIR__.'/acesso.php';

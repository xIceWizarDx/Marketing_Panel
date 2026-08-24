<?php

use App\Http\Controllers\Cliente\ConexaoController;
use App\Http\Controllers\Cliente\GrupoController;
use App\Http\Controllers\Cliente\MidiaController;
use App\Http\Controllers\Cliente\PublicacaoController;
use App\Http\Controllers\Cliente\VisaoGeralController;
use App\Http\Controllers\MidiaTemporariaController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('boas-vindas'))->name('inicio');

/*
| ⭐ **Os documentos públicos** — e eles são PORTA DE ENTRADA, não enfeite
| (DEC-171).
|
| ⛔ TikTok e Meta **bloqueiam o cadastro do aplicativo** sem estes dois
| endereços, e o YouTube exige a referência à política do Google. Sem eles não
| existe integração nenhuma — nem em modo de teste.
|
| ⚠️ **Fora de qualquer grupo autenticado, de propósito.** O robô da plataforma
| abre o endereço sem sessão: uma página que mandasse entrar reprovaria a
| análise sem dizer por quê.
*/
Route::get('termos', fn () => Inertia::render('termos'))->name('termos');
Route::get('privacidade', fn () => Inertia::render('privacidade'))->name('privacidade');

/*
| ⭐ O ÚNICO endereço que serve arquivo SEM SESSÃO — e ele está fora do grupo
| autenticado de propósito.
|
| Existe porque o Threads não aceita envio de arquivo: ele recebe uma URL e vai
| BUSCAR a mídia. Quem busca é um servidor da Meta, que não tem login no painel e
| nunca terá (DEC-100).
|
| ⛔ Por isso `signed` é a trava inteira: sem ele, o endereço vira o arquivo de
| qualquer cliente para quem souber montar a URL. A assinatura não se forja, o
| prazo é curto, e o endereço não serve mais nada além do arquivo.
*/
Route::get('midia-temporaria/{ulid}', MidiaTemporariaController::class)
    ->middleware('signed')
    ->name('midias.temporaria');

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
    // ⚠️ Antes do `{ulid}` genérico não faz diferença aqui porque o segmento
    // extra desempata — mas o nome é próprio: trocar hashtag não é renomear.
    Route::patch('grupos/{ulid}/hashtags', [GrupoController::class, 'hashtags'])->name('grupos.hashtags');
    Route::delete('grupos/{ulid}', [GrupoController::class, 'excluir'])->name('grupos.excluir');
    Route::post('grupos/{ulid}/usar', [GrupoController::class, 'usar'])->name('grupos.usar');
    Route::patch('conexoes/{ulid}/grupo', [GrupoController::class, 'moverCanal'])->name('conexoes.grupo');

    Route::post('conexoes/bluesky', [ConexaoController::class, 'conectarBluesky'])->name('conexoes.bluesky');
    Route::get('conexoes/youtube', [ConexaoController::class, 'iniciarYoutube'])->name('conexoes.youtube');
    Route::get('conexoes/youtube/retorno', [ConexaoController::class, 'retornoYoutube'])->name('conexoes.youtube.retorno');

    // ⚠️ Caminho próprio, e não uma variação do da Meta: a janela de autorizacao
    // do Threads e em `threads.net` e os escopos sao outros (DEC-99).
    Route::get('conexoes/threads', [ConexaoController::class, 'iniciarThreads'])->name('conexoes.threads');
    Route::get('conexoes/threads/retorno', [ConexaoController::class, 'retornoThreads'])->name('conexoes.threads.retorno');

    // Uma conexão só acende Facebook e Instagram: a conta do Instagram fica
    // pendurada numa Página do Facebook, e o login é o mesmo.
    Route::get('conexoes/meta', [ConexaoController::class, 'iniciarMeta'])->name('conexoes.meta');
    Route::get('conexoes/meta/retorno', [ConexaoController::class, 'retornoMeta'])->name('conexoes.meta.retorno');
    /*
     * ⚠️ O LinkedIn nao tem parentesco nenhum com as outras: portal proprio,
     * aplicativo proprio, e um token de 60 dias que NAO se renova sozinho
     * (DEC-112) — quando vencer, e por esta porta que a pessoa passa de novo.
     */
    Route::get('conexoes/linkedin', [ConexaoController::class, 'iniciarLinkedin'])->name('conexoes.linkedin');
    Route::get('conexoes/linkedin/retorno', [ConexaoController::class, 'retornoLinkedin'])->name('conexoes.linkedin.retorno');
    /*
     * ⚠️ O token do TikTok vive 24 HORAS — o prazo mais curto do painel. A
     * conexao nao morre por isso (o de renovacao vale 365 dias), mas renovar
     * faz parte de publicar, nao e rotina de madrugada (DEC-118).
     */
    Route::get('conexoes/tiktok', [ConexaoController::class, 'iniciarTiktok'])->name('conexoes.tiktok');
    Route::get('conexoes/tiktok/retorno', [ConexaoController::class, 'retornoTiktok'])->name('conexoes.tiktok.retorno');
    /*
     * ⛔ Primeira rede do painel com PKCE obrigatorio (DEC-129) — o segredo da
     * ida fica na sessao junto com o `state`. E o codigo de autorizacao vive
     * 30 SEGUNDOS (DEC-128): a troca e a primeira coisa da volta.
     */
    Route::get('conexoes/x', [ConexaoController::class, 'iniciarX'])->name('conexoes.x');
    Route::get('conexoes/x/retorno', [ConexaoController::class, 'retornoX'])->name('conexoes.x.retorno');
    /*
     * ⚠️ O Pinterest traz um canal por QUADRO (DEC-134) — e por isso o retorno
     * conta quantos entraram, como o da Meta faz com as Paginas.
     */
    Route::get('conexoes/pinterest', [ConexaoController::class, 'iniciarPinterest'])->name('conexoes.pinterest');
    Route::get('conexoes/pinterest/retorno', [ConexaoController::class, 'retornoPinterest'])->name('conexoes.pinterest.retorno');
    /*
     * ⭐ A terceira forma de conectar (DEC-138): antes de autorizar, a pessoa
     * diz ONDE a conta mora. Por isso a porta e POST — ela leva um formulario,
     * nao um clique.
     */
    Route::post('conexoes/mastodon', [ConexaoController::class, 'iniciarMastodon'])->name('conexoes.mastodon');
    Route::get('conexoes/mastodon/retorno', [ConexaoController::class, 'retornoMastodon'])->name('conexoes.mastodon.retorno');
    // ⭐ A conexao mais simples do painel: a pessoa cola o endereco do webhook
    // que ela mesma criou no Discord (DEC-141).
    Route::post('conexoes/discord', [ConexaoController::class, 'conectarDiscord'])->name('conexoes.discord');
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

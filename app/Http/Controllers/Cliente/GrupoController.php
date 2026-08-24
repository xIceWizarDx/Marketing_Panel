<?php

namespace App\Http\Controllers\Cliente;

use App\Enums\Plataforma;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\GuardarGrupoRequest;
use App\Http\Requests\Cliente\GuardarHashtagsDoGrupoRequest;
use App\Models\ContaSocial;
use App\Models\Grupo;
use App\Services\GrupoService;
use App\Support\GrupoCorrente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Criar, renomear, excluir e trocar de grupo — **só ações**.
 *
 * ⛔ Grupo não tem tela (DEC-71): ele é modo, e modo se mostra onde a pessoa
 * está. Tudo aqui acontece por diálogo em cima da tela em que ela já estava.
 */
class GrupoController extends Controller
{
    public function __construct(
        private readonly GrupoService $grupos,
    ) {}

    public function criar(GuardarGrupoRequest $request): RedirectResponse
    {
        $grupo = $this->grupos->criar($request->nomeEscolhido());

        /*
         * ⛔ **Não troca de grupo sozinho** (DEC-78).
         *
         * Entrar no grupo novo esvaziaria o painel inteiro sem explicar — e a
         * tela de vazio diz literalmente que a pessoa nunca publicou nada. Ela
         * concluiria que o sistema apagou o trabalho dela.
         */
        return back()->with(
            'sucesso',
            "Grupo «{$grupo->nome}» criado. Seus canais e publicações continuam onde estavam."
        );
    }

    public function renomear(GuardarGrupoRequest $request, string $ulid): RedirectResponse
    {
        // ULID alheio simplesmente não é encontrado: o escopo do dono filtra
        // antes, e 404 não confirma que aquele grupo existe.
        $grupo = Grupo::where('ulid', $ulid)->firstOrFail();

        $this->grupos->renomear($grupo, $request->nomeEscolhido());

        return back()->with('sucesso', "Agora ele se chama «{$grupo->nome}».");
    }

    /**
     * ⭐ As hashtags que este grupo já traz escritas ao compor (DEC-152).
     *
     * ⚠️ Gesto próprio, separado do renomear: são duas decisões diferentes, e
     * juntá-las num formulário só faria trocar a hashtag exigir reconfirmar o
     * nome — e vice-versa.
     */
    public function hashtags(GuardarHashtagsDoGrupoRequest $request, string $ulid): RedirectResponse
    {
        $grupo = Grupo::where('ulid', $ulid)->firstOrFail();

        $this->grupos->definirHashtags($grupo, $request->hashtagsEscolhidas());

        return back()->with(
            'sucesso',
            $grupo->hashtags
                ? 'Estas hashtags já vêm escritas ao publicar neste grupo. Você pode mudar cada post.'
                : 'Este grupo não traz mais hashtags escritas.'
        );
    }

    public function excluir(string $ulid): RedirectResponse
    {
        $grupo = Grupo::where('ulid', $ulid)->firstOrFail();
        $nome = $grupo->nome;

        $this->grupos->excluir($grupo);

        // Excluiu o que estava em foco: o resolvedor elege outro sozinho na
        // próxima leitura, em vez de deixar a tela apontando para o nada.
        GrupoCorrente::esquecer();

        return back()->with('sucesso', "Grupo «{$nome}» excluído.");
    }

    /**
     * Troca o grupo em foco.
     *
     * ⛔ POST, nunca GET: com GET o prefetch do navegador trocaria o modo
     * sozinho, e a próxima publicação sairia no lugar errado.
     *
     * ⭐ **`conectar` leva junto para o catálogo de redes.** É o que permite
     * "conectar uma rede neste grupo" a partir da janela de gerenciar: o modo
     * segue a intenção, em vez de a conta nascer num grupo que a pessoa não
     * está olhando — que é o mesmo acidente que o grupo existe para evitar,
     * só que na hora de conectar em vez de na hora de publicar.
     */
    public function usar(Request $request, string $ulid): RedirectResponse
    {
        $grupo = Grupo::where('ulid', $ulid)->firstOrFail();

        GrupoCorrente::definir($grupo);

        /*
          * ⚠️ FLASH, e não parâmetro na URL.
          *
          * Com `?conectar=1` o pedido ficava grudado no endereço, e a
          * atualização automática da tela (que recarrega de poucos em poucos
          * segundos, na mesma URL) reabria o catálogo sem parar. Flash existe
          * por uma requisição só — é exatamente a duração de um recado.
          */
        if ($request->boolean('conectar')) {
            return to_route('painel')->with('abrirCatalogo', true);
        }

        /*
         * ⭐ **`rede` leva à janela DAQUELA rede** (DEC-154) — onde moram
         * desconectar e mover de grupo.
         *
         * ⛔ É o que resolve o beco da janela do grupo sem duplicar a ação: ela
         * mostra as redes de dentro dele e não tinha como agir sobre nenhuma.
         * Repetir "desconectar" ali criaria **dois lugares para o mesmo gesto
         * irreversível** — a receita do "desconectei e continuou aparecendo".
         *
         * ⚠️ Só passa o que é plataforma de verdade: valor inventado viraria um
         * modal que nunca abre, e o silêncio pareceria defeito da tela.
         */
        $rede = Plataforma::tryFrom((string) $request->input('rede'));

        return $rede
            ? to_route('painel')->with('abrirRede', $rede->value)
            : back();
    }

    /**
     * Move um canal de grupo (DEC-77).
     *
     * ⚠️ O histórico não vai junto — as publicações que já saíram continuam
     * apontando para o grupo de onde saíram. Sem isso, o número de um grupo
     * mudaria sozinho quando alguém reorganizasse os canais.
     */
    public function moverCanal(Request $request, string $ulid): RedirectResponse
    {
        $conta = ContaSocial::where('ulid', $ulid)->firstOrFail();

        $destino = Grupo::where('ulid', $request->string('grupo')->toString())->first();

        if (! $destino) {
            throw ValidationException::withMessages([
                'grupo' => 'Escolha um grupo para onde levar este canal.',
            ]);
        }

        $this->grupos->moverCanal($conta, $destino);

        return back()->with(
            'sucesso',
            "«{$conta->nome_exibicao}» agora publica em {$destino->nome}. ".
                'O que já foi publicado continua no histórico de onde saiu.'
        );
    }
}

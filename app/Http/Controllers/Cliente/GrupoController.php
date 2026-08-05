<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\GuardarGrupoRequest;
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

    /** ⛔ POST, nunca GET: com GET o prefetch do navegador trocaria o modo sozinho. */
    public function usar(string $ulid): RedirectResponse
    {
        $grupo = Grupo::where('ulid', $ulid)->firstOrFail();

        GrupoCorrente::definir($grupo);

        return back();
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

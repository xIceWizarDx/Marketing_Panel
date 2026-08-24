<?php

namespace App\Http\Middleware;

use App\Enums\Papel;
use App\Enums\Plataforma;
use App\Enums\StatusConta;
use App\Models\ContaSocial;
use App\Models\Grupo;
use App\Services\ImpersonacaoService;
use App\Support\GrupoCorrente;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Dados enviados ao React em toda visita.
     *
     * ⚠️ Regra de seguranca: o que entra aqui vai pro HTML de TODA pagina.
     * Nada de token de rede, hash de senha ou dado de outro usuario. O usuario
     * e montado campo a campo, de proposito — mandar o model inteiro faria
     * qualquer coluna nova vazar pro navegador sem ninguem perceber.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $usuario = $request->user();
        $impersonacao = app(ImpersonacaoService::class);
        $adminResponsavel = $impersonacao->ativa($request) ? $impersonacao->administrador($request) : null;

        return [
            ...parent::share($request),

            'nomeDoApp' => config('app.name'),

            /*
             * ⭐ O token do formulário — e ele existe por um caso só: a conexão
             * com rede federada (DEC-138).
             *
             * ⚠️ Aquela porta responde com um redirecionamento para **outro
             * domínio** (o servidor Mastodon da pessoa), e requisição XHR não
             * atravessa isso. Precisa ser navegação de verdade, e navegação de
             * verdade precisa do token na página.
             */
            'csrf' => csrf_token(),

            'auth' => [
                'usuario' => $usuario ? [
                    'ulid' => $usuario->ulid,
                    'nome' => $usuario->nome,
                    'email' => $usuario->email,
                    'papel' => $usuario->papel->value,
                    'papelRotulo' => $usuario->papel->rotulo(),
                    'emailVerificado' => $usuario->hasVerifiedEmail(),
                ] : null,
            ],

            /*
             * ⭐ O grupo em foco e a lista para trocar (DEC-71).
             *
             * ⚠️ Chave RAIZ, nunca dentro de `auth`: o guardião de vazamento
             * confere o que sai em `auth.usuario` campo a campo, e enfiar coisa
             * nova ali quebraria a garantia dele sem ninguém notar.
             *
             * Vem `null` para visitante e para o admin — que não publica, e para
             * quem um seletor de grupo seria enfeite sem função.
             */
            'grupos' => $usuario?->papel === Papel::Cliente ? fn () => $this->grupos() : null,

            // Recado de uma requisição só: "abra o catálogo de redes assim que
            // a tela montar". Vem de quem clicou na engrenagem de um grupo.
            'abrirCatalogo' => fn () => (bool) $request->session()->get('abrirCatalogo'),

            // ⭐ O mesmo recado, apontando para UMA rede (DEC-154): é o que leva
            // da janela do grupo até onde desconectar e mover de fato moram.
            'abrirRede' => fn () => $request->session()->get('abrirRede'),

            // Alimenta a tarja fixa de "modo impersonação". Enquanto tiver
            // conteudo, a tela inteira mostra que aquilo nao e a conta de quem
            // esta olhando — e o que impede o admin de agir achando que e o dono.
            'impersonacao' => $adminResponsavel ? [
                'adminNome' => $adminResponsavel->nome,
                'usuarioNome' => $usuario?->nome,
            ] : null,

            'avisos' => [
                'sucesso' => fn () => $request->session()->get('sucesso'),
                'erro' => fn () => $request->session()->get('erro'),
                'aviso' => fn () => $request->session()->get('aviso'),
            ],
        ];
    }

    /**
     * ⭐ O grupo em foco e o que existe dentro de cada um.
     *
     * ⚠️ **Duas consultas, não uma por grupo.** A janela de gerenciar abre por
     * cima de qualquer tela sem pedir nada ao servidor, então tudo o que ela
     * mostra precisa já estar aqui — e isto roda em TODA requisição.
     *
     * @return array{atual: ?array<string, mixed>, lista: list<array<string, mixed>>}
     */
    private function grupos(): array
    {
        /*
         * ⚠️ Só rede CONECTADA. A desconectada não aparece na grade, então
         * contá-la aqui faria a tela dizer "1 rede" num grupo que a pessoa vê
         * vazio — e ainda seguraria a exclusão sem nada explicando (DEC-85).
         */
        $porGrupo = ContaSocial::query()
            ->where('status', '!=', StatusConta::Desconectada)
            ->get(['grupo_id', 'plataforma'])
            ->groupBy('grupo_id');

        return [
            'atual' => GrupoCorrente::grupo()?->only(['ulid', 'nome']),
            'lista' => Grupo::query()
                ->withCount('publicacoes as publicacoes')
                ->oldest('id')
                ->get(['id', 'ulid', 'nome', 'hashtags'])
                ->map(function (Grupo $grupo) use ($porGrupo) {
                    $contas = $porGrupo->get($grupo->id, collect());

                    return [
                        'ulid' => $grupo->ulid,
                        'nome' => $grupo->nome,
                        // ⭐ As que já vêm escritas ao compor neste grupo
                        // (DEC-152) — ponto de partida, nunca carimbo.
                        'hashtags' => $grupo->hashtags ?? [],
                        // As marcas das redes que ele tem — é o que faz um
                        // grupo ser reconhecido sem ler o nome.
                        'plataformas' => $contas->pluck('plataforma')
                            ->map(fn (Plataforma $p) => $p->value)
                            ->unique()->values()->all(),
                        'redes' => $contas->count(),
                        'publicacoes' => $grupo->publicacoes,
                    ];
                })
                ->all(),
        ];
    }
}

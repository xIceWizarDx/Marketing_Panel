<?php

namespace App\Http\Middleware;

use App\Enums\Papel;
use App\Enums\StatusConta;
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
            'grupos' => $usuario?->papel === Papel::Cliente ? fn () => [
                'atual' => GrupoCorrente::grupo()?->only(['ulid', 'nome']),
                /*
                 * ⚠️ `withCount` numa consulta só, e não duas por grupo.
                 *
                 * As contagens vêm porque a janela de gerenciar abre por cima
                 * de qualquer tela, sem pedir nada ao servidor — e são elas que
                 * dizem POR QUE um grupo não pode ser excluído. Sumir com o
                 * botão deixaria a pessoa sem saber o que fazer.
                 */
                'lista' => Grupo::query()
                    ->withCount([
                        // ⚠️ Só rede CONECTADA. A desconectada não aparece na
                        // grade, então contá-la aqui faria a tela dizer "1 rede"
                        // num grupo que a pessoa vê vazio.
                        'contasSociais as redes' => fn ($q) => $q->where('status', '!=', StatusConta::Desconectada->value),
                        'publicacoes as publicacoes',
                    ])
                    ->oldest('id')
                    ->get(['id', 'ulid', 'nome'])
                    ->map->only(['ulid', 'nome', 'redes', 'publicacoes'])
                    ->all(),
            ] : null,

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
}

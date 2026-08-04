<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogImpersonacao;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Histórico de acessos de suporte.
 *
 * Não é enfeite: é a resposta a "quem entrou na minha conta?", que a LGPD dá ao
 * titular o direito de fazer. Registro só de leitura — ninguém edita nem apaga.
 */
class LogImpersonacaoController extends Controller
{
    public function listar(Request $request): Response
    {
        $registros = LogImpersonacao::query()
            // `admin:` e `usuario:` selecionam colunas — Model::shouldBeStrict()
            // estoura se a tela ler qualquer coluna fora desta lista.
            ->with(['admin:id,nome', 'usuario:id,nome'])
            ->latest('iniciada_em')
            ->paginate(30)
            ->through($this->paraTela(...));

        return Inertia::render('admin/impersonacoes', [
            'registros' => $registros,
            'emAndamento' => LogImpersonacao::whereNull('finalizada_em')->count(),
        ]);
    }

    private function paraTela(LogImpersonacao $log): array
    {
        return [
            'id' => $log->id,
            // Depois de a conta ser apagada sobra só o apelido (DEC-44): o
            // evento continua auditável sem guardar dado pessoal.
            'admin' => $log->admin?->nome ?? 'conta removida',
            'usuario' => $log->usuario?->nome ?? 'conta removida',
            'usuarioUlid' => $log->usuario_ulid,
            'iniciadaEm' => $log->iniciada_em?->toIso8601String(),
            'finalizadaEm' => $log->finalizada_em?->toIso8601String(),
            'duracao' => $this->duracaoLegivel($log),
            'emAndamento' => $log->emAndamento(),
            'ip' => $log->ip,
        ];
    }

    private function duracaoLegivel(LogImpersonacao $log): ?string
    {
        if ($log->finalizada_em === null || $log->iniciada_em === null) {
            return null;
        }

        $segundos = $log->iniciada_em->diffInSeconds($log->finalizada_em);

        return $segundos < 60
            ? "{$segundos}s"
            : intdiv($segundos, 60).'min';
    }
}

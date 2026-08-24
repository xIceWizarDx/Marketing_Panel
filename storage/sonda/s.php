<?php
use App\Models\ContaSocial;
use App\Support\ContextoDoUsuario;

ContextoDoUsuario::semEscopo(function () {
    $contas = ContaSocial::withoutGlobalScopes()
        ->whereIn('plataforma', ['facebook', 'instagram'])
        ->with(['credencial', 'grupo' => fn ($q) => $q->withTrashed()])
        ->get();

    if ($contas->isEmpty()) {
        echo "NENHUMA conta da Meta no banco.\n";

        return;
    }

    foreach ($contas as $c) {
        echo sprintf(
            "%-10s %-28s grupo=%-10s status=%-10s cred=%-4s escopos=%s\n",
            $c->plataforma->value,
            mb_substr((string) $c->nome_exibicao, 0, 28),
            $c->grupo?->nome ?? '???',
            $c->status->value,
            $c->credencial ? 'sim' : 'NAO',
            implode(',', (array) ($c->credencial->escopos ?? []))
        );
    }
});

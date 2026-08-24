<?php

use Illuminate\Support\Facades\File;

/*
| Guardiao do PISO TIPOGRAFICO.
|
| ⛔ A fonte base do painel e FLUIDA: `clamp(13px, …, 15px)`. Num telefone ela
| assenta em 13px — e ali `text-xs` (0.75rem) vira 9,75px, e `0.65rem` vira
| 8,4px.
|
| ⚠️ E o que estava nesses tamanhos nao era enfeite: era o numero de
| visualizacoes, a palavra "entregue em baixa", o nome do grupo. O produto
| gastava atencao para dizer a verdade e depois a escrevia pequena demais para
| ser lida.
|
| ⭐ Este teste quebra quando alguem escrever menor de novo — que e onde tem que
| quebrar, antes de virar dívida outra vez.
*/

it('⛔ nenhuma tela escreve abaixo do piso de 0.8125rem', function () {
    $piso = 0.8125;

    $infratores = [];

    foreach (File::allFiles(resource_path('js')) as $arquivo) {
        if (! in_array($arquivo->getExtension(), ['tsx', 'ts'], true)) {
            continue;
        }

        preg_match_all('/text-\[(\d*\.?\d+)rem\]/', (string) file_get_contents($arquivo->getPathname()), $achados, PREG_SET_ORDER);

        foreach ($achados as $achado) {
            if ((float) $achado[1] < $piso) {
                $caminho = str_replace(resource_path('js').DIRECTORY_SEPARATOR, '', $arquivo->getPathname());
                $infratores[] = "{$caminho}: {$achado[0]}";
            }
        }
    }

    expect($infratores)->toBe([], "Texto abaixo do piso:\n".implode("\n", $infratores));
});

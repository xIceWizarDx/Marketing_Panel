<?php

use App\Enums\Papel;
use Illuminate\Support\Facades\Route;

/*
| Guardiao de ROTULOS (DEC-18).
|
| A chave do enum e canonica e nunca muda; o texto da tela vive em
| lang/pt_BR/rotulos.php. Este teste impede o caso mais comum: alguem adiciona
| um valor novo no enum e esquece o rotulo — a tela mostraria "rotulos.papel.x"
| pro usuario final.
*/

it('tem rotulo em portugues para todo papel', function () {
    foreach (Papel::cases() as $papel) {
        $chave = "rotulos.papel.{$papel->value}";

        expect(__($chave))->not->toBe($chave, "Falta o rotulo de '{$papel->value}' em lang/pt_BR/rotulos.php")
            ->and($papel->rotulo())->not->toBeEmpty();
    }
});

it('nunca muda a chave canonica dos papeis', function () {
    // Se este teste falhar, alguem renomeou a chave gravada no banco. Renomear
    // exige migration de dados — nao e so trocar a string no enum.
    expect(array_column(Papel::cases(), 'value'))->toBe(['admin', 'cliente']);
});

it('entrega o papel ao frontend como chave mais rotulo, nunca so o texto', function () {
    $this->actingAs(cliente())
        ->get('/painel')
        ->assertInertia(fn ($pagina) => $pagina
            ->where('auth.usuario.papel', 'cliente')
            ->where('auth.usuario.papelRotulo', 'Cliente'));
});

it('exige que todo papel declare lado e tela inicial', function () {
    // Rede de seguranca para quando entrar `comercial`, `suporte` etc.:
    // papel sem lado ou sem tela inicial valida estoura AQUI, nao em producao.
    foreach (Papel::cases() as $papel) {
        expect($papel->ehOperador())->toBeBool();

        expect(Route::has($papel->rotaInicial()))
            ->toBeTrue("O papel '{$papel->value}' aponta para a rota '{$papel->rotaInicial()}', que nao existe.");
    }
});

it('separa quem opera a plataforma de quem e cliente', function () {
    expect(Papel::Admin->ehOperador())->toBeTrue()
        ->and(Papel::Cliente->ehOperador())->toBeFalse()
        ->and(Papel::listaDeOperadores())->toBe('admin');

    // Cliente NUNCA pode virar operador por engano — seria acesso ao painel
    // da plataforma inteira.
    expect(Papel::operadores())->not->toContain(Papel::Cliente);
});

it('lista os papeis prontos para um campo de selecao', function () {
    expect(Papel::paraSelecao())->toBe([
        ['valor' => 'admin', 'rotulo' => 'Administrador'],
        ['valor' => 'cliente', 'rotulo' => 'Cliente'],
    ]);
});

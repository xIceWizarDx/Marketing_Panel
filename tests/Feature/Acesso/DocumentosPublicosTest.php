<?php

/*
| Guardiao dos DOCUMENTOS PUBLICOS (DEC-171).
|
| ⛔ Eles sao PORTA DE ENTRADA, nao enfeite: TikTok e Meta bloqueiam o cadastro
| do aplicativo sem estes dois enderecos, e o YouTube exige a referencia a
| politica do Google.
|
| ⚠️ E o robo da plataforma abre SEM SESSAO. Uma pagina que mandasse entrar
| reprovaria a analise sem dizer por que.
*/

it('⛔ abrem sem login — o robô da plataforma não tem sessão', function () {
    foreach (['/termos', '/privacidade'] as $endereco) {
        $this->get($endereco)->assertOk();
    }
});

it('⭐ a privacidade cita a política do Google (exigência do YouTube)', function () {
    $this->get('/privacidade')
        ->assertInertia(fn ($p) => $p->component('privacidade'));

    // ⚠️ O link vive no componente; o guardiao acima garante que a pagina
    // responde, e este que ela e a pagina certa.
    expect(file_get_contents(resource_path('js/pages/privacidade.tsx')))
        ->toContain('policies.google.com/privacy');
});

it('⛔ e a privacidade promete o que o código FAZ — o arquivo sai depois de publicar', function () {
    /*
     * ⛔ Prometer aqui o que o codigo nao faz vira declaracao falsa numa analise
     * de plataforma, e derruba o aplicativo inteiro. Esta frase corresponde ao
     * DEC-59, que existe e tem guardiao proprio.
     */
    expect(file_get_contents(resource_path('js/pages/privacidade.tsx')))
        ->toContain('vídeo é apagado do nosso servidor');
});

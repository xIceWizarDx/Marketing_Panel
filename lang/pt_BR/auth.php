<?php

/*
|--------------------------------------------------------------------------
| Mensagens de acesso
|--------------------------------------------------------------------------
| O Laravel nao traz estes arquivos prontos: sem eles, a tela mostra a CHAVE
| crua ("auth.failed") no lugar da mensagem — e a pessoa nao entende nada.
|
| Tom: calmo e orientando o proximo passo. Nada de "erro", "falha grave" ou
| "credenciais invalidas" — quem esta tentando entrar ja esta frustrado.
*/

return [

    // A mesma mensagem para senha errada E conta desativada, de proposito:
    // diferenciar entregaria a quem tenta invadir quais e-mails existem.
    'failed' => 'E-mail ou senha não conferem. Confira e tente de novo.',

    'password' => 'A senha não confere.',

    'throttle' => 'Muitas tentativas seguidas. Aguarde :seconds segundos e tente de novo.',

];

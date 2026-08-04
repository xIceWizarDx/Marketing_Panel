<?php

/*
|--------------------------------------------------------------------------
| Rotulos da interface (DEC-18)
|--------------------------------------------------------------------------
| Este arquivo separa o NOME QUE APARECE do NOME QUE O CODIGO USA.
|
| A chave da esquerda e canonica: esta no banco, nas rotas, nos testes, e nunca
| muda. O texto da direita e so o que o usuario le — pode ser reescrito aqui
| sem tocar em migration, model, controller ou teste.
|
| Foi exatamente esse acoplamento que gerou os drifts em cadeia no projeto
| anterior: renomear um cargo obrigava a renomear coluna, rota e teste juntos.
*/

return [

    'papel' => [
        'admin' => 'Administrador',
        'cliente' => 'Cliente',
    ],

    'tipo_midia' => [
        'video' => 'Vídeo',
        'imagem' => 'Imagem',
    ],

    'plataforma' => [
        'bluesky' => 'Bluesky',
        'linkedin' => 'LinkedIn',
        'youtube' => 'YouTube',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'threads' => 'Threads',
        'tiktok' => 'TikTok',
        'pinterest' => 'Pinterest',
        'x' => 'X',
        'mastodon' => 'Mastodon',
        'discord' => 'Discord',
        'linkedin_pagina' => 'LinkedIn Página',
        'snapchat' => 'Snapchat',
        'google_business' => 'Google Meu Negócio',
    ],

    'situacao_rede' => [
        'disponivel' => 'Pronta para usar',
        'planejada' => 'Aguardando aprovação',
        'em_estudo' => 'Em estudo',
    ],

    'nivel_achado' => [
        'ok' => 'Tudo certo',
        'atencao' => 'Atenção',
        'erro' => 'Não aceita',
    ],

    'status_conta' => [
        // Chave `ativa`, rótulo "Conectada" — DEC-18 em ação: a chave é técnica,
        // o texto é o que faz sentido para quem lê.
        'ativa' => 'Conectada',
        'expirada' => 'Precisa reconectar',
        'erro' => 'Com problema',
        'desconectada' => 'Desconectada',
    ],

    'status_destino' => [
        'pendente' => 'Na fila',
        'aguardando_janela' => 'Aguardando vaga',
        'enviando' => 'Enviando…',
        // ⭐ Nunca diz "publicado" antes de a gente ter conferido na rede (DEC-31).
        'processando' => 'Processando na rede',
        'publicado' => 'No ar',
        'falhou' => 'Falhou',
    ],

    'status_publicacao' => [
        'rascunho' => 'Rascunho',
        'processando' => 'Publicando…',
        'concluida' => 'Publicada',
        'concluida_com_falhas' => 'Publicada com falhas',
        'falhou' => 'Falhou',
    ],

];

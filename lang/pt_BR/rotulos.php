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
        // ⛔ Decidida como fora, com motivo — nao e o mesmo que "em estudo".
        'fora' => 'Fora do escopo',
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
        // ⭐ Esteve no ar e saiu — nem "no ar", nem "falhou" (DEC-148).
        'removido' => 'Saiu do ar',
    ],

    'status_publicacao' => [
        'rascunho' => 'Rascunho',
        'processando' => 'Publicando…',
        'concluida' => 'Publicada',
        'concluida_com_falhas' => 'Publicada com falhas',
        'falhou' => 'Falhou',
    ],

    /*
    | O que cada rede NAO publica — dito com todas as letras (DEC-94).
    |
    | ⭐ E aqui que o produto ganha a discussao das metricas. A alternativa e uma
    | tabela com colunas iguais para todas as redes, que obriga a inventar um
    | valor para a celula que nao existe. Escrever a frase nao fica feio: fica
    | serio, e e o mesmo argumento do "HTTP 200 nao e publicado" aplicado ao
    | numero em vez de aplicado ao post.
    |
    | Rede sem frase aqui simplesmente nao mostra nada — silencio nao promete.
    */
    /*
    | O nome de cada contador, para o cabecalho do grafico de comparacao.
    |
    | ⚠️ Cada rede compara os posts dela pelo numero que ELA publica — YouTube
    | por visualizacao, Bluesky por curtida. Por isso o nome da medida vive
    | aqui, e nao chumbado na tela: o grafico de uma rede nunca diz o nome da
    | medida da outra.
    */
    'metrica' => [
        'visualizacoes' => 'visualizações',
        'curtidas' => 'curtidas',
        'comentarios' => 'comentários',
        'compartilhamentos' => 'compartilhamentos',
    ],

    'nota_de_metrica' => [
        'seguidores' => [
            'youtube' => 'O YouTube arredonda esse número — é assim para todo mundo, inclusive no YouTube Studio.',
        ],
        'post' => [
            'bluesky' => 'O Bluesky não conta visualizações.',
        ],
        /*
         * ⛔ O grau de certeza da PROVA, quando ele é menor que o das outras
         * redes (DEC-106).
         *
         * ⚠️ Aqui não cabe eufemismo: quem publica precisa saber que neste
         * canal a conferência não acontece, para ir olhar quando importar.
         */
        'prova' => [
            'linkedin' => 'O LinkedIn não deixa reler o post pela API, então este link não foi conferido depois de publicado.',
        ],
    ],

];

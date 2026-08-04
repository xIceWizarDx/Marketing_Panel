<?php

return [

    /*
    |--------------------------------------------------------------------------
    | FFmpeg e FFprobe
    |--------------------------------------------------------------------------
    | ⚠️ NÃO são bibliotecas do projeto — são programas do sistema operacional.
    | `composer install` não traz. Precisam estar instalados na máquina de
    | desenvolvimento E no servidor, separadamente.
    |
    | `ffprobe` LÊ o arquivo e diz o que tem dentro (duração, resolução, codec).
    | É o que sustenta o laudo de mídia (DEC-32/33) — o diferencial de dizer
    | ANTES de agendar o que vai acontecer com o vídeo.
    | `ffmpeg` MEXE no arquivo (recodificar áudio quando a rede exigir).
    |
    | O caminho é configurável de propósito: em hospedagem gerenciada o binário
    | costuma estar fora do PATH do usuário do PHP, e depender do PATH faz o
    | recurso falhar em produção funcionando em dev.
    |
    | Como saber onde está:  Windows `where ffprobe`  ·  Linux `which ffprobe`
    */

    'ffprobe' => env('FFPROBE_CAMINHO', 'ffprobe'),
    'ffmpeg' => env('FFMPEG_CAMINHO', 'ffmpeg'),

    /*
    | Tempo máximo que a inspeção pode levar. Arquivo corrompido pode fazer o
    | ffprobe pendurar; sem teto, o upload trava a requisição inteira.
    */
    'tempo_limite_inspecao' => (int) env('MIDIA_TEMPO_LIMITE_INSPECAO', 30),

    /*
    |--------------------------------------------------------------------------
    | Onde os arquivos ficam
    |--------------------------------------------------------------------------
    | Disco `local` = storage/app/private, FORA da raiz pública (0.M Camada 5).
    | Nenhum arquivo enviado pode ser alcançado por URL direta — o acesso passa
    | por rota assinada, que confere o dono.
    */

    'disco' => env('MIDIA_DISCO', 'local'),

    /*
    | Teto de upload. 300 MB é o limite do Instagram (o mais apertado das
    | quatro redes). Não adianta aceitar arquivo que nenhuma rede publica.
    |
    | ⚠️ O servidor precisa concordar: `upload_max_filesize` e `post_max_size`
    | no php.ini, e `client_max_body_size` no nginx. Se o servidor recusar antes,
    | o PHP nem é chamado e o cliente vê erro genérico do servidor.
    */

    'tamanho_maximo_mb' => (int) env('MIDIA_TAMANHO_MAXIMO_MB', 300),

    /*
    |----------------------------------------------------------------------
    | Quanto tempo o arquivo ABANDONADO fica
    |----------------------------------------------------------------------
    |
    | ⚠️ Isto NAO e carencia. O arquivo publicado sai no instante em que o ultimo
    | destino termina — o produto nao guarda acervo (DEC-59).
    |
    | Este prazo vale so para quem enviou e desistiu no meio: o arquivo precisa
    | sobreviver enquanto a pessoa escreve a legenda. Passado isso, e lixo.
    |
    | ⚠️ O registro NUNCA e apagado. Some so o arquivo pesado; miniatura, laudo,
    | links e prova ficam.
    |
    */
    'limpar_abandonado_em_dias' => (int) env('MIDIA_LIMPAR_ABANDONADO_EM_DIAS', 1),

];

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Watchdog do motor.
|
| De cinco em cinco minutos, devolve a fila os destinos que travaram no meio do
| envio (worker morto, deploy, servidor reiniciado). Sem isto a tela mostra
| "Enviando..." para sempre e ninguem descobre que o post nunca saiu.
|
| ⚠️ Precisa do cron do servidor apontando para `schedule:run` — em maquina nova,
| conferir com `php artisan schedule:list`.
*/
Schedule::command('motor:resgatar-orfaos')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

/*
| Reconferencia das contas do YouTube — exigencia das Politicas do Desenvolvedor.
|
| A politica manda atualizar o dado guardado E confirmar a autorizacao a cada
| 30 dias. Roda todo dia, mas so mexe em conta parada ha mais de 25 — a folga
| cobre o dia em que o agendador nao rodar.
*/
Schedule::command('youtube:reconferir')
    ->dailyAt('04:20')
    ->withoutOverlapping()
    ->runInBackground();

/*
| Renovacao do token do Threads.
|
| ⚠️ E a unica rede do produto onde o token MORRE DE VEZ: vale 60 dias e so
| renova entre as 24h de idade e o vencimento. Passou, nao ha renovacao — so
| reconectar.
|
| Roda todo dia e mexe com 15 dias de folga, nao no dia do vencimento: com folga,
| uma semana de servidor desligado ainda cabe dentro da janela.
*/
Schedule::command('threads:renovar')
    ->dailyAt('04:50')
    ->withoutOverlapping()
    ->runInBackground();

/*
| TikTok: rede de seguranca, NAO o mecanismo principal.
|
| ⚠️ O token daqui vive 24 horas — quem mantem a conexao viva de verdade e o
| `TokenDoTiktok`, chamado na hora de publicar (DEC-118). Este comando resolve o
| outro lado: conta que fica SEM publicar, cujo `refresh_token` de 365 dias
| venceria em silencio.
|
| ⚠️ 04:55 e nao 04:50: separado do Threads para saber qual dos dois falhou, no
| dia em que um deles falhar.
*/
Schedule::command('tiktok:renovar')
    ->dailyAt('04:55')
    ->withoutOverlapping()
    ->runInBackground();

/*
| X: mesma logica do TikTok, prazo ainda mais curto (2 horas).
|
| ⚠️ Quem mantem a conexao viva de verdade e o `TokenDoX`, chamado na hora de
| publicar (DEC-130). Este comando cuida da conta que fica SEM publicar.
*/
Schedule::command('x:renovar')
    ->dailyAt('05:00')
    ->withoutOverlapping()
    ->runInBackground();

/*
| Os contadores que cada rede publica.
|
| Uma vez por dia, e nunca dentro da requisicao da tela: a tela mostra o que esta
| guardado, com a data da leitura. Chamar a rede no meio do carregamento faria a
| pagina travar no dia em que a rede estivesse lenta.
|
| ⚠️ 05:10 e nao 04:20: a reconferencia do YouTube roda antes e consome a MESMA
| cota. Separar os dois deixa claro qual dos dois estourou, no dia em que
| estourar.
*/
Schedule::command('metricas:atualizar')
    ->dailyAt('05:10')
    ->withoutOverlapping()
    ->runInBackground();

/*
| ⭐ A PROVA QUE CONTINUA (DEC-145).
|
| ⛔ A conciliacao pergunta 20 vezes e para — cerca de 3h30. Moderacao de rede
| nao trabalha nesse relogio: sem este comando, um video derrubado no dia
| seguinte continuava marcado como "No ar".
|
| ⚠️ 05:30 e nao junto com as metricas: as duas releem post, e separa-las deixa
| claro qual das duas estourou cota, no dia em que uma estourar.
*/
Schedule::command('publicacoes:reconferir')
    ->dailyAt('05:30')
    ->withoutOverlapping()
    ->runInBackground();

/*
 * Rede de seguranca, nao a regra.
 *
 * A regra e o motor: o arquivo sai no instante em que a publicacao termina
 * (DEC-59). Este comando pega o que sobrou — envio abandonado no meio e o que
 * escapou do motor. De madrugada porque nao ha pressa: ninguem esta esperando.
 *
 * O registro nunca e apagado — some so o video pesado.
 */
Schedule::command('midias:liberar')
    ->dailyAt('04:40')
    ->withoutOverlapping();

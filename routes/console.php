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

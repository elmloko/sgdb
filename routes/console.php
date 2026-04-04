<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduler — SLA de bugs
|--------------------------------------------------------------------------
|
| Para que el scheduler funcione, el servidor debe tener configurado un
| cron que ejecute cada minuto:
|
|   * * * * * cd /ruta/del/proyecto && php artisan schedule:run >> /dev/null 2>&1
|
| En desarrollo local puede iniciarse con:  php artisan schedule:work
|
*/
Schedule::command('sla:verificar')
    ->everyFifteenMinutes()
    ->withoutOverlapping()          // evita ejecuciones concurrentes si tarda más de 15 min
    ->runInBackground()             // no bloquea otros comandos del scheduler
    ->appendOutputTo(storage_path('logs/sla-verificar.log'));

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * ⏰ ZAMANLANMIŞ GÖREVLER (CRON SCHEDULE)
 * Her gece saat 00:00'da günlük işletme raporunu otomatik çalıştırır.
 */
Schedule::command('report:daily')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));


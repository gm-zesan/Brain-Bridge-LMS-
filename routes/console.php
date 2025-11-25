<?php

use App\Jobs\CheckTeacherPromotions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;

Artisan::command('teachers:check-promotions', function () {
    CheckTeacherPromotions::dispatch();
})->purpose('Check and update teacher promotion levels');

app(Schedule::class)->command('teachers:check-promotions')->dailyAt('00:00')->withoutOverlapping()->onOneServer();
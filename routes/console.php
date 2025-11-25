<?php

use App\Jobs\CheckTeacherPromotions;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;

Artisan::command('teachers:check-promotions', function () {
    CheckTeacherPromotions::dispatch();
    $this->info('Teacher promotion check dispatched!');
});

app(Schedule::class)->command('teachers:check-promotions')->daily();

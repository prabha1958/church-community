<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\SendAnniversaryGreetings;
use App\Console\Commands\SendBirthdayWishes;
use Illuminate\Support\Facades\Schedule;

Artisan::command('greetings:anniversary {--date=}', function () {
    $this->call(SendAnniversaryGreetings::class, [
        '--date' => $this->option('date'),
    ]);
});


Artisan::command('send:birthday-wishes', function () {
    $this->call(SendBirthdayWishes::class);
});

Schedule::command('send:birthday-wishes')
    ->dailyAt('08:00')
    ->withoutOverlapping();

Schedule::command('greetings:anniversary')
    ->dailyAt('08:10')
    ->withoutOverlapping();

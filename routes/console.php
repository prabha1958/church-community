<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\SendAnniversaryGreetings;
use App\Console\Commands\SendBirthdayWishes;

Artisan::command('greetings:anniversary {--date=}', function () {
    $this->call(SendAnniversaryGreetings::class, [
        '--date' => $this->option('date'),
    ]);
});


Artisan::command('send:birthday-wishes', function () {
    $this->call(SendBirthdayWishes::class,);
});

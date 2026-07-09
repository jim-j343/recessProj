<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// SDD 5.1 Member Inactivity & Blacklisting Component — runs once a day.
Schedule::command('members:check-inactivity')->daily();

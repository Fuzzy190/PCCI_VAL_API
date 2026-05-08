<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Default Laravel inspire command
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule your custom membership commands to run daily at midnight
Schedule::command('memberships:check-expiring')->daily();
Schedule::command('memberships:update-statuses')->daily();

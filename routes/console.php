<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Remove activity logs older than ActivityLog::KEEP_DAYS (needs the "schedule:run" cron).
Schedule::command('model:prune', ['--model' => [App\Models\ActivityLog::class]])->daily();

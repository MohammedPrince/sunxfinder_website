<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Remove activity logs older than ActivityLog::KEEP_DAYS (needs the "schedule:run" cron).
Schedule::command('model:prune', ['--model' => [App\Models\ActivityLog::class]])->daily();

// Automatic notifications (Admin → Notifications → Automatic): sends schedules that are due.
Artisan::command('notifications:send-scheduled', function () {
    $summary = app(App\Services\Admin\AdminService::class)->runDueNotificationSchedules();

    foreach ($summary['messages'] as $line) {
        $this->line($line);
    }

    $this->info("Due: {$summary['due']} · sent: {$summary['sent']} · failed: {$summary['failed']}");
})->purpose('Send automatic notifications that are due now');

Schedule::command('notifications:send-scheduled')->everyMinute()->withoutOverlapping(10);

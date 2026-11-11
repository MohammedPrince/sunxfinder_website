<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Remove old activity logs and automatic-notification run logs (needs the "schedule:run" cron).
Schedule::command('model:prune', ['--model' => [
    App\Models\ActivityLog::class,
    App\Models\NotificationScheduleRun::class,
]])->daily();

// Automatic notifications (Admin → Notifications → Automatic): sends schedules that are due.
Artisan::command('notifications:send-scheduled', function () {
    $service = app(App\Services\Admin\AdminService::class);
    // Lets Admin → Background jobs show whether the scheduler cron is running.
    $service->stampSchedulerHeartbeat();
    $summary = $service->runDueNotificationSchedules();

    foreach ($summary['messages'] as $line) {
        $this->line($line);
    }

    $this->info("Due: {$summary['due']} · sent: {$summary['sent']} · failed: {$summary['failed']}");
})->purpose('Send automatic notifications that are due now');

Schedule::command('notifications:send-scheduled')->everyMinute()->withoutOverlapping(10);

// A finished project the customer neither confirms nor reports a problem on is confirmed
// automatically App\Models\Project::AUTO_CONFIRM_DAYS days after the company finished it.
Artisan::command('projects:auto-confirm-handovers', function () {
    $summary = App\Support\ProjectHandover::autoConfirmDue();
    $this->info("Handovers confirmed automatically: {$summary['confirmed']} · failed: {$summary['failed']}");
})->purpose('Confirm handovers the customer left unanswered for ' . App\Models\Project::AUTO_CONFIRM_DAYS . ' days');

Schedule::command('projects:auto-confirm-handovers')->hourly()->withoutOverlapping(30);

// Plain notifications leave the history and the customers' inbox after
// Notification::AUTO_DELETE_DAYS (admin and company). Campaigns, sponsored sends and package offers are kept.
Artisan::command('notifications:delete-old', function () {
    $count = app(App\Services\Admin\AdminService::class)->deleteOldNotifications();
    $this->info("Deleted {$count} notifications older than " . App\Models\Notification::AUTO_DELETE_DAYS . ' days.');
})->purpose('Delete plain notifications older than the keep period');

Schedule::command('notifications:delete-old')->dailyAt('01:00')->withoutOverlapping(30);

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One attempt to send an automatic notification; listed in Admin → Background jobs. */
class NotificationScheduleRun extends Model
{
    use Prunable;

    protected $table = 'tbl_notification_schedule_runs';

    public const UPDATED_AT = null;

    public const KEEP_DAYS = 180;

    public const STATUSES = [
        'done' => 'Done',
        'partial' => 'Partly delivered',
        'failed' => 'Failed',
    ];

    public const STATUS_PILLS = [
        'done' => 'pill-green',
        'partial' => 'pill-yellow',
        'failed' => 'pill-red',
    ];

    protected $fillable = [
        'schedule_id',
        'schedule_name',
        'message_id',
        'message_order',
        'message_title',
        'notification_id',
        'trigger',
        'status',
        'recipients',
        'devices',
        'delivered',
        'failed_devices',
        'error',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'message_order' => 'integer',
            'recipients' => 'integer',
            'devices' => 'integer',
            'delivered' => 'integer',
            'failed_devices' => 'integer',
            'duration_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(NotificationSchedule::class, 'schedule_id');
    }

    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(self::KEEP_DAYS));
    }
}

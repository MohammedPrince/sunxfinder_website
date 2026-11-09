<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One message of an automatic notification schedule, sent in sort_order. */
class NotificationScheduleMessage extends Model
{
    use LogsActivity;

    protected $table = 'tbl_notification_schedule_messages';

    protected $fillable = [
        'schedule_id',
        'sort_order',
        'is_active',
        'title_en',
        'title_ar',
        'message_en',
        'message_ar',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'sent_count' => 'integer',
            'last_sent_at' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(NotificationSchedule::class, 'schedule_id');
    }
}

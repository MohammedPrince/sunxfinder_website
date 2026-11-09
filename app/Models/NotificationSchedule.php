<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Automatic notifications: sends its messages one by one, in order, on the chosen weekdays,
 * at most once every `every_days` days, at `send_time` in `timezone`.
 *
 * Progress fields (last_*, sent_count) are written with query-builder updates by the sender,
 * so they do not fill the activity log; admin edits are logged as usual.
 */
class NotificationSchedule extends Model
{
    use LogsActivity;

    protected $table = 'tbl_notification_schedules';

    /** Carbon dayOfWeek => label, in the order shown in the admin (Saturday first). */
    public const WEEKDAYS = [
        6 => 'Saturday',
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
    ];

    /** Same keys as the quick notification form. */
    public const TYPES = [
        'general' => 'General announcement',
        'promotion' => 'Promotion / news',
        'account' => 'Account and security',
        'support' => 'Messages and support',
        'maintenance' => 'Maintenance notice',
    ];

    public const AFTER_LAST = [
        'stop' => 'Stop after the last message',
        'repeat' => 'Start again from the first message',
    ];

    public const DEFAULT_TIMEZONE = 'Africa/Khartoum';

    /** How far ahead the "next send" date is looked for. */
    private const LOOKAHEAD_DAYS = 90;

    protected $fillable = [
        'name',
        'is_active',
        'days',
        'every_days',
        'send_time',
        'timezone',
        'starts_on',
        'ends_on',
        'notification_type',
        'audience_type',
        'audience_city',
        'after_last',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'days' => 'array',
            'every_days' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'last_sent_on' => 'date',
            'last_run_on' => 'date',
            'last_sent_at' => 'datetime',
            'sent_count' => 'integer',
            'last_message_order' => 'integer',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(NotificationScheduleMessage::class, 'schedule_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'notification_schedule_id');
    }

    public function localNow(?Carbon $now = null): Carbon
    {
        return ($now ?? now())->copy()->setTimezone($this->timezone ?: self::DEFAULT_TIMEZONE);
    }

    /** Weekday numbers, always ints. */
    public function dayNumbers(): array
    {
        return array_values(array_map('intval', (array) $this->days));
    }

    /** "Every day", or "Saturday, Monday, Wednesday" in Saturday-first order. */
    public function daysLabel(bool $short = false): string
    {
        $days = $this->dayNumbers();

        if (count($days) === 7) {
            return 'Every day';
        }

        $names = [];

        foreach (self::WEEKDAYS as $number => $name) {
            if (in_array($number, $days, true)) {
                $names[] = $short ? substr($name, 0, 3) : $name;
            }
        }

        return $names ? implode(', ', $names) : 'No days';
    }

    public function frequencyLabel(): string
    {
        $every = max(1, (int) $this->every_days);

        return ($every === 1 ? 'Every chosen day' : "Every {$every} days")
            . ' at ' . $this->send_time;
    }

    /** The message that will go out next, or null when a "stop" schedule has sent its last one. */
    public function nextMessage(): ?NotificationScheduleMessage
    {
        $active = $this->messages()->where('is_active', true);

        if ($this->last_message_order === null) {
            return $active->first();
        }

        $next = (clone $active)
            ->where(function ($query) {
                $query->where('sort_order', '>', $this->last_message_order)
                    ->orWhere(function ($same) {
                        $same->where('sort_order', $this->last_message_order)
                            ->where('id', '>', (int) $this->last_message_id);
                    });
            })
            ->first();

        if ($next) {
            return $next;
        }

        return $this->after_last === 'repeat' ? $active->first() : null;
    }

    /** True when a send is due right now: right day, time reached, not yet tried today. */
    public function isDueAt(?Carbon $now = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $local = $this->localNow($now);
        $today = $local->toDateString();

        if ($this->last_run_on?->toDateString() === $today) {
            return false;
        }

        return $this->dateAllowed($local->copy()->startOfDay())
            && $local->format('H:i') >= $this->send_time;
    }

    /** Local date and time of the next send, or null when nothing more will be sent. */
    public function nextSendAt(?Carbon $now = null): ?Carbon
    {
        if (empty($this->dayNumbers()) || !$this->nextMessage()) {
            return null;
        }

        $local = $this->localNow($now);
        [$hour, $minute] = array_map('intval', explode(':', $this->send_time));

        for ($offset = 0; $offset <= self::LOOKAHEAD_DAYS; $offset++) {
            $day = $local->copy()->startOfDay()->addDays($offset);

            if (!$this->dateAllowed($day)) {
                continue;
            }

            if ($offset === 0 && $this->last_run_on?->toDateString() === $day->toDateString()) {
                continue;
            }

            // Today after the send time and not yet tried: it goes out within a minute.
            return $day->setTime($hour, $minute);
        }

        return null;
    }

    /** active | paused | finished | ended | empty: shown as a pill in the admin. */
    public function state(?Carbon $now = null): string
    {
        $today = $this->localNow($now)->toDateString();

        if (!$this->messages()->where('is_active', true)->exists()) {
            return 'empty';
        }

        if ($this->ends_on && $this->ends_on->toDateString() < $today) {
            return 'ended';
        }

        if (!$this->nextMessage()) {
            return 'finished';
        }

        return $this->is_active ? 'active' : 'paused';
    }

    /** Weekday, date range and "every N days" rules for one local day. */
    private function dateAllowed(Carbon $day): bool
    {
        $date = $day->toDateString();

        if ($this->starts_on && $date < $this->starts_on->toDateString()) {
            return false;
        }

        if ($this->ends_on && $date > $this->ends_on->toDateString()) {
            return false;
        }

        if (!in_array($day->dayOfWeek, $this->dayNumbers(), true)) {
            return false;
        }

        if ($this->last_sent_on) {
            $lastSent = Carbon::parse($this->last_sent_on->toDateString(), $day->getTimezone());
            $daysSince = (int) round($lastSent->diffInDays($day, false));

            if ($daysSince < max(1, (int) $this->every_days)) {
                return false;
            }
        }

        return true;
    }
}

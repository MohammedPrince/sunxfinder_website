<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
     use SoftDeletes, LogsActivity;
    protected $table = 'tbl_notifications';

    protected $fillable = [
        'sender_type',
        'sender_id',
        'type',
        'title_en',
        'title_ar',
        'message_en',
        'message_ar',
        'audience_type',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    // sender_id points to a company or an admin depending on sender_type.
    public function senderCompany(): BelongsTo
    {
        return $this->belongsTo(SolarCompany::class, 'sender_id')->withTrashed();
    }

    public function senderAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'sender_id');
    }

    /** Display name of whoever published it: the company name, or "Admin · name". */
    public function senderLabel(): string
    {
        if ($this->sender_type === 'company') {
            return $this->senderCompany?->name ?? 'Company #' . $this->sender_id;
        }

        // tbl_admins has a username but no name column.
        $adminName = $this->senderAdmin?->username;

        return $adminName ? 'Admin · ' . $adminName : 'Admin';
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(
            CustomerNotification::class,
            'notification_id'
        );
    }
}

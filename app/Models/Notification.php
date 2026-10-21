<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
     use SoftDeletes;
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

    public function recipients(): HasMany
    {
        return $this->hasMany(
            CustomerNotification::class,
            'notification_id'
        );
    }
}

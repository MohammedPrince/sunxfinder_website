<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SentEmail extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_sent_emails';

    protected $fillable = [
        'recipient_email',
        'recipient_name',
        'subject',
        'message',
        'lang',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }
}

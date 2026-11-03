<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    protected $table = 'tbl_visitors';

    protected $fillable = [
        'session_id',
        'country',
    ];
}

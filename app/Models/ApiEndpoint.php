<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * An API server the mobile app can call (e.g. DEV, PRD). Exactly one is active at a time;
 * is_online lets the admin take any of them offline (the app then shows the offline message).
 */
class ApiEndpoint extends Model
{
    use LogsActivity;

    protected $table = 'tbl_api_endpoints';

    /** Cache key for GET /api/v1/api-config (cleared whenever an admin changes an endpoint). */
    public const CONFIG_CACHE_KEY = 'api-config';

    protected $fillable = [
        'name',
        'base_url',
        'is_active',
        'is_online',
        'offline_message_en',
        'offline_message_ar',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_online' => 'boolean',
        ];
    }
}

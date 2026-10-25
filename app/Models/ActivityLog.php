<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class ActivityLog extends Model
{
    use Prunable;

    protected $table = 'tbl_activity_logs';

    // Append-only: no updated_at.
    public const UPDATED_AT = null;

    /** Logs older than this are removed by the daily "model:prune" task. */
    public const KEEP_DAYS = 365;

    public const ACTOR_TYPES = [
        'admin' => 'Admin',
        'company' => 'Company',
        'customer' => 'Customer',
        'guest' => 'Guest',
        'system' => 'System',
    ];

    public const ACTIONS = [
        'login' => 'Signed in',
        'login_failed' => 'Failed sign-in',
        'login_blocked' => 'Sign-in blocked',
        'logout' => 'Signed out',
        'registered' => 'Signed up',
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'restored' => 'Restored',
        'force_deleted' => 'Permanently deleted',
        'password_changed' => 'Password changed',
        'account_deleted' => 'Account deleted',
        'cancelled' => 'Cancelled',
        'reassigned' => 'Reassigned',
        'nudged' => 'Reminder sent',
        'email_sent' => 'Email sent',
        'job_retried' => 'Job retried',
        'job_deleted' => 'Job deleted',
    ];

    public const CATEGORIES = [
        'auth' => 'Sign-in & security',
        'admins' => 'Admins',
        'customers' => 'Customers',
        'companies' => 'Companies',
        'quotes' => 'Quotes & offers',
        'projects' => 'Projects',
        'ratings' => 'Ratings',
        'notifications' => 'Notifications',
        'catalog' => 'Products & suppliers',
        'app' => 'App settings',
        'system' => 'Background jobs',
        'other' => 'Other',
    ];

    protected $fillable = [
        'actor_type',
        'actor_id',
        'actor_name',
        'action',
        'category',
        'subject_type',
        'subject_id',
        'subject_label',
        'description',
        'properties',
        'ip_address',
        'user_agent',
        'method',
        'url',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(self::KEEP_DAYS));
    }

    /** Pill color for the action, matching the admin panel's pill classes. */
    public function actionClass(): string
    {
        return match ($this->action) {
            'login', 'registered', 'created', 'restored' => 'pill-green',
            'login_failed', 'login_blocked', 'deleted', 'force_deleted', 'account_deleted', 'cancelled', 'job_deleted' => 'pill-red',
            'password_changed', 'nudged', 'reassigned', 'job_retried' => 'pill-orange',
            'logout' => 'pill-purple',
            default => 'pill-blue',
        };
    }
}

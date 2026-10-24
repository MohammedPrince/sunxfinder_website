<?php

namespace App\Models\Concerns;

use App\Support\ActivityLogger;
use Illuminate\Support\Str;

/**
 * Logs created / updated / deleted / restored events of a model to the activity log,
 * with the changed fields (old -> new). Bulk query-builder updates and deletes fire no
 * model events, so they are not logged here.
 */
trait LogsActivity
{
    /** Never stored in the log. */
    protected static array $activitySecretFields = ['password', 'remember_token', 'fcm_token', 'token_hash'];

    /** Changes to only these are not worth a log row. */
    protected static array $activityIgnoredFields = ['created_at', 'updated_at', 'deleted_at', 'last_login_at', 'last_used_at'];

    public static function bootLogsActivity(): void
    {
        static::created(fn($model) => $model->logModelActivity('created'));
        static::updated(fn($model) => $model->logModelActivity('updated'));

        static::deleted(function ($model) {
            $soft = method_exists($model, 'isForceDeleting') && !$model->isForceDeleting();
            $model->logModelActivity($soft ? 'deleted' : 'force_deleted');
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(fn($model) => $model->logModelActivity('restored'));
        }
    }

    protected function logModelActivity(string $event): void
    {
        $name = ActivityLogger::subjectName($this);
        $label = ActivityLogger::labelFor($this);

        if ($event === 'updated') {
            $changed = array_keys($this->getChanges());

            // A password change gets its own row, without any value.
            if (array_intersect($changed, ['password'])) {
                ActivityLogger::log('password_changed', "Password changed for {$name} \"{$label}\"", $this, [], null, 'auth');
            }

            $fields = array_values(array_diff($changed, static::$activitySecretFields, static::$activityIgnoredFields));

            if (empty($fields)) {
                return;
            }

            $changes = [];

            foreach ($fields as $field) {
                $changes[$field] = [
                    'old' => $this->activityValue($this->getOriginal($field)),
                    'new' => $this->activityValue($this->getAttribute($field)),
                ];
            }

            // e.g. Updated company "Red Sea Solar" (status: pending → approved, is_verified: No → Yes)
            $summary = collect($changes)->take(3)->map(
                fn($change, $field) => "{$field}: " . $this->activityShort($change['old']) . ' → ' . $this->activityShort($change['new'])
            )->implode(', ');

            if (count($changes) > 3) {
                $summary .= ', +' . (count($changes) - 3) . ' more';
            }

            ActivityLogger::log('updated', "Updated {$name} \"{$label}\" ({$summary})", $this, ['changes' => $changes]);

            return;
        }

        $actor = ActivityLogger::currentActor();

        // A new customer/company row with nobody signed in is a sign-up.
        if ($event === 'created' && $actor['type'] === 'guest' && in_array($name, ['customer', 'company'], true)) {
            ActivityLogger::log('registered', 'New ' . $name . ' signed up: "' . $label . '"', $this, [], ActivityLogger::actorFor($this));

            return;
        }

        $verb = [
            'created' => 'Created',
            'deleted' => 'Deleted',
            'force_deleted' => 'Permanently deleted',
            'restored' => 'Restored',
        ][$event];

        $properties = $event === 'created'
            ? ['attributes' => collect($this->getAttributes())
                ->except(array_merge(static::$activitySecretFields, static::$activityIgnoredFields))
                ->map(fn($value) => $this->activityValue($value))
                ->all()]
            : [];

        ActivityLogger::log($event, "{$verb} {$name} \"{$label}\"", $this, $properties, $actor);
    }

    /** JSON-safe, short value for the log. */
    private function activityValue($value)
    {
        return match (true) {
            $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i:s'),
            $value instanceof \BackedEnum => $value->value,
            is_bool($value), is_int($value), is_float($value), $value === null => $value,
            is_array($value) || is_object($value) => Str::limit(json_encode($value), 300),
            default => Str::limit(strip_tags((string) $value), 300),
        };
    }

    private function activityShort($value): string
    {
        return match (true) {
            $value === null || $value === '' => '—',
            is_bool($value) => $value ? 'Yes' : 'No',
            default => Str::limit((string) $value, 40),
        };
    }
}

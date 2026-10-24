<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\SolarCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes rows to tbl_activity_logs. Logging must never break the action being
 * logged, so every failure is reported and swallowed.
 */
class ActivityLogger
{
    /** Model class => [readable name, category]. */
    public const SUBJECTS = [
        \App\Models\Admin::class => ['admin account', 'admins'],
        \App\Models\Customer::class => ['customer', 'customers'],
        \App\Models\SolarCompany::class => ['company', 'companies'],
        \App\Models\Quote::class => ['quote request', 'quotes'],
        \App\Models\CompanyQuotation::class => ['offer', 'quotes'],
        \App\Models\Project::class => ['project', 'projects'],
        \App\Models\ProjectUpdate::class => ['project update', 'projects'],
        \App\Models\Rating::class => ['rating', 'ratings'],
        \App\Models\RatingQuestion::class => ['rating question', 'ratings'],
        \App\Models\RatingValue::class => ['rating value', 'ratings'],
        \App\Models\Notification::class => ['push notification', 'notifications'],
        \App\Models\Product::class => ['product', 'catalog'],
        \App\Models\Supplier::class => ['supplier', 'catalog'],
        \App\Models\ApplianceGroup::class => ['calculator group', 'app'],
        \App\Models\CompanyCarousel::class => ['carousel entry', 'app'],
        \App\Models\AppVersion::class => ['app version', 'app'],
        \App\Models\Setting::class => ['setting', 'app'],
    ];

    /**
     * @param array{type: string, id: ?int, name: ?string}|null $actor Defaults to whoever is signed in.
     */
    public static function log(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?array $actor = null,
        ?string $category = null
    ): void {
        try {
            $actor ??= self::currentActor();
            $request = self::httpRequest();

            ActivityLog::query()->create([
                'actor_type' => $actor['type'],
                'actor_id' => $actor['id'],
                'actor_name' => $actor['name'] !== null ? Str::limit($actor['name'], 188) : null,
                'action' => $action,
                'category' => $category ?? self::categoryFor($subject),
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'subject_label' => $subject ? Str::limit(self::labelFor($subject), 188) : null,
                'description' => Str::limit($description, 497),
                'properties' => $properties ?: null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? Str::limit((string) $request->userAgent(), 252) : null,
                'method' => $request?->method(),
                'url' => $request ? Str::limit($request->fullUrl(), 497) : null,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Actor array for a known account, e.g. right after a login. */
    public static function actorFor(?Model $user): array
    {
        return match (true) {
            $user instanceof Admin => ['type' => 'admin', 'id' => (int) $user->id, 'name' => $user->username],
            $user instanceof SolarCompany => ['type' => 'company', 'id' => (int) $user->id, 'name' => $user->name ?: $user->username],
            $user instanceof Customer => ['type' => 'customer', 'id' => (int) $user->id, 'name' => $user->full_name ?: $user->username],
            default => ['type' => 'guest', 'id' => null, 'name' => null],
        };
    }

    public static function guest(?string $name = null): array
    {
        return ['type' => 'guest', 'id' => null, 'name' => $name];
    }

    /**
     * Who is acting: picked by the part of the site the request is on, so an admin
     * who is also signed in as a company in the same browser is logged correctly.
     */
    public static function currentActor(): array
    {
        $request = self::httpRequest();

        if (!$request) {
            return ['type' => 'system', 'id' => null, 'name' => 'System'];
        }

        $guards = match (true) {
            $request->is('admin', 'admin/*') => ['admin'],
            $request->is('company', 'company/*') => ['company'],
            $request->is('api/*') => ['sanctum'],
            default => ['admin', 'company', 'sanctum'],
        };

        foreach ($guards as $guard) {
            $user = auth($guard)->user();

            if ($user) {
                return self::actorFor($user);
            }
        }

        return self::guest();
    }

    public static function labelFor(Model $subject): string
    {
        foreach (['name', 'title_en', 'quote_name', 'full_name', 'username', 'label_en', 'key', 'platform'] as $attribute) {
            $value = $subject->getAttribute($attribute);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '#' . $subject->getKey();
    }

    public static function subjectName(Model $subject): string
    {
        return self::SUBJECTS[get_class($subject)][0] ?? Str::headline(class_basename($subject));
    }

    private static function categoryFor(?Model $subject): string
    {
        return $subject ? (self::SUBJECTS[get_class($subject)][1] ?? 'other') : 'other';
    }

    /** The current HTTP request, or null for queue jobs, cron and the console. */
    private static function httpRequest(): ?\Illuminate\Http\Request
    {
        $request = request();

        return $request && $request->route() ? $request : null;
    }
}

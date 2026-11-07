<?php

namespace App\Support;

use App\Models\Appliance;
use Illuminate\Support\Str;
use Throwable;

/**
 * A load from the website calculator, checked and cleaned before it is saved on a
 * customer and handed to the app. Contract: sunxfinder_app/docs/website-load-handoff.md
 *
 *   {
 *     "id": "uuid", "source": "website", "created_at": "ISO 8601",
 *     "backup": "none|night|full",
 *     "items": [{"appliance_id": 15, "quantity": 2, "hours": 8}]
 *   }
 */
class CalculatorLoad
{
    public const BACKUPS = ['none', 'night', 'full'];
    public const MAX_ITEMS = 100;
    public const MAX_INPUT_BYTES = 10000;

    /**
     * Builds a load from what the browser sent (JSON text). Returns null when there is
     * nothing usable, so a bad or empty value never blocks sign-up.
     *
     * Only active appliances are kept. Quantities are 1-99 and hours 0-24 in half hours.
     * The same appliance twice is merged.
     */
    public static function fromInput(mixed $input): ?array
    {
        if (!is_string($input) || $input === '' || strlen($input) > self::MAX_INPUT_BYTES) {
            return null;
        }

        try {
            $data = json_decode($input, true, 8, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        if (!is_array($data) || !is_array($data['items'] ?? null)) {
            return null;
        }

        $items = [];

        foreach (array_slice($data['items'], 0, self::MAX_ITEMS) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = filter_var($row['appliance_id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($row['quantity'] ?? null, FILTER_VALIDATE_INT);
            $hours = is_numeric($row['hours'] ?? null) ? (float) $row['hours'] : null;

            if (!$id || $id < 1 || !$quantity || $quantity < 1 || $hours === null) {
                continue;
            }

            if (isset($items[$id])) {
                $items[$id]['quantity'] = min(99, $items[$id]['quantity'] + $quantity);
                continue;
            }

            $items[$id] = [
                'appliance_id' => $id,
                'quantity' => min(99, $quantity),
                'hours' => round(max(0, min(24, $hours)) * 2) / 2,
            ];
        }

        if (!$items) {
            return null;
        }

        $activeIds = Appliance::query()
            ->whereIn('id', array_keys($items))
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        $items = array_values(array_intersect_key($items, array_flip($activeIds)));

        if (!$items) {
            return null;
        }

        $backup = in_array($data['backup'] ?? null, self::BACKUPS, true) ? $data['backup'] : 'night';

        return [
            'id' => (string) Str::uuid(),
            'source' => 'website',
            'created_at' => now()->toIso8601String(),
            'backup' => $backup,
            'items' => $items,
        ];
    }

    /**
     * A load from an "open in app" link (?load=…, base64url JSON), cleaned the same way.
     * Null when it is missing or unusable.
     */
    public static function fromLinkParam(mixed $param): ?array
    {
        if (!is_string($param) || $param === '' || strlen($param) > self::MAX_INPUT_BYTES
            || !preg_match('/\A[A-Za-z0-9_-]+\z/', $param)) {
            return null;
        }

        $json = base64_decode(strtr($param, '-_', '+/'), true);

        return $json === false ? null : self::fromInput($json);
    }

    /** The ?load= value for the app link: backup and items only, base64url without padding. */
    public static function toLinkParam(array $load): string
    {
        $json = json_encode(['backup' => $load['backup'], 'items' => $load['items']], JSON_UNESCAPED_SLASHES);

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }
}

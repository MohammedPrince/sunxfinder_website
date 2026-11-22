<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * Warranty and terms a company adds to its offer on a quote request
 * (stored as JSON in tbl_company_quotations.terms).
 *
 * Every field is optional. Stored shape, after normalize():
 *  [
 *    'warranties' => ['panels' => ['value' => 10, 'unit' => 'years'], 'inverter' => …, 'batteries' => …, 'installation' => …],
 *    'installation_days' => 3,
 *    'free_maintenance_months' => 12,
 *    'payment_terms' => 'deposit_50',
 *    'validity_days' => 14,
 *    'included' => ['delivery', 'installation', …],
 *    'extras' => [['en' => 'Free 24/7 phone support', 'ar' => 'دعم هاتفي مجاني على مدار الساعة']],
 *  ]
 *
 * lines() turns it into ready-to-show label/value pairs in English and Arabic,
 * used by the company page, the invoice, the admin page and the customer API.
 */
final class OfferTerms
{
    public const WARRANTY_PARTS = ['panels', 'inverter', 'batteries', 'installation'];
    public const UNITS = ['years', 'months'];
    public const PAYMENT_TERMS = ['on_completion', 'deposit_50', 'deposit_30', 'full_upfront', 'installments'];
    public const INCLUDED = ['delivery', 'installation', 'mounting', 'cabling', 'testing', 'site_visit', 'training', 'after_sales'];
    public const VALIDITY_OPTIONS = [3, 7, 14, 30, 60, 90];
    public const MAX_EXTRAS = 10;

    private const LANG = 'company/messages.offer_terms.';

    /** Validation rules for the offer form; the form posts everything under terms[…]. */
    public static function rules(): array
    {
        return [
            'terms' => ['nullable', 'array'],
            'terms.warranties' => ['nullable', 'array'],
            'terms.warranties.*.value' => ['nullable', 'integer', 'min:1', 'max:360'],
            'terms.warranties.*.unit' => ['nullable', Rule::in(self::UNITS)],
            'terms.installation_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'terms.free_maintenance_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'terms.payment_terms' => ['nullable', Rule::in(self::PAYMENT_TERMS)],
            'terms.validity_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'terms.included' => ['nullable', 'array'],
            'terms.included.*' => [Rule::in(self::INCLUDED)],
            'terms.extras' => ['nullable', 'array', 'max:' . self::MAX_EXTRAS],
            'terms.extras.*.en' => ['nullable', 'string', 'max:150'],
            'terms.extras.*.ar' => ['nullable', 'string', 'max:150'],
        ];
    }

    /** Readable field names for validation messages. */
    public static function attributes(): array
    {
        $attributes = [
            'terms.installation_days' => __(self::LANG . 'installation_time'),
            'terms.free_maintenance_months' => __(self::LANG . 'free_maintenance'),
            'terms.validity_days' => __(self::LANG . 'validity'),
            'terms.payment_terms' => __(self::LANG . 'payment'),
            'terms.extras' => __(self::LANG . 'extras'),
        ];

        foreach (self::WARRANTY_PARTS as $part) {
            $attributes["terms.warranties.$part.value"] = __(self::LANG . 'warranty_' . $part);
        }

        return $attributes;
    }

    /**
     * Keeps only known, filled fields. Returns null when nothing was filled,
     * so an offer without terms stores NULL rather than an empty object.
     */
    public static function normalize(?array $input): ?array
    {
        if (!$input) {
            return null;
        }

        $terms = [];

        $warranties = [];
        foreach (self::WARRANTY_PARTS as $part) {
            $value = (int) ($input['warranties'][$part]['value'] ?? 0);
            if ($value > 0) {
                $unit = $input['warranties'][$part]['unit'] ?? 'years';
                $warranties[$part] = [
                    'value' => $value,
                    'unit' => in_array($unit, self::UNITS, true) ? $unit : 'years',
                ];
            }
        }
        if ($warranties) {
            $terms['warranties'] = $warranties;
        }

        foreach (['installation_days', 'free_maintenance_months', 'validity_days'] as $key) {
            $value = (int) ($input[$key] ?? 0);
            if ($value > 0) {
                $terms[$key] = $value;
            }
        }

        if (in_array($input['payment_terms'] ?? null, self::PAYMENT_TERMS, true)) {
            $terms['payment_terms'] = $input['payment_terms'];
        }

        // Kept in the fixed order of INCLUDED, so the display does not depend on click order.
        $included = array_values(array_intersect(self::INCLUDED, (array) ($input['included'] ?? [])));
        if ($included) {
            $terms['included'] = $included;
        }

        $extras = [];
        foreach (array_slice((array) ($input['extras'] ?? []), 0, self::MAX_EXTRAS) as $extra) {
            $en = trim((string) ($extra['en'] ?? ''));
            $ar = trim((string) ($extra['ar'] ?? ''));
            if ($en !== '' || $ar !== '') {
                $extras[] = ['en' => $en ?: null, 'ar' => $ar ?: null];
            }
        }
        if ($extras) {
            $terms['extras'] = $extras;
        }

        return $terms ?: null;
    }

    /**
     * Ready-to-show lines in both languages:
     *  [['key' => 'warranty_panels', 'label' => 'Panels warranty', 'ar_label' => 'ضمان الألواح', 'value' => '10 years', 'ar_value' => '10 سنوات'], …]
     */
    public static function lines(?array $terms): array
    {
        if (!$terms) {
            return [];
        }

        $lines = [];
        $add = function (string $key, string $labelKey, callable $value) use (&$lines) {
            $lines[] = [
                'key' => $key,
                'label' => __(self::LANG . $labelKey, [], 'en'),
                'ar_label' => __(self::LANG . $labelKey, [], 'ar'),
                'value' => $value('en'),
                'ar_value' => $value('ar'),
            ];
        };

        foreach (self::WARRANTY_PARTS as $part) {
            if ($warranty = $terms['warranties'][$part] ?? null) {
                $add('warranty_' . $part, 'warranty_' . $part,
                    fn($lang) => self::duration((int) $warranty['value'], $warranty['unit'] ?? 'years', $lang));
            }
        }

        if ($days = $terms['installation_days'] ?? null) {
            $add('installation_time', 'installation_time',
                fn($lang) => __(self::LANG . 'within', ['duration' => self::duration((int) $days, 'days', $lang)], $lang));
        }

        if ($months = $terms['free_maintenance_months'] ?? null) {
            $add('free_maintenance', 'free_maintenance', fn($lang) => self::duration((int) $months, 'months', $lang));
        }

        if ($payment = $terms['payment_terms'] ?? null) {
            $add('payment_terms', 'payment', fn($lang) => __(self::LANG . 'payment_' . $payment, [], $lang));
        }

        if ($included = $terms['included'] ?? null) {
            $add('included', 'included', fn($lang) => collect($included)
                ->map(fn($item) => __(self::LANG . 'included_' . $item, [], $lang))
                ->implode($lang === 'ar' ? '، ' : ', '));
        }

        foreach ($terms['extras'] ?? [] as $extra) {
            // Written by the company: one language falls back to the other.
            $en = $extra['en'] ?? null;
            $ar = $extra['ar'] ?? null;
            $add('extra', 'extra', fn($lang) => $lang === 'ar' ? ($ar ?: $en) : ($en ?: $ar));
        }

        if ($validity = $terms['validity_days'] ?? null) {
            $add('validity', 'validity', fn($lang) => self::duration((int) $validity, 'days', $lang));
        }

        return $lines;
    }

    /** "10 years" / "10 سنوات", with Arabic number agreement (سنة واحدة، سنتان، 3 سنوات، 11 سنة). */
    public static function duration(int $count, string $unit, string $lang): string
    {
        return trans_choice(self::LANG . 'unit_' . $unit, $count, ['count' => $count], $lang);
    }
}

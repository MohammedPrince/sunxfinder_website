<?php

if (! function_exists('asset_v')) {
    /**
     * asset() URL with a cache-busting version (a hash of the file contents).
     *
     * Static files are served with a 1-year cache (see public/.htaccess). When a
     * file changes, its hash changes, so browsers and CDNs fetch the new copy even
     * when deployment preserves the file's modification time.
     */
    function asset_v(string $path): string
    {
        static $versions = [];

        $file = public_path($path);
        if (! array_key_exists($path, $versions)) {
            $versions[$path] = is_file($file) ? hash_file('sha256', $file) : null;
        }

        return asset($path) . ($versions[$path] ? '?v=' . $versions[$path] : '');
    }
}

if (! function_exists('company_portal')) {
    /** True on /company pages, the only panel that follows the Arabic language choice. */
    function company_portal(): bool
    {
        return request()->is('company', 'company/*');
    }
}

if (! function_exists('company_text')) {
    /**
     * For views and classes shared with admin: the company translation on /company pages,
     * and the given English everywhere else, so admin keeps its wording.
     */
    function company_text(string $key, string $fallback, array $replace = []): string
    {
        return company_portal() ? __('company/messages.' . $key, $replace) : $fallback;
    }
}

if (! function_exists('company_label')) {
    /** A label for a stored value, or the given text when no translation exists for that value. */
    function company_label(string $key, string $fallback): string
    {
        return trans()->has('company/messages.' . $key) ? __('company/messages.' . $key) : $fallback;
    }
}

if (! function_exists('company_date')) {
    /** Same idea for dates: month and day names are translated on /company pages only. */
    function company_date(\DateTimeInterface $date, string $format): string
    {
        $date = \Illuminate\Support\Carbon::instance($date);

        return company_portal() ? $date->translatedFormat($format) : $date->format($format);
    }
}

if (! function_exists('name_initials')) {
    /**
     * Avatar letters for a name. Prefers English letters, so "تسلا - Tesla" gives "T";
     * a name with none falls back to its first characters, never a broken byte.
     */
    function name_initials(?string $name, int $length = 1, string $fallback = '?'): string
    {
        $name = trim((string) $name);

        if (preg_match_all('/[A-Za-z]/', $name, $letters) && $letters[0]) {
            return strtoupper(implode('', array_slice($letters[0], 0, $length)));
        }

        return mb_strtoupper(mb_substr($name, 0, $length)) ?: $fallback;
    }
}

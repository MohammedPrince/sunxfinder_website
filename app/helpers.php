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

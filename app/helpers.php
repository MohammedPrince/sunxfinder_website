<?php

if (! function_exists('asset_v')) {
    /**
     * asset() URL with a cache-busting version (the file's modification time).
     *
     * Static files are served with a 1-year cache (see public/.htaccess). When a
     * file changes, its mtime changes, so the URL changes and browsers fetch the
     * new copy instead of a stale cached one.
     */
    function asset_v(string $path): string
    {
        $file = public_path($path);
        $version = is_file($file) ? filemtime($file) : null;

        return asset($path) . ($version ? '?v=' . $version : '');
    }
}

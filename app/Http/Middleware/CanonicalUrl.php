<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * One address per page: http://, www. and /index.php copies of the live site move permanently (301)
 * to https://sunxfinder.com/..., so search engines don't see duplicate pages. Other hosts (local,
 * staging) keep their scheme. App-link verification files must never redirect.
 */
class CanonicalUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->isMethodSafe() || $request->is('.well-known/*')) {
            return $next($request);
        }

        $host = strtolower((string) config('seo.canonical_host'));
        $isLiveSite = $host !== '' && in_array($request->getHost(), [$host, 'www.' . $host], true);

        $base = $request->getBaseUrl();
        $hasIndexPhp = str_ends_with($base, '/index.php');
        $wrongOrigin = $isLiveSite && (!$request->isSecure() || $request->getHost() !== $host);

        if (!$hasIndexPhp && !$wrongOrigin) {
            return $next($request);
        }

        $origin = $isLiveSite ? 'https://' . $host : $request->getSchemeAndHttpHost();
        $base = $hasIndexPhp ? substr($base, 0, -strlen('/index.php')) : $base;
        // The raw query string: nothing is re-encoded (ad and app links carry their own values).
        $query = (string) $request->server('QUERY_STRING', '');

        return redirect()->away(
            $origin . $base . '/' . ltrim($request->getPathInfo(), '/') . ($query !== '' ? '?' . $query : ''),
            301
        );
    }
}

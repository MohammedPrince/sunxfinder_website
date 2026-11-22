<?php

namespace App\Http\Middleware;

use App\Support\Seo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tells search engines not to index the admin and company panels (logins included),
 * the app hand-off and ad-click URLs, or anything at all when SEO_INDEXABLE=false.
 */
class SearchEngineHeaders
{
    private const PRIVATE_PATHS = ['admin', 'admin/*', 'company', 'company/*', 'open/*', 'go/*', 'download/*'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!Seo::indexable() || $request->is(...self::PRIVATE_PATHS)) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}

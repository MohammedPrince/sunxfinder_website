<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = ['en', 'ar'];
        $requested = $request->query('lang');
        $cookieLocale = $request->cookie('locale');

        if (in_array($requested, $allowed, true)) {
            $locale = $requested;
        } else {
            $locale = in_array($cookieLocale, $allowed, true) ? $cookieLocale : 'en';
        }

        App::setLocale($locale);
        $response = $next($request);

        // Save only an explicit language selection; do not overwrite a user's choice on every request.
        if (in_array($requested, $allowed, true)) {
            $response->headers->setCookie(cookie(
                'locale',
                $locale,
                60 * 24 * 365,
                '/',
                null,
                $request->isSecure(),
                false,
                false,
                'Lax'
            ));
        }

        return $response;
    }
}

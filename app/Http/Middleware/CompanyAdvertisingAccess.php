<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Advertising pages open only for companies allowed in Admin → Settings. */
class CompanyAdvertisingAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Setting::companyAdsEnabled((int) auth('company')->id()), 404);

        return $next($request);
    }
}

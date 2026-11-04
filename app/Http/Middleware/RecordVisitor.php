<?php

namespace App\Http\Middleware;

use App\Services\MainService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Website visitor counter: records the visitor on public pages and shares
 * $visitorCount with the footer (visitors.layouts.app). The maintenance page
 * counts its own visits in SiteMaintenance.
 */
class RecordVisitor
{
    /** Pages that count a visit. Redirects, downloads and app-link files don't. */
    private const PAGES = [
        'home',
        'company-profile.show',
        'customer.register',
        'company.register',
        'terms',
        'privacy-policy',
        'delete-account',
    ];

    public function __construct(private MainService $mainService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->routeIs(...self::PAGES)) {
            View::share('visitorCount', $this->mainService->countVisit());
        }

        return $next($request);
    }
}

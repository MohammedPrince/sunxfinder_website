<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyAuthenticated
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $guard = Auth::guard('company');

        // Redirect unauthenticated visitors to company login.
        if (!$guard->check()) {
            return redirect()->route('company.login');
        }

        $company = $guard->user();

        // Allow active companies that are pending or approved.
        // Verification is not required for dashboard access.
        $isAllowed = (bool) $company->is_active
            && in_array($company->status, ['pending', 'approved'], true);

        if (!$isAllowed) {
            $guard->logout();

            $request->session()->forget('company_id');
            $request->session()->regenerateToken();

            return redirect()
                ->route('company.login')
                ->withErrors([
                    'login' => 'Your company account is inactive or not eligible to log in.',
                ]);
        }

        return $next($request);
    }
}

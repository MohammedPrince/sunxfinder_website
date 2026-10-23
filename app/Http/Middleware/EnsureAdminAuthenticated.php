<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $guard = Auth::guard('admin');

        // Logged-out visitors go to the admin login.
        if (!$guard->check()) {
            return redirect()->route('admin.login');
        }

        $admin = $guard->user();

        // Reject disabled administrator accounts.
        if (!$admin->is_active) {
            $guard->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('admin.login')
                ->withErrors([
                    'login' => 'Your administrator account is inactive.',
                ]);
        }

        return $next($request);
    }
}

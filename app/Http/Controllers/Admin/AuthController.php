<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{

    public function showLogin()
    {
        $admin = Auth::guard('admin')->user();

        if ($admin && $admin->is_active) {
            return redirect()->route('admin.dashboard');
        }

        if ($admin) {
            Auth::guard('admin')->logout();
        }

        return view('admin.auth.login', [
            'loginTitle' => 'Admin Sign In',
            'portalLabel' => 'SUNXFINDER ADMIN PORTAL',
            'portalDescription' => 'Manage solar providers, customer enquiries, and the connections that move clean energy forward.',
            'loginFieldLabel' => 'Admin Username',
            'loginPlaceholder' => 'Enter your admin username',
            'loginAction' => route('admin.login.submit'),
            'securityTitle' => 'Secure administrator access',
            'securityMessage' => 'Only authorized administrators can access this workspace.',
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:191'],
            'password' => ['required', 'string'],
        ]);

        $credentials['is_active'] = true;

        if (
            Auth::guard('admin')->attempt(
                $credentials,
                $request->boolean('remember')
            )
        ) {
            $request->session()->regenerate();

            $admin = Auth::guard('admin')->user();

            $admin->forceFill([
                'last_login_at' => now(),
            ])->save();

            return redirect()->route('admin.dashboard.page');
        }

        return back()
            ->withErrors([
                'login' => 'Invalid username or password, or your administrator account is inactive.',
            ])
            ->onlyInput('username');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}

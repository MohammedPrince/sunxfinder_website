<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('company')->check()) {
            $company = Auth::guard('company')->user();

            if (
                $company->is_active &&
                $company->is_verified &&
                $company->status === 'approved'
            ) {
                return redirect()->route('company.dashboard');
            }

            Auth::guard('company')->logout();
        }

        return view('admin.auth.login', [
            'loginTitle' => 'Company Sign In',
            'portalLabel' => 'SUNXFINDER COMPANY PORTAL',
            'portalDescription' =>
                'Manage your solar company profile and reach customers looking for solar solutions.',
            'loginFieldLabel' => 'Company Username',
            'loginPlaceholder' => 'Enter your company username',
            'loginAction' => route('company.login.submit'),
            'securityTitle' => 'Secure company access',
            'securityMessage' =>
                'Only approved and active company accounts can access this workspace.',
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:191'],
            'password' => ['required', 'string'],
        ]);

        // Only active accounts can log in.
        $credentials['is_active'] = true;

        if (
            Auth::guard('company')->attempt(
                $credentials,
                $request->boolean('remember')
            )
        ) {
            $company = Auth::guard('company')->user();

            // Allow pending and approved companies, verified or not.
            if (!in_array($company->status, ['pending', 'approved'], true)) {
                Auth::guard('company')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withErrors([
                        'login' => 'Your company account is not eligible to log in.',
                    ])
                    ->onlyInput('username');
            }

            $request->session()->regenerate();

            // Store the company ID in the session.
            $request->session()->put('company_id', $company->id);

            // Redirect to the company dashboard.
            return redirect()->intended(
                route('company.dashboard')
            );
        }

        return back()
            ->withErrors([
                'login' => 'Invalid username or password, or your company account is inactive.',
            ])
            ->onlyInput('username');
    }


    public function logout(Request $request)
    {
        Auth::guard('company')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('company.login');
    }
}
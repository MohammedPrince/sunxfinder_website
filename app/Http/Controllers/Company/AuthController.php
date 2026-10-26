<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Concerns\HandlesPasswordReset;
use App\Http\Controllers\Controller;
use App\Services\Company\CompanyService;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use HandlesPasswordReset;

    protected $companyService;

    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }
    public function showLogin(Request $request)
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

            // This browser only: keep the company signed in on its other devices.
            Auth::guard('company')->logoutCurrentDevice();
            $request->session()->forget(['company_id', 'admin_company_impersonation']);
        }

        return view('admin.auth.login', array_merge($this->portalViewData(), [
            'loginFieldLabel' => 'Company Username',
            'loginPlaceholder' => 'Enter your company username',
            'loginAction' => route('company.login.submit'),
            'forgotPasswordAction' => route('company.password.email'),
            'accountLabel' => 'company',
        ]));
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
                ActivityLogger::log('login_blocked', "Company sign-in blocked: account is {$company->status}", $company, [
                    'status' => $company->status,
                ], ActivityLogger::actorFor($company), 'auth');

                Auth::guard('company')->logout();
                $request->session()->forget(['company_id', 'admin_company_impersonation']);

                $request->session()->regenerateToken();

                return back()
                    ->withErrors([
                        'login' => 'Your company account is not eligible to log in.',
                    ])
                    ->onlyInput('username');
            }

            $request->session()->regenerate();

            // Store the company ID in the session.
            $request->session()->forget('admin_company_impersonation');
            $request->session()->put('company_id', $company->id);

            ActivityLogger::log('login', 'Signed in to the company panel', $company, [], ActivityLogger::actorFor($company), 'auth');

            // Redirect to the company dashboard.
            return redirect()->intended(
                route('company.dashboard')
            );
        }

        ActivityLogger::log('login_failed', "Failed company sign-in for \"{$credentials['username']}\"", null, [
            'username' => $credentials['username'],
        ], ActivityLogger::guest($credentials['username']), 'auth');

        return back()
            ->withErrors([
                'login' => 'Invalid username or password, or your company account is inactive.',
            ])
            ->onlyInput('username');
    }


    public function logout(Request $request)
    {
        $guard = Auth::guard('company');
        $impersonation = $request->session()->pull('admin_company_impersonation');

        if ($company = $guard->user()) {
            ActivityLogger::log(
                $impersonation ? 'admin_company_logout' : 'logout',
                $impersonation ? "Admin signed out of company {$company->name}" : 'Signed out of the company panel',
                $company,
                [],
                $impersonation ? ActivityLogger::actorFor(Auth::guard('admin')->user()) : ActivityLogger::actorFor($company),
                'auth'
            );
        }

        if (!$impersonation) {
            $guard->logout();
            $request->session()->forget('company_id');
            $request->session()->regenerateToken();

            return redirect()->route('company.login');
        }

        // Admin session: only this browser leaves the company. logoutCurrentDevice()
        // keeps the company's remember token, so its own devices stay signed in.
        $previousCompanyId = $impersonation['previous_company_id'] ?? null;
        $previousCompany = $previousCompanyId
            ? \App\Models\SolarCompany::query()->find($previousCompanyId)
            : null;

        if (
            $previousCompany &&
            $previousCompany->is_active &&
            in_array($previousCompany->status, ['pending', 'approved'], true)
        ) {
            // Restore the real company without clearing its "remember me" cookie.
            $guard->login($previousCompany);
            $request->session()->put('company_id', $previousCompany->id);
        } else {
            $guard->logoutCurrentDevice();
            $request->session()->forget('company_id');
        }

        $request->session()->regenerateToken();

        return $previousCompany && Auth::guard('company')->check()
            ? redirect()->route('company.dashboard')
            : redirect()->route('company.login');
    }

    // Branding shared by the login and reset password pages.
    private function portalViewData(): array
    {
        return [
            'loginTitle' => 'Company Sign In',
            'portalLabel' => 'SUNXFINDER COMPANY PORTAL',
            'portalDescription' =>
                'Manage your solar company profile and reach customers looking for solar solutions.',
            'securityTitle' => 'Secure company access',
            'securityMessage' =>
                'Only approved and active company accounts can access this workspace.',
        ];
    }

    //Password Reset (see HandlesPasswordReset)

    protected function passwordResetPortal(): string
    {
        return 'company';
    }

    protected function passwordResetAccountLabel(): string
    {
        return 'company';
    }

    protected function passwordResetEmailExists(string $email): bool
    {
        return $this->companyService->companyEmailExists($email);
    }

    protected function sendPasswordResetOtp(string $email, string $locale): array
    {
        return $this->companyService->sendPasswordResetOtp($email, $locale);
    }

    protected function resetPasswordWithOtp(string $email, string $code, string $password): array
    {
        return $this->companyService->resetPasswordWithOtp($email, $code, $password);
    }

    protected function passwordResetViewData(): array
    {
        return $this->portalViewData();
    }
}

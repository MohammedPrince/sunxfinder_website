<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Concerns\HandlesPasswordReset;
use App\Http\Controllers\Controller;
use App\Services\Company\CompanyService;
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
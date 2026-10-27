<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesPasswordReset;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminService;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use HandlesPasswordReset;

    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function showLogin()
    {
        $admin = Auth::guard('admin')->user();

        if ($admin && $admin->is_active) {
            return redirect()->route('admin.dashboard.page');
        }

        if ($admin) {
            Auth::guard('admin')->logout();
        }

        return view('admin.auth.login', array_merge($this->portalViewData(), [
            'loginFieldLabel' => 'Admin Username',
            'loginPlaceholder' => 'Enter your admin username',
            'loginAction' => route('admin.login.submit'),
            'forgotPasswordAction' => route('admin.password.email'),
            'accountLabel' => 'administrator',
        ]));
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

            ActivityLogger::log('login', 'Signed in to the admin panel', $admin, [], ActivityLogger::actorFor($admin), 'auth');

            return redirect()->route('admin.dashboard.page');
        }

        ActivityLogger::log('login_failed', "Failed admin sign-in for \"{$credentials['username']}\"", null, [
            'username' => $credentials['username'],
        ], ActivityLogger::guest($credentials['username']), 'auth');

        return back()
            ->withErrors([
                'login' => 'Invalid username or password, or your administrator account is inactive.',
            ])
            ->onlyInput('username');
    }

    public function logout(Request $request)
    {
        if ($admin = Auth::guard('admin')->user()) {
            ActivityLogger::log('logout', 'Signed out of the admin panel', $admin, [], ActivityLogger::actorFor($admin), 'auth');
        }

        Auth::guard('admin')->logout();

        $request->session()->forget('admin_id');
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    // Branding shared by the login and reset password pages.
    private function portalViewData(): array
    {
        return [
            'loginTitle' => 'Admin Sign In',
            'portalLabel' => 'SUNXFINDER ADMIN PORTAL',
            'portalDescription' => 'Manage solar providers, customer enquiries, and the connections that move clean energy forward.',
            'securityTitle' => 'Secure administrator access',
            'securityMessage' => 'Only authorized administrators can access this workspace.',
        ];
    }

    //Password Reset (see HandlesPasswordReset)

    protected function passwordResetPortal(): string
    {
        return 'admin';
    }

    protected function passwordResetAccountLabel(): string
    {
        return 'administrator';
    }

    protected function passwordResetEmailExists(string $email): bool
    {
        return $this->adminService->adminEmailExists($email);
    }

    protected function sendPasswordResetOtp(string $email, string $locale): array
    {
        return $this->adminService->sendPasswordResetOtp($email, $locale);
    }

    protected function resetPasswordWithOtp(string $email, string $code, string $password): array
    {
        return $this->adminService->resetPasswordWithOtp($email, $code, $password);
    }

    protected function passwordResetViewData(): array
    {
        return $this->portalViewData();
    }
}

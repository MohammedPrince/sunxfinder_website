<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * Forgot password with an emailed 6-digit OTP, shared by the admin and company portals.
 *
 * Flow: login modal checks the email and sends a code -> reset page (OTP + new password)
 * -> back to the login page. The email being reset is kept in the session, never trusted
 * from the form.
 */
trait HandlesPasswordReset
{
    /** Portal key: "admin" or "company" (also the route prefix and OTP type). */
    abstract protected function passwordResetPortal(): string;

    /** Account wording used in messages, e.g. "administrator" or "company". */
    abstract protected function passwordResetAccountLabel(): string;

    abstract protected function passwordResetEmailExists(string $email): bool;

    abstract protected function sendPasswordResetOtp(string $email, string $locale): array;

    abstract protected function resetPasswordWithOtp(string $email, string $code, string $password): array;

    /** Branding for the reset page (same keys as the login page). */
    abstract protected function passwordResetViewData(): array;

    // Step 1 (login modal): check the email and email a code when it exists.
    public function checkResetEmail(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:191'],
        ]);

        $email = strtolower(trim($data['email']));
        $label = $this->passwordResetAccountLabel();

        if (!$this->passwordResetEmailExists($email)) {
            $message = "No {$label} account is registered with this email.";

            return $request->expectsJson()
                ? response()->json(['success' => true, 'exists' => false, 'message' => $message])
                : back()->withErrors(['email' => $message]);
        }

        $result = $this->sendPasswordResetOtp($email, app()->getLocale());

        if (!$result['success']) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'exists' => true, 'message' => $result['message']], 503)
                : back()->withErrors(['email' => $result['message']]);
        }

        $this->rememberPasswordResetRequest($request, $email, $result['expires_at']);

        $redirect = route($this->passwordResetPortal() . '.password.reset');

        return $request->expectsJson()
            ? response()->json([
                'success' => true,
                'exists' => true,
                'message' => $result['message'],
                'redirect' => $redirect,
            ])
            : redirect($redirect)->with('status', $result['message']);
    }

    // Step 2: page to enter the code and the new password.
    public function showResetPassword(Request $request)
    {
        $pending = $request->session()->get($this->passwordResetSessionKey());

        if (!$pending) {
            return redirect()
                ->route($this->passwordResetPortal() . '.login')
                ->withErrors(['email' => 'Start by entering your registered email under "Forgot password?".']);
        }

        return view('admin.auth.reset-password', array_merge($this->passwordResetViewData(), [
            'resetEmail' => $pending['email'],
            'expiresAt' => $pending['expires_at'],
            'resetAction' => route($this->passwordResetPortal() . '.password.update'),
            'resendAction' => route($this->passwordResetPortal() . '.password.resend'),
            'loginUrl' => route($this->passwordResetPortal() . '.login'),
            'accountLabel' => $this->passwordResetAccountLabel(),
        ]));
    }

    // Step 3: verify the code and save the new password.
    public function resetPassword(Request $request)
    {
        $pending = $request->session()->get($this->passwordResetSessionKey());

        if (!$pending) {
            return redirect()
                ->route($this->passwordResetPortal() . '.login')
                ->withErrors(['email' => 'Your reset request has ended. Please start again.']);
        }

        $data = $request->validate([
            'otp' => ['required', 'digits:6'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ], [
            'otp.required' => 'Enter the 6-digit code from your email.',
            'otp.digits' => 'The code must be exactly 6 digits.',
        ]);

        $result = $this->resetPasswordWithOtp($pending['email'], $data['otp'], $data['password']);

        if (!$result['success']) {
            return back()->withErrors(['otp' => $result['message']]);
        }

        $request->session()->forget($this->passwordResetSessionKey());

        return redirect()
            ->route($this->passwordResetPortal() . '.login')
            ->with('status', $result['message']);
    }

    // Send a fresh code (the previous one stops working).
    public function resendResetOtp(Request $request)
    {
        $pending = $request->session()->get($this->passwordResetSessionKey());

        if (!$pending) {
            return redirect()->route($this->passwordResetPortal() . '.login');
        }

        $result = $this->sendPasswordResetOtp($pending['email'], app()->getLocale());

        if (!$result['success']) {
            return back()->withErrors(['otp' => $result['message']]);
        }

        $this->rememberPasswordResetRequest($request, $pending['email'], $result['expires_at']);

        return back()->with('status', 'A new code was sent to your email. It expires in 5 minutes.');
    }

    private function passwordResetSessionKey(): string
    {
        return 'password_reset.' . $this->passwordResetPortal();
    }

    private function rememberPasswordResetRequest(Request $request, string $email, $expiresAt): void
    {
        $request->session()->put($this->passwordResetSessionKey(), [
            'email' => $email,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }
}

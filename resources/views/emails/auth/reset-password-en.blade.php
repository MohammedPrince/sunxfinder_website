<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Reset your SunXFinder password</title>
</head>

@php
    $privacyUrl = url('/privacy-policy') . '?lang=en';
    $termsUrl = url('/terms') . '?lang=en';
@endphp

<body style="margin:0;padding:0;background:#f4f6f8;color:#10366b;font-family:Arial,Helvetica,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">Your SunXFinder password reset code is
        {{ $code }}. It expires in 5 minutes.</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f6f8;">
        <tr>
            <td align="center" style="padding:24px 10px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
                    style="width:100%;max-width:600px;background:#fff;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:22px 28px;background:#fff;" align="left">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="left" valign="middle" width="140">
                                        <img src="https://dev.sunxfinder.com/visitors/images/sunxfinder-logo-email.png"
                                            alt="SunXFinder" width="180"
                                            style="display:block;border:0;max-width:180px;height:auto;">
                                    </td>
                                    <td align="right" valign="middle"
                                        style="font-size:14px;line-height:1.7;color:#10366b;font-weight:bold;">Account
                                        Security<br><span style="font-weight:normal;">Password reset request</span>
                                        @include('emails.partials.brand-tagline', ['emailTaglineLocale' => 'en'])
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#fff0d8;padding:32px 30px 30px;">
                            <div style="font-size:30px;line-height:1.2;font-weight:800;color:#10366b;">
                                Reset your <span style="color:#f15b24;">password</span></div>
                            <p style="font-size:16px;line-height:1.7;color:#243b60;margin:17px 0 8px;">
                                <strong>Hi {{ $recipientName ?: 'there' }},</strong>
                            </p>
                            <p style="font-size:15px;line-height:1.7;color:#243b60;margin:0 0 22px;">We received a
                                request to reset the password for your SunXFinder {{ $accountLabel }} account. Enter
                                this code on the reset page:</p>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td align="center"
                                        style="background:#ffffff;border:2px dashed #ff5a0a;border-radius:12px;padding:16px 28px;">
                                        <div
                                            style="font-family:'Courier New',Courier,monospace;font-size:36px;line-height:1;font-weight:bold;letter-spacing:10px;color:#10366b;">
                                            {{ $code }}</div>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size:14px;line-height:1.7;color:#b54708;margin:20px 0 0;text-align:center;">
                                ⏱ This code expires in <strong>5 minutes</strong> and can only be used once.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 28px;background:#fff;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="background:#eaf3ff;border-radius:12px;">
                                <tr>
                                    <td width="56" align="center" style="padding:16px 4px 16px 16px;font-size:26px;">🔒
                                    </td>
                                    <td style="padding:16px 16px 16px 8px;">
                                        <strong style="font-size:15px;color:#10366b;">Didn't request this?</strong>
                                        <div style="font-size:13px;line-height:1.6;color:#526783;margin-top:4px;">You
                                            can safely ignore this email. Your password will not change. Never share
                                            this code with anyone, including SunXFinder staff.</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#fff0d8;padding:18px 22px;text-align:center;">
                            <div style="font-size:18px;font-weight:bold;color:#10366b;">Sun<b
                                    style="color:#f15b24;">X</b>Finder</div>
                            <div style="font-size:11px;line-height:1.8;color:#526783;margin-top:8px;">
                                <a href="{{ $privacyUrl }}" style="color:#526783;">Privacy Policy</a>
                                &nbsp;|&nbsp;
                                <a href="{{ $termsUrl }}" style="color:#526783;">Terms of Service</a>
                                &nbsp;|&nbsp;
                                <a href="mailto:{{ $supportEmail ?? 'support@sunxfinder.com' }}"
                                    style="color:#526783;">Contact Us</a>
                            </div>
                            <div style="font-size:11px;color:#718096;margin-top:5px;">© {{ date('Y') }} SunXFinder. All
                                rights reserved.</div>
                        </td>
                    </tr>
                </table>
                <div style="font-size:11px;line-height:1.6;color:#8a96a8;padding:12px 10px;">You received this email
                    because a password reset was requested for this email address.</div>
            </td>
        </tr>
    </table>
</body>

</html>

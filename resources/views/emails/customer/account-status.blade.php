@php($isArabic = ($emailLocale ?? 'en') === 'ar')
<!doctype html>
<html lang="{{ $isArabic ? 'ar' : 'en' }}" @if($isArabic) dir="rtl" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $isArabic ? 'تحديث حساب SunXFinder' : 'SunXFinder account update' }}</title>
</head>
<body @if($isArabic) dir="rtl" @endif style="margin:0;padding:0;background:#f4f6f8;color:#10366b;font-family:{{ $isArabic ? 'Tahoma,Arial,sans-serif' : 'Arial,Helvetica,sans-serif' }};">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f6f8;">
        <tr><td align="center" style="padding:24px 10px;">
            <table role="presentation" width="680" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:680px;background:#fff;border-radius:16px;overflow:hidden;">
                <tr><td style="padding:24px 30px;background:#fff;text-align:center;">
                    <img src="{{ $logoUrl }}" alt="SunXFinder" width="180" style="display:block;margin:0 auto 8px;border:0;max-width:180px;height:auto;">
                    @include('emails.partials.brand-tagline', ['emailTaglineLocale' => $emailLocale])
                </td></tr>
                <tr><td style="background:#fff0d8;padding:32px 30px;color:#243b60;line-height:1.8;">
                    <h1 style="margin:0 0 18px;color:#10366b;font-size:25px;line-height:1.4;">
                        @if($isArabic)
                            {{ $decision === 'approved' ? 'تمت الموافقة على حسابك' : 'تحديث بشأن طلب حسابك' }}
                        @else
                            {{ $decision === 'approved' ? 'Your account has been approved' : 'Update on your account request' }}
                        @endif
                    </h1>
                    <p style="margin:0 0 14px;">
                        @if($isArabic)
                            مرحباً {{ $customerName }}،
                        @else
                            Hello {{ $customerName }},
                        @endif
                    </p>
                    <p style="margin:0 0 14px;">
                        @if($isArabic)
                            @if($decision === 'approved')
                                تمت الموافقة على حسابك في SunXFinder. يمكنك الآن تسجيل الدخول إلى التطبيق باستخدام اسم المستخدم <strong dir="ltr">{{ $username }}</strong>.
                            @else
                                نعتذر، لم تتم الموافقة على طلب إنشاء حسابك في SunXFinder. إذا كنت تعتقد أن هذا القرار صدر عن طريق الخطأ، يرجى التواصل معنا.
                            @endif
                        @else
                            @if($decision === 'approved')
                                Your SunXFinder account has been approved. You can now sign in to the app using the username <strong>{{ $username }}</strong>.
                            @else
                                We’re sorry, your request to create a SunXFinder account was not approved. Please contact us if you believe this decision was made in error.
                            @endif
                        @endif
                    </p>
                    @if($decision === 'approved')
                        <p style="margin:0 0 20px;">{{ $isArabic ? 'سجّل الدخول من تطبيق SunXFinder للبدء.' : 'Sign in through the SunXFinder app to get started.' }}</p>
                    @endif
                    <a href="{{ $homeUrl }}" style="display:inline-block;padding:12px 20px;border-radius:8px;background:#ff5a0a;color:#fff;text-decoration:none;font-weight:bold;">
                        {{ $isArabic ? 'زيارة SunXFinder' : 'Visit SunXFinder' }}
                    </a>
                </td></tr>
                <tr><td style="padding:18px 28px;text-align:center;color:#7b8798;font-size:12px;">
                    {{ $isArabic ? 'مع التحية، فريق SunXFinder' : 'Regards, the SunXFinder team' }}
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>

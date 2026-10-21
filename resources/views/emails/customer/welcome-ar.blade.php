<!doctype html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>مرحباً بك في SunXFinder</title>
    <link rel="icon" type="image/png" href="{{ asset('visitors/images/sun.png') }}">
</head>


@php
    $emailLocale = $emailLocale ?? app()->getLocale();
    $emailLocale = $emailLocale === 'ar' ? 'ar' : 'en';

    $privacyUrl = url('/privacy-policy') . '?lang=' . $emailLocale;
    $termsUrl = url('/terms') . '?lang=' . $emailLocale;
@endphp

<body dir="rtl"
    style="margin:0;padding:0;background:#f4f6f8;color:#10366b;font-family:Tahoma,Arial,sans-serif;direction:rtl;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">حسابك في SunXFinder جاهز. اكتشف شركات الطاقة
        الشمسية الموثوقة وقارن العروض.</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f6f8;">
        <tr>
            <td align="center" style="padding:24px 10px;">
                <table role="presentation" width="680" cellpadding="0" cellspacing="0" border="0" dir="rtl"
                    style="width:100%;max-width:680px;background:#fff;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:22px 28px;background:#fff;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                dir="rtl">
                                <tr>
                                    <td align="right" valign="middle" width="140">
                                        <img src="{{ $logoUrl }}" alt="SunXFinder" width="180"
                                            style="display:block;border:0;max-width:180px;height:auto;">
                                    </td>
                                    <td align="left" valign="middle"
                                        style="font-size:14px;line-height:1.8;color:#10366b;font-weight:bold;">ابحث عن
                                        حلول الطاقة الشمسية<br><span style="font-weight:normal;">تواصل مع شركات
                                            موثوقة</span></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#fff0d8;padding:32px 30px 26px;text-align:right;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                dir="rtl">
                                <tr>
                                    <td width="58%" valign="middle" style="padding-left:12px;">
                                        <div style="font-size:32px;line-height:1.4;font-weight:800;color:#10366b;">
                                            مرحباً بك في<br><span dir="ltr"
                                                style="color:#0866c9;unicode-bidi:embed;">Sun<span
                                                    style="color:#f15b24;">X</span>Finder!</span></div>
                                        <p style="font-size:16px;line-height:1.9;color:#243b60;margin:17px 0 8px;">
                                            <strong>مرحباً {{ $customerName ?? 'بك' }}،</strong>
                                        </p>
                                        <p style="font-size:15px;line-height:1.9;color:#243b60;margin:0 0 22px;">شكراً
                                            لك على إنشاء حسابك. أصبحت الآن جزءاً من مجتمع متنامٍ يؤمن بمستقبل أنظف وأكثر
                                            إشراقاً واستدامة.</p>
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" dir="rtl">
                                            <tr>
                                                <td bgcolor="#ff5a0a" style="border-radius:9px;"><a
                                                        href="{{ $homeUrl ?? url('/') }}"
                                                        style="display:inline-block;padding:14px 21px;color:#fff;text-decoration:none;font-size:15px;font-weight:bold;">استكشف
                                                        SunXFinder &nbsp;←</a></td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td width="42%" valign="middle" align="center"
                                        style="font-size:72px;line-height:1.25;">☀️<br><span
                                            style="font-size:60px;">🏡</span>
                                        <div style="font-size:12px;line-height:1.7;color:#9b6b31;">مستقبل أفضل بالطاقة
                                            الشمسية يبدأ من هنا</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 20px 20px;background:#fff;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                dir="rtl">
                                <tr>
                                    <td width="25%" valign="top" align="center" style="padding:10px 8px;">
                                        <div
                                            style="width:52px;height:52px;line-height:52px;border-radius:50%;background:#fff0df;font-size:25px;">
                                            🏢</div><strong
                                            style="display:block;margin:11px 0 7px;font-size:14px;line-height:1.6;color:#10366b;">اكتشف
                                            شركات موثوقة</strong><span
                                            style="font-size:12px;line-height:1.8;color:#526783;">اعثر على شركات الطاقة
                                            الشمسية الموثوقة في منطقتك.</span>
                                    </td>
                                    <td width="25%" valign="top" align="center" style="padding:10px 8px;">
                                        <div
                                            style="width:52px;height:52px;line-height:52px;border-radius:50%;background:#e7f1ff;font-size:25px;">
                                            📋</div><strong
                                            style="display:block;margin:11px 0 7px;font-size:14px;line-height:1.6;color:#10366b;">قارن
                                            العروض بسهولة</strong><span
                                            style="font-size:12px;line-height:1.8;color:#526783;">قارن عدة عروض واختر
                                            الأنسب لاحتياجاتك.</span>
                                    </td>
                                    <td width="25%" valign="top" align="center" style="padding:10px 8px;">
                                        <div
                                            style="width:52px;height:52px;line-height:52px;border-radius:50%;background:#e4f7ec;font-size:25px;">
                                            🌿</div><strong
                                            style="display:block;margin:11px 0 7px;font-size:14px;line-height:1.6;color:#10366b;">قرارات
                                            شمسية ذكية</strong><span
                                            style="font-size:12px;line-height:1.8;color:#526783;">اتخذ قرارات مدروسة
                                            ووفر المال.</span>
                                    </td>
                                    <td width="25%" valign="top" align="center" style="padding:10px 8px;">
                                        <div
                                            style="width:52px;height:52px;line-height:52px;border-radius:50%;background:#eeebff;font-size:25px;">
                                            👥</div><strong
                                            style="display:block;margin:11px 0 7px;font-size:14px;line-height:1.6;color:#10366b;">نحو
                                            مستقبل أنظف</strong><span
                                            style="font-size:12px;line-height:1.8;color:#526783;">ساهم في مستقبل أكثر
                                            استدامة في السودان.</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 22px 24px;background:#fff;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" dir="rtl"
                                style="background:#eaf3ff;border-radius:12px;">
                                <tr>
                                    <td width="60" align="center" style="padding:17px 16px 17px 4px;font-size:28px;">🎧
                                    </td>
                                    <td style="padding:17px 8px;"><strong style="font-size:16px;color:#10366b;">هل تحتاج
                                            إلى مساعدة؟</strong>
                                        <div style="font-size:13px;line-height:1.8;color:#526783;margin-top:4px;">إذا
                                            كانت لديك أي أسئلة، فإن فريقنا هنا لمساعدتك.</div>
                                    </td>
                                    <td align="center" style="padding:14px 16px;"><a
                                            href="mailto:{{ $supportEmail ?? 'support@sunxfinder.com' }}"
                                            style="display:inline-block;padding:11px 14px;border:1px solid #0866e8;border-radius:8px;color:#075fe4;text-decoration:none;font-weight:bold;font-size:13px;white-space:nowrap;">تواصل
                                            مع الدعم ←</a></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:0 22px 22px;background:#fff;border-top:1px solid #e7ebf0;">
                            <p style="font-size:12px;line-height:1.8;color:#526783;margin:18px 0 12px;">تابعنا للحصول
                                على نصائح وتحديثات حول الطاقة الشمسية.</p>
                            <div style="font-size:12px;color:#526783;">SunXFinder · نحو مستقبل أكثر إشراقاً.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#fff0d8;padding:18px 22px;text-align:center;">
                            <div dir="ltr" style="font-size:18px;font-weight:bold;color:#10366b;">Sun<b
                                    style="color:#f15b24;">X</b>Finder</div>
                            <div style="font-size:11px;line-height:1.9;color:#526783;margin-top:8px;">
                                <a href="{{ $privacyUrl }}" style="color:#526783;">
                                    {{ $emailLocale === 'ar' ? 'سياسة الخصوصية' : 'Privacy Policy' }}
                                </a>
                                &nbsp;|&nbsp;
                                <a href="{{ $termsUrl }}" style="color:#526783;">
                                    {{ $emailLocale === 'ar' ? 'شروط الاستخدام' : 'Terms of Use' }}
                                </a>
                                &nbsp;|&nbsp;
                                <a href="mailto:{{ $supportEmail ?? 'support@sunxfinder.com' }}" style="color:#526783;">
                                    {{ $emailLocale === 'ar' ? 'اتصل بنا' : 'Contact Us' }}
                                </a>
                            </div>
                            <div style="font-size:11px;color:#718096;margin-top:5px;">© {{ date('Y') }} SunXFinder. جميع
                                الحقوق محفوظة.</div>
                        </td>
                    </tr>
                </table>
                <div style="font-size:11px;line-height:1.8;color:#8a96a8;padding:12px 10px;">وصلتك هذه الرسالة لأنه تم
                    إنشاء حساب باستخدام بريدك الإلكتروني.</div>
            </td>
        </tr>
    </table>
</body>

</html>
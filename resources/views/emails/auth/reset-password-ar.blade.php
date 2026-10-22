<!doctype html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>إعادة تعيين كلمة المرور - SunXFinder</title>
</head>

@php
    $privacyUrl = url('/privacy-policy') . '?lang=ar';
    $termsUrl = url('/terms') . '?lang=ar';

    // $accountLabel is passed by PasswordResetOtpMail: administrator, company or customer.
    $accountLabelKey = (string) ($accountLabel ?? '');

    $accountLabelAr = [
        'administrator' => 'المشرف',
        'company' => 'الشركة',
        'customer' => 'العميل',
    ][$accountLabelKey] ?? $accountLabelKey;
@endphp

<body dir="rtl"
    style="margin:0;padding:0;background:#f4f6f8;color:#10366b;font-family:Tahoma,Arial,sans-serif;direction:rtl;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">رمز إعادة تعيين كلمة المرور في SunXFinder هو
        {{ $code }}. تنتهي صلاحيته خلال دقيقة واحدة.
    </div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f6f8;">
        <tr>
            <td align="center" style="padding:24px 10px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" dir="rtl"
                    style="width:100%;max-width:600px;background:#fff;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:22px 28px;background:#fff;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                dir="rtl">
                                <tr>
                                    <td align="right" valign="middle" width="140">
                                        <img src="https://dev.sunxfinder.com/visitors/images/sunxfinder-logo-email.png"
                                            alt="SunXFinder" width="180"
                                            style="display:block;border:0;max-width:180px;height:auto;">
                                    </td>
                                    <td align="left" valign="middle"
                                        style="font-size:14px;line-height:1.8;color:#10366b;font-weight:bold;">أمان
                                        الحساب<br><span style="font-weight:normal;">طلب إعادة تعيين كلمة المرور</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#fff0d8;padding:32px 30px 30px;text-align:right;">
                            <div style="font-size:28px;line-height:1.4;font-weight:800;color:#10366b;">
                                إعادة تعيين <span style="color:#f15b24;">كلمة المرور</span></div>
                            <p style="font-size:16px;line-height:1.9;color:#243b60;margin:17px 0 8px;">
                                <strong>مرحباً {{ $recipientName ?: 'بك' }}،</strong>
                            </p>
                            <p style="font-size:15px;line-height:1.9;color:#243b60;margin:0 0 22px;">تلقينا طلباً
                                لإعادة تعيين كلمة المرور لحساب {{ $accountLabelAr }} الخاص بك في
                                <span dir="ltr">SunXFinder</span>. أدخل هذا الرمز في صفحة إعادة التعيين:
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td align="center" dir="ltr"
                                        style="background:#ffffff;border:2px dashed #ff5a0a;border-radius:12px;padding:16px 28px;">
                                        <div
                                            style="font-family:'Courier New',Courier,monospace;font-size:36px;line-height:1;font-weight:bold;letter-spacing:10px;color:#10366b;">
                                            {{ $code }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size:14px;line-height:1.9;color:#b54708;margin:20px 0 0;text-align:center;">
                                ⏱ تنتهي صلاحية هذا الرمز خلال <strong>دقيقة واحدة</strong> ويمكن استخدامه مرة واحدة
                                فقط.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 28px;background:#fff;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" dir="rtl"
                                style="background:#eaf3ff;border-radius:12px;">
                                <tr>
                                    <td width="56" align="center" style="padding:16px 16px 16px 4px;font-size:26px;">🔒
                                    </td>
                                    <td style="padding:16px 8px 16px 16px;">
                                        <strong style="font-size:15px;color:#10366b;">لم تطلب ذلك؟</strong>
                                        <div style="font-size:13px;line-height:1.8;color:#526783;margin-top:4px;">
                                            يمكنك تجاهل هذه الرسالة بأمان، ولن تتغير كلمة المرور الخاصة بك. لا تشارك هذا
                                            الرمز مع أي شخص، بما في ذلك فريق SunXFinder.</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#fff0d8;padding:18px 22px;text-align:center;">
                            <div dir="ltr" style="font-size:18px;font-weight:bold;color:#10366b;">Sun<b
                                    style="color:#f15b24;">X</b>Finder</div>
                            <div style="font-size:11px;line-height:1.9;color:#526783;margin-top:8px;">
                                <a href="{{ $privacyUrl }}" style="color:#526783;">سياسة الخصوصية</a>
                                &nbsp;|&nbsp;
                                <a href="{{ $termsUrl }}" style="color:#526783;">شروط الاستخدام</a>
                                &nbsp;|&nbsp;
                                <a href="mailto:{{ $supportEmail ?? 'support@sunxfinder.com' }}"
                                    style="color:#526783;">اتصل بنا</a>
                            </div>
                            <div style="font-size:11px;color:#718096;margin-top:5px;">© {{ date('Y') }} SunXFinder.
                                جميع الحقوق محفوظة.</div>
                        </td>
                    </tr>
                </table>
                <div style="font-size:11px;line-height:1.8;color:#8a96a8;padding:12px 10px;">وصلتك هذه الرسالة لأنه تم
                    طلب إعادة تعيين كلمة المرور لهذا البريد الإلكتروني.</div>
            </td>
        </tr>
    </table>
</body>

</html>
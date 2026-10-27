<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Your offer was accepted</title>
</head>

<body style="margin:0;padding:0;background:#f4f6f8;color:#10366b;font-family:Arial,Helvetica,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">{{ $customerName }} accepted your offer on
        request #{{ $quote_id }}. A new project is ready.</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f6f8;">
        <tr>
            <td align="center" style="padding:24px 10px;">
                <table role="presentation" width="680" cellpadding="0" cellspacing="0" border="0"
                    style="width:100%;max-width:680px;background:#fff;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:22px 28px;background:#fff;" align="left">
                            <img src="https://dev.sunxfinder.com/visitors/images/sunxfinder-logo-email.png"
                                alt="SunXFinder" width="180" style="display:block;border:0;max-width:180px;height:auto;">
                            @include('emails.partials.brand-tagline', ['emailTaglineLocale' => 'en'])
                        </td>
                    </tr>

                    {{-- English --}}
                    <tr>
                        <td style="background:#e4f7ec;padding:30px 30px 26px;">
                            <div style="font-size:30px;line-height:1.2;font-weight:800;color:#10366b;">
                                🎉 Your offer was <span style="color:#1a9d58;">accepted!</span></div>
                            <p style="font-size:16px;line-height:1.7;color:#243b60;margin:17px 0 8px;">
                                <strong>Hi {{ $companyName }},</strong>
                            </p>
                            <p style="font-size:15px;line-height:1.7;color:#243b60;margin:0 0 18px;">
                                Great news! <strong>{{ $customerName }}</strong> accepted your offer on quote request
                                <strong>#{{ $quote_id }}</strong>. A project has been created with a default
                                installation checklist, so you can start preparing.
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                style="background:#fff;border-radius:10px;margin:0 0 22px;">
                                <tr>
                                    <td style="padding:12px 16px;font-size:14px;color:#526783;">Request</td>
                                    <td style="padding:12px 16px;font-size:14px;font-weight:bold;color:#10366b;">
                                        #{{ $quote_id }}</td>
                                </tr>
                                @if (!empty($quoteName))
                                    <tr>
                                        <td style="padding:12px 16px;font-size:14px;color:#526783;">Quote name</td>
                                        <td style="padding:12px 16px;font-size:14px;font-weight:bold;color:#10366b;">
                                            {{ $quoteName }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td style="padding:12px 16px;font-size:14px;color:#526783;">Accepted price</td>
                                    <td style="padding:12px 16px;font-size:14px;font-weight:bold;color:#10366b;">
                                        {{ $price }}</td>
                                </tr>
                            </table>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td bgcolor="#ff5a0a" style="border-radius:9px;"><a href="{{ $actionUrl }}"
                                            style="display:inline-block;padding:14px 21px;color:#fff;text-decoration:none;font-size:15px;font-weight:bold;">View
                                            project &nbsp;→</a></td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Arabic --}}
                    <tr>
                        <td dir="rtl" style="padding:28px 30px;background:#fff;text-align:right;border-top:1px solid #e7ebf0;">
                            <div style="font-size:24px;line-height:1.3;font-weight:800;color:#10366b;">
                                🎉 تم <span style="color:#1a9d58;">قبول عرضك!</span></div>
                            <p style="font-size:16px;line-height:1.8;color:#243b60;margin:14px 0 8px;">
                                <strong>مرحباً {{ $companyName }}،</strong>
                            </p>
                            <p style="font-size:15px;line-height:1.8;color:#243b60;margin:0 0 18px;">
                                أخبار رائعة! قام <strong>{{ $customerName }}</strong> بقبول عرضك على طلب عرض السعر
                                رقم <strong>#{{ $quote_id }}</strong> بقيمة <strong>{{ $price }}</strong>. تم إنشاء
                                مشروع جديد مع قائمة مهام التركيب الافتراضية لتبدأ التحضير.
                            </p>
                            <a href="{{ $actionUrl }}"
                                style="display:inline-block;padding:12px 20px;border:1px solid #ff5a0a;border-radius:9px;color:#ff5a0a;text-decoration:none;font-size:15px;font-weight:bold;">عرض
                                المشروع ←</a>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#fff0d8;padding:18px 22px;text-align:center;">
                            <div style="font-size:18px;font-weight:bold;color:#10366b;">Sun<b
                                    style="color:#f15b24;">X</b>Finder</div>
                            <div style="font-size:12px;line-height:1.8;color:#526783;margin-top:8px;">
                                This is an automated message from a no-reply address. Please do not reply to this
                                email.<br>
                                هذه رسالة تلقائية من عنوان لا يستقبل الردود، يرجى عدم الرد عليها.<br>
                                Need help? / للمساعدة:
                                <a href="mailto:{{ $supportEmail ?? 'support@sunxfinder.com' }}"
                                    style="color:#075fe4;">{{ $supportEmail ?? 'support@sunxfinder.com' }}</a>
                            </div>
                            <div style="font-size:11px;color:#718096;margin-top:5px;">© {{ date('Y') }} SunXFinder. All
                                rights reserved.</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>

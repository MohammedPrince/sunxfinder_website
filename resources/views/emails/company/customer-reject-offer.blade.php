<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Your offer was not selected</title>
</head>

<body style="margin:0;padding:0;background:#f4f6f8;color:#10366b;font-family:Arial,Helvetica,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">The customer declined your offer on request
        #{{ $quote_id }}.</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f6f8;">
        <tr>
            <td align="center" style="padding:24px 10px;">
                <table role="presentation" width="680" cellpadding="0" cellspacing="0" border="0"
                    style="width:100%;max-width:680px;background:#fff;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:22px 28px;background:#fff;" align="left">
                            <img src="https://dev.sunxfinder.com/visitors/images/sunxfinder-logo-email.png"
                                alt="SunXFinder" width="180" style="display:block;margin:0 auto;border:0;max-width:180px;height:auto;">
                            @include('emails.partials.brand-tagline', ['emailTaglineLocale' => 'en'])
                        </td>
                    </tr>

                    {{-- English --}}
                    <tr>
                        <td style="background:#fff0d8;padding:30px 30px 26px;">
                            <div style="font-size:28px;line-height:1.2;font-weight:800;color:#10366b;">
                                Your offer was not selected</div>
                            <p style="font-size:16px;line-height:1.7;color:#243b60;margin:17px 0 8px;">
                                <strong>Hi {{ $companyName }},</strong>
                            </p>
                            <p style="font-size:15px;line-height:1.7;color:#243b60;margin:0 0 18px;">
                                <strong>{{ $customerName }}</strong> has declined your offer of
                                <strong>{{ $price }}</strong> on quote request <strong>#{{ $quote_id }}</strong>.
                                Thank you for taking the time to respond. New requests are waiting for you on
                                SunXFinder.
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td bgcolor="#ff5a0a" style="border-radius:9px;"><a href="{{ $actionUrl }}"
                                            style="display:inline-block;padding:14px 21px;color:#fff;text-decoration:none;font-size:15px;font-weight:bold;">View
                                            request &nbsp;→</a></td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Arabic --}}
                    <tr>
                        <td dir="rtl" style="padding:28px 30px;background:#fff;text-align:right;border-top:1px solid #e7ebf0;">
                            <div style="font-size:24px;line-height:1.3;font-weight:800;color:#10366b;">
                                لم يتم اختيار عرضك</div>
                            <p style="font-size:16px;line-height:1.8;color:#243b60;margin:14px 0 8px;">
                                <strong>مرحباً {{ $companyName }}،</strong>
                            </p>
                            <p style="font-size:15px;line-height:1.8;color:#243b60;margin:0 0 18px;">
                                قام <strong>{{ $customerName }}</strong> برفض عرضك بقيمة <strong>{{ $price }}</strong>
                                على طلب عرض السعر رقم <strong>#{{ $quote_id }}</strong>. شكراً لتقديم عرضك، هناك طلبات
                                جديدة بانتظارك على SunXFinder.
                            </p>
                            <a href="{{ $actionUrl }}"
                                style="display:inline-block;padding:12px 20px;border:1px solid #ff5a0a;border-radius:9px;color:#ff5a0a;text-decoration:none;font-size:15px;font-weight:bold;">عرض
                                الطلب ←</a>
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

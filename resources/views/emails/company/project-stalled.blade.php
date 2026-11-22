@php
    $statusEn = ['accepted' => 'Accepted, not started yet', 'in_progress' => 'In progress'][$status] ?? ucfirst(str_replace('_', ' ', $status));
    $statusAr = ['accepted' => 'مقبول، لم يبدأ التركيب بعد', 'in_progress' => 'قيد التنفيذ'][$status] ?? $statusEn;
@endphp
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Project #{{ $projectId }} needs an update</title>
</head>

<body style="margin:0;padding:0;background:#f4f6f8;color:#10366b;font-family:Arial,Helvetica,sans-serif;">
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
                            <div style="font-size:26px;line-height:1.2;font-weight:800;color:#10366b;">
                                ⏰ Your project needs <span style="color:#f15b24;">an update</span></div>
                            <p style="font-size:16px;line-height:1.7;color:#243b60;margin:17px 0 8px;">
                                <strong>Hi {{ $companyName }},</strong>
                            </p>
                            <p style="font-size:15px;line-height:1.7;color:#243b60;margin:0 0 18px;">
                                Project <strong>#{{ $projectId }}</strong>
                                @if (!empty($quoteName))
                                    (<strong>{{ $quoteName }}</strong>)
                                @endif
                                for request <strong>{{ $reference }}</strong> has had no update for
                                <strong>{{ $idleDays }} days</strong>. Your customer follows the installation in the
                                SunXFinder app, so please post a progress update today. If something is holding the
                                work up, contact SunXFinder support (address below).
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                style="width:100%;background:#fff;border-radius:10px;margin:0 0 18px;font-size:14px;color:#243b60;">
                                @if (!empty($customerName))
                                    <tr>
                                        <td style="padding:9px 14px;color:#526783;">Customer</td>
                                        <td style="padding:9px 14px;font-weight:bold;">{{ $customerName }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td style="padding:9px 14px;color:#526783;">Status</td>
                                    <td style="padding:9px 14px;font-weight:bold;">{{ $statusEn }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:9px 14px;color:#526783;">Progress</td>
                                    <td style="padding:9px 14px;font-weight:bold;">{{ $progress }}%</td>
                                </tr>
                                <tr>
                                    <td style="padding:9px 14px;color:#526783;">Last update</td>
                                    <td style="padding:9px 14px;font-weight:bold;">{{ $lastActivity }}</td>
                                </tr>
                            </table>
                            @if (!empty($note))
                                <div style="background:#fff;border-left:4px solid #f15b24;border-radius:8px;padding:12px 14px;margin:0 0 18px;">
                                    <div style="font-size:12px;font-weight:bold;color:#526783;margin-bottom:4px;">Message from SunXFinder / رسالة من SunXFinder</div>
                                    <div dir="auto" style="font-size:15px;line-height:1.7;color:#243b60;white-space:pre-line;">{{ $note }}</div>
                                </div>
                            @endif
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td bgcolor="#ff5a0a" style="border-radius:9px;"><a href="{{ $actionUrl }}"
                                            style="display:inline-block;padding:14px 21px;color:#fff;text-decoration:none;font-size:15px;font-weight:bold;">Post
                                            an update &nbsp;→</a></td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Arabic --}}
                    <tr>
                        <td dir="rtl" style="padding:28px 30px;background:#fff;text-align:right;border-top:1px solid #e7ebf0;">
                            <div style="font-size:22px;line-height:1.3;font-weight:800;color:#10366b;">
                                ⏰ مشروعك <span style="color:#f15b24;">يحتاج إلى تحديث</span></div>
                            <p style="font-size:16px;line-height:1.8;color:#243b60;margin:14px 0 8px;">
                                <strong>مرحباً {{ $companyName }}،</strong>
                            </p>
                            <p style="font-size:15px;line-height:1.8;color:#243b60;margin:0 0 14px;">
                                لم يُسجَّل أي تحديث على المشروع رقم <strong>{{ $projectId }}</strong>
                                @if (!empty($quoteName))
                                    (<strong>{{ $quoteName }}</strong>)
                                @endif
                                الخاص بالطلب <strong>{{ $reference }}</strong> منذ <strong>{{ $idleDays }} يوماً</strong>.
                                يتابع العميل سير التركيب من تطبيق SunXFinder، لذا نرجو إضافة تحديث للتقدم اليوم.
                                وإن كان هناك ما يؤخر العمل فتواصل مع دعم SunXFinder (العنوان في الأسفل).
                            </p>
                            <p style="font-size:14px;line-height:1.9;color:#526783;margin:0 0 18px;">
                                @if (!empty($customerName))
                                    العميل: <strong style="color:#243b60;">{{ $customerName }}</strong><br>
                                @endif
                                الحالة: <strong style="color:#243b60;">{{ $statusAr }}</strong><br>
                                نسبة الإنجاز: <strong style="color:#243b60;">{{ $progress }}%</strong><br>
                                آخر تحديث: <strong style="color:#243b60;" dir="ltr">{{ $lastActivity }}</strong>
                            </p>
                            <a href="{{ $actionUrl }}"
                                style="display:inline-block;padding:12px 20px;border:1px solid #ff5a0a;border-radius:9px;color:#ff5a0a;text-decoration:none;font-size:15px;font-weight:bold;">أضف
                                تحديثاً ←</a>
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
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>

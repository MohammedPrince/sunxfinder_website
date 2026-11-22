@php
    // One template, three cases (CompanyOfferDecisionMail):
    //  completed       the customer confirmed the handover in the app
    //  auto_completed  nobody answered within the auto-confirm period, so it was confirmed automatically
    //  problem         the customer reported a problem; the project is back in progress
    $variant = $variant ?? 'completed';
    $days = $autoConfirmDays ?? \App\Models\Project::AUTO_CONFIRM_DAYS;
    $isProblem = $variant === 'problem';

    $title = match ($variant) {
        'auto_completed' => 'Handover confirmed automatically',
        'problem' => 'The customer reported a problem',
        default => 'Customer confirmed the handover',
    };
    $headlineEn = match ($variant) {
        'auto_completed' => '✅ Project <span style="color:#1a9d58;">completed</span>',
        'problem' => '⚠️ Problem <span style="color:#d9480f;">reported</span>',
        default => '✅ Project <span style="color:#1a9d58;">completed</span>',
    };
    $headlineAr = match ($variant) {
        'problem' => '⚠️ أبلغ العميل عن <span style="color:#d9480f;">مشكلة</span>',
        default => '✅ اكتمل <span style="color:#1a9d58;">المشروع</span>',
    };
    $background = $isProblem ? '#fff1e6' : '#e4f7ec';
@endphp
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $title }}</title>
</head>

<body style="margin:0;padding:0;background:#f4f6f8;color:#10366b;font-family:Arial,Helvetica,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
        @if ($isProblem)
            {{ $customerName }} reported a problem with the handover on request #{{ $quote_id }}.
        @elseif ($variant === 'auto_completed')
            Request #{{ $quote_id }} was confirmed automatically. The project is completed.
        @else
            {{ $customerName }} confirmed the handover on request #{{ $quote_id }}. The project is completed.
        @endif
    </div>
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
                        <td style="background:{{ $background }};padding:30px 30px 26px;">
                            <div style="font-size:30px;line-height:1.2;font-weight:800;color:#10366b;">{!! $headlineEn !!}</div>
                            <p style="font-size:16px;line-height:1.7;color:#243b60;margin:17px 0 8px;">
                                <strong>Hi {{ $companyName }},</strong>
                            </p>
                            <p style="font-size:15px;line-height:1.7;color:#243b60;margin:0 0 18px;">
                                @if ($isProblem)
                                    <strong>{{ $customerName }}</strong> did not confirm the handover on quote request
                                    <strong>#{{ $quote_id }}</strong> and reported a problem. The project is back
                                    <strong>in progress</strong>. Please contact the customer, fix the problem, then
                                    confirm the handover step again in your dashboard.
                                @elseif ($variant === 'auto_completed')
                                    The customer did not confirm or report a problem within {{ $days }} days after you
                                    finished quote request <strong>#{{ $quote_id }}</strong>, so the handover was
                                    confirmed automatically. The project is now completed and locked.
                                @else
                                    <strong>{{ $customerName }}</strong> confirmed in the app that the system was handed
                                    over on quote request <strong>#{{ $quote_id }}</strong>. The project is now completed
                                    and locked, and the customer can rate your work.
                                @endif
                            </p>
                            @if ($isProblem && !empty($problemMessage))
                                <div style="background:#fff;border-left:4px solid #d9480f;border-radius:8px;padding:12px 16px;margin:0 0 18px;font-size:14px;line-height:1.7;color:#243b60;">
                                    <strong>Customer's message:</strong><br>{{ $problemMessage }}
                                </div>
                            @endif
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
                                    <td style="padding:12px 16px;font-size:14px;color:#526783;">Price</td>
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
                            <div style="font-size:24px;line-height:1.3;font-weight:800;color:#10366b;">{!! $headlineAr !!}</div>
                            <p style="font-size:16px;line-height:1.8;color:#243b60;margin:14px 0 8px;">
                                <strong>مرحباً {{ $companyName }}،</strong>
                            </p>
                            <p style="font-size:15px;line-height:1.8;color:#243b60;margin:0 0 18px;">
                                @if ($isProblem)
                                    لم يؤكد <strong>{{ $customerName }}</strong> استلام النظام على طلب عرض السعر رقم
                                    <strong>#{{ $quote_id }}</strong> وأبلغ عن مشكلة. عاد المشروع إلى
                                    <strong>قيد التنفيذ</strong>. يرجى التواصل مع العميل وحل المشكلة، ثم تأكيد مرحلة
                                    التسليم مرة أخرى من لوحة التحكم.
                                @elseif ($variant === 'auto_completed')
                                    لم يؤكد العميل الاستلام ولم يبلغ عن مشكلة خلال {{ $days }} أيام من إنهائكم الطلب رقم
                                    <strong>#{{ $quote_id }}</strong>، لذلك تم تأكيد الاستلام تلقائياً. أصبح المشروع
                                    مكتملاً ومقفلاً.
                                @else
                                    أكّد <strong>{{ $customerName }}</strong> في التطبيق استلام النظام على طلب عرض السعر
                                    رقم <strong>#{{ $quote_id }}</strong>. أصبح المشروع مكتملاً ومقفلاً، ويمكن للعميل الآن
                                    تقييم عملكم.
                                @endif
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

@php
    // One template, five cases (CompanyOfferDecisionMail::DEAL_CHANGES):
    //  reopened         another company left the deal; this company's offer can be chosen again
    //  price_accepted   the customer accepted this company's new price
    //  price_declined   the customer declined it; the deal ended and the request reopened
    //  cancel_approved  an admin approved this company's cancellation request
    //  cancel_rejected  an admin did not approve it; the project carries on
    $variant = $variant ?? 'reopened';
    $isGood = in_array($variant, ['reopened', 'price_accepted'], true);
    $background = $isGood ? '#e4f7ec' : '#fff1e6';
    $accent = $isGood ? '#1a9d58' : '#d9480f';

    $title = match ($variant) {
        'price_accepted' => 'The customer accepted the new price',
        'price_declined' => 'The customer declined the new price',
        'cancel_approved' => 'Cancellation approved',
        'cancel_rejected' => 'Cancellation not approved',
        default => 'A request is open again',
    };
    $headlineEn = match ($variant) {
        'price_accepted' => '✅ New price <span style="color:' . $accent . ';">accepted</span>',
        'price_declined' => 'New price <span style="color:' . $accent . ';">declined</span>',
        'cancel_approved' => 'Cancellation <span style="color:' . $accent . ';">approved</span>',
        'cancel_rejected' => 'Cancellation <span style="color:' . $accent . ';">not approved</span>',
        default => '🔔 Request <span style="color:' . $accent . ';">open again</span>',
    };
    $headlineAr = match ($variant) {
        'price_accepted' => '✅ تم <span style="color:' . $accent . ';">قبول</span> السعر الجديد',
        'price_declined' => 'تم <span style="color:' . $accent . ';">رفض</span> السعر الجديد',
        'cancel_approved' => 'تمت <span style="color:' . $accent . ';">الموافقة</span> على الإلغاء',
        'cancel_rejected' => 'لم تتم <span style="color:' . $accent . ';">الموافقة</span> على الإلغاء',
        default => '🔔 الطلب <span style="color:' . $accent . ';">مفتوح من جديد</span>',
    };
    $buttonEn = in_array($variant, ['price_accepted', 'cancel_rejected'], true) ? 'View project' : 'View request';
    $buttonAr = in_array($variant, ['price_accepted', 'cancel_rejected'], true) ? 'عرض المشروع' : 'عرض الطلب';
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
        {{ $title }} — request #{{ $quote_id }}.
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
                                @switch($variant)
                                    @case('price_accepted')
                                        <strong>{{ $customerName }}</strong> accepted your new price on quote request
                                        <strong>#{{ $quote_id }}</strong>. Your offer now shows the new price, and you can
                                        start the installation when you are ready.
                                        @break
                                    @case('price_declined')
                                        <strong>{{ $customerName }}</strong> did not accept your new price on quote request
                                        <strong>#{{ $quote_id }}</strong>, so the deal has ended and the request is open to
                                        other companies again. This does not count against your company.
                                        @break
                                    @case('cancel_approved')
                                        The SunXFinder team approved your request to cancel the job on quote request
                                        <strong>#{{ $quote_id }}</strong>. The project is closed and the customer can choose
                                        another company.
                                        @break
                                    @case('cancel_rejected')
                                        The SunXFinder team did not approve your request to cancel the job on quote request
                                        <strong>#{{ $quote_id }}</strong>. The project carries on: please continue the work and
                                        keep the customer updated.
                                        @break
                                    @default
                                        The company the customer chose can no longer carry out quote request
                                        <strong>#{{ $quote_id }}</strong>, so the request is open again and
                                        <strong>{{ $customerName }}</strong> can choose your offer. Check that your price and
                                        validity are still right, and update your offer if needed.
                                @endswitch
                            </p>
                            @if (!empty($adminNote))
                                <div style="background:#fff;border-left:4px solid {{ $accent }};border-radius:8px;padding:12px 16px;margin:0 0 18px;font-size:14px;line-height:1.7;color:#243b60;">
                                    <strong>Note from SunXFinder:</strong><br>{{ $adminNote }}
                                </div>
                            @endif
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                style="background:#fff;border-radius:10px;margin:0 0 22px;">
                                <tr>
                                    <td style="padding:12px 16px;font-size:14px;color:#526783;">Request</td>
                                    <td style="padding:12px 16px;font-size:14px;font-weight:bold;color:#10366b;">#{{ $quote_id }}</td>
                                </tr>
                                @if (!empty($quoteName))
                                    <tr>
                                        <td style="padding:12px 16px;font-size:14px;color:#526783;">Quote name</td>
                                        <td style="padding:12px 16px;font-size:14px;font-weight:bold;color:#10366b;">{{ $quoteName }}</td>
                                    </tr>
                                @endif
                                @if (!empty($oldPrice))
                                    <tr>
                                        <td style="padding:12px 16px;font-size:14px;color:#526783;">Previous price</td>
                                        <td style="padding:12px 16px;font-size:14px;color:#10366b;">{{ $oldPrice }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td style="padding:12px 16px;font-size:14px;color:#526783;">{{ $variant === 'price_declined' ? 'Proposed price' : 'Price' }}</td>
                                    <td style="padding:12px 16px;font-size:14px;font-weight:bold;color:#10366b;">{{ $newPrice ?? $price }}</td>
                                </tr>
                                @if (in_array($variant, ['cancel_approved', 'cancel_rejected'], true) && !empty($reasonText))
                                    <tr>
                                        <td style="padding:12px 16px;font-size:14px;color:#526783;">Your reason</td>
                                        <td style="padding:12px 16px;font-size:14px;color:#10366b;">{{ $reasonText }}</td>
                                    </tr>
                                @endif
                            </table>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td bgcolor="#ff5a0a" style="border-radius:9px;"><a href="{{ $actionUrl }}"
                                            style="display:inline-block;padding:14px 21px;color:#fff;text-decoration:none;font-size:15px;font-weight:bold;">{{ $buttonEn }}
                                            &nbsp;→</a></td>
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
                                @switch($variant)
                                    @case('price_accepted')
                                        قبل <strong>{{ $customerName }}</strong> السعر الجديد على طلب عرض السعر رقم
                                        <strong>#{{ $quote_id }}</strong>. يظهر عرضكم الآن بالسعر الجديد، ويمكنكم بدء التركيب
                                        متى كنتم جاهزين.
                                        @break
                                    @case('price_declined')
                                        لم يقبل <strong>{{ $customerName }}</strong> السعر الجديد على طلب عرض السعر رقم
                                        <strong>#{{ $quote_id }}</strong>، لذلك انتهى الاتفاق وأصبح الطلب مفتوحاً لشركات أخرى.
                                        لا يُحتسب هذا ضد شركتكم.
                                        @break
                                    @case('cancel_approved')
                                        وافق فريق SunXFinder على طلبكم إلغاء العمل على طلب عرض السعر رقم
                                        <strong>#{{ $quote_id }}</strong>. تم إغلاق المشروع ويمكن للعميل اختيار شركة أخرى.
                                        @break
                                    @case('cancel_rejected')
                                        لم يوافق فريق SunXFinder على طلبكم إلغاء العمل على طلب عرض السعر رقم
                                        <strong>#{{ $quote_id }}</strong>. يستمر المشروع: يرجى متابعة العمل وإبقاء العميل على اطلاع.
                                        @break
                                    @default
                                        لم تعد الشركة التي اختارها العميل قادرة على تنفيذ طلب عرض السعر رقم
                                        <strong>#{{ $quote_id }}</strong>، لذلك أصبح الطلب مفتوحاً من جديد ويمكن لـ
                                        <strong>{{ $customerName }}</strong> اختيار عرضكم. تأكدوا من أن السعر ومدة الصلاحية
                                        ما زالا مناسبين، وحدّثوا عرضكم إذا لزم الأمر.
                                @endswitch
                            </p>
                            <a href="{{ $actionUrl }}"
                                style="display:inline-block;padding:12px 20px;border:1px solid #ff5a0a;border-radius:9px;color:#ff5a0a;text-decoration:none;font-size:15px;font-weight:bold;">{{ $buttonAr }}
                                ←</a>
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

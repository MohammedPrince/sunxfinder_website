@php
    $emailTaglineLocale = ($emailTaglineLocale ?? 'en') === 'ar' ? 'ar' : 'en';
@endphp

@if ($emailTaglineLocale === 'ar')
    <div dir="rtl" style="margin-top:8px;font-family:Tahoma,Arial,sans-serif;font-size:14px;line-height:1.6;font-weight:bold;color:#10366b;text-align:center;">
        اكتشف. <span style="color:#f15b24;">قارن.</span> تواصل
    </div>
@else
    <div dir="ltr" style="margin-top:8px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;font-weight:bold;color:#10366b;text-align:center;">
        Find. <span style="color:#f15b24;">Compare.</span> Connect.
    </div>
@endif

@php
    $emailTaglineLocale = ($emailTaglineLocale ?? 'en') === 'ar' ? 'ar' : 'en';
@endphp

<div dir="{{ $emailTaglineLocale === 'ar' ? 'rtl' : 'ltr' }}"
    style="margin:8px auto 0;font-family:{{ $emailTaglineLocale === 'ar' ? 'Tahoma,Arial,sans-serif' : 'Arial,Helvetica,sans-serif' }};font-size:20px;line-height:1.4;font-weight:700;color:#10366b;text-align:center;white-space:nowrap;">
    @if ($emailTaglineLocale === 'ar')
        اكتشف. <span style="color:#f15b24;">قارن.</span> تواصل
    @else
        Find. <span style="color:#f15b24;">Compare.</span> Connect.
    @endif
</div>
<table role="presentation" width="120" cellpadding="0" cellspacing="0" border="0" align="center"
    style="margin:12px auto 0;">
    <tr>
        <td width="50%" height="3" bgcolor="#0877d1" style="font-size:0;line-height:0;">&nbsp;</td>
        <td width="50%" height="3" bgcolor="#f15b24" style="font-size:0;line-height:0;">&nbsp;</td>
    </tr>
</table>

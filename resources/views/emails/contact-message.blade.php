<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>New website message</title>
</head>

<body style="margin:0;padding:0;background:#f4f6f8;color:#10366b;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f6f8;">
        <tr>
            <td align="center" style="padding:24px 10px;">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0" border="0"
                    style="width:100%;max-width:640px;background:#fff;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:22px 28px;border-bottom:1px solid #e7ebf0;">
                            <div style="font-size:18px;font-weight:bold;color:#10366b;">Sun<b
                                    style="color:#f15b24;">X</b>Finder</div>
                            <div style="font-size:13px;color:#526783;margin-top:4px;">New message from the website
                                contact form</div>
                            @include('emails.partials.brand-tagline', ['emailTaglineLocale' => 'en'])
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="font-size:14px;line-height:1.6;">
                                <tr>
                                    <td style="padding:6px 0;color:#526783;width:120px;">Name</td>
                                    <td style="padding:6px 0;font-weight:bold;">{{ $contact['name'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;color:#526783;">Email</td>
                                    <td style="padding:6px 0;"><a href="mailto:{{ $contact['email'] }}"
                                            style="color:#075fe4;">{{ $contact['email'] }}</a></td>
                                </tr>
                                @if (!empty($contact['phone']))
                                    <tr>
                                        <td style="padding:6px 0;color:#526783;">Phone</td>
                                        <td style="padding:6px 0;">{{ $contact['phone'] }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td style="padding:6px 0;color:#526783;">I am a</td>
                                    <td style="padding:6px 0;">{{ $contact['sender_type_label'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;color:#526783;">Subject</td>
                                    <td style="padding:6px 0;font-weight:bold;">{{ $contact['subject'] }}</td>
                                </tr>
                            </table>

                            <div
                                style="margin-top:18px;padding:16px 18px;border-left:3px solid #fc5202;background:#fff7f2;border-radius:0 10px 10px 0;font-size:14px;line-height:1.7;color:#243b60;white-space:pre-line;"
                                dir="auto">{{ $contact['message'] }}</div>

                            <p style="margin:18px 0 0;font-size:12px;color:#8a96a8;">
                                Sent {{ $contact['sent_at'] }} · Language: {{ strtoupper($contact['lang']) }} ·
                                IP: {{ $contact['ip'] ?? '—' }}<br>
                                Reply to this email to answer {{ $contact['name'] }} directly.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>

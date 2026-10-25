<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $subjectLine }}</title>
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
                                alt="SunXFinder" width="180" style="display:block;border:0;max-width:180px;height:auto;">
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#fff0d8;padding:30px 30px 26px;">
                            <div style="font-size:26px;line-height:1.25;font-weight:800;color:#10366b;">
                                {{ $subjectLine }}
                            </div>
                            <p style="font-size:16px;line-height:1.7;color:#243b60;margin:17px 0 8px;">
                                <strong>Hi {{ $companyName ?: 'there' }},</strong>
                            </p>
                            <div style="font-size:15px;line-height:1.7;color:#243b60;margin:0 0 22px;">
                                {!! nl2br(e($messageText)) !!}
                            </div>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td bgcolor="#ff5a0a" style="border-radius:9px;"><a href="{{ $joinUrl }}"
                                            style="display:inline-block;padding:14px 21px;color:#fff;text-decoration:none;font-size:15px;font-weight:bold;">Join
                                            SunXFinder &nbsp;→</a></td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:22px 30px;background:#fff;border-top:1px solid #e7ebf0;">
                            <p style="font-size:14px;line-height:1.7;color:#526783;margin:0;">
                                SunXFinder connects customers with trusted solar companies: receive real quote
                                requests, send your offers and manage your projects in one place.
                                <a href="{{ $websiteUrl }}" style="color:#075fe4;">Visit our website</a>.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#fff0d8;padding:18px 22px;text-align:center;">
                            <div style="font-size:18px;font-weight:bold;color:#10366b;">Sun<b
                                    style="color:#f15b24;">X</b>Finder</div>
                            <div style="font-size:12px;line-height:1.8;color:#526783;margin-top:8px;">
                                Questions? Just reply to this email or write to
                                <a href="mailto:{{ $supportEmail }}" style="color:#075fe4;">{{ $supportEmail }}</a>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>

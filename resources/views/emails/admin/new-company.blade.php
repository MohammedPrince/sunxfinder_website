<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>New company sign-up</title>
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

                    <tr>
                        <td style="background:#fff0d8;padding:30px 30px 26px;">
                            <div style="font-size:24px;line-height:1.25;font-weight:800;color:#10366b;">
                                🏢 New company <span style="color:#f15b24;">waiting for approval</span>
                            </div>
                            <p style="font-size:15px;line-height:1.7;color:#243b60;margin:16px 0 18px;">
                                <strong>{{ $company['name'] }}</strong> just created an account on the website.
                                Review the details and approve or reject it.
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                style="width:100%;font-size:14px;line-height:1.6;color:#243b60;margin:0 0 20px;">
                                @foreach ([
                                    'Company' => $company['name'],
                                    'Username' => $company['username'],
                                    'Email' => $company['email'],
                                    'Phone' => $company['phone'],
                                    'City' => $company['city'],
                                    'Signed up' => $company['registered_at'],
                                ] as $label => $value)
                                    <tr>
                                        <td style="padding:6px 0;width:120px;color:#526783;">{{ $label }}</td>
                                        <td style="padding:6px 0;"><strong>{{ $value ?: '—' }}</strong></td>
                                    </tr>
                                @endforeach
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td bgcolor="#ff5a0a" style="border-radius:9px;"><a href="{{ $reviewUrl }}"
                                            style="display:inline-block;padding:14px 21px;color:#fff;text-decoration:none;font-size:15px;font-weight:bold;">Review
                                            in admin &nbsp;→</a></td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#fff0d8;padding:18px 22px;text-align:center;">
                            <div style="font-size:12px;line-height:1.8;color:#526783;">
                                Automatic notice from the SunXFinder website.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>New customer sign-up</title>
</head>

<body style="margin:0;padding:0;background:#f4f6f8;color:#10366b;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f6f8;">
        <tr>
            <td align="center" style="padding:24px 10px;">
                <table role="presentation" width="680" cellpadding="0" cellspacing="0" border="0"
                    style="width:100%;max-width:680px;background:#fff;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:22px 28px;background:#fff;text-align:center;">
                            <img src="{{ $logoUrl }}" alt="SunXFinder" width="180"
                                style="display:block;margin:0 auto;border:0;max-width:180px;height:auto;">
                            @include('emails.partials.brand-tagline', ['emailTaglineLocale' => 'en'])
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#fff0d8;padding:30px;">
                            <h1 style="margin:0;color:#10366b;font-size:24px;line-height:1.3;">New customer registration</h1>
                            <p style="font-size:15px;line-height:1.7;color:#243b60;margin:14px 0 18px;">
                                A new customer has created an account. Review their details and account status in the admin panel.
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                style="width:100%;font-size:14px;line-height:1.6;color:#243b60;margin:0 0 20px;">
                                @foreach ([
                                    'Customer' => $customer['name'],
                                    'Username' => $customer['username'],
                                    'Email' => $customer['email'],
                                    'Phone' => $customer['phone'],
                                    'City' => $customer['city'],
                                    'Address' => $customer['address'],
                                    'Registration source' => ucfirst($customer['source']),
                                    'Signed up' => $customer['registered_at'],
                                ] as $label => $value)
                                    <tr>
                                        <td style="padding:6px 0;width:150px;color:#526783;vertical-align:top;">{{ $label }}</td>
                                        <td style="padding:6px 0;overflow-wrap:anywhere;"><strong>{{ $value ?: '—' }}</strong></td>
                                    </tr>
                                @endforeach
                            </table>
                            <a href="{{ $reviewUrl }}"
                                style="display:inline-block;padding:13px 20px;border-radius:9px;background:#ff5a0a;color:#fff;text-decoration:none;font-size:14px;font-weight:bold;">
                                Review customer in admin &nbsp;→
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#fff0d8;padding:18px 22px;text-align:center;color:#526783;font-size:12px;line-height:1.8;">
                            Automatic notice from the SunXFinder website.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>

@php
    /** Passed by App\Http\Middleware\SiteMaintenance. */
    /** @var bool $isArabic */
    /** @var string|null $title */
    /** @var string|null $message */
    /** @var \Illuminate\Support\Carbon|null $until */
@endphp
<!doctype html>
<html lang="{{ $isArabic ? 'ar' : 'en' }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title ?: 'SunXFinder' }} · SunXFinder</title>
    <link rel="icon" type="image/png" href="{{ asset_v('visitors/images/logo-128.png') }}">
    {{-- Same fonts as the home page (layouts/app.blade.php): DM Sans + Manrope, plus Cairo for Arabic --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@700;800{{ $isArabic ? '&family=Cairo:wght@400;600;700;800' : '' }}&display=swap">
    <style>
        :root {
            --navy: #0b2d5c;
            --orange: #fc5202;
            --sun: #ffb31a;
            --muted: #5f7189;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px 16px;
            /* Home page body: "DM Sans", Arial (sunxfinder.css); Arabic pages: Cairo first */
            font-family:
                {!! $isArabic ? 'Cairo, "DM Sans", Tahoma, Arial, sans-serif' : '"DM Sans", Arial, sans-serif' !!}
            ;
            color: var(--navy);
            background:
                radial-gradient(circle at 85% 10%, rgba(255, 179, 26, .28), transparent 40%),
                radial-gradient(circle at 10% 95%, rgba(21, 101, 192, .08), transparent 45%),
                linear-gradient(180deg, #fffaf4 0%, #fff 100%);
        }

        .card {
            width: min(560px, 100%);
            padding: 40px 32px 30px;
            border: 1px solid #e6edf5;
            border-radius: 26px;
            background: #fff;
            box-shadow: 0 30px 80px rgba(11, 45, 92, .12);
            text-align: center;
        }

        .logo {
            width: 72px;
            height: 72px;
            margin-bottom: 18px;
        }

        .sun {
            display: inline-grid;
            place-items: center;
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            border-radius: 50%;
            background: #fff4e0;
            font-size: 32px;
            animation: spin 12s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .kicker {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 999px;
            background: #fff0e6;
            color: var(--orange);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .05em;
        }

        /* Home page headings: Manrope, "DM Sans" with -0.02em (home.css); Arabic: Cairo, no spacing */
        h1 {
            margin: 14px 0 12px;
            font-family:
                {!! $isArabic ? 'Cairo, Tahoma, sans-serif' : 'Manrope, "DM Sans", sans-serif' !!}
            ;
            font-size: clamp(28px, 3.4vw, 40px);
            font-weight: 800;
            line-height:
                {{ $isArabic ? '1.3' : '1.15' }}
            ;
            letter-spacing:
                {{ $isArabic ? '0' : '-0.02em' }}
            ;
        }

        .message {
            margin: 0 0 22px;
            color: var(--muted);
            font-size: 16px;
            line-height: 1.8;
            white-space: pre-line;
        }

        .until {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 22px;
            padding: 10px 16px;
            border-radius: 12px;
            background: #f6f9fc;
            font-weight: 700;
        }

        .contact {
            margin: 0 0 18px;
            color: var(--muted);
            font-size: 14px;
        }

        .contact a {
            color: var(--orange);
            font-weight: 700;
        }

        .lang {
            display: inline-flex;
            gap: 6px;
            padding: 4px;
            border: 1px solid #e6edf5;
            border-radius: 12px;
        }

        .lang a {
            padding: 6px 14px;
            border-radius: 8px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .lang a.active {
            background: var(--navy);
            color: #fff;
        }

        footer {
            margin-top: 22px;
            color: #8a96a8;
            font-size: 12px;
        }

        @media (prefers-reduced-motion: reduce) {
            .sun {
                animation: none;
            }
        }
    </style>
</head>

<body>
    <main class="card">
        <img class="logo" src="{{ asset_v('visitors/images/logo-128.webp') }}" alt="SunXFinder" width="72" height="72">
        <div><span class="sun" aria-hidden="true">☀️</span></div>

        <span class="kicker">{{ $isArabic ? 'صيانة مجدولة' : 'Scheduled maintenance' }}</span>

        <h1>{{ $title ?: ($isArabic ? 'الموقع تحت الصيانة' : 'We will be back soon') }}</h1>

        <p class="message">
            {{ $message ?: ($isArabic ? 'نعمل على تحسين الموقع. شكراً لصبرك.' : 'We are improving the website. Thank you for your patience.') }}
        </p>

        @if ($until && $until->isFuture())
            <div class="until">
                ⏱ {{ $isArabic ? 'نتوقع العودة' : 'Expected back' }}:
                <span dir="ltr">{{ $until->format('M d, Y · H:i') }}</span>
            </div>
        @endif

        <p class="contact">
            {{ $isArabic ? 'تحتاج مساعدة؟ راسلنا على' : 'Need help? Email us at' }}
            <a href="mailto:{{ config('mail.contact_address', 'info@sunxfinder.com') }}" dir="ltr">{{
                config('mail.contact_address', 'info@sunxfinder.com') }}</a>
        </p>

        <nav class="lang" aria-label="Language">
            <a href="{{ url()->current() }}?lang=en" class="{{ $isArabic ? '' : 'active' }}" lang="en">English</a>
            <a href="{{ url()->current() }}?lang=ar" class="{{ $isArabic ? 'active' : '' }}" lang="ar">العربية</a>
        </nav>
        <p>
            {{ $isArabic ? 'عدد الزيارات' : 'Visitors' }}: {{ number_format($visitorCount ?? 0) }}
        </p>
        <footer>
            <span aria-hidden="true"> </span>
            Sun<b style="color: #fc5202;">X</b>Finder
        </footer>
    </main>
</body>

</html>
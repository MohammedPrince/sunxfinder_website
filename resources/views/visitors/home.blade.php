@extends('visitors.layouts.app')
@section('title', __('messages.SunXFinder - Find. Compare. Connect.'))

@push('styles')
    <link rel="stylesheet" href="{{ asset_v('visitors/css/home.css') }}">
@endpush

@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
    $customerRegisterUrl = route('customer.register', ['lang' => $locale]);
    // A company that is already logged in goes to its dashboard instead of sign-up.
    $companyLoggedIn = auth('company')->check();
    $companyRegisterUrl = $companyLoggedIn
        ? route('company.dashboard')
        : route('company.register', ['lang' => $locale]);
    $appLinks = $appLinks ?? [];
@endphp

@section('content')

    {{-- ============ Hero: the three words ============ --}}
    <section class="hx-hero">
        <div class="container hx-hero-grid">
            <div class="hx-hero-copy">
                <span class="hx-eyebrow">
                    <i aria-hidden="true"></i>{{ __('messages.Solar energy, made simple') }}
                </span>

                <h1 class="hx-title">
                    <span class="hx-word hx-word-find">{{ __('messages.Find.') }}</span>
                    <span class="hx-word hx-word-compare">{{ __('messages.Compare.') }}</span>
                    <span class="hx-word hx-word-connect">{{ __('messages.Connect.') }}</span>
                </h1>

                <p class="hx-lead">
                    {{ __('messages.SunXFinder helps you find trusted solar companies, compare their offers side by side, and connect with the one that suits your home, business or farm.') }}
                </p>

                <div class="hx-actions">
                    <a class="hx-btn hx-btn-primary" href="{{ $customerRegisterUrl }}">
                        {{ __('messages.Get free offers') }}
                        <span aria-hidden="true">{{ $isArabic ? '←' : '→' }}</span>
                    </a>
                    <a class="hx-btn hx-btn-ghost" href="#how">{{ __('messages.See how it works') }}</a>
                </div>

                <ul class="hx-checks">
                    <li>{{ __('messages.Free for customers') }}</li>
                    <li>{{ __('messages.Verified companies') }}</li>
                    <li>{{ __('messages.No obligation') }}</li>
                </ul>

                <p class="hx-company-link">
                    {{ __('messages.Are you a solar company?') }}
                    <a href="{{ $companyRegisterUrl }}">{{ __('messages.Join SunXFinder') }}</a>
                </p>
            </div>

            {{-- Logo with the three steps around it --}}
            <div class="hx-hero-art" aria-hidden="true">
                <div class="hx-sun-glow"></div>
                <div class="hx-logo-disc">
                    <picture>
                        <source type="image/webp" srcset="{{ asset_v('visitors/images/logo-256.webp') }} 1x, {{ asset_v('visitors/images/logo-512.webp') }} 2x">
                        <img src="{{ asset_v('visitors/images/logo-256.png') }}" alt="" width="256" height="256" fetchpriority="high">
                    </picture>
                </div>

                <div class="hx-chip hx-chip-find">
                    <span class="hx-chip-icon">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    </span>
                    <span><strong>{{ __('messages.Find') }}</strong><small>{{ __('messages.Verified solar companies') }}</small></span>
                </div>
                <div class="hx-chip hx-chip-compare">
                    <span class="hx-chip-icon">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M5 7h14M5 7l-3 7a4 4 0 0 0 6 0L5 7Zm14 0-3 7a4 4 0 0 0 6 0l-3-7Z"/></svg>
                    </span>
                    <span><strong>{{ __('messages.Compare') }}</strong><small>{{ __('messages.Offers side by side') }}</small></span>
                </div>
                <div class="hx-chip hx-chip-connect">
                    <span class="hx-chip-icon">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                    <span><strong>{{ __('messages.Connect') }}</strong><small>{{ __('messages.Choose and go solar') }}</small></span>
                </div>
            </div>
        </div>

        @if ($verifiedCount > 0)
            <div class="container">
                <p class="hx-proof">
                    <strong>{{ number_format($verifiedCount) }}</strong>
                    {{ __('messages.verified solar companies are ready to send you offers') }}
                </p>
            </div>
        @endif
    </section>

    {{-- ============ How it works: one card per word ============ --}}
    <section class="hx-section" id="how">
        <div class="container">
            <div class="hx-heading">
                <span class="hx-kicker">{{ __('messages.How it works') }}</span>
                <h2>{{ __('messages.Three simple steps to go solar') }}</h2>
                <p>{{ __('messages.No phone calls, no guessing. Tell us what you need and let the companies come to you.') }}</p>
            </div>

            <ol class="hx-steps">
                <li class="hx-step hx-step-find">
                    <span class="hx-step-no">1</span>
                    <span class="hx-step-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    </span>
                    <h3>{{ __('messages.Find') }}</h3>
                    <p>{{ __('messages.Tell us what you need for your home, business or farm. We show your request to verified solar companies.') }}</p>
                </li>

                <li class="hx-step hx-step-compare">
                    <span class="hx-step-no">2</span>
                    <span class="hx-step-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M5 7h14M5 7l-3 7a4 4 0 0 0 6 0L5 7Zm14 0-3 7a4 4 0 0 0 6 0l-3-7Z"/></svg>
                    </span>
                    <h3>{{ __('messages.Compare') }}</h3>
                    <p>{{ __('messages.Receive offers from several companies and compare prices, details and experience in one place.') }}</p>
                </li>

                <li class="hx-step hx-step-connect">
                    <span class="hx-step-no">3</span>
                    <span class="hx-step-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                    <h3>{{ __('messages.Connect') }}</h3>
                    <p>{{ __('messages.Accept the offer you like best, then follow your installation step by step until it is done.') }}</p>
                </li>
            </ol>

            <div class="hx-center">
                <a class="hx-btn hx-btn-primary" href="{{ $customerRegisterUrl }}">{{ __('messages.Start with step 1') }}</a>
                <small class="hx-note">{{ __('messages.It is free and takes about 2 minutes.') }}</small>
            </div>


        <div class="hx-app-walkthrough">
            {{-- App description --}}
            <div class="hx-app-copy">
                <span class="hx-kicker">
                    {{ $isArabic ? 'تطبيق SunXFinder' : 'THE SUNXFINDER APP' }}
                </span>

                <h2>
                    {{ $isArabic
                        ? 'كل ما تحتاجه للطاقة الشمسية في تطبيق واحد'
                        : 'Your solar project, made simple' }}
                </h2>

                <p>
                    {{ $isArabic
                        ? 'اكتشف الشركات الموثوقة، وقارن العروض، وتابع مشروعك بسهولة من هاتفك.'
                        : 'Find trusted companies, compare offers, and follow your project, all from your phone.' }}
                </p>

                <ul class="hx-app-highlights">
                    <li>
                        {{ $isArabic ? 'اطلب عروضاً تناسب احتياجاتك' : 'Request offers for your needs' }}
                    </li>
                    <li>
                        {{ $isArabic ? 'قارن الأسعار والتفاصيل بسهولة' : 'Compare prices and details' }}
                    </li>
                    <li>
                        {{ $isArabic ? 'تابع مشروعك خطوة بخطوة' : 'Follow your project step by step' }}
                    </li>
                </ul>

                <div class="hx-actions hx-app-actions">
                    <a class="hx-btn hx-btn-primary" href="{{ $customerRegisterUrl }}">
                        {{ $isArabic ? 'ابدأ الآن' : 'Get started' }}
                        <span aria-hidden="true">
                            {{ $isArabic ? '←' : '→' }}
                        </span>
                    </a>

                    {{-- Shown only while the admin has it switched on (Admin → App Versions) --}}
                    @if (!empty($apkDownloadUrl))
                        <a class="hx-btn hx-btn-outline" href="{{ $apkDownloadUrl }}" download rel="nofollow">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 3v12" />
                                <path d="m7 10 5 5 5-5" />
                                <path d="M5 21h14" />
                            </svg>
                            {{ $isArabic ? 'تحميل' : 'Direct Download' }}
                        </a>
                    @endif

                    @if (!empty($playStoreUrl))
                        <a class="hx-store" href="{{ $playStoreUrl }}" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M4 3.5v17l9.5-8.5L4 3.5Zm11 7L6.5 2.3l10.3 5.9L15 10.5Zm0 3 1.8 2.3L6.5 21.7 15 13.5Zm1.4-1.5L19.7 10l2.3 1.3c.6.4.6 1.1 0 1.4L19.7 14l-3.3-2Z"/></svg>
                            <span><small>{{ __('messages.Get it on') }}</small>Google Play</span>
                        </a>
                    @endif
                </div>
            </div>

            {{-- App screenshots slider --}}
            @php
                // Each screen has {n}.png (fallback), {n}.webp (720w) and {n}-360.webp
                $sliderImages = [1, 2, 3, 4, 5, 6, 8, 9, 10];

                $sliderFolder = $isArabic
                    ? 'visitors/images/app-slider/ar'
                    : 'visitors/images/app-slider/en';
            @endphp

            <div class="hx-app-visual">
                <div
                    class="hx-app-slider"
                    data-app-slider
                    dir="{{ $isArabic ? 'rtl' : 'ltr' }}"
                    aria-label="{{ $isArabic ? 'صور تطبيق SunXFinder' : 'SunXFinder app screenshots' }}"
                    aria-roledescription="{{ $isArabic ? 'عارض صور' : 'carousel' }}"
                >
                    <div class="hx-app-slider-track">
                        @foreach ($sliderImages as $index => $image)
                            <div
                                class="hx-app-slide {{ $index === 0 ? 'is-active' : '' }}"
                                aria-hidden="{{ $index === 0 ? 'false' : 'true' }}"
                            >
                                <picture>
                                    <source
                                        type="image/webp"
                                        srcset="{{ asset_v($sliderFolder . '/' . $image . '-360.webp') }} 360w,
                                                {{ asset_v($sliderFolder . '/' . $image . '.webp') }} 720w"
                                        sizes="(max-width: 760px) 240px, 290px"
                                    >
                                    <img
                                        src="{{ asset_v($sliderFolder . '/' . $image . '.png') }}"
                                        width="720"
                                        height="1280"
                                        alt="{{ $isArabic
                                            ? 'شاشة تطبيق SunXFinder رقم ' . ($index + 1)
                                            : 'SunXFinder app screen ' . ($index + 1) }}"
                                        loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                        @if ($index === 0) fetchpriority="high" @endif
                                        decoding="async"
                                    >
                                </picture>
                            </div>
                        @endforeach
                    </div>

                    {{-- Arrows point the direction they move, so they swap glyphs in RTL.
                         dir="ltr" stops the browser mirroring the brackets a second time. --}}
                    <button
                        type="button"
                        class="hx-app-slider-arrow hx-app-slider-prev"
                        data-slider-prev
                        aria-label="{{ $isArabic ? 'الصورة السابقة' : 'Previous image' }}"
                    >
                        <span aria-hidden="true" dir="ltr">{!! $isArabic ? '&#10095;' : '&#10094;' !!}</span>
                    </button>

                    <button
                        type="button"
                        class="hx-app-slider-arrow hx-app-slider-next"
                        data-slider-next
                        aria-label="{{ $isArabic ? 'الصورة التالية' : 'Next image' }}"
                    >
                        <span aria-hidden="true" dir="ltr">{!! $isArabic ? '&#10094;' : '&#10095;' !!}</span>
                    </button>

                    <div
                        class="hx-app-slider-dots"
                        aria-label="{{ $isArabic ? 'اختيار الصورة' : 'Choose image' }}"
                    >
                        @foreach ($sliderImages as $index => $image)
                            <button
                                type="button"
                                class="hx-app-slider-dot {{ $index === 0 ? 'is-active' : '' }}"
                                data-slide-to="{{ $index }}"
                                aria-label="{{ $isArabic
                                    ? 'الصورة ' . ($index + 1)
                                    : 'Image ' . ($index + 1) }}"
                                aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                            ></button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>




        </div>
    </section>

    {{-- ============ Solutions ============ --}}
    <section class="hx-section hx-section-soft" id="solutions">
        <div class="container">
            <div class="hx-heading">
                <span class="hx-kicker">{{ __('messages.Solutions') }}</span>
                <h2>{{ __('messages.Solar for every need') }}</h2>
                <p>{{ __('messages.Whatever you want to power, there is a company on SunXFinder that can help.') }}</p>
            </div>

            <div class="hx-solutions">
                <article class="hx-solution">
                    <span class="hx-solution-icon hx-tone-blue" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/></svg>
                    </span>
                    <h3>{{ __('messages.Home energy') }}</h3>
                    <p>{{ __('messages.Solar panels, batteries and backup power for homeowners.') }}</p>
                </article>

                <article class="hx-solution">
                    <span class="hx-solution-icon hx-tone-orange" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="1.5"/><path d="M9 7h1M14 7h1M9 11h1M14 11h1M9 15h1M14 15h1M10 21v-3h4v3"/></svg>
                    </span>
                    <h3>{{ __('messages.Business & industry') }}</h3>
                    <p>{{ __('messages.Energy options for shops, offices, facilities and operations.') }}</p>
                </article>

                <article class="hx-solution">
                    <span class="hx-solution-icon hx-tone-green" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V11"/><path d="M12 11c0-4 3-7 7-7 0 4-3 7-7 7Z"/><path d="M12 14c0-3-2.5-5.5-6-5.5 0 3.5 2.5 5.5 6 5.5Z"/><path d="M4 21h16"/></svg>
                    </span>
                    <h3>{{ __('messages.Farms & water pumps') }}</h3>
                    <p>{{ __('messages.Solar-powered irrigation and remote energy systems.') }}</p>
                </article>
            </div>
        </div>
    </section>

    {{-- ============ Companies ============ --}}
    <section class="hx-section" id="companies">
        <div class="container">
            <div class="hx-heading hx-heading-row">
                <div>
                    <span class="hx-kicker">{{ __('messages.Companies') }}</span>
                    <h2>{{ __('messages.Meet some of our verified companies') }}</h2>
                    <p>{{ __('messages.Every company is reviewed and approved by our team.') }}</p>
                </div>

                @if ($companies->count() > 1)
                    <div class="company-slider-controls">
                        <button type="button" class="company-slider-btn" id="companiesPrev" aria-label="{{ __('messages.Previous companies') }}">
                            {{-- The controls are forced LTR in Arabic too, so the glyphs never swap --}}
                            <span aria-hidden="true">←</span>
                        </button>
                        <button type="button" class="company-slider-btn" id="companiesNext" aria-label="{{ __('messages.Next companies') }}">
                            <span aria-hidden="true">→</span>
                        </button>
                    </div>
                @endif
            </div>

            <div class="companies-slider" id="companiesSlider">
                @forelse ($companies as $carouselEntry)
                    @php
                        $company = $carouselEntry->company;

                        $description = $isArabic
                            ? ($company->ar_description ?: $company->description)
                            : ($company->description ?: $company->ar_description);

                        $services = $isArabic
                            ? ($company->ar_services ?: $company->services)
                            : ($company->services ?: $company->ar_services);

                        $serviceList = collect(preg_split('/[,،]/u', $services ?? ''))
                            ->map(fn($service) => trim($service))
                            ->filter()
                            ->take(3);

                        $reviewCount = (int) ($company->visible_reviews ?? 0);
                        $rating = $reviewCount > 0 ? round((float) $company->visible_rating, 1) : null;
                        $filledStars = $rating !== null ? max(0, min(5, (int) round($rating))) : 0;
                    @endphp

                    <article class="company-card" data-interval="{{ $carouselEntry->interval_seconds }}">
                        <div class="company-card-top">
                            <div class="company-logo-placeholder">
                                {{ mb_strtoupper(mb_substr($company->name, 0, 1)) }}
                            </div>

                            <div class="company-main-info">
                                <h3>{{ $company->name }}</h3>
                                <span class="company-location">
                                    <span aria-hidden="true">⌖</span>
                                    {{ $company->city ? $company->city . ', ' : '' }}{{ __('messages.Sudan') }}
                                </span>

                                <div class="hx-company-rating" dir="ltr">
                                    @if ($rating !== null)
                                        <span class="hx-stars" aria-label="{{ $rating }} out of 5 stars">{{ str_repeat('★', $filledStars) }}<span
                                                class="hx-stars-off">{{ str_repeat('★', 5 - $filledStars) }}</span></span>
                                        <strong>{{ number_format($rating, 1) }}</strong>
                                        <span class="hx-rating-count">({{ number_format($reviewCount) }} {{ __('messages.customer reviews') }})</span>
                                    @else
                                        <span class="hx-rating-empty">{{ __('messages.Not rated yet') }}</span>
                                    @endif
                                </div>
                            </div>

                            @if ($company->is_verified)
                                <span class="verification-badge">
                                    <span aria-hidden="true">✓</span>
                                    {{ __('messages.Verified') }}
                                </span>
                            @endif
                        </div>

                        @if ($company->year_of_exp || $company->number_of_projects)
                            <div class="hx-company-facts">
                                @if ($company->year_of_exp)
                                    <span>{{ __('messages.Years of experience') }}: <strong>{{ $company->year_of_exp }}</strong></span>
                                @endif
                                @if ($company->number_of_projects)
                                    <span>{{ __('messages.Projects done') }}: <strong>{{ $company->number_of_projects }}</strong></span>
                                @endif
                            </div>
                        @endif

                        @if ($description)
                            <div class="company-description">
                                {!! \Mews\Purifier\Facades\Purifier::clean($description) !!}
                            </div>
                        @endif

                        @if ($serviceList->isNotEmpty())
                            <div class="company-specialties">
                                @foreach ($serviceList as $service)
                                    <span>{{ $service }}</span>
                                @endforeach
                            </div>
                        @endif

                        <div class="company-card-footer">
                            <a href="{{ route('company-profile.show', ['slug' => $company->slug, 'lang' => $locale]) }}"
                                class="hx-btn hx-btn-outline hx-btn-sm">
                                {{ __('messages.View company') }}
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="company-empty-state">
                        <p>{{ __('messages.No companies available yet.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- ============ For companies ============ --}}
    <section class="hx-section hx-section-navy" id="for-companies">
        <div class="container hx-split">
            <div>
                <span class="hx-kicker hx-kicker-light">{{ __('messages.For solar companies') }}</span>
                <h2>{{ __('messages.Get customers who are ready to go solar') }}</h2>
                <p>{{ __('messages.Customers send their needs. You send your offer, win the project and keep the customer updated, all from one dashboard.') }}</p>

                <a class="hx-btn hx-btn-primary" href="{{ $companyRegisterUrl }}">
                    {{ $companyLoggedIn ? __('messages.Go to your dashboard') : __('messages.Join as a company') }}
                </a>
            </div>

            <ul class="hx-benefits">
                <li>
                    <strong>{{ __('messages.Real requests') }}</strong>
                    <span>{{ __('messages.See what each customer needs before you send a price.') }}</span>
                </li>
                <li>
                    <strong>{{ __('messages.Send offers in minutes') }}</strong>
                    <span>{{ __('messages.Price, message and details in one simple form.') }}</span>
                </li>
                <li>
                    <strong>{{ __('messages.Manage your projects') }}</strong>
                    <span>{{ __('messages.Track each installation and keep the customer informed.') }}</span>
                </li>
                <li>
                    <strong>{{ __('messages.A verified profile') }}</strong>
                    <span>{{ __('messages.Show your services and experience to build trust.') }}</span>
                </li>
            </ul>
        </div>
    </section>

    {{-- ============ App download (only when store links are set) ============ --}}
    @if (!empty($appLinks))
        <section class="hx-section hx-app">
            <div class="container hx-app-panel">
                <div>
                    <h2>{{ __('messages.Get the SunXFinder app') }}</h2>
                    <p>{{ __('messages.Request offers, compare them and follow your project from your phone.') }}</p>
                </div>
                <div class="hx-app-buttons">
                    @isset($appLinks['android'])
                        <a class="hx-store" href="{{ $appLinks['android'] }}" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M4 3.5v17l9.5-8.5L4 3.5Zm11 7L6.5 2.3l10.3 5.9L15 10.5Zm0 3 1.8 2.3L6.5 21.7 15 13.5Zm1.4-1.5L19.7 10l2.3 1.3c.6.4.6 1.1 0 1.4L19.7 14l-3.3-2Z"/></svg>
                            <span><small>{{ __('messages.Get it on') }}</small>Google Play</span>
                        </a>
                    @endisset
                    @isset($appLinks['ios'])
                        <a class="hx-store" href="{{ $appLinks['ios'] }}" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M16.4 12.6c0-2.5 2-3.6 2.1-3.7-1.2-1.7-3-1.9-3.6-2-1.5-.2-3 .9-3.8.9-.8 0-2-.9-3.3-.9-1.7 0-3.3 1-4.2 2.5-1.8 3.1-.5 7.7 1.3 10.2.9 1.2 1.9 2.6 3.2 2.6 1.3-.1 1.8-.8 3.3-.8 1.6 0 2 .8 3.4.8 1.4 0 2.3-1.3 3.1-2.5 1-1.4 1.4-2.8 1.4-2.9-.1 0-2.9-1.1-2.9-4.2ZM13.9 5c.7-.8 1.2-2 1-3.2-1 .1-2.2.7-3 1.5-.6.7-1.2 1.9-1 3.1 1.1.1 2.3-.6 3-1.4Z"/></svg>
                            <span><small>{{ __('messages.Download on the') }}</small>App Store</span>
                        </a>
                    @endisset
                </div>
            </div>
        </section>
    @endif

    {{-- ============ FAQ ============ --}}
    <section class="hx-section hx-section-soft faq-section" id="faq">
        <div class="container">
            <div class="hx-heading">
                <span class="hx-kicker">{{ __('messages.FAQ') }}</span>
                <h2>{{ __('messages.Frequently Asked Questions') }}</h2>
            </div>

            @php
                $faqs = [
                    ['messages.How does SunXFinder work for homeowners and businesses?', ['messages.Homeowners and businesses tell us about their energy needs, and we match them with pre-vetted solar and energy companies in their area. You can then compare options, read verified reviews, and connect directly with the provider of your choice—all without any fees or obligation.']],
                    ['messages.How does SunXFinder work for energy companies?', ['messages.Energy companies join our network to receive qualified project leads from customers actively seeking their services. You can showcase your expertise, verify your credentials, and connect with customers who are ready to move forward with their energy projects.']],
                    ['messages.Is there a fee to use SunXFinder?', ['messages.For homeowners and businesses:', 'messages.No, our service is completely free. We never charge fees for searching, comparing, or connecting with energy companies.', 'messages.For energy companies:', 'messages.Yes, we charge a modest participation fee to maintain our network, verify company credentials, and provide ongoing platform improvements. This ensures you receive high-quality, serious inquiries only.']],
                    ['messages.How do you verify the companies in your network?', ['messages.We conduct a thorough verification process that includes: checking licenses and insurance, verifying business credentials, reviewing customer feedback and references, confirming service areas and specialties, and conducting ongoing quality checks to maintain network standards.']],
                    ['messages.What types of energy projects and services do you support?', ['messages.We support the full spectrum: residential solar, commercial solar, agricultural solar, off-grid systems, battery storage, energy audits, efficiency upgrades, and more. Companies can specify their exact services to receive the most relevant matches.']],
                    ['messages.How long does it take to see results?', ['messages.For homeowners and businesses:', 'messages.You can start seeing matches and receiving company information immediately after submitting your request, with detailed quotes typically arriving within 24-48 hours.', 'messages.For energy companies:', 'messages.Approved providers can start receiving qualified leads within 24 hours of joining our network, depending on service area and customer demand.']],
                ];
            @endphp

            <div class="faq-container">
                @foreach ($faqs as [$question, $answers])
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>{{ __($question) }}</h3>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </div>
                        <div class="faq-answer">
                            {{-- Pairs of "audience:" + answer render as a label and a paragraph --}}
                            @foreach ($answers as $answer)
                                @if (str_ends_with($answer, ':'))
                                    <strong class="hx-faq-label">{{ __($answer) }}</strong>
                                @else
                                    <p>{{ __($answer) }}</p>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ Contact us ============ --}}
    @php
        $contactEmail = config('mail.contact_address', 'info@sunxfinder.com');
        $contactErrors = $errors->getBag('contact');
    @endphp
    <section class="hx-section hx-contact" id="contact">
        <div class="container hx-contact-grid">
            <div class="hx-contact-info">
                <span class="hx-kicker">{{ __('messages.Contact us') }}</span>
                <h2>{{ __('messages.We are here to help') }}</h2>
                <p>{{ __('messages.Have a question about a quote, a company or your account? Send us a message and our team will get back to you by email.') }}</p>

                <a class="hx-contact-card" href="mailto:{{ $contactEmail }}">
                    <span class="hx-contact-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round" >
                            <rect x="3" y="5" width="18" height="14" rx="2" />
                            <path d="m3 7 9 6 9-6" />
                        </svg>
                    </span>
                    <span>
                        <small>{{ __('messages.Email us') }}</small>
                        <strong dir="ltr">{{ $contactEmail }}</strong>
                    </span>
                </a>

                <ul class="hx-contact-points">
                    <li>{{ __('messages.We usually reply within 1 business day.') }}</li>
                    <li>{{ __('messages.You can write in Arabic or English.') }}</li>
                </ul>
            </div>

            <div class="hx-contact-form-card">
                @if (session('contact_success'))
                    <div class="hx-contact-alert hx-contact-alert-success" role="status">
                        <strong>{{ __('messages.Thank you! Your message has been sent.') }}</strong>
                        <span>{{ __('messages.We will reply to your email as soon as possible.') }}</span>
                    </div>
                @elseif (session('contact_error'))
                    <div class="hx-contact-alert hx-contact-alert-error" role="alert">
                        <strong>{{ __('messages.Your message could not be sent.') }}</strong>
                        <span>{{ __('messages.Please try again, or email us directly at') }} <a href="mailto:{{ $contactEmail }}" dir="ltr">{{ $contactEmail }}</a>.</span>
                    </div>
                @elseif ($contactErrors->any())
                    <div class="hx-contact-alert hx-contact-alert-error" role="alert">
                        <strong>{{ __('messages.Please check the highlighted fields.') }}</strong>
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.send', ['lang' => $locale]) }}" class="hx-contact-form" novalidate>
                    @csrf

                    {{-- Honeypot: hidden from people, bots fill it in. --}}
                    <div class="hx-contact-hp" aria-hidden="true">
                        <label for="contact_website">Website</label>
                        <input type="text" id="contact_website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="hx-contact-row">
                        <div class="hx-field {{ $contactErrors->has('name') ? 'has-error' : '' }}">
                            <label for="contact_name">{{ __('messages.Full name') }} <b>*</b></label>
                            <input id="contact_name" type="text" name="name" value="{{ old('name') }}" maxlength="100"
                                autocomplete="name" placeholder="{{ __('messages.e.g. Mohammed Ahmed') }}" required>
                            @if ($contactErrors->has('name'))
                                <small>{{ $contactErrors->first('name') }}</small>
                            @endif
                        </div>

                        <div class="hx-field {{ $contactErrors->has('email') ? 'has-error' : '' }}">
                            <label for="contact_email">{{ __('messages.Email address') }} <b>*</b></label>
                            <input id="contact_email" type="email" name="email" value="{{ old('email') }}" maxlength="150"
                                autocomplete="email" dir="ltr" placeholder="{{ __('messages.e.g. name@example.com') }}" required>
                            @if ($contactErrors->has('email'))
                                <small>{{ $contactErrors->first('email') }}</small>
                            @endif
                        </div>
                    </div>

                    <div class="hx-contact-row">
                        <div class="hx-field {{ $contactErrors->has('phone') ? 'has-error' : '' }}">
                            <label for="contact_phone">{{ __('messages.Phone number') }} <span>({{ __('messages.optional') }})</span></label>
                            <input id="contact_phone" type="tel" name="phone" value="{{ old('phone') }}" maxlength="20"
                                autocomplete="tel" dir="ltr" placeholder="{{ __('messages.e.g. 0912345678') }}">
                            @if ($contactErrors->has('phone'))
                                <small>{{ $contactErrors->first('phone') }}</small>
                            @endif
                        </div>

                        <div class="hx-field {{ $contactErrors->has('sender_type') ? 'has-error' : '' }}">
                            <label for="contact_sender_type">{{ __('messages.I am a') }} <b>*</b></label>
                            <select id="contact_sender_type" name="sender_type" required>
                                @foreach (['customer' => __('messages.Customer / homeowner'), 'company' => __('messages.Solar company'), 'other' => __('messages.Other')] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('sender_type', 'customer') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @if ($contactErrors->has('sender_type'))
                                <small>{{ $contactErrors->first('sender_type') }}</small>
                            @endif
                        </div>
                    </div>

                    <div class="hx-field {{ $contactErrors->has('subject') ? 'has-error' : '' }}">
                        <label for="contact_subject">{{ __('messages.Subject') }} <b>*</b></label>
                        <input id="contact_subject" type="text" name="subject" value="{{ old('subject') }}" maxlength="150"
                            placeholder="{{ __('messages.What is your message about?') }}" required>
                        @if ($contactErrors->has('subject'))
                            <small>{{ $contactErrors->first('subject') }}</small>
                        @endif
                    </div>

                    <div class="hx-field {{ $contactErrors->has('message') ? 'has-error' : '' }}">
                        <label for="contact_message">{{ __('messages.Message') }} <b>*</b></label>
                        <textarea id="contact_message" name="message" rows="5" maxlength="3000"
                            placeholder="{{ __('messages.Write your message here. Include your request number if it is about a quote.') }}" required>{{ old('message') }}</textarea>
                        @if ($contactErrors->has('message'))
                            <small>{{ $contactErrors->first('message') }}</small>
                        @endif
                    </div>

                    <button type="submit" class="hx-btn hx-btn-primary hx-contact-submit">
                        {{ __('messages.Send message') }}
                    </button>
                </form>
            </div>
        </div>
    </section>

    {{-- ============ Final call to action ============ --}}
    <section class="hx-final" id="start">
        <div class="container hx-final-panel">
            <h2 class="hx-final-title">
                <span class="hx-word-find">{{ __('messages.Find.') }}</span>
                <span class="hx-word-compare">{{ __('messages.Compare.') }}</span>
                <span class="hx-word-connect">{{ __('messages.Connect.') }}</span>
            </h2>
            <p>{{ __('messages.Your solar project starts with one free request.') }}</p>
            <div class="hx-actions hx-actions-center">
                <a class="hx-btn hx-btn-primary" href="{{ $customerRegisterUrl }}">{{ __('messages.Get free offers') }}</a>
                <a class="hx-btn hx-btn-light" href="{{ $companyRegisterUrl }}">{{ __('messages.I am a solar company') }}</a>
            </div>
        </div>
    </section>

    <script>

document.querySelectorAll('[data-app-slider]').forEach((slider) => {
    if (slider.dataset.sliderInitialized === 'true') return;
    slider.dataset.sliderInitialized = 'true';

    const slides = Array.from(
        slider.querySelectorAll('.hx-app-slide')
    );

    const dots = Array.from(
        slider.querySelectorAll('.hx-app-slider-dot')
    );

    const prevButton = slider.querySelector('[data-slider-prev]');
    const nextButton = slider.querySelector('[data-slider-next]');

    if (!slides.length) return;

    let currentIndex = 0;
    let autoplayTimer = null;

    function showSlide(index) {
        currentIndex = (index + slides.length) % slides.length;

        slides.forEach((slide, i) => {
            const active = i === currentIndex;

            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', String(!active));
        });

        dots.forEach((dot, i) => {
            const active = i === currentIndex;

            dot.classList.toggle('is-active', active);
            dot.setAttribute('aria-current', String(active));
        });
    }

    function stopAutoplay() {
        if (autoplayTimer !== null) {
            clearInterval(autoplayTimer);
            autoplayTimer = null;
        }
    }

    function startAutoplay() {
        stopAutoplay();

        if (
            slides.length < 2 ||
            window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ) {
            return;
        }

        autoplayTimer = setInterval(() => {
            showSlide(currentIndex + 1);
        }, 4000);
    }

    prevButton?.addEventListener('click', () => {
        showSlide(currentIndex - 1);
        startAutoplay();
    });

    nextButton?.addEventListener('click', () => {
        showSlide(currentIndex + 1);
        startAutoplay();
    });

    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            showSlide(index);
            startAutoplay();
        });
    });

    slider.addEventListener('mouseenter', stopAutoplay);
    slider.addEventListener('mouseleave', startAutoplay);
    slider.addEventListener('focusin', stopAutoplay);
    slider.addEventListener('focusout', (event) => {
        if (!slider.contains(event.relatedTarget)) {
            startAutoplay();
        }
    });

    showSlide(0);
    startAutoplay();
});


</script>

@endsection

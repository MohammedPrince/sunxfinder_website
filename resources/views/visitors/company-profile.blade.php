@extends('visitors.layouts.app')
@section('title', app()->getLocale() === 'ar' ? 'SunXFinder - بروفايل الشركة' : 'SunXFinder - Company Profile')

{{-- New design of the company profile. The classic design is company-profile-classic.blade.php
     (Admin → Settings → Website design chooses between them). --}}
@push('styles')
    <link rel="stylesheet" href="{{ asset_v('visitors/css/company-profile-new.css') }}">
@endpush

@php
    $isArabic = app()->getLocale() === 'ar';
    $hasRating = is_numeric($companyRating) && $companyReviews > 0;
    $reviewCount = count($companyReviewsList);
    $productCount = $companyProducts->count();
    $projectCount = (int) ($company->number_of_projects ?? 0);
    $isMobileDevice = (bool) preg_match('/android|iphone|ipad|ipod/i', (string) request()->userAgent());
    // Quote requests are sent from the app, so the quote button and box are for phones only:
    // a laptop has no app. On a phone they hide when the browser can tell the app is not installed
    // (see the script at the end of this file).
    $quoteUrl = route('app.company', ['slug' => $company->slug]);

    $text = $isArabic
        ? [
            'home' => 'الرئيسية', 'companies' => 'الشركات', 'quote' => 'اطلب عرض سعر من الشركة', 'share' => 'مشاركة الملف',
            'copied' => 'تم نسخ الرابط', 'products' => 'منتجاً معروضاً', 'projects' => 'مشروعاً منجزاً',
            'reviews' => 'تقييماً', 'of' => 'من', 'about' => 'نبذة', 'services' => 'الخدمات', 'productsTab' => 'المنتجات',
            'reviewsTab' => 'التقييمات', 'based' => 'بناءً على :count تقييماً', 'quoteTitle' => 'هل تريد عرض سعر من هذه الشركة؟',
            'quoteText' => 'أرسل طلبك عبر التطبيق واستلم السعر والتفاصيل مباشرة.', 'quoteButton' => 'اطلب عرض سعر',
            'quotePoints' => ['مجاني للعملاء', 'دون أي التزام', 'قارن مع شركات أخرى'], 'call' => 'اتصال', 'email' => 'مراسلة',
            'verifiedTitle' => 'شركة موثّقة من SunXFinder',
        ]
        : [
            'home' => 'Home', 'companies' => 'Companies', 'quote' => 'Request a quote from this company', 'share' => 'Share profile',
            'copied' => 'Link copied', 'products' => 'products listed', 'projects' => 'projects done',
            'reviews' => 'reviews', 'of' => 'from', 'about' => 'About', 'services' => 'Services', 'productsTab' => 'Products',
            'reviewsTab' => 'Reviews', 'based' => 'Based on :count reviews', 'quoteTitle' => 'Want a quote from this company?',
            'quoteText' => 'Send your request in the app and receive the price and details directly.', 'quoteButton' => 'Request a quote',
            'quotePoints' => ['Free for customers', 'No obligation', 'Compare with other companies'], 'call' => 'Call', 'email' => 'Email',
            'verifiedTitle' => 'Verified by SunXFinder',
        ];

    // Stroke icons (24x24), reused across the page.
    $icons = [
        'pin' => '<path d="M12 21s7-6.2 7-11.5a7 7 0 0 0-14 0C5 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'building' => '<path d="M3 21h18M5 21V9l7-5 7 5v12M10 21v-6h4v6"/>',
        'box' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3ZM4 7.5l8 4.5 8-4.5M12 12v9"/>',
        'star' => '<path d="m12 3 2.7 5.5 6 .9-4.4 4.2 1 6L12 16.8 6.7 19.6l1-6L3.3 9.4l6-.9L12 3Z"/>',
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.200 3 14.800 0 18M12 3c-3 3.200-3 14.800 0 18"/>',
        'shield' => '<path d="M12 3 5 6v5c0 4.5 3 8 7 10 4-2 7-5.500 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>',
        'share' => '<path d="M4 12v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7M16 6l-4-4-4 4M12 2v13"/>',
        'check' => '<path d="m5 12.5 4.500 4.500L19 7.500"/>',
        'inverter' => '<rect x="4" y="3" width="16" height="18" rx="3"/><path d="M8 8h8M13 11l-3 4h4l-3 4"/>',
        'solar_panel' => '<path d="M4 5h16l2 12H2L4 5ZM9 5 8 17M15 5l1 12M3 11h18M12 17v4"/>',
        'battery' => '<rect x="3" y="7" width="16" height="11" rx="2.5"/><path d="M21 11v3M7 10.500v4M11 10.500v4"/>',
    ];
    $icon = fn(string $name, int $size = 22) => '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size
        . '" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . str_replace(['.500', '.800', '.200'], ['.5', '.8', '.2'], $icons[$name] ?? $icons['box']) . '</svg>';

    // Star breakdown, only when every published review is in the list (it holds the latest ones).
    $starShares = [];
    if ($reviewCount > 0 && $reviewCount === (int) $companyReviews) {
        $perStar = array_count_values(array_map(fn($review) => max(1, min(5, (int) data_get($review, 'rating', 0))), $companyReviewsList));
        foreach ([5, 4, 3, 2, 1] as $stars) {
            $starShares[$stars] = (int) round(($perStar[$stars] ?? 0) / $reviewCount * 100);
        }
    }
@endphp

@section('content')
    <div class="cp-page">
        {{-- ============ Header band ============ --}}
        <section class="cp-hero">
            <div class="container">
                <nav class="cp-crumbs" aria-label="Breadcrumb">
                    <a href="{{ url('/') }}?lang={{ app()->getLocale() }}">{{ $text['home'] }}</a>
                    <span aria-hidden="true">›</span>
                    <a href="{{ url('/') }}?lang={{ app()->getLocale() }}#companies">{{ $text['companies'] }}</a>
                    <span aria-hidden="true">›</span>
                    <b dir="auto">{{ $companyName }}</b>
                </nav>

                <div class="cp-hero-row">
                    <div class="cp-logo">
                        @if ($companyLogo)
                            <img src="{{ app(\App\Helpers\Helper::class)->publicUrl($company->logo_path) }}"
                                alt="{{ $company->name }} logo" width="132" height="132">
                        @else
                            <span>{{ name_initials($companyName) }}</span>
                        @endif
                    </div>

                    <div class="cp-hero-copy">
                        <div class="cp-badges">
                            @if ($companyVerified)
                                <span class="cp-pill cp-pill-ok"><span aria-hidden="true">✓</span> {{ trans('messages.Verified company') }}</span>
                            @else
                                <span class="cp-pill cp-pill-soft">{{ trans('messages.Verification not confirmed') }}</span>
                            @endif
                        </div>

                        <h1 dir="auto">{{ $companyName }}</h1>

                        <div class="cp-hero-meta">
                            <span>
                                @if ($hasRating)
                                    @php $filledStars = max(0, min(5, (int) round($companyRating))); @endphp
                                    <span class="cp-stars" dir="ltr"
                                        aria-label="{{ $companyRating }} out of 5 stars">{{ str_repeat('★', $filledStars) }}<span
                                            class="cp-stars-off">{{ str_repeat('★', 5 - $filledStars) }}</span></span>
                                    <b>{{ number_format((float) $companyRating, 1) }}</b>
                                    ({{ number_format((int) $companyReviews) }} {{ trans('messages.customer reviews') }})
                                @else
                                    <span class="cp-stars cp-stars-off" aria-hidden="true">★</span>
                                    {{ trans('messages.Not rated yet') }}
                                @endif
                            </span>
                            <span class="cp-hero-city">{!! $icon('pin', 17) !!} <span dir="auto">{{ $companyCity }}</span></span>
                        </div>
                    </div>

                    <div class="cp-hero-actions">
                        @if ($isMobileDevice)
                            <a class="hx-btn hx-btn-primary" href="{{ $quoteUrl }}" data-app-only>{{ $text['quote'] }}</a>
                        @endif
                        <button type="button" class="hx-btn cp-btn-ghost" data-share-profile data-copied="{{ $text['copied'] }}"
                            data-title="{{ $companyName }}">
                            {!! $icon('share', 18) !!} <span>{{ $text['share'] }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <div class="container">
            {{-- ============ Key figures ============ --}}
            <div class="cp-stats">
                <div class="cp-stat">
                    <span class="cp-ico cp-tone-orange">{!! $icon('calendar', 26) !!}</span>
                    <div><b dir="ltr">{{ is_numeric($companyYears) ? $companyYears . '+' : '—' }}</b><small>{{ trans('messages.Years of experience') }}</small></div>
                </div>
                <div class="cp-stat">
                    <span class="cp-ico cp-tone-blue">{!! $icon('building', 26) !!}</span>
                    <div><b>{{ $projectCount > 0 ? number_format($projectCount) : '—' }}</b><small>{{ $text['projects'] }}</small></div>
                </div>
                <div class="cp-stat">
                    <span class="cp-ico cp-tone-green">{!! $icon('box', 26) !!}</span>
                    <div><b>{{ number_format($productCount) }}</b><small>{{ $text['products'] }}</small></div>
                </div>
                <div class="cp-stat">
                    <span class="cp-ico cp-tone-yellow">{!! $icon('star', 26) !!}</span>
                    <div>
                        @if ($hasRating)
                            <b>{{ number_format((float) $companyRating, 1) }}</b>
                            <small>{{ $text['of'] }} {{ number_format((int) $companyReviews) }} {{ $text['reviews'] }}</small>
                        @else
                            <b>—</b><small>{{ trans('messages.Not rated yet') }}</small>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ============ Jump links ============ --}}
            <nav class="cp-tabs" aria-label="{{ $companyName }}">
                <a href="#company-about" class="is-active">{{ $text['about'] }}</a>
                <a href="#company-services">{{ $text['services'] }} <span>{{ count($companyServices) }}</span></a>
                <a href="#company-products">{{ $text['productsTab'] }} <span>{{ $productCount }}</span></a>
                <a href="#company-reviews">{{ $text['reviewsTab'] }} <span>{{ (int) $companyReviews }}</span></a>
            </nav>

            <div class="cp-grid">
                <div class="cp-main">
                    {{-- About --}}
                    <section class="cp-panel" id="company-about">
                        <span class="cp-kicker">{{ trans('messages.COMPANY OVERVIEW') }}</span>
                        <h2 dir="auto">{{ $companyName }}</h2>
                        @if ($companyDescription !== '')
                            <p class="cp-about" dir="auto">{{ $companyDescription }}</p>
                        @endif
                    </section>

                    {{-- Services --}}
                    <section class="cp-panel" id="company-services">
                        <span class="cp-kicker">{{ trans('messages.WHAT WE OFFER') }}</span>
                        <h2>{{ trans('messages.Our services') }}</h2>
                        @if (count($companyServices))
                            <div class="cp-services">
                                @foreach ($companyServices as $service)
                                    <article class="cp-service">
                                        <span class="cp-ico cp-tone-{{ ['orange', 'blue', 'green'][$loop->index % 3] }}">{!! $icon('check', 22) !!}</span>
                                        <h3 dir="auto">{{ $service }}</h3>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <p class="cp-muted">{{ trans('messages.Services will be added by this company.') }}</p>
                        @endif
                    </section>

                    {{-- Products --}}
                    <section class="cp-panel" id="company-products">
                        @if ($companyProducts->isNotEmpty())
                            @php
                                // Filter chips: one per product type, in catalog order.
                                $productTypes = $companyProducts->groupBy('type')->map(fn($group) => [
                                    'label' => company_label('common.product_type_' . $group->first()->type, $group->first()->typeLabel()),
                                    'count' => $group->count(),
                                ]);
                                $productsVisible = 6;
                            @endphp

                            <div data-product-catalog data-visible="{{ $productsVisible }}">
                                <div class="cp-panel-head">
                                    <div>
                                        <span class="cp-kicker">{{ trans('messages.PRODUCT CATALOG') }}</span>
                                        <h2>{{ trans('messages.Our Products') }}</h2>
                                    </div>

                                    @if ($productTypes->count() > 1)
                                        <div class="cp-chips" role="group" aria-label="{{ trans('messages.Filter products') }}">
                                            <button type="button" class="is-active" data-product-filter="all" aria-pressed="true">
                                                {{ trans('messages.All') }} <span>{{ $productCount }}</span>
                                            </button>
                                            @foreach ($productTypes as $type => $info)
                                                <button type="button" data-product-filter="{{ $type }}" aria-pressed="false">
                                                    {{ $info['label'] }} <span>{{ $info['count'] }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="cp-products">
                                    @foreach ($companyProducts as $product)
                                        @php $productLine = collect([$product->supplier?->name, $product->model])->filter()->implode(' · '); @endphp
                                        <article class="company-product-item cp-product cp-product-{{ $product->type }}"
                                            data-product-type="{{ $product->type }}" @if ($loop->index >= $productsVisible) hidden @endif>
                                            <div class="cp-product-top">
                                                <span class="cp-product-tag">{{ company_label('common.product_type_' . $product->type, $product->typeLabel()) }}</span>
                                                {!! $icon($product->type, 44) !!}
                                            </div>
                                            <div class="cp-product-body">
                                                <h3 dir="auto">{{ $product->name }}</h3>
                                                @if ($productLine !== '')
                                                    <small dir="auto">{{ $productLine }}</small>
                                                @endif
                                                @if ($product->description)
                                                    <p dir="auto">{{ $product->description }}</p>
                                                @endif
                                                @if ($product->specs->isNotEmpty())
                                                    <div class="cp-specs">
                                                        @foreach ($product->specs as $spec)
                                                            <span>{{ $spec->spec_name }}: <b dir="auto">{{ $spec->spec_value }}</b></span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </article>
                                    @endforeach
                                </div>

                                @if ($productCount > $productsVisible)
                                    <button type="button" class="hx-btn hx-btn-outline cp-more" data-product-more
                                        data-more="{{ trans('messages.Show all products', ['count' => $productCount]) }}"
                                        data-less="{{ trans('messages.Show fewer products') }}" aria-expanded="false">
                                        {{ trans('messages.Show all products', ['count' => $productCount]) }}
                                    </button>
                                @endif
                            </div>

                            <script>
                                // Show/hide for the catalog: type filter, plus "show all" past the first few.
                                document.querySelectorAll('[data-product-catalog]').forEach(function (catalog) {
                                    const items = Array.from(catalog.querySelectorAll('.company-product-item'));
                                    const more = catalog.querySelector('[data-product-more]');
                                    const limit = Number(catalog.dataset.visible) || 6;
                                    let filter = 'all';
                                    let expanded = false;

                                    function render() {
                                        const matching = items.filter(item => filter === 'all' || item.dataset.productType === filter);
                                        items.forEach(item => { item.hidden = true; });
                                        matching.forEach((item, index) => { item.hidden = !expanded && index >= limit; });
                                        if (more) {
                                            more.hidden = matching.length <= limit;
                                            more.textContent = expanded ? more.dataset.less : more.dataset.more.replace(/\d+/, matching.length);
                                            more.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                                        }
                                    }

                                    catalog.querySelectorAll('[data-product-filter]').forEach(function (button) {
                                        button.addEventListener('click', function () {
                                            filter = button.dataset.productFilter;
                                            expanded = false;
                                            catalog.querySelectorAll('[data-product-filter]').forEach(other => {
                                                other.classList.toggle('is-active', other === button);
                                                other.setAttribute('aria-pressed', other === button ? 'true' : 'false');
                                            });
                                            render();
                                        });
                                    });

                                    if (more) {
                                        more.addEventListener('click', function () {
                                            expanded = !expanded;
                                            render();
                                            if (!expanded) catalog.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                        });
                                    }

                                    render();
                                });
                            </script>
                        @else
                            <span class="cp-kicker">{{ trans('messages.PRODUCT CATALOG') }}</span>
                            <h2>{{ trans('messages.Our Products') }}</h2>
                            <p class="cp-muted">{{ trans('messages.No products have been added yet.') }}</p>
                        @endif
                    </section>

                    {{-- Reviews --}}
                    <section class="cp-panel" id="company-reviews">
                        <div class="cp-panel-head">
                            <div>
                                <span class="cp-kicker">{{ trans('messages.CUSTOMER FEEDBACK') }}</span>
                                <h2>{{ trans('messages.Customer reviews') }}</h2>
                            </div>

                            @if ($reviewCount > 1)
                                <div class="cp-review-controls" dir="ltr">
                                    <button type="button" class="cp-round-btn" data-review-prev
                                        aria-label="{{ trans('messages.Previous') }}">←</button>
                                    <button type="button" class="cp-round-btn" data-review-next
                                        aria-label="{{ trans('messages.Next') }}">→</button>
                                </div>
                            @endif
                        </div>

                        @if ($reviewCount)
                            @if ($hasRating)
                                <div class="cp-score">
                                    <div class="cp-score-main">
                                        <b>{{ number_format((float) $companyRating, 1) }}</b>
                                        <div>
                                            <span class="cp-stars" dir="ltr">{{ str_repeat('★', $filledStars) }}<span
                                                    class="cp-stars-off">{{ str_repeat('★', 5 - $filledStars) }}</span></span>
                                            <small>{{ str_replace(':count', number_format((int) $companyReviews), $text['based']) }}</small>
                                        </div>
                                    </div>
                                    @if ($starShares)
                                        <div class="cp-bars" aria-hidden="true">
                                            @foreach ($starShares as $stars => $share)
                                                <div class="cp-bar">{{ $stars }} <i><u style="width: {{ $share }}%"></u></i></div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div class="cp-reviews" data-review-slider tabindex="0" aria-label="{{ trans('messages.Customer reviews') }}">
                                @foreach ($companyReviewsList as $review)
                                    @php
                                        $reviewStars = max(0, min(5, (int) data_get($review, 'rating', 0)));
                                        $reviewer = data_get($review, 'customer') ?: trans('messages.Customer');
                                    @endphp
                                    <article class="cp-review">
                                        <header>
                                            <span class="cp-avatar cp-avatar-{{ ($loop->index % 3) + 1 }}" aria-hidden="true">{{ name_initials($reviewer) }}</span>
                                            <span class="cp-review-who">
                                                <strong>{{ $reviewer }}</strong>
                                                <small>{{ data_get($review, 'date', '') }}</small>
                                            </span>
                                            <span class="cp-stars cp-stars-dark" dir="ltr"
                                                aria-label="{{ $reviewStars }} out of 5 stars">{{ str_repeat('★', $reviewStars) }}<span
                                                    class="cp-stars-off">{{ str_repeat('★', 5 - $reviewStars) }}</span></span>
                                        </header>
                                        @if (data_get($review, 'comment'))
                                            <p dir="auto">{{ data_get($review, 'comment') }}</p>
                                        @else
                                            <p class="cp-muted">{{ trans('messages.Rated without a comment.') }}</p>
                                        @endif
                                    </article>
                                @endforeach
                            </div>

                            @if ($reviewCount > 1)
                                <div class="cp-dots" data-review-dots></div>
                            @endif
                        @else
                            <p class="cp-muted">{{ trans('messages.No customer reviews have been published yet.') }}</p>
                        @endif
                    </section>
                </div>

                {{-- ============ Side column ============ --}}
                <aside class="cp-side">
                    @if ($isMobileDevice)
                        <div class="cp-quote" data-app-only>
                            <h3>{{ $text['quoteTitle'] }}</h3>
                            <p>{{ $text['quoteText'] }}</p>
                            <a class="hx-btn hx-btn-primary" href="{{ $quoteUrl }}">{{ $text['quoteButton'] }}</a>
                            <ul>
                                @foreach ($text['quotePoints'] as $point)
                                    <li>{{ $point }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="cp-panel cp-contact">
                        <h3>{{ trans('messages.Contact details') }}</h3>

                        <div class="cp-row">
                            <span class="cp-ico cp-tone-orange">{!! $icon('pin', 21) !!}</span>
                            <div><small>{{ trans('messages.Location') }}</small><b dir="auto">{{ $companyCity }}</b></div>
                        </div>
                        @if ($companyPhone)
                            <a class="cp-row" href="tel:{{ $companyPhone }}">
                                <span class="cp-ico cp-tone-blue">{!! $icon('phone', 21) !!}</span>
                                <div><small>{{ trans('messages.Phone') }}</small><b dir="ltr">{{ $companyPhone }}</b></div>
                            </a>
                        @endif
                        @if ($companyEmail)
                            <a class="cp-row" href="mailto:{{ $companyEmail }}">
                                <span class="cp-ico cp-tone-green">{!! $icon('mail', 21) !!}</span>
                                <div><small>{{ trans('messages.Email') }}</small><b dir="ltr">{{ $companyEmail }}</b></div>
                            </a>
                        @endif
                        @if ($companyWebsite)
                            <a class="cp-row" href="{{ $companyWebsite }}" target="_blank" rel="noopener noreferrer">
                                <span class="cp-ico cp-tone-yellow">{!! $icon('globe', 21) !!}</span>
                                <div><small>{{ $isArabic ? 'الموقع الإلكتروني' : 'Website' }}</small><b>{{ $isArabic ? 'زيارة الموقع' : 'Visit website' }}</b></div>
                            </a>
                        @endif

                        @if ($companyPhone || $companyEmail)
                            <div class="cp-contact-buttons">
                                @if ($companyPhone)
                                    <a class="hx-btn hx-btn-outline hx-btn-sm" href="tel:{{ $companyPhone }}">{{ $text['call'] }}</a>
                                @endif
                                @if ($companyEmail)
                                    <a class="hx-btn hx-btn-outline hx-btn-sm" href="mailto:{{ $companyEmail }}">{{ $text['email'] }}</a>
                                @endif
                            </div>
                        @endif

                        {{-- Company social media links (known sites only) --}}
                        @if (!empty($company->social_media))
                            @php
                                $allowedHosts = ['facebook.com', 'instagram.com', 'linkedin.com', 'youtube.com', 'tiktok.com', 'x.com', 'twitter.com', 'wa.me'];
                                $socialLinks = [];
                                foreach (array_filter(array_map('trim', preg_split('/[\r\n,]+/', $company->social_media))) as $link) {
                                    $url = preg_match('/^https?:\/\//i', $link) ? $link : 'https://' . $link;
                                    $host = preg_replace('/^www\./', '', strtolower((string) parse_url($url, PHP_URL_HOST)));
                                    if (in_array($host, $allowedHosts, true)) {
                                        $socialLinks[] = ['url' => $url, 'label' => ucfirst($host)];
                                    }
                                }
                            @endphp

                            @if ($socialLinks)
                                <div class="cp-social">
                                    <h4>{{ trans('messages.Follow us') }}</h4>
                                    <div>
                                        @foreach ($socialLinks as $link)
                                            <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">
                                                {{ $link['label'] }} <span aria-hidden="true">↗</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>

                    <div class="cp-panel cp-verify {{ $companyVerified ? 'is-verified' : '' }}">
                        <span class="cp-ico">{!! $icon('shield', 24) !!}</span>
                        <div>
                            <h3>{{ $companyVerified ? $text['verifiedTitle'] : trans('messages.About company verification') }}</h3>
                            <p>{{ trans('messages.Verification indicates that selected company information has been reviewed by SunXFinder. It is not a guarantee of project quality or results.') }}</p>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    {{-- Reviews slider: buttons, dots and swipe (native scroll-snap). Works in RTL. --}}
    <script>
        (function () {
            var track = document.querySelector('[data-review-slider]');
            if (!track) return;

            var section = track.closest('#company-reviews');
            var prev = section.querySelector('[data-review-prev]');
            var next = section.querySelector('[data-review-next]');
            var dotsBox = section.querySelector('[data-review-dots]');
            var cards = Array.prototype.slice.call(track.children);
            var isRtl = getComputedStyle(track).direction === 'rtl';

            function step() {
                var card = cards[0];
                var gap = parseFloat(getComputedStyle(track).columnGap) || 0;
                return card ? card.getBoundingClientRect().width + gap : track.clientWidth;
            }

            // How many cards fit, and which page we are on (scrollLeft is negative in RTL).
            function perView() {
                return Math.max(1, Math.round(track.clientWidth / step()));
            }

            function pageCount() {
                return Math.max(1, Math.ceil(cards.length / perView()));
            }

            function currentPage() {
                return Math.round(Math.abs(track.scrollLeft) / (step() * perView()));
            }

            // The page shown now. Buttons and dots set it directly; swiping updates it from the scroll.
            var page = 0;

            function render() {
                var last = pageCount() - 1;
                // In RTL the first card is on the right, so "←" moves forward and "→" back.
                if (prev) prev.disabled = isRtl ? page >= last : page <= 0;
                if (next) next.disabled = isRtl ? page <= 0 : page >= last;
                if (dotsBox) {
                    Array.prototype.forEach.call(dotsBox.children, function (dot, i) {
                        dot.classList.toggle('is-active', i === page);
                        dot.setAttribute('aria-current', i === page ? 'true' : 'false');
                    });
                }
                if (prev) prev.parentElement.hidden = last < 1;
            }

            function goTo(target) {
                page = Math.max(0, Math.min(pageCount() - 1, target));
                var left = page * perView() * step();
                track.scrollTo({ left: isRtl ? -left : left, behavior: 'smooth' });
                render();
            }

            function renderDots() {
                if (!dotsBox) return;
                dotsBox.innerHTML = '';
                for (var i = 0; i < pageCount(); i++) {
                    var dot = document.createElement('button');
                    dot.type = 'button';
                    dot.className = 'review-slider-dot';
                    dot.setAttribute('aria-label', (i + 1) + ' / ' + pageCount());
                    dot.addEventListener('click', goTo.bind(null, i));
                    dotsBox.appendChild(dot);
                }
                dotsBox.hidden = pageCount() < 2;
            }

            // Arrows point the way the cards move on screen ("→" shows what is to the right).
            if (prev) prev.addEventListener('click', function () { goTo(page + (isRtl ? 1 : -1)); });
            if (next) next.addEventListener('click', function () { goTo(page + (isRtl ? -1 : 1)); });

            // Finger swipe / trackpad: follow the scroll once it settles.
            var settle;
            track.addEventListener('scroll', function () {
                clearTimeout(settle);
                settle = setTimeout(function () {
                    page = Math.max(0, Math.min(pageCount() - 1, currentPage()));
                    render();
                }, 120);
            }, { passive: true });

            window.addEventListener('resize', function () {
                renderDots();
                page = Math.max(0, Math.min(pageCount() - 1, currentPage()));
                render();
            });

            renderDots();
            render();
        })();
    </script>

    {{-- Quote button and box: hide them on a phone that does not have the app. Only Android Chrome can
         answer that (getInstalledRelatedApps, with the app listed in /manifest.webmanifest and the app
         declaring this website). Other phones cannot be asked, so there the button stays and the
         "open in app" page offers the store when the app is missing. --}}
    @if ($isMobileDevice && config('services.mobile_app.detect_installed') && config('services.mobile_app.android_package'))
        <script>
            if (navigator.getInstalledRelatedApps) {
                navigator.getInstalledRelatedApps().then(function (apps) {
                    var installed = apps.some(function (app) {
                        return app.id === @json(config('services.mobile_app.android_package'));
                    });
                    if (!installed) {
                        document.querySelectorAll('[data-app-only]').forEach(function (element) { element.hidden = true; });
                    }
                }).catch(function () {});
            }
        </script>
    @endif

    {{-- Share button: the phone's share sheet when there is one, otherwise copy the link. --}}
    <script>
        document.querySelectorAll('[data-share-profile]').forEach(function (button) {
            button.addEventListener('click', function () {
                var label = button.querySelector('span');
                var original = label.textContent;
                function copied() {
                    label.textContent = button.dataset.copied;
                    setTimeout(function () { label.textContent = original; }, 2000);
                }
                if (navigator.share) {
                    navigator.share({ title: button.dataset.title, url: location.href }).catch(function () {});
                } else if (navigator.clipboard) {
                    navigator.clipboard.writeText(location.href).then(copied);
                }
            });
        });

        // Jump links: mark the section being read.
        (function () {
            var links = Array.prototype.slice.call(document.querySelectorAll('.cp-tabs a'));
            if (!links.length || !('IntersectionObserver' in window)) return;
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    links.forEach(function (link) {
                        link.classList.toggle('is-active', link.getAttribute('href') === '#' + entry.target.id);
                    });
                });
            }, { rootMargin: '-30% 0px -60% 0px' });
            links.forEach(function (link) {
                var target = document.querySelector(link.getAttribute('href'));
                if (target) observer.observe(target);
            });
        })();
    </script>
@endsection

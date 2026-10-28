@extends('visitors.layouts.app')
@section('title', app()->getLocale() === 'ar' ? 'SunXFinder - بروفايل الشركة' : 'SunXFinder - Company Profile')
@section('content')

    <div class="company-profile-page">
        <section class="company-cover {{ $companyCover ? 'has-cover' : '' }}" @if ($companyCover)
        style="--company-cover:url('{{ $companyCover }}')" @endif>

            <div class="container company-cover-inner" style="direction:ltr">
                <a class="company-back-link" href="{{ url('/') }}?lang={{ app()->getLocale() }}#companies">
                    ← {{ trans('messages.Back to companies') }}
                </a>

                <div class="company-profile-identity" dir="ltr">

                    <div class="company-profile-logo">
                        @if ($companyLogo)
                            <img src="{{ app(\App\Helpers\Helper::class)->publicUrl($company->logo_path) }}"
                                alt="{{ $company->name }} logo" loading="lazy">
                        @else
                            <span>{{ mb_strtoupper(mb_substr($companyName, 0, 1)) }}</span>
                        @endif
                    </div>

                    <div class="company-profile-title" dir="ltr">
                        <div class="company-title-badges">
                            @if ($companyVerified)
                                <span class="company-verified">
                                    <span>✓</span>
                                    {{ trans('messages.Verified company') }}
                                </span>
                            @else
                                <span class="company-unverified">
                                    {{ trans('messages.Verification not confirmed') }}
                                </span>
                            @endif
                        </div>

                        <h1 dir="ltr" style="text-align: left;">{{ $companyName }}</h1>

                        <p class="company-profile-location" dir="ltr" style="text-align: left;">
                            <span aria-hidden="true">⌖</span> {{ $companyCity }}
                        </p>

                        <div class="company-profile-rating" dir="ltr">
                            @if (is_numeric($companyRating) && $companyReviews > 0)
                                @php $filledStars = max(0, min(5, (int) round($companyRating))); @endphp
                                <span class="company-profile-stars"
                                    aria-label="{{ $companyRating }} out of 5 stars">{{ str_repeat('★', $filledStars) }}<span
                                        style="opacity:.3">{{ str_repeat('★', 5 - $filledStars) }}</span></span>
                                <strong>{{ number_format((float) $companyRating, 1) }}</strong>
                                <span>{{ number_format((int) $companyReviews) }}
                                    {{ trans('messages.customer reviews') }}</span>
                            @else
                                <span class="company-rating-empty">
                                    {{ trans('messages.Not rated yet') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="company-profile-actions" dir="ltr">
                        {{-- @if ($companyEmail)
                        <a class="btn btn-primary"
                            href="mailto:{{ $companyEmail }}?subject={{ rawurlencode('Solar project inquiry - ' . $companyName) }}">
                            {{ trans('messages.Contact company') }}
                        </a>
                        @else
                        <a class="btn btn-primary"
                            href="mailto:hello@sunxfinder.com?subject={{ rawurlencode('Contact request for ' . $companyName) }}">
                            {{ trans('messages.Request contact') }}
                        </a>
                        @endif --}}

                        @php $isMobileDevice = (bool) preg_match('/android|iphone|ipad|ipod/i', (string) request()->userAgent()); @endphp

                        {{-- Phones and tablets only: open this company in the SunXFinder app --}}
                        @if ($isMobileDevice)
                            <a class="btn btn-quiet" href="{{ route('app.company', ['slug' => $company->slug]) }}">
                                {{ trans('messages.Open in App') }}
                            </a>
                        @endif

                        {{-- Laptops/PCs: no app button, so "View services" is the orange second action --}}
                        <a class="btn btn-quiet {{ $isMobileDevice ? '' : 'btn-services' }}" href="#company-services">
                            {{ trans('messages.View services') }}
                        </a>
                    </div>

                </div>
            </div>

        </section>

        <section class="section company-profile-content">
            <div class="container company-profile-grid">
                <div class="company-profile-main">
                    <section class="profile-panel" id="company-about">
                        <span class="eyebrow">{{ trans('messages.COMPANY OVERVIEW') }}</span>
                        <h2 style="text-align:left;"> <em>{{ $companyName }}</em></h2>
                        <p class="company-about-copy" dir="auto">{{ $companyDescription }}</p>
                        <div class="company-facts">
                            <div class="company-fact"><span class="fact-icon">☀</span>
                                <div>
                                    <strong>{{ trans('messages.Solar energy') }}</strong><small>{{ trans('messages.Core industry') }}</small>
                                </div>
                            </div>
                            <div class="company-fact"><span class="fact-icon">⌖</span>
                                <div>
                                    <strong>{{ $companyCity }}</strong><small>{{ trans('messages.Service location') }}</small>
                                </div>
                            </div>
                            <div class="company-fact"><span class="fact-icon">▦</span>
                                <div>
                                    <strong>{{ is_numeric($companyYears) ? $companyYears . '+' : trans('messages.Information unavailable') }}</strong><small>{{ trans('messages.Years of experience') }}</small>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="profile-panel" id="company-services">
                        <span class="eyebrow">{{ trans('messages.WHAT WE OFFER') }}</span>
                        <h2>{{ trans('messages.Our services') }}</h2>
                        @if (count($companyServices))
                            <div class="company-service-grid">
                                @foreach ($companyServices as $service)
                                    <article class="company-service-item"><span
                                            class="service-check">✓</span><span>{{ $service }}</span></article>
                                @endforeach
                            </div>
                        @else
                            <p class="company-muted">{{ trans('messages.Services will be added by this company.') }}</p>
                        @endif
                    </section>


                    <section class="profile-panel" id="company-products">
                        <span class="eyebrow">
                            {{ trans('messages.PRODUCT CATALOG') }}
                        </span>

                        <h2>{{ trans('messages.Our Products') }}</h2>

                        @if ($companyProducts->isNotEmpty())
                            <div class="company-product-grid">
                                @foreach ($companyProducts as $product)
                                    <article class="company-product-card">
                                        <div class="company-product-icon" aria-hidden="true">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path d="m21 8-9-5-9 5" />
                                                <path d="m3 8 9 5 9-5" />
                                                <path d="M12 13v9" />
                                                <path d="M3 8v8l9 5 9-5V8" />
                                            </svg>
                                        </div>

                                        <div class="company-product-content">
                                            <span class="company-product-type">
                                                {{ $product->typeLabel() }}
                                            </span>

                                            <h3>{{ $product->name }}</h3>

                                            @if ($product->model)
                                                <p class="company-product-model">
                                                    {{ trans('messages.Model') }}:
                                                    {{ $product->model }}
                                                </p>
                                            @endif

                                            @if ($product->supplier)
                                                <p class="company-product-supplier">
                                                    {{ trans('messages.Brand') }}:
                                                    {{ $product->supplier->name }}
                                                </p>
                                            @endif

                                            @if ($product->description)
                                                <p class="company-product-description">
                                                    {{ $product->description }}
                                                </p>
                                            @endif

                                            @if ($product->specs->isNotEmpty())
                                                <div class="company-product-specs">
                                                    @foreach ($product->specs as $spec)
                                                        <div class="company-product-spec">
                                                            <span>{{ $spec->spec_name }}</span>
                                                            <strong>{{ $spec->spec_value }}</strong>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <p class="company-muted">
                                {{ trans('messages.No products have been added yet.') }}
                            </p>
                        @endif
                    </section>


                    <section class="profile-panel" id="company-reviews">
                        @php $reviewCount = count($companyReviewsList); @endphp
                        <div class="review-slider-head">
                            <div>
                                <span class="eyebrow">{{ trans('messages.CUSTOMER FEEDBACK') }}</span>
                                <h2>{{ trans('messages.Customer reviews') }}</h2>
                            </div>

                            @if ($reviewCount > 1)
                                <div class="review-slider-controls" dir="ltr">
                                    <button type="button" class="review-slider-btn" data-review-prev
                                        aria-label="{{ trans('messages.Previous') }}">←</button>
                                    <button type="button" class="review-slider-btn" data-review-next
                                        aria-label="{{ trans('messages.Next') }}">→</button>
                                </div>
                            @endif
                        </div>

                        @if ($reviewCount)
                            <div class="review-slider" data-review-slider tabindex="0"
                                aria-label="{{ trans('messages.Customer reviews') }}">
                                @foreach ($companyReviewsList as $review)
                                    @php
                                        $reviewStars = max(0, min(5, (int) data_get($review, 'rating', 0)));
                                        $reviewer = data_get($review, 'customer') ?: trans('messages.Customer');
                                    @endphp
                                    <article class="company-review">
                                        <div class="review-head">
                                            <span class="review-avatar"
                                                aria-hidden="true">{{ mb_strtoupper(mb_substr($reviewer, 0, 1)) }}</span>
                                            <span class="review-who">
                                                <strong>{{ $reviewer }}</strong>
                                                <small>{{ data_get($review, 'date', '') }}</small>
                                            </span>
                                            <span class="company-profile-stars" dir="ltr"
                                                aria-label="{{ $reviewStars }} out of 5 stars">{{ str_repeat('★', $reviewStars) }}<span
                                                    style="opacity:.3">{{ str_repeat('★', 5 - $reviewStars) }}</span></span>
                                        </div>
                                        @if (data_get($review, 'comment'))
                                            <p class="review-comment">“{{ data_get($review, 'comment') }}”</p>
                                        @else
                                            <p class="review-comment review-comment-empty">
                                                {{ trans('messages.Rated without a comment.') }}</p>
                                        @endif
                                    </article>
                                @endforeach
                            </div>

                            @if ($reviewCount > 1)
                                <div class="review-slider-dots" data-review-dots></div>
                            @endif
                        @else
                            <p class="company-muted">{{ trans('messages.No customer reviews have been published yet.') }}
                            </p>
                        @endif
                    </section>
                </div>

                <aside class="company-profile-sidebar">
                    <div class="profile-panel contact-panel">
                        <span class="eyebrow">{{ trans('messages.GET IN TOUCH') }}</span>
                        <h2>{{ trans('messages.Contact details') }}</h2>
                        <div class="contact-detail"><span>⌖</span>
                            <div><small>{{ trans('messages.Location') }}</small><strong>{{ $companyCity }}</strong></div>
                        </div>
                        @if ($companyPhone)
                            <div class="contact-detail"><span>☎</span>
                                <div><small>{{ trans('messages.Phone') }}</small><a
                                        href="tel:{{ $companyPhone }}">{{ $companyPhone }}</a></div>
                            </div>
                        @endif
                        @if ($companyEmail)
                            <div class="contact-detail"><span>✉</span>
                                <div><small>{{ trans('messages.Email') }}</small><a
                                        href="mailto:{{ $companyEmail }}">{{ $companyEmail }}</a></div>
                            </div>
                        @endif
                        @if ($companyWebsite)
                            <div class="contact-detail">
                                <div><small>{{ trans('messages.Website') }}</small><a href="{{ $companyWebsite }}"
                                        target="_blank" rel="noopener noreferrer">{{ trans('messages.Visit website') }}</a>
                                </div>
                            </div>
                        @endif

                        {{-- <a class="btn btn-primary contact-full-btn"
                            href="mailto:{{ $companyEmail ?: 'hello@sunxfinder.com' }}?subject={{ rawurlencode('Solar project inquiry - ' . $companyName) }}">{{
                            trans('messages.Send an inquiry') }}
                        </a>
                        <p class="company-disclaimer">
                            {{ trans('messages.Contact details are provided for project inquiries. Confirm scope, pricing,
                            and terms directly with the company.') }}
                        </p> --}}

                        {{-- Company Social Media Links --}}
                        @if (!empty($company->social_media))
                            @php
                                $socialLinks = preg_split('/[\r\n,]+/', $company->social_media);
                                $socialLinks = array_filter(array_map('trim', $socialLinks));
                            @endphp

                            @if (count($socialLinks))
                                <div class="company-social-media">
                                    <h4>{{ trans('messages.Follow us') }}</h4>

                                    <div class="company-social-links">
                                        @foreach ($socialLinks as $link)
                                            @php
                                                $url = trim($link);

                                                if (!preg_match('/^https?:\/\//i', $url)) {
                                                    $url = 'https://' . $url;
                                                }

                                                $host = parse_url($url, PHP_URL_HOST);
                                                $allowedHosts = [
                                                    'facebook.com',
                                                    'www.facebook.com',
                                                    'instagram.com',
                                                    'www.instagram.com',
                                                    'linkedin.com',
                                                    'www.linkedin.com',
                                                    'youtube.com',
                                                    'www.youtube.com',
                                                    'tiktok.com',
                                                    'www.tiktok.com',
                                                    'x.com',
                                                    'www.x.com',
                                                    'twitter.com',
                                                    'www.twitter.com',
                                                    'wa.me',
                                                    'www.wa.me',
                                                ];
                                            @endphp

                                            @if ($host && in_array(strtolower($host), $allowedHosts, true))
                                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="company-social-link">
                                                    {{ ucfirst(str_replace('www.', '', $host)) }}
                                                    <span aria-hidden="true">↗</span>
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                    <div class="profile-panel verification-info-panel">
                        <center>
                            <div class="verification-info-icon">✓</div>
                            <h3>{{ trans('messages.About company verification') }}</h3>
                            <p>{{ trans('messages.Verification indicates that selected company information has been reviewed by SunXFinder. It is not a guarantee of project quality or results.') }}
                            </p>
                        </center>
                    </div>
                </aside>
            </div>
        </section>
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
@endsection

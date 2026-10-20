@extends('visitors.layouts.app')
@section('title', app()->getLocale() === 'ar' ? 'SunXFinder - Solar Solutions' : 'SunXFinder - Solar Solutions')
@section('content')

    {{-- Home --}}
    <section class="hero">
        <div class="glow glow-a"></div>
        <div class="glow glow-b"></div>
        <div class="container hero-grid">
            <div class="hero-copy"><span
                    class="eyebrow"><i>✦</i>{{ trans('messages.YOUR ENERGY JOURNEY STARTS HERE') }}</span>
                <h1>
                    {{ __('messages.Connect') }}
                    <em>
                        {{ __('messages.customers with the right') }}
                        <span class="solar-highlight">{{ __('messages.solar') }}</span>
                        {{ __('messages.companies') }}
                    </em>
                </h1>
                <p class="lead">

                    {{ trans('messages.Whether you\'re seeking solar solutions or looking to grow your energy business, SunXFinder makes connections simple and trustworthy.') }}
                </p>
                <div class="actions"><a class="btn btn-primary btn-lg" href="#start">{{ trans('messages.Find your match') }}
                    </a><a class="btn btn-quiet btn-lg" href="#how"><span class="play">▶</span>
                        {{ trans('messages.See how it works') }}</a></div>
                <div class="trust-mini">
                    <div class="check-stack"><i>✓</i><i>✓</i><i>✓</i></div>
                    <div>

                        <strong>{{ trans('messages.A trusted connection platform') }}</strong><small
                            style="font-size: 14px;color:#fd5103">{{ trans('messages.Verified companies. Informed choices. Successful projects.') }}</small>
                    </div>
                </div>
            </div>
            <div class="hero-art">
                <div class="orbit orbit-one"></div>
                <div class="orbit orbit-two"></div>
                <div class="sun"></div>
                <div class="house">
                    <div class="roof"></div>
                    <div class="panel">
                        <i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i>
                    </div>
                    <div class="wall"><span></span><b></b></div>
                    <div class="hill"></div>
                </div>
                {{-- <div class="visual-x">X</div> --}}
                <div class="match-card">
                    <div class="match-check"></div>
                    <div>

                        <small>{{ trans('messages.YOUR MATCH') }}</small><strong>{{ trans('messages.Energy partners found') }}</strong><span><i></i>
                            {{ trans('messages.Matched to your needs') }}</span>
                    </div><b class="arrow">↗</b>
                </div>
                <div class="float-pill pill-top">✦   {{ trans('messages.Smart matching') }}
                </div>
                <div class="float-pill pill-bottom">✓
                    {{ trans('messages.Compare with confidence') }}
                </div>
            </div>
        </div>
        <div class="container hero-bottom">
            <span><b
                    style="font-size:12px;color: #000;">{{ trans('messages.SERVING BOTH SIDES OF THE ENERGY MARKET') }}</b></span>
            <div>

                <i>{{ trans('messages.Homeowners') }}</i><i>{{ trans('messages.Businesses') }}</i><i>{{ trans('messages.Installers') }}</i><i>{{ trans('messages.Providers') }}</i><i>{{ trans('messages.Contractors') }}</i>
            </div>
        </div>
    </section>

    {{-- Why Us --}}
    <section class="benefits" id="why">
        <div class="container benefit-grid">
            <div class="benefit"><span class="benefit-icon blue">⌕</span>
                <div>
                    <h3>{{ trans('messages.For energy seekers') }}</h3>
                    <p>
                        {{ trans('messages.Find verified companies, compare options, and choose with confidence.') }}
                    </p>
                </div>
            </div>
            <div class="benefit"><span class="benefit-icon orange">⇄</span>
                <div>
                    <h3>{{ trans('messages.For energy providers') }}</h3>
                    <p>
                        {{ trans('messages.Get qualified leads, showcase expertise, and grow your customer base.') }}
                    </p>
                </div>
            </div>
            <div class="benefit"><span class="benefit-icon green">✓</span>
                <div>
                    <h3>{{ trans('messages.For everyone') }}</h3>
                    <p>
                        {{ trans('messages.Transparent connections, verified reviews, and successful energy projects.') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="section" id="how">
        <div class="container">
            <div class="section-heading"><span class="eyebrow"
                    style="color: #fd5103">{{ trans('messages.SIMPLE FROM START TO FINISH') }}</span>
                <h2>
                    {{ trans('messages.How connections work') }}<em>{{ trans('messages. for both customers and companies') }}</em>
                </h2>
                <p>
                    {{ trans('messages.Our platform streamlines the process for anyone looking to go solar or grow their energy business.') }}
                </p>
            </div>
            <div class="steps">
                <article><span class="step-no" style="color: #fd5103;">01</span>
                    <div class="step-icon blue">⌂</div>
                    <h3>{{ trans('messages.Share your needs') }}</h3>
                    <p>
                        {{ trans('messages.Customers tell us about their project. Companies share their services and specialties.') }}
                    </p>
                </article>
                <article><span class="step-no" style="color: #fd5103;">02</span>
                    <div class="step-icon orange">⌕</div>
                    <h3>{{ trans('messages.We make matches') }}</h3>
                    <p>
                        {{ trans('messages.Our algorithm connects customers with suitable companies based on location, needs, and expertise.') }}
                    </p>
                </article>
                <article><span class="step-no" style="color: #fd5103;">03</span>
                    <div class="step-icon green">⇄</div>
                    <h3>{{ trans('messages.Connections made') }}</h3>
                    <p>
                        {{ trans('messages.Customers review options and connect directly. Companies receive qualified leads and new opportunities.') }}
                    </p>
                </article>
            </div>
            <div class="center"><a class="btn btn-primary" href="#start">{{ trans('messages.Start connecting') }}
                </a></div>
        </div>
    </section>

    {{-- Solutions --}}
    <section class="section solutions" id="solutions">
        <div class="container solutions-grid">
            <div class="solutions-copy"><span class="eyebrow">{{ trans('messages.ENERGY SOLUTIONS FOR EVERY NEED') }}</span>
                <h2>
                    {{ trans('messages.From residential to commercial') }}<em>{{ trans('messages. we cover the full spectrum') }}</em>
                </h2>
                <p>
                    {{ trans('messages.Whether you\'re a homeowner looking for rooftop solar or a business seeking comprehensive energy solutions, we help you find the right provider. Energy companies can connect with customers seeking their specific services.') }}
                </p>

            </div>
            <div class="solution-list">
                <article class="solution-card home"><span class="solution-art">⌂</span>
                    <div><small style="color: #fd5103;">{{ trans('messages.01 / RESIDENTIAL') }}</small>
                        <h3>{{ trans('messages.Home energy') }}</h3>
                        <p>
                            {{ trans('messages.Solar panels, batteries and backup power for homeowners.') }}
                        </p>
                    </div><b>↗</b>
                </article>
                <article class="solution-card business"><span class="solution-art">▥</span>
                    <div><small style="color: #fd5103;">{{ trans('messages.02 / COMMERCIAL') }}</small>
                        <h3>{{ trans('messages.Business & industry') }}</h3>
                        <p>
                            {{ trans('messages.Energy options for shops, offices, facilities and operations.') }}
                        </p>
                    </div><b>↗</b>
                </article>
                <article class="solution-card farm"><span class="solution-art">♧</span>
                    <div><small style="color: #fd5103;">{{ trans('messages.03 / AGRICULTURE') }}</small>
                        <h3>{{ trans('messages.Farms & water pumps') }}</h3>
                        <p>
                            {{ trans('messages.Solar-powered irrigation and remote energy systems.') }}
                        </p>
                    </div><b>↗</b>
                </article>
                <article class="solution-card company"><span class="solution-art">⌕</span>
                    <div><small style="color: #fd5103;">{{ trans('messages.04 / ENERGY PROVIDERS') }}</small>
                        <h3>{{ trans('messages.Grow your business') }}</h3>
                        <p>
                            {{ trans('messages.Connect with customers seeking your specific energy services and solutions.') }}
                        </p>
                    </div><b>↗</b>
                </article>
            </div>
        </div>
    </section>

    {{-- Our Solar Companies --}}
    <section class="section companies-section" id="our-compaines" style="padding: 5px 0">
        <div class="container">

            <div class="section-heading">
                <span class="eyebrow" style="color:#fd5103">
                    {{ trans('messages.TRUSTED SOLAR PROFESSIONALS') }}
                </span>

                <h2>
                    {{ trans('messages.Find the right') }}
                    <em>{{ trans('messages.solar company for your needs') }}</em>
                </h2>

                <p>
                    {{ trans('messages.Explore solar companies, compare customer ratings, and find the right expertise for your project.') }}
                </p>
            </div>

            <div class="companies-toolbar">
                <div class="companies-trust-note">
                    <span class="trust-icon">✓</span>
                    <span>{{ trans('messages.Compare ratings and verification status') }}</span>
                </div>

                <div class="company-slider-controls">
                    <button type="button" class="company-slider-btn" id="companiesPrev" aria-label="Previous companies">
                        &#8592;
                    </button>

                    <button type="button" class="company-slider-btn" id="companiesNext" aria-label="Next companies">
                        &#8594;
                    </button>
                </div>
            </div>

            <div class="companies-slider" id="companiesSlider">

                @forelse ($companies as $carouselEntry)
                            @php
                                $company = $carouselEntry->company;

                                $isArabic = app()->getLocale() === 'ar';

                                $companyName = $isArabic
                                    ? ($company->ar_name ?: $company->name)
                                    : ($company->name ?: $company->ar_name);

                                $description = $isArabic
                                    ? ($company->ar_description ?: $company->description)
                                    : ($company->description ?: $company->ar_description);

                                $services = $isArabic
                                    ? ($company->ar_services ?: $company->services)
                                    : ($company->services ?: $company->ar_services);

                                $serviceList = collect(explode(',', $services ?? ''))
                                    ->map(fn($service) => trim($service))
                                    ->filter()
                                    ->take(3);

                                $rating = 4.0;
                            @endphp

                            {{-- Keep your existing company-card HTML here.
                            Use $company for company fields and relationships. --}}
                            @php
                                $isArabic = app()->getLocale() === 'ar';

                                $companyName = $company->name;

                                $description = $isArabic
                                    ? ($company->ar_description ?:
                                        $company->description)
                                    : ($company->description ?:
                                        $company->ar_description);

                                $services = $isArabic
                                    ? ($company->ar_services ?:
                                        $company->services)
                                    : ($company->services ?:
                                        $company->ar_services);

                                $serviceList = collect(explode(',', $services ?? ''))
                                    ->map(fn($service) => trim($service))
                                    ->filter()
                                    ->take(3);

                                $rating = 4.0;
                            @endphp

                            <article class="company-card" data-interval="{{ $carouselEntry->interval_seconds }}">
                            
                                <div class="company-card-top">

                                    <div class="company-logo-placeholder">
                                        {{ mb_strtoupper(mb_substr($companyName, 0, 1)) }}
                                    </div>

                                    <div class="company-main-info">
                                        <h3>{{ $companyName }}</h3>

                                        <span class="company-location">
                                            <span aria-hidden="true">⌖</span>
                                            {{ $company->city ?: trans('messages.Sudan') }}, Sudan
                                        </span>
                                    </div>

                                    @if ($company->is_verified)
                                        <span class="verification-badge">
                                            <span>✓</span>
                                            {{ trans('messages.Verified') }}
                                        </span>
                                    @endif
                                </div>

                                <div class="company-rating">
                                    <span class="rating-stars" aria-label="4 out of 5 stars">
                                        ★★★★☆
                                    </span>

                                    <strong>{{ number_format($rating, 1) }}</strong>
                                </div>

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
                                    <span class="company-profile-label">
                                        {{ trans('messages.Solar service provider') }}
                                    </span>

                                    <a href="{{ route('company-profile.show', [
                        'slug' => $company->slug,
                        'lang' => app()->getLocale(),
                    ]) }}" class="btn btn-primary">
                                        {{ trans('messages.View company') }}
                                    </a>
                                </div>
                            </article>
                @empty
                    <div class="company-empty-state">
                        <p>{{ trans('messages.No companies available yet.') }}</p>
                    </div>
                @endforelse


            </div>

            {{-- <div class="companies-slider-footer">
                <span></span>

                <a href="{{ route('dump-companies.show', [
                                'slug' => 'sample-solar-company',
                                'lang' => app()->getLocale(),
                            ]) }}" class="btn btn-primary">
                    {{ trans('messages.Explore companies') }}

                </a>
            </div> --}}

        </div>
    </section>

    {{-- Our Partner --}}
    <section class="section partner-section" id="partners">
        <div class="container partner-panel">
            <div class="partner-art">
                <div class="partner-orb"></div>
                <div class="partner-card"><span>↗</span>
                    <div>

                        <strong>{{ trans('messages.For energy seekers') }}</strong><small>{{ trans('messages.Find verified providers in your area') }}</small>
                    </div>
                </div>
                <div class="partner-card second"><span>✓</span>
                    <div>

                        <strong>{{ trans('messages.For energy providers') }}</strong><small>{{ trans('messages.Join our network of trusted companies') }}</small>
                    </div>
                </div>
            </div>
            <div class="partner-copy"><span class="eyebrow">{{ trans('messages.THE SUNXFINDER NETWORK') }}</span>
                <h2>
                    {{ trans('messages.Where customers meet') }}<em>{{ trans('messages. qualified energy companies') }}</em>
                </h2>
                <p>
                    {{ trans('messages.Our platform creates mutually beneficial connections: customers find trustworthy providers, and companies gain access to qualified project opportunities.') }}
                </p>
                <ul>
                    <li>✓
                        {{ trans('messages.Customers: Search, compare, and connect with confidence') }}
                    </li>
                    <li>✓
                        {{ trans('messages.Companies: Receive leads, showcase expertise, and grow') }}
                    </li>
                    <li>✓

                        {{ trans('messages.Both: Transparent process, verified information, successful outcomes') }}
                    </li>
                </ul>
                {{-- <a class="btn btn-dark" href="mailto:partners@sunxfinder.com">{{ trans('messages.Learn more') }}</a>
                --}}
            </div>
        </div>
    </section>

    {{-- Our Trust --}}
    <section class="section trust-section">
        <div class="container trust-grid">
            <div><span class="eyebrow">{{ trans('messages.TRUST BUILT TRANSPARENTLY') }}</span>
                <h2>
                    {{ trans('messages.How we ensure quality') }}<em>{{ trans('messages. for everyone involved') }}</em>
                </h2>
                <p>
                    {{ trans('messages.Our verification process protects both customers seeking reliable service and companies wanting to showcase their expertise.') }}
                </p>
            </div>
            <div class="trust-list">
                <article><span>✓</span>
                    <div>
                        <h3>{{ trans('messages.For customers') }}</h3>
                        <p>
                            {{ trans('messages.Verified licenses, insurance, and customer reviews help you choose confidently.') }}
                        </p>
                    </div>
                </article>
                <article><span>✓</span>
                    <div>
                        <h3>{{ trans('messages.For companies') }}</h3>
                        <p>
                            {{ trans('messages.Showcase your credentials, expertise, and track record to attract the right customers.') }}
                        </p>
                    </div>
                </article>
                <article><span>✓</span>
                    <div>
                        <h3>{{ trans('messages.For the platform') }}</h3>
                        <p>
                            {{ trans('messages.Ongoing verification ensures our network maintains the highest standards.') }}
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="section cta" id="start">
        <div class="container cta-container" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
            lang="{{ app()->getLocale() }}">
            <div class="cta-panel">
                <div class="cta-content">
                    <span class="eyebrow">
                        {{ trans('messages.READY TO CONNECT?') }}
                    </span>

                    <h2>
                        {{ trans('messages.Whether you\'re seeking') }}
                        <em
                            style="color: #ff8b12">{{ trans('messages. solar solutions or seeking to grow your energy business') }}</em>
                    </h2>

                    <p>
                        {{ trans('messages.Take the first step toward successful energy connections today.') }}
                    </p>

                    <div class="cta-buttons">
                        <a class="btn btn-white" href="{{ route('customer.register', ['lang' => app()->getLocale()]) }}">
                            {{ trans('messages.I\'m seeking energy solutions') }}
                        </a>

                        <a class="btn btn-primary" href="{{ route('company.register', ['lang' => app()->getLocale()]) }}">
                            {{ trans("messages.I'm an energy provider") }}
                        </a>
                    </div>
                </div>

                <div class="cta-art solar-art" aria-hidden="true">
                    <div class="cta-ring"></div>

                    <svg class="solar-icon" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                        <!-- Rising sun -->
                        <path d="M35 43 A25 25 0 0 1 85 43" fill="none" stroke="#FFB900" stroke-width="8"
                            stroke-linecap="round" />

                        <!-- Sun rays -->
                        <g stroke="#fd5103" stroke-width="4" stroke-linecap="round">
                            <path d="M60 8V17" />
                            <path d="M31 19L37 26" />
                            <path d="M89 19L83 26" />
                            <path d="M17 43H27" />
                            <path d="M93 43H103" />
                        </g>

                        <!-- Solar panel -->
                        <path d="M20 48H100L110 88H10Z" fill="#123D70" stroke="#FFFFFF" stroke-width="3"
                            stroke-linejoin="round" />

                        <!-- Photovoltaic cells -->
                        <g stroke="#FFFFFF" stroke-width="2" fill="#0878C9">
                            <path d="M27 54H49L47 64H24Z" />
                            <path d="M54 54H76L78 64H52Z" />
                            <path d="M81 54H96L99 64H83Z" />
                            <path d="M23 69H46L44 81H20Z" />
                            <path d="M51 69H79L81 81H49Z" />
                            <path d="M84 69H101L104 81H86Z" />
                        </g>

                        <!-- Panel stand -->
                        <path d="M60 88V101M43 103H77" fill="none" stroke="#fd5103" stroke-width="5"
                            stroke-linecap="round" />
                    </svg>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="section faq-section animate-fade-in-up delay-300" id="faq">
        <div class="container">
            <div class="section-heading">
                <span class="eyebrow">{{ trans('messages.QUESTIONS & ANSWERS') }}</span>
                <h2>{{ trans('messages.Frequently Asked Questions') }}</h2>
                <p>{{ trans('messages.Find answers for both energy seekers and providers.') }}
                </p>
            </div>
            <div class="faq-container">
                <div class="faq-item animate-fade-in-up delay-100">
                    <div class="faq-question">
                        <h3>
                            {{ trans('messages.How does SunXFinder work for homeowners and businesses?') }}
                        </h3>
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>
                            {{ trans('messages.Homeowners and businesses tell us about their energy needs, and we match them with pre-vetted solar and energy companies in their area. You can then compare options, read verified reviews, and connect directly with the provider of your choice—all without any fees or obligation.') }}
                        </p>
                    </div>
                </div>
                <div class="faq-item animate-fade-in-up delay-200">
                    <div class="faq-question">
                        <h3>
                            {{ trans('messages.How does SunXFinder work for energy companies?') }}
                        </h3>
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>
                            {{ trans('messages.Energy companies join our network to receive qualified project leads from customers actively seeking their services. You can showcase your expertise, verify your credentials, and connect with customers who are ready to move forward with their energy projects.') }}
                        </p>
                    </div>
                </div>
                <div class="faq-item animate-fade-in-up delay-300">
                    <div class="faq-question">
                        <h3>{{ trans('messages.Is there a fee to use SunXFinder?') }}
                        </h3>
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>
                            {{ trans('messages.For homeowners and businesses:') }}<br>{{ trans('messages.No, our service is completely free. We never charge fees for searching, comparing, or connecting with energy companies.') }}
                        </p>
                        <p>
                            {{ trans('messages.For energy companies:') }}<br>{{ trans('messages.Yes, we charge a modest participation fee to maintain our network, verify company credentials, and provide ongoing platform improvements. This ensures you receive high-quality, serious inquiries only.') }}
                        </p>
                    </div>
                </div>
                <div class="faq-item animate-fade-in-up delay-400">
                    <div class="faq-question">
                        <h3>
                            {{ trans('messages.How do you verify the companies in your network?') }}
                        </h3>
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>
                            {{ trans('messages.We conduct a thorough verification process that includes: checking licenses and insurance, verifying business credentials, reviewing customer feedback and references, confirming service areas and specialties, and conducting ongoing quality checks to maintain network standards.') }}
                        </p>
                    </div>
                </div>
                <div class="faq-item animate-fade-in-up delay-500">
                    <div class="faq-question">
                        <h3>
                            {{ trans('messages.What types of energy projects and services do you support?') }}
                        </h3>
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>
                            {{ trans('messages.We support the full spectrum: residential solar, commercial solar, agricultural solar, off-grid systems, battery storage, energy audits, efficiency upgrades, and more. Companies can specify their exact services to receive the most relevant matches.') }}
                        </p>
                    </div>
                </div>
                <div class="faq-item animate-fade-in-up delay-600">
                    <div class="faq-question">
                        <h3>
                            {{ trans('messages.How long does it take to see results?') }}
                        </h3>
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>
                            {{ trans('messages.For homeowners and businesses:') }}<br>{{ trans('messages.You can start seeing matches and receiving company information immediately after submitting your request, with detailed quotes typically arriving within 24-48 hours.') }}
                        </p>
                        <p>
                            {{ trans('messages.For energy companies:') }}<br>{{ trans('messages.Approved providers can start receiving qualified leads within 24 hours of joining our network, depending on service area and customer demand.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
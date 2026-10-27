@php
    $homeUrl = url('/') . '?lang=' . app()->getLocale();
@endphp
<header class="header">
    <div class="container nav">
        <a class="brand" href="{{ $homeUrl }}" aria-label="SunXFinder">
            <img class="brand-logo" src="{{ asset_v($isHome ? 'visitors/images/sunxfinder-logo-home-128.webp' : 'visitors/images/logo-128.png') }}" alt="SunXFinder logo"
                width="78" height="56" fetchpriority="high" decoding="async"
                onerror="this.style.display='none';this.closest('a').querySelector('.brand-fallback').hidden=false">
            <span class="brand-tagline">{{ trans('messages.Find.') }} <span class="slogan-compare">{{ trans('messages.Compare.') }}</span> {{ trans('messages.Connect.') }}</span>
            <span class="brand-fallback" hidden>Sun<span>X</span>Finder</span>
        </a>
        <button class="menu-toggle" aria-label="{{ trans('messages.Toggle navigation') }}"
            aria-expanded="false"><span></span><span></span><span></span></button>

        <nav class="nav-links">
            <a href="{{ $homeUrl }}#how">{{ trans('messages.How it works') }}</a>

            <a href="{{ $homeUrl }}#solutions">{{ trans('messages.Solutions') }}</a>

            <a href="{{ $homeUrl }}#companies">{{ trans('messages.Companies') }}</a>

            <a href="{{ $homeUrl }}#for-companies">{{ trans('messages.For companies') }}</a>

            <a href="{{ $homeUrl }}#faq">{{ trans('messages.FAQ') }}</a>

            <a href="{{ $homeUrl }}#contact">{{ trans('messages.Contact us') }}</a>

            <a class="btn btn-primary nav-cta" href="{{ route('customer.register', ['lang' => app()->getLocale()]) }}">
                {{ trans('messages.Get free offers') }}
            </a>

            <button class="language-trigger" type="button" id="openLanguageModal" aria-haspopup="dialog">
                {{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}
                <span aria-hidden="true">▾</span>
            </button>
        </nav>
    </div>
</header>

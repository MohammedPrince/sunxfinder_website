@php
    $homeUrl = url('/') . '?lang=' . app()->getLocale();
@endphp
<header class="header">
    <div class="container nav">
        <a class="brand" href="{{ url('/') }}"><img src="{{ asset('visitors/images/sunxfinder-logo.png') }}"
                alt="SunXFinder logo" loading="lazy"
                onerror="this.style.display='none';this.nextElementSibling.hidden=false"><span class="brand-fallback"
                hidden>Sun<span>X</span>Finder</span></a>
        <button class="menu-toggle" aria-label="Toggle navigation"
            aria-expanded="false"><span></span><span></span><span></span></button>

        <nav class="nav-links">
            <a href="{{ $homeUrl }}#how">{{ trans('messages.How it works') }}</a>

            <a href="{{ $homeUrl }}#solutions">{{ trans('messages.Solutions') }}</a>

            <a href="{{ $homeUrl }}#our-compaines">{{ trans('messages.our partners') }}</a>

            <a href="{{ $homeUrl }}#partners">{{ trans('messages.Why SunXFinder') }}</a>

            <a href="{{ $homeUrl }}#faq">{{ trans('messages.FAQ') }}</a>

            <a class="btn btn-primary nav-cta" href="{{ $homeUrl }}#start">
                {{ trans('messages.Get started') }}
            </a>

            <button class="language-trigger" type="button" id="openLanguageModal" aria-haspopup="dialog">
                {{ trans('messages.Change language') }}
                <span aria-hidden="true">▾</span>
            </button>
        </nav>
    </div>
</header>
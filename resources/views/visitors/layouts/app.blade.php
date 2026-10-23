@php
    $currentRoute = request()->route()?->getName();
    $language = request()->query('lang', app()->getLocale());
    $homeUrl = url('/') . '?lang=' . app()->getLocale();
    $isHome = $currentRoute === 'home';
    $needsEditor = $currentRoute === 'company.register';
@endphp

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b2d5c">
    <meta name="description" content="{{ trans('messages.Find, compare, and connect with trusted solar companies.') }}">
    <title>@yield('title', 'SunXFinder-Solar Solutions')</title>

    <link rel="icon" type="image/png" href="{{ asset_v('visitors/images/sun.png') }}">

    {{-- Logo is above the fold: start downloading it immediately --}}
    <link rel="preload" as="image" type="image/webp" href="{{ asset_v('visitors/images/logo-128.webp') }}"
        fetchpriority="high">

    {{-- Google Fonts loaded without blocking first render (display=swap shows fallback text meanwhile).
         Arabic pages also load Cairo, since DM Sans has no Arabic letters. --}}
    @php
        $fontsUrl = 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@700;800'
            . (app()->getLocale() === 'ar' ? '&family=Cairo:wght@400;600;700;800' : '')
            . '&display=swap';
    @endphp
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="{{ $fontsUrl }}">
    <link rel="stylesheet" media="print" onload="this.media='all'" href="{{ $fontsUrl }}">
    <noscript>
        <link rel="stylesheet" href="{{ $fontsUrl }}">
    </noscript>

    <link rel="stylesheet" href="{{ asset_v('visitors/css/sunxfinder.css') }}">

    {{-- Page-specific CSS: the home page doesn't use these, so don't block its render with them --}}
    @unless ($isHome)
        <link rel="stylesheet" href="{{ asset_v('visitors/css/company-profile.css') }}">
    @endunless
    @if ($needsEditor)
        <link rel="stylesheet" href="{{ asset_v('main/css/quill.snow.css') }}">
    @endif

    @if ($currentRoute === 'privacy-policy')
        @if ($language === 'ar')
            <link rel="stylesheet" href="{{ asset_v('visitors/css/privacy-policy-ar.css') }}">
        @else
            <link rel="stylesheet" href="{{ asset_v('visitors/css/privacy-policy.css') }}">
        @endif
    @endif

    @if ($currentRoute === 'terms')
        @if ($language === 'ar')
            <link rel="stylesheet" href="{{ asset_v('visitors/css/terms-ar.css') }}">
        @else
            <link rel="stylesheet" href="{{ asset_v('visitors/css/terms.css') }}">
        @endif
    @endif

    @stack('styles')
</head>

<body>
    @if (!request()->cookie('locale') && !in_array(request()->query('lang'), ['en', 'ar'], true))
        <div class="language-modal-overlay" id="languageModal" role="presentation">
    @else
            <div class="language-modal-overlay" id="languageModal" role="presentation" hidden>
        @endif
            <section class="language-modal" role="dialog" aria-modal="true" aria-labelledby="languageModalTitle"
                aria-describedby="languageModalDescription">
                <button class="language-close" id="closeLanguageModal" type="button"
                    aria-label="{{ trans('messages.Close') }}">×</button>

                <div class="language-modal-head">
                    <img class="language-modal-logo" src="{{ asset_v('visitors/images/logo-128.webp') }}" alt="SunXFinder"
                        width="56" height="56">
                    <span class="language-kicker">Language · اللغة</span>
                    <h2 id="languageModalTitle">{{ trans('messages.Choose your language') }}</h2>
                    <p id="languageModalDescription">{{ trans('messages.Select your preferred language to continue.') }}</p>
                </div>

                <div class="language-options">
                    <a class="language-option language-option-en {{ app()->getLocale() === 'en' ? 'is-current' : '' }}"
                        href="{{ url()->current() }}?lang=en" lang="en" dir="ltr">
                        <span class="language-flag" aria-hidden="true">En</span>
                        <span><strong>English</strong><small>Continue in English</small></span>
                        <b aria-hidden="true">→</b>
                    </a>
                    <a class="language-option language-option-ar {{ app()->getLocale() === 'ar' ? 'is-current' : '' }}"
                        href="{{ url()->current() }}?lang=ar" lang="ar" dir="rtl">
                        <span class="language-flag" aria-hidden="true">ع</span>
                        <span><strong>العربية</strong><small>المتابعة باللغة العربية</small></span>
                        <b aria-hidden="true">←</b>
                    </a>
                </div>
                <small class="language-note">{{ trans('messages.You can change your preference at any time.') }}</small>
            </section>
        </div>

        <div class="announcement"><i></i>{{ trans('messages.Free for customers: get offers from verified solar companies') }}</div>
        @include('visitors.partials.header')

        <main>
            @yield('content')
        </main>

        <footer class="footer">
            <div class="container footer-grid">
                <div>
                    <a class="brand footer-brand" href="{{ $homeUrl }}">
                        <picture>
                            <source type="image/webp"
                                srcset="{{ asset_v('visitors/images/logo-128.webp') }} 1x, {{ asset_v('visitors/images/logo-256.webp') }} 2x">
                            <img src="{{ asset_v('visitors/images/logo-128.png') }}" alt="SunXFinder"
                                width="88" height="88" loading="lazy" decoding="async"
                                onerror="this.style.display='none';this.closest('a').querySelector('.brand-fallback').hidden=false">
                        </picture>
                        <span class="brand-fallback" hidden>Sun<span>X</span>Finder</span>
                    </a>

                    <p class="footer-slogan">{{ trans('messages.Find.') }} <span class="slogan-compare">{{ trans('messages.Compare.') }}</span> {{ trans('messages.Connect.') }}</p>
                    <p>{{ trans('messages.The easy way to find, compare and choose a solar company.') }}</p>
                </div>

                <div>
                    <strong>{{ trans('messages.Explore') }}</strong>
                    <a href="{{ $homeUrl }}#how">{{ trans('messages.How it works') }}</a>
                    <a href="{{ $homeUrl }}#solutions">{{ trans('messages.Solutions') }}</a>
                    <a href="{{ $homeUrl }}#companies">{{ trans('messages.Companies') }}</a>
                    <a href="{{ $homeUrl }}#faq">{{ trans('messages.FAQ') }}</a>
                </div>

                <div>
                    <strong>{{ trans('messages.For customers') }}</strong>
                    <a href="{{ route('customer.register', ['lang' => app()->getLocale()]) }}">{{ trans('messages.Get free offers') }}</a>
                    <a href="mailto:hello@sunxfinder.com">{{ trans('messages.Need help?') }}</a>
                </div>

                <div>
                    <strong>{{ trans('messages.For companies') }}</strong>
                    <a href="{{ route('company.register', ['lang' => app()->getLocale()]) }}">{{ trans('messages.Join as a company') }}</a>
                    <a href="{{ route('company.login') }}">{{ trans('messages.Company login') }}</a>
                    <a href="mailto:info@sunxfinder.com">{{ trans('messages.Company inquiries') }}</a>
                </div>
            </div>

            <div class="container footer-bottom">
                <span>© {{ date('Y') }} SunXFinder. {{ trans('messages.All rights reserved.') }}</span>


                <div class="footer-bottom-links">
                    <a href="{{ route('privacy-policy', ['lang' => app()->getLocale()]) }}">
                        {{ trans('messages.Privacy') }}
                    </a>
                    <a href="{{ route('terms', ['lang' => app()->getLocale()]) }}">
                        {{ trans('messages.Terms') }}
                    </a>
                </div>

                <div class="visitor-counter">
                    <span class="visitor-counter__label">
                        {{ trans('messages.Total visitors') }}
                    </span>

                    <strong class="visitor-counter__count">
                        {{ number_format($visitorCount ?? 0) }}
                    </strong>
                </div>
            </div>
        </footer>


        <script>
            (() => {

                //Toggle Menu
                const menuButton = document.querySelector('.menu-toggle');
                const nav = document.querySelector('.nav-links');

                if (menuButton && nav) {
                    menuButton.addEventListener('click', () => {
                        const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
                        menuButton.setAttribute('aria-expanded', String(!isOpen));
                        nav.classList.toggle('open', !isOpen);
                    });

                    nav.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
                        menuButton.setAttribute('aria-expanded', 'false');
                        nav.classList.remove('open');
                    }));
                }

                //language Modal
                const modal = document.getElementById('languageModal');
                const openLanguage = document.getElementById('openLanguageModal');
                const closeLanguage = document.getElementById('closeLanguageModal');

                const showModal = () => {
                    if (!modal) return;
                    modal.hidden = false;
                    document.body.classList.add('modal-open');
                    const firstLanguage = modal.querySelector('a');
                    if (firstLanguage) firstLanguage.focus();
                };

                const hideModal = () => {
                    if (!modal) return;
                    modal.hidden = true;
                    document.body.classList.remove('modal-open');
                    if (openLanguage) openLanguage.focus();
                };

                if (modal && !modal.hidden) document.body.classList.add('modal-open');
                if (openLanguage) openLanguage.addEventListener('click', showModal);
                if (closeLanguage) closeLanguage.addEventListener('click', hideModal);
                if (modal) modal.addEventListener('click', event => {
                    if (event.target === modal) hideModal();
                });
                document.addEventListener('keydown', event => {
                    if (event.key === 'Escape' && modal && !modal.hidden) hideModal();
                });

                document.querySelectorAll('.faq-question').forEach(question => {
                    question.setAttribute('role', 'button');
                    question.setAttribute('tabindex', '0');

                    const toggleFaq = () => {
                        const item = question.parentElement;
                        item.classList.toggle('active');
                        const toggle = question.querySelector('.faq-toggle');
                        if (toggle) toggle.textContent = item.classList.contains('active') ? '−' : '+';
                        question.setAttribute('aria-expanded', String(item.classList.contains('active')));
                    };

                    question.addEventListener('click', toggleFaq);
                    question.addEventListener('keydown', event => {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            toggleFaq();
                        }
                    });
                    question.setAttribute('aria-expanded', 'false');
                });

                const animated = document.querySelectorAll('.animate-fade-in-up');
                if ('IntersectionObserver' in window) {
                    const observer = new IntersectionObserver(entries => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                entry.target.classList.add('animate-in');
                                observer.unobserve(entry.target);
                            }
                        });
                    }, {
                        threshold: 0.08
                    });
                    animated.forEach(element => observer.observe(element));
                } else {
                    animated.forEach(element => element.classList.add('animate-in'));
                }

                // Active navigation link on scroll (works across pages)
                const activeNavLinks = document.querySelectorAll(
                    '.nav-links a[href*="#"]:not(.btn)'
                );

                const activeNavSections = [...activeNavLinks]
                    .map(link => {
                        const url = new URL(link.href, window.location.origin);

                        // Only observe sections on the current page
                        if (url.pathname !== window.location.pathname) {
                            return null;
                        }

                        return url.hash.length > 1 ?
                            document.querySelector(url.hash) :
                            null;
                    })
                    .filter(Boolean);

                if (
                    'IntersectionObserver' in window &&
                    activeNavSections.length
                ) {
                    const activeNavObserver = new IntersectionObserver(entries => {
                        entries.forEach(entry => {
                            if (!entry.isIntersecting) return;

                            const currentHash = `#${entry.target.id}`;

                            activeNavLinks.forEach(link => {
                                const url = new URL(
                                    link.href,
                                    window.location.origin
                                );

                                const isActive = url.hash === currentHash &&
                                    url.pathname === window.location.pathname;

                                link.classList.toggle('active', isActive);

                                if (isActive) {
                                    link.setAttribute('aria-current', 'location');
                                } else {
                                    link.removeAttribute('aria-current');
                                }
                            });
                        });
                    }, {
                        rootMargin: '-20% 0px -65% 0px',
                        threshold: 0
                    });

                    activeNavSections.forEach(section => {
                        activeNavObserver.observe(section);
                    });
                }

                //Slider 

                /* Company Slider with per-company interval */
                const companiesSlider = document.getElementById('companiesSlider');
                const companiesPrev = document.getElementById('companiesPrev');
                const companiesNext = document.getElementById('companiesNext');

                if (companiesSlider && companiesPrev && companiesNext) {
                    const isArabic =
                        companiesSlider.dataset.direction === 'rtl' ||
                        document.documentElement.lang === 'ar';

                    let autoplayTimer = null;
                    let isManuallyInteracting = false;
                    let interactionTimeout = null;

                    const getCards = () =>
                        Array.from(companiesSlider.querySelectorAll('.company-card'));

                    const getScrollAmount = () => {
                        const card = companiesSlider.querySelector('.company-card');

                        if (!card) return companiesSlider.clientWidth;

                        const styles = getComputedStyle(companiesSlider);
                        const gap = parseFloat(styles.columnGap || styles.gap) || 0;

                        return card.getBoundingClientRect().width + gap;
                    };

                    const getMaxScroll = () =>
                        Math.max(
                            0,
                            companiesSlider.scrollWidth - companiesSlider.clientWidth
                        );

                    const getScrollPosition = () => {
                        if (!isArabic) {
                            return companiesSlider.scrollLeft;
                        }

                        const maxScroll = getMaxScroll();

                        return Math.max(
                            0,
                            Math.min(
                                maxScroll,
                                maxScroll - Math.abs(companiesSlider.scrollLeft)
                            )
                        );
                    };

                    const updateCompanyControls = () => {
                        const maxScroll = getMaxScroll();
                        const position = getScrollPosition();

                        companiesPrev.disabled = isArabic
                            ? position >= maxScroll - 2
                            : position <= 2;

                        companiesNext.disabled = isArabic
                            ? position <= 2
                            : position >= maxScroll - 2;
                    };

                    const scrollSlider = (buttonDirection) => {
                        const amount = getScrollAmount();
                        const delta = buttonDirection === 'prev' ? -1 : 1;
                        const direction = isArabic ? -delta : delta;

                        companiesSlider.scrollBy({
                            left: direction * amount,
                            behavior: 'smooth'
                        });
                    };

                    // Find the card nearest the beginning of the visible viewport.
                    const getCurrentCardIndex = () => {
                        const cards = getCards();

                        if (!cards.length) return -1;

                        const sliderRect = companiesSlider.getBoundingClientRect();

                        let closestIndex = 0;
                        let closestDistance = Infinity;

                        cards.forEach((card, index) => {
                            const rect = card.getBoundingClientRect();

                            const distance = isArabic
                                ? Math.abs(sliderRect.right - rect.right)
                                : Math.abs(rect.left - sliderRect.left);

                            if (distance < closestDistance) {
                                closestDistance = distance;
                                closestIndex = index;
                            }
                        });

                        return closestIndex;
                    };

                    const goToCard = (index) => {
                        const cards = getCards();
                        const card = cards[index];

                        if (!card) return;

                        const sliderRect = companiesSlider.getBoundingClientRect();
                        const cardRect = card.getBoundingClientRect();

                        const delta = isArabic
                            ? cardRect.right - sliderRect.right
                            : cardRect.left - sliderRect.left;

                        companiesSlider.scrollBy({
                            left: delta,
                            behavior: 'smooth'
                        });
                    };

                    const getIntervalForCard = (card) => {
                        const seconds = Number.parseInt(card?.dataset.interval, 10);

                        // Use 5 seconds if the interval is missing or invalid.
                        return Number.isFinite(seconds) && seconds > 0
                            ? seconds * 1000
                            : 5000;
                    };

                    const stopAutoplay = () => {
                        if (autoplayTimer !== null) {
                            clearTimeout(autoplayTimer);
                            autoplayTimer = null;
                        }
                    };

                    const scheduleNextSlide = () => {
                        stopAutoplay();

                        const cards = getCards();

                        if (cards.length < 2 || isManuallyInteracting) return;

                        const currentIndex = getCurrentCardIndex();
                        const currentCard = cards[currentIndex];

                        autoplayTimer = setTimeout(() => {
                            const latestCards = getCards();

                            if (latestCards.length < 2) return;

                            const latestIndex = getCurrentCardIndex();
                            const nextIndex = latestIndex + 1;

                            // Return to the beginning after the last company.
                            if (nextIndex >= latestCards.length) {
                                goToCard(0);
                            } else {
                                goToCard(nextIndex);
                            }

                            // Wait for the smooth scroll to finish before scheduling
                            // the next interval for the newly displayed company.
                            setTimeout(() => {
                                updateCompanyControls();
                                scheduleNextSlide();
                            }, 500);
                        }, getIntervalForCard(currentCard));
                    };

                    const restartAutoplay = () => {
                        stopAutoplay();
                        scheduleNextSlide();
                    };

                    const handleManualInteraction = (direction) => {
                        isManuallyInteracting = true;
                        stopAutoplay();

                        scrollSlider(direction);
                        updateCompanyControls();

                        clearTimeout(interactionTimeout);

                        // Resume autoplay after the user has had time to interact.
                        interactionTimeout = setTimeout(() => {
                            isManuallyInteracting = false;
                            restartAutoplay();
                        }, 5000);
                    };

                    companiesPrev.addEventListener('click', () => {
                        handleManualInteraction('prev');
                    });

                    companiesNext.addEventListener('click', () => {
                        handleManualInteraction('next');
                    });

                    // Pause autoplay while the user interacts with the slider.
                    companiesSlider.addEventListener('mouseenter', stopAutoplay);

                    companiesSlider.addEventListener('mouseleave', () => {
                        if (!isManuallyInteracting) restartAutoplay();
                    });

                    companiesSlider.addEventListener('focusin', stopAutoplay);

                    companiesSlider.addEventListener('focusout', () => {
                        setTimeout(() => {
                            if (
                                !companiesSlider.contains(document.activeElement) &&
                                !isManuallyInteracting
                            ) {
                                restartAutoplay();
                            }
                        }, 100);
                    });

                    let scrollFrame = null;
                    companiesSlider.addEventListener('scroll', () => {
                        if (scrollFrame) return;
                        scrollFrame = requestAnimationFrame(() => {
                            scrollFrame = null;
                            updateCompanyControls();
                        });
                    }, { passive: true });

                    // Debounce resize so layout is measured once per resize burst,
                    // not on every resize event.
                    let resizeFrame = null;
                    window.addEventListener('resize', () => {
                        if (resizeFrame) cancelAnimationFrame(resizeFrame);
                        resizeFrame = requestAnimationFrame(() => {
                            resizeFrame = null;
                            updateCompanyControls();
                            restartAutoplay();
                        });
                    }, { passive: true });

                    // Measure the slider only after the page has rendered.
                    // Calling getBoundingClientRect/scrollWidth while the HTML is still
                    // being parsed forces a synchronous layout (Lighthouse "Forced reflow").
                    const initSlider = () => requestAnimationFrame(() => {
                        updateCompanyControls();
                        restartAutoplay();
                    });

                    if (document.readyState === 'complete') {
                        initSlider();
                    } else {
                        window.addEventListener('load', initSlider, { once: true });
                    }
                }



            })();
        </script>
        @if ($needsEditor)
            <script src="{{ asset_v('main/js/quill.js') }}"></script>
        @endif
        @yield('scripts')
</body>

</html>
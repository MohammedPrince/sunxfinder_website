<aside class="sidebar" id="adminSidebar">
    @php
        $brandCompanyName = auth('company')->user()->name ?? 'SunXFinder';
    @endphp
    @php
        // The public page (/companies/{slug}) only shows approved, active companies.
        $sidebarCompany = auth('company')->user();
        $publicPageUrl = $sidebarCompany?->slug
            ? route('company-profile.show', ['slug' => $sidebarCompany->slug])
            : null;
        $isPubliclyVisible = $publicPageUrl
            && $sidebarCompany->status === 'approved'
            && $sidebarCompany->is_active;
    @endphp
    <div class="brand company-brand">
        <a class="brand-mark" href="{{ route('company.dashboard') }}">
            <img src="{{ !empty($sidebarCompany->logo_path)
    ? asset($sidebarCompany->logo_path)
    : asset('admin/images/sunxfinder-logo.png') }}" alt="{{ $sidebarCompany->slug ?? 'SunXFinder' }}"
                onerror="this.onerror=null; this.src='{{ asset('admin/images/sunxfinder-logo.png') }}';">
        </a>

        <span class="brand-copy">
            <a href="{{ route('company.dashboard') }}">
                <strong title="{{ $brandCompanyName }}">
                    {{ $brandCompanyName }}
                </strong>
            </a>

            <a href="https://dev.sunxfinder.com" target="_blank" rel="noopener">
                <small>Sun<b style="color: #fc5202">X</b>Finder</small>
            </a>
        </span>
    </div>
    {{-- Only the links scroll; brand and account box stay in place --}}
    <div class="sidebar-scroll">
        @component('partials.nav-group', ['key' => 'overview', 'label' => __('company/messages.navigation.overview'), 'icon' => 'grid', 'routes' => ['company.dashboard', 'company.reports.*']])
        <a class="nav-link {{ request()->routeIs('company.dashboard') ? 'active' : '' }}"
            href="{{ route('company.dashboard') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z" />
                </svg>
            </span>
            <span>{{ __('company/messages.navigation.dashboard') }}</span>
        </a>
        <a class="nav-link {{ request()->routeIs('company.reports.*') ? 'active' : '' }}"
            href="{{ route('company.reports.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 3v18h18" />
                    <path d="M7 15v2M11 11v6M15 7v10M19 12v5" />
                </svg>
            </span>
            <span>{{ __('company/messages.navigation.reports') }}</span>
        </a>
        @endcomponent

        @component('partials.nav-group', ['key' => 'management', 'label' => __('company/messages.navigation.management'), 'icon' => 'briefcase', 'routes' => ['company.profile', 'company.products.*']])
        <a class="nav-link {{ request()->routeIs('company.profile') ? 'active' : '' }}"
            href="{{ route('company.profile') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="8" r="4" />
                    <path d="M5 21v-2a7 7 0 0 1 14 0v2" />
                </svg>
            </span>
            <span>{{ __('company/messages.navigation.company_profile') }}</span>
        </a>
        <a class="nav-link {{ request()->routeIs('company.products.*') ? 'active' : '' }}"
            href="{{ route('company.products.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m12 3 9 5-9 5-9-5 9-5Z" />
                    <path d="m3 8 9 5 9-5" />
                    <path d="M3 8v9l9 5 9-5V8" />
                    <path d="M12 13v9" />
                </svg>
            </span>
            <span>{{ __('company/messages.navigation.my_products') }}</span>
            <span class="nav-count warm">{{ $productsCount ?? 0 }}</span>
        </a>
        @endcomponent

        @component('partials.nav-group', ['key' => 'quotes', 'label' => __('company/messages.navigation.quotes_projects'), 'icon' => 'file', 'routes' => ['company.quote-requests.*', 'company.projects.*', 'company.invoices.*']])
        <a class="nav-link {{ request()->routeIs('company.quote-requests.*') ? 'active' : '' }}"
            href="{{ route('company.quote-requests.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 2.75h8l5 5V21a1 1 0 0 1-1 1H6a2 2 0 0 1-2-2V4.75a2 2 0 0 1 2-2z" />
                    <path d="M14 2.75v5h5" />
                    <path d="M8 12h8M8 15h4" />
                    <circle cx="16" cy="17" r="3.5" />
                </svg>
            </span>
            <span>{{ __('company/messages.navigation.quote_requests') }}</span>
            <span class="nav-count warm">{{ $myCompanyQuoteCount ?? 0 }}</span>
        </a>
        <a class="nav-link {{ request()->routeIs('company.projects.*') ? 'active' : '' }}"
            href="{{ route('company.projects.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 6.5a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <path d="M7 11l1.5 1.5L11 10M13 11h4M7 15l1.5 1.5L11 14M13 15h4" />
                </svg>
            </span>
            <span>{{ __('company/messages.navigation.active_projects') }}</span>
            <span class="nav-count warm">{{ $myActiveProjectCount ?? 0 }}</span>
        </a>
        <a class="nav-link {{ request()->routeIs('company.invoices.*') ? 'active' : '' }}"
            href="{{ route('company.invoices.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 2.75h8l5 5V21a1 1 0 0 1-1 1H6a2 2 0 0 1-2-2V4.75a2 2 0 0 1 2-2z" />
                    <path d="M14 2.75v5h5M8 12h8M8 16h5" />
                </svg>
            </span>
            <span>{{ __('company/messages.navigation.invoices') }}</span>
        </a>
        @endcomponent

        @component('partials.nav-group', ['key' => 'customers', 'label' => __('company/messages.navigation.customers'), 'icon' => 'chat', 'routes' => ['company.customers.*', 'company.notifications.*', 'company.ratings.*', 'company.ads.*']])
        <a class="nav-link {{ request()->routeIs('company.customers.*') ? 'active' : '' }}"
            href="{{ route('company.customers.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="10" cy="7" r="4" />
                    <path d="M20 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
            </span>
            <span>{{ __('company/messages.navigation.company_customers') }}</span>
            <span class="nav-count warm">{{ $companyCustomersCount ?? 0 }}</span>
        </a>
        <a class="nav-link {{ request()->routeIs('company.notifications.*') ? 'active' : '' }}"
            href="{{ route('company.notifications.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                    <path d="M10 21h4" />
                    <path d="M12 2v1" />
                </svg>
            </span>
            <span>{{ __('company/messages.navigation.push_notifications') }}</span>
            <span class="nav-count warm">{{ $notificationsCount ?? 0 }}</span>
        </a>
        @if (\App\Models\Setting::companyAdsEnabled((int) $sidebarCompany?->id))
            <a class="nav-link {{ request()->routeIs('company.ads.*') ? 'active' : '' }}"
                href="{{ route('company.ads.index') }}">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 11v2a1 1 0 0 0 1 1h3l9 5V5L7 10H4a1 1 0 0 0-1 1Z" />
                        <path d="M19.5 9.5a3.5 3.5 0 0 1 0 5" />
                    </svg>
                </span>
                <span>{{ __('company/messages.navigation.advertising') }}</span>
            </a>
        @endif
        <a class="nav-link {{ request()->routeIs('company.ratings.*') ? 'active' : '' }}"
            href="{{ route('company.ratings.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m12 3 2.7 5.5 6 .9-4.4 4.2 1 6L12 16.8 6.7 19.6l1-6L3.3 9.4l6-.9L12 3Z" />
                </svg>
            </span>
            <span>{{ __('company/messages.navigation.my_ratings') }}</span>
            <span class="nav-count warm">{{ $ratingsCount ?? 0 }}</span>
        </a>
        @endcomponent

        @component('partials.nav-group', ['key' => 'system', 'label' => __('company/messages.navigation.system'), 'icon' => 'gear', 'routes' => []])
        @if ($isPubliclyVisible)
            <a class="nav-link" href="{{ $publicPageUrl }}" target="_blank" rel="noopener"
                title="{{ __('company/messages.navigation.public_page_title') }}">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18" />
                    </svg>
                </span>
                <span>{{ __('company/messages.navigation.company_on_website') }}</span>
            </a>
        @else
            <span class="nav-link nav-link-disabled"
                title="{{ __('company/messages.navigation.public_page_pending') }}">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18" />
                    </svg>
                </span>
                <span>{{ __('company/messages.navigation.company_on_website') }} <small>{{ __('company/messages.navigation.after_approval') }}</small></span>
            </span>
        @endif
        <a class="nav-link" href="{{ url('/') }}" target="_blank" rel="noopener">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 3h7v7" />
                    <path d="M10 14 21 3" />
                    <path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5" />
                </svg>
            </span>
            <span>{{ __('company/messages.navigation.view_website') }}</span>
        </a>
        @endcomponent
    </div>

    @php
        $companyUser = auth('company')->user();
        $companyUsername = $companyUser->username ?? 'CO';
        $companyDisplayName = $companyUser->name ?? ($companyUser->username ?? 'SunXFinder Company');

        $companyInitials = strtoupper(substr($companyUsername, 0, 2));
    @endphp

    <div class="sidebar-bottom">

        <div class="sidebar-status">
            <i class="status-dot"></i>
            <span>
                <strong>{{ __('company/messages.navigation.system_operational') }}</strong>
                <small>{{ __('company/messages.navigation.all_services_good') }}</small>
            </span>
        </div>

        <div class="admin-mini">
            <div class="avatar">
                {{ $companyInitials }}
            </div>

            <div class="admin-mini-copy">
                <strong>{{ $companyDisplayName }}</strong>
                <small>{{ __('company/messages.navigation.company_user') }}</small>
            </div>

            <form method="POST" action="{{ route('company.logout') }}">
                @csrf

                <button type="submit" class="icon-button" title="{{ __('company/messages.navigation.log_out') }}" aria-label="{{ __('company/messages.navigation.log_out') }}">
                    ↪
                </button>
            </form>
        </div>
    </div>
</aside>

@php
    // Account badge: approval first, then verification.
    $accountBadge = match (true) {
        ($topbar['accountStatus'] ?? null) === 'pending' => [__('company/messages.navigation.waiting_approval'), 'pill-orange'],
        ($topbar['accountStatus'] ?? 'approved') !== 'approved' => [company_label('common.' . $topbar['accountStatus'], ucfirst($topbar['accountStatus'])), 'pill-red'],
        !empty($topbar['isVerified']) => [__('company/messages.navigation.verified_company'), 'pill-green'],
        default => [__('company/messages.navigation.not_verified_yet'), 'pill-blue'],
    };

    $companyAccountLinks = [
        ['label' => __('company/messages.navigation.company_profile_link'), 'url' => route('company.profile')],
        ['label' => __('company/messages.navigation.reports'), 'url' => route('company.reports.index')],
    ];

    if (!empty($topbar['publicProfileUrl'])) {
        $companyAccountLinks[] = ['label' => __('company/messages.navigation.see_public_page'), 'url' => $topbar['publicProfileUrl'], 'external' => true];
    }
@endphp

@include('partials.topbar', [
    'userName' => $companyDisplayName,
    'greetingName' => $companyDisplayName,
    'userInitials' => $companyInitials,
    'roleLabel' => __('company/messages.navigation.company_account'),
    'userEmail' => $companyUser->email ?? null,
    'accountBadge' => $accountBadge,
    'quickActions' => [
        ['label' => __('company/messages.navigation.find_new_requests'), 'url' => route('company.quote-requests.index', ['status' => 'pending']), 'hint' => __('company/messages.navigation.find_new_requests_hint')],
        ['label' => __('company/messages.navigation.update_project'), 'url' => route('company.projects.index', ['status' => 'in_progress']), 'hint' => __('company/messages.navigation.update_project_hint')],
        ['label' => __('company/messages.navigation.add_product'), 'url' => route('company.products.create'), 'hint' => __('company/messages.navigation.add_product_hint')],
        ['label' => __('company/messages.navigation.send_notification'), 'url' => route('company.notifications.create'), 'hint' => __('company/messages.navigation.send_notification_hint')],
    ],
    'accountLinks' => $companyAccountLinks,
    'logoutRoute' => 'company.logout',
])

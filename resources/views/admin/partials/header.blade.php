<aside class="sidebar" id="adminSidebar">
    <div class="brand"><a class="brand-mark" href="{{ route('admin.dashboard.page') }}"><img
                src="{{ asset('admin/images/profile_pic.png') }}" alt="SunXFinder"></a><span class="brand-copy"><a
                href="https://sunxfinder.com" target="_blank" rel="noopener"><strong>Sun<b
                        style="color: #fc5202">X</b>Finder</strong></a><a
                href="{{ route('admin.dashboard.page') }}"><small>ADMIN CONSOLE</small></a></span></div>
    {{-- Only the links scroll; brand and account box stay in place --}}
    <div class="sidebar-scroll">
        @component('partials.nav-group', ['key' => 'overview', 'label' => 'Overview', 'icon' => 'grid', 'routes' => ['admin.dashboard.page', 'admin.reports.*']])
        <a class="nav-link {{ request()->routeIs('admin.dashboard.page') ? 'active' : '' }}"
            href="{{ route('admin.dashboard.page') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z" />
                </svg>
            </span>
            <span>Dashboard</span>
        </a>
        <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"
            href="{{ route('admin.reports.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 3v18h18" />
                    <path d="M7 15v2M11 11v6M15 7v10M19 12v5" />
                </svg>
            </span>
            <span>Reports</span>
        </a>
        @endcomponent

        @component('partials.nav-group', ['key' => 'management', 'label' => 'Management', 'icon' => 'users', 'routes' => ['admin.companies.*', 'admin.customers.*', 'admin.suppliers.*', 'admin.products.*']])
        <a class="nav-link {{ request()->routeIs('admin.companies.*') ? 'active' : '' }}"
            href="{{ route('admin.companies.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="2" />
                    <path d="M9 21V7h6v14" />
                    <path d="M6 7h.01M6 11h.01M6 15h.01M18 7h.01M18 11h.01M18 15h.01M11 10h2M11 14h2" />
                </svg>
            </span>
            <span>Solar companies</span>
            <span class="nav-count warm">{{ $companyCount ?? 0 }}</span>
        </a>
        <a class="nav-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}"
            href="{{ route('admin.customers.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="10" cy="7" r="4" />
                    <path d="M20 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
            </span>
            <span>Customers</span>
            <span class="nav-count warm">{{ $customersCount ?? 0 }}</span>
        </a>
        <a class="nav-link {{ request()->routeIs('admin.suppliers.*') ? 'active' : '' }}"
            href="{{ route('admin.suppliers.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 10V6.5L5 3h14l2 3.5V10" />
                    <path d="M4 10v10h16V10" />
                    <path d="M9 20v-6h6v6" />
                    <path d="M3 10a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0" />
                </svg>
            </span>
            <span>Supplier</span>
            <span class="nav-count warm">{{ $suppliersCount ?? 0 }}</span>
        </a>
        <a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}"
            href="{{ route('admin.products.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m12 3 9 5-9 5-9-5 9-5Z" />
                    <path d="m3 8 9 5 9-5" />
                    <path d="M3 8v9l9 5 9-5V8" />
                    <path d="M12 13v9" />
                </svg>
            </span>
            <span>Products</span>
            <span class="nav-count warm">{{ $productsCount ?? 0 }}</span>
        </a>
        @endcomponent

        @component('partials.nav-group', ['key' => 'app', 'label' => 'App Management', 'icon' => 'layers', 'routes' => ['admin.calculator-groups.*', 'admin.company-carousel.*', 'admin.app-versions.*']])
        <a class="nav-link {{ request()->routeIs('admin.calculator-groups.*') ? 'active' : '' }}"
            href="{{ route('admin.calculator-groups.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="2" />
                    <path d="M3 9h18M9 9v12M15 9v12" />
                    <path d="M6 6h.01M12 6h.01M18 6h.01" />
                </svg>
            </span>
            <span>Calculator Groups</span>
            <span class="nav-count warm">{{ $applianceGroupCount ?? 0 }}</span>
        </a>
        <a class="nav-link {{ request()->routeIs('admin.company-carousel.*') ? 'active' : '' }}"
            href="{{ route('admin.company-carousel.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="16" rx="2" />
                    <path d="M7 8h10M7 12h6M7 16h4" />
                    <path d="m16 13 2 2 3-4" />
                </svg>
            </span>
            <span>Company Carousel</span>
            <span class="nav-count warm">{{ $carouselCount ?? 0 }}</span>
        </a>
        <a class="nav-link {{ request()->routeIs('admin.app-versions.*') ? 'active' : '' }}"
            href="{{ route('admin.app-versions.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="6" y="2" width="12" height="20" rx="2.5" />
                    <path d="M11 18h2" />
                    <path d="M12 7v6" />
                    <path d="m9.5 10.5 2.5 2.5 2.5-2.5" />
                </svg>
            </span>
            <span>App Versions</span>
            <span class="nav-count warm">{{ $appVersionCount ?? 0 }}</span>
        </a>
        @endcomponent

        @component('partials.nav-group', ['key' => 'quotes', 'label' => 'Quotes', 'icon' => 'file', 'routes' => ['admin.quote-requests.*', 'admin.projects.*', 'admin.complaints.*', 'admin.notifications.*']])
        <a class="nav-link {{ request()->routeIs('admin.quote-requests.*') ? 'active' : '' }}"
            href="{{ route('admin.quote-requests.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 2.75h8l5 5V21a1 1 0 0 1-1 1H6a2 2 0 0 1-2-2V4.75a2 2 0 0 1 2-2z" />
                    <path d="M14 2.75v5h5" />
                    <path d="M8 12h8M8 15h4" />
                    <circle cx="16" cy="17" r="3.5" />
                </svg>
            </span>
            <span>Quote requests</span>
            <span class="nav-count warm">{{ $quoteCount ?? 0 }}</span>
        </a>
        <a class="nav-link {{ request()->routeIs('admin.projects.*') ? 'active' : '' }}"
            href="{{ route('admin.projects.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="7" width="18" height="13" rx="2" />
                    <path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18M10 13v2h4v-2" />
                </svg>
            </span>
            <span>Projects</span>
        </a>
        <a class="nav-link {{ request()->routeIs('admin.complaints.*') ? 'active' : '' }}"
            href="{{ route('admin.complaints.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 3 3.5 7v5c0 5 3.5 8 8.5 9 5-1 8.5-4 8.5-9V7L12 3Z" />
                    <path d="M12 8v5m0 3h.01" />
                </svg>
            </span>
            <span>Complaints</span>
        </a>
        <a class="nav-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}"
            href="{{ route('admin.notifications.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                    <path d="M10 21h4" />
                    <path d="M12 2v1" />
                </svg>
            </span>
            <span>Push Notifications</span>
            <span class="nav-count warm">{{ $notificationsCount ?? 0 }}</span>
        </a>
        @endcomponent

        @component('partials.nav-group', ['key' => 'ratings', 'label' => 'Ratings', 'icon' => 'star', 'routes' => ['admin.ratings.*', 'admin.rating-questions.*', 'admin.rating-values.*']])
        <a class="nav-link {{ request()->routeIs('admin.ratings.*') ? 'active' : '' }}"
            href="{{ route('admin.ratings.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m12 3 2.7 5.5 6 .9-4.4 4.2 1 6L12 16.8 6.7 19.6l1-6L3.3 9.4l6-.9L12 3Z" />
                </svg>
            </span>
            <span>Ratings</span>
            <span class="nav-count warm">{{ $ratingsCount ?? 0 }}</span>
        </a>
        <a class="nav-link {{ request()->routeIs('admin.rating-questions.*', 'admin.rating-values.*') ? 'active' : '' }}"
            href="{{ route('admin.rating-questions.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 6h11M9 12h11M9 18h11" />
                    <path d="m3.5 6 1 1 2-2M3.5 12l1 1 2-2M3.5 18l1 1 2-2" />
                </svg>
            </span>
            <span>Rating Questions</span>
            <span class="nav-count warm">{{ $ratingQuestionsCount ?? 0 }}</span>
        </a>
        @endcomponent

        @component('partials.nav-group', ['key' => 'system', 'label' => 'System', 'icon' => 'gear', 'routes' => ['admin.send-email.*', 'admin.logs.*', 'admin.queue.*', 'admin.settings.*', 'admin.backups.*', 'admin.archives.*']])
        <a class="nav-link {{ request()->routeIs('admin.send-email.*') ? 'active' : '' }}"
            href="{{ route('admin.send-email.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="5" width="18" height="14" rx="2" />
                    <path d="m3 7 9 6 9-6" />
                </svg>
            </span>
            <span>Send Email</span>
        </a>
        <a class="nav-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}"
            href="{{ route('admin.logs.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" />
                    <path d="M14 3v6h6" />
                    <path d="M8 13h8M8 17h5" />
                </svg>
            </span>
            <span>Activity Logs</span>
        </a>
        <a class="nav-link {{ request()->routeIs('admin.queue.*') ? 'active' : '' }}"
            href="{{ route('admin.queue.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="5" rx="1.5" />
                    <rect x="3" y="10" width="18" height="5" rx="1.5" />
                    <rect x="3" y="16" width="18" height="5" rx="1.5" />
                </svg>
            </span>
            <span>Background Jobs</span>
            @if (($failedJobsCount ?? 0) > 0)
                <span class="nav-count warm" title="Failed jobs">{{ $failedJobsCount }}</span>
            @endif
        </a>
        <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"
            href="{{ route('admin.settings.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6" />
                </svg>
            </span>
            <span>Settings</span>
            @if (\App\Models\Setting::maintenance()['enabled'])
                <span class="nav-count warm" title="Maintenance mode is on">ON</span>
            @endif
        </a>
        <a class="nav-link {{ request()->routeIs('admin.backups.*') ? 'active' : '' }}"
            href="{{ route('admin.backups.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <ellipse cx="12" cy="5" rx="8" ry="3" />
                    <path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5" />
                    <path d="M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6" />
                </svg>
            </span>
            <span>Database Backups</span>
        </a>
        <a class="nav-link {{ request()->routeIs('admin.archives.*') ? 'active' : '' }}"
            href="{{ route('admin.archives.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 4h18v4H3z" />
                    <path d="M5 8v12h14V8M10 12h4" />
                </svg>
            </span>
            <span>Archives</span>
        </a>

        @endcomponent
    </div>

    <div class="sidebar-bottom">
        <div class="sidebar-status">
            <a class="nav-link" href="{{ url('/') }}" target="_blank" rel="noopener">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 3h7v7" />
                        <path d="M10 14 21 3" />
                        <path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5" />
                    </svg>
                </span>
                <span>View website</span>
            </a>
        </div>
        <div class="sidebar-status"><i class="status-dot"></i><span><strong>System operational</strong><small>All
                    services look good</small></span></div>
        <div class="admin-mini">
            @php
                $adminUser = auth('admin')->user();
            @endphp

            <div class="avatar">
                {{ strtoupper(substr($adminUser->username ?? 'SA', 0, 2)) }}
            </div>

            <div class="admin-mini-copy">
                <strong>{{ $adminUser->username ?? 'SunXFinder Admin' }}</strong>
                <small>Administrator</small>
            </div>

            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf

                <button type="submit" class="icon-button" title="Log out" aria-label="Log out">
                    ↪
                </button>
            </form>
        </div>
    </div>
</aside>
@include('partials.topbar', [
    'userName' => $adminUser?->name ?: ($adminUser?->username ?? 'SunXFinder Admin'),
    'userInitials' => strtoupper(substr($adminUser->username ?? 'SA', 0, 2)),
    'roleLabel' => 'Administrator',
    'userEmail' => $adminUser->email ?? null,
    'quickActions' => [
        ['label' => 'Add a company', 'url' => route('admin.companies.create'), 'hint' => 'Register a solar company'],
        ['label' => 'Add a customer', 'url' => route('admin.customers.create'), 'hint' => 'Create a customer account'],
        ['label' => 'Add a product', 'url' => route('admin.products.create'), 'hint' => 'List a product in the catalog'],
        ['label' => 'Send a notification', 'url' => route('admin.notifications.create'), 'hint' => 'Push a message to the app'],
        ['label' => 'Publish an app version', 'url' => route('admin.app-versions.create'), 'hint' => 'Android or iOS release'],
    ],
    'accountLinks' => [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard.page')],
        ['label' => 'Reports', 'url' => route('admin.reports.index')],
        ['label' => 'View website', 'url' => url('/'), 'external' => true],
    ],
    'logoutRoute' => 'admin.logout',
])

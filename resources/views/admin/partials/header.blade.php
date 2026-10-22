<aside class="sidebar" id="adminSidebar">
    <a class="brand" href="{{ route('admin.dashboard.page') }}"><span class="brand-mark"><img
                src="{{ asset('admin/images/profile_pic.png') }}" alt="SunXFinder"></span><span
            class="brand-copy"><strong>Sun<b style="color: #fc5202">X</b>Finder</strong><small>ADMIN
                CONSOLE</small></span></a>
    <div class="sidebar-label">WORKSPACE</div>
    <nav class="side-nav">
        <a class="nav-link {{ request()->routeIs('admin.dashboard.page') ? 'active' : '' }}"
            href="{{ route('admin.dashboard.page') }}"><span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                    aria-hidden="true">
                    <rect x="3" y="3" width="7" height="9" rx="1.5" />
                    <rect x="14" y="3" width="7" height="5" rx="1.5" />
                    <rect x="14" y="12" width="7" height="9" rx="1.5" />
                    <rect x="3" y="16" width="7" height="5" rx="1.5" />
                </svg>
            </span>Dashboard</a>

        <div class="sidebar-label">MANAGEMENT</div>
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
            Solar companies
            <span class="nav-count warm">{{ $companyCount }}</span>
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
            Customers
            <span class="nav-count warm">{{ $customersCount }}</span>

        </a>

        <a class="nav-link {{ request()->routeIs('admin.suppliers.*') ? 'active' : '' }}"
            href="{{ route('admin.suppliers.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <!-- Store roof -->
                    <path d="M3 10V6.5L5 3h14l2 3.5V10" />
                    <!-- Storefront -->
                    <path d="M4 10v10h16V10" />
                    <path d="M9 20v-6h6v6" />
                    <!-- Solar/product boxes -->
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

        <div class="sidebar-label">APP MANAGEMENT</div>

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

            Calculator Groups

            <span class="nav-count warm">{{ $applianceGroupCount ?? 0 }}</span>
        </a>

        <a class="nav-link {{ request()->routeIs('admin.company-carousel.*') ? 'active' : '' }}"
            href="{{ route('admin.company-carousel.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="16" rx="2" />
                    <path d="M7 8h10" />
                    <path d="M7 12h6" />
                    <path d="M7 16h4" />
                    <path d="m16 13 2 2 3-4" />
                </svg>
            </span>
            <span>Company Carousel</span>

            <span class="nav-count warm">{{ $carouselCount ?? 0 }}</span>
        </a>

        <div class="sidebar-label">QUOTES</div>

        <a class="nav-link {{ request()->routeIs('admin.quote-requests.*') ? 'active' : '' }}"
            href="{{ route('admin.quote-requests.index') }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 2.75h8l5 5V21a1 1 0 0 1-1 1H6a2 2 0 0 1-2-2V4.75a2 2 0 0 1 2-2z" />
                    <path d="M14 2.75v5h5" />
                    <path d="M8 12h8" />
                    <path d="M8 15h4" />
                    <circle cx="16" cy="17" r="3.5" />
                    <path d="M16 14.8v4.4" />
                    <path
                        d="M17.3 15.8c-.3-.4-.7-.6-1.3-.6-.7 0-1.2.4-1.2.9 0 1.3 2.4.7 2.4 1.9 0 .5-.5.9-1.2.9-.6 0-1.1-.2-1.4-.6" />
                </svg>
            </span>

            <span>Quote requests</span>

            <span class="nav-count warm">{{ $quoteCount ?? 0 }}</span>
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

            {{-- <span class="nav-count warm">{{ $quoteCount ?? 0 }}</span> --}}
        </a>


    </nav>
    <div class="sidebar-label">SYSTEM</div>
    <nav class="side-nav"><a class="nav-link" href="{{ url('/') }}" target="_blank" rel="noopener"><span
                class="nav-icon">↗</span>View website</a><a class="nav-link" href="#"
            data-demo-toast="Connect this item to your settings page."><span class="nav-icon">⚙</span>Settings</a>
    </nav>
    <div class="sidebar-bottom">
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
<header class="topbar"><button class="icon-button mobile-menu" id="sidebarToggle"
        aria-label="Toggle navigation">☰</button>
    <div class="breadcrumb"><span>SunXFinder</span><span>/</span><strong>@yield('breadcrumb', 'Overview')</strong></div>
    <div class="topbar-right"><span class="topbar-date" id="todayDate"></span><button class="icon-button"
            data-demo-toast="You are all caught up." aria-label="Notifications">♧</button>
        <div class="topbar-avatar"> {{ strtoupper(substr($adminUser->username ?? 'SA', 0, 2)) }}</div>
    </div>
</header>
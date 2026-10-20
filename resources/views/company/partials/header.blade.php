<aside class="sidebar" id="adminSidebar">
    <a class="brand" href="{{ route('company.dashboard') }}"><span class="brand-mark"><img
                src="{{ asset('admin/images/sunxfinder-logo.png') }}" alt="SunXFinder"></span><span
            class="brand-copy"><strong>Sun<b
                    style="color: #fc5202">X</b>Finder</strong><small>{{ auth('company')->user()->name ?? 'SunXFinder' }}</small></span></a>
    <div class="sidebar-label">WORKSPACE</div>
    <nav class="side-nav">
        <a class="nav-link {{ request()->routeIs('company.dashboard') ? 'active' : '' }}"
            href="{{ route('company.dashboard') }}"><span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                    aria-hidden="true">
                    <rect x="3" y="3" width="7" height="9" rx="1.5" />
                    <rect x="14" y="3" width="7" height="5" rx="1.5" />
                    <rect x="14" y="12" width="7" height="9" rx="1.5" />
                    <rect x="3" y="16" width="7" height="5" rx="1.5" />
                </svg>
            </span>Dashboard</a>

    </nav>
    <div class="sidebar-label">MANGMENT</div>
    <nav class="side-nav">
        <a class="nav-link {{ request()->routeIs('company.profile') ? 'active' : '' }}"
            href="{{ route('company.profile') }}">
            <span class="nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                    aria-hidden="true">
                    <circle cx="12" cy="8" r="4" />
                    <path d="M5 21v-2a7 7 0 0 1 14 0v2" />
                </svg>
            </span>
            <span>Company Profile</span>
        </a>

        <a href="{{ route('company.products.index') }}"
            class="nav-link {{ request()->routeIs('company.products.*') ? 'active' : '' }}">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m12 3 9 5-9 5-9-5 9-5Z" />
                    <path d="m3 8 9 5 9-5" />
                    <path d="M3 8v9l9 5 9-5V8" />
                    <path d="M12 13v9" />
                </svg>
            </span>
            <span>My Products</span>

            <span class="nav-count warm">{{ $productsCount ?? 0 }}</span>
        </a>

    </nav>
    <div class="sidebar-label">QUOTES</div>

    <a class="nav-link {{ request()->routeIs('company.quote-requests.*') ? 'active' : '' }}"
        href="{{ route('company.quote-requests.index') }}">
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

        <span class="nav-count warm">{{ $myCompanyQuoteCount ?? 0 }}</span>
    </a>


    <div class="sidebar-label">Projects</div>

    <a class="nav-link {{ request()->routeIs('company.projects.*') ? 'active' : '' }}"
        href="{{ route('company.projects.index') }}">

        <span class="nav-icon">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">

                {{-- Project folder --}}
                <path d="M3 6.5a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />

                {{-- Checklist --}}
                <path d="M7 11l1.5 1.5L11 10" />
                <path d="M13 11h4" />

                <path d="M7 15l1.5 1.5L11 14" />
                <path d="M13 15h4" />
            </svg>
        </span>

        <span>Active Projects</span>

        <span class="nav-count warm">{{ $myActiveProjectCount ?? 0 }}</span>
    </a>

    <div class="sidebar-label">SYSTEM</div>

    <nav class="side-nav">
        <a class="nav-link" href="{{ url('/') }}" target="_blank" rel="noopener">
            <span class="nav-icon">↗</span>
            View website
        </a>

        {{-- <a class="nav-link" href="#" data-demo-toast="Connect this item to your settings page.">
            <span class="nav-icon">⚙</span>
            Settings
        </a> --}}
    </nav>

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
                <strong>System operational</strong>
                <small>All services look good</small>
            </span>
        </div>

        <div class="admin-mini">
            <div class="avatar">
                {{ $companyInitials }}
            </div>

            <div class="admin-mini-copy">
                <strong>{{ $companyDisplayName }}</strong>
                <small>Company User</small>
            </div>

            <form method="POST" action="{{ route('company.logout') }}">
                @csrf

                <button type="submit" class="icon-button" title="Log out" aria-label="Log out">
                    ↪
                </button>
            </form>
        </div>
    </div>
</aside>

<header class="topbar">

    <button class="icon-button mobile-menu" id="sidebarToggle" aria-label="Toggle navigation">
        ☰
    </button>

    <div class="breadcrumb">
        <span>SunXFinder</span>
        <span>/</span>
        <strong>@yield('breadcrumb', 'Overview')</strong>
    </div>

    <div class="topbar-right">
        <span class="topbar-date" id="todayDate"></span>

        <button class="icon-button" data-demo-toast="You are all caught up." aria-label="Notifications">
            ♧
        </button>

        <div class="topbar-avatar">
            {{ $companyInitials }}
        </div>
    </div>
</header>
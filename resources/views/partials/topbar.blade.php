{{--
    Shared top bar for the admin and company panels.
    Expects: $topbar (attention/today counts, may be null), $userName, $userInitials, $roleLabel,
    $userEmail, $quickActions [[label, url, hint]], $accountLinks [[label, url, external?]], $logoutRoute,
    optional $accountBadge [label, class] and $greetingName (defaults to the first word of $userName).
--}}
@php
    $attention = $topbar['attention'] ?? [];
    $attentionTotal = (int) ($topbar['attentionTotal'] ?? 0);
    $today = $topbar['today'] ?? [];
    $greetingName = $greetingName ?? \Illuminate\Support\Str::before($userName, ' ');
@endphp

<header class="topbar">
    <button class="icon-button mobile-menu" id="sidebarToggle" type="button" aria-label="Toggle navigation">☰</button>

    <div class="topbar-left">
        <div class="breadcrumb">
            <span>SunXFinder</span>
            <span>/</span>
            <strong>@yield('breadcrumb', 'Overview')</strong>
        </div>
        <div class="topbar-greeting">
            <span data-greeting data-name="{{ $greetingName }}">Welcome back, {{ $greetingName }}</span>
        </div>
    </div>

    <div class="topbar-right">
        <div class="topbar-clock" title="Your local date and time">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="4.5" width="18" height="16.5" rx="2" />
                <path d="M3 9.5h18M8 2.75v3.5M16 2.75v3.5" />
            </svg>
            <span class="topbar-date" id="todayDate"></span>
            <span class="topbar-time" id="todayTime"></span>
        </div>

        @if ($quickActions)
            <details class="topbar-menu">
                <summary class="topbar-action" aria-label="Quick actions">
                    <span aria-hidden="true">+</span><span class="topbar-action-label">Quick actions</span>
                </summary>
                <div class="topbar-dropdown topbar-dropdown-sm">
                    <div class="topbar-dropdown-head"><strong>Quick actions</strong></div>
                    @foreach ($quickActions as $action)
                        <a class="topbar-item" href="{{ $action['url'] }}">
                            <span class="topbar-item-copy">
                                <strong>{{ $action['label'] }}</strong>
                                @isset($action['hint'])<small>{{ $action['hint'] }}</small>@endisset
                            </span>
                            <span class="topbar-item-arrow" aria-hidden="true">→</span>
                        </a>
                    @endforeach
                </div>
            </details>
        @endif

        <details class="topbar-menu">
            <summary class="icon-button topbar-bell {{ $attentionTotal ? 'has-alerts' : '' }}"
                aria-label="Needs your attention: {{ $attentionTotal }}">
                <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                    <path d="M10 21h4" />
                </svg>
                @if ($attentionTotal)
                    <span class="topbar-badge">{{ $attentionTotal > 99 ? '99+' : $attentionTotal }}</span>
                @endif
            </summary>
            <div class="topbar-dropdown">
                <div class="topbar-dropdown-head">
                    <strong>Needs your attention</strong>
                    <small>{{ $attentionTotal ? $attentionTotal . ' item' . ($attentionTotal === 1 ? '' : 's') . ' to act on' : 'You are all caught up' }}</small>
                </div>

                @foreach ($attention as $item)
                    <a class="topbar-item {{ $item['count'] ? '' : 'is-clear' }}" href="{{ $item['url'] }}">
                        <span class="topbar-count tone-{{ $item['count'] ? $item['tone'] : 'muted' }}">{{ $item['count'] }}</span>
                        <span class="topbar-item-copy">
                            <strong>{{ $item['label'] }}</strong>
                            <small>{{ $item['count'] ? $item['hint'] : 'Nothing waiting.' }}</small>
                        </span>
                        <span class="topbar-item-arrow" aria-hidden="true">→</span>
                    </a>
                @endforeach

                @if ($today)
                    <div class="topbar-today">
                        <span class="topbar-today-title">Today so far</span>
                        <div class="topbar-today-grid">
                            @foreach ($today as $stat)
                                <div><strong>{{ number_format($stat['count']) }}</strong><small>{{ $stat['label'] }}</small></div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </details>

        <details class="topbar-menu">
            <summary class="topbar-account" aria-label="Account menu">
                <span class="topbar-avatar">{{ $userInitials }}</span>
                <span class="topbar-account-copy">
                    <strong>{{ $userName }}</strong>
                    <small>{{ $roleLabel }}</small>
                </span>
                <span class="topbar-caret" aria-hidden="true">▾</span>
            </summary>
            <div class="topbar-dropdown topbar-dropdown-sm">
                <div class="topbar-dropdown-head topbar-profile">
                    <span class="topbar-avatar topbar-avatar-lg">{{ $userInitials }}</span>
                    <span>
                        <strong>{{ $userName }}</strong>
                        <small>{{ $userEmail ?: 'No email on file' }}</small>
                        @if (!empty($accountBadge))
                            <span class="pill {{ $accountBadge[1] }}">{{ $accountBadge[0] }}</span>
                        @endif
                    </span>
                </div>
                @foreach ($accountLinks as $link)
                    <a class="topbar-item topbar-link" href="{{ $link['url'] }}"
                        @if (!empty($link['external'])) target="_blank" rel="noopener" @endif>
                        <span class="topbar-item-copy"><strong>{{ $link['label'] }}</strong></span>
                        <span class="topbar-item-arrow" aria-hidden="true">{{ !empty($link['external']) ? '↗' : '→' }}</span>
                    </a>
                @endforeach
                <form method="POST" action="{{ route($logoutRoute) }}" class="topbar-logout">
                    @csrf
                    <button type="submit">Log out</button>
                </form>
            </div>
        </details>
    </div>
</header>

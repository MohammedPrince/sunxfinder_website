@extends('admin.layouts.app')

@section('title', 'Terms Acceptance')
@section('breadcrumb', 'Terms Acceptance')
@section('eyebrow', 'SYSTEM')
@section('page-title', 'Terms Acceptance')
@section('page-description', 'Which customers accepted the Terms & Conditions in the app, which version, and when.')

@section('page-actions')
    <a href="{{ route('admin.terms-acceptances.export') }}" class="btn btn-light">⤓ Export CSV</a>
    <a href="{{ route('admin.terms-acceptances.index', request()->query()) }}" class="btn btn-light">↻ Refresh</a>
@endsection

@section('content')
    @php
        $platforms = \App\Models\CustomerTermsAcceptance::PLATFORMS;
        $statusFilters = [
            'current' => 'Accepted current Terms',
            'outdated' => 'Accepted an older version',
            'never' => 'Never accepted',
        ];
    @endphp

    @include('partials.page-guide', [
        'key' => 'admin-terms-acceptances',
        'title' => 'How Terms acceptance works',
        'intro' => 'The app shows a summary of the Terms & Conditions on every sign-in. The customer ticks "I have read and agree" before signing in, and each acceptance is saved here.',
        'steps' => [
            '<strong>Version</strong>: the Terms "Last updated" date the customer saw. The current one is set in System → Privacy &amp; Terms.',
            '<strong>Older version</strong>: the customer accepted before the Terms last changed. They accept the new version next time they sign in.',
            '<strong>Record</strong>: time and IP address come from the server, not the phone. Rows are never edited or deleted. Export CSV for the full record.',
        ],
        'note' => 'Customers signing in from app builds older than the Terms screen are not recorded until they update the app.',
    ])

    <div class="metrics-strip">
        <div>
            <small>Current Terms version</small>
            <strong>{{ $currentVersion ? \Illuminate\Support\Carbon::parse($currentVersion)->format('M j, Y') : '—' }}</strong>
        </div>
        <div>
            <small>Accepted current Terms</small>
            <strong>{{ number_format($metrics['current']) }}</strong>
        </div>
        <div>
            <small>Accepted an older version</small>
            <strong class="{{ $metrics['outdated'] ? 'warning' : '' }}">{{ number_format($metrics['outdated']) }}</strong>
        </div>
        <div>
            <small>Never accepted</small>
            <strong>{{ number_format($metrics['never']) }}</strong>
        </div>
        <div>
            <small>Acceptances today</small>
            <strong>{{ number_format($metrics['today']) }}</strong>
        </div>
    </div>

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Customers</h2>
                <p>{{ number_format($metrics['customers']) }} customers. Latest acceptance first.</p>
            </div>

            <form method="GET" action="{{ route('admin.terms-acceptances.index') }}" class="table-filters">
                <select name="status" class="select-control" aria-label="Acceptance">
                    <option value="">All customers</option>
                    @foreach ($statusFilters as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <input class="table-search" type="search" name="search" value="{{ request('search') }}"
                    placeholder="Name, username, phone or email" maxlength="100" aria-label="Search customers">

                <button type="submit" class="btn btn-light">Search</button>

                @if (request()->hasAny(['status', 'search']))
                    <a href="{{ route('admin.terms-acceptances.index') }}" class="btn btn-light">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Terms</th>
                        <th>Accepted</th>
                        <th>Language</th>
                        <th>Device</th>
                        <th>IP address</th>
                        <th>Times</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($customers as $customer)
                        @php
                            $latest = $customer->latestTermsAcceptance;
                            $version = $customer->terms_accepted_version;
                            $name = $customer->full_name ?: $customer->username;
                        @endphp
                        <tr>
                            <td>
                                <div class="person-cell">
                                    <span class="person-avatar">{{ name_initials($name) }}</span>
                                    <span>
                                        <strong>{{ $name }}</strong>
                                        <small>{{ '@' . $customer->username }} · {{ ucfirst($customer->status) }}</small>
                                    </span>
                                </div>
                            </td>

                            <td>
                                @if (!$version)
                                    <span class="pill">Never accepted</span>
                                @elseif ($version === $currentVersion)
                                    <span class="pill pill-green">Current</span>
                                    <small class="cell-note">{{ \Illuminate\Support\Carbon::parse($version)->format('M j, Y') }}</small>
                                @else
                                    <span class="pill pill-orange">Older version</span>
                                    <small class="cell-note">{{ \Illuminate\Support\Carbon::parse($version)->format('M j, Y') }}</small>
                                @endif
                            </td>

                            <td style="white-space:nowrap">
                                @if ($customer->terms_accepted_at)
                                    {{ $customer->terms_accepted_at->format('M d, Y H:i') }}
                                    <small class="cell-note">{{ $customer->terms_accepted_at->diffForHumans() }}</small>
                                @else
                                    <span class="gray-text">—</span>
                                @endif
                            </td>

                            <td>{{ $latest ? ($latest->lang === 'ar' ? 'Arabic' : 'English') : '—' }}</td>

                            <td>
                                @if ($latest)
                                    {{ $platforms[$latest->platform] ?? ($latest->platform ?: '—') }}
                                    @if ($latest->app_version)
                                        <small class="cell-note">App {{ $latest->app_version }}</small>
                                    @endif
                                @else
                                    <span class="gray-text">—</span>
                                @endif
                            </td>

                            <td><code>{{ $latest?->ip_address ?: '—' }}</code></td>

                            <td>{{ $customer->terms_acceptances_count ?: '—' }}</td>

                            <td>
                                @if ($customer->terms_acceptances_count)
                                    <a href="{{ route('admin.terms-acceptances.show', $customer->id) }}" class="row-action">History ↗</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="dashboard-empty">
                                <strong>No customers found.</strong>
                                @if (request()->hasAny(['status', 'search']))
                                    Try changing the search text or filter.
                                @else
                                    Acceptances appear here as customers sign in to the app.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $customers, 'label' => 'customers'])
    </section>
@endsection

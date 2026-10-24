@extends('admin.layouts.app')

@section('title', 'Activity Logs')
@section('breadcrumb', 'Activity Logs')
@section('eyebrow', 'SYSTEM')
@section('page-title', 'Activity Logs')
@section('page-description', 'Everything admins, companies and customers do: sign-ins, changes and deletions. Visible to admins only.')

@section('page-actions')
    <a href="{{ route('admin.logs.export', request()->query()) }}" class="btn btn-light">⤓ Export CSV</a>
    <a href="{{ route('admin.logs.index', request()->query()) }}" class="btn btn-light">↻ Refresh</a>
@endsection

@section('content')
    @php
        $actorTypes = \App\Models\ActivityLog::ACTOR_TYPES;
        $actions = \App\Models\ActivityLog::ACTIONS;
        $categories = \App\Models\ActivityLog::CATEGORIES;
        $actorClasses = ['admin' => 'blue-bg', 'company' => 'orange-bg', 'customer' => 'green-bg', 'guest' => '', 'system' => ''];
        $filterKeys = ['actor_type', 'actor_id', 'action', 'category', 'subject_type', 'subject_id', 'from', 'to', 'search'];
    @endphp

    @include('partials.page-guide', [
        'key' => 'admin-activity-logs',
        'title' => 'How activity logs work',
        'intro' => 'Every sign-in, change and deletion is recorded with who did it, when, and from which IP address.',
        'steps' => [
            '<strong>Actor</strong>: the admin, company or customer who acted. "Guest" means nobody was signed in, e.g. a failed sign-in or a new sign-up.',
            '<strong>Changes</strong>: open a row to see each changed field, old value → new value. Passwords and tokens are never stored.',
            '<strong>History</strong>: click an actor or a record to see everything for that person or record.',
        ],
        'note' => 'Logs are kept for ' . \App\Models\ActivityLog::KEEP_DAYS . ' days, then removed automatically.',
    ])

    {{-- Summary metrics --}}
    <div class="metrics-strip">
        <div>
            <small>Today</small>
            <strong>{{ number_format($metrics['today']) }}</strong>
        </div>
        <div>
            <small>Sign-ins today</small>
            <strong>{{ number_format($metrics['logins_today']) }}</strong>
        </div>
        <div>
            <small>Failed / blocked sign-ins today</small>
            <strong class="{{ $metrics['failed_today'] ? 'warning' : '' }}">{{ number_format($metrics['failed_today']) }}</strong>
        </div>
        <div>
            <small>Deletions today</small>
            <strong>{{ number_format($metrics['deletions_today']) }}</strong>
        </div>
        <div>
            <small>All logs</small>
            <strong>{{ number_format($metrics['total']) }}</strong>
        </div>
    </div>

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar log-toolbar">
            <div>
                <h2>Activity</h2>
                <p>
                    Today:
                    @foreach (['admin', 'company', 'customer', 'guest'] as $type)
                        <a href="{{ route('admin.logs.index', ['actor_type' => $type, 'from' => now()->toDateString()]) }}">{{ $actorTypes[$type] }} {{ $metrics['by_actor'][$type] }}</a>@if (!$loop->last) · @endif
                    @endforeach
                </p>
            </div>

            <form method="GET" action="{{ route('admin.logs.index') }}" class="table-filters log-filters">
                @foreach (['actor_id', 'subject_type', 'subject_id'] as $hidden)
                    @if (request()->filled($hidden))
                        <input type="hidden" name="{{ $hidden }}" value="{{ request($hidden) }}">
                    @endif
                @endforeach

                <select name="actor_type" class="select-control" aria-label="Actor type">
                    <option value="">Everyone</option>
                    @foreach ($actorTypes as $value => $label)
                        <option value="{{ $value }}" @selected(request('actor_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="action" class="select-control" aria-label="Action">
                    <option value="">All actions</option>
                    @foreach ($actions as $value => $label)
                        <option value="{{ $value }}" @selected(request('action') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="category" class="select-control" aria-label="Category">
                    <option value="">All areas</option>
                    @foreach ($categories as $value => $label)
                        <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <span class="log-date-range">
                    <input class="select-control" type="date" name="from" value="{{ request('from') }}"
                        aria-label="From date" max="{{ now()->toDateString() }}">
                    <span aria-hidden="true">→</span>
                    <input class="select-control" type="date" name="to" value="{{ request('to') }}"
                        aria-label="To date" max="{{ now()->toDateString() }}">
                </span>

                <input class="table-search" type="search" name="search" value="{{ request('search') }}"
                    placeholder="Name, record, text or IP" maxlength="100" aria-label="Search logs">

                <button type="submit" class="btn btn-light">Search</button>

                @if (request()->hasAny($filterKeys))
                    <a href="{{ route('admin.logs.index') }}" class="btn btn-light">Clear</a>
                @endif
            </form>
        </div>

        @if (request()->filled('actor_id') || request()->filled('subject_id'))
            <div class="alert alert-success log-scope-note">
                @if (request()->filled('actor_id'))
                    Showing activity by {{ $actorTypes[request('actor_type')] ?? 'actor' }} #{{ request('actor_id') }}.
                @endif
                @if (request()->filled('subject_id'))
                    Showing history of {{ request('subject_type') }} #{{ request('subject_id') }}.
                @endif
            </div>
        @endif

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>What happened</th>
                        <th>Record</th>
                        <th>IP address</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($logs as $log)
                        @php $actorName = $log->actor_name ?: ($actorTypes[$log->actor_type] ?? $log->actor_type); @endphp
                        <tr>
                            <td style="white-space:nowrap">
                                {{ $log->created_at?->format('M d, Y H:i:s') }}
                                <small class="cell-note">{{ $log->created_at?->diffForHumans() }}</small>
                            </td>

                            <td>
                                <div class="company-cell">
                                    <span class="company-avatar {{ $actorClasses[$log->actor_type] ?? '' }}">
                                        {{ strtoupper(mb_substr($actorName, 0, 1)) }}
                                    </span>
                                    <span>
                                        @if ($log->actor_id)
                                            <a href="{{ route('admin.logs.index', ['actor_type' => $log->actor_type, 'actor_id' => $log->actor_id]) }}"
                                                title="All activity by this {{ strtolower($actorTypes[$log->actor_type] ?? '') }}">
                                                <strong>{{ $actorName }}</strong>
                                            </a>
                                        @else
                                            <strong>{{ $actorName }}</strong>
                                        @endif
                                        <small>{{ $actorTypes[$log->actor_type] ?? $log->actor_type }}</small>
                                    </span>
                                </div>
                            </td>

                            <td>
                                <span class="pill {{ $log->actionClass() }}">{{ $actions[$log->action] ?? $log->action }}</span>
                                <small class="cell-note">{{ $categories[$log->category] ?? $log->category }}</small>
                            </td>

                            <td style="max-width:420px">{{ $log->description }}</td>

                            <td>
                                @if ($log->subject_type)
                                    <a href="{{ route('admin.logs.index', ['subject_type' => $log->subject_type, 'subject_id' => $log->subject_id]) }}"
                                        title="Full history of this record">
                                        <strong>{{ $log->subject_type }} #{{ $log->subject_id }}</strong>
                                    </a>
                                    <small class="cell-note">{{ \Illuminate\Support\Str::limit($log->subject_label, 30) }}</small>
                                @else
                                    <span class="gray-text">—</span>
                                @endif
                            </td>

                            <td><code>{{ $log->ip_address ?: '—' }}</code></td>

                            <td><a href="{{ route('admin.logs.show', $log->id) }}" class="row-action">Details ↗</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="dashboard-empty">
                                <strong>No activity found.</strong>
                                @if (request()->hasAny($filterKeys))
                                    Try changing the filters.
                                @else
                                    Activity appears here as admins, companies and customers use the platform.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $logs, 'label' => 'log entries'])
    </section>
@endsection

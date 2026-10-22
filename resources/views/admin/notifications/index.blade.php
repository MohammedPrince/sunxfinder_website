@extends('admin.layouts.app')

@section('title', 'Notifications')
@section('breadcrumb', 'Notifications')
@section('eyebrow', 'CUSTOMER COMMUNICATION')
@section('page-title', 'Notifications')
@section('page-description', 'Create announcements and review notifications sent to SunXFinder customers.')

@section('page-actions')
    <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary">+ Send Notification</a>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible">
            <strong>Error!</strong> {{ session('error') }}
            <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following errors:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="metrics-strip">
        <div><small>Total notifications</small><strong>{{ $notifications->total() }}</strong></div>
        <div><small>Published</small><strong>{{ $publishedCount ?? '—' }}</strong></div>
        <div><small>Drafts</small><strong>{{ $draftCount ?? '—' }}</strong></div>
        <div><small>Total recipients</small><strong>{{ number_format($recipientCount ?? 0) }}</strong></div>
    </div>

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Notification history</h2>
                <p>Review announcements, audiences, and delivery/read statistics.</p>
            </div>
            <form method="GET" action="{{ route('admin.notifications.index') }}" class="table-filters">
                <select class="select-control" name="type" aria-label="Filter by notification type">
                    <option value="">All types</option>
                    @foreach ([
                        'general' => 'General announcement',
                        'quote' => 'Quote update',
                        'promotion' => 'Promotion',
                        'account' => 'Account',
                        'support' => 'Support',
                        'maintenance' => 'Maintenance',
                    ] as $typeValue => $typeLabel)
                        <option value="{{ $typeValue }}" @selected(request('type') === $typeValue)>{{ $typeLabel }}</option>
                    @endforeach
                </select>
                <select class="select-control" name="status" aria-label="Filter by status">
                    <option value="">All statuses</option>
                    <option value="published" @selected(request('status') === 'published')>Published</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                </select>
                <input class="table-search" type="search" name="search" value="{{ request('search') }}"
                    placeholder="Search notifications…" aria-label="Search notifications">
                <button type="submit" class="btn btn-light">Filter</button>
                @if (request()->hasAny(['type', 'status', 'search']))
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-light">Reset</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table js-data-table">
                <thead>
                    <tr><th>Notification</th><th>Type</th><th>Audience</th><th>Recipients</th><th>Status</th><th>Published</th><th>Published by</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($notifications as $notification)
                        @php
                            $statusClass = match ($notification->status) {
                                'published' => 'pill-green',
                                'draft' => 'pill-yellow',
                                default => 'pill-yellow',
                            };
                            $typeLabels = [
                                'general' => 'General',
                                'quote' => 'Quote update',
                                'promotion' => 'Promotion',
                                'account' => 'Account',
                                'support' => 'Support',
                                'maintenance' => 'Maintenance',
                            ];
                        @endphp
                        <tr>
                            <td>
                                <div class="person-cell">
                                    <span class="company-avatar solar">{{ strtoupper(substr($notification->title_en ?? 'N', 0, 1)) }}</span>
                                    <span>
                                        <strong>{{ $notification->title_en }}</strong>
                                        <small>{{ \Illuminate\Support\Str::limit(strip_tags($notification->message_en), 90) }}</small>
                                    </span>
                                </div>
                            </td>
                            <td>{{ $typeLabels[$notification->type] ?? ucfirst($notification->type) }}</td>
                            <td>{{ $notification->audience_type === 'all' ? 'All customers' : 'Selected customers' }}</td>
                            <td>{{ number_format($notification->recipients_count ?? 0) }}</td>
                            <td><span class="pill {{ $statusClass }}" data-status="{{ $notification->status }}">{{ ucfirst($notification->status) }}</span></td>
                            <td>{{ $notification->published_at?->format('M d, Y H:i') ?? '—' }}</td>
                            <td>
                                @if ($notification->sender_type === 'company')
                                    @if ($notification->senderCompany)
                                        <a href="{{ route('admin.companies.edit', $notification->sender_id) }}">
                                            <strong>{{ $notification->senderLabel() }}</strong>
                                        </a>
                                    @else
                                        <strong>{{ $notification->senderLabel() }}</strong>
                                    @endif
                                    <small class="cell-note">Company</small>
                                @else
                                    <strong>{{ $notification->senderLabel() }}</strong>
                                    <small class="cell-note">SunXFinder team</small>
                                @endif
                            </td>
                            <td>
                                <div class="company-actions">
                                    <a href="{{ route('admin.notifications.show', $notification->id) }}"
                                        class="row-action row-action-icon" title="View notification"
                                        aria-label="View notification {{ $notification->title_en }}">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.notifications.destroy', $notification->id) }}" method="POST"
                                        class="company-delete-form"
                                        onsubmit="return confirm('Delete this notification? Customers will no longer see it in the app.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="row-action row-action-icon row-action-delete"
                                            title="Delete notification" aria-label="Delete notification {{ $notification->title_en }}">
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M3 6h18" />
                                                <path d="M8 6V4h8v2" />
                                                <path d="m19 6-1 14H6L5 6" />
                                                <path d="M10 11v5M14 11v5" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">No notifications found.
                            <a href="{{ route('admin.notifications.create') }}">Send your first notification</a>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $notifications, 'label' => 'notifications'])
    </section>
@endsection

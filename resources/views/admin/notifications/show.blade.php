@extends('admin.layouts.app')

@section('title', 'Notification Details')
@section('breadcrumb', 'Notifications / Details')
@section('eyebrow', 'CUSTOMER COMMUNICATION')
@section('page-title', 'Notification Details')
@section('page-description', 'Review notification content, audience, and recipient read status.')

@section('page-actions')
    <a href="{{ route('admin.notifications.index') }}" class="btn btn-primary">← Back to Notifications</a>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
        </div>
    @endif

    <div class="metrics-strip">
        <div><small>Total recipients</small><strong>{{ number_format($notification->recipients_count ?? $notification->recipients()->count()) }}</strong></div>
        <div><small>Read</small><strong>{{ number_format($readCount ?? 0) }}</strong></div>
        <div><small>Unread</small><strong>{{ number_format($unreadCount ?? max(0, ($notification->recipients_count ?? $notification->recipients()->count()) - ($readCount ?? 0))) }}</strong></div>
        <div><small>Published</small><strong>{{ $notification->published_at?->format('M d, Y') ?? '—' }}</strong></div>
    </div>

    <section class="panel">
        <div class="panel-heading"><h2>Notification overview</h2><p>Notification #{{ $notification->id }}</p></div>
        <div class="panel-body">
            <div class="form-grid">
                <div class="form-group"><label>Type</label><div class="form-control">{{ ucfirst(str_replace('_', ' ', $notification->type)) }}</div></div>
                <div class="form-group"><label>Audience</label><div class="form-control">{{ $notification->audience_type === 'all' ? 'All customers' : 'Selected customers' }}</div></div>
                <div class="form-group"><label>Status</label><div><span class="pill {{ $notification->status === 'published' ? 'pill-green' : 'pill-yellow' }}">{{ ucfirst($notification->status) }}</span></div></div>
                <div class="form-group"><label>Created</label><div>{{ $notification->created_at?->format('M d, Y H:i') ?? '—' }}</div></div>
                <div class="form-group"><label>Sender</label><div>{{ ucfirst($notification->sender_type) }}{{ $notification->sender_id ? ' #' . $notification->sender_id : '' }}</div></div>
            </div>
        </div>
    </section>

        <section class="panel">
                <div class="panel-heading"><h2>English content</h2><p>Content shown to English-language customers.</p></div>
                <div class="panel-body">
                    <div class="form-group"><label>Title</label><div><strong>{{ $notification->title_en }}</strong></div></div>
                    <div class="form-group"><label>Message</label><div style="white-space:pre-line">{{ $notification->message_en }}</div></div>
                </div>
        </section>

    <section class="panel">
        <div class="panel-heading"><h2>Arabic content</h2><p>Content shown to Arabic-language customers.</p></div>
        <div class="panel-body" dir="rtl" lang="ar">
            <div class="form-group"><label>العنوان</label><div><strong>{{ $notification->title_ar ?: '—' }}</strong></div></div>
            <div class="form-group"><label>الرسالة</label><div style="white-space:pre-line">{{ $notification->message_ar ?: '—' }}</div></div>
        </div>
    </section>

      @if (isset($recipients))
        <section class="panel table-panel">
            <div class="panel-heading table-toolbar">
                <div>
                    <h2>Recipients</h2>
                    <p>Customer contact details, registered devices, and read status.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="data-table js-data-table">
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Phone</th>
                            <th>FCM Token</th>
                            <th>Language</th>
                            <th>Read Status</th>
                            <th>Read At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recipients as $recipient)
                            @php
                                $customer = $recipient->customer;
                                $devices = $customer?->deviceTokens ?? collect();
                            @endphp

                            @if ($devices->isNotEmpty())
                                @foreach ($devices as $device)
                                    <tr>
                                        <td>{{ $customer?->full_name ?? '-' }}</td>
                                        <td>{{ $customer?->phone ?? '-' }}</td>
                                        <td>
                                            <span title="{{ $device->fcm_token }}">
                                                {{ $device->fcm_token }}
                                            </span>
                                        </td>
                                        <td>{{ strtoupper($device->lang ?? 'en') }}</td>
                                        <td>
                                            <span class="pill {{ $recipient->read_at ? 'pill-green' : 'pill-yellow' }}">
                                                {{ $recipient->read_at ? 'Read' : 'Unread' }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $recipient->read_at?->format('M d, Y H:i') ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td>{{ $customer?->full_name ?? '-' }}</td>
                                    <td>{{ $customer?->phone ?? '-' }}</td>
                                    <td colspan="2">No registered device</td>
                                    <td>
                                        <span class="pill {{ $recipient->read_at ? 'pill-green' : 'pill-yellow' }}">
                                            {{ $recipient->read_at ? 'Read' : 'Unread' }}
                                        </span>
                                    </td>
                                    <td>{{ $recipient->read_at?->format('M d, Y H:i') ?? '—' }}</td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">
                                    No recipients found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (method_exists($recipients, 'links'))
                @include('partials.table-pagination', [
                    'paginator' => $recipients,
                    'label' => 'recipients',
                ])
            @endif
        </section>
    @endif
@endsection

@extends('company.layouts.app')

@section('title', __('company/messages.notifications.details_title'))
@section('breadcrumb', __('company/messages.notifications.crumb_details'))
@section('eyebrow', __('company/messages.notifications.eyebrow'))
@section('page-title', __('company/messages.notifications.details_title'))
@section('page-description', __('company/messages.notifications.show_description'))

@section('page-actions')
    <a href="{{ route('company.notifications.index') }}" class="btn btn-primary">{{ __('company/messages.notifications.back') }}</a>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <strong>{{ __('company/messages.common.success_label') }}</strong> {{ session('success') }}
            <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="{{ __('company/messages.common.close') }}">&times;</button>
        </div>
    @endif

    @include('company.partials.guide', ['page' => 'notification_show'])

    <div class="metrics-strip">
        <div><small>{{ __('company/messages.notifications.total_recipients') }}</small><strong>{{ number_format($notification->recipients_count ?? $notification->recipients()->count()) }}</strong></div>
        <div><small>{{ __('company/messages.notifications.read') }}</small><strong>{{ number_format($readCount ?? 0) }}</strong></div>
        <div><small>{{ __('company/messages.notifications.unread') }}</small><strong>{{ number_format($unreadCount ?? max(0, ($notification->recipients_count ?? $notification->recipients()->count()) - ($readCount ?? 0))) }}</strong></div>
        <div><small>{{ __('company/messages.notifications.published') }}</small><strong>{{ $notification->published_at?->translatedFormat('M d, Y') ?? '—' }}</strong></div>
    </div>

    <section class="panel">
        <div class="panel-heading"><h2>{{ __('company/messages.notifications.overview') }}</h2><p>{{ __('company/messages.notifications.number', ['id' => $notification->id]) }}</p></div>
        <div class="panel-body">
            <div class="form-grid">
                <div class="form-group"><label>{{ __('company/messages.notifications.type') }}</label><div class="form-control">{{ company_label('notifications.show_type_' . $notification->type, ucfirst(str_replace('_', ' ', $notification->type))) }}</div></div>
                <div class="form-group"><label>{{ __('company/messages.notifications.audience') }}</label><div class="form-control">{{ $notification->audience_type === 'all' ? __('company/messages.notifications.all_customers') : __('company/messages.notifications.selected_customers') }}</div></div>
                <div class="form-group"><label>{{ __('company/messages.common.status') }}</label><div><span class="pill {{ $notification->status === 'published' ? 'pill-green' : 'pill-yellow' }}">{{ __('company/messages.notifications.' . ($notification->status === 'draft' ? 'draft' : 'published')) }}</span></div></div>
                <div class="form-group"><label>{{ __('company/messages.notifications.created') }}</label><div>{{ $notification->created_at?->translatedFormat('M d, Y H:i') ?? '—' }}</div></div>
                <div class="form-group"><label>{{ __('company/messages.notifications.sender') }}</label><div>{{ company_label('notifications.sender_' . $notification->sender_type, ucfirst($notification->sender_type)) }}{{ $notification->sender_id ? ' #' . $notification->sender_id : '' }}</div></div>
            </div>
        </div>
    </section>

        <section class="panel">
                <div class="panel-heading"><h2>{{ __('company/messages.notifications.english_content') }}</h2><p>{{ __('company/messages.notifications.content_en_sub') }}</p></div>
                <div class="panel-body">
                    <div class="form-group"><label>{{ __('company/messages.notifications.label_title') }}</label><div><strong>{{ $notification->title_en }}</strong></div></div>
                    <div class="form-group"><label>{{ __('company/messages.notifications.label_message') }}</label><div style="white-space:pre-line">{{ $notification->message_en }}</div></div>
                </div>
        </section>

    <section class="panel">
        <div class="panel-heading"><h2>{{ __('company/messages.notifications.arabic_content') }}</h2><p>{{ __('company/messages.notifications.content_ar_sub') }}</p></div>
        <div class="panel-body" dir="rtl" lang="ar">
            <div class="form-group"><label>العنوان</label><div><strong>{{ $notification->title_ar ?: '—' }}</strong></div></div>
            <div class="form-group"><label>الرسالة</label><div style="white-space:pre-line">{{ $notification->message_ar ?: '—' }}</div></div>
        </div>
    </section>

      @if (isset($recipients))
        <section class="panel table-panel">
            <div class="panel-heading table-toolbar">
                <div>
                    <h2>{{ __('company/messages.notifications.recipients') }}</h2>
                    <p>{{ __('company/messages.notifications.recipients_sub') }}</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="data-table js-data-table">
                    <thead>
                        <tr>
                            <th>{{ __('company/messages.notifications.customer_name') }}</th>
                            <th>{{ __('company/messages.common.phone') }}</th>
                            <th>{{ __('company/messages.notifications.devices') }}</th>
                            <th>{{ __('company/messages.notifications.language') }}</th>
                            <th>{{ __('company/messages.notifications.read_status') }}</th>
                            <th>{{ __('company/messages.notifications.read_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- One row per customer; the read status belongs to the customer, not to each device. --}}
                        @forelse ($recipients as $recipient)
                            @php
                                $customer = $recipient->customer;
                                $devices = $customer?->deviceTokens ?? collect();
                                $activeDevices = $devices->where('is_active', true);
                                $platforms = $devices->pluck('platform')->filter()->map(fn ($p) => ucfirst($p))->unique()->implode(', ');
                                $languages = ($activeDevices->isNotEmpty() ? $activeDevices : $devices)
                                    ->map(fn ($d) => strtoupper($d->lang ?: 'en'))->unique()->implode(', ');
                            @endphp
                            <tr>
                                <td>{{ $customer?->full_name ?? '-' }}</td>
                                <td>{{ $customer?->phone ?? '-' }}</td>
                                @if ($devices->isNotEmpty())
                                    <td>
                                        {{ __('company/messages.notifications.devices_summary', ['active' => $activeDevices->count(), 'total' => $devices->count()]) }}
                                        @if ($platforms)
                                            <small class="cell-note">{{ $platforms }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $languages }}</td>
                                @else
                                    <td colspan="2">{{ __('company/messages.notifications.no_device') }}</td>
                                @endif
                                <td>
                                    <span class="pill {{ $recipient->read_at ? 'pill-green' : 'pill-yellow' }}">
                                        {{ $recipient->read_at ? __('company/messages.notifications.read') : __('company/messages.notifications.unread') }}
                                    </span>
                                </td>
                                <td>{{ $recipient->read_at?->translatedFormat('M d, Y H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">
                                    {{ __('company/messages.notifications.no_recipients') }}
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

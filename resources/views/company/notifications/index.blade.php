@extends('company.layouts.app')

@section('title', __('company/messages.notifications.title'))
@section('breadcrumb', __('company/messages.notifications.title'))
@section('eyebrow', __('company/messages.notifications.eyebrow'))
@section('page-title', __('company/messages.notifications.title'))
@section('page-description', __('company/messages.notifications.page_description'))

@section('page-actions')
    <a href="{{ route('company.notifications.create') }}" class="btn btn-primary">{{ __('company/messages.notifications.send_button') }}</a>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <strong>{{ __('company/messages.common.success_label') }}</strong> {{ session('success') }}
            <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="{{ __('company/messages.common.close') }}">&times;</button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible">
            <strong>{{ __('company/messages.common.error_label') }}</strong> {{ session('error') }}
            <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="{{ __('company/messages.common.close') }}">&times;</button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>{{ __('company/messages.common.fix_errors') }}</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="metrics-strip">
        <div><small>{{ __('company/messages.notifications.total') }}</small><strong>{{ $notifications->total() }}</strong></div>
        <div><small>{{ __('company/messages.notifications.published') }}</small><strong>{{ $publishedCount ?? '—' }}</strong></div>
        <div><small>{{ __('company/messages.notifications.drafts') }}</small><strong>{{ $draftCount ?? '—' }}</strong></div>
        <div><small>{{ __('company/messages.notifications.total_recipients') }}</small><strong>{{ number_format($recipientCount ?? 0) }}</strong></div>
    </div>

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>{{ __('company/messages.notifications.history') }}</h2>
                <p>{{ __('company/messages.notifications.history_sub') }}</p>
            </div>
            <form method="GET" action="{{ route('company.notifications.index') }}" class="table-filters">
                <select class="select-control" name="type" aria-label="{{ __('company/messages.notifications.filter_type') }}">
                    <option value="">{{ __('company/messages.notifications.all_types') }}</option>
                    @foreach ([
                        'general' => __('company/messages.notifications.type_general_announcement'),
                        'quote' => __('company/messages.notifications.type_quote'),
                        'promotion' => __('company/messages.notifications.type_promotion'),
                        'account' => __('company/messages.notifications.type_account'),
                        'support' => __('company/messages.notifications.type_support'),
                        'maintenance' => __('company/messages.notifications.type_maintenance'),
                    ] as $typeValue => $typeLabel)
                        <option value="{{ $typeValue }}" @selected(request('type') === $typeValue)>{{ $typeLabel }}</option>
                    @endforeach
                </select>
                <select class="select-control" name="status" aria-label="{{ __('company/messages.notifications.filter_status') }}">
                    <option value="">{{ __('company/messages.notifications.all_statuses') }}</option>
                    <option value="published" @selected(request('status') === 'published')>{{ __('company/messages.notifications.published') }}</option>
                    <option value="draft" @selected(request('status') === 'draft')>{{ __('company/messages.notifications.draft') }}</option>
                </select>
                <input class="table-search" type="search" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('company/messages.notifications.search_placeholder') }}" aria-label="{{ __('company/messages.notifications.search_label') }}">
                <button type="submit" class="btn btn-light">{{ __('company/messages.notifications.filter') }}</button>
                @if (request()->hasAny(['type', 'status', 'search']))
                    <a href="{{ route('company.notifications.index') }}" class="btn btn-light">{{ __('company/messages.notifications.reset') }}</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table js-data-table">
                <thead>
                    <tr><th>{{ __('company/messages.notifications.notification') }}</th><th>{{ __('company/messages.notifications.type') }}</th><th>{{ __('company/messages.notifications.audience') }}</th><th>{{ __('company/messages.notifications.recipients') }}</th><th>{{ __('company/messages.common.status') }}</th><th>{{ __('company/messages.notifications.published') }}</th><th>{{ __('company/messages.common.actions') }}</th></tr>
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
                                'general' => __('company/messages.notifications.type_general'),
                                'quote' => __('company/messages.notifications.type_quote'),
                                'promotion' => __('company/messages.notifications.type_promotion'),
                                'account' => __('company/messages.notifications.type_account'),
                                'support' => __('company/messages.notifications.type_support'),
                                'maintenance' => __('company/messages.notifications.type_maintenance'),
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
                            <td>{{ $notification->audience_type === 'all' ? __('company/messages.notifications.all_customers') : __('company/messages.notifications.selected_customers') }}</td>
                            <td>{{ number_format($notification->recipients_count ?? 0) }}</td>
                            <td><span class="pill {{ $statusClass }}" data-status="{{ $notification->status }}">{{ __('company/messages.notifications.' . ($notification->status === 'draft' ? 'draft' : 'published')) }}</span></td>
                            <td>{{ $notification->published_at?->translatedFormat('M d, Y H:i') ?? '—' }}</td>
                            <td>
                                <div class="company-actions">
                                    <a href="{{ route('company.notifications.show', $notification->id) }}"
                                        class="row-action row-action-icon" title="{{ __('company/messages.notifications.view') }}"
                                        aria-label="{{ __('company/messages.notifications.view_label', ['title' => $notification->title_en]) }}">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('company.notifications.destroy', $notification->id) }}" method="POST"
                                        class="company-delete-form"
                                        onsubmit="return confirm(@js(__('company/messages.notifications.delete_confirm')));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="row-action row-action-icon row-action-delete"
                                            title="{{ __('company/messages.notifications.delete') }}" aria-label="{{ __('company/messages.notifications.delete_label', ['title' => $notification->title_en]) }}">
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
                        <tr><td colspan="7" class="text-center">{{ __('company/messages.notifications.none_found') }}
                            <a href="{{ route('company.notifications.create') }}">{{ __('company/messages.notifications.send_first') }}</a>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $notifications, 'label' => 'notifications'])
    </section>
@endsection

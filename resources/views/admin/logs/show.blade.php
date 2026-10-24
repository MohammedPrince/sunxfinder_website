@extends('admin.layouts.app')

@section('title', 'Activity Log Details')
@section('breadcrumb', 'Activity Logs / Details')
@section('eyebrow', 'SYSTEM')
@section('page-title', 'Log entry #' . $log->id)
@section('page-description', $log->description)

@section('page-actions')
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.logs.index') }}" class="btn btn-light">
        &larr; Back to logs
    </a>
@endsection

@section('content')
    @php
        $actorTypes = \App\Models\ActivityLog::ACTOR_TYPES;
        $properties = $log->properties ?? [];
        $changes = $properties['changes'] ?? [];
        $attributes = $properties['attributes'] ?? [];
        $extra = collect($properties)->except(['changes', 'attributes'])->all();

        $show = function ($value) {
            return match (true) {
                $value === null || $value === '' => '—',
                is_bool($value) => $value ? 'Yes' : 'No',
                is_array($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
                default => (string) $value,
            };
        };

        // Open the record in the panel when there is a page for it.
        $recordUrl = match ($log->subject_type) {
            'Customer' => route('admin.customers.edit', $log->subject_id),
            'SolarCompany' => route('admin.companies.edit', $log->subject_id),
            'Quote' => route('admin.quote-requests.show', $log->subject_id),
            'Rating' => route('admin.ratings.show', $log->subject_id),
            'RatingQuestion' => route('admin.rating-questions.edit', $log->subject_id),
            'AppVersion' => route('admin.app-versions.edit', $log->subject_id),
            'Notification' => route('admin.notifications.show', $log->subject_id),
            'Product' => route('admin.products.edit', $log->subject_id),
            'Supplier' => route('admin.suppliers.edit', $log->subject_id),
            default => null,
        };
    @endphp

    <section class="quote-summary">
        <div class="quote-summary-item">
            <span>When</span>
            <strong>{{ $log->created_at?->format('M d, Y H:i:s') }}</strong>
        </div>
        <div class="quote-summary-item">
            <span>Actor</span>
            <strong>
                @if ($log->actor_id)
                    <a href="{{ route('admin.logs.index', ['actor_type' => $log->actor_type, 'actor_id' => $log->actor_id]) }}">
                        {{ $log->actor_name ?: '#' . $log->actor_id }}
                    </a>
                @else
                    {{ $log->actor_name ?: '—' }}
                @endif
                <small class="cell-note">{{ $actorTypes[$log->actor_type] ?? $log->actor_type }}{{ $log->actor_id ? ' #' . $log->actor_id : '' }}</small>
            </strong>
        </div>
        <div class="quote-summary-item">
            <span>Action</span>
            <strong>
                <span class="pill {{ $log->actionClass() }}">{{ \App\Models\ActivityLog::ACTIONS[$log->action] ?? $log->action }}</span>
                <small class="cell-note">{{ \App\Models\ActivityLog::CATEGORIES[$log->category] ?? $log->category }}</small>
            </strong>
        </div>
        <div class="quote-summary-item">
            <span>Record</span>
            <strong>
                @if ($log->subject_type)
                    @if ($recordUrl)
                        <a href="{{ $recordUrl }}">{{ $log->subject_type }} #{{ $log->subject_id }}</a>
                    @else
                        {{ $log->subject_type }} #{{ $log->subject_id }}
                    @endif
                    <small class="cell-note">{{ $log->subject_label }}</small>
                @else
                    —
                @endif
            </strong>
        </div>
    </section>

    @if ($changes)
        <section class="panel table-panel">
            <div class="panel-heading">
                <div>
                    <h2>Changes</h2>
                    <p>Each field that changed, before and after.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Before</th>
                            <th>After</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($changes as $field => $change)
                            <tr>
                                <td><strong>{{ $field }}</strong></td>
                                <td style="color:#c0392b;white-space:pre-line">{{ $show($change['old'] ?? null) }}</td>
                                <td style="color:#1c9b69;white-space:pre-line">{{ $show($change['new'] ?? null) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($attributes)
        <section class="panel table-panel">
            <div class="panel-heading">
                <div>
                    <h2>Saved values</h2>
                    <p>What the record contained when it was created.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <tbody>
                        @foreach ($attributes as $field => $value)
                            <tr>
                                <td style="width:220px"><strong>{{ $field }}</strong></td>
                                <td style="white-space:pre-line">{{ $show($value) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($extra)
        <section class="panel table-panel">
            <div class="panel-heading">
                <div>
                    <h2>Details</h2>
                </div>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <tbody>
                        @foreach ($extra as $key => $value)
                            <tr>
                                <td style="width:220px"><strong>{{ \Illuminate\Support\Str::headline($key) }}</strong></td>
                                <td>{{ $show($value) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="panel table-panel">
        <div class="panel-heading">
            <div>
                <h2>Request</h2>
                <p>Where the action came from.</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <tbody>
                    <tr>
                        <td style="width:220px"><strong>IP address</strong></td>
                        <td>
                            @if ($log->ip_address)
                                <a href="{{ route('admin.logs.index', ['search' => $log->ip_address]) }}">{{ $log->ip_address }}</a>
                                <small class="cell-note">All activity from this IP</small>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Request</strong></td>
                        <td style="word-break:break-all">{{ $log->method }} {{ $log->url ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Device / browser</strong></td>
                        <td style="word-break:break-all">{{ $log->user_agent ?: '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
@endsection

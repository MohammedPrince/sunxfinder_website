@extends('admin.layouts.app')

@section('title', 'App Slider')
@section('breadcrumb', 'App Slider')
@section('eyebrow', 'APP MANAGEMENT')
@section('page-title', 'App Slider')
@section('page-description', 'Ads and announcements shown in the slider at the top of the app Home screen.')

@section('page-actions')
    <a href="{{ route('admin.app-sliders.create') }}" class="btn btn-primary">+ Add slide</a>
@endsection

@section('content')
    @php
        $stateLabels = ['live' => 'Live', 'scheduled' => 'Scheduled', 'expired' => 'Expired', 'inactive' => 'Hidden'];
        $stateClasses = ['live' => 'pill-green', 'scheduled' => 'pill-blue', 'expired' => 'pill-yellow', 'inactive' => 'pill-red'];
    @endphp

    @include('partials.page-guide', [
        'key' => 'admin-app-sliders',
        'title' => 'How the app slider works',
        'intro' => 'Slides appear at the top of the Home screen in the customer app and rotate automatically, lowest order number first.',
        'steps' => [
            '<strong>Image</strong>: upload a wide banner (about 1280 × 640 px works well). It is converted to a light WebP so the app loads it quickly.',
            '<strong>Timing</strong>: "Display time" is how many seconds the slide stays before the next one.',
            '<strong>Link</strong>: optionally open a website (https://…) or an in-app page (sunxfinder://company/{slug}) when tapped.',
            '<strong>Schedule</strong>: a slide is "Live" only while it is active and inside its start/end dates. The app shows up to 10 live slides.',
        ],
        'note' => 'Changes reach the app within a minute. Deleted slides are kept in Archives and can be recovered.',
    ])

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="metrics-strip">
        <div><small>All slides</small><strong>{{ number_format($metrics['total']) }}</strong></div>
        <div><small>Live in the app</small><strong>{{ number_format($metrics['live']) }}</strong></div>
        <div><small>Scheduled</small><strong>{{ number_format($metrics['scheduled']) }}</strong></div>
        <div><small>Hidden</small><strong>{{ number_format($metrics['inactive']) }}</strong></div>
    </div>

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar log-toolbar">
            <div>
                <h2>Slides</h2>
                <p>Ordered as they appear in the app.</p>
            </div>

            <form method="GET" action="{{ route('admin.app-sliders.index') }}" class="table-filters log-filters">
                <input class="table-search" type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                    placeholder="Name or description" maxlength="100" aria-label="Search slides">
                <select class="select-control" name="state" aria-label="Filter by status">
                    <option value="">All statuses</option>
                    @foreach ($stateLabels as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['state'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-light" type="submit">Filter</button>
                @if (request()->hasAny(['search', 'state']))
                    <a class="btn btn-light" href="{{ route('admin.app-sliders.index') }}">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Slide</th>
                        <th>Link</th>
                        <th>Display time</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sliders as $slider)
                        @php $state = $slider->displayState(); @endphp
                        <tr>
                            <td><strong>{{ $slider->sort_order }}</strong></td>
                            <td>
                                <div class="slider-cell">
                                    <img class="slider-thumb" src="{{ $slider->imageUrl() }}" alt="{{ $slider->name }}" loading="lazy">
                                    <span>
                                        <a href="{{ route('admin.app-sliders.edit', $slider->id) }}"><strong>{{ $slider->name }}</strong></a>
                                        <small class="cell-note">{{ \Illuminate\Support\Str::limit($slider->description_en ?: $slider->description_ar ?: 'No description', 60) }}</small>
                                    </span>
                                </div>
                            </td>
                            <td class="cell-wrap">
                                @if ($slider->link_url)
                                    <span class="slider-link" title="{{ $slider->link_url }}">{{ \Illuminate\Support\Str::limit($slider->link_url, 40) }}</span>
                                @else
                                    <span class="cell-note">Not tappable</span>
                                @endif
                            </td>
                            <td>{{ $slider->interval_seconds }} sec</td>
                            <td style="white-space:nowrap">
                                {{ $slider->starts_at?->format('M j, Y H:i') ?? 'Immediately' }}
                                <small class="cell-note">until {{ $slider->ends_at?->format('M j, Y H:i') ?? 'no end date' }}</small>
                            </td>
                            <td><span class="pill {{ $stateClasses[$state] }}">{{ $stateLabels[$state] }}</span></td>
                            <td>
                                <div class="company-actions">
                                    <a href="{{ route('admin.app-sliders.edit', $slider->id) }}" class="row-action row-action-icon"
                                        aria-label="Edit {{ $slider->name }}" title="Edit">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 20h9" />
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" />
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.app-sliders.toggle', $slider->id) }}" method="POST" class="company-delete-form">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="row-action row-action-icon"
                                            title="{{ $slider->is_active ? 'Hide from the app' : 'Show in the app' }}"
                                            aria-label="{{ $slider->is_active ? 'Hide' : 'Show' }} {{ $slider->name }}">
                                            @if ($slider->is_active)
                                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                                                    <circle cx="12" cy="12" r="2.5" />
                                                </svg>
                                            @else
                                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M10.6 6.1A10 10 0 0 1 12 6c6 0 9.5 6 9.5 6a17 17 0 0 1-2.4 3.1M6.6 6.6C3.9 8.3 2.5 12 2.5 12s3.5 6 9.5 6a9.7 9.7 0 0 0 5.4-1.6" />
                                                    <path d="m3 3 18 18" />
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.app-sliders.destroy', $slider->id) }}" method="POST" class="company-delete-form"
                                        onsubmit="return confirm('Delete this slide? It will be removed from the app and kept in Archives.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="row-action row-action-icon row-action-delete" aria-label="Delete {{ $slider->name }}" title="Delete">
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
                        <tr>
                            <td colspan="7" class="dashboard-empty">
                                <strong>No slides found.</strong>
                                @if (request()->hasAny(['search', 'state']))
                                    Try adjusting or clearing the filters.
                                @else
                                    <a href="{{ route('admin.app-sliders.create') }}">Add the first slide</a> to show it on the app Home screen.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $sliders, 'label' => 'slides'])
    </section>
@endsection

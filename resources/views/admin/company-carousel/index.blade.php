@extends('admin.layouts.app')
@section('title', 'Company Carousel')
@section('breadcrumb', 'Company Carousel')
@section('eyebrow', 'HOMEPAGE MANAGEMENT')
@section('page-title', 'Company Carousel')
@section('page-description', 'Choose which companies appear in the app and website carousel, and manage their order and display duration.')
@section('page-actions')
    <a href="{{ route('admin.company-carousel.create') }}" class="btn btn-primary">+ Add Company</a>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Carousel entries</h2>
                <p>{{ $entries->total() }} {{ \Illuminate\Support\Str::plural('entry', $entries->total()) }} configured</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Company</th>
                        <th>Platform</th>
                        <th>Display time</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td><span class="pill pill-warm">{{ $entry->sort_order }}</span></td>
                            <td>
                                <strong>{{ $entry->company?->name ?? 'Company #' . $entry->company_id }}</strong>
                                <small class="d-block text-muted">ID: {{ $entry->company_id }}</small>
                            </td>
                            <td>
                                @php($platformLabels = ['app' => 'Mobile app', 'website' => 'Website', 'both' => 'App + website'])
                                <span class="pill {{ $entry->platform === 'website' ? 'pill-blue' : ($entry->platform === 'both' ? 'pill-green' : 'pill-yellow') }}">
                                    {{ $platformLabels[$entry->platform] ?? $entry->platform }}
                                </span>
                            </td>
                            <td><strong>{{ $entry->interval_seconds }} sec</strong></td>
                            <td>
                                <span class="d-block">From: {{ $entry->starts_at?->format('Y-m-d H:i') ?? 'Immediately' }}</span>
                                <span class="d-block">Until: {{ $entry->ends_at?->format('Y-m-d H:i') ?? 'No end date' }}</span>
                            </td>
                            <td>
                                <span class="pill {{ $entry->is_active ? 'pill-green' : 'pill-red' }}">
                                    {{ $entry->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="company-actions">
                                    <a href="{{ route('admin.company-carousel.edit', $entry->id) }}"
                                       class="row-action row-action-icon" aria-label="Edit carousel entry" title="Edit">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 20h9" />
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" />
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.company-carousel.destroy', $entry->id) }}" method="POST"
                                          class="company-delete-form" onsubmit="return confirm('Delete this carousel entry?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="row-action row-action-icon row-action-delete" aria-label="Delete carousel entry" title="Delete">
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
                            <td colspan="7" class="text-center">No carousel entries found. Add a company to feature it here.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-bottom">
            <span class="table-info">
                Showing {{ $entries->firstItem() ?? 0 }}–{{ $entries->lastItem() ?? 0 }} of {{ $entries->total() }}
            </span>
            <div class="pagination-wrapper">{{ $entries->links() }}</div>
        </div>
    </section>
@endsection

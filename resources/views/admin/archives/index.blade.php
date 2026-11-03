@extends('admin.layouts.app')

@section('title', 'Archives')
@section('breadcrumb', 'Archives')
@section('eyebrow', 'SYSTEM')
@section('page-title', 'Archives')
@section('page-description', 'Records captured when customers, companies or admins delete data in the website or app.')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @php
        $filterKeys = ['type', 'source', 'customer_id', 'from', 'to', 'search'];
    @endphp

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar log-toolbar">
            <div>
                <h2>Archived records</h2>
                <p>Newest first. Deleting a row here soft deletes the archive entry.</p>
            </div>

            <form method="GET" action="{{ route('admin.archives.index') }}" class="table-filters log-filters">
                <select name="type" class="select-control" aria-label="Record type">
                    <option value="">All types</option>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="source" class="select-control" aria-label="Source">
                    <option value="">Website and app</option>
                    <option value="website" @selected(($filters['source'] ?? '') === 'website')>Website</option>
                    <option value="app" @selected(($filters['source'] ?? '') === 'app')>App</option>
                </select>

                <input class="select-control" type="number" min="1" name="customer_id"
                    value="{{ $filters['customer_id'] ?? '' }}" placeholder="Customer ID" aria-label="Customer ID">

                <span class="log-date-range">
                    <input class="select-control" type="date" name="from" value="{{ $filters['from'] ?? '' }}" aria-label="From date">
                    <span aria-hidden="true">→</span>
                    <input class="select-control" type="date" name="to" value="{{ $filters['to'] ?? '' }}" aria-label="To date">
                </span>

                <input class="table-search" type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                    placeholder="Type, item ID or customer ID" maxlength="100" aria-label="Search archives">

                <button type="submit" class="btn btn-light">Search</button>
                @if (request()->hasAny($filterKeys))
                    <a href="{{ route('admin.archives.index') }}" class="btn btn-light">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Type</th>
                        <th>Item</th>
                        <th>Customer</th>
                        <th>Source</th>
                        <th>Archived data</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($archives as $archive)
                        <tr>
                            <td style="white-space:nowrap">
                                {{ $archive->created_at?->format('M d, Y H:i:s') }}
                                <small class="cell-note">{{ $archive->created_at?->diffForHumans() }}</small>
                            </td>
                            <td><span class="pill pill-blue">{{ $types[$archive->type] ?? str($archive->type)->replace('_', ' ')->title() }}</span></td>
                            <td>{{ $archive->item_id ? '#' . $archive->item_id : '—' }}</td>
                            <td>
                                @if ($archive->customer_id)
                                    <strong>{{ $archive->customer?->full_name ?: $archive->customer?->username ?: 'Customer' }}</strong>
                                    <small class="cell-note">#{{ $archive->customer_id }}</small>
                                @else
                                    —
                                @endif
                            </td>
                            <td><span class="pill {{ $archive->source === 'app' ? 'pill-green' : 'pill-orange' }}">{{ ucfirst($archive->source) }}</span></td>
                            <td style="max-width:360px">
                                @if ($archive->data)
                                    <details>
                                        <summary style="cursor:pointer">View snapshot</summary>
                                        <pre style="white-space:pre-wrap;overflow-wrap:anywhere;max-width:420px;max-height:240px;overflow:auto;margin:8px 0 0;font-size:12px">{{ json_encode($archive->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @else
                                    <span class="gray-text">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="company-actions">
                                    <form method="POST" action="{{ route('admin.archives.destroy', array_merge(['archivedItem' => $archive->id], request()->query())) }}"
                                        onsubmit="return confirm('Move this archive entry to trash?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="row-action row-action-icon row-action-delete"
                                            title="Delete archive entry" aria-label="Delete archive entry #{{ $archive->id }}">
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
                        <tr>
                            <td colspan="7" class="dashboard-empty">
                                <strong>No archived records found.</strong>
                                @if (request()->hasAny($filterKeys))
                                    Try changing the filters.
                                @else
                                    Website and app deletion snapshots will appear here.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $archives, 'label' => 'archive entries'])
    </section>
@endsection

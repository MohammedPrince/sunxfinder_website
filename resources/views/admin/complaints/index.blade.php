@extends('admin.layouts.app')

@section('title', 'Customer complaints')
@section('breadcrumb', 'Customer complaints')
@section('eyebrow', 'CUSTOMER SUPPORT')
@section('page-title', 'Customer complaints')
@section('page-description', 'Review customer issues, follow up with companies, and send a response to the customer.')

@section('content')
    @php
        $statuses = ['pending' => 'Pending', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'rejected' => 'Rejected'];
        $statusClasses = ['pending' => 'pill-orange', 'in_progress' => 'pill-blue', 'resolved' => 'pill-green', 'rejected' => 'pill-red'];
        $categories = [
            'product_issue' => 'Product issue', 'service_issue' => 'Service issue', 'pricing_issue' => 'Pricing issue',
            'delivery_issue' => 'Delivery issue', 'customer_service' => 'Customer service', 'other' => 'Other',
        ];
        $filterKeys = ['search', 'status', 'category'];
    @endphp

    @include('partials.page-guide', [
        'key' => 'admin-complaints',
        'title' => 'How complaints work',
        'intro' => 'Customers can report a problem with a company from the app, with a category, a description and optional photos.',
        'steps' => [
            '<strong>Pending</strong>: a new complaint that nobody has reviewed yet.',
            '<strong>In progress</strong>: an admin is looking into it, often after emailing the company.',
            '<strong>Resolved</strong>: the issue was fixed and the customer was informed.',
            '<strong>Rejected</strong>: the complaint was reviewed and not accepted, ideally with a reply explaining why.',
        ],
        'note' => 'Work through "Pending" complaints first. Open a complaint to reply to the customer or notify the company.',
    ])

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar log-toolbar">
            <div>
                <h2>Complaint queue</h2>
                <p>{{ number_format($complaints->total()) }} {{ \Illuminate\Support\Str::plural('report', $complaints->total()) }}, newest first.</p>
            </div>

            <form method="GET" action="{{ route('admin.complaints.index') }}" class="table-filters log-filters">
                <input class="table-search" type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                    placeholder="Company, customer or complaint" maxlength="100" aria-label="Search complaints">

                <select class="select-control" name="status" aria-label="Filter by status">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select class="select-control" name="category" aria-label="Filter by category">
                    <option value="">All categories</option>
                    @foreach ($categories as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <button class="btn btn-light" type="submit">Filter</button>
                @if (request()->hasAny($filterKeys))
                    <a class="btn btn-light" href="{{ route('admin.complaints.index') }}">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Complaint</th>
                        <th>Company</th>
                        <th>Customer</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Received</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($complaints as $complaint)
                        @php
                            $customerName = $complaint->customer?->full_name ?: $complaint->customer?->username;
                            $photoCount = count($complaint->photos ?? []);
                        @endphp
                        <tr>
                            <td class="cell-wrap">
                                <a href="{{ route('admin.complaints.show', $complaint->id) }}"><strong>Complaint #{{ $complaint->id }}</strong></a>
                                <small class="cell-note">
                                    {{ \Illuminate\Support\Str::limit($complaint->description, 80) }}
                                    @if ($photoCount) · {{ $photoCount }} {{ \Illuminate\Support\Str::plural('photo', $photoCount) }}@endif
                                </small>
                            </td>
                            <td>{{ $complaint->company?->name ?? 'Company removed' }}</td>
                            <td>
                                <strong>{{ $customerName ?: 'Customer removed' }}</strong>
                                @if ($complaint->customer?->email)
                                    <small class="cell-note">{{ $complaint->customer->email }}</small>
                                @endif
                            </td>
                            <td><span class="complaint-category">{{ $categories[$complaint->category] ?? str($complaint->category)->replace('_', ' ')->title() }}</span></td>
                            <td>
                                <span class="pill {{ $statusClasses[$complaint->status] ?? 'pill-blue' }}">
                                    {{ $statuses[$complaint->status] ?? str($complaint->status)->replace('_', ' ')->title() }}
                                </span>
                                @if ($complaint->customer_replied_at)
                                    <small class="cell-note">Reply sent {{ $complaint->customer_replied_at->diffForHumans() }}</small>
                                @endif
                            </td>
                            <td style="white-space:nowrap">
                                {{ $complaint->created_at?->format('M j, Y H:i') }}
                                <small class="cell-note">{{ $complaint->created_at?->diffForHumans() }}</small>
                            </td>
                            <td>
                                <a href="{{ route('admin.complaints.show', $complaint->id) }}" class="row-action row-action-icon"
                                    title="View complaint" aria-label="View complaint #{{ $complaint->id }}">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                                        <circle cx="12" cy="12" r="2.5" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="dashboard-empty">
                                <strong>No complaints found.</strong>
                                @if (request()->hasAny($filterKeys)) Try adjusting or clearing the filters. @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $complaints, 'label' => 'complaints'])
    </section>
@endsection

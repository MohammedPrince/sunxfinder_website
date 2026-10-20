@extends('admin.layouts.app')

@section('title', 'Quote requests')
@section('breadcrumb', 'Quote requests')
@section('eyebrow', 'CUSTOMER ENQUIRIES')
@section('page-title', 'Quote requests')
@section('page-description', 'Track customer enquiries and help them connect with solar providers.')

@section('page-actions')
    <a href="{{ route('admin.quote-requests.index', request()->query()) }}" class="btn btn-light">
        ↻ Refresh
    </a>
@endsection

@section('content')
    @php
        $statusLabels = [
            'pending' => 'Pending',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        $statusClasses = [
            'pending' => 'pill-yellow',
            'accepted' => 'pill-orange',
            'rejected' => 'pill-red',
            'completed' => 'pill-green',
            'cancelled' => 'pill-purple',
        ];
    @endphp

    {{-- Summary metrics --}}
    <div class="metrics-strip">
        <div>
            <small>Total requests</small>
            <strong>{{ number_format($metrics['total']) }}</strong>
        </div>

        <div>
            <small>Pending</small>
            <strong>{{ number_format($metrics['pending']) }}</strong>
        </div>

        <div>
            <small>Accepted</small>
            <strong>{{ number_format($metrics['in_progress']) }}</strong>
        </div>

        <div>
            <small>Completed</small>
            <strong>{{ number_format($metrics['completed']) }}</strong>
        </div>
    </div>

    {{-- Quote requests table --}}
    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Customer enquiries</h2>
                <p>Latest solar quote requests from the database</p>
            </div>

            <form method="GET" action="{{ route('admin.quote-requests.index') }}" class="table-filters">
                <select name="status" class="select-control">
                    <option value="">All statuses</option>

                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <input class="table-search" type="search" name="search" value="{{ request('search') }}"
                    placeholder="Search requests..." maxlength="100">

                <button type="submit" class="btn btn-light">
                    Search
                </button>

                @if (request()->hasAny(['status', 'search']))
                    <a href="{{ route('admin.quote-requests.index') }}" class="btn btn-light">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Customer</th>
                        <th>Location</th>
                        <th>Service</th>
                        <th>Load estimate</th>
                        <th>Received</th>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($quotes as $quote)
                        @php
                            $customer = $quote->customer;
                            $company = $quote->company;

                            $customerName = $customer?->name
                                ?? $customer?->full_name
                                ?? $customer?->customer_name
                                ?? 'Customer #' . $quote->customer_id;

                            $customerEmail = $customer?->email ?? 'No email';
                            $customerCity = $customer?->city ?? '—';

                            $status = $quote->status;
                            $statusLabel = $statusLabels[$status] ?? ucfirst($status);
                            $statusClass = $statusClasses[$status] ?? 'pill-blue';

                            $summary = $quote->estimated_load ?? '';
                            $systemType = '—';

                            if (
                                preg_match(
                                    '/System type:\s*([^\r\n]+)/i',
                                    $summary,
                                    $matches
                                )
                            ) {
                                $systemType = trim($matches[1]);
                            }
                        @endphp

                        <tr>
                            {{-- Request reference --}}
                            <td>
                                <strong>
                                    #Q-{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }}
                                </strong>

                                <small style="display:block;color:var(--text-muted, #73818c);margin-top:4px">
                                    {{ $company ? 'Company quote' : 'Open quote' }}
                                </small>
                            </td>

                            {{-- Customer --}}
                            <td>
                                <div class="person-cell">
                                    <span class="person-avatar">
                                        {{ strtoupper(substr($customerName, 0, 1)) }}
                                    </span>

                                    <span>
                                        <strong>{{ $customerName }}</strong>
                                        <small>{{ $customerEmail }}</small>
                                    </span>
                                </div>
                            </td>

                            {{-- Customer location --}}
                            <td>
                                {{ $customerCity }}
                            </td>

                            {{-- Service and system type --}}
                            <td>
                                <strong>{{ $quote->service_needed }}</strong>

                                <small style="display:block;margin-top:4px;color:var(--text-muted, #73818c)">
                                    {{ $systemType }}
                                </small>
                            </td>

                            {{-- Energy estimates --}}
                            <td>
                                <strong>
                                    {{ $quote->daily_load ?: '—' }}
                                </strong>

                                <small style="display:block;margin-top:4px">
                                    Peak: {{ $quote->peak_load ?: '—' }}
                                </small>
                            </td>

                            {{-- Received --}}
                            <td>
                                {{ $quote->created_at?->format('M d, Y') ?? '—' }}

                                <small style="display:block;margin-top:4px">
                                    {{ $quote->created_at?->format('H:i') }}
                                </small>
                            </td>

                            {{-- Assigned company --}}
                            <td>
                                @if ($company)
                                    <strong>{{ $company->name }}</strong>
                                @else
                                    <span class="pill pill-orange">
                                        Waiting
                                    </span>
                                @endif
                            </td>

                            {{-- Quote status --}}
                            <td>
                                <span class="pill {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            {{-- Open dedicated details page --}}
                            <td>
                                <a href="{{ route('admin.quote-requests.show', $quote->id) }}" class="row-action">
                                    View ↗
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center;padding:40px 16px">
                                <strong>No quote requests found.</strong>

                                <p>
                                    Try changing the search text or status filter.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="table-bottom">
            <span class="table-info">
                Showing {{ $quotes->firstItem() ?? 0 }}
                to {{ $quotes->lastItem() ?? 0 }}
                of {{ $quotes->total() }} requests
            </span>

            <div class="pagination-wrapper">
                {{ $quotes->links() }}
            </div>
        </div>
    </section>
@endsection
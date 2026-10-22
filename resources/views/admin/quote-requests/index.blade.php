@extends('admin.layouts.app')

@section('title', 'Quote requests')
@section('breadcrumb', 'Quote requests')
@section('eyebrow', 'CUSTOMER ENQUIRIES')
@section('page-title', 'Quote requests')
@section('page-description', 'Every solar quote request customers sent from the app, and how far each one has gone.')

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
    @endphp

    @include('partials.page-guide', [
        'key' => 'admin-quote-requests',
        'title' => 'How quote requests work',
        'intro' => 'Customers describe the solar system they need in the app. Companies then compete with price offers, and the customer chooses one.',
        'steps' => [
            '<strong>Request sent</strong>: the customer submits their energy needs. It is either open to all companies or sent to one company.',
            '<strong>Offers</strong>: approved companies send a total price. The customer compares them in the app.',
            '<strong>Offer accepted</strong>: the customer picks one offer. A project is created for that company.',
            '<strong>Installation → Completed</strong>: the company updates progress until handover to the customer.',
        ],
        'note' => 'Admins can follow every request here. Watch "Waiting for offers": those customers have not heard from any company yet.',
    ])

    {{-- Summary metrics --}}
    <div class="metrics-strip">
        <div>
            <small>Total requests</small>
            <strong>{{ number_format($metrics['total']) }}</strong>
        </div>

        <div>
            <small>Waiting for offers</small>
            <strong class="{{ $metrics['awaiting_offers'] ? 'warning' : '' }}">{{ number_format($metrics['awaiting_offers']) }}</strong>
        </div>

        <div>
            <small>Open (pending)</small>
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
                <p>Newest first. Open a request to see every offer and the project.</p>
            </div>

            <form method="GET" action="{{ route('admin.quote-requests.index') }}" class="table-filters">
                <select name="status" class="select-control" aria-label="Request status">
                    <option value="">All statuses</option>

                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <input class="table-search" type="search" name="search" value="{{ request('search') }}"
                    placeholder="Customer, company, service or #ID" maxlength="100" aria-label="Search requests">

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
                        <th>Service</th>
                        <th>Load estimate</th>
                        <th>Received</th>
                        <th>Offers</th>
                        <th>Company</th>
                        <th>Stage</th>
                        <th>Details</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($quotes as $quote)
                        @php
                            $customer = $quote->customer;
                            $company = $quote->company;
                            $project = $quote->project;
                            $stage = $quote->stage();

                            $customerName = $customer?->full_name
                                ?: ($customer?->username ?: 'Customer #' . $quote->customer_id);

                            $systemType = null;

                            if (preg_match('/System type:\s*([^\r\n]+)/i', $quote->estimated_load ?? '', $matches)) {
                                $systemType = trim($matches[1]);
                            }
                        @endphp

                        <tr>
                            <td>
                                <strong>#Q-{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }}</strong>
                                <small class="cell-note">{{ $quote->company_id && !$project ? 'Sent to one company' : 'Open to all companies' }}</small>
                            </td>

                            <td>
                                <div class="person-cell">
                                    <span class="person-avatar">{{ strtoupper(mb_substr($customerName, 0, 1)) }}</span>
                                    <span>
                                        <strong>{{ $customerName }}</strong>
                                        <small>{{ $customer?->city ?: 'City not set' }}</small>
                                    </span>
                                </div>
                            </td>

                            <td>
                                <strong>{{ $quote->service_needed }}</strong>
                                @if ($systemType)
                                    <small class="cell-note">{{ $systemType }}</small>
                                @endif
                            </td>

                            <td>
                                <strong>{{ $quote->daily_load ?: '—' }}</strong>
                                <small class="cell-note">Peak: {{ $quote->peak_load ?: '—' }}</small>
                            </td>

                            <td>
                                {{ $quote->created_at?->format('M d, Y') ?? '—' }}
                                <small class="cell-note">{{ $quote->created_at?->diffForHumans() }}</small>
                            </td>

                            <td>
                                @if ($quote->company_quotations_count > 0)
                                    <strong>{{ $quote->company_quotations_count }}</strong>
                                @else
                                    <span class="gray-text">None</span>
                                @endif
                            </td>

                            <td>
                                @if ($company)
                                    <strong>{{ $company->name }}</strong>
                                    <small class="cell-note">{{ $project ? 'Chosen by customer' : 'Requested directly' }}</small>
                                @else
                                    <span class="gray-text">Not chosen yet</span>
                                @endif
                            </td>

                            <td style="min-width:150px">
                                <span class="pill {{ $stage['class'] }}">{{ $stage['label'] }}</span>
                                @if ($project && $project->status !== 'completed')
                                    <div class="mini-progress" title="{{ $project->progress_percent }}% complete">
                                        <span style="width: {{ $project->progress_percent }}%"></span>
                                    </div>
                                @endif
                            </td>

                            <td>
                                <a href="{{ route('admin.quote-requests.show', $quote->id) }}" class="row-action">
                                    View ↗
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="dashboard-empty">
                                <strong>No quote requests found.</strong>
                                Try changing the search text or status filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $quotes, 'label' => 'requests'])
    </section>
@endsection

@extends('company.layouts.app')

@section('title', 'Quote requests')
@section('breadcrumb', 'Quote requests')
@section('eyebrow', 'COMPANY ENQUIRIES')
@section('page-title', 'Quote requests')
@section('page-description', 'Customers looking for a solar system. Send your best price to win their project.')

@section('content')
    @php
        $requestStatusLabels = [
            'pending' => 'Open',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        $offerStatusLabels = [
            'submitted' => 'Waiting for customer',
            'selected' => 'Won',
            'not_selected' => 'Not selected',
            'withdrawn' => 'Not selected',
        ];

        $offerStatusClasses = [
            'submitted' => 'pill-yellow',
            'selected' => 'pill-green',
            'not_selected' => 'pill-red',
            'withdrawn' => 'pill-red',
        ];

        $authenticatedCompanyId = auth()->guard('company')->id();
    @endphp

    @include('partials.page-guide', [
        'key' => 'company-quote-requests',
        'title' => 'How to win customers',
        'intro' => 'Each row is a customer asking for a solar system. Requests come either to all companies or directly to you.',
        'steps' => [
            '<strong>Open a request</strong> and review the customer’s energy needs (daily energy, peak load, suggested equipment).',
            '<strong>Send your total price</strong> with a short message in English and Arabic. You can see other companies’ prices (never their names).',
            '<strong>Edit your offer</strong> any time while the customer is still deciding.',
            '<strong>If the customer chooses you</strong>, a project is created. Go to it from the "Next step" column.',
        ],
        'note' => 'Tip: customers usually decide quickly. Answer new requests the same day.',
    ])

    <div class="metrics-strip">
        <div>
            <small>Waiting for your offer</small>
            <strong class="{{ $metrics['awaiting_offer'] ? 'warning' : '' }}">{{ number_format($metrics['awaiting_offer']) }}</strong>
        </div>
        <div>
            <small>Open requests</small>
            <strong>{{ number_format($metrics['pending']) }}</strong>
        </div>
        <div>
            <small>Offers won</small>
            <strong>{{ number_format($metrics['won']) }}</strong>
        </div>
        <div>
            <small>All requests you can see</small>
            <strong>{{ number_format($metrics['total']) }}</strong>
        </div>
    </div>

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Customer requests</h2>
                <p>Newest first. The "Next step" column tells you what to do.</p>
            </div>

            <form method="GET" action="{{ route('company.quote-requests.index') }}" class="table-filters">

                <select name="status" class="select-control" aria-label="Request status">
                    <option value="">All requests</option>

                    @foreach ($requestStatusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <input class="table-search" type="search" name="search" value="{{ request('search') }}"
                    placeholder="Customer, service or #ID" maxlength="100" aria-label="Search requests">

                <button type="submit" class="btn btn-light">Search</button>

                @if (request()->hasAny(['status', 'search']))
                    <a href="{{ route('company.quote-requests.index') }}" class="btn btn-light">
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
                        <th>Quote name</th>
                        <th>Energy needs</th>
                        <th>Received</th>
                        <th>Offers</th>
                        <th>Your offer</th>
                        <th>Next step</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($quotes as $quote)
                        @php
                            $customer = $quote->customer;

                            $customerName = $customer?->full_name
                                ?: ($customer?->username ?: 'Customer #' . $quote->customer_id);

                            // This relationship holds only this company's own offer.
                            $myOffer = $quote->companyQuotations->first();
                            $project = $quote->project; // only loaded when this company won
                            $offerStatus = $myOffer?->status;

                            // Another company was chosen: this offer is not selected, or the
                            // request now belongs to a different company.
                            $belongsToAnotherCompany = $quote->company_id !== null
                                && (string) $quote->company_id !== (string) $authenticatedCompanyId;

                            $lostToAnother = $offerStatus === 'not_selected'
                                || ($belongsToAnotherCompany
                                    && ($myOffer || in_array($quote->status, ['accepted', 'completed'], true)));

                            if ($lostToAnother && $myOffer && $offerStatus !== 'withdrawn') {
                                $offerStatus = 'not_selected';
                            }

                            $isDirect = (string) $quote->company_id === (string) $authenticatedCompanyId && !$project;

                            // What the company should do next on this request.
                            [$nextLabel, $nextClass, $nextUrl] = match (true) {
                                $project !== null && $project->status === 'accepted' => ['Start the project', 'pill-green', route('company.projects.show', $project->id)],
                                $project !== null && $project->status === 'in_progress' => ['Update progress (' . $project->progress_percent . '%)', 'pill-blue', route('company.projects.show', $project->id)],
                                $project !== null => ['Project completed', 'pill-green', route('company.projects.show', $project->id)],
                                $lostToAnother => ['Closed: another offer chosen', 'pill-red', route('company.quote-requests.show', $quote->id)],
                                in_array($quote->status, ['cancelled', 'rejected'], true) => ['Closed by customer', 'pill-purple', route('company.quote-requests.show', $quote->id)],
                                $quote->status === 'pending' && !$myOffer => ['Send your offer', 'pill-orange', route('company.quote-requests.show', $quote->id)],
                                $quote->status === 'pending' && $offerStatus === 'submitted' => ['Customer deciding (you can edit)', 'pill-yellow', route('company.quote-requests.show', $quote->id)],
                                default => ['View request', 'pill-blue', route('company.quote-requests.show', $quote->id)],
                            };
                        @endphp

                        <tr>
                            <td>
                                <strong>#Q-{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }}</strong>
                                <small class="cell-note">{{ $isDirect ? 'Sent to you directly' : 'Open to all companies' }}</small>
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

                            <td><strong>{{ $quote->quote_name }}</strong></td>

                            <td>
                                <strong>{{ $quote->daily_load ?: '—' }}</strong>
                                <small class="cell-note">Peak: {{ $quote->peak_load ?: '—' }}</small>
                            </td>

                            <td>
                                {{ $quote->created_at?->format('d M Y') ?? '—' }}
                                <small class="cell-note">{{ $quote->created_at?->diffForHumans() }}</small>
                            </td>

                            <td>
                                <strong>{{ (int) $quote->total_offers_count }}</strong>
                                <small class="cell-note">{{ $myOffer ? 'incl. yours' : 'from others' }}</small>
                            </td>

                            <td>
                                @if ($myOffer)
                                    <strong>{{ $myOffer->currency }} {{ number_format((float) $myOffer->price, 2) }}</strong>
                                    <span class="pill {{ $offerStatusClasses[$offerStatus] ?? 'pill-blue' }}">
                                        {{ $offerStatusLabels[$offerStatus] ?? ucfirst((string) $offerStatus) }}
                                    </span>
                                @else
                                    <span class="gray-text">Not sent</span>
                                @endif
                            </td>

                            <td>
                                <a href="{{ $nextUrl }}" class="pill {{ $nextClass }} next-step-link">
                                    {{ $nextLabel }} →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="dashboard-empty">
                                <strong>No quote requests found.</strong>
                                Requests appear here when customers send them to you or to all companies.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $quotes, 'label' => 'requests'])
    </section>
@endsection

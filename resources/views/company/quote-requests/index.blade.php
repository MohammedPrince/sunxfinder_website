@extends('company.layouts.app')

@section('title', 'Quote requests')
@section('breadcrumb', 'Quote requests')
@section('eyebrow', 'COMPANY ENQUIRIES')
@section('page-title', 'Quote requests')
@section('page-description', 'Manage customer enquiries assigned to your solar company.')

@section('content')
    @php
        $requestStatusLabels = [
            'pending' => 'Pending',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        $requestStatusClasses = [
            'pending' => 'pill-yellow',
            'accepted' => 'pill-orange',
            'rejected' => 'pill-red',
            'completed' => 'pill-green',
            'cancelled' => 'pill-purple',
            'selected_another' => 'pill-red',
        ];

        $offerStatusLabels = [
            'submitted' => 'Pending',
            'selected' => 'Selected',
            'not_selected' => 'Not Selected',
            'withdrawn' => 'Rejected',
        ];

        $offerStatusClasses = [
            'submitted' => 'pill-yellow',
            'selected' => 'pill-green',
            'not_selected' => 'pill-red',
            'withdrawn' => 'pill-red',
        ];

        $authenticatedCompanyId = auth()->guard('company')->id();
    @endphp

    <div class="metrics-strip">
        <div>
            <small>Assigned requests</small>
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

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Assigned quote requests</h2>
                <p>Customer enquiries assigned to your company</p>
            </div>

            <form method="GET" action="{{ route('company.quote-requests.index') }}" class="table-filters">

                <select name="status" class="select-control">
                    <option value="">All request statuses</option>

                    @foreach ($requestStatusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <input class="table-search" type="search" name="search" value="{{ request('search') }}"
                    placeholder="Search assigned requests..." maxlength="100">

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
                        <th>Service</th>
                        <th>Daily energy</th>
                        <th>Peak load</th>
                        <th>Received</th>
                        <th>Price</th>
                        <th>Offer status</th>
                        <th>Request status</th>
                        <th>Details</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($quotes as $quote)
                        @php
                            $customer = $quote->customer;

                            $customerName = $customer?->name
                                ?? $customer?->full_name
                                ?? $customer?->customer_name
                                ?? 'Customer #' . $quote->customer_id;

                            // This relationship should contain only this company's offers.
                            $myOffer = $quote->companyQuotations->first();

                            $offerStatus = $myOffer?->status;
                            $requestStatus = $quote->status;

                            // The customer chose another company: either this offer was marked
                            // not selected, or the request now belongs to a different company.
                            $belongsToAnotherCompany = $quote->company_id !== null
                                && (string) $quote->company_id !== (string) $authenticatedCompanyId;

                            $selectedAnotherOffer = $offerStatus === 'not_selected'
                                || ($belongsToAnotherCompany
                                    && ($myOffer || in_array($quote->status, ['accepted', 'completed'], true)));

                            if ($selectedAnotherOffer) {
                                // A rejected (withdrawn) offer stays "Rejected"; otherwise "Not Selected".
                                if ($myOffer && $offerStatus !== 'withdrawn') {
                                    $offerStatus = 'not_selected';
                                }

                                $requestStatus = 'selected_another';
                            }
                        @endphp

                        <tr>
                            <td>
                                <strong>
                                    #Q-{{ str_pad($quote->id, 5, '0', STR_PAD_LEFT) }}
                                </strong>
                            </td>

                            <td>
                                <div class="person-cell">
                                    <span class="person-avatar">
                                        {{ strtoupper(substr($customerName, 0, 1)) }}
                                    </span>

                                    <span>
                                        <strong>{{ $customerName }}</strong>
                                        <small>{{ $customer?->email ?? 'No email' }}</small>
                                    </span>
                                </div>
                            </td>

                            <td>
                                <strong>{{ $quote->service_needed }}</strong>
                            </td>

                            <td>{{ $quote->daily_load ?: '—' }}</td>

                            <td>{{ $quote->peak_load ?: '—' }}</td>

                            <td>
                                {{ $quote->created_at?->format('d M Y') ?? '—' }}
                                <small style="display:block">
                                    {{ $quote->created_at?->format('H:i') }}
                                </small>
                            </td>

                            {{-- Price from this company's offer --}}
                            <td>
                                @if ($myOffer)
                                    <strong>
                                        {{ $myOffer->currency }}
                                        {{ number_format((float) $myOffer->price, 2) }}
                                    </strong>
                                @else
                                    <span>Not submitted</span>
                                @endif
                            </td>

                            {{-- Status from CompanyQuotation --}}
                            <td>
                                <span class="pill {{ $offerStatusClasses[$offerStatus] ?? 'pill-blue' }}">
                                    {{ $offerStatusLabels[$offerStatus] ?? 'No offer' }}
                                </span>
                            </td>

                            {{-- Status from Quote --}}
                            <td>
                                <span class="pill {{ $requestStatusClasses[$requestStatus] ?? 'pill-blue' }}">
                                    {{ $selectedAnotherOffer
                                        ? 'Selected another offer'
                                        : $requestStatusLabels[$requestStatus] ?? ucfirst((string) $requestStatus) }}
                                </span>
                                <small>By customer</small>
                            </td>

                            <td>
                                <a href="{{ route('company.quote-requests.show', $quote->id) }}" class="row-action">
                                    View &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align:center;padding:40px 16px">
                                <strong>No assigned quote requests found.</strong>
                                <p>
                                    Requests will appear here when they are assigned
                                    to your company or become available to all companies.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $quotes, 'label' => 'requests'])
    </section>
@endsection
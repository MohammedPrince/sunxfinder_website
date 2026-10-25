@extends('company.layouts.app')

@section('title', 'Company Customers')
@section('breadcrumb', 'Company Customers')
@section('eyebrow', 'CUSTOMERS')
@section('page-title', 'Company Customers')
@section('page-description', 'Customers who chose your company by accepting one of your offers.')

@section('content')
    <div class="metrics-strip">
        <div>
            <small>Customers</small>
            <strong>{{ number_format($summary['customers']) }}</strong>
        </div>

        <div>
            <small>With an active project</small>
            <strong>{{ number_format($summary['active']) }}</strong>
        </div>

        <div>
            <small>Returning customers</small>
            <strong>{{ number_format($summary['repeat']) }}</strong>
        </div>

        <div>
            <small>New this month</small>
            <strong>{{ number_format($summary['this_month']) }}</strong>
        </div>
    </div>

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Your customers</h2>
                <p>Most recent first. A customer appears here once they accept your offer.</p>
            </div>

            <form method="GET" action="{{ route('company.customers.index') }}" class="table-filters">
                <input class="table-search" type="search" name="search" maxlength="100"
                    placeholder="Name, phone or city" aria-label="Search customers" value="{{ request('search') }}">
                <button type="submit" class="btn btn-light">Search</button>

                @if (request()->filled('search'))
                    <a href="{{ route('company.customers.index') }}" class="btn btn-light">Clear</a>
                @endif

                @if ($summary['customers'] > 0)
                    <a href="{{ route('company.notifications.create', ['to' => 'company-customers']) }}"
                        class="btn btn-primary">Notify all my customers</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Projects</th>
                        <th>Active</th>
                        <th>Completed</th>
                        <th>Customer since</th>
                        <th>Latest project</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($customers as $customer)
                        @php
                            $customerName = $customer->full_name ?: ($customer->username ?: 'Customer');
                        @endphp
                        <tr>
                            <td>
                                <div class="person-cell">
                                    <span class="person-avatar">{{ strtoupper(mb_substr($customerName, 0, 1)) }}</span>
                                    <span>
                                        <strong>{{ $customerName }}</strong>
                                        <small>{{ $customer->city ?: 'City not set' }}</small>
                                    </span>
                                </div>
                            </td>

                            <td dir="ltr">{{ $customer->phone ?: '—' }}</td>
                            <td>{{ $customer->email ?: '—' }}</td>
                            <td><strong>{{ $customer->projects }}</strong></td>
                            <td>{{ (int) $customer->active_projects }}</td>
                            <td>{{ (int) $customer->completed_projects }}</td>

                            <td>
                                {{ \Illuminate\Support\Carbon::parse($customer->first_project_at)->format('M d, Y') }}
                            </td>

                            <td>
                                <a href="{{ route('company.projects.show', $customer->last_project_id) }}">
                                    <strong>View project</strong>
                                </a>
                                <small class="cell-note">
                                    {{ \Illuminate\Support\Carbon::parse($customer->last_project_at)->diffForHumans() }}
                                </small>
                            </td>

                            <td>
                                <a href="{{ route('company.notifications.create', ['customer_ids' => [$customer->id]]) }}"
                                    class="btn btn-light" title="Send a push notification to {{ $customerName }}">
                                    Notify
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="dashboard-empty">
                                @if (request()->filled('search'))
                                    <strong>No customers match your search.</strong>
                                @else
                                    <strong>No customers yet.</strong>
                                    When a customer accepts one of your offers, they appear here.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $customers, 'label' => 'customers'])
    </section>
@endsection

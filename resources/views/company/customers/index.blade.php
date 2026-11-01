@extends('company.layouts.app')

@section('title', __('company/messages.customers.title'))
@section('breadcrumb', __('company/messages.customers.title'))
@section('eyebrow', __('company/messages.customers.eyebrow'))
@section('page-title', __('company/messages.customers.title'))
@section('page-description', __('company/messages.customers.page_description'))

@section('content')
    <div class="metrics-strip">
        <div>
            <small>{{ __('company/messages.common.customers') }}</small>
            <strong>{{ number_format($summary['customers']) }}</strong>
        </div>

        <div>
            <small>{{ __('company/messages.customers.with_active') }}</small>
            <strong>{{ number_format($summary['active']) }}</strong>
        </div>

        <div>
            <small>{{ __('company/messages.customers.returning') }}</small>
            <strong>{{ number_format($summary['repeat']) }}</strong>
        </div>

        <div>
            <small>{{ __('company/messages.customers.new_this_month') }}</small>
            <strong>{{ number_format($summary['this_month']) }}</strong>
        </div>
    </div>

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>{{ __('company/messages.customers.your_customers') }}</h2>
                <p>{{ __('company/messages.customers.list_sub') }}</p>
            </div>

            <form method="GET" action="{{ route('company.customers.index') }}" class="table-filters">
                <input class="table-search" type="search" name="search" maxlength="100"
                    placeholder="{{ __('company/messages.customers.search_placeholder') }}" aria-label="{{ __('company/messages.customers.search_label') }}" value="{{ request('search') }}">
                <button type="submit" class="btn btn-light">{{ __('company/messages.common.search') }}</button>

                @if (request()->filled('search'))
                    <a href="{{ route('company.customers.index') }}" class="btn btn-light">{{ __('company/messages.common.clear') }}</a>
                @endif

                @if ($summary['customers'] > 0)
                    <a href="{{ route('company.notifications.create', ['to' => 'company-customers']) }}"
                        class="btn btn-primary">{{ __('company/messages.customers.notify_all') }}</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('company/messages.common.customer') }}</th>
                        <th>{{ __('company/messages.common.phone') }}</th>
                        <th>{{ __('company/messages.common.email') }}</th>
                        <th>{{ __('company/messages.customers.projects') }}</th>
                        <th>{{ __('company/messages.common.active') }}</th>
                        <th>{{ __('company/messages.common.completed') }}</th>
                        <th>{{ __('company/messages.customers.since') }}</th>
                        <th>{{ __('company/messages.customers.latest_project') }}</th>
                        <th>{{ __('company/messages.common.actions') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($customers as $customer)
                        @php
                            $customerName = $customer->full_name ?: ($customer->username ?: __('company/messages.common.customer'));
                        @endphp
                        <tr>
                            <td>
                                <div class="person-cell">
                                    <span class="person-avatar">{{ strtoupper(mb_substr($customerName, 0, 1)) }}</span>
                                    <span>
                                        <strong>{{ $customerName }}</strong>
                                        <small>{{ $customer->city ?: __('company/messages.common.city_not_set') }}</small>
                                    </span>
                                </div>
                            </td>

                            <td dir="ltr">{{ $customer->phone ?: '—' }}</td>
                            <td>{{ $customer->email ?: '—' }}</td>
                            <td><strong>{{ $customer->projects }}</strong></td>
                            <td>{{ (int) $customer->active_projects }}</td>
                            <td>{{ (int) $customer->completed_projects }}</td>

                            <td>
                                {{ \Illuminate\Support\Carbon::parse($customer->first_project_at)->translatedFormat('M d, Y') }}
                            </td>

                            <td>
                                <a href="{{ route('company.projects.show', $customer->last_project_id) }}">
                                    <strong>{{ __('company/messages.customers.view_project') }}</strong>
                                </a>
                                <small class="cell-note">
                                    {{ \Illuminate\Support\Carbon::parse($customer->last_project_at)->diffForHumans() }}
                                </small>
                            </td>

                            <td>
                                <a href="{{ route('company.notifications.create', ['customer_ids' => [$customer->id]]) }}"
                                    class="btn btn-light" title="{{ __('company/messages.customers.notify_title', ['name' => $customerName]) }}">
                                    {{ __('company/messages.customers.notify') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="dashboard-empty">
                                @if (request()->filled('search'))
                                    <strong>{{ __('company/messages.customers.no_match') }}</strong>
                                @else
                                    <strong>{{ __('company/messages.customers.empty_title') }}</strong>
                                    {{ __('company/messages.customers.empty_body') }}
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

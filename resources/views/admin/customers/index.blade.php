@extends('admin.layouts.app')

@section('title', 'Customers')
@section('breadcrumb', 'Customers')
@section('eyebrow', 'CUSTOMER DIRECTORY')
@section('page-title', 'Customers')
@section('page-description', 'Review and manage customer accounts registered on SunXFinder.')

@section('page-actions')
    <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">+ Add New Customer</a>
@endsection

@section('content')

    {{-- Success message --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
        </div>
    @endif

    {{-- Error message --}}
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible">
            <strong>Error!</strong> {{ session('error') }}
            <button type="button" class="alert-close" onclick="this.parentElement.remove()"
                aria-label="Close">&times;</button>
        </div>
    @endif

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following errors:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Customer metrics --}}
    <div class="metrics-strip">
        <div>
            <small>All customers</small>
            <strong>{{ $totalCount }}</strong>
        </div>

        <div>
            <small>Active</small>
            <strong>{{ $activeCount }}</strong>
        </div>

        <div>
            <small>Pending</small>
            <strong>{{ $pendingCount }}</strong>
        </div>

        <div>
            <small>Inactive</small>
            <strong>{{ $inactiveCount }}</strong>
        </div>
    </div>

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Customer directory</h2>
                <p>Manage registered customer accounts</p>
            </div>

            {{-- Status filters on the server (all pages); search still filters the current page. --}}
            <form method="GET" action="{{ route('admin.customers.index') }}" class="table-filters">
                <select class="select-control" name="status" aria-label="Customer status" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach (['active' => 'Active', 'pending' => 'Pending approval', 'suspended' => 'Suspended', 'deleted' => 'Deleted'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                {{-- Live search; Enter must not submit the status form and wipe the text. --}}
                <input class="table-search" type="search" placeholder="Search customers…" data-table-search
                    onkeydown="if (event.key === 'Enter') event.preventDefault();">

                @if (request()->filled('status'))
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-light">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table js-data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Gender</th>
                        <th>City</th>
                        <th>Joined</th>
                        <th>Last login</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            {{-- Customer information --}}
                            <td>
                                <div class="company-cell">
                                    <span class="company-avatar solar">
                                        {{ strtoupper(substr($customer->full_name ?: $customer->username, 0, 1)) }}
                                    </span>

                                    <span>
                                        <strong>{{ $customer->full_name ?: '—' }}</strong>
                                        <small>{{ $customer->username }}</small>
                                    </span>
                                </div>
                            </td>

                            {{-- Phone --}}
                            <td>{{ $customer->phone ?: '—' }}</td>

                            {{-- Email --}}
                            <td>{{ $customer->email ?: '—' }}</td>

                            {{-- Gender --}}
                            <td>
                                {{ $customer->gender ? ucfirst($customer->gender) : '—' }}
                            </td>

                            {{-- City --}}
                            <td>{{ $customer->city ?: '—' }}</td>

                            {{-- Registration date --}}
                            <td>
                                {{ $customer->created_at?->format('M d, Y') ?? '—' }}
                            </td>

                            {{-- Last app login --}}
                            <td>
                                @if ($customer->last_login_at)
                                    <span title="{{ $customer->last_login_at->format('M d, Y H:i') }}">
                                        {{ $customer->last_login_at->format('M d, Y') }}
                                    </span>
                                    <small class="cell-note">{{ $customer->last_login_at->diffForHumans() }}</small>
                                @else
                                    <span class="cell-note">Never</span>
                                @endif

                                @if ($customer->tokens_count)
                                    <small class="cell-note signed-in-note">● Signed in on app</small>
                                @endif
                            </td>

                            {{-- Account status --}}
                            <td>
                                @php
                                    $statusClass = match ($customer->status) {
                                        'active' => 'pill-green',
                                        'pending' => 'pill-yellow',
                                        'suspended', 'deleted' => 'pill-red',
                                        default => 'pill-yellow',
                                    };
                                @endphp

                                <span class="pill {{ $statusClass }}" data-status="{{ $customer->status }}">
                                    {{ ucfirst($customer->status) }}
                                </span>

                                @if (!$customer->is_active)
                                    <small class="d-block">Inactive account</small>
                                @endif
                            </td>


                            {{-- Actions --}}
                            <td>
                                <div class="company-actions">
                                    {{-- Edit customer --}}
                                    <a href="{{ route('admin.customers.edit', $customer->id) }}"
                                        class="row-action row-action-icon" title="Edit customer"
                                        aria-label="Edit {{ $customer->full_name }}">

                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none"
                                            stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                            stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 20h9" />
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" />
                                        </svg>
                                    </a>

                                    {{-- Delete customer --}}
                                    <form action="{{ route('admin.customers.destroy', $customer->id) }}" method="POST"
                                        class="company-delete-form"
                                        onsubmit="return confirm('Are you sure you want to delete this customer?');">

                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="row-action row-action-icon row-action-delete"
                                            title="Delete customer" aria-label="Delete {{ $customer->full_name }}">

                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none"
                                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                                stroke-linejoin="round" aria-hidden="true">
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
                            <td colspan="9" class="text-center">
                                No customers found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @include('partials.table-pagination', ['paginator' => $customers, 'label' => 'customers'])
    </section>

    {{-- Sign every customer out of the app --}}
    <section class="panel danger-zone">
        <div class="danger-zone-row">
            <div>
                <h2>Force logout all customers</h2>
                <p>
                    Signs every customer out of the mobile app. They must log in again, and push notifications
                    stop until they do. Use it after a security issue or a major app change.
                    <strong>{{ number_format($signedInCount) }}</strong>
                    {{ $signedInCount === 1 ? 'customer is' : 'customers are' }} signed in right now.
                </p>
            </div>
            <form action="{{ route('admin.customers.force-logout') }}" method="POST"
                onsubmit="return confirm('Sign ALL customers out of the app? Everyone will have to log in again.');">
                @csrf
                <button type="submit" class="btn btn-danger" @disabled($signedInCount === 0)>Force logout all</button>
            </form>
        </div>
    </section>

@endsection

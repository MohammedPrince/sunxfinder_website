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

            <div class="table-filters">
                <select class="select-control" data-table-filter="status">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="pending">Pending</option>
                    <option value="suspended">Suspended</option>
                    <option value="deleted">Deleted</option>
                </select>

                <input class="table-search" type="search" placeholder="Search customers…" data-table-search>
            </div>
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
                            <td colspan="8" class="text-center">
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

@endsection

@extends('admin.layouts.app')
@section('title', 'Solar companies') @section('breadcrumb', 'Solar companies')
@section('eyebrow', 'PROVIDER DIRECTORY') @section('page-title', 'Solar companies')
@section('page-description', 'Review, verify and manage solar companies listed on SunXFinder.')
@section('page-actions')

  <a href="{{ route('admin.companies.create') }}" class="btn btn-primary">+ Add New Company</a>

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

    {{-- Dynamic company metrics --}}
    <div class="metrics-strip">
        <div>
            <small>All companies</small>
            <strong>{{ $companies->total() }}</strong>
        </div>
        <div>
            <small>Verified</small>
            <strong>{{ $verifiedCount }}</strong>
        </div>
        <div>
            <small>Pending review</small>
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
                <h2>Company directory</h2>
                <p>Manage marketplace providers</p>
            </div>

            <div class="table-filters">
                <select class="select-control" data-table-filter="status">
                    <option value="">All statuses</option>
                    <option value="approved">Approved</option>
                    <option value="pending">Pending</option>
                    <option value="rejected">Rejected</option>
                </select>

                <input class="table-search" type="search" placeholder="Search companies…" data-table-search>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table js-data-table">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>City</th>
                        <th>Email</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th>Verification</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($companies as $company)
                        <tr>
                            {{-- Company --}}
                            <td>
                                <div class="company-cell">
                                    <span class="company-avatar solar">
                                        {{ strtoupper(substr($company->name, 0, 1)) }}
                                    </span>

                                    <span>
                                        <strong>{{ $company->name }}</strong>
                                        <small>{{ $company->username }}</small>
                                    </span>
                                </div>
                            </td>

                            {{-- City --}}
                            <td>{{ $company->city ?: '—' }}</td>

                            {{-- Email --}}
                            <td>{{ $company->email ?: '—' }}</td>

                            {{-- Registration date --}}
                            <td>
                                {{ $company->created_at?->format('M d, Y') ?? '—' }}
                            </td>

                            {{-- Status --}}
                            <td>
                                @php
                                    $statusClass = match ($company->status) {
                                        'approved' => 'pill-green',
                                        'pending' => 'pill-yellow',
                                        'rejected' => 'pill-red',
                                        default => 'pill-yellow',
                                    };
                                @endphp

                                <span class="pill {{ $statusClass }}" data-status="{{ $company->status }}">
                                    {{ ucfirst($company->status) }}
                                </span>

                                @if (!$company->is_active)
                                    <small class="d-block">Inactive</small>
                                @endif
                            </td>

                            {{-- Verification --}}
                            <td>
                                @if ($company->is_verified)
                                    <span class="verify verified">✓ Verified</span>
                                @else
                                    <span class="verify pending">◷ Unverified</span>
                                @endif
                            </td>
                            {{-- Actions --}}
                            <td>
                                <div class="company-actions">
                                    {{-- Update company --}}
                                    <a href="{{ route('admin.companies.edit', $company->id) }}"
                                        class="row-action row-action-icon" title="Update company"
                                        aria-label="Update {{ $company->name }}">

                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none"
                                            stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                            stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 20h9" />
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" />
                                        </svg>
                                    </a>

                                    {{-- Delete company --}}
                                    <form action="{{ route('admin.companies.destroy', $company->id) }}" method="POST"
                                        class="company-delete-form"
                                        onsubmit="return confirm('Are you sure you want to delete {{ addslashes($company->name) }}?');">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="row-action row-action-icon row-action-delete"
                                            title="Delete company" aria-label="Delete {{ $company->name }}">

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
                            <td colspan="7" class="text-center">
                                No companies found.
                                <a href="{{ route('admin.companies.create') }}">
                                    Add your first company
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @include('partials.table-pagination', ['paginator' => $companies, 'label' => 'companies'])
    </section>

@endsection

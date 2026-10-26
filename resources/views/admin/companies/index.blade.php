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
                        <th>Email</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th>Verification</th>
                        <th>Rating</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($companies as $company)
                        <tr>
                            {{-- Company --}}
                            <td>
                                <div class="company-cell">
                                    @if (!empty($company->logo_path))
                                        <img class="company-avatar solar"
                                            src="{{ app(\App\Helpers\Helper::class)->publicUrl($company->logo_path) }}"
                                            alt="{{ $company->name }} logo" loading="lazy"
                                            style="object-fit: cover;">
                                    @else
                                        @php
                                            preg_match('/^\X/u', $company->name ?? '', $initialMatch);
                                            $companyInitial = mb_strtoupper($initialMatch[0] ?? '?', 'UTF-8');
                                        @endphp
                                        <span class="company-avatar solar" dir="auto">
                                            {{ $companyInitial }}
                                        </span>
                                    @endif

                                    <span>
                                        <strong>
                                            @if ($company->slug && $company->status === 'approved' && $company->is_active)
                                                <a href="{{ route('company-profile.show', ['slug' => $company->slug]) }}"
                                                    target="_blank" rel="noopener noreferrer">
                                                    {{ $company->name }}
                                                </a>
                                            @else
                                                {{ $company->name }}
                                            @endif
                                        </strong>
                                        <small>{{ $company->username }}</small>
                                    </span>
                                </div>
                            </td>

                            {{-- Email --}}
                            <td>
                                @if ($company->email)
                                    <a href="{{ route('admin.send-email.index', ['email' => $company->email]) }}">
                                        {{ $company->email }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>

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
                            <td>
                                @if ($company->visible_rating_count > 0)
                                    <span title="{{ $company->visible_rating_count }} visible ratings">
                                        ★ {{ number_format((float) $company->visible_rating_avg, 1) }}
                                        <small>({{ $company->visible_rating_count }})</small>
                                    </span>
                                @else
                                    <span class="text-muted">No ratings</span>
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

                                    {{-- Company details --}}
                                    <a href="{{ route('admin.companies.details', $company->id) }}"
                                        class="row-action row-action-icon" title="Company details"
                                        aria-label="View details for {{ $company->name }}">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none"
                                            stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                            stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="12" cy="12" r="9" />
                                            <path d="M12 11v5M12 8h.01" />
                                        </svg>
                                    </a>

                                    {{-- Log in as company (opens the company dashboard in a new tab) --}}
                                    @php
                                        $loginIcon = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" /><path d="m10 17 5-5-5-5" /><path d="M15 12H3" /></svg>';
                                    @endphp
                                    @if ($company->is_active && in_array($company->status, ['pending', 'approved'], true))
                                        <form action="{{ route('admin.companies.login', $company->id) }}" method="POST"
                                            class="company-login-form" target="_blank">
                                            @csrf
                                            <button type="submit" class="row-action row-action-icon row-action-login"
                                                title="Log in as company" aria-label="Log in as {{ $company->name }}">
                                                {!! $loginIcon !!}
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="row-action row-action-icon" disabled
                                            title="This company account is inactive or not eligible to log in"
                                            aria-label="Log in as {{ $company->name }} (not available)">
                                            {!! $loginIcon !!}
                                        </button>
                                    @endif

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

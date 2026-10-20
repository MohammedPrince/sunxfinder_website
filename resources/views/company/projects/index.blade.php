
@extends('company.layouts.app')

@section('title', 'Active Projects')
@section('breadcrumb', 'Active Projects')
@section('eyebrow', 'PROJECT MANAGEMENT')
@section('page-title', 'Active Projects')
@section('page-description', 'Track installation milestones and keep customers informed.')

@section('content')
    @php
        $statusLabels = [
            'accepted' => 'Accepted',
            'in_progress' => 'On Progress',
            'completed' => 'Completed',
        ];

        $statusClasses = [
            'accepted' => 'pill-blue',
            'in_progress' => 'pill-yellow',
            'completed' => 'pill-green',
        ];
    @endphp

    <div class="metrics-strip">
        <div>
            <small>Total projects</small>
            <strong>{{ number_format($metrics['total']) }}</strong>
        </div>
        <div>
            <small>Accepted</small>
            <strong>{{ number_format($metrics['accepted']) }}</strong>
        </div>
        <div>
            <small>On progress</small>
            <strong>{{ number_format($metrics['in_progress']) }}</strong>
        </div>
        <div>
            <small>Completed</small>
            <strong>{{ number_format($metrics['completed']) }}</strong>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Project management</h2>
                <p>Projects created from offers accepted by customers.</p>
            </div>

            <form method="GET"
                  action="{{ route('company.projects.index') }}"
                  class="table-filters">

                <select name="status" class="select-control">
                    <option value="">All project statuses</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <input
                    type="search"
                    name="search"
                    class="table-search"
                    value="{{ request('search') }}"
                    maxlength="100"
                    placeholder="Search projects..."
                >

                <button type="submit" class="btn btn-light">Search</button>

                @if (request()->hasAny(['status', 'search']))
                    <a href="{{ route('company.projects.index') }}" class="btn btn-light">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Selected offer</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Last update</th>
                        <th>Details</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($projects as $project)
                        @php
                            $customer = $project->customer;
                            $quote = $project->quote;
                            $offer = $project->companyQuotation;

                            $customerName = $customer?->name
                                ?? $customer?->full_name
                                ?? $customer?->customer_name
                                ?? 'Customer #' . $project->customer_id;

                            $reference = '#Q-' . str_pad(
                                (string) $project->quote_id,
                                5,
                                '0',
                                STR_PAD_LEFT
                            );
                        @endphp

                        <tr>
                            <td>
                                <strong>{{ $reference }}</strong>
                                <small style="display:block">
                                    Project #{{ $project->id }}
                                </small>
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

                            <td>{{ $quote?->service_needed ?? 'Solar installation' }}</td>

                            <td>
                                @if ($offer)
                                    <strong>
                                        {{ $offer->currency }}
                                        {{ number_format((float) $offer->price, 2) }}
                                    </strong>
                                @else
                                    —
                                @endif
                            </td>

                            <td style="min-width:130px">
                                <div style="display:flex;justify-content:space-between;gap:8px">
                                    <small>Completion</small>
                                    <strong>{{ $project->progress_percent }}%</strong>
                                </div>
                                <div style="height:7px;background:#e8edf4;border-radius:10px;overflow:hidden;margin-top:7px">
                                    <div style="height:100%;width:{{ $project->progress_percent }}%;background:#ff5a0a;border-radius:10px"></div>
                                </div>
                            </td>

                            <td>
                                <span class="pill {{ $statusClasses[$project->status] ?? 'pill-blue' }}">
                                    {{ $statusLabels[$project->status] ?? ucfirst(str_replace('_', ' ', $project->status)) }}
                                </span>
                            </td>

                            <td>
                                {{ $project->updated_at?->format('d M Y') ?? '—' }}
                                <small style="display:block">
                                    {{ $project->updated_at?->format('H:i') }}
                                </small>
                            </td>

                            <td>
                                <a href="{{ route('company.projects.show', $project->id) }}"
                                   class="row-action">
                                    Manage &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;padding:40px 16px">
                                <strong>No projects found.</strong>
                                <p>
                                    Projects will appear here after a customer accepts
                                    one of your offers.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-bottom">
            <span class="table-info">
                Showing {{ $projects->firstItem() ?? 0 }}
                to {{ $projects->lastItem() ?? 0 }}
                of {{ $projects->total() }} projects
            </span>

            <div class="pagination-wrapper">
                {{ $projects->links() }}
            </div>
        </div>
    </section>
@endsection

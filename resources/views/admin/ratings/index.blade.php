@extends('admin.layouts.app')

@section('title', 'Ratings')
@section('breadcrumb', 'Ratings')
@section('eyebrow', 'CUSTOMER FEEDBACK')
@section('page-title', 'Ratings')
@section('page-description', 'How customers rated the companies they hired, after accepting an offer in the app.')

@section('page-actions')
    <a href="{{ route('admin.rating-questions.index') }}" class="btn btn-light">Rating questions</a>
    <a href="{{ route('admin.ratings.index', request()->query()) }}" class="btn btn-light">↻ Refresh</a>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @include('partials.page-guide', [
        'key' => 'admin-ratings',
        'title' => 'How ratings work',
        'intro' => 'After accepting an offer, the customer can rate that company once per request from the app.',
        'steps' => [
            '<strong>Stars (1–5)</strong>: the overall score. The company average on the website and in the app is based on these.',
            '<strong>Questions (1–4)</strong>: optional answers to the questions you set, like Price or Service.',
            '<strong>Hide</strong>: removes a rating from the public page and the average without deleting it.',
            '<strong>Delete</strong>: removes it completely. The customer can then rate that request again.',
        ],
        'note' => 'Hide ratings with abusive comments rather than deleting them, so you keep a record.',
    ])

    {{-- Summary metrics --}}
    <div class="metrics-strip">
        <div>
            <small>Total ratings</small>
            <strong>{{ number_format($metrics['total']) }}</strong>
        </div>

        <div>
            <small>Average stars</small>
            <strong>{{ $metrics['average'] !== null ? number_format($metrics['average'], 1) . ' / 5' : '—' }}</strong>
        </div>

        <div>
            <small>This month</small>
            <strong>{{ number_format($metrics['this_month']) }}</strong>
        </div>

        <div>
            <small>Rated companies</small>
            <strong>{{ number_format($metrics['rated_companies']) }}</strong>
        </div>

        <div>
            <small>Hidden</small>
            <strong class="{{ $metrics['hidden'] ? 'warning' : '' }}">{{ number_format($metrics['hidden']) }}</strong>
        </div>
    </div>

    {{-- Star distribution --}}
    @if ($metrics['total'] > 0)
        @php $visibleTotal = max(1, array_sum($metrics['distribution'])); @endphp
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Star distribution</h2>
                    <p>Visible ratings only.</p>
                </div>
            </div>
            <div class="panel-body">
                <div class="rating-overview">
                    <div class="rating-score">
                        <strong>{{ $metrics['average'] !== null ? number_format($metrics['average'], 1) : '—' }}</strong>
                        <span>
                            @include('partials.stars', ['value' => $metrics['average'], 'large' => true])
                            <small class="cell-note">{{ number_format(array_sum($metrics['distribution'])) }} visible ratings</small>
                        </span>
                    </div>

                    <div class="rating-bars">
                        @foreach ($metrics['distribution'] as $stars => $count)
                            <div class="rating-bar-row">
                                <span>{{ $stars }} ★</span>
                                <div class="mini-progress"><span style="width: {{ round($count / $visibleTotal * 100) }}%"></span></div>
                                <b>{{ $count }}</b>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Ratings table --}}
    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Customer ratings</h2>
                <p>Newest first. Open a rating to see each question's answer.</p>
            </div>

            <form method="GET" action="{{ route('admin.ratings.index') }}" class="table-filters">
                <select name="company_id" class="select-control" aria-label="Company">
                    <option value="">All companies</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>

                <select name="stars" class="select-control" aria-label="Stars">
                    <option value="">All stars</option>
                    @for ($s = 5; $s >= 1; $s--)
                        <option value="{{ $s }}" @selected((string) request('stars') === (string) $s)>{{ $s }} ★</option>
                    @endfor
                </select>

                <select name="visibility" class="select-control" aria-label="Visibility">
                    <option value="">Visible and hidden</option>
                    <option value="visible" @selected(request('visibility') === 'visible')>Visible</option>
                    <option value="hidden" @selected(request('visibility') === 'hidden')>Hidden</option>
                </select>

                <input class="table-search" type="search" name="search" value="{{ request('search') }}"
                    placeholder="Customer, company, comment or #ID" maxlength="100" aria-label="Search ratings">

                <button type="submit" class="btn btn-light">Search</button>

                @if (request()->hasAny(['company_id', 'stars', 'visibility', 'search']))
                    <a href="{{ route('admin.ratings.index') }}" class="btn btn-light">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Company</th>
                        <th>Request</th>
                        <th>Stars</th>
                        <th>Comment</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($ratings as $rating)
                        @php
                            $customerName = $rating->customer?->full_name
                                ?: ($rating->customer?->username ?: 'Customer #' . $rating->customer_id);
                        @endphp
                        <tr>
                            <td>
                                <div class="person-cell">
                                    <span class="person-avatar">{{ strtoupper(mb_substr($customerName, 0, 1)) }}</span>
                                    <span>
                                        <strong>{{ $customerName }}</strong>
                                        <small>{{ $rating->customer?->city ?: 'City not set' }}</small>
                                    </span>
                                </div>
                            </td>

                            <td>
                                <strong>{{ $rating->company?->name ?? 'Company #' . $rating->company_id }}</strong>
                                <small class="cell-note">{{ $rating->company?->city }}</small>
                            </td>

                            <td>
                                <a href="{{ route('admin.quote-requests.show', $rating->quote_id) }}">
                                    <strong>#Q-{{ str_pad($rating->quote_id, 5, '0', STR_PAD_LEFT) }}</strong>
                                </a>
                                <small class="cell-note">{{ $rating->quote?->quote_name }}</small>
                            </td>

                            <td>
                                @include('partials.stars', ['value' => $rating->stars])
                                <small class="cell-note">
                                    {{ $rating->answers_count }} {{ \Illuminate\Support\Str::plural('answer', $rating->answers_count) }}
                                </small>
                            </td>

                            <td>
                                <div class="rating-comment-cell" title="{{ $rating->comment }}">
                                    {{ $rating->comment ?: '—' }}
                                </div>
                            </td>

                            <td>
                                {{ $rating->created_at?->format('M d, Y') }}
                                <small class="cell-note">{{ $rating->created_at?->diffForHumans() }}</small>
                            </td>

                            <td>
                                <span class="pill {{ $rating->is_visible ? 'pill-green' : 'pill-purple' }}">
                                    {{ $rating->is_visible ? 'Visible' : 'Hidden' }}
                                </span>
                            </td>

                            <td>
                                <div class="company-actions">
                                    <a href="{{ route('admin.ratings.show', $rating->id) }}" class="row-action">View ↗</a>

                                    <form action="{{ route('admin.ratings.visibility', $rating->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="row-action">
                                            {{ $rating->is_visible ? 'Hide' : 'Show' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="dashboard-empty">
                                <strong>No ratings found.</strong>
                                @if (request()->hasAny(['company_id', 'stars', 'visibility', 'search']))
                                    Try changing the filters.
                                @else
                                    Ratings appear here once customers rate the companies they hired.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $ratings, 'label' => 'ratings'])
    </section>
@endsection

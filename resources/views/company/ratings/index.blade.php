@extends('company.layouts.app')

@section('title', 'My Ratings')
@section('breadcrumb', 'My Ratings')
@section('eyebrow', 'CUSTOMER FEEDBACK')
@section('page-title', 'My Ratings')
@section('page-description', 'What your customers said after hiring you. Your average shows on your public page and in the app.')

@section('content')
    @include('partials.page-guide', [
        'key' => 'company-ratings',
        'title' => 'How ratings work',
        'intro' => 'After accepting your offer, a customer can rate you once per request from the app.',
        'steps' => [
            '<strong>Stars (1–5)</strong>: the overall score. Your average is based on these.',
            '<strong>Questions</strong>: answers to questions like Price or Service, from ' . e($valueLabels[1] ?? 'Poor') . ' to ' . e($valueLabels[\App\Models\RatingValue::MAX] ?? 'Excellent') . '.',
            '<strong>Comments</strong>: shown on your public page with the customer\'s first name.',
        ],
        'note' => 'Finish projects on time and keep customers updated: it is the best way to earn good ratings.',
    ])

    <div class="metrics-strip">
        <div>
            <small>Average stars</small>
            <strong>{{ $summary['average'] !== null ? number_format($summary['average'], 1) . ' / 5' : '—' }}</strong>
        </div>

        <div>
            <small>Total ratings</small>
            <strong>{{ number_format($summary['total']) }}</strong>
        </div>

        <div>
            <small>This month</small>
            <strong>{{ number_format($summary['this_month']) }}</strong>
        </div>

        <div>
            <small>With a comment</small>
            <strong>{{ number_format($summary['with_comment']) }}</strong>
        </div>
    </div>

    @if ($summary['total'] > 0)
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Overview</h2>
                    <p>Your overall stars and how customers answered each question.</p>
                </div>
            </div>
            <div class="panel-body">
                <div class="rating-overview">
                    <div class="rating-score">
                        <strong>{{ number_format($summary['average'], 1) }}</strong>
                        <span>
                            @include('partials.stars', ['value' => $summary['average'], 'large' => true])
                            <small class="cell-note">{{ number_format($summary['total']) }} ratings</small>
                        </span>
                    </div>

                    <div class="rating-bars">
                        @foreach ($summary['distribution'] as $stars => $count)
                            <div class="rating-bar-row">
                                <span>{{ $stars }} ★</span>
                                <div class="mini-progress"><span style="width: {{ round($count / $summary['total'] * 100) }}%"></span></div>
                                <b>{{ $count }}</b>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if (count($summary['questions']))
                    <div class="rating-answer-list" style="margin-top:24px">
                        @foreach ($summary['questions'] as $question)
                            <div class="rating-answer">
                                <span>
                                    <strong>{{ $question['title'] }}</strong>
                                    <small>{{ $question['question'] }} · {{ $question['answers'] }} answers</small>
                                </span>
                                <strong>{{ number_format($question['average'], 1) }} / {{ \App\Models\RatingValue::MAX }}</strong>
                                <div class="mini-progress">
                                    <span style="width: {{ round($question['average'] / \App\Models\RatingValue::MAX * 100) }}%"></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Customer reviews</h2>
                <p>Newest first.</p>
            </div>

            <form method="GET" action="{{ route('company.ratings.index') }}" class="table-filters">
                <select name="stars" class="select-control" aria-label="Stars" onchange="this.form.submit()">
                    <option value="">All stars</option>
                    @for ($s = 5; $s >= 1; $s--)
                        <option value="{{ $s }}" @selected((string) request('stars') === (string) $s)>{{ $s }} ★</option>
                    @endfor
                </select>

                @if (request()->filled('stars'))
                    <a href="{{ route('company.ratings.index') }}" class="btn btn-light">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Request</th>
                        <th>Stars</th>
                        <th>Answers</th>
                        <th>Comment</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($ratings as $rating)
                        @php
                            $customerName = $rating->customer?->full_name
                                ?: ($rating->customer?->username ?: 'Customer');
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
                                <a href="{{ route('company.quote-requests.show', $rating->quote_id) }}">
                                    <strong>#Q-{{ str_pad($rating->quote_id, 5, '0', STR_PAD_LEFT) }}</strong>
                                </a>
                                <small class="cell-note">{{ $rating->quote?->quote_name }}</small>
                            </td>

                            <td>@include('partials.stars', ['value' => $rating->stars])</td>

                            <td>
                                @forelse ($rating->answers as $answer)
                                    <small class="cell-note">
                                        {{ $answer->question?->title_en ?? 'Question' }}:
                                        <b>{{ $valueLabels[$answer->value] ?? $answer->value }}</b>
                                    </small>
                                @empty
                                    <span class="gray-text">—</span>
                                @endforelse
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="dashboard-empty">
                                <strong>No ratings yet.</strong>
                                @if (request()->filled('stars'))
                                    No ratings with this many stars.
                                @else
                                    Customers can rate you after accepting your offer.
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

@extends('company.layouts.app')

@section('title', __('company/messages.ratings.title'))
@section('breadcrumb', __('company/messages.ratings.title'))
@section('eyebrow', __('company/messages.ratings.eyebrow'))
@section('page-title', __('company/messages.ratings.title'))
@section('page-description', __('company/messages.ratings.page_description'))

@section('content')
    @include('partials.page-guide', [
        'key' => 'company-ratings',
        'title' => __('company/messages.ratings.guide_title'),
        'intro' => __('company/messages.ratings.guide_intro'),
        'steps' => [
            __('company/messages.ratings.guide_1'),
            __('company/messages.ratings.guide_2', ['low' => e($valueLabels[1] ?? __('company/messages.ratings.poor')), 'high' => e($valueLabels[\App\Models\RatingValue::MAX] ?? __('company/messages.ratings.excellent'))]),
            __('company/messages.ratings.guide_3'),
        ],
        'note' => __('company/messages.ratings.guide_note'),
    ])

    <div class="metrics-strip">
        <div>
            <small>{{ __('company/messages.ratings.average_stars') }}</small>
            <strong>{{ $summary['average'] !== null ? number_format($summary['average'], 1) . ' / 5' : '—' }}</strong>
        </div>

        <div>
            <small>{{ __('company/messages.ratings.total') }}</small>
            <strong>{{ number_format($summary['total']) }}</strong>
        </div>

        <div>
            <small>{{ __('company/messages.ratings.this_month') }}</small>
            <strong>{{ number_format($summary['this_month']) }}</strong>
        </div>

        <div>
            <small>{{ __('company/messages.ratings.with_comment') }}</small>
            <strong>{{ number_format($summary['with_comment']) }}</strong>
        </div>
    </div>

    @if ($summary['total'] > 0)
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>{{ __('company/messages.ratings.overview') }}</h2>
                    <p>{{ __('company/messages.ratings.overview_sub') }}</p>
                </div>
            </div>
            <div class="panel-body">
                <div class="rating-overview">
                    <div class="rating-score">
                        <strong>{{ number_format($summary['average'], 1) }}</strong>
                        <span>
                            @include('partials.stars', ['value' => $summary['average'], 'large' => true])
                            <small class="cell-note">{{ __('company/messages.ratings.ratings_count', ['count' => number_format($summary['total'])]) }}</small>
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
                                    <small>{{ $question['question'] }} · {{ __('company/messages.ratings.answers_count', ['count' => $question['answers']]) }}</small>
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
                <h2>{{ __('company/messages.ratings.reviews') }}</h2>
                <p>{{ __('company/messages.ratings.newest_first') }}</p>
            </div>

            <form method="GET" action="{{ route('company.ratings.index') }}" class="table-filters">
                <select name="stars" class="select-control" aria-label="{{ __('company/messages.ratings.stars') }}" onchange="this.form.submit()">
                    <option value="">{{ __('company/messages.ratings.all_stars') }}</option>
                    @for ($s = 5; $s >= 1; $s--)
                        <option value="{{ $s }}" @selected((string) request('stars') === (string) $s)>{{ $s }} ★</option>
                    @endfor
                </select>

                @if (request()->filled('stars'))
                    <a href="{{ route('company.ratings.index') }}" class="btn btn-light">{{ __('company/messages.common.clear') }}</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('company/messages.common.customer') }}</th>
                        <th>{{ __('company/messages.common.request') }}</th>
                        <th>{{ __('company/messages.ratings.stars') }}</th>
                        <th>{{ __('company/messages.ratings.answers') }}</th>
                        <th>{{ __('company/messages.ratings.comment') }}</th>
                        <th>{{ __('company/messages.ratings.date') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($ratings as $rating)
                        @php
                            $customerName = $rating->customer?->full_name
                                ?: ($rating->customer?->username ?: __('company/messages.common.customer'));
                        @endphp
                        <tr>
                            <td>
                                <div class="person-cell">
                                    <span class="person-avatar">{{ strtoupper(mb_substr($customerName, 0, 1)) }}</span>
                                    <span>
                                        <strong>{{ $customerName }}</strong>
                                        <small>{{ $rating->customer?->city ?: __('company/messages.common.city_not_set') }}</small>
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
                                        {{ $answer->question?->title_en ?? __('company/messages.ratings.question') }}:
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
                                {{ $rating->created_at?->translatedFormat('M d, Y') }}
                                <small class="cell-note">{{ $rating->created_at?->diffForHumans() }}</small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="dashboard-empty">
                                <strong>{{ __('company/messages.ratings.empty_title') }}</strong>
                                @if (request()->filled('stars'))
                                    {{ __('company/messages.ratings.empty_stars') }}
                                @else
                                    {{ __('company/messages.ratings.empty_body') }}
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

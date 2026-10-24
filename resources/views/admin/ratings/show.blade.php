@extends('admin.layouts.app')

@section('title', 'Rating Details')
@section('breadcrumb', 'Ratings / Details')
@section('eyebrow', 'CUSTOMER FEEDBACK')
@section('page-title', 'Rating details')
@section('page-description', 'The overall stars, each question answer and the comment for one rating.')

@section('page-actions')
    <a href="{{ route('admin.ratings.index') }}" class="btn btn-light">&larr; Back to ratings</a>
@endsection

@section('content')
    @php
        $customer = $rating->customer;
        $company = $rating->company;
        $customerName = $customer?->full_name ?: ($customer?->username ?: 'Customer #' . $rating->customer_id);
        $reference = '#Q-' . str_pad($rating->quote_id, 5, '0', STR_PAD_LEFT);
        $maxValue = \App\Models\RatingValue::MAX;
    @endphp

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Summary --}}
    <section class="quote-summary">
        <div class="quote-summary-item">
            <span>Overall stars</span>
            <strong>@include('partials.stars', ['value' => $rating->stars]) {{ $rating->stars }} / 5</strong>
        </div>

        <div class="quote-summary-item">
            <span>Company</span>
            <strong>{{ $company?->name ?? 'Company #' . $rating->company_id }}</strong>
        </div>

        <div class="quote-summary-item">
            <span>Request</span>
            <strong><a href="{{ route('admin.quote-requests.show', $rating->quote_id) }}">{{ $reference }}</a></strong>
        </div>

        <div class="quote-summary-item">
            <span>Status</span>
            <strong>
                <span class="pill {{ $rating->is_visible ? 'pill-green' : 'pill-purple' }}">
                    {{ $rating->is_visible ? 'Visible' : 'Hidden' }}
                </span>
            </strong>
        </div>
    </section>

    {{-- Comment --}}
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Comment</h2>
                <p>By {{ $customerName }} · {{ $rating->created_at?->format('M d, Y H:i') }}</p>
            </div>
        </div>
        <div class="panel-body">
            @if ($rating->comment)
                <p class="rating-comment">{{ $rating->comment }}</p>
            @else
                <p class="gray-text">The customer did not leave a comment.</p>
            @endif
        </div>
    </section>

    {{-- Question answers --}}
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Question answers</h2>
                <p>Each question is answered on a 1–{{ $maxValue }} scale.</p>
            </div>
        </div>
        <div class="panel-body">
            @if ($rating->answers->isNotEmpty())
                <div class="rating-answer-list">
                    @foreach ($rating->answers as $answer)
                        <div class="rating-answer">
                            <span>
                                <strong>{{ $answer->question?->title_en ?? 'Question #' . $answer->question_id }}</strong>
                                <small>
                                    {{ $answer->question?->question_en }}
                                    @if ($answer->question?->trashed())
                                        · question deleted
                                    @endif
                                </small>
                            </span>
                            <span>
                                <span class="rating-scale-dots" aria-hidden="true">
                                    @for ($i = 1; $i <= $maxValue; $i++)
                                        <i class="{{ $i <= $answer->value ? 'on' : '' }}"></i>
                                    @endfor
                                </span>
                                <strong>{{ $valueLabels[$answer->value] ?? $answer->value }}</strong>
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="gray-text">No question answers. The customer gave the overall stars only.</p>
            @endif
        </div>
    </section>

    {{-- Customer and actions --}}
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Customer</h2>
                <p>Contact details are only visible to admins.</p>
            </div>
        </div>
        <div class="panel-body">
            <div class="person-cell">
                <span class="person-avatar">{{ strtoupper(mb_substr($customerName, 0, 1)) }}</span>
                <span>
                    <strong>{{ $customerName }}</strong>
                    <small>{{ collect([$customer?->city, $customer?->phone, $customer?->email])->filter()->implode(' · ') ?: '—' }}</small>
                </span>
            </div>

            <div class="form-actions">
                <form action="{{ route('admin.ratings.visibility', $rating->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-light">
                        {{ $rating->is_visible ? 'Hide rating' : 'Show rating' }}
                    </button>
                </form>

                <form action="{{ route('admin.ratings.destroy', $rating->id) }}" method="POST"
                    onsubmit="return confirm('Delete this rating? The customer will be able to rate this request again.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete rating</button>
                </form>
            </div>
        </div>
    </section>
@endsection

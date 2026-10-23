@extends('admin.layouts.app')
@section('title', 'Rating Questions')
@section('breadcrumb', 'Rating Questions')
@section('eyebrow', 'CUSTOMER FEEDBACK')
@section('page-title', 'Rating Questions')
@section('page-description', 'Questions customers answer when they rate a company, like Price or Service.')
@section('page-actions')
    <a href="{{ route('admin.rating-values.index') }}" class="btn btn-light">Rating values</a>
    <a href="{{ route('admin.rating-questions.create') }}" class="btn btn-primary">+ Add Question</a>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Questions</h2>
                <p>
                    Active questions show in the app in this order. Each is answered as
                    {{ collect($valueLabels)->map(fn($label, $value) => "{$value} {$label}")->implode(' · ') }}.
                </p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Question</th>
                        <th>Arabic</th>
                        <th>Answers</th>
                        <th>Average</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($questions as $question)
                        @php $avg = $question->answers_avg_value !== null ? (float) $question->answers_avg_value : null; @endphp
                        <tr>
                            <td><strong>{{ $question->sort_order }}</strong></td>
                            <td>
                                <strong>{{ $question->title_en }}</strong>
                                <small class="cell-note">{{ $question->question_en ?: '—' }}</small>
                            </td>
                            <td dir="rtl" lang="ar">
                                <strong>{{ $question->title_ar }}</strong>
                                <small class="cell-note">{{ $question->question_ar ?: '—' }}</small>
                            </td>
                            <td>{{ number_format($question->answers_count) }}</td>
                            <td style="min-width:140px">
                                @if ($avg !== null)
                                    <strong>{{ number_format($avg, 1) }} / {{ \App\Models\RatingValue::MAX }}</strong>
                                    <div class="mini-progress">
                                        <span style="width: {{ round($avg / \App\Models\RatingValue::MAX * 100) }}%"></span>
                                    </div>
                                @else
                                    <span class="gray-text">No answers yet</span>
                                @endif
                            </td>
                            <td>
                                <span class="pill {{ $question->is_active ? 'pill-green' : 'pill-purple' }}">
                                    {{ $question->is_active ? 'Active' : 'Hidden from app' }}
                                </span>
                            </td>
                            <td>
                                <div class="company-actions">
                                    <a href="{{ route('admin.rating-questions.edit', $question->id) }}"
                                        class="row-action row-action-icon" title="Edit question"
                                        aria-label="Edit {{ $question->title_en }}">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                            aria-hidden="true">
                                            <path d="M12 20h9" />
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" />
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.rating-questions.destroy', $question->id) }}" method="POST"
                                        class="company-delete-form"
                                        onsubmit="return confirm('Delete the question &quot;{{ $question->title_en }}&quot;? Past answers are kept.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="row-action row-action-icon row-action-delete"
                                            title="Delete question" aria-label="Delete {{ $question->title_en }}">
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
                            <td colspan="7" class="dashboard-empty">
                                <strong>No rating questions yet.</strong>
                                Add questions like Price or Service. Customers can still give overall stars without them.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

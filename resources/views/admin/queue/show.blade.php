@extends('admin.layouts.app')

@section('title', 'Failed Job')
@section('breadcrumb', 'Background Jobs / Failed job')
@section('eyebrow', 'SYSTEM')
@section('page-title', $job->label)
@section('page-description', 'Failed ' . $job->failed_at->format('M d, Y H:i') . ' (' . $job->failed_at->diffForHumans() . ')')

@section('page-actions')
    <a href="{{ route('admin.queue.index') }}" class="btn btn-light">&larr; Back to background jobs</a>
@endsection

@section('content')
    <section class="quote-summary">
        <div class="quote-summary-item">
            <span>Job</span>
            <strong>{{ class_basename($job->class) }}</strong>
        </div>
        <div class="quote-summary-item">
            <span>Sent to</span>
            <strong>{{ $job->recipient ?: '—' }}</strong>
        </div>
        <div class="quote-summary-item">
            <span>Queue</span>
            <strong>{{ $job->connection }} / {{ $job->queue }}</strong>
        </div>
        <div class="quote-summary-item">
            <span>Tries</span>
            <strong>{{ $job->max_tries ? $job->max_tries . ' max' : '—' }}</strong>
        </div>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>What went wrong</h2>
                <p>{{ $job->error }}</p>
            </div>
        </div>
        <div class="panel-body">
            @if ($job->hint)
                <div class="alert alert-success" style="margin-top:0">💡 <strong>How to fix:</strong> {{ $job->hint }}</div>
            @endif

            <div class="form-actions" style="justify-content:flex-start">
                <form method="POST" action="{{ route('admin.queue.retry', $job->uuid) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">↻ Retry this job</button>
                </form>
                <form method="POST" action="{{ route('admin.queue.destroy', $job->uuid) }}"
                    onsubmit="return confirm('Delete this failed job? It will not be sent.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Full error</h2>
                <p>For the developer. ID: <code>{{ $job->uuid }}</code></p>
            </div>
        </div>
        <div class="panel-body">
            <pre class="job-trace">{{ $job->exception }}</pre>
        </div>
    </section>
@endsection

@extends('admin.layouts.app')

@section('title', 'Background Jobs')
@section('breadcrumb', 'Background Jobs')
@section('eyebrow', 'SYSTEM')
@section('page-title', 'Background Jobs')
@section('page-description', 'Emails and push notifications are sent in the background. See what is waiting, what failed and why, and retry.')

@section('page-actions')
    <a href="{{ route('admin.queue.index', request()->query()) }}" class="btn btn-light">↻ Refresh</a>
    @if ($overview['failed'] > 0)
        <form method="POST" action="{{ route('admin.queue.retry-all') }}" style="display:inline"
            onsubmit="return confirm('Retry all {{ $overview['failed'] }} failed jobs?');">
            @csrf
            <button type="submit" class="btn btn-primary">↻ Retry all failed</button>
        </form>
    @endif
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($overview['stuck'] > 0)
        <div class="alert alert-danger">
            <strong>{{ $overview['stuck'] }} {{ \Illuminate\Support\Str::plural('job', $overview['stuck']) }} waiting over
                {{ \App\Repositories\Admin\AdminRepository::STUCK_JOB_MINUTES }} minutes.</strong>
            The queue worker is probably not running, so emails and pushes are not going out. On the server run
            <code>php artisan queue:work</code> (or restart it in Supervisor / cron), then refresh this page.
        </div>
    @endif

    @include('partials.page-guide', [
        'key' => 'admin-background-jobs',
        'title' => 'How background jobs work',
        'intro' => 'Emails (welcome, offer decisions, contact form, reminders) and pushes are queued, then sent by the queue worker.',
        'steps' => [
            '<strong>Waiting</strong>: queued and about to be sent. If this number keeps growing, the worker is down.',
            '<strong>Failed</strong>: tried ' . 3 . ' times and gave up, e.g. a wrong email password (error 535).',
            '<strong>Retry</strong>: fix the cause first (the hint under each error tells you how), then retry.',
        ],
        'note' => 'Retried jobs run as soon as the queue worker picks them up.',
    ])

    <div class="metrics-strip">
        <div>
            <small>Waiting in queue</small>
            <strong class="{{ $overview['stuck'] ? 'warning' : '' }}">{{ number_format($overview['pending']) }}</strong>
        </div>
        <div>
            <small>Running now</small>
            <strong>{{ number_format($overview['running']) }}</strong>
        </div>
        <div>
            <small>Oldest waiting</small>
            <strong>{{ $overview['oldest_pending_at']?->diffForHumans(null, true) ?? '—' }}</strong>
        </div>
        <div>
            <small>Failed (all)</small>
            <strong class="{{ $overview['failed'] ? 'warning' : '' }}">{{ number_format($overview['failed']) }}</strong>
        </div>
        <div>
            <small>Failed today</small>
            <strong>{{ number_format($overview['failed_today']) }}</strong>
        </div>
    </div>

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Failed jobs</h2>
                <p>Newest first. Queue connection: <code>{{ $overview['connection'] }}</code></p>
            </div>

            <form method="GET" action="{{ route('admin.queue.index') }}" class="table-filters">
                <input class="table-search" type="search" name="search" value="{{ request('search') }}"
                    placeholder="Email address or error text" maxlength="100" aria-label="Search failed jobs">
                <button type="submit" class="btn btn-light">Search</button>
                @if (request()->filled('search'))
                    <a href="{{ route('admin.queue.index') }}" class="btn btn-light">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Job</th>
                        <th>Sent to</th>
                        <th>Error</th>
                        <th>Failed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($failedJobs as $job)
                        <tr>
                            <td>
                                <strong>{{ $job->label }}</strong>
                                <small class="cell-note">{{ class_basename($job->class) }} · queue "{{ $job->queue }}"</small>
                            </td>
                            <td>{{ $job->recipient ?: '—' }}</td>
                            <td>
                                <div class="job-error">{{ $job->error }}</div>
                                @if ($job->hint)
                                    <span class="job-hint">💡 {{ $job->hint }}</span>
                                @endif
                            </td>
                            <td style="white-space:nowrap">
                                {{ $job->failed_at->format('M d, Y H:i') }}
                                <small class="cell-note">{{ $job->failed_at->diffForHumans() }}</small>
                            </td>
                            <td>
                                <div class="company-actions">
                                    <a href="{{ route('admin.queue.show', $job->uuid) }}" class="row-action">Details</a>
                                    <form method="POST" action="{{ route('admin.queue.retry', $job->uuid) }}">
                                        @csrf
                                        <button type="submit" class="row-action">Retry</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.queue.destroy', $job->uuid) }}"
                                        onsubmit="return confirm('Delete this failed job? It will not be sent.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="row-action row-action-delete">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="dashboard-empty">
                                <strong>No failed jobs. ✓</strong>
                                Every email and push notification went out.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $failedJobs, 'label' => 'failed jobs'])

        @if ($overview['failed'] > 0)
            <div class="form-actions" style="padding:0 19px 16px">
                <form method="POST" action="{{ route('admin.queue.destroy-all') }}"
                    onsubmit="return confirm('Delete ALL failed jobs? None of them will be sent.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete all failed jobs</button>
                </form>
            </div>
        @endif
    </section>
@endsection

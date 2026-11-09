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

    @if ($automatic['overview']['scheduler_down'])
        <div class="alert alert-danger">
            <strong>Automatic notifications are not being sent.</strong>
            The scheduler has not run
            {{ $automatic['overview']['scheduler_last_run'] ? 'since ' . $automatic['overview']['scheduler_last_run']->diffForHumans() : 'yet' }},
            but {{ $automatic['overview']['active_schedules'] }}
            {{ $automatic['overview']['active_schedules'] === 1 ? 'schedule is' : 'schedules are' }} on.
            Add a cron job that runs every minute:
            <code>cd {{ base_path() }} &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code>
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

    {{-- Automatic notifications are sent by the scheduler, not the queue: each attempt is logged here. --}}
    @php
        $auto = $automatic['overview'];
        $runs = $automatic['runs'];
    @endphp
    <section class="panel table-panel" id="automatic-notifications">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Automatic notifications</h2>
                <p>
                    Every send by a schedule, newest first.
                    Scheduler last ran:
                    @if ($auto['scheduler_last_run'])
                        <strong class="{{ $auto['scheduler_down'] ? 'schedule-error' : '' }}">{{ $auto['scheduler_last_run']->diffForHumans() }}</strong>
                    @else
                        <strong class="{{ $auto['scheduler_down'] ? 'schedule-error' : '' }}">never</strong>
                    @endif
                    · {{ $auto['active_schedules'] }} {{ \Illuminate\Support\Str::plural('schedule', $auto['active_schedules']) }} on ·
                    <a href="{{ route('admin.notifications.schedules.index') }}">Manage schedules</a>
                </p>
            </div>

            <form method="GET" action="{{ route('admin.queue.index') }}#automatic-notifications" class="table-filters">
                @if (request()->filled('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
                <select class="select-control" name="run_status" aria-label="Filter automatic sends by status"
                    onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach (\App\Models\NotificationScheduleRun::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected(request('run_status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @if (request()->filled('run_status'))
                    <a href="{{ route('admin.queue.index', request()->except(['run_status', 'runs_page'])) }}#automatic-notifications"
                        class="btn btn-light">Clear</a>
                @endif
            </form>
        </div>

        <div class="metrics-strip" style="margin:0 19px 12px">
            <div><small>Done today</small><strong>{{ number_format($auto['done_today']) }}</strong></div>
            <div><small>Partly delivered today</small><strong>{{ number_format($auto['partial_today']) }}</strong></div>
            <div>
                <small>Failed today</small>
                <strong class="{{ $auto['failed_today'] ? 'warning' : '' }}">{{ number_format($auto['failed_today']) }}</strong>
            </div>
            <div><small>Failed (7 days)</small><strong>{{ number_format($auto['failed_week']) }}</strong></div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Schedule / message</th>
                        <th>Status</th>
                        <th>Delivery</th>
                        <th>Details</th>
                        <th>When</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($runs as $run)
                        @php
                            $schedule = $run->schedule;
                            // Retry = "Send next message now", only while the failed message is still the next one.
                            $canRetry = $run->status === 'failed'
                                && $schedule
                                && in_array($run->id, $automatic['retryableIds'], true)
                                && $run->message_id
                                && $schedule->nextMessage()?->id === $run->message_id;
                        @endphp
                        <tr>
                            <td>
                                @if ($schedule)
                                    <a href="{{ route('admin.notifications.schedules.edit', $schedule->id) }}"><strong>{{ $run->schedule_name }}</strong></a>
                                @else
                                    <strong>{{ $run->schedule_name }}</strong> <small class="cell-note">(deleted)</small>
                                @endif
                                <small class="cell-note">
                                    @if ($run->message_order)
                                        Message {{ $run->message_order }}: {{ \Illuminate\Support\Str::limit($run->message_title, 50) }}
                                    @else
                                        No message
                                    @endif
                                </small>
                            </td>
                            <td>
                                <span class="pill {{ \App\Models\NotificationScheduleRun::STATUS_PILLS[$run->status] ?? 'pill-yellow' }}">
                                    {{ \App\Models\NotificationScheduleRun::STATUSES[$run->status] ?? ucfirst($run->status) }}
                                </span>
                                <small class="cell-note">{{ $run->trigger === 'manual' ? 'Sent by admin' : 'Automatic' }}</small>
                            </td>
                            <td style="white-space:nowrap">
                                @if ($run->notification_id)
                                    {{ number_format($run->recipients) }} {{ \Illuminate\Support\Str::plural('customer', $run->recipients) }}
                                    <small class="cell-note">{{ number_format($run->delivered) }} of {{ number_format($run->devices) }} devices reached</small>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($run->error)
                                    <div class="job-error">{{ $run->error }}</div>
                                @else
                                    <span class="cell-note">Sent to every device.</span>
                                @endif
                            </td>
                            <td style="white-space:nowrap">
                                {{ $run->created_at->format('M d, Y H:i') }}
                                <small class="cell-note">{{ $run->created_at->diffForHumans() }}</small>
                            </td>
                            <td>
                                <div class="company-actions">
                                    @if ($run->notification_id)
                                        <a href="{{ route('admin.notifications.show', $run->notification_id) }}" class="row-action">View</a>
                                    @endif
                                    @if ($canRetry)
                                        <form method="POST" action="{{ route('admin.notifications.schedules.send-now', $schedule->id) }}"
                                            onsubmit="return confirm('Send message {{ $run->message_order }} of this schedule again now?');">
                                            @csrf
                                            <button type="submit" class="row-action">Retry</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="dashboard-empty">
                                @if (request()->filled('run_status'))
                                    <strong>No automatic sends with this status.</strong>
                                @else
                                    <strong>No automatic notifications sent yet.</strong>
                                    Sends appear here once a schedule is on and its day and time come.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $runs, 'label' => 'automatic sends'])
    </section>
@endsection

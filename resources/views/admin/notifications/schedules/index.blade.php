@extends('admin.layouts.app')

@section('title', 'Automatic Notifications')
@section('breadcrumb', 'Automatic Notifications')
@section('eyebrow', 'CUSTOMER COMMUNICATION')
@section('page-title', 'Automatic Notifications')
@section('page-description', 'Messages sent to customers one by one, on the days and at the time you choose.')

@section('page-actions')
    <a href="{{ route('admin.notifications.index') }}" class="btn btn-light">← Notifications</a>
    <a href="{{ route('admin.notifications.schedules.create') }}" class="btn btn-primary">+ New schedule</a>
@endsection

@section('content')
    @php
        $stateLabels = ['active' => 'On', 'paused' => 'Paused', 'finished' => 'Finished', 'ended' => 'Ended', 'empty' => 'No messages'];
        $stateClasses = ['active' => 'pill-green', 'paused' => 'pill-yellow', 'finished' => 'pill-blue', 'ended' => 'pill-purple', 'empty' => 'pill-red'];
    @endphp

    @include('partials.page-guide', [
        'key' => 'admin-notification-schedules',
        'title' => 'How automatic notifications work',
        'intro' => 'A schedule holds a list of messages. Each time it runs, it sends the next message in the list as a push notification.',
        'steps' => [
            '<strong>Days</strong>: tick the weekdays (Saturday to Friday) when sending is allowed.',
            '<strong>Every N days</strong>: the minimum gap between two messages. All days ticked + every 2 days = one message every 2 days.',
            '<strong>Time</strong>: the local time the message goes out (Sudan time by default).',
            '<strong>After the last message</strong>: stop (the schedule switches itself off) or start again from message 1.',
            '<strong>Send next now</strong> sends the next message immediately and counts as today\'s send.',
        ],
        'note' => 'Sending needs the server cron "php artisan schedule:run" every minute. Sent messages also appear in Notifications history.',
    ])

    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="metrics-strip">
        <div><small>Schedules</small><strong>{{ number_format($metrics['total']) }}</strong></div>
        <div><small>Switched on</small><strong>{{ number_format($metrics['active']) }}</strong></div>
        <div><small>Messages</small><strong>{{ number_format($metrics['messages']) }}</strong></div>
        <div><small>Notifications sent</small><strong>{{ number_format($metrics['sent']) }}</strong></div>
    </div>

    <section class="panel table-panel">
        <div class="panel-heading">
            <h2>Schedules</h2>
            <p>Times are local to each schedule's time zone.</p>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Schedule</th>
                        <th>Days</th>
                        <th>Frequency</th>
                        <th>Audience</th>
                        <th>Progress</th>
                        <th>Next send</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schedules as $schedule)
                        @php
                            $state = $schedule->state();
                            $next = $schedule->nextMessage();
                            $nextAt = $schedule->is_active ? $schedule->nextSendAt() : null;
                        @endphp
                        <tr>
                            <td>
                                <a
                                    href="{{ route('admin.notifications.schedules.edit', $schedule->id) }}"><strong>{{ $schedule->name }}</strong></a>
                                <small
                                    class="cell-note">{{ \App\Models\NotificationSchedule::TYPES[$schedule->notification_type] ?? $schedule->notification_type }}</small>
                            </td>
                            <td class="cell-wrap">{{ $schedule->daysLabel(true) }}</td>
                            <td style="white-space:nowrap">
                                {{ $schedule->frequencyLabel() }}
                                <small class="cell-note">{{ $schedule->timezone }}</small>
                            </td>
                            <td>{{ $schedule->audience_type === 'city' ? 'City: ' . $schedule->audience_city : 'All app customers' }}
                            </td>
                            <td>
                                {{ number_format($schedule->sent_count) }} sent ·
                                {{ $schedule->active_messages_count }}/{{ $schedule->messages_count }} messages
                                <small class="cell-note">
                                    @if ($next)
                                        Next: {{ $next->sort_order }}. {{ \Illuminate\Support\Str::limit($next->title_en, 40) }}
                                    @else
                                        No message left
                                    @endif
                                </small>
                            </td>
                            <td style="white-space:nowrap">
                                @if ($nextAt)
                                    {{ $nextAt->format('D, M j · H:i') }}
                                @else
                                    <span class="cell-note">—</span>
                                @endif
                                @if ($schedule->last_sent_at)
                                    <small class="cell-note">Last:
                                        {{ $schedule->localNow($schedule->last_sent_at)->format('M j, H:i') }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="pill {{ $stateClasses[$state] }}">{{ $stateLabels[$state] }}</span>
                                @if ($schedule->last_error)
                                    <small class="cell-note schedule-error" title="{{ $schedule->last_error }}">⚠
                                        {{ \Illuminate\Support\Str::limit($schedule->last_error, 50) }}</small>
                                @endif
                            </td>
                            <td>
                                <div class="company-actions">
                                    <a href="{{ route('admin.notifications.schedules.edit', $schedule->id) }}"
                                        class="row-action row-action-icon" aria-label="Edit {{ $schedule->name }}" title="Edit">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                            aria-hidden="true">
                                            <path d="M12 20h9" />
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" />
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.notifications.schedules.toggle', $schedule->id) }}"
                                        method="POST" class="company-delete-form">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="row-action row-action-icon"
                                            title="{{ $schedule->is_active ? 'Pause' : 'Turn on' }}"
                                            aria-label="{{ $schedule->is_active ? 'Pause' : 'Turn on' }} {{ $schedule->name }}">
                                            @if ($schedule->is_active)
                                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                                    aria-hidden="true">
                                                    <path d="M9 5v14M15 5v14" />
                                                </svg>
                                            @else
                                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                                    aria-hidden="true">
                                                    <path d="m7 4 13 8-13 8Z" />
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                    @if ($next)
                                        <form action="{{ route('admin.notifications.schedules.send-now', $schedule->id) }}"
                                            method="POST" class="company-delete-form"
                                            onsubmit="return confirm('Send message {{ $next->sort_order }} of this schedule to its customers now?');">
                                            @csrf
                                            <button type="submit" class="row-action row-action-icon" title="Send next message now"
                                                aria-label="Send next message of {{ $schedule->name }} now">
                                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                                    aria-hidden="true">
                                                    <path d="M22 2 11 13" />
                                                    <path d="M22 2 15 22l-4-9-9-4Z" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.notifications.schedules.destroy', $schedule->id) }}"
                                        method="POST" class="company-delete-form"
                                        onsubmit="return confirm('Delete this schedule and its messages? Notifications already sent are kept.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="row-action row-action-icon row-action-delete"
                                            aria-label="Delete {{ $schedule->name }}" title="Delete">
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                                aria-hidden="true">
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
                            <td colspan="8" class="text-center">No schedules yet.
                                <a href="{{ route('admin.notifications.schedules.create') }}">Create the first one</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
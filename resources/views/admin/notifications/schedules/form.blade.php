@extends('admin.layouts.app')

@use('App\Models\NotificationSchedule')
@use('Illuminate\Support\Str')

@php
    $isEdit = (bool) $schedule;
    $savedMessages = $messages->keyBy('id');

    // Rows to show: what was just posted (after a validation error), else the saved messages.
    $rows = old('messages') !== null
        ? collect(old('messages'))->values()->map(fn ($row) => [
            'id' => $row['id'] ?? null,
            'is_active' => !empty($row['is_active']),
            'title_en' => $row['title_en'] ?? '',
            'title_ar' => $row['title_ar'] ?? '',
            'message_en' => $row['message_en'] ?? '',
            'message_ar' => $row['message_ar'] ?? '',
        ])
        : $messages->map(fn ($message) => [
            'id' => $message->id,
            'is_active' => $message->is_active,
            'title_en' => $message->title_en,
            'title_ar' => $message->title_ar,
            'message_en' => $message->message_en,
            'message_ar' => $message->message_ar,
        ]);

    if ($rows->isEmpty()) {
        $rows = collect([['id' => null, 'is_active' => true, 'title_en' => '', 'title_ar' => '', 'message_en' => '', 'message_ar' => '']]);
    }

    $selectedDays = array_map('intval', (array) old('days', $schedule?->dayNumbers() ?? array_keys(NotificationSchedule::WEEKDAYS)));
    $timezone = old('timezone', $schedule?->timezone ?? NotificationSchedule::DEFAULT_TIMEZONE);
    $audienceType = old('audience_type', $schedule?->audience_type ?? 'all');
    $nextAt = $isEdit && $schedule->is_active ? $schedule->nextSendAt() : null;
@endphp

@section('title', $isEdit ? 'Edit Schedule' : 'New Schedule')
@section('breadcrumb', 'Automatic Notifications')
@section('eyebrow', 'CUSTOMER COMMUNICATION')
@section('page-title', $isEdit ? $schedule->name : 'New automatic notification')
@section('page-description', 'Choose the days, the time and the messages. Each run sends the next message in the list.')

@section('page-actions')
    <a href="{{ route('admin.notifications.schedules.index') }}" class="btn btn-light">← All schedules</a>
@endsection

@section('content')
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following errors:</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if ($isEdit)
        <section class="panel">
            <div class="panel-heading">
                <h2>Progress</h2>
                <p>What was sent and what goes out next.</p>
            </div>
            <div class="panel-body">
                <div class="metrics-strip schedule-progress">
                    <div><small>Status</small><strong>{{ ucfirst($schedule->state()) }}</strong></div>
                    <div><small>Next message</small><strong>{{ $nextMessage ? $nextMessage->sort_order . '. ' . Str::limit($nextMessage->title_en, 32) : 'None left' }}</strong></div>
                    <div><small>Next send</small><strong>{{ $nextAt?->format('D, M j · H:i') ?? ($schedule->is_active ? '—' : 'Paused') }}</strong></div>
                    <div><small>Sent so far</small><strong>{{ number_format($schedule->sent_count) }}</strong></div>
                </div>

                @if ($schedule->last_error)
                    <div class="alert alert-danger">Last attempt failed: {{ $schedule->last_error }}</div>
                @endif

                <div class="schedule-progress-actions">
                    @if ($nextMessage)
                        <form method="POST" action="{{ route('admin.notifications.schedules.send-now', $schedule->id) }}"
                            onsubmit="return confirm('Send message {{ $nextMessage->sort_order }} to the audience now? It counts as today\'s send.');">
                            @csrf
                            <button type="submit" class="btn btn-primary">Send next message now</button>
                        </form>
                    @endif
                    @if ($schedule->last_message_id || $schedule->last_sent_on)
                        <form method="POST" action="{{ route('admin.notifications.schedules.reset', $schedule->id) }}"
                            onsubmit="return confirm('Start again from message 1? The history is kept.');">
                            @csrf
                            <button type="submit" class="btn btn-light">Restart from message 1</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.notifications.schedules.toggle', $schedule->id) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-light">{{ $schedule->is_active ? 'Pause schedule' : 'Turn schedule on' }}</button>
                    </form>
                </div>

                @if ($recentSends->isNotEmpty())
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead><tr><th>Sent</th><th>Message</th><th>Recipients</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($recentSends as $sent)
                                    <tr>
                                        <td style="white-space:nowrap">{{ $sent->published_at ? $schedule->localNow($sent->published_at)->format('D, M j · H:i') : '—' }}</td>
                                        <td>{{ $sent->title_en }}</td>
                                        <td>{{ number_format($sent->recipients_count) }}</td>
                                        <td><a href="{{ route('admin.notifications.show', $sent->id) }}">View</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    @endif

    <form method="POST" id="scheduleForm"
        action="{{ $isEdit ? route('admin.notifications.schedules.update', $schedule->id) : route('admin.notifications.schedules.store') }}">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <section class="panel">
            <div class="panel-heading"><h2>Schedule</h2><p>A name for you, and how it appears in the notifications history.</p></div>
            <div class="panel-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Name <b style="color:red">*</b></label>
                        <input class="form-control" id="name" name="name" type="text" maxlength="150" required
                            value="{{ old('name', $schedule?->name) }}" placeholder="e.g. App testers: 14 messages">
                    </div>
                    <div class="form-group">
                        <label for="notification_type">Notification type <b style="color:red">*</b></label>
                        <select class="select-control" id="notification_type" name="notification_type" required>
                            @foreach (NotificationSchedule::TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('notification_type', $schedule?->notification_type ?? 'general') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <input type="hidden" name="is_active" value="0">
                        <label class="schedule-check">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $schedule?->is_active ?? false))>
                            <span><strong>Schedule is on</strong> — messages are sent automatically on the chosen days.</span>
                        </label>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading"><h2>When to send</h2><p id="scheduleSummary" class="schedule-summary"></p></div>
            <div class="panel-body">
                <div class="form-group full-width">
                    <label>Days of the week <b style="color:red">*</b></label>
                    <div class="schedule-days" role="group" aria-label="Days of the week">
                        @foreach (NotificationSchedule::WEEKDAYS as $number => $dayName)
                            <label class="schedule-day">
                                <input type="checkbox" name="days[]" value="{{ $number }}" data-day-name="{{ $dayName }}"
                                    @checked(in_array($number, $selectedDays, true))>
                                <span>{{ $dayName }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="schedule-presets">
                        <button type="button" class="btn btn-light btn-sm" data-days="6,0,1,2,3,4,5">All days</button>
                        <button type="button" class="btn btn-light btn-sm" data-days="6,1,3">Sat · Mon · Wed</button>
                        <button type="button" class="btn btn-light btn-sm" data-days="0,1,2,3,4">Sun – Thu</button>
                        <button type="button" class="btn btn-light btn-sm" data-days="">Clear</button>
                    </div>
                </div>

                <div class="form-grid" style="margin-top:22px">
                    <div class="form-group">
                        <label for="every_days">Send every … days <b style="color:red">*</b></label>
                        <input class="form-control" id="every_days" name="every_days" type="number" min="1" max="60" required
                            value="{{ old('every_days', $schedule?->every_days ?? 2) }}">
                        <small class="form-hint">1 = every ticked day. 2 = at least 2 days between messages. If the day is not ticked, it waits for the next ticked day.</small>
                    </div>
                    <div class="form-group">
                        <label for="send_time">Time <b style="color:red">*</b></label>
                        <input class="form-control" id="send_time" name="send_time" type="time" required
                            value="{{ old('send_time', $schedule?->send_time ?? '10:00') }}">
                    </div>
                    <div class="form-group">
                        <label for="timezone">Time zone <b style="color:red">*</b></label>
                        <select class="select-control" id="timezone" name="timezone" required>
                            @foreach (\DateTimeZone::listIdentifiers() as $zone)
                                <option value="{{ $zone }}" @selected($timezone === $zone)>{{ $zone }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="after_last">After the last message <b style="color:red">*</b></label>
                        <select class="select-control" id="after_last" name="after_last" required>
                            @foreach (NotificationSchedule::AFTER_LAST as $value => $label)
                                <option value="{{ $value }}" @selected(old('after_last', $schedule?->after_last ?? 'stop') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="starts_on">Start date</label>
                        <input class="form-control" id="starts_on" name="starts_on" type="date"
                            value="{{ old('starts_on', $schedule?->starts_on?->toDateString()) }}">
                        <small class="form-hint">Empty = start on the next chosen day.</small>
                    </div>
                    <div class="form-group">
                        <label for="ends_on">End date</label>
                        <input class="form-control" id="ends_on" name="ends_on" type="date"
                            value="{{ old('ends_on', $schedule?->ends_on?->toDateString()) }}">
                        <small class="form-hint">Empty = no end date.</small>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading"><h2>Audience</h2><p>Only customers who use the app (have a device registered) receive pushes.</p></div>
            <div class="panel-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="audience_type">Send to <b style="color:red">*</b></label>
                        <select class="select-control" id="audience_type" name="audience_type" required>
                            <option value="all" @selected($audienceType === 'all')>All app customers ({{ number_format($appCustomerCount) }})</option>
                            <option value="city" @selected($audienceType === 'city')>Customers in one city</option>
                        </select>
                    </div>
                    <div class="form-group" id="cityGroup" @if ($audienceType !== 'city') hidden @endif>
                        <label for="audience_city">City <b style="color:red">*</b></label>
                        <select class="select-control" id="audience_city" name="audience_city">
                            <option value="">Choose a city</option>
                            @foreach ($cities as $city => $count)
                                <option value="{{ $city }}" @selected(old('audience_city', $schedule?->audience_city) === $city)>{{ $city }} ({{ $count }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading table-toolbar">
                <div>
                    <h2>Messages</h2>
                    <p>Sent in this order, one per run. Untick "Active" to skip a message without deleting it.</p>
                </div>
                <button type="button" class="btn btn-light" id="addMessage">+ Add message</button>
            </div>
            <div class="panel-body">
                <div id="messageList" class="schedule-messages">
                    @foreach ($rows as $index => $row)
                        @php $saved = $row['id'] ? $savedMessages->get((int) $row['id']) : null; @endphp
                        <div class="schedule-message" data-message-row>
                            <input type="hidden" name="messages[{{ $index }}][id]" value="{{ $row['id'] }}">
                            <div class="schedule-message-head">
                                <strong>Message <span data-number>{{ $index + 1 }}</span></strong>
                                @if ($saved && $nextMessage && $nextMessage->id === $saved->id)
                                    <span class="pill pill-blue">Next</span>
                                @endif
                                @if ($saved && $saved->sent_count)
                                    <small class="cell-note">Sent {{ $saved->sent_count }}× · last {{ $schedule->localNow($saved->last_sent_at)->format('M j') }}</small>
                                @endif
                                <span class="schedule-message-tools">
                                    <input type="hidden" name="messages[{{ $index }}][is_active]" value="0">
                                    <label class="schedule-check"><input type="checkbox" name="messages[{{ $index }}][is_active]" value="1" @checked($row['is_active'])> Active</label>
                                    <button type="button" class="btn btn-light btn-sm" data-move="up" title="Move up" aria-label="Move up">↑</button>
                                    <button type="button" class="btn btn-light btn-sm" data-move="down" title="Move down" aria-label="Move down">↓</button>
                                    <button type="button" class="btn btn-light btn-sm schedule-remove" data-remove title="Remove" aria-label="Remove message">✕</button>
                                </span>
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Title (English) <b style="color:red">*</b></label>
                                    <input class="form-control" name="messages[{{ $index }}][title_en]" type="text" maxlength="180" required value="{{ $row['title_en'] }}">
                                </div>
                                <div class="form-group">
                                    <label>Title (Arabic)</label>
                                    <input class="form-control" name="messages[{{ $index }}][title_ar]" type="text" maxlength="180" dir="rtl" lang="ar" value="{{ $row['title_ar'] }}">
                                </div>
                                <div class="form-group">
                                    <label>Message (English) <b style="color:red">*</b></label>
                                    <textarea class="form-control" name="messages[{{ $index }}][message_en]" rows="3" maxlength="2000" required>{{ $row['message_en'] }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Message (Arabic)</label>
                                    <textarea class="form-control" name="messages[{{ $index }}][message_ar]" rows="3" maxlength="2000" dir="rtl" lang="ar">{{ $row['message_ar'] }}</textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <template id="messageTemplate">
                    <div class="schedule-message" data-message-row>
                        <input type="hidden" name="messages[__i__][id]" value="">
                        <div class="schedule-message-head">
                            <strong>Message <span data-number></span></strong>
                            <span class="pill pill-orange">New</span>
                            <span class="schedule-message-tools">
                                <input type="hidden" name="messages[__i__][is_active]" value="0">
                                <label class="schedule-check"><input type="checkbox" name="messages[__i__][is_active]" value="1" checked> Active</label>
                                <button type="button" class="btn btn-light btn-sm" data-move="up" title="Move up" aria-label="Move up">↑</button>
                                <button type="button" class="btn btn-light btn-sm" data-move="down" title="Move down" aria-label="Move down">↓</button>
                                <button type="button" class="btn btn-light btn-sm schedule-remove" data-remove title="Remove" aria-label="Remove message">✕</button>
                            </span>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Title (English) <b style="color:red">*</b></label>
                                <input class="form-control" name="messages[__i__][title_en]" type="text" maxlength="180" required>
                            </div>
                            <div class="form-group">
                                <label>Title (Arabic)</label>
                                <input class="form-control" name="messages[__i__][title_ar]" type="text" maxlength="180" dir="rtl" lang="ar">
                            </div>
                            <div class="form-group">
                                <label>Message (English) <b style="color:red">*</b></label>
                                <textarea class="form-control" name="messages[__i__][message_en]" rows="3" maxlength="2000" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Message (Arabic)</label>
                                <textarea class="form-control" name="messages[__i__][message_ar]" rows="3" maxlength="2000" dir="rtl" lang="ar"></textarea>
                            </div>
                        </div>
                    </div>
                </template>

                <button type="button" class="btn btn-light" id="addMessageBottom" style="margin-top:16px">+ Add message</button>
            </div>
        </section>

        <section class="panel">
            <div class="panel-body">
                <div class="form-actions">
                    <a href="{{ route('admin.notifications.schedules.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Save schedule' : 'Create schedule' }}</button>
                </div>
            </div>
        </section>
    </form>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('scheduleForm');
            const list = document.getElementById('messageList');
            const template = document.getElementById('messageTemplate');
            const dayBoxes = Array.from(form.querySelectorAll('input[name="days[]"]'));
            const summary = document.getElementById('scheduleSummary');

            // Keep messages[0..n] in on-screen order, so the server saves that order.
            function renumber() {
                list.querySelectorAll('[data-message-row]').forEach(function (row, index) {
                    row.querySelectorAll('[name^="messages["]').forEach(function (field) {
                        field.name = field.name.replace(/^messages\[[^\]]*\]/, 'messages[' + index + ']');
                    });
                    row.querySelector('[data-number]').textContent = index + 1;
                });
            }

            function addMessage() {
                const row = template.content.firstElementChild.cloneNode(true);
                list.appendChild(row);
                renumber();
                row.querySelector('input[type="text"]').focus();
            }

            document.getElementById('addMessage').addEventListener('click', addMessage);
            document.getElementById('addMessageBottom').addEventListener('click', addMessage);

            list.addEventListener('click', function (event) {
                const button = event.target.closest('button');
                if (!button) return;
                const row = button.closest('[data-message-row]');

                if (button.hasAttribute('data-remove')) {
                    if (list.querySelectorAll('[data-message-row]').length === 1) {
                        alert('A schedule needs at least one message.');
                        return;
                    }
                    if (confirm('Remove this message? It is deleted when you save.')) {
                        row.remove();
                    }
                } else if (button.dataset.move === 'up' && row.previousElementSibling) {
                    list.insertBefore(row, row.previousElementSibling);
                } else if (button.dataset.move === 'down' && row.nextElementSibling) {
                    list.insertBefore(row.nextElementSibling, row);
                }
                renumber();
            });

            // Day presets
            form.querySelectorAll('[data-days]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const days = button.dataset.days === '' ? [] : button.dataset.days.split(',');
                    dayBoxes.forEach(function (box) { box.checked = days.includes(box.value); });
                    updateSummary();
                });
            });

            // Audience: show the city list only for "one city"
            const audience = document.getElementById('audience_type');
            const cityGroup = document.getElementById('cityGroup');
            const city = document.getElementById('audience_city');
            function updateAudience() {
                const isCity = audience.value === 'city';
                cityGroup.hidden = !isCity;
                city.required = isCity;
            }
            audience.addEventListener('change', updateAudience);
            updateAudience();

            // Plain-language summary of the schedule
            function updateSummary() {
                const days = dayBoxes.filter(function (box) { return box.checked; })
                    .map(function (box) { return box.dataset.dayName; });
                const every = parseInt(document.getElementById('every_days').value, 10) || 1;
                const time = document.getElementById('send_time').value || '--:--';
                const zone = document.getElementById('timezone').value;

                if (days.length === 0) {
                    summary.textContent = 'Choose at least one day.';
                    return;
                }

                const dayText = days.length === 7 ? 'any day of the week' : days.join(', ');
                summary.textContent = every === 1
                    ? 'One message on ' + dayText + ' at ' + time + ' (' + zone + ').'
                    : 'One message every ' + every + ' days, on ' + dayText + ', at ' + time + ' (' + zone + ').';
            }

            dayBoxes.forEach(function (box) { box.addEventListener('change', updateSummary); });
            ['every_days', 'send_time', 'timezone'].forEach(function (id) {
                document.getElementById(id).addEventListener('input', updateSummary);
                document.getElementById(id).addEventListener('change', updateSummary);
            });
            updateSummary();

            form.addEventListener('submit', function (event) {
                renumber();
                if (!dayBoxes.some(function (box) { return box.checked; })) {
                    event.preventDefault();
                    alert('Choose at least one day of the week.');
                }
            });
        });
    </script>
@endsection

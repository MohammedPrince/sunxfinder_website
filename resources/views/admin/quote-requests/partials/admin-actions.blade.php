{{-- Admin actions for one request: nudge companies, reassign, cancel. Hidden when printing. --}}
@php
    $isOpen = $quote->status === 'pending' && !$quote->project;
    $hasOffers = $quote->companyQuotations->isNotEmpty();
    $cooldownHours = \App\Repositories\Admin\AdminRepository::NUDGE_COOLDOWN_HOURS;
@endphp

<div class="no-print">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <section class="panel quote-panel">
        <div class="panel-heading quote-section-heading">
            <div>
                <h2>Admin actions</h2>
                <p>
                    @if ($isOpen)
                        Help this request get offers, send it to the right company, or close it.
                    @else
                        This request is {{ $quote->status === 'pending' ? 'in progress' : $quote->status }}. Actions are only
                        available while it is open (no accepted offer).
                    @endif
                </p>
            </div>
        </div>

        @if ($isOpen)
            <div class="panel-body">
                <div class="quote-admin-actions">
                    {{-- Nudge companies --}}
                    <div class="quote-admin-action">
                        <strong>Remind companies</strong>
                        <small>
                            Email {{ $quote->company_id ? 'the assigned company' : 'every approved company' }} that has
                            not sent an offer yet. {{ $hasOffers ? '' : 'No offers so far.' }}
                        </small>

                        @if ($nudgedAt)
                            <span class="pill pill-purple">Reminded {{ $nudgedAt->diffForHumans() }}</span>
                            <small class="cell-note">Next reminder after {{ $nudgedAt->copy()->addHours($cooldownHours)->format('M d, H:i') }}.</small>
                        @else
                            <form method="POST" action="{{ route('admin.quote-requests.nudge', $quote->id) }}"
                                onsubmit="return confirm('Email a reminder to companies about this request?');">
                                @csrf
                                <button type="submit" class="btn btn-primary">📣 Nudge companies</button>
                            </form>
                        @endif
                    </div>

                    {{-- Reassign --}}
                    <div class="quote-admin-action">
                        <strong>Reassign</strong>
                        <small>
                            Now: <b>{{ $quote->company?->name ?? 'open to all companies' }}</b>. A chosen company is
                            emailed straight away.
                        </small>

                        <form method="POST" action="{{ route('admin.quote-requests.reassign', $quote->id) }}" class="quote-admin-inline">
                            @csrf
                            <select name="company_id" class="select-control" aria-label="Send request to">
                                <option value="" @selected(!$quote->company_id)>Open to all companies</option>
                                @foreach ($assignableCompanies as $option)
                                    <option value="{{ $option->id }}" @selected((int) $quote->company_id === (int) $option->id)>
                                        {{ $option->name }}{{ $option->city ? ' · ' . $option->city : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-light">Save</button>
                        </form>
                    </div>

                    {{-- Cancel --}}
                    <div class="quote-admin-action quote-admin-danger">
                        <strong>Cancel request</strong>
                        <small>For spam, duplicates or requests the customer no longer needs. Waiting offers are withdrawn.</small>

                        <form method="POST" action="{{ route('admin.quote-requests.cancel', $quote->id) }}"
                            onsubmit="return confirm('Cancel this request? Companies can no longer send offers on it.');">
                            @csrf
                            <input class="form-control" type="text" name="reason" maxlength="300" required
                                placeholder="Reason, e.g. spam / duplicate of #Q-00012" value="{{ old('reason') }}">
                            <label class="quote-admin-check">
                                <input type="hidden" name="notify_customer" value="0">
                                <input type="checkbox" name="notify_customer" value="1" @checked(old('notify_customer', '1') === '1')>
                                Tell the customer in the app
                            </label>
                            <button type="submit" class="btn btn-danger">Cancel request</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </section>
</div>

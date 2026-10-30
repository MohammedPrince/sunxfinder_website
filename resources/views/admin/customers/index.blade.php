@extends('admin.layouts.app')

@section('title', 'Customers')
@section('breadcrumb', 'Customers')
@section('eyebrow', 'CUSTOMER DIRECTORY')
@section('page-title', 'Customers')
@section('page-description', 'Review and manage customer accounts registered on SunXFinder.')

@section('page-actions')
    <a href="{{ route('admin.notifications.campaign.create') }}" class="btn btn-light">📣 Send offers &amp; news</a>
    <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">+ Add New Customer</a>
@endsection

@section('content')

    {{-- Success message --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
        </div>
    @endif

    {{-- Error message --}}
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible">
            <strong>Error!</strong> {{ session('error') }}
            <button type="button" class="alert-close" onclick="this.parentElement.remove()"
                aria-label="Close">&times;</button>
        </div>
    @endif

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following errors:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Customer metrics --}}
    <div class="metrics-strip">
        <div>
            <small>All customers</small>
            <strong>{{ $totalCount }}</strong>
        </div>

        <div>
            <small>Active</small>
            <strong>{{ $activeCount }}</strong>
        </div>

        <div>
            <small>Pending</small>
            <strong>{{ $pendingCount }}</strong>
        </div>

        <div>
            <small>Inactive</small>
            <strong>{{ $inactiveCount }}</strong>
        </div>
    </div>

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Customer directory</h2>
                <p>Manage registered customer accounts</p>
            </div>

            {{-- Status filters on the server (all pages); search still filters the current page. --}}
            <form method="GET" action="{{ route('admin.customers.index') }}" class="table-filters">
                <select class="select-control" name="status" aria-label="Customer status" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach (['active' => 'Active', 'pending' => 'Pending approval', 'suspended' => 'Suspended', 'deleted' => 'Deleted'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                {{-- Live search; Enter must not submit the status form and wipe the text. --}}
                <input class="table-search" type="search" placeholder="Search customers…" data-table-search
                    onkeydown="if (event.key === 'Enter') event.preventDefault();">

                @if (request()->filled('status'))
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-light">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table js-data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>City</th>
                        <th title="Company whose offer the customer accepted most recently">Last company</th>
                        <th>Joined</th>
                        <th>Last login</th>
                        <th>Status</th>
                        <th>App</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            {{-- Customer information --}}
                            <td>
                                <div class="company-cell">
                                    <span class="company-avatar solar">
                                        {{ strtoupper(substr($customer->full_name ?: $customer->username, 0, 1)) }}
                                    </span>

                                    <span>
                                        <strong>{{ $customer->full_name ?: '—' }}</strong>
                                        <small>{{ $customer->username }}</small>
                                    </span>
                                </div>
                            </td>

                            {{-- Phone --}}
                            <td>{{ $customer->phone ?: '—' }}</td>

                            {{-- Email --}}
                            <td>{{ $customer->email ?: '—' }}</td>

                            {{-- City --}}
                            <td>{{ $customer->city ?: '—' }}</td>

                            {{-- Last company: whose offer the customer accepted most recently --}}
                            <td>
                                @if ($customer->last_company_id)
                                    <a href="{{ route('admin.companies.edit', $customer->last_company_id) }}">
                                        <strong>{{ $customer->last_company_name }}</strong>
                                    </a>
                                    <small style="display:block">
                                        {{ \Illuminate\Support\Carbon::parse($customer->last_company_at)->format('M d, Y') }}
                                    </small>
                                @else
                                    —
                                @endif
                            </td>

                            {{-- Registration date --}}
                            <td>
                                {{ $customer->created_at?->format('M d, Y') ?? '—' }}
                            </td>

                            {{-- Last app login --}}
                            <td>
                                @if ($customer->last_login_at)
                                    <span title="{{ $customer->last_login_at->format('M d, Y H:i') }}">
                                        {{ $customer->last_login_at->format('M d, Y') }}
                                    </span>
                                    <small class="cell-note">{{ $customer->last_login_at->diffForHumans() }}</small>
                                @else
                                    <span class="cell-note">Never</span>
                                @endif

                                @if ($customer->tokens_count)
                                    <small class="cell-note signed-in-note">● Signed in on app</small>
                                @endif
                            </td>

                            {{-- Account status --}}
                            <td>
                                @php
                                    $statusClass = match ($customer->status) {
                                        'active' => 'pill-green',
                                        'pending' => 'pill-yellow',
                                        'suspended', 'deleted' => 'pill-red',
                                        default => 'pill-yellow',
                                    };
                                @endphp

                                <span class="pill {{ $statusClass }}" data-status="{{ $customer->status }}">
                                    {{ ucfirst($customer->status) }}
                                </span>

                                @if (!$customer->is_active)
                                    <small class="d-block">Inactive account</small>
                                @endif
                            </td>

                            {{-- Mobile app access: Active / Stopped (with a message shown in the app) --}}
                            <td>
                                @php $appStopped = $customer->app_status === 'stopped'; @endphp
                                <select class="select-control app-status-select {{ $appStopped ? 'is-stopped' : '' }}"
                                    aria-label="App access for {{ $customer->full_name ?: $customer->username }}"
                                    data-app-status-select
                                    data-url="{{ route('admin.customers.app-status', $customer->id) }}"
                                    data-name="{{ $customer->full_name ?: $customer->username }}"
                                    data-current="{{ $customer->app_status ?? 'active' }}"
                                    data-message-en="{{ $customer->app_status_message_en }}"
                                    data-message-ar="{{ $customer->app_status_message_ar }}">
                                    <option value="active" @selected(!$appStopped)>Active</option>
                                    <option value="stopped" @selected($appStopped)>Stopped</option>
                                </select>
                                @if ($appStopped)
                                    <small class="cell-note" title="{{ $customer->app_status_message_en }}">
                                        Stopped {{ $customer->app_status_changed_at?->diffForHumans() }}
                                    </small>
                                @endif
                            </td>


                            {{-- Actions --}}
                            <td>
                                <div class="company-actions">
                                    {{-- Edit customer --}}
                                    <a href="{{ route('admin.customers.edit', $customer->id) }}"
                                        class="row-action row-action-icon" title="Edit customer"
                                        aria-label="Edit {{ $customer->full_name }}">

                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none"
                                            stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                            stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 20h9" />
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" />
                                        </svg>
                                    </a>

                                    {{-- Send an offer / news to this customer (only possible while signed in to the app) --}}
                                    @if ($customer->tokens_count)
                                        <a href="{{ route('admin.notifications.campaign.create', ['customer_id' => $customer->id]) }}"
                                            class="row-action row-action-icon" title="Send offer or news"
                                            aria-label="Send offer or news to {{ $customer->full_name ?: $customer->username }}">
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none"
                                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                                stroke-linejoin="round" aria-hidden="true">
                                                <path d="m3 11 18-7v16L3 13v-2Z" />
                                                <path d="M7 12.5V18a2 2 0 0 0 4 0v-4" />
                                            </svg>
                                        </a>
                                    @endif

                                    {{-- Delete customer --}}
                                    <form action="{{ route('admin.customers.destroy', $customer->id) }}" method="POST"
                                        class="company-delete-form"
                                        onsubmit="return confirm('Are you sure you want to delete this customer?');">

                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="row-action row-action-icon row-action-delete"
                                            title="Delete customer" aria-label="Delete {{ $customer->full_name }}">

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
                            <td colspan="10" class="text-center">
                                No customers found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @include('partials.table-pagination', ['paginator' => $customers, 'label' => 'customers'])
    </section>

    {{-- Sign every customer out of the app --}}
    <section class="panel danger-zone">
        <div class="danger-zone-row">
            <div>
                <h2>Force logout all customers</h2>
                <p>
                    Signs every customer out of the mobile app. They must log in again, and push notifications
                    stop until they do. Use it after a security issue or a major app change.
                    <strong>{{ number_format($signedInCount) }}</strong>
                    {{ $signedInCount === 1 ? 'customer is' : 'customers are' }} signed in right now.
                </p>
            </div>
            <form action="{{ route('admin.customers.force-logout') }}" method="POST"
                onsubmit="return confirm('Sign ALL customers out of the app? Everyone will have to log in again.');">
                @csrf
                <button type="submit" class="btn btn-danger" @disabled($signedInCount === 0)>Force logout all</button>
            </form>
        </div>
    </section>

    {{-- Stop / re-allow the app for one customer --}}
    <dialog class="admin-dialog" id="appStatusDialog" aria-labelledby="appStatusDialogTitle">
        <form method="POST" action="" id="appStatusForm">
            @csrf
            <input type="hidden" name="app_status" id="appStatusValue">

            <div class="admin-dialog-head">
                <div>
                    <h2 id="appStatusDialogTitle">Stop the app</h2>
                    <p id="appStatusDialogIntro"></p>
                </div>
                <button type="button" class="admin-dialog-close" data-dialog-cancel aria-label="Close">×</button>
            </div>

            <div class="admin-dialog-body">
                <div class="form-group">
                    <label for="appStatusMessageEn">Message in English <b style="color:red" data-required-mark>*</b></label>
                    <textarea class="form-control" id="appStatusMessageEn" name="message_en" rows="3" maxlength="1000"
                        placeholder="e.g. Your account is under review. Please contact support@sunxfinder.com."></textarea>
                </div>

                <div class="form-group">
                    <label for="appStatusMessageAr">Message in Arabic <b style="color:red" data-required-mark>*</b></label>
                    <textarea class="form-control" id="appStatusMessageAr" name="message_ar" rows="3" maxlength="1000"
                        dir="rtl" lang="ar" placeholder="مثال: حسابك قيد المراجعة. يرجى التواصل مع support@sunxfinder.com."></textarea>
                </div>

                <label class="quote-admin-check">
                    <input type="hidden" name="push" value="0">
                    <input type="checkbox" name="push" value="1" checked>
                    Send this message as a push notification
                </label>

                <small class="form-hint" id="appStatusHint">
                    The app shows this message (in the customer's app language) and stops working until you set it back
                    to Active.
                </small>
            </div>

            <div class="admin-dialog-actions">
                <button type="button" class="btn btn-light" data-dialog-cancel>Cancel</button>
                <button type="submit" class="btn btn-danger" id="appStatusSubmit">Stop app</button>
            </div>
        </form>
    </dialog>

@endsection

@section('scripts')
    <script>
        (function() {
            var dialog = document.getElementById('appStatusDialog');
            var form = document.getElementById('appStatusForm');
            if (!dialog || !form) return;

            var activeSelect = null;
            var en = document.getElementById('appStatusMessageEn');
            var ar = document.getElementById('appStatusMessageAr');

            function open(select) {
                var stopping = select.value === 'stopped';
                activeSelect = select;

                form.action = select.dataset.url;
                document.getElementById('appStatusValue').value = select.value;
                document.getElementById('appStatusDialogTitle').textContent = stopping ? 'Stop the app' : 'Re-activate the app';
                document.getElementById('appStatusDialogIntro').textContent = stopping
                    ? 'Stop the mobile app for ' + select.dataset.name + '. Write what they will see.'
                    : 'Let ' + select.dataset.name + ' use the app again. A message is optional.';
                document.getElementById('appStatusHint').hidden = !stopping;

                en.value = stopping ? (select.dataset.messageEn || '') : '';
                ar.value = stopping ? (select.dataset.messageAr || '') : '';
                en.required = ar.required = stopping;
                dialog.querySelectorAll('[data-required-mark]').forEach(function(mark) { mark.hidden = !stopping; });

                var submit = document.getElementById('appStatusSubmit');
                submit.textContent = stopping ? 'Stop app' : 'Re-activate app';
                submit.className = 'btn ' + (stopping ? 'btn-danger' : 'btn-primary');

                dialog.showModal();
                en.focus();
            }

            function cancel() {
                if (activeSelect) activeSelect.value = activeSelect.dataset.current;
                dialog.close();
            }

            document.querySelectorAll('[data-app-status-select]').forEach(function(select) {
                select.addEventListener('change', function() {
                    if (select.value !== select.dataset.current) open(select);
                });
            });

            dialog.querySelectorAll('[data-dialog-cancel]').forEach(function(button) {
                button.addEventListener('click', cancel);
            });

            // Esc key closes the dialog: put the dropdown back too.
            dialog.addEventListener('cancel', function(event) {
                event.preventDefault();
                cancel();
            });
        })();
    </script>
@endsection

@extends('company.layouts.app')

@section('title', 'Send Notification')

@section('eyebrow', 'CUSTOMER COMMUNICATION')
@section('page-title', 'Send Notification')
@section('page-description', 'Compose a bilingual announcement and choose which customers should receive it.')

@section('page-actions')
    <a href="{{ route('company.notifications.index') }}" class="btn btn-primary">← Back to Notifications</a>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following errors:</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('company.notifications.store') }}">
        @csrf
        <section class="panel">
            <div class="panel-heading">
                <h2>Notification details</h2>
                <p>Choose the category and audience for this notification.</p>
            </div>
            <div class="panel-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="type">Notification Type <b style="color:red">*</b></label>
                        <select class="select-control" id="type" name="type" required>
                            @foreach ([
                                'general' => 'General announcement',
                                'quote' => 'Quote update',
                                'promotion' => 'Promotion / solar offer',
                                'account' => 'Account and security',
                                'support' => 'Messages and support',
                                'maintenance' => 'Maintenance notice',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', 'general') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    @php
                        // Pre-selected from Company Customers (?customer_ids[]= or ?to=company-customers).
                        $preselected = array_map('strval', $preselected ?? []);
                        $selectedIds = array_map('strval', old('customer_ids', $preselected));
                        $reachableIds = ($customers ?? collect())->pluck('id')->map(fn($id) => (string) $id)->all();
                        $unreachable = count(array_diff($preselected, $reachableIds));
                        $hasCustomers = ($customers ?? collect())->isNotEmpty();
                    @endphp
                    <input type="hidden" name="audience_type" value="selected">
                    <div class="form-group">
                        <label for="customer_ids">{{ $canNotifyAll ? 'Customers' : 'Your customers' }} <b style="color:red">*</b></label>
                        @if ($unreachable > 0)
                            <small class="form-hint" style="color:#b54708">
                                {{ $unreachable }} of your selected {{ \Illuminate\Support\Str::plural('customer', $unreachable) }}
                                {{ $unreachable === 1 ? 'is' : 'are' }} not signed in to the app, so cannot receive push
                                notifications and {{ $unreachable === 1 ? 'is' : 'are' }} not listed.
                            </small>
                        @endif
                        <small class="form-hint">
                            @if ($canNotifyAll)
                                The admin has opened all customers for your company. Contact details are shown only for your own customers. Select one or more.
                            @else
                                Only customers who have dealt with your company are listed. Select one or more.
                            @endif
                        </small>
                        <select class="select-control" id="customer_ids" name="customer_ids[]" multiple size="6" @required($hasCustomers)>
                            @foreach (($customers ?? collect()) as $customer)
                                <option value="{{ $customer->id }}" @selected(in_array((string) $customer->id, $selectedIds, true))>
                                    {{ $customer->full_name ?? $customer->name ?? $customer->username ?? ('Customer #' . $customer->id) }}
                                    @if ($customer->email ?? $customer->phone)
                                        — {{ $customer->email ?? $customer->phone }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @unless ($hasCustomers)
                            <small class="form-hint">No eligible customers with app notifications are available yet.</small>
                        @else
                            <small class="form-hint">Hold Ctrl (Windows) or Command (Mac) to select multiple customers.</small>
                            @if ($canNotifyAll)
                                <button type="button" class="btn btn-light" style="margin-top:8px"
                                    onclick="for (const option of document.getElementById('customer_ids').options) { option.selected = true; }">Select all customers</button>
                            @endif
                        @endunless
                        @error('customer_ids')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading"><h2>English content</h2><p>Displayed to customers who use English.</p></div>
            <div class="panel-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title_en">Title (English) <b style="color:red">*</b></label>
                        <input class="form-control" id="title_en" name="title_en" type="text" maxlength="180"
                            value="{{ old('title_en') }}" required placeholder="e.g. New solar offers are available">
                        @error('title_en')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label for="message_en">Message (English) <b style="color:red">*</b></label>
                        <textarea class="form-control" id="message_en" name="message_en" rows="5" maxlength="5000"
                            required placeholder="Write the notification message...">{{ old('message_en') }}</textarea>
                        @error('message_en')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading"><h2>Arabic content</h2><p>Displayed to customers who use Arabic.</p></div>
            <div class="panel-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title_ar">Title (Arabic)</label>
                        <input class="form-control" id="title_ar" name="title_ar" type="text" maxlength="180"
                            dir="rtl" lang="ar" value="{{ old('title_ar') }}" placeholder="عنوان الإشعار">
                        @error('title_ar')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label for="message_ar">Message (Arabic)</label>
                        <textarea class="form-control" id="message_ar" name="message_ar" rows="5" maxlength="5000"
                            dir="rtl" lang="ar" placeholder="اكتب نص الإشعار هنا...">{{ old('message_ar') }}</textarea>
                        @error('message_ar')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading"><h2>Publishing</h2><p>Review the audience and content before sending.</p></div>
            <div class="panel-body">
                <div class="form-actions">
                    <a href="{{ route('company.notifications.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary"
                        @disabled(!$hasCustomers)
                        onclick="return confirm('Send this notification to the selected customers?')">Send Notification</button>
                </div>
            </div>
        </section>
    </form>
@endsection

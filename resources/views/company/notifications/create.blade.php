@extends('company.layouts.app')

@section('title', __('company/messages.notifications.send_title'))

@section('eyebrow', __('company/messages.notifications.eyebrow'))
@section('page-title', __('company/messages.notifications.send_title'))
@section('page-description', __('company/messages.notifications.create_description'))

@section('page-actions')
    <a href="{{ route('company.notifications.index') }}" class="btn btn-primary">{{ __('company/messages.notifications.back') }}</a>
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
            <strong>{{ __('company/messages.common.fix_errors') }}</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('company.notifications.store') }}">
        @csrf
        <section class="panel">
            <div class="panel-heading">
                <h2>{{ __('company/messages.notifications.details') }}</h2>
                <p>{{ __('company/messages.notifications.details_sub') }}</p>
            </div>
            <div class="panel-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="type">{{ __('company/messages.notifications.notification_type') }} <b style="color:red">*</b></label>
                        <select class="select-control" id="type" name="type" required>
                            @foreach ([
                                'general' => __('company/messages.notifications.type_general_announcement'),
                                'quote' => __('company/messages.notifications.type_quote'),
                                'promotion' => __('company/messages.notifications.type_promotion_offer'),
                                'account' => __('company/messages.notifications.type_account_security'),
                                'support' => __('company/messages.notifications.type_messages_support'),
                                'maintenance' => __('company/messages.notifications.type_maintenance_notice'),
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
                        <label for="customer_ids">{{ $canNotifyAll ? __('company/messages.common.customers') : __('company/messages.notifications.your_customers') }} <b style="color:red">*</b></label>
                        @if (!empty($sendLimit) && $sendLimit['limit'] !== null)
                            <small class="form-hint">
                                {{ __('company/messages.ads.limit_hint', ['used' => $sendLimit['used'], 'limit' => $sendLimit['limit']]) }}
                            </small>
                        @endif
                        @if ($unreachable > 0)
                            <small class="form-hint" style="color:#b54708">
                                {{ trans_choice('company/messages.notifications.unreachable_choice', $unreachable) }}
                            </small>
                        @endif
                        <small class="form-hint">
                            @if ($canNotifyAll)
                                {{ __('company/messages.notifications.hint_all') }}
                            @else
                                {{ __('company/messages.notifications.hint_own') }}
                            @endif
                        </small>
                        <select class="select-control" id="customer_ids" name="customer_ids[]" multiple size="6" @required($hasCustomers)>
                            @foreach (($customers ?? collect()) as $customer)
                                <option value="{{ $customer->id }}" @selected(in_array((string) $customer->id, $selectedIds, true))>
                                    {{ $customer->full_name ?? $customer->name ?? $customer->username ?? (__('company/messages.common.customer_number', ['id' => $customer->id])) }}
                                    @if ($customer->email ?? $customer->phone)
                                        — {{ $customer->email ?? $customer->phone }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @unless ($hasCustomers)
                            <small class="form-hint">{{ __('company/messages.notifications.no_eligible') }}</small>
                        @else
                            <small class="form-hint">{{ __('company/messages.notifications.multi_hint') }}</small>
                            @if ($canNotifyAll)
                                <button type="button" class="btn btn-light" style="margin-top:8px"
                                    onclick="for (const option of document.getElementById('customer_ids').options) { option.selected = true; }">{{ __('company/messages.notifications.select_all') }}</button>
                            @endif
                        @endunless
                        @error('customer_ids')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading"><h2>{{ __('company/messages.notifications.english_content') }}</h2><p>{{ __('company/messages.notifications.english_sub') }}</p></div>
            <div class="panel-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title_en">{{ __('company/messages.notifications.title_en') }} <b style="color:red">*</b></label>
                        <input class="form-control" id="title_en" name="title_en" type="text" maxlength="180"
                            value="{{ old('title_en') }}" required placeholder="{{ __('company/messages.notifications.title_placeholder') }}">
                        @error('title_en')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label for="message_en">{{ __('company/messages.notifications.message_en') }} <b style="color:red">*</b></label>
                        <textarea class="form-control" id="message_en" name="message_en" rows="5" maxlength="5000"
                            required placeholder="{{ __('company/messages.notifications.message_placeholder') }}">{{ old('message_en') }}</textarea>
                        @error('message_en')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading"><h2>{{ __('company/messages.notifications.arabic_content') }}</h2><p>{{ __('company/messages.notifications.arabic_sub') }}</p></div>
            <div class="panel-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title_ar">{{ __('company/messages.notifications.title_ar') }}</label>
                        <input class="form-control" id="title_ar" name="title_ar" type="text" maxlength="180"
                            dir="rtl" lang="ar" value="{{ old('title_ar') }}" placeholder="عنوان الإشعار">
                        @error('title_ar')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label for="message_ar">{{ __('company/messages.notifications.message_ar') }}</label>
                        <textarea class="form-control" id="message_ar" name="message_ar" rows="5" maxlength="5000"
                            dir="rtl" lang="ar" placeholder="اكتب نص الإشعار هنا...">{{ old('message_ar') }}</textarea>
                        @error('message_ar')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading"><h2>{{ __('company/messages.notifications.publishing') }}</h2><p>{{ __('company/messages.notifications.publishing_sub') }}</p></div>
            <div class="panel-body">
                <div class="form-actions">
                    <a href="{{ route('company.notifications.index') }}" class="btn btn-light">{{ __('company/messages.common.cancel') }}</a>
                    <button type="submit" class="btn btn-primary"
                        @disabled(!$hasCustomers)
                        onclick="return confirm(@js(__('company/messages.notifications.send_confirm')))">{{ __('company/messages.notifications.send_title') }}</button>
                </div>
            </div>
        </section>
    </form>
@endsection

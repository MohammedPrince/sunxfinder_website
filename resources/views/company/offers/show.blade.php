@extends('company.layouts.app')

@php
    $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';
    $status = $offer->displayStatus();
    $hasCustomers = $customers->isNotEmpty();
    $selectedIds = array_map('strval', old('customer_ids', []));
@endphp

@section('title', $offer->title($lang))
@section('breadcrumb', __('company/messages.package_offers.title'))
@section('eyebrow', __('company/messages.package_offers.eyebrow'))
@section('page-title', $offer->title($lang))
@section('page-description', __('company/messages.package_offers.show_description'))

@push('styles')
    <link rel="stylesheet" href="{{ asset_v('css/package-offer-builder.css') }}">
@endpush

@section('page-actions')
    <a href="{{ route('company.offers.index') }}" class="btn btn-light">{{ __('company/messages.package_offers.back') }}</a>
    @unless ($offer->isLocked())
        <a href="{{ route('company.offers.edit', $offer->id) }}" class="btn btn-light">{{ __('company/messages.package_offers.edit') }}</a>
    @endunless
    <a href="{{ route('company.offers.create', ['from' => $offer->id]) }}" class="btn btn-light">{{ __('company/messages.package_offers.copy') }}</a>
    @if ($offer->status === \App\Models\PackageOffer::STATUS_ACTIVE)
        <form method="POST" action="{{ route('company.offers.close', $offer->id) }}" class="inline-form"
            onsubmit="return confirm(@js(__('company/messages.package_offers.close_confirm')))">
            @csrf
            <button type="submit" class="btn btn-light">{{ __('company/messages.package_offers.close') }}</button>
        </form>
    @endif
    @unless ($offer->isLocked())
        <form method="POST" action="{{ route('company.offers.destroy', $offer->id) }}" class="inline-form"
            onsubmit="return confirm(@js(__('company/messages.package_offers.delete_confirm')))">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-light">{{ __('company/messages.package_offers.delete') }}</button>
        </form>
    @endunless
@endsection

@section('content')
    @include('company.offers.partials.alerts')

    <div class="metrics-strip">
        <div><small>{{ __('company/messages.common.status') }}</small><strong>@include('company.offers.partials.status-pill', ['status' => $status])</strong></div>
        <div><small>{{ __('company/messages.package_offers.sent_to') }}</small><strong>{{ number_format($sentCount) }}</strong></div>
        <div><small>{{ __('company/messages.package_offers.accepted') }}</small><strong>{{ number_format($acceptedCount) }}</strong></div>
        <div><small>{{ __('company/messages.package_offers.declined') }}</small><strong>{{ number_format($declinedCount) }}</strong></div>
    </div>

    <div class="package-offer-layout">
        <div class="package-offer-main">
            {{-- Who answered --}}
            <section class="panel table-panel">
                <div class="panel-heading">
                    <div>
                        <h2>{{ __('company/messages.package_offers.responses') }}</h2>
                        <p>{{ __('company/messages.package_offers.responses_sub') }}</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('company/messages.common.customer') }}</th>
                                <th>{{ __('company/messages.package_offers.answer') }}</th>
                                <th>{{ __('company/messages.quote.peak_load') }}</th>
                                <th>{{ __('company/messages.package_offers.project') }}</th>
                                <th>{{ __('company/messages.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($responses as $response)
                                @php
                                    $quote = $response->quote;
                                    $stage = $quote?->stage();
                                    $project = $quote?->project;
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $response->customer?->full_name ?: $response->customer?->username ?: __('company/messages.common.customer_number', ['id' => $response->customer_id]) }}</strong>
                                        <small class="cell-note">{{ $response->customer?->city }}</small>
                                    </td>
                                    <td>
                                        <span class="pill {{ $response->status === 'accepted' ? 'pill-green' : 'pill-red' }}">
                                            {{ __('company/messages.package_offers.answer_' . $response->status) }}
                                        </span>
                                        <small class="cell-note">{{ $response->responded_at?->translatedFormat('d M Y, H:i') }}</small>
                                    </td>
                                    <td>{{ $quote?->peak_load ?: '—' }}</td>
                                    <td>
                                        @if ($quote)
                                            <span class="pill {{ $stage['class'] }}">{{ __('company/messages.package_offers.project_' . ($project?->status ?? 'none')) }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($project)
                                            <a href="{{ route('company.projects.show', $project->id) }}" class="btn {{ $project->status === 'accepted' ? 'btn-primary' : 'btn-light' }}">
                                                {{ $project->status === 'accepted' ? __('company/messages.package_offers.start_project') : __('company/messages.package_offers.open_project') }}
                                            </a>
                                        @elseif ($quote)
                                            <a href="{{ route('company.quote-requests.show', $quote->id) }}" class="btn btn-light">{{ __('company/messages.package_offers.open_request') }}</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center">{{ __('company/messages.package_offers.no_responses') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include('partials.table-pagination', ['paginator' => $responses, 'label' => 'responses'])
            </section>

            {{-- Send --}}
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>{{ __('company/messages.package_offers.send_title') }}</h2>
                        <p>{{ __('company/messages.package_offers.send_sub') }}</p>
                    </div>
                </div>
                <div class="panel-body">
                    @if (!$offer->isOpen())
                        <p class="form-hint">{{ __('company/messages.package_offers.not_open') }}</p>
                    @else
                        <form method="POST" action="{{ route('company.offers.send', $offer->id) }}"
                            onsubmit="return confirm(@js(__('company/messages.package_offers.send_confirm')))">
                            @csrf
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label for="customer_ids">{{ __('company/messages.notifications.your_customers') }} <b style="color:red">*</b></label>
                                    <small class="form-hint">{{ __('company/messages.package_offers.own_customers_only') }}</small>
                                    <select class="select-control" id="customer_ids" name="customer_ids[]" multiple size="8" @required($hasCustomers)>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" @selected(in_array((string) $customer->id, $selectedIds, true))>
                                                {{ $customer->full_name ?: $customer->username ?: __('company/messages.common.customer_number', ['id' => $customer->id]) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($hasCustomers)
                                        <small class="form-hint">{{ __('company/messages.notifications.multi_hint') }}</small>
                                        <button type="button" class="btn btn-light" style="margin-top:8px"
                                            onclick="for (const option of document.getElementById('customer_ids').options) { option.selected = true; }">{{ __('company/messages.notifications.select_all') }}</button>
                                    @else
                                        <small class="form-hint">{{ __('company/messages.notifications.no_eligible') }}</small>
                                    @endif
                                    @error('customer_ids')<small class="field-error">{{ $message }}</small>@enderror
                                </div>
                                <div class="form-group">
                                    <label for="message_en">{{ __('company/messages.package_offers.push_message_en') }}</label>
                                    <textarea class="form-control" id="message_en" name="message_en" rows="3" maxlength="1000"
                                        placeholder="{{ __('company/messages.package_offers.push_message_placeholder') }}">{{ old('message_en') }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label for="message_ar">{{ __('company/messages.package_offers.push_message_ar') }}</label>
                                    <textarea class="form-control" id="message_ar" name="message_ar" rows="3" maxlength="1000" dir="rtl" lang="ar"
                                        placeholder="اتركه فارغًا لاستخدام الرسالة الافتراضية">{{ old('message_ar') }}</textarea>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary" @disabled(!$hasCustomers)>{{ __('company/messages.package_offers.send') }}</button>
                            </div>
                        </form>
                    @endif
                </div>
            </section>
        </div>

        {{-- What customers see --}}
        <aside class="package-offer-aside">
            <div class="offer-summary-card">
                <h3>{{ __('company/messages.package_offers.what_customers_see') }}</h3>
                @if ($offer->image_path)
                    <img class="pkg-offer-image" src="{{ $offer->imageUrl() }}" alt="" width="{{ $offer->image_width }}" height="{{ $offer->image_height }}">
                @endif
                @if ($offer->created_by_type === 'admin')
                    <p class="form-hint">{{ __('company/messages.package_offers.made_by_admin') }}</p>
                @endif
                <dl>
                    <div><dt>{{ __('company/messages.package_offers.max_load') }}</dt><dd>{{ __('company/messages.package_offers.kw_value', ['value' => $offer->maxLoadLabel()]) }}</dd></div>
                    <div><dt>{{ __('company/messages.package_offers.valid_until') }}</dt><dd>{{ $offer->expires_at?->translatedFormat('d M Y, H:i') ?? __('company/messages.package_offers.no_expiry') }}</dd></div>
                </dl>
                <ul class="package-offer-items">
                    @foreach ($offer->items ?? [] as $item)
                        <li>
                            <span>
                                <strong>{{ $item['description'] }}</strong>
                                @if (!empty($item['type']))<small>{{ __('company/messages.common.product_type_' . $item['type']) }}</small>@endif
                            </span>
                            <span>{{ rtrim(rtrim(number_format((float) $item['quantity'], 2, '.', ''), '0'), '.') }} × {{ number_format((float) $item['unit_price'], 2) }}</span>
                        </li>
                    @endforeach
                </ul>
                <dl>
                    <div><dt>{{ __('company/messages.quote.subtotal') }}</dt><dd>{{ number_format((float) $offer->subtotal, 2) }}</dd></div>
                    @if ((float) $offer->discount > 0)
                        <div><dt>{{ __('company/messages.quote.discount') }}</dt><dd>− {{ number_format((float) $offer->discount, 2) }}</dd></div>
                    @endif
                </dl>
                <div class="offer-summary-total">
                    <span>{{ __('company/messages.package_offers.customer_pays') }}</span>
                    <strong>{{ number_format((float) $offer->price, 2) }} <small>{{ $offer->currency }}</small></strong>
                </div>
                @if ($offer->description($lang))
                    <p class="package-offer-description">{{ $offer->description($lang) }}</p>
                @endif
            </div>
            @if ($offer->isLocked())
                <div class="offer-notice"><p>{{ __('company/messages.package_offers.locked') }}</p></div>
            @endif
        </aside>
    </div>
@endsection

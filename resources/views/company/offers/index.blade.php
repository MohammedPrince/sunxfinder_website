@extends('company.layouts.app')

@section('title', __('company/messages.package_offers.title'))
@section('breadcrumb', __('company/messages.package_offers.title'))
@section('eyebrow', __('company/messages.package_offers.eyebrow'))
@section('page-title', __('company/messages.package_offers.title'))
@section('page-description', __('company/messages.package_offers.page_description'))

@section('page-actions')
    <a href="{{ route('company.offers.create') }}" class="btn btn-primary">{{ __('company/messages.package_offers.create') }}</a>
@endsection

@section('content')
    @include('company.offers.partials.alerts')

    @include('partials.page-guide', [
        'key' => 'company-package-offers',
        'title' => __('company/messages.package_offers.guide_title'),
        'intro' => __('company/messages.package_offers.guide_intro'),
        'steps' => [
            __('company/messages.package_offers.guide_1'),
            __('company/messages.package_offers.guide_2'),
            __('company/messages.package_offers.guide_3'),
            __('company/messages.package_offers.guide_4'),
        ],
        'note' => __('company/messages.package_offers.guide_note'),
    ])

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>{{ __('company/messages.package_offers.list_title') }}</h2>
                <p>{{ __('company/messages.package_offers.list_sub') }}</p>
            </div>
            <form method="GET" action="{{ route('company.offers.index') }}" class="table-filters">
                <select class="select-control" name="status" aria-label="{{ __('company/messages.common.status') }}">
                    <option value="">{{ __('company/messages.package_offers.all') }}</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>{{ __('company/messages.package_offers.status_active') }}</option>
                    <option value="ended" @selected(($filters['status'] ?? '') === 'ended')>{{ __('company/messages.package_offers.status_ended') }}</option>
                </select>
                <input class="table-search" type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                    placeholder="{{ __('company/messages.package_offers.search') }}" aria-label="{{ __('company/messages.package_offers.search') }}">
                <button type="submit" class="btn btn-light">{{ __('company/messages.notifications.filter') }}</button>
                @if (!empty($filters))
                    <a href="{{ route('company.offers.index') }}" class="btn btn-light">{{ __('company/messages.notifications.reset') }}</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('company/messages.package_offers.offer') }}</th>
                        <th>{{ __('company/messages.package_offers.max_load') }}</th>
                        <th>{{ __('company/messages.package_offers.price') }}</th>
                        <th>{{ __('company/messages.package_offers.sent_to') }}</th>
                        <th>{{ __('company/messages.package_offers.accepted') }}</th>
                        <th>{{ __('company/messages.package_offers.declined') }}</th>
                        <th>{{ __('company/messages.common.status') }}</th>
                        <th>{{ __('company/messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($offers as $offer)
                        @php $status = $offer->displayStatus(); @endphp
                        <tr>
                            <td>
                                <div class="person-cell">
                                    <span class="company-avatar solar">{{ name_initials($offer->title_en) }}</span>
                                    <span>
                                        <strong>{{ $offer->title(app()->getLocale()) }}</strong>
                                        <small>{{ trans_choice('company/messages.package_offers.items_count', count($offer->items ?? []), ['count' => count($offer->items ?? [])]) }}
                                            @if ($offer->expires_at) · {{ __('company/messages.package_offers.until', ['date' => $offer->expires_at->translatedFormat('d M Y')]) }} @endif</small>
                                    </span>
                                </div>
                            </td>
                            <td>{{ __('company/messages.package_offers.kw_value', ['value' => $offer->maxLoadLabel()]) }}</td>
                            <td>
                                <strong>{{ number_format((float) $offer->price, 2) }} {{ $offer->currency }}</strong>
                                @if ($offer->discountPercent())
                                    <small class="cell-note">−{{ $offer->discountPercent() }}%</small>
                                @endif
                            </td>
                            <td>{{ number_format($offer->sent_count) }}</td>
                            <td><strong>{{ number_format($offer->accepted_count) }}</strong></td>
                            <td>{{ number_format($offer->declined_count) }}</td>
                            <td>@include('company.offers.partials.status-pill', ['status' => $status])</td>
                            <td>
                                <a href="{{ route('company.offers.show', $offer->id) }}" class="btn btn-light">{{ __('company/messages.package_offers.open') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">
                            {{ __('company/messages.package_offers.none') }}
                            <a href="{{ route('company.offers.create') }}">{{ __('company/messages.package_offers.create_first') }}</a>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $offers, 'label' => 'offers'])
    </section>
@endsection

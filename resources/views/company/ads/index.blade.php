@extends('company.layouts.app')

@section('title', __('company/messages.ads.title'))
@section('breadcrumb', __('company/messages.ads.title'))
@section('eyebrow', __('company/messages.ads.eyebrow'))
@section('page-title', __('company/messages.ads.title'))
@section('page-description', __('company/messages.ads.page_description'))

@section('page-actions')
    <a href="{{ route('company.ads.report') }}" class="btn btn-light">{{ __('company/messages.ads.monthly_report') }}</a>
    <a href="{{ route('company.ads.create') }}" class="btn btn-primary">{{ __('company/messages.ads.request_button') }}</a>
@endsection

@section('content')
    @php
        $statusClasses = ['requested' => 'pill-orange', 'awaiting_payment' => 'pill-yellow', 'paid' => 'pill-green', 'cancelled' => 'pill-red'];
    @endphp

    @include('partials.page-guide', [
        'key' => 'company-ads',
        'title' => __('company/messages.ads.guide_title'),
        'intro' => __('company/messages.ads.guide_intro'),
        'steps' => [
            __('company/messages.ads.guide_1'),
            __('company/messages.ads.guide_2'),
            __('company/messages.ads.guide_3'),
        ],
        'note' => __('company/messages.ads.guide_note'),
    ])

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="metrics-strip">
        <div><small>{{ __('company/messages.ads.active_ads') }}</small><strong>{{ number_format($activeAds) }}</strong></div>
        <div><small>{{ __('company/messages.ads.views_month') }}</small><strong>{{ number_format($viewsThisMonth) }}</strong></div>
        <div><small>{{ __('company/messages.ads.clicks_month') }}</small><strong>{{ number_format($clicksThisMonth) }}</strong></div>
        <div>
            <small>{{ __('company/messages.ads.sends_used') }}</small>
            <strong>
                @if (!$sendLimit['open'])
                    —
                @elseif ($sendLimit['limit'] === null)
                    {{ number_format($sendLimit['used']) }}
                @else
                    {{ number_format($sendLimit['used']) }} / {{ number_format($sendLimit['limit']) }}
                @endif
            </strong>
        </div>
    </div>

    <section class="panel table-panel">
        <div class="panel-heading">
            <div>
                <h2>{{ __('company/messages.ads.your_ads') }}</h2>
                <p>{{ __('company/messages.ads.your_ads_sub') }}</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('company/messages.ads.ad') }}</th>
                        <th>{{ __('company/messages.ads.dates') }}</th>
                        <th>{{ __('company/messages.ads.amount') }}</th>
                        <th>{{ __('company/messages.common.status') }}</th>
                        <th>{{ __('company/messages.ads.views') }}</th>
                        <th>{{ __('company/messages.ads.clicks') }}</th>
                        <th>{{ __('company/messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td>
                                <strong>{{ __('company/messages.ads.products.' . $sale->product) }}</strong>
                                <small class="cell-note">{{ __('company/messages.ads.platforms.' . $sale->platform) }}</small>
                            </td>
                            <td>
                                {{ $sale->starts_on?->translatedFormat('M d, Y') ?? '—' }} – {{ $sale->ends_on?->translatedFormat('M d, Y') ?? '—' }}
                                @if ($sale->isEndingSoon())
                                    <span class="pill pill-orange">{{ __('company/messages.ads.ending_soon') }}</span>
                                @endif
                            </td>
                            <td>{{ $sale->amount !== null ? number_format((float) $sale->amount, 2) . ' ' . $sale->currency : '—' }}</td>
                            <td><span class="pill {{ $statusClasses[$sale->status] ?? 'pill-blue' }}">{{ __('company/messages.ads.statuses.' . $sale->status) }}</span></td>
                            <td>{{ $sale->results ? number_format($sale->results['views']) : '—' }}</td>
                            <td>{{ $sale->results ? number_format($sale->results['clicks']) : '—' }}</td>
                            <td>
                                @if ($sale->status === 'requested')
                                    <form method="POST" action="{{ route('company.ads.cancel', $sale->id) }}"
                                        onsubmit="return confirm(@js(__('company/messages.ads.cancel_confirm')));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-light">{{ __('company/messages.common.cancel') }}</button>
                                    </form>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="dashboard-empty">
                                <strong>{{ __('company/messages.ads.empty_title') }}</strong>
                                {{ __('company/messages.ads.empty_body') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $sales, 'label' => 'records'])
    </section>
@endsection

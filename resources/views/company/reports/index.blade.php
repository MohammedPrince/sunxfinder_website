@extends('company.layouts.app')
@section('title', __('company/messages.reports.title'))
@section('breadcrumb', __('company/messages.reports.title'))
@section('eyebrow', __('company/messages.reports.eyebrow'))
@section('page-title', __('company/messages.reports.title'))
@section('page-description', __('company/messages.reports.page_description'))
@section('page-actions')
    <button type="button" class="btn btn-light no-print" onclick="window.print()">{{ __('company/messages.reports.print') }}</button>
@endsection

@section('content')
    @php
        $from = $period['from'];
        $to = $period['to'];
        $exportQuery = ['preset' => $period['preset'], 'from' => $from->toDateString(), 'to' => $to->toDateString()];

        $money = fn($value) => $value === null ? '—' : number_format($value, 0);
        $quoteRef = fn($id) => '#Q-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);

        $trendBuckets = $trend['buckets'];
        $labelEvery = max(1, (int) ceil(count($trendBuckets) / 12));
        $barHeight = fn($v) => $v > 0 ? max(3, round($v / $trend['max'] * 100)) : 0;

        $statusLabels = [
            'pending' => __('company/messages.common.pending'),
            'accepted' => __('company/messages.reports.accepted_date'),
            'rejected' => __('company/messages.common.rejected'),
            'completed' => __('company/messages.reports.completed_date'),
            'cancelled' => __('company/messages.common.cancelled'),
            'in_progress' => __('company/messages.common.in_progress'),
            'submitted' => __('company/messages.common.awaiting_customer'),
            'selected' => __('company/messages.common.won'),
            'not_selected' => __('company/messages.common.not_selected'),
            'withdrawn' => __('company/messages.common.withdrawn'),
        ];

        $market = $offers['market'];

        $productTypeLabels = [];
        foreach (['solar_panel', 'inverter', 'battery', 'other'] as $type) {
            $productTypeLabels[$type] = __('company/messages.reports.type_' . $type);
        }

        $notificationTypeLabels = [];
        foreach (['general', 'offer_new', 'quote', 'promotion', 'account', 'support', 'maintenance'] as $type) {
            $notificationTypeLabels[$type] = __('company/messages.reports.type_' . $type);
        }
    @endphp

    {{-- Period filter --}}
    <section class="panel report-filters no-print">
        <form method="GET" action="{{ route('company.reports.index') }}" class="report-filter-form" data-report-filters>
            <div class="form-group">
                <label for="preset">{{ __('company/messages.reports.period') }}</label>
                <select class="select-control" id="preset" name="preset">
                    @foreach ($presets as $value => $label)
                        <option value="{{ $value }}" @selected($period['preset'] === $value)>{{ __('company/messages.reports.preset.' . $value) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" data-custom-range>
                <label for="from">{{ __('company/messages.reports.from') }}</label>
                <input class="form-control" type="date" id="from" name="from" value="{{ $from->toDateString() }}">
            </div>
            <div class="form-group" data-custom-range>
                <label for="to">{{ __('company/messages.reports.to') }}</label>
                <input class="form-control" type="date" id="to" name="to" value="{{ $to->toDateString() }}">
            </div>
            <button type="submit" class="btn btn-primary">{{ __('company/messages.reports.apply') }}</button>
        </form>
        @if ($errors->any())
            <small class="field-error">{{ $errors->first() }}</small>
        @endif
    </section>

    <p class="report-period">
        <strong>{{ __('company/messages.reports.preset.' . $period['preset']) }}</strong>:
        {{ $from->translatedFormat('M d, Y') }} – {{ $to->translatedFormat('M d, Y') }} ({{ trans_choice('company/messages.reports.days_count', $period['days'], ['count' => number_format($period['days'])]) }})
        @if ($period['previous_from'])
            · {{ __('company/messages.reports.compared_with', ['from' => $period['previous_from']->translatedFormat('M d'), 'to' => $period['previous_to']->translatedFormat('M d, Y')]) }}
        @endif
    </p>

    {{-- Key figures --}}
    <section class="report-kpis">
        @foreach ($kpis as $key => $kpi)
            <article class="stat-card report-kpi">
                <div class="stat-top"><span>{{ $kpi['label'] }}</span></div>
                <div class="stat-value">
                    {{ $key === 'value_won' ? $money($kpi['value']) : number_format($kpi['value']) }}{{ $kpi['suffix'] ?? '' }}
                </div>
                <div class="stat-foot">
                    @if (isset($kpi['hint']))
                        {{ $kpi['hint'] }}
                    @elseif ($kpi['change'] !== null)
                        <b class="{{ $kpi['change'] >= 0 ? 'positive' : 'negative' }}">
                            {{ $kpi['change'] >= 0 ? '↗' : '↘' }} {{ abs($kpi['change']) }}%
                        </b>
                        {{ __('company/messages.reports.vs_previous', ['value' => $key === 'value_won' ? $money($kpi['previous']) : number_format($kpi['previous'])]) }}
                    @elseif ($kpi['previous'] !== null)
                        <span class="gray-text">{{ __('company/messages.reports.previous_value', ['value' => $key === 'value_won' ? $money($kpi['previous']) : number_format($kpi['previous'])]) }}</span>
                    @endif
                </div>
            </article>
        @endforeach
    </section>

    {{-- Trend --}}
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>{{ __('company/messages.reports.activity_trend') }}</h2>
                <p>{{ $trend['monthly'] ? __('company/messages.reports.trend_sub_month') : __('company/messages.reports.trend_sub_day') }}</p>
            </div>
            <div class="report-trend-totals">
                <span>{{ __('company/messages.reports.offers_won') }} <strong>{{ number_format(collect($trendBuckets)->sum('won')) }}</strong></span>
            </div>
        </div>
        <div class="chart-legend">
            <span>{{ __('company/messages.reports.legend_received') }}</span>
            <span class="orange-text">{{ __('company/messages.reports.legend_sent') }}</span>
        </div>
        <div class="report-chart-scroll">
            <div class="simple-chart report-chart" style="min-width: {{ max(100, count($trendBuckets) * 18) }}px">
                @foreach ($trendBuckets as $bucket)
                    <div class="chart-col"
                        title="{{ __('company/messages.reports.chart_title', ['label' => $bucket['label'], 'requests' => $bucket['requests'], 'offers' => $bucket['offers'], 'won' => $bucket['won']]) }}">
                        <i style="height: {{ $barHeight($bucket['requests']) }}%"></i>
                        <b style="height: {{ $barHeight($bucket['offers']) }}%"></b>
                    </div>
                @endforeach
            </div>
            <div class="chart-days report-chart-days" style="min-width: {{ max(100, count($trendBuckets) * 18) }}px">
                @foreach ($trendBuckets as $i => $bucket)
                    <span>{{ $i % $labelEvery === 0 ? $bucket['short'] : '' }}</span>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Requests and response --}}
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>{{ __('company/messages.reports.requests_response') }}</h2>
                <p>{{ __('company/messages.reports.requests_sub') }}</p>
            </div>
        </div>

        <div class="report-metrics">
            <div><small>{{ __('company/messages.reports.requests_received') }}</small><strong>{{ number_format($kpis['requests_received']['value']) }}</strong></div>
            <div><small>{{ __('company/messages.reports.assigned_you') }}</small><strong>{{ number_format($requests['assigned_to_you']) }}</strong>
                <span>{{ __('company/messages.reports.assigned_sub') }}</span></div>
            <div><small>{{ __('company/messages.reports.open_all') }}</small><strong>{{ number_format($requests['open_marketplace']) }}</strong></div>
            <div><small>{{ __('company/messages.reports.response_rate') }}</small><strong>{{ $kpis['response_rate']['value'] }}%</strong>
                <span>{{ $kpis['response_rate']['hint'] }}</span></div>
            <div><small>{{ __('company/messages.reports.avg_time_offer') }}</small>
                <strong>{{ $requests['avg_response_hours'] !== null ? __('company/messages.reports.hours_short', ['count' => $requests['avg_response_hours']]) : '—' }}</strong>
                <span>{{ __('company/messages.reports.faster_wins') }}</span></div>
        </div>

        <div class="report-grid">
            @include('partials.report-breakdown', ['title' => __('company/messages.reports.by_status'), 'items' => $requests['by_status'], 'labels' => $statusLabels])
            @include('partials.report-breakdown', ['title' => __('company/messages.reports.by_service'), 'items' => $requests['by_service']])
            @include('partials.report-breakdown', ['title' => __('company/messages.reports.by_customer_city'), 'items' => $requests['by_city']])
        </div>

        <div class="report-subheading">
            <h3>{{ __('company/messages.reports.waiting_offer') }} <span class="pill {{ $requests['awaiting_count'] ? 'pill-orange' : 'pill-green' }}">{{ $requests['awaiting_count'] }}</span></h3>
            <p>{{ __('company/messages.reports.waiting_sub') }}</p>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('company/messages.common.request') }}</th>
                        <th>{{ __('company/messages.common.customer') }}</th>
                        <th>{{ __('company/messages.common.city') }}</th>
                        <th>{{ __('company/messages.common.quote_name') }}</th>
                        <th>{{ __('company/messages.reports.other_offers') }}</th>
                        <th>{{ __('company/messages.reports.waiting') }}</th>
                        <th class="no-print">{{ __('company/messages.common.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests['awaiting'] as $row)
                        <tr>
                            <td><strong>{{ $quoteRef($row['id']) }}</strong></td>
                            <td>{{ $row['customer'] }}</td>
                            <td>{{ $row['city'] }}</td>
                            <td>{{ $row['service'] }}</td>
                            <td>{{ $row['competing_offers'] }}</td>
                            <td>
                                <span class="pill {{ $row['age_hours'] >= 48 ? 'pill-red' : ($row['age_hours'] >= 24 ? 'pill-orange' : 'pill-yellow') }}">
                                    {{ $row['age_hours'] >= 48 ? __('company/messages.reports.age_label', ['days' => floor($row['age_hours'] / 24)]) : __('company/messages.reports.age_hours', ['count' => $row['age_hours']]) }}
                                </span>
                            </td>
                            <td class="no-print"><a class="row-action" href="{{ route('company.quote-requests.show', $row['id']) }}">{{ __('company/messages.reports.send_offer') }}</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="dashboard-empty"><strong>{{ __('company/messages.reports.caught_up_title') }}</strong> {{ __('company/messages.reports.caught_up_body') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Offers and pricing --}}
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>{{ __('company/messages.reports.offers_pricing') }}</h2>
                <p>{{ __('company/messages.reports.offers_sub') }}</p>
            </div>
            <a class="btn btn-light no-print" href="{{ route('company.reports.export', ['section' => 'offers'] + $exportQuery) }}">↓ CSV</a>
        </div>

        <div class="report-metrics">
            <div><small>{{ __('company/messages.reports.offers_sent') }}</small><strong>{{ number_format($offers['total']) }}</strong></div>
            <div><small>{{ __('company/messages.reports.win_rate') }}</small><strong>{{ $kpis['win_rate']['value'] }}%</strong><span>{{ $kpis['win_rate']['hint'] }}</span></div>
            <div><small>{{ __('company/messages.reports.your_vs_others') }}</small>
                <strong>
                    @if ($market['diff'] === null)
                        —
                    @else
                        <span class="{{ $market['diff'] <= 0 ? 'positive' : 'warning' }}">{{ $market['diff'] > 0 ? '+' : '' }}{{ $market['diff'] }}%</span>
                    @endif
                </strong>
                <span>{{ __('company/messages.reports.same_requests', ['you' => $money($market['your_avg']), 'others' => $money($market['market_avg'])]) }}</span></div>
            <div><small>{{ __('company/messages.reports.lost_vs_chosen') }}</small>
                <strong>
                    @if ($market['lost_diff'] === null)
                        —
                    @else
                        <span class="{{ $market['lost_diff'] <= 0 ? 'positive' : 'warning' }}">{{ $market['lost_diff'] > 0 ? '+' : '' }}{{ $market['lost_diff'] }}%</span>
                    @endif
                </strong>
                <span>{{ __('company/messages.reports.lost_line', ['count' => $market['lost_count'], 'you' => $money($market['lost_your_avg']), 'chosen' => $money($market['lost_winning_avg'])]) }}</span></div>
        </div>

        <div class="report-grid report-grid-2">
            @include('partials.report-breakdown', ['title' => __('company/messages.reports.by_status'), 'items' => $offers['by_status'], 'labels' => $statusLabels])

            <div class="report-breakdown">
                <h3>{{ __('company/messages.reports.prices_currency') }}</h3>
                @forelse ($offers['prices'] as $price)
                    <div class="table-responsive">
                        <table class="data-table report-mini-table">
                            <tbody>
                                <tr><th>{{ __('company/messages.reports.currency') }}</th><td><strong>{{ $price['currency'] }}</strong> {{ __('company/messages.reports.offers_count', ['count' => number_format($price['count'])]) }}</td></tr>
                                <tr><th>{{ __('company/messages.reports.average_offer') }}</th><td>{{ $money($price['avg']) }}</td></tr>
                                <tr><th>{{ __('company/messages.reports.lowest_highest') }}</th><td>{{ $money($price['min']) }} – {{ $money($price['max']) }}</td></tr>
                                <tr><th>{{ __('company/messages.reports.average_won') }}</th><td>{{ $money($price['avg_selected']) }}</td></tr>
                                <tr><th>{{ __('company/messages.reports.total_won') }}</th><td><strong>{{ $money($price['selected_value']) }}</strong></td></tr>
                            </tbody>
                        </table>
                    </div>
                @empty
                    <p class="report-empty">{{ __('company/messages.reports.no_offers') }}</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Package offers --}}
    @include('partials.report-package-offers', [
        'stats' => $packageOffers,
        'exportUrl' => route('company.reports.export', ['section' => 'package_offers'] + $exportQuery),
        'offerUrl' => fn($id) => route('company.offers.show', $id),
        'manageUrl' => route('company.offers.index'),
    ])

    {{-- Projects --}}
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>{{ __('company/messages.reports.projects') }}</h2>
                <p>{{ __('company/messages.reports.projects_sub') }}</p>
            </div>
            <a class="btn btn-light no-print" href="{{ route('company.reports.export', ['section' => 'projects'] + $exportQuery) }}">↓ CSV</a>
        </div>

        <div class="report-metrics">
            <div><small>{{ __('company/messages.reports.started') }}</small><strong>{{ number_format($projects['started_in_period']) }}</strong></div>
            <div><small>{{ __('company/messages.reports.completed_period') }}</small><strong>{{ number_format($projects['completed_in_period']) }}</strong></div>
            <div><small>{{ __('company/messages.reports.avg_complete') }}</small>
                <strong>{{ $projects['avg_completion_days'] !== null ? __('company/messages.reports.days_value', ['count' => $projects['avg_completion_days']]) : '—' }}</strong></div>
            <div><small>{{ __('company/messages.reports.avg_progress') }}</small><strong>{{ $projects['avg_active_progress'] }}%</strong></div>
            <div><small>{{ __('company/messages.reports.updates_posted') }}</small><strong>{{ number_format($projects['updates_in_period']) }}</strong>
                <span>{{ __('company/messages.reports.customers_see') }}</span></div>
        </div>

        <div class="report-grid report-grid-2">
            @include('partials.report-breakdown', ['title' => __('company/messages.reports.all_by_status', ['count' => $projects['total']]), 'items' => $projects['by_status'], 'labels' => $statusLabels])
            @include('partials.report-breakdown', ['title' => __('company/messages.reports.milestones_status'), 'items' => $projects['milestones'], 'labels' => $statusLabels])
        </div>

        <div class="report-subheading">
            <h3>{{ __('company/messages.reports.active_projects') }}
                @if ($projects['stalled_count'])
                    <span class="pill pill-red">{{ __('company/messages.reports.need_update', ['count' => $projects['stalled_count']]) }}</span>
                @endif
            </h3>
            <p>{{ __('company/messages.reports.least_progress', ['days' => $stalledDays]) }}</p>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('company/messages.common.project') }}</th>
                        <th>{{ __('company/messages.common.customer') }}</th>
                        <th>{{ __('company/messages.common.quote_name') }}</th>
                        <th>{{ __('company/messages.common.progress') }}</th>
                        <th>{{ __('company/messages.common.last_update') }}</th>
                        <th class="no-print">{{ __('company/messages.common.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projects['active'] as $row)
                        <tr>
                            <td><strong>{{ __('company/messages.common.project_number', ['id' => $row['id']]) }}</strong><small style="display:block">{{ $quoteRef($row['quote_id']) }}</small></td>
                            <td>{{ $row['customer'] }}</td>
                            <td>{{ $row['service'] }}</td>
                            <td style="min-width:140px">
                                <div class="report-progress"><span style="width: {{ $row['progress'] }}%"></span></div>
                                <small>{{ $row['progress'] }}% · {{ $statusLabels[$row['status']] ?? $row['status'] }}</small>
                            </td>
                            <td>
                                @if ($row['stalled'])
                                    <span class="pill pill-red">{{ __('company/messages.reports.idle_days', ['days' => $row['idle_days']]) }}</span>
                                @else
                                    {{ $row['idle_days'] === 0 ? __('company/messages.reports.idle_or_today') : __('company/messages.reports.idle_days', ['days' => $row['idle_days']]) }}
                                @endif
                            </td>
                            <td class="no-print"><a class="row-action" href="{{ route('company.projects.show', $row['id']) }}">{{ __('company/messages.reports.update_link') }}</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="dashboard-empty">{{ __('company/messages.reports.no_active') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Customers --}}
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>{{ __('company/messages.reports.customers') }}</h2>
                <p>{{ __('company/messages.reports.customers_sub') }}</p>
            </div>
            <a class="btn btn-light no-print" href="{{ route('company.reports.export', ['section' => 'customers'] + $exportQuery) }}">↓ CSV</a>
        </div>

        <div class="report-metrics">
            <div><small>{{ __('company/messages.reports.served_total') }}</small><strong>{{ number_format($customers['served_total']) }}</strong></div>
            <div><small>{{ __('company/messages.reports.won_period') }}</small><strong>{{ number_format($customers['won_in_period']) }}</strong></div>
            <div><small>{{ __('company/messages.reports.repeat') }}</small><strong>{{ number_format($customers['repeat']) }}</strong><span>{{ __('company/messages.reports.two_plus') }}</span></div>
            <div><small>{{ __('company/messages.reports.avg_value') }}</small><strong>{{ $money($customers['avg_project_value']) }}</strong></div>
        </div>

        <div class="report-grid report-grid-2">
            @include('partials.report-breakdown', ['title' => __('company/messages.reports.served_city'), 'items' => $customers['by_city']])

            <div class="report-breakdown">
                <h3>{{ __('company/messages.reports.top_customers') }}</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('company/messages.common.customer') }}</th>
                                <th>{{ __('company/messages.common.city') }}</th>
                                <th>{{ __('company/messages.reports.offers_won') }}</th>
                                <th>{{ __('company/messages.reports.value_sdg') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($customers['top'] as $row)
                                <tr>
                                    <td><strong>{{ $row['name'] }}</strong><small style="display:block">{{ __('company/messages.reports.last_win', ['date' => $row['last_win_at']->translatedFormat('M d, Y')]) }}</small></td>
                                    <td>{{ $row['city'] }}</td>
                                    <td>{{ $row['wins'] }}</td>
                                    <td>{{ $money($row['value_sdg']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="dashboard-empty">{{ __('company/messages.reports.no_wins') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    {{-- Products and notifications --}}
    <div class="report-columns">
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>{{ __('company/messages.reports.your_products') }}</h2>
                    <p>{{ __('company/messages.reports.products_sub') }}</p>
                </div>
            </div>
            <div class="report-metrics report-metrics-compact">
                <div><small>{{ __('company/messages.reports.products') }}</small><strong>{{ number_format($products['total']) }}</strong></div>
                <div><small>{{ __('company/messages.reports.visible') }}</small><strong>{{ number_format($products['active']) }}</strong></div>
                <div><small>{{ __('company/messages.reports.hidden') }}</small><strong>{{ number_format($products['total'] - $products['active']) }}</strong></div>
            </div>
            @include('partials.report-breakdown', ['title' => __('company/messages.reports.by_type'), 'items' => $products['by_type'], 'labels' => $productTypeLabels])
            @include('partials.report-breakdown', ['title' => __('company/messages.reports.by_supplier'), 'items' => $products['by_supplier']])
        </section>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>{{ __('company/messages.reports.push') }}</h2>
                    <p>{{ __('company/messages.reports.push_sub') }}</p>
                </div>
            </div>
            <div class="report-metrics report-metrics-compact">
                <div><small>{{ __('company/messages.reports.sent') }}</small><strong>{{ number_format($notifications['sent']) }}</strong></div>
                <div><small>{{ __('company/messages.reports.recipients') }}</small><strong>{{ number_format($notifications['recipients']) }}</strong></div>
                <div><small>{{ __('company/messages.reports.read_rate') }}</small>
                    <strong>{{ $notifications['read_rate'] !== null ? $notifications['read_rate'] . '%' : '—' }}</strong>
                    <span>{{ __('company/messages.reports.read_count', ['count' => number_format($notifications['read'])]) }}</span></div>
            </div>
            @include('partials.report-breakdown', ['title' => __('company/messages.reports.by_type'), 'items' => $notifications['by_type'], 'labels' => $notificationTypeLabels])
        </section>
    </div>

    {{-- Ratings and advertising --}}
    <div class="report-columns">
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>{{ __('company/messages.reports.ratings_title') }}</h2>
                    <p>{{ __('company/messages.reports.ratings_sub') }}</p>
                </div>
            </div>
            <div class="report-metrics report-metrics-compact">
                <div><small>{{ __('company/messages.reports.ratings_count') }}</small><strong>{{ number_format($ratings['count']) }}</strong></div>
                <div><small>{{ __('company/messages.reports.average_rating') }}</small><strong dir="ltr">{{ $ratings['average'] !== null ? $ratings['average'] . ' ★' : '—' }}</strong></div>
            </div>
            @include('partials.report-breakdown', ['title' => __('company/messages.reports.by_stars'), 'items' => $ratings['count'] ? $ratings['by_stars'] : []])
        </section>

        @if ($advertising)
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>{{ __('company/messages.reports.advertising') }}</h2>
                        <p>{{ __('company/messages.reports.advertising_sub') }}</p>
                    </div>
                    <a class="text-link no-print" href="{{ route('company.ads.report') }}">{{ __('company/messages.reports.ad_details') }} ↗</a>
                </div>
                <div class="report-metrics report-metrics-compact">
                    <div><small>{{ __('company/messages.reports.ad_views') }}</small><strong>{{ number_format($advertising['views']) }}</strong></div>
                    <div><small>{{ __('company/messages.reports.ad_clicks') }}</small><strong>{{ number_format($advertising['clicks']) }}</strong>
                        <span>{{ $advertising['click_rate'] !== null ? __('company/messages.reports.of_views', ['rate' => $advertising['click_rate']]) : __('company/messages.reports.no_views') }}</span></div>
                    <div><small>{{ __('company/messages.reports.ads_running') }}</small><strong>{{ number_format($advertising['active']) }}</strong>
                        <span>{{ __('company/messages.reports.requests_waiting', ['count' => number_format($advertising['requested']), 'awaiting' => number_format($advertising['awaiting_payment'])]) }}</span></div>
                    @forelse ($advertising['paid'] as $paid)
                        <div><small>{{ __('company/messages.reports.paid_currency', ['currency' => $paid['currency']]) }}</small><strong>{{ number_format($paid['total']) }}</strong></div>
                    @empty
                        <div><small>{{ __('company/messages.reports.paid') }}</small><strong>0</strong></div>
                    @endforelse
                </div>
                @include('partials.report-breakdown', [
                    'title' => __('company/messages.reports.by_product'),
                    'items' => $advertising['by_product'],
                    'labels' => collect(\App\Models\AdSale::PRODUCTS)->mapWithKeys(fn($label, $key) => [$key => company_label('ads.products.' . $key, $label)])->all(),
                ])
            </section>
        @endif
    </div>

    <p class="report-generated">{{ __('company/messages.reports.generated', ['date' => now()->translatedFormat('M d, Y H:i')]) }}</p>
@endsection

@push('scripts')
    <script>
        // Show the date fields only for a custom range.
        document.querySelectorAll('[data-report-filters]').forEach(form => {
            const preset = form.querySelector('[name="preset"]');
            const toggle = () => form.querySelectorAll('[data-custom-range]').forEach(el => {
                el.hidden = preset.value !== 'custom';
            });

            preset.addEventListener('change', () => {
                toggle();
                if (preset.value !== 'custom') form.submit();
            });

            toggle();
        });
    </script>
@endpush

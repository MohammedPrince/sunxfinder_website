@extends('admin.layouts.app')
@section('title', 'Reports')
@section('breadcrumb', 'Reports')
@section('eyebrow', 'INSIGHTS')
@section('page-title', 'Reports')
@section('page-description', 'Activity, performance and health of the SunXFinder marketplace for any period.')
@section('page-actions')
    <button type="button" class="btn btn-light no-print" onclick="window.print()">Print / PDF</button>
@endsection

@section('content')
    @php
        $from = $period['from'];
        $to = $period['to'];
        $exportQuery = ['preset' => $period['preset'], 'from' => $from->toDateString(), 'to' => $to->toDateString()];

        $money = fn($value) => $value === null ? '—' : number_format($value, 0);
        $quoteRef = fn($id) => '#Q-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);

        $trendBuckets = $trend['buckets'];
        // Thin out axis labels on long ranges so they stay readable.
        $labelEvery = max(1, (int) ceil(count($trendBuckets) / 12));
    @endphp

    {{-- Period filter --}}
    <section class="panel report-filters no-print">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="report-filter-form" data-report-filters>
            <div class="form-group">
                <label for="preset">Period</label>
                <select class="select-control" id="preset" name="preset">
                    @foreach ($presets as $value => $label)
                        <option value="{{ $value }}" @selected($period['preset'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" data-custom-range>
                <label for="from">From</label>
                <input class="form-control" type="date" id="from" name="from" value="{{ $from->toDateString() }}">
            </div>
            <div class="form-group" data-custom-range>
                <label for="to">To</label>
                <input class="form-control" type="date" id="to" name="to" value="{{ $to->toDateString() }}">
            </div>
            <button type="submit" class="btn btn-primary">Apply</button>
        </form>
        @if ($errors->any())
            <small class="field-error">{{ $errors->first() }}</small>
        @endif
    </section>

    <p class="report-period">
        <strong>{{ $period['label'] }}</strong>:
        {{ $from->format('M d, Y') }} – {{ $to->format('M d, Y') }} ({{ number_format($period['days']) }}
        {{ \Illuminate\Support\Str::plural('day', $period['days']) }})
        @if ($period['previous_from'])
            · compared with {{ $period['previous_from']->format('M d') }} – {{ $period['previous_to']->format('M d, Y') }}
        @endif
    </p>

    {{-- Key figures --}}
    <section class="report-kpis">
        @foreach ($kpis as $kpi)
            <article class="stat-card report-kpi">
                <div class="stat-top"><span>{{ $kpi['label'] }}</span></div>
                <div class="stat-value">{{ number_format($kpi['value']) }}{{ $kpi['suffix'] ?? '' }}</div>
                <div class="stat-foot">
                    @if (isset($kpi['hint']))
                        {{ $kpi['hint'] }}
                    @elseif ($kpi['change'] !== null)
                        <b class="{{ $kpi['change'] >= 0 ? 'positive' : 'negative' }}">
                            {{ $kpi['change'] >= 0 ? '↗' : '↘' }} {{ abs($kpi['change']) }}%
                        </b>
                        vs previous ({{ number_format($kpi['previous']) }})
                    @elseif ($kpi['previous'] !== null)
                        <span class="gray-text">Previous: {{ number_format($kpi['previous']) }}</span>
                    @endif
                </div>
            </article>
        @endforeach
    </section>

    {{-- Trend --}}
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Activity trend</h2>
                <p>Quote requests and offers per {{ $trend['monthly'] ? 'month' : 'day' }}</p>
            </div>
            <div class="report-trend-totals">
                <span>New customers <strong>{{ number_format(collect($trendBuckets)->sum('customers')) }}</strong></span>
                <span>New companies <strong>{{ number_format(collect($trendBuckets)->sum('companies')) }}</strong></span>
            </div>
        </div>
        <div class="chart-legend">
            <span>● Quote requests</span>
            <span class="orange-text">● Offers</span>
        </div>
        <div class="report-chart-scroll">
            <div class="simple-chart report-chart" style="min-width: {{ max(100, count($trendBuckets) * 18) }}px">
                @php
                    $h = fn($v) => $v > 0 ? max(3, round($v / $trend['max'] * 100)) : 0;
                @endphp
                @foreach ($trendBuckets as $bucket)
                    <div class="chart-col"
                        title="{{ $bucket['label'] }}: {{ $bucket['requests'] }} requests, {{ $bucket['offers'] }} offers, {{ $bucket['customers'] }} new customers, {{ $bucket['companies'] }} new companies">
                        <i style="height: {{ $h($bucket['requests']) }}%"></i>
                        <b style="height: {{ $h($bucket['offers']) }}%"></b>
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

    {{-- Quote requests --}}
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Quote requests</h2>
                <p>Customer demand received in this period</p>
            </div>
            <a class="btn btn-light no-print"
                href="{{ route('admin.reports.export', ['section' => 'quote_requests'] + $exportQuery) }}">↓ CSV</a>
        </div>

        <div class="report-metrics">
            <div><small>Requests</small><strong>{{ number_format($quotes['total']) }}</strong></div>
            <div><small>Received an offer</small><strong>{{ $quotes['with_offers_rate'] }}%</strong>
                <span>{{ number_format($quotes['with_offers']) }} requests</span></div>
            <div><small>Offers per request</small><strong>{{ $quotes['avg_offers'] }}</strong></div>
            <div><small>Time to first offer</small>
                <strong>{{ $quotes['avg_hours_to_first_offer'] !== null ? $quotes['avg_hours_to_first_offer'] . ' h' : '—' }}</strong>
            </div>
        </div>

        <div class="report-grid">
            @include('partials.report-breakdown', ['title' => 'By status', 'items' => $quotes['by_status']])
            @include('partials.report-breakdown', ['title' => 'By service', 'items' => $quotes['by_service']])
            @include('partials.report-breakdown', ['title' => 'By customer city', 'items' => $quotes['by_city']])
        </div>

        <div class="report-subheading">
            <h3>Waiting for a first offer <span class="pill {{ $quotes['backlog_count'] ? 'pill-orange' : 'pill-green' }}">{{ $quotes['backlog_count'] }}</span></h3>
            <p>Pending requests that no company has answered yet (any date), oldest first.</p>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Customer</th>
                        <th>City</th>
                        <th>Service</th>
                        <th>Received</th>
                        <th>Waiting</th>
                        <th class="no-print">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($quotes['backlog'] as $row)
                        <tr>
                            <td><strong>{{ $quoteRef($row['id']) }}</strong></td>
                            <td>{{ $row['customer'] }}</td>
                            <td>{{ $row['city'] }}</td>
                            <td>{{ $row['service'] }}</td>
                            <td>{{ $row['created_at']->format('M d, Y H:i') }}</td>
                            <td>
                                <span class="pill {{ $row['age_hours'] >= 48 ? 'pill-red' : ($row['age_hours'] >= 24 ? 'pill-orange' : 'pill-yellow') }}">
                                    {{ $row['age_hours'] >= 48 ? floor($row['age_hours'] / 24) . ' days' : $row['age_hours'] . ' h' }}
                                </span>
                            </td>
                            <td class="no-print"><a class="row-action" href="{{ route('admin.quote-requests.show', $row['id']) }}">View ↗</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="dashboard-empty"><strong>All caught up.</strong> Every pending request has at least one offer.</td>
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
                <h2>Offers and pricing</h2>
                <p>Offers companies submitted in this period</p>
            </div>
        </div>

        <div class="report-metrics">
            <div><small>Offers</small><strong>{{ number_format($offers['total']) }}</strong></div>
            <div><small>Win rate</small><strong>{{ $offers['win_rate'] !== null ? $offers['win_rate'] . '%' : '—' }}</strong>
                <span>selected ÷ (selected + not selected)</span></div>
        </div>

        <div class="report-grid report-grid-2">
            @include('partials.report-breakdown', [
                'title' => 'By status',
                'items' => $offers['by_status'],
                'labels' => ['submitted' => 'Awaiting customer', 'selected' => 'Selected', 'not_selected' => 'Not selected', 'withdrawn' => 'Withdrawn'],
            ])

            <div class="report-breakdown">
                <h3>Prices by currency</h3>
                @forelse ($offers['prices'] as $price)
                    <div class="table-responsive">
                        <table class="data-table report-mini-table">
                            <tbody>
                                <tr><th>Currency</th><td><strong>{{ $price['currency'] }}</strong> ({{ number_format($price['count']) }} offers)</td></tr>
                                <tr><th>Average offer</th><td>{{ $money($price['avg']) }}</td></tr>
                                <tr><th>Lowest – highest</th><td>{{ $money($price['min']) }} – {{ $money($price['max']) }}</td></tr>
                                <tr><th>Average selected</th><td>{{ $money($price['avg_selected']) }}</td></tr>
                                <tr><th>Total value selected</th><td><strong>{{ $money($price['selected_value']) }}</strong></td></tr>
                            </tbody>
                        </table>
                    </div>
                @empty
                    <p class="report-empty">No offers in this period.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Company performance --}}
    <section class="panel table-panel">
        <div class="panel-heading">
            <div>
                <h2>Company performance</h2>
                <p>Offers and wins in this period; projects and products are current totals</p>
            </div>
            <a class="btn btn-light no-print"
                href="{{ route('admin.reports.export', ['section' => 'companies'] + $exportQuery) }}">↓ CSV</a>
        </div>
        <div class="table-responsive">
            <table class="data-table js-data-table">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Offers</th>
                        <th>Selected</th>
                        <th>Win rate</th>
                        <th>Avg offer (SDG)</th>
                        <th>Avg response</th>
                        <th>Active projects</th>
                        <th>Completed</th>
                        <th>Avg progress</th>
                        <th>Products</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($companyPerformance as $company)
                        <tr>
                            <td>
                                <div class="company-cell">
                                    <span class="company-avatar solar">{{ strtoupper(mb_substr($company['name'], 0, 1)) }}</span>
                                    <span>
                                        <strong>{{ $company['name'] }}</strong>
                                        <small>
                                            {{ $company['city'] }} · {{ ucfirst($company['status']) }}
                                            @if ($company['is_verified']) · ✓ Verified @endif
                                            @unless ($company['is_active']) · Inactive @endunless
                                        </small>
                                    </span>
                                </div>
                            </td>
                            <td>{{ $company['offers'] }}</td>
                            <td><strong>{{ $company['selected'] }}</strong></td>
                            <td>{{ $company['win_rate'] !== null ? $company['win_rate'] . '%' : '—' }}</td>
                            <td>{{ $money($company['avg_price_sdg']) }}</td>
                            <td>{{ $company['avg_response_hours'] !== null ? $company['avg_response_hours'] . ' h' : '—' }}</td>
                            <td>{{ $company['active_projects'] }}</td>
                            <td>{{ $company['completed_projects'] }}</td>
                            <td>{{ $company['avg_progress'] !== null ? $company['avg_progress'] . '%' : '—' }}</td>
                            <td>
                                @if ($company['products'] === 0 && $company['status'] === 'approved')
                                    <span class="pill pill-orange" title="Approved but no products listed">0</span>
                                @else
                                    {{ $company['products'] }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="dashboard-empty"><strong>No companies yet.</strong></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Projects --}}
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Projects</h2>
                <p>Installations created from accepted offers</p>
            </div>
        </div>

        <div class="report-metrics">
            <div><small>Started in period</small><strong>{{ number_format($projects['started_in_period']) }}</strong></div>
            <div><small>Completed in period</small><strong>{{ number_format($projects['completed_in_period']) }}</strong></div>
            <div><small>Avg time to complete</small>
                <strong>{{ $projects['avg_completion_days'] !== null ? $projects['avg_completion_days'] . ' days' : '—' }}</strong></div>
            <div><small>Avg progress (active)</small><strong>{{ $projects['avg_active_progress'] }}%</strong></div>
            <div><small>Progress updates in period</small><strong>{{ number_format($projects['updates_in_period']) }}</strong></div>
        </div>

        <div class="report-grid report-grid-2">
            @include('partials.report-breakdown', [
                'title' => 'All projects by status (' . $projects['total'] . ')',
                'items' => $projects['by_status'],
                'labels' => ['in_progress' => 'In progress'],
            ])
            @include('partials.report-breakdown', ['title' => 'Milestones by status', 'items' => $projects['milestones']])
        </div>

        <div class="report-subheading" id="stalled-projects">
            <h3>Stalled projects <span class="pill {{ count($projects['stalled']) ? 'pill-red' : 'pill-green' }}">{{ count($projects['stalled']) }}</span></h3>
            <p>Active projects with no progress update for {{ $stalledDays }} days or more.</p>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Company</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>No update for</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projects['stalled'] as $row)
                        <tr>
                            <td><strong>Project #{{ $row['id'] }}</strong><small style="display:block">{{ $quoteRef($row['quote_id']) }}</small></td>
                            <td>{{ $row['company'] }}</td>
                            <td>{{ $row['customer'] }}</td>
                            <td><span class="pill pill-yellow">{{ \Illuminate\Support\Str::headline($row['status']) }}</span></td>
                            <td>{{ $row['progress'] }}%</td>
                            <td><span class="pill pill-red">{{ $row['idle_days'] }} days</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="dashboard-empty"><strong>No stalled projects.</strong> Every active project was updated recently.</td>
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
                <h2>Customers</h2>
                <p>Totals are current; "new" and requests are for this period</p>
            </div>
            <a class="btn btn-light no-print"
                href="{{ route('admin.reports.export', ['section' => 'customers'] + $exportQuery) }}">↓ New customers CSV</a>
        </div>

        <div class="report-metrics">
            <div><small>Total customers</small><strong>{{ number_format($customers['total']) }}</strong></div>
            <div><small>Active</small><strong>{{ number_format($customers['active']) }}</strong></div>
            <div><small>New in period</small><strong>{{ number_format($customers['new_in_period']) }}</strong></div>
            <div><small>Requested a quote in period</small><strong>{{ number_format($customers['with_requests_in_period']) }}</strong></div>
            <div><small>Using the app</small><strong>{{ $customers['app_users_rate'] }}%</strong>
                <span>{{ number_format($customers['app_users']) }} customers · {{ number_format($customers['notifications_enabled']) }} with notifications on</span></div>
            <div><small>No email (can't reset by email)</small><strong>{{ number_format($customers['without_email']) }}</strong></div>
        </div>

        <div class="report-grid">
            @include('partials.report-breakdown', ['title' => 'By status', 'items' => $customers['by_status']])
            @include('partials.report-breakdown', ['title' => 'By city', 'items' => $customers['by_city']])
            @include('partials.report-breakdown', ['title' => 'By gender', 'items' => $customers['by_gender']])
            @include('partials.report-breakdown', ['title' => 'App devices by platform', 'items' => $customers['by_platform'], 'labels' => ['ios' => 'iOS', 'android' => 'Android']])
            @include('partials.report-breakdown', ['title' => 'App language', 'items' => $customers['by_lang'], 'labels' => ['en' => 'English', 'ar' => 'Arabic']])
        </div>

        <div class="report-subheading">
            <h3>Most active customers</h3>
            <p>By quote requests in this period.</p>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>City</th>
                        <th>Requests</th>
                        <th>Accepted an offer</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers['top_requesters'] as $row)
                        <tr>
                            <td><strong>{{ $row['name'] }}</strong></td>
                            <td>{{ $row['city'] }}</td>
                            <td>{{ $row['requests'] }}</td>
                            <td>{{ $row['accepted'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="dashboard-empty">No quote requests in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Companies, catalog, notifications --}}
    <div class="report-columns">
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Companies</h2>
                    <p>Provider directory health</p>
                </div>
            </div>
            <div class="report-metrics report-metrics-compact">
                <div><small>Total</small><strong>{{ number_format($companies['total']) }}</strong></div>
                <div><small>New in period</small><strong>{{ number_format($companies['new_in_period']) }}</strong></div>
                <div><small>Verified</small><strong>{{ number_format($companies['verified']) }}</strong></div>
                <div><small>Active</small><strong>{{ number_format($companies['active']) }}</strong></div>
                <div><small>In carousel</small><strong>{{ number_format($companies['in_carousel']) }}</strong></div>
                <div><small>Approved, no products</small><strong>{{ number_format($companies['approved_without_products']) }}</strong></div>
            </div>
            @include('partials.report-breakdown', ['title' => 'By status', 'items' => $companies['by_status']])
            @include('partials.report-breakdown', ['title' => 'By city', 'items' => $companies['by_city']])
        </section>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Product catalog</h2>
                    <p>Current products and suppliers</p>
                </div>
            </div>
            <div class="report-metrics report-metrics-compact">
                <div><small>Products</small><strong>{{ number_format($catalog['products']) }}</strong></div>
                <div><small>Visible</small><strong>{{ number_format($catalog['active_products']) }}</strong></div>
                <div><small>Suppliers</small><strong>{{ number_format($catalog['active_suppliers']) }}/{{ number_format($catalog['suppliers']) }}</strong><span>active / total</span></div>
                <div><small>Companies listing products</small><strong>{{ number_format($catalog['companies_with_products']) }}</strong></div>
            </div>
            @include('partials.report-breakdown', ['title' => 'By type', 'items' => $catalog['by_type'], 'labels' => ['solar_panel' => 'Solar panel']])
            @include('partials.report-breakdown', ['title' => 'By supplier', 'items' => $catalog['by_supplier']])
        </section>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Push notifications</h2>
                    <p>Sent in this period</p>
                </div>
            </div>
            <div class="report-metrics report-metrics-compact">
                <div><small>Sent</small><strong>{{ number_format($notifications['sent']) }}</strong></div>
                <div><small>Recipients</small><strong>{{ number_format($notifications['recipients']) }}</strong></div>
                <div><small>Read rate</small>
                    <strong>{{ $notifications['read_rate'] !== null ? $notifications['read_rate'] . '%' : '—' }}</strong>
                    <span>{{ number_format($notifications['read']) }} read</span></div>
            </div>
            @include('partials.report-breakdown', ['title' => 'By sender', 'items' => $notifications['by_sender']])
            @include('partials.report-breakdown', ['title' => 'By type', 'items' => $notifications['by_type']])
        </section>
    </div>

    <p class="report-generated">Generated {{ now()->format('M d, Y H:i') }}</p>
@endsection

@section('scripts')
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
@endsection

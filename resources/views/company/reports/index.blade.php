@extends('company.layouts.app')
@section('title', 'Reports')
@section('breadcrumb', 'Reports')
@section('eyebrow', 'COMPANY INSIGHTS')
@section('page-title', 'Reports')
@section('page-description', 'Your requests, offers, projects and customers for any period.')
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
        $labelEvery = max(1, (int) ceil(count($trendBuckets) / 12));
        $barHeight = fn($v) => $v > 0 ? max(3, round($v / $trend['max'] * 100)) : 0;

        $statusLabels = [
            'pending' => 'Pending',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'in_progress' => 'In progress',
            'submitted' => 'Awaiting customer',
            'selected' => 'Won',
            'not_selected' => 'Not selected',
            'withdrawn' => 'Withdrawn',
        ];

        $market = $offers['market'];
    @endphp

    {{-- Period filter --}}
    <section class="panel report-filters no-print">
        <form method="GET" action="{{ route('company.reports.index') }}" class="report-filter-form" data-report-filters>
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
                        vs previous ({{ $key === 'value_won' ? $money($kpi['previous']) : number_format($kpi['previous']) }})
                    @elseif ($kpi['previous'] !== null)
                        <span class="gray-text">Previous: {{ $key === 'value_won' ? $money($kpi['previous']) : number_format($kpi['previous']) }}</span>
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
                <p>Requests you received and offers you sent per {{ $trend['monthly'] ? 'month' : 'day' }}</p>
            </div>
            <div class="report-trend-totals">
                <span>Offers won <strong>{{ number_format(collect($trendBuckets)->sum('won')) }}</strong></span>
            </div>
        </div>
        <div class="chart-legend">
            <span>● Requests received</span>
            <span class="orange-text">● Offers sent</span>
        </div>
        <div class="report-chart-scroll">
            <div class="simple-chart report-chart" style="min-width: {{ max(100, count($trendBuckets) * 18) }}px">
                @foreach ($trendBuckets as $bucket)
                    <div class="chart-col"
                        title="{{ $bucket['label'] }}: {{ $bucket['requests'] }} requests, {{ $bucket['offers'] }} offers sent, {{ $bucket['won'] }} won">
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
                <h2>Requests and response</h2>
                <p>Requests assigned to you or open to all companies in this period</p>
            </div>
        </div>

        <div class="report-metrics">
            <div><small>Requests received</small><strong>{{ number_format($kpis['requests_received']['value']) }}</strong></div>
            <div><small>Assigned to you</small><strong>{{ number_format($requests['assigned_to_you']) }}</strong>
                <span>customer chose you directly or accepted your offer</span></div>
            <div><small>Open to all companies</small><strong>{{ number_format($requests['open_marketplace']) }}</strong></div>
            <div><small>Response rate</small><strong>{{ $kpis['response_rate']['value'] }}%</strong>
                <span>{{ $kpis['response_rate']['hint'] }}</span></div>
            <div><small>Avg time to send an offer</small>
                <strong>{{ $requests['avg_response_hours'] !== null ? $requests['avg_response_hours'] . ' h' : '—' }}</strong>
                <span>Faster offers win more often</span></div>
        </div>

        <div class="report-grid">
            @include('partials.report-breakdown', ['title' => 'By status', 'items' => $requests['by_status'], 'labels' => $statusLabels])
            @include('partials.report-breakdown', ['title' => 'By service', 'items' => $requests['by_service']])
            @include('partials.report-breakdown', ['title' => 'By customer city', 'items' => $requests['by_city']])
        </div>

        <div class="report-subheading">
            <h3>Waiting for your offer <span class="pill {{ $requests['awaiting_count'] ? 'pill-orange' : 'pill-green' }}">{{ $requests['awaiting_count'] }}</span></h3>
            <p>Open requests you have not offered on yet (any date), oldest first.</p>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Customer</th>
                        <th>City</th>
                        <th>Quote name</th>
                        <th>Other offers</th>
                        <th>Waiting</th>
                        <th class="no-print">Action</th>
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
                                    {{ $row['age_hours'] >= 48 ? floor($row['age_hours'] / 24) . ' days' : $row['age_hours'] . ' h' }}
                                </span>
                            </td>
                            <td class="no-print"><a class="row-action" href="{{ route('company.quote-requests.show', $row['id']) }}">Send offer ↗</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="dashboard-empty"><strong>All caught up.</strong> You have offered on every open request.</td>
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
                <p>Offers you sent in this period</p>
            </div>
            <a class="btn btn-light no-print" href="{{ route('company.reports.export', ['section' => 'offers'] + $exportQuery) }}">↓ CSV</a>
        </div>

        <div class="report-metrics">
            <div><small>Offers sent</small><strong>{{ number_format($offers['total']) }}</strong></div>
            <div><small>Win rate</small><strong>{{ $kpis['win_rate']['value'] }}%</strong><span>{{ $kpis['win_rate']['hint'] }}</span></div>
            <div><small>Your average vs other companies</small>
                <strong>
                    @if ($market['diff'] === null)
                        —
                    @else
                        <span class="{{ $market['diff'] <= 0 ? 'positive' : 'warning' }}">{{ $market['diff'] > 0 ? '+' : '' }}{{ $market['diff'] }}%</span>
                    @endif
                </strong>
                <span>on the same requests (SDG): you {{ $money($market['your_avg']) }} · others {{ $money($market['market_avg']) }}</span></div>
            <div><small>Lost offers: your price vs chosen price</small>
                <strong>
                    @if ($market['lost_diff'] === null)
                        —
                    @else
                        <span class="{{ $market['lost_diff'] <= 0 ? 'positive' : 'warning' }}">{{ $market['lost_diff'] > 0 ? '+' : '' }}{{ $market['lost_diff'] }}%</span>
                    @endif
                </strong>
                <span>{{ $market['lost_count'] }} lost · you {{ $money($market['lost_your_avg']) }} · chosen {{ $money($market['lost_winning_avg']) }}</span></div>
        </div>

        <div class="report-grid report-grid-2">
            @include('partials.report-breakdown', ['title' => 'By status', 'items' => $offers['by_status'], 'labels' => $statusLabels])

            <div class="report-breakdown">
                <h3>Prices by currency</h3>
                @forelse ($offers['prices'] as $price)
                    <div class="table-responsive">
                        <table class="data-table report-mini-table">
                            <tbody>
                                <tr><th>Currency</th><td><strong>{{ $price['currency'] }}</strong> ({{ number_format($price['count']) }} offers)</td></tr>
                                <tr><th>Average offer</th><td>{{ $money($price['avg']) }}</td></tr>
                                <tr><th>Lowest – highest</th><td>{{ $money($price['min']) }} – {{ $money($price['max']) }}</td></tr>
                                <tr><th>Average won offer</th><td>{{ $money($price['avg_selected']) }}</td></tr>
                                <tr><th>Total value won</th><td><strong>{{ $money($price['selected_value']) }}</strong></td></tr>
                            </tbody>
                        </table>
                    </div>
                @empty
                    <p class="report-empty">No offers in this period.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Projects --}}
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Projects</h2>
                <p>Installations from offers customers accepted</p>
            </div>
            <a class="btn btn-light no-print" href="{{ route('company.reports.export', ['section' => 'projects'] + $exportQuery) }}">↓ CSV</a>
        </div>

        <div class="report-metrics">
            <div><small>Started in period</small><strong>{{ number_format($projects['started_in_period']) }}</strong></div>
            <div><small>Completed in period</small><strong>{{ number_format($projects['completed_in_period']) }}</strong></div>
            <div><small>Avg time to complete</small>
                <strong>{{ $projects['avg_completion_days'] !== null ? $projects['avg_completion_days'] . ' days' : '—' }}</strong></div>
            <div><small>Avg progress (active)</small><strong>{{ $projects['avg_active_progress'] }}%</strong></div>
            <div><small>Updates posted in period</small><strong>{{ number_format($projects['updates_in_period']) }}</strong>
                <span>Customers see these in the app</span></div>
        </div>

        <div class="report-grid report-grid-2">
            @include('partials.report-breakdown', ['title' => 'All projects by status (' . $projects['total'] . ')', 'items' => $projects['by_status'], 'labels' => $statusLabels])
            @include('partials.report-breakdown', ['title' => 'Milestones by status', 'items' => $projects['milestones'], 'labels' => $statusLabels])
        </div>

        <div class="report-subheading">
            <h3>Active projects
                @if ($projects['stalled_count'])
                    <span class="pill pill-red">{{ $projects['stalled_count'] }} need an update</span>
                @endif
            </h3>
            <p>Least progress first. Projects with no update for {{ $stalledDays }}+ days are flagged.</p>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Customer</th>
                        <th>Quote name</th>
                        <th>Progress</th>
                        <th>Last update</th>
                        <th class="no-print">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projects['active'] as $row)
                        <tr>
                            <td><strong>Project #{{ $row['id'] }}</strong><small style="display:block">{{ $quoteRef($row['quote_id']) }}</small></td>
                            <td>{{ $row['customer'] }}</td>
                            <td>{{ $row['service'] }}</td>
                            <td style="min-width:140px">
                                <div class="report-progress"><span style="width: {{ $row['progress'] }}%"></span></div>
                                <small>{{ $row['progress'] }}% · {{ $statusLabels[$row['status']] ?? $row['status'] }}</small>
                            </td>
                            <td>
                                @if ($row['stalled'])
                                    <span class="pill pill-red">{{ $row['idle_days'] }} days ago</span>
                                @else
                                    {{ $row['idle_days'] === 0 ? 'Today' : $row['idle_days'] . ' days ago' }}
                                @endif
                            </td>
                            <td class="no-print"><a class="row-action" href="{{ route('company.projects.show', $row['id']) }}">Update ↗</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="dashboard-empty">No active projects right now.</td>
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
                <p>Customers who chose your offers</p>
            </div>
            <a class="btn btn-light no-print" href="{{ route('company.reports.export', ['section' => 'customers'] + $exportQuery) }}">↓ CSV</a>
        </div>

        <div class="report-metrics">
            <div><small>Customers served (all time)</small><strong>{{ number_format($customers['served_total']) }}</strong></div>
            <div><small>Won in period</small><strong>{{ number_format($customers['won_in_period']) }}</strong></div>
            <div><small>Repeat customers</small><strong>{{ number_format($customers['repeat']) }}</strong><span>2+ projects with you</span></div>
            <div><small>Avg project value (SDG)</small><strong>{{ $money($customers['avg_project_value']) }}</strong></div>
        </div>

        <div class="report-grid report-grid-2">
            @include('partials.report-breakdown', ['title' => 'Customers served by city', 'items' => $customers['by_city']])

            <div class="report-breakdown">
                <h3>Top customers in this period</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>City</th>
                                <th>Offers won</th>
                                <th>Value (SDG)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($customers['top'] as $row)
                                <tr>
                                    <td><strong>{{ $row['name'] }}</strong><small style="display:block">Last: {{ $row['last_win_at']->format('M d, Y') }}</small></td>
                                    <td>{{ $row['city'] }}</td>
                                    <td>{{ $row['wins'] }}</td>
                                    <td>{{ $money($row['value_sdg']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="dashboard-empty">No offers won in this period.</td>
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
                    <h2>Your products</h2>
                    <p>Shown to customers on your profile</p>
                </div>
            </div>
            <div class="report-metrics report-metrics-compact">
                <div><small>Products</small><strong>{{ number_format($products['total']) }}</strong></div>
                <div><small>Visible</small><strong>{{ number_format($products['active']) }}</strong></div>
                <div><small>Hidden</small><strong>{{ number_format($products['total'] - $products['active']) }}</strong></div>
            </div>
            @include('partials.report-breakdown', ['title' => 'By type', 'items' => $products['by_type'], 'labels' => ['solar_panel' => 'Solar panel']])
            @include('partials.report-breakdown', ['title' => 'By supplier', 'items' => $products['by_supplier']])
        </section>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Push notifications</h2>
                    <p>You sent in this period</p>
                </div>
            </div>
            <div class="report-metrics report-metrics-compact">
                <div><small>Sent</small><strong>{{ number_format($notifications['sent']) }}</strong></div>
                <div><small>Recipients</small><strong>{{ number_format($notifications['recipients']) }}</strong></div>
                <div><small>Read rate</small>
                    <strong>{{ $notifications['read_rate'] !== null ? $notifications['read_rate'] . '%' : '—' }}</strong>
                    <span>{{ number_format($notifications['read']) }} read</span></div>
            </div>
            @include('partials.report-breakdown', ['title' => 'By type', 'items' => $notifications['by_type']])
        </section>
    </div>

    <p class="report-generated">Generated {{ now()->format('M d, Y H:i') }}</p>
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

{{-- Package offers panel for the admin and company Reports pages.
     Expects $stats (BusinessStats::packageOffers) and $exportUrl; optional $offerUrl (fn(int $id): string),
     $manageUrl and $showCompany (admin: one row per company's offer). --}}
@php
    // company_text() returns the English fallback as is, so fill its :placeholders here.
    $t = fn(string $key, string $fallback, array $replace = []) => company_text($key,
        strtr($fallback, collect($replace)->mapWithKeys(fn($value, $name) => [':' . $name => $value])->all()), $replace);
    $offerUrl = $offerUrl ?? null;
    $manageUrl = $manageUrl ?? null;
    $showCompany = $showCompany ?? false;
    $creatorLabels = [
        'company' => $t('reports.pkg_by_company', 'Company'),
        'admin' => $t('reports.pkg_by_admin', 'Admin campaign'),
    ];
@endphp

<section class="panel" id="package-offers">
    <div class="panel-heading">
        <div>
            <h2>{{ $t('reports.pkg_title', 'Package offers') }}</h2>
            <p>{{ $t('reports.pkg_sub', 'Ready systems pushed to customers, and how they answered in this period') }}</p>
        </div>
        <div class="no-print" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            @if ($manageUrl)
                <a class="text-link" href="{{ $manageUrl }}">{{ $t('reports.pkg_manage', 'Manage offers') }} ↗</a>
            @endif
            <a class="btn btn-light" href="{{ $exportUrl }}">↓ CSV</a>
        </div>
    </div>

    <div class="report-metrics">
        <div><small>{{ $t('reports.pkg_sent', 'Offers sent') }}</small><strong>{{ number_format($stats['sent']) }}</strong>
            <span>{{ $t('reports.pkg_open_now', ':count open now', ['count' => number_format($stats['open_now'])]) }}</span></div>
        <div><small>{{ $t('reports.pkg_reached', 'Customers reached') }}</small><strong>{{ number_format($stats['reached']) }}</strong></div>
        <div><small>{{ $t('reports.pkg_accepted', 'Accepted') }}</small><strong>{{ number_format($stats['accepted']) }}</strong>
            <span>{{ $stats['accept_rate'] !== null
                ? $t('reports.pkg_rate', ':rate% of customers reached', ['rate' => $stats['accept_rate']])
                : $t('reports.pkg_no_reach', 'No customers reached yet') }}</span></div>
        <div><small>{{ $t('reports.pkg_declined', 'Declined') }}</small><strong>{{ number_format($stats['declined']) }}</strong></div>
        @forelse ($stats['won'] as $won)
            <div><small>{{ $t('reports.pkg_value', 'Value accepted (:currency)', ['currency' => $won['currency']]) }}</small>
                <strong>{{ number_format($won['total']) }}</strong>
                <span>{{ $t('reports.pkg_projects', ':count projects created', ['count' => number_format($won['count'])]) }}</span></div>
        @empty
            <div><small>{{ $t('reports.pkg_value_plain', 'Value accepted') }}</small><strong>0</strong></div>
        @endforelse
    </div>

    <div class="report-grid {{ $showCompany ? 'report-grid-2' : '' }}">
        @if ($showCompany)
            @include('partials.report-breakdown', ['title' => 'Sent by', 'items' => $stats['by_creator'], 'labels' => $creatorLabels])
        @endif

        <div class="report-breakdown">
            <h3>{{ $t('reports.pkg_top', 'Most accepted offers') }}</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ $t('reports.pkg_offer', 'Offer') }}</th>
                            @if ($showCompany)<th>Company</th>@endif
                            <th>{{ $t('reports.pkg_price', 'Price') }}</th>
                            <th>{{ $t('reports.pkg_accepted', 'Accepted') }}</th>
                            <th>{{ $t('reports.pkg_declined', 'Declined') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stats['top'] as $row)
                            <tr>
                                <td>
                                    @if ($offerUrl)
                                        <a class="text-link" href="{{ $offerUrl($row['id']) }}"><strong>{{ $row['title'] }}</strong></a>
                                    @else
                                        <strong>{{ $row['title'] }}</strong>
                                    @endif
                                </td>
                                @if ($showCompany)<td>{{ $row['company'] ?? '—' }}</td>@endif
                                <td>{{ number_format($row['price']) }} {{ $row['currency'] }}</td>
                                <td>{{ number_format($row['accepted']) }}</td>
                                <td>{{ number_format($row['declined']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $showCompany ? 5 : 4 }}" class="dashboard-empty">{{ $t('reports.pkg_none', 'No customer answered a package offer in this period.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

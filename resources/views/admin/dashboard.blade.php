@extends('admin.layouts.app')
@section('title', 'Dashboard') @section('breadcrumb', 'Dashboard')
@section('eyebrow', 'SUNXFINDER MARKETPLACE') @section('page-title', 'Dashboard overview')
@section('page-description', 'Here is what is happening across your solar marketplace today.')
@section('page-actions')<button class="btn btn-light"
        data-demo-toast="Connect report export to your reporting endpoint.">↓ Export report</button><a class="btn btn-primary"
    href="{{ route('admin.companies.index') }}">＋ Manage companies</a>@endsection
@section('content')
    <section class="stats-grid">
        <article class="stat-card">
            <div class="stat-top"><span>Total companies</span><span class="stat-icon blue">◫</span></div>
            <div class="stat-value">128</div>
            <div class="stat-foot"><b class="positive">↗ 12.8%</b> vs last month</div>
            <div class="mini-bars"><i style="height:30%"></i><i style="height:45%"></i><i style="height:38%"></i><i
                    style="height:68%"></i><i style="height:56%"></i><i style="height:90%"></i></div>
        </article>
        <article class="stat-card">
            <div class="stat-top"><span>Pending verification</span><span class="stat-icon orange">◷</span></div>
            <div class="stat-value">14</div>
            <div class="stat-foot"><b class="warning">Needs attention</b></div>
            <div class="stat-progress"><span style="width:42%"></span></div><small>Companies awaiting review</small>
        </article>
        <article class="stat-card">
            <div class="stat-top"><span>Quote requests</span><span class="stat-icon purple">⇄</span></div>
            <div class="stat-value">356</div>
            <div class="stat-foot"><b class="positive">↗ 8.2%</b> vs last month</div>
            <div class="sparkline">▁▃▂▅▄▆▅█</div>
        </article>
        <article class="stat-card">
            <div class="stat-top"><span>Active customers</span><span class="stat-icon green">♙</span></div>
            <div class="stat-value">2,481</div>
            <div class="stat-foot"><b class="positive">↗ 18.4%</b> vs last month</div>
            <div class="stat-progress green-progress"><span style="width:72%"></span></div><small>Strong growth this
                month</small>
        </article>
    </section>
    <section class="content-grid">
        <article class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Marketplace activity</h2>
                    <p>Illustrative weekly activity overview</p>
                </div><select class="select-control">
                    <option>Last 7 days</option>
                    <option>Last 30 days</option>
                </select>
            </div>
            <div class="chart-legend"><span>● Quote requests</span><span class="orange-text">● New companies</span></div>
            <div class="simple-chart">
                <div class="chart-col"><i style="height:48%"></i><b style="height:25%"></b></div>
                <div class="chart-col"><i style="height:66%"></i><b style="height:35%"></b></div>
                <div class="chart-col"><i style="height:57%"></i><b style="height:29%"></b></div>
                <div class="chart-col"><i style="height:78%"></i><b style="height:43%"></b></div>
                <div class="chart-col"><i style="height:63%"></i><b style="height:38%"></b></div>
                <div class="chart-col"><i style="height:90%"></i><b style="height:52%"></b></div>
                <div class="chart-col"><i style="height:74%"></i><b style="height:44%"></b></div>
            </div>
            <div class="chart-days">
                <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
            </div>
        </article>
        <article class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Company verification</h2>
                    <p>Provider profile status</p>
                </div>
            </div>
            <div class="donut-area">
                <div class="donut">
                    <div><strong>128</strong><small>Companies</small></div>
                </div>
            </div>
            <div class="status-list">
                <div><span>● Verified</span><strong>86 <small>67%</small></strong></div>
                <div><span class="orange-text">● Pending</span><strong>14 <small>11%</small></strong></div>
                <div><span class="gray-text">● Draft / inactive</span><strong>28 <small>22%</small></strong></div>
            </div><a class="text-link" href="{{ route('admin.companies.index') }}">Review companies →</a>
        </article>
    </section>
    <section class="panel table-panel">
        <div class="panel-heading">
            <div>
                <h2>Recently added companies</h2>
                <p>Latest providers joining SunXFinder</p>
            </div><a class="text-link" href="{{ route('admin.companies.index') }}">View all →</a>
        </div>
        <div class="table-responsive">
            <table class="data-table js-data-table">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Location</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th>Verification</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="company-cell"><span class="company-avatar solar">S</span><span><strong>Solar Horizon
                                        Co.</strong><small>SF-00128</small></span></div>
                        </td>
                        <td>Khartoum</td>
                        <td>Oct 11, 2026</td>
                        <td><span class="pill pill-green">Active</span></td>
                        <td><span class="verify verified">✓ Verified</span></td>
                        <td><a class="row-action" href="{{ route('admin.companies.index') }}">View ↗</a></td>
                    </tr>
                    <tr>
                        <td>
                            <div class="company-cell"><span class="company-avatar sun">R</span><span><strong>Renewable
                                        Future</strong><small>SF-00127</small></span></div>
                        </td>
                        <td>Port Sudan</td>
                        <td>Oct 10, 2026</td>
                        <td><span class="pill pill-green">Active</span></td>
                        <td><span class="verify pending">◷ Pending</span></td>
                        <td><a class="row-action" href="{{ route('admin.companies.index') }}">Review ↗</a></td>
                    </tr>
                    <tr>
                        <td>
                            <div class="company-cell"><span class="company-avatar blue-bg">E</span><span><strong>EcoPower
                                        Solutions</strong><small>SF-00126</small></span></div>
                        </td>
                        <td>Wad Madani</td>
                        <td>Oct 09, 2026</td>
                        <td><span class="pill pill-yellow">Under review</span></td>
                        <td><span class="verify pending">◷ Pending</span></td>
                        <td><a class="row-action" href="{{ route('admin.companies.index') }}">Review ↗</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
@endsection

@extends('admin.layouts.app')

@php $name = $customer->full_name ?: $customer->username; @endphp

@section('title', 'Terms history · ' . $name)
@section('breadcrumb', 'Terms Acceptance')
@section('eyebrow', 'SYSTEM')
@section('page-title', $name)
@section('page-description', 'Every time this customer accepted the Terms & Conditions, newest first.')

@section('page-actions')
    <a href="{{ route('admin.terms-acceptances.index') }}" class="btn btn-light">← All customers</a>
    <a href="{{ route('admin.terms-acceptances.export', ['customer_id' => $customer->id]) }}" class="btn btn-light">⤓ Export CSV</a>
@endsection

@section('content')
    @php
        $platforms = \App\Models\CustomerTermsAcceptance::PLATFORMS;
        $sources = \App\Models\CustomerTermsAcceptance::SOURCES;
    @endphp

    <div class="metrics-strip">
        <div>
            <small>Customer</small>
            <strong>{{ '@' . $customer->username }}</strong>
        </div>
        <div>
            <small>Status</small>
            <strong>{{ $customer->trashed() ? 'Deleted' : ucfirst($customer->status) }}</strong>
        </div>
        <div>
            <small>Latest version accepted</small>
            <strong class="{{ $customer->terms_accepted_version && $customer->terms_accepted_version !== $currentVersion ? 'warning' : '' }}">
                {{ $customer->terms_accepted_version ? \Illuminate\Support\Carbon::parse($customer->terms_accepted_version)->format('M j, Y') : '—' }}
            </strong>
        </div>
        <div>
            <small>Current Terms version</small>
            <strong>{{ $currentVersion ? \Illuminate\Support\Carbon::parse($currentVersion)->format('M j, Y') : '—' }}</strong>
        </div>
        <div>
            <small>Acceptances</small>
            <strong>{{ number_format($acceptances->count()) }}</strong>
        </div>
    </div>

    <section class="panel table-panel">
        <div class="panel-heading">
            <div>
                <h2>Acceptance history</h2>
                <p>Time and IP address are recorded by the server.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Accepted</th>
                        <th>Terms version</th>
                        <th>Language</th>
                        <th>Where</th>
                        <th>Device</th>
                        <th>IP address</th>
                        <th>User agent</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($acceptances as $row)
                        <tr>
                            <td style="white-space:nowrap">
                                {{ $row->accepted_at->format('M d, Y H:i:s') }}
                                <small class="cell-note">{{ $row->accepted_at->diffForHumans() }}</small>
                            </td>
                            <td>
                                {{ \Illuminate\Support\Carbon::parse($row->terms_version)->format('M j, Y') }}
                                @if ($row->terms_version === $currentVersion)
                                    <span class="pill pill-green">Current</span>
                                @endif
                            </td>
                            <td>{{ $row->lang === 'ar' ? 'Arabic' : 'English' }}</td>
                            <td>{{ $sources[$row->source] ?? $row->source }}</td>
                            <td>
                                {{ $platforms[$row->platform] ?? ($row->platform ?: '—') }}
                                @if ($row->app_version)
                                    <small class="cell-note">App {{ $row->app_version }}</small>
                                @endif
                            </td>
                            <td><code>{{ $row->ip_address ?: '—' }}</code></td>
                            <td style="max-width:320px"><small class="gray-text">{{ $row->user_agent ?: '—' }}</small></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="dashboard-empty">
                                <strong>This customer has not accepted the Terms yet.</strong>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

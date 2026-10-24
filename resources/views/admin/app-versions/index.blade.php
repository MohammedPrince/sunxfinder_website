@extends('admin.layouts.app')
@section('title', 'App Versions')
@section('breadcrumb', 'App Versions')
@section('eyebrow', 'APP MANAGEMENT')
@section('page-title', 'App Versions')
@section('page-description', 'Configure minimum supported versions and update links for the Android and iOS apps.')
@section('page-actions')
    @if (count($missingPlatforms) > 0)
        <a href="{{ route('admin.app-versions.create') }}" class="btn btn-primary">+ Add App Version</a>
    @endif
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Supported versions</h2>
                <p>Users on an older version than the minimum are asked to update from the store link.</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Platform</th>
                        <th>Minimum version</th>
                        <th>Store link</th>
                        <th>Update policy</th>
                        <th>Last updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($versions as $version)
                        <tr>
                            <td>
                                <div class="company-cell">
                                    <span class="company-avatar {{ $version->platform === 'ios' ? 'blue-bg' : 'green-bg' }}">
                                        {{ $version->platform === 'ios' ? 'i' : 'A' }}
                                    </span>
                                    <span>
                                        <strong>{{ \App\Models\AppVersion::PLATFORMS[$version->platform] ?? $version->platform }}</strong>
                                        <small>Added by {{ $version->creator?->username ?? '—' }}</small>
                                    </span>
                                </div>
                            </td>
                            <td><strong>{{ $version->minimum_version }}</strong></td>
                            <td>
                                <a class="app-version-link" href="{{ $version->app_url }}" target="_blank"
                                    rel="noopener noreferrer" title="{{ $version->app_url }}">{{ $version->app_url }}</a>
                            </td>
                            <td>
                                <span class="pill {{ $version->force_update ? 'pill-red' : 'pill-blue' }}">
                                    {{ $version->force_update ? 'Force update' : 'Optional update' }}
                                </span>
                            </td>
                            <td>{{ $version->updated_at?->format('M d, Y H:i') ?? '—' }}</td>
                            <td>
                                <div class="company-actions">
                                    <a href="{{ route('admin.app-versions.edit', $version->id) }}"
                                        class="row-action row-action-icon" title="Edit version"
                                        aria-label="Edit {{ $version->platform }} version">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                            aria-hidden="true">
                                            <path d="M12 20h9" />
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" />
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.app-versions.destroy', $version->id) }}" method="POST"
                                        class="company-delete-form"
                                        onsubmit="return confirm('Delete the {{ $version->platform }} app version?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="row-action row-action-icon row-action-delete"
                                            title="Delete version" aria-label="Delete {{ $version->platform }} version">
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none"
                                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                                stroke-linejoin="round" aria-hidden="true">
                                                <path d="M3 6h18" />
                                                <path d="M8 6V4h8v2" />
                                                <path d="m19 6-1 14H6L5 6" />
                                                <path d="M10 11v5M14 11v5" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="dashboard-empty">
                                <strong>No app versions yet.</strong>
                                Add the minimum supported version for Android and iOS.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

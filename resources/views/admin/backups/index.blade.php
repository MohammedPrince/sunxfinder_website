@extends('admin.layouts.app')

@section('title', 'Database Backups')
@section('breadcrumb', 'Database Backups')
@section('eyebrow', 'SYSTEM')
@section('page-title', 'Database Backups')
@section('page-description', 'Export the full database to a .sql file, download it, or delete old backups.')

@section('page-actions')
    <form method="POST" action="{{ route('admin.backups.store') }}" style="display:inline"
        onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').textContent = 'Creating backup…';">
        @csrf
        <button type="submit" class="btn btn-primary">+ Create backup now</button>
    </form>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @php
        $totalSize = collect($backups)->sum('size');
        $size = fn($bytes) => $bytes >= 1048576 ? round($bytes / 1048576, 1) . ' MB' : ($bytes >= 1024 ? round($bytes / 1024, 1) . ' KB' : $bytes . ' B');
    @endphp

    @include('partials.page-guide', [
        'key' => 'admin-database-backups',
        'title' => 'About database backups',
        'intro' => 'A backup is a full copy of the database (all tables and data) in one .sql file.',
        'steps' => [
            '<strong>Create</strong> a backup before big changes, imports or app updates.',
            '<strong>Download</strong> it to your computer and keep a copy somewhere safe (not only on the server).',
            '<strong>Restore</strong> by importing the .sql file in phpMyAdmin (Import tab) on an empty database.',
        ],
        'note' => 'Files are stored in storage/app/private/db: not reachable from the web and never committed to git. Only signed-in admins can download them.',
    ])

    <div class="metrics-strip">
        <div>
            <small>Backups</small>
            <strong>{{ count($backups) }}</strong>
        </div>
        <div>
            <small>Latest</small>
            <strong>{{ $backups ? $backups[0]['created_at']->diffForHumans() : '—' }}</strong>
        </div>
        <div>
            <small>Total size</small>
            <strong>{{ $size($totalSize) }}</strong>
        </div>
        <div>
            <small>Database</small>
            <strong style="font-size:15px">{{ config('database.connections.' . config('database.default') . '.database') }}</strong>
        </div>
    </div>

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Saved backups</h2>
                <p>Newest first.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Created</th>
                        <th>Size</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($backups as $backup)
                        <tr>
                            <td>
                                <strong>{{ $backup['name'] }}</strong>
                                @if ($loop->first)
                                    <small class="cell-note">Latest backup</small>
                                @endif
                            </td>
                            <td>
                                {{ $backup['created_at']->format('M d, Y H:i:s') }}
                                <small class="cell-note">{{ $backup['created_at']->diffForHumans() }}</small>
                            </td>
                            <td>{{ $size($backup['size']) }}</td>
                            <td>
                                <div class="company-actions">
                                    <a href="{{ route('admin.backups.download', $backup['name']) }}" class="row-action">
                                        ⤓ Download
                                    </a>
                                    <form method="POST" action="{{ route('admin.backups.destroy', $backup['name']) }}"
                                        onsubmit="return confirm('Delete {{ $backup['name'] }}? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="row-action row-action-delete">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="dashboard-empty">
                                <strong>No backups yet.</strong>
                                Click "Create backup now" to export the database.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

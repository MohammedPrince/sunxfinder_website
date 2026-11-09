@extends('admin.layouts.app')

@section('title', 'Server Tools')
@section('breadcrumb', 'Server Tools')
@section('eyebrow', 'SYSTEM')
@section('page-title', 'Server Tools')
@section('page-description', 'Run the deploy commands on the hosting: caches, migrations, waiting jobs, composer and public files.')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="metrics-strip">
        <div>
            <small>Site</small>
            <strong>
                <span @class(['pill', 'pill-red' => $status['site']['name'] === 'PROD', 'pill-blue' => $status['site']['name'] === 'DEV', 'pill-yellow' => !in_array($status['site']['name'], ['PROD', 'DEV'], true)])>{{ $status['site']['name'] }}</span>
            </strong>
        </div>
        <div>
            <small>Address</small>
            <strong style="font-size:15px" dir="ltr">{{ $status['site']['url'] ?? '—' }}</strong>
        </div>
        <div>
            <small>Project folder</small>
            <strong style="font-size:15px" dir="ltr">{{ basename($status['base_path']) }}</strong>
        </div>
        <div>
            <small>Server Tools</small>
            <strong>{{ $status['available'] ? 'On' : 'Off' }}</strong>
        </div>
    </div>

    @unless ($status['available'])
        <div class="alert alert-warning">
            <strong>Server Tools are off here.</strong> {{ $status['reason'] }}
        </div>
    @endunless

    @if ($status['available'] && !$status['processes'])
        <div class="alert alert-warning">
            <strong>Commands cannot run on this hosting:</strong> proc_open is turned off in PHP. "Copy public folders"
            still works; run the other commands over SSH or ask the host to enable proc_open.
        </div>
    @endif

    @include('partials.page-guide', [
        'key' => 'admin-server-tools',
        'title' => 'After uploading new code',
        'intro' => 'Run these in order after you upload a new version to the hosting.',
        'steps' => [
            '<strong>Database Backups</strong>: create a backup first.',
            '<strong>Composer install</strong>: only when composer.json or composer.lock changed.',
            '<strong>Clear caches</strong>, then <strong>Run migrations</strong>, then <strong>Optimize</strong>.',
            'Changed only .env? <strong>Clear config cache</strong>, then <strong>Cache config</strong>.',
            '<strong>Copy public folders</strong> (PROD only): when CSS, JS, images or sounds changed.',
            'Something went wrong? Check <strong>laravel.log</strong> at the bottom of this page.',
            '<strong>Run waiting jobs</strong>: only if emails or pushes are stuck in the queue.',
        ],
        'note' => 'One tool runs at a time. Every run is saved in Activity Logs (category System) with its output.',
    ])

    @if (session('tool_output'))
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Output: {{ $actions[session('tool_action')]['title'] ?? 'last run' }}</h2>
                    <p>From the run you just started.</p>
                </div>
            </div>
            <div class="panel-body">
                <pre class="server-tools-output" dir="ltr">{{ session('tool_output') }}</pre>
            </div>
        </section>
    @endif

    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Tools</h2>
                <p>Each button runs one command on the server and shows its output.</p>
            </div>
        </div>

        <div class="panel-body">
            <div class="server-tools-grid">
                @foreach ($actions as $key => $action)
                    @php
                        $needsProcess = $key !== 'copy_public';
                        $copyOff = $key === 'copy_public' && !$status['copy_public'];
                        $disabled = !$status['available'] || ($needsProcess && !$status['processes']) || $status['running'] || $copyOff;
                    @endphp
                    <form method="POST" action="{{ route('admin.server-tools.run') }}" class="server-tool"
                        onsubmit="if (!confirm(@js($action['confirm']))) return false; const b = this.querySelector('button'); b.disabled = true; b.textContent = 'Running… please wait';">
                        @csrf
                        <input type="hidden" name="action" value="{{ $key }}">
                        <h3>{{ $action['title'] }}</h3>
                        <code dir="ltr">{{ $action['command'] }}</code>
                        <p>{{ $action['help'] }}</p>
                        @if ($copyOff && $status['available'])
                            <small class="cell-note">Off on {{ $status['site']['name'] }}: only PROD copies to public_html.</small>
                        @endif
                        <button type="submit" @class(['btn', 'btn-danger' => !empty($action['danger']), 'btn-primary' => empty($action['danger'])]) @disabled($disabled)>
                            Run
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Server details</h2>
                <p>Change any of these in .env (SERVER_TOOLS_* keys) if the hosting paths are different.</p>
            </div>
        </div>
        <div class="panel-body">
            <table class="data-table">
                <tbody>
                    <tr><th>Status</th><td>{{ $status['available'] ? 'On (hosting)' : 'Off' }}{{ $status['running'] ? ' · a tool is running now' : '' }}</td></tr>
                    <tr><th>Project folder</th><td dir="ltr"><code>{{ $status['base_path'] }}</code></td></tr>
                    <tr><th>PHP binary</th><td dir="ltr"><code>{{ $status['php'] }}</code></td></tr>
                    <tr><th>Composer</th><td dir="ltr"><code>{{ $status['composer'] }}</code>{{ $status['available'] && !$status['composer_found'] ? ' (not found)' : '' }}</td></tr>
                    <tr><th>Copy from</th><td dir="ltr"><code>{{ $status['public_source'] }}</code></td></tr>
                    <tr><th>Copy to</th><td dir="ltr">@if ($status['copy_public'])<code>{{ $status['public_target'] }}</code>@else Off on {{ $status['site']['name'] }} (PROD only) @endif</td></tr>
                    <tr><th>Folders copied</th><td dir="ltr">{{ implode(', ', $status['public_folders']) }}</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    @php
        $logSize = $log['size'] >= 1048576 ? round($log['size'] / 1048576, 1) . ' MB' : round($log['size'] / 1024, 1) . ' KB';
    @endphp
    <section class="panel" id="server-log">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>laravel.log</h2>
                <p>
                    storage/logs/laravel.log ·
                    @if ($log['exists'])
                        {{ $logSize }} · updated {{ $log['modified']->diffForHumans() }} · last {{ count($log['lines']) }} lines, newest first
                    @else
                        no log file yet
                    @endif
                </p>
            </div>
            @if ($log['exists'])
                <div class="company-actions">
                    <a href="{{ route('admin.server-tools.index') }}#server-log" class="btn btn-light">↻ Refresh</a>
                    <a href="{{ route('admin.server-tools.log.download') }}" class="btn btn-light">⤓ Download</a>
                    <form method="POST" action="{{ route('admin.server-tools.log.clear') }}"
                        onsubmit="return confirm('Empty laravel.log? Download it first if you need the old entries.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Clear log</button>
                    </form>
                </div>
            @endif
        </div>
        <div class="panel-body">
            @if ($log['lines'] && trim(implode('', $log['lines'])) !== '')
                <pre class="server-tools-output server-log" dir="ltr">@foreach ($log['lines'] as $line)<span @class(['log-error' => preg_match('/\.(ERROR|CRITICAL|ALERT|EMERGENCY):/', $line), 'log-warning' => preg_match('/\.WARNING:/', $line)])>{{ $line }}</span>
@endforeach</pre>
            @else
                <p class="dashboard-empty">The log is empty. Errors will appear here.</p>
            @endif
        </div>
    </section>
@endsection

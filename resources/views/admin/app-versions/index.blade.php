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

    {{-- Google Play button on the home page --}}
    @php
        $isPlayLink = str_contains((string) $playStoreUrl, 'play.google.com');
    @endphp
    <section class="panel" style="margin-top:24px">
        <div class="panel-heading">
            <div>
                <h2>
                    Google Play button
                    <span class="pill {{ $playStoreButton && $isPlayLink ? 'pill-green' : 'pill-blue' }}">
                        {{ $playStoreButton && $isPlayLink ? 'Shown on home page' : 'Hidden' }}
                    </span>
                </h2>
                <p>
                    Shows "Get it on Google Play" on the home page, linking to the Android store link above.
                    @unless ($isPlayLink)
                        It only appears once the Android store link is a Google Play address (play.google.com).
                    @endunless
                </p>
            </div>
        </div>

        <div class="panel-body">
            <form action="{{ route('admin.app-versions.play-store.visibility') }}" method="POST">
                @csrf
                @method('PATCH')
                <label class="toggle-card" for="play_store_enabled">
                    <span class="toggle-card-copy">
                        <strong>Show the Google Play button</strong>
                        <small>Beside "Get started" and in the "Get the app" section of the home page.</small>
                    </span>
                    <input type="hidden" name="enabled" value="0">
                    <input class="toggle-input" type="checkbox" id="play_store_enabled" name="enabled" value="1"
                        @checked($playStoreButton) onchange="this.form.submit()">
                    <span class="toggle-switch" aria-hidden="true"></span>
                </label>
            </form>
        </div>
    </section>

    {{-- Android APK offered as "Direct Download" / "تحميل" on the home page --}}
    <section class="panel" style="margin-top:24px">
        <div class="panel-heading">
            <div>
                <h2>
                    Direct APK download
                    <span class="pill {{ $apk['visible'] ? 'pill-green' : 'pill-blue' }}">
                        {{ $apk['visible'] ? 'Shown on home page' : 'Hidden' }}
                    </span>
                </h2>
                <p>
                    Upload the Android APK to offer it beside "Get started" on the home page. The file is kept in a
                    private folder and is only downloadable while the button is shown. A Google Play button also
                    appears when the Android store link above points to Google Play.
                </p>
            </div>
        </div>

        <div class="panel-body">
            @if ($apk['exists'])
                <p style="margin:0 0 16px">
                    <strong>Current file:</strong>
                    {{ \App\Support\ApkDownload::downloadName() }}
                    · {{ number_format($apk['size'] / 1048576, 1) }} MB
                    @if ($apk['uploaded_at'])
                        · uploaded {{ \Illuminate\Support\Carbon::parse($apk['uploaded_at'])->format('M d, Y H:i') }}
                    @endif
                </p>

                <form action="{{ route('admin.app-versions.apk.visibility') }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <label class="toggle-card" for="apk_enabled">
                        <span class="toggle-card-copy">
                            <strong>Show the Direct Download button</strong>
                            <small>Visitors see "Direct Download" (English) or "تحميل" (Arabic) on the home page.</small>
                        </span>
                        <input type="hidden" name="enabled" value="0">
                        <input class="toggle-input" type="checkbox" id="apk_enabled" name="enabled" value="1"
                            @checked($apk['enabled']) onchange="this.form.submit()">
                        <span class="toggle-switch" aria-hidden="true"></span>
                    </label>
                </form>
            @else
                <p style="margin:0 0 16px">No APK uploaded yet. The home page shows no download button.</p>
            @endif

            <form id="apkUploadForm" class="form-grid" style="margin-top:18px"
                data-chunk-url="{{ route('admin.app-versions.apk.chunk') }}"
                data-finish-url="{{ route('admin.app-versions.apk.finish') }}"
                data-chunk-bytes="{{ $apkChunkBytes }}"
                data-max-bytes="{{ \App\Support\ApkDownload::MAX_BYTES }}">
                <div class="form-group">
                    <label for="apk_file">{{ $apk['exists'] ? 'Replace APK' : 'APK file' }}</label>
                    <input class="form-control" type="file" id="apk_file" accept=".apk,application/vnd.android.package-archive" required>
                    <small>Up to {{ \App\Support\ApkDownload::MAX_BYTES / 1048576 }} MB. Uploaded in small parts, so large files work on any hosting.</small>
                </div>

                <div class="form-group">
                    <label for="apk_version">Version (optional)</label>
                    <input class="form-control" type="text" id="apk_version" maxlength="20" placeholder="e.g. 1.6.2"
                        value="{{ $apk['version'] }}">
                    <small>Used in the downloaded file name, e.g. SunXFinder-1.6.2.apk.</small>
                </div>

                <div class="form-group" style="grid-column:1/-1">
                    <div id="apkProgress" hidden
                        style="height:10px;border-radius:999px;background:#e6edf5;overflow:hidden">
                        <div id="apkProgressBar" style="width:0;height:100%;background:#fc5202;transition:width .2s"></div>
                    </div>
                    <small id="apkStatus" role="status" aria-live="polite"></small>
                </div>

                <div style="grid-column:1/-1;display:flex;gap:12px;flex-wrap:wrap">
                    <button type="submit" class="btn btn-primary" id="apkUploadButton">Upload APK</button>
                </div>
            </form>

            @if ($apk['exists'])
                <form action="{{ route('admin.app-versions.apk.destroy') }}" method="POST" style="margin-top:12px"
                    onsubmit="return confirm('Delete the APK? The download button will be hidden.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-light">Delete APK</button>
                </form>
            @endif
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        (() => {
            const form = document.getElementById('apkUploadForm');
            if (!form) return;

            const fileInput = document.getElementById('apk_file');
            const versionInput = document.getElementById('apk_version');
            const button = document.getElementById('apkUploadButton');
            const progress = document.getElementById('apkProgress');
            const bar = document.getElementById('apkProgressBar');
            const status = document.getElementById('apkStatus');
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const chunkBytes = Number(form.dataset.chunkBytes);
            const maxBytes = Number(form.dataset.maxBytes);

            const post = async (url, body) => {
                // Retry each request a few times so a flaky connection doesn't lose the upload.
                for (let attempt = 1; ; attempt++) {
                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            body,
                            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                            credentials: 'same-origin',
                        });
                        const json = await response.json().catch(() => ({}));

                        if (response.ok) return json;
                        if (response.status === 422 || attempt >= 3) {
                            const firstError = json.errors ? Object.values(json.errors)[0][0] : null;
                            throw new Error(firstError || json.message || 'Upload failed.');
                        }
                    } catch (error) {
                        if (attempt >= 3 || error.message !== 'Failed to fetch') throw error;
                    }
                    await new Promise(resolve => setTimeout(resolve, 1000 * attempt));
                }
            };

            form.addEventListener('submit', async event => {
                event.preventDefault();

                const file = fileInput.files[0];
                if (!file) return;

                if (!file.name.toLowerCase().endsWith('.apk')) {
                    status.textContent = 'Choose an .apk file.';
                    return;
                }
                if (file.size > maxBytes) {
                    status.textContent = 'The APK is too large.';
                    return;
                }

                const uploadId = Array.from(crypto.getRandomValues(new Uint8Array(16)),
                    b => b.toString(16).padStart(2, '0')).join('');
                const total = Math.ceil(file.size / chunkBytes);

                button.disabled = true;
                fileInput.disabled = true;
                progress.hidden = false;

                try {
                    for (let index = 0; index < total; index++) {
                        const body = new FormData();
                        body.append('upload_id', uploadId);
                        body.append('index', index);
                        body.append('chunk', file.slice(index * chunkBytes, (index + 1) * chunkBytes), 'chunk');

                        await post(form.dataset.chunkUrl, body);

                        const percent = Math.round(((index + 1) / total) * 100);
                        bar.style.width = percent + '%';
                        status.textContent = `Uploading… ${percent}%`;
                    }

                    status.textContent = 'Checking the file…';

                    const body = new FormData();
                    body.append('upload_id', uploadId);
                    body.append('total', total);
                    body.append('version', versionInput.value.trim());

                    await post(form.dataset.finishUrl, body);

                    window.location.reload();
                } catch (error) {
                    status.textContent = error.message;
                    bar.style.width = '0';
                    progress.hidden = true;
                    button.disabled = false;
                    fileInput.disabled = false;
                }
            });
        })();
    </script>
@endsection

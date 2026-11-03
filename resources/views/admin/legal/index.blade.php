@extends('admin.layouts.app')

@section('title', 'Privacy & Terms')
@section('breadcrumb', 'Privacy & Terms')
@section('eyebrow', 'SYSTEM')
@section('page-title', 'Privacy & Terms')
@section('page-description', 'The text of the public Privacy Policy and Terms pages, in English and Arabic.')

@section('page-actions')
    @if ($sections->isNotEmpty())
        <a href="{{ route('admin.legal.create', $page) }}" class="btn btn-primary">+ Add section</a>
    @endif
@endsection

@section('content')
    @php
        $routeName = \App\Models\LegalSection::ROUTES[$page];
        $typeLabels = ['header' => 'Page header', 'section' => 'Section', 'contact' => 'Contact box'];
    @endphp

    @include('partials.page-guide', [
        'key' => 'admin-legal-pages',
        'title' => 'How Privacy & Terms works',
        'intro' => 'Each page is made of a header, numbered sections and a closing contact box. Every part has English and Arabic text.',
        'steps' => [
            '<strong>Edit</strong>: changes appear on the website as soon as you save.',
            '<strong>Add or delete</strong>: section numbers update by themselves, so never type them in the title.',
            '<strong>Order</strong>: use the arrows to move a section up or down.',
            '<strong>Hide</strong>: keeps a section saved but removes it from the website.',
        ],
        'note' => 'Deleted sections are kept in Archives. If a page has no sections, the website shows the built-in text.',
    ])

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <nav class="legal-tabs" aria-label="Legal pages">
        @foreach (\App\Models\LegalSection::PAGES as $key => $label)
            <a href="{{ route('admin.legal.index', ['tab' => $key]) }}" @class(['is-active' => $key === $page])
                @if ($key === $page) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar log-toolbar">
            <div>
                <h2>{{ \App\Models\LegalSection::PAGES[$page] }}</h2>
                <p>
                    <span class="pill {{ $usesSavedText ? 'pill-green' : 'pill-yellow' }}">{{ $usesSavedText ? 'Showing the text below' : 'Showing the built-in text' }}</span>
                    @if ($header?->last_updated)
                        Last updated {{ $header->last_updated->format('M j, Y') }}
                    @endif
                </p>
            </div>

            <div class="legal-toolbar-actions">
                <a class="btn btn-light" href="{{ route($routeName, ['lang' => 'en']) }}" target="_blank" rel="noopener">View English</a>
                <a class="btn btn-light" href="{{ route($routeName, ['lang' => 'ar']) }}" target="_blank" rel="noopener">View Arabic</a>
                @if ($sections->isNotEmpty())
                    <form method="POST" action="{{ route('admin.legal.load', $page) }}"
                        onsubmit="return confirm('Replace every section of this page with the built-in text? The current sections move to Archives.');">
                        @csrf
                        <button type="submit" class="btn btn-light">Reset to built-in text</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>English title</th>
                        <th>Arabic title</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php $number = 0; @endphp
                    @forelse ($sections as $section)
                        @php
                            $siblings = $sections->where('type', $section->type)->values();
                            $position = $siblings->search(fn($item) => $item->id === $section->id);
                            if ($section->type === 'section' && $section->is_active) {
                                $number++;
                            }
                        @endphp
                        <tr>
                            <td><strong>{{ $section->type === 'section' && $section->is_active ? $number : '—' }}</strong></td>
                            <td class="cell-wrap">
                                <a href="{{ route('admin.legal.edit', $section->id) }}"><strong>{{ $section->title_en }}</strong></a>
                                <small class="cell-note">Updated {{ $section->updated_at?->format('M j, Y') ?? '—' }}</small>
                            </td>
                            <td class="cell-wrap" dir="rtl">{{ $section->title_ar }}</td>
                            <td>{{ $typeLabels[$section->type] ?? $section->type }}</td>
                            <td>
                                <span class="pill {{ $section->is_active ? 'pill-green' : 'pill-red' }}">{{ $section->is_active ? 'Shown' : 'Hidden' }}</span>
                            </td>
                            <td>
                                <div class="company-actions">
                                    @unless ($section->isHeader())
                                        @foreach (['up' => 'm18 15-6-6-6 6', 'down' => 'm6 9 6 6 6-6'] as $direction => $arrow)
                                            <form action="{{ route('admin.legal.move', $section->id) }}" method="POST" class="company-delete-form">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="direction" value="{{ $direction }}">
                                                <button type="submit" class="row-action row-action-icon" title="Move {{ $direction }}"
                                                    aria-label="Move {{ $section->title_en }} {{ $direction }}"
                                                    @disabled($direction === 'up' ? $position === 0 : $position === $siblings->count() - 1)>
                                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="{{ $arrow }}" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endforeach
                                    @endunless

                                    <a href="{{ route('admin.legal.edit', $section->id) }}" class="row-action row-action-icon"
                                        aria-label="Edit {{ $section->title_en }}" title="Edit">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 20h9" />
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" />
                                        </svg>
                                    </a>

                                    @unless ($section->isHeader())
                                        <form action="{{ route('admin.legal.toggle', $section->id) }}" method="POST" class="company-delete-form">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="row-action row-action-icon"
                                                title="{{ $section->is_active ? 'Hide from the website' : 'Show on the website' }}"
                                                aria-label="{{ $section->is_active ? 'Hide' : 'Show' }} {{ $section->title_en }}">
                                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    @if ($section->is_active)
                                                        <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                                                        <circle cx="12" cy="12" r="2.5" />
                                                    @else
                                                        <path d="M10.6 6.1A10 10 0 0 1 12 6c6 0 9.5 6 9.5 6a17 17 0 0 1-2.4 3.1M6.6 6.6C3.9 8.3 2.5 12 2.5 12s3.5 6 9.5 6a9.7 9.7 0 0 0 5.4-1.6" />
                                                        <path d="m3 3 18 18" />
                                                    @endif
                                                </svg>
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.legal.destroy', $section->id) }}" method="POST" class="company-delete-form"
                                            onsubmit="return confirm('Delete this section? It will be removed from the website and kept in Archives.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="row-action row-action-icon row-action-delete" aria-label="Delete {{ $section->title_en }}" title="Delete">
                                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M3 6h18" />
                                                    <path d="M8 6V4h8v2" />
                                                    <path d="m19 6-1 14H6L5 6" />
                                                    <path d="M10 11v5M14 11v5" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="dashboard-empty">
                                <strong>No saved text for this page.</strong>
                                The website is showing the built-in text.
                                <form method="POST" action="{{ route('admin.legal.load', $page) }}" class="legal-empty-action">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">Load the current text to edit it</button>
                                </form>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

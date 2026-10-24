@extends('admin.layouts.app')

@section('title', 'Calculator Groups')
@section('breadcrumb', 'Calculator Groups')
@section('eyebrow', 'SOLAR CALCULATOR')
@section('page-title', 'Calculator Groups')
@section('page-description', 'Manage appliance categories and power ratings used by the SunXFinder solar calculator.')

@section('page-actions')
    <a href="{{ route('admin.calculator-groups.create') }}" class="btn btn-primary">
        + Add Appliance Group
    </a>
@endsection

@section('content')

    <style>

    </style>

    <div class="calculator-groups-page">

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible">
                <strong>Success!</strong> {{ session('success') }}
                <button type="button" class="alert-close" onclick="this.parentElement.remove()"
                    aria-label="Close">&times;</button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible">
                <strong>Error!</strong> {{ session('error') }}
                <button type="button" class="alert-close" onclick="this.parentElement.remove()"
                    aria-label="Close">&times;</button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Please correct the following errors:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Summary metrics --}}
        @php
            $pageGroups = $groups->getCollection();
        @endphp

        <div class="metrics-strip">
            <div>
                <small>All groups</small>
                <strong>{{ $groups->total() }}</strong>
            </div>
            <div>
                <small>Active groups</small>
                <strong>{{ $pageGroups->where('is_active', true)->count() }}</strong>
            </div>
            <div>
                <small>Appliances on this page</small>
                <strong>{{ $pageGroups->sum('appliances_count') }}</strong>
            </div>
            <div>
                <small>Inactive groups</small>
                <strong>{{ $pageGroups->where('is_active', false)->count() }}</strong>
            </div>
        </div>

        {{-- Search and category filters --}}
        <div class="panel filter-panel">
            <div class="filter-grid">
                <div class="form-group">
                    <input type="search" id="applianceSearch" class="form-control"
                        placeholder="Search English or Arabic names..." aria-label="Search appliance groups and appliances">
                </div>

                <div class="form-group">

                    <select id="categoryFilter" class="select-control" aria-label="Filter by appliance category">
                        <option value="all">All categories</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>

                </div>
            </div>

            {{-- Quick filters --}}
            <div class="filter-shortcuts">
                <button type="button" class="filter-chip active" data-quick-filter="all">
                    All
                </button>
                <button type="button" class="filter-chip" data-quick-filter="ac">
                    <i data-lucide="snowflake" style="width:14px;height:14px;vertical-align:middle;"></i>
                    Air conditioners
                </button>
                <button type="button" class="filter-chip" data-quick-filter="fans">
                    <i data-lucide="fan" style="width:14px;height:14px;vertical-align:middle;"></i>
                    Fans
                </button>
                <button type="button" class="filter-chip" data-quick-filter="lighting">
                    <i data-lucide="lightbulb" style="width:14px;height:14px;vertical-align:middle;"></i>
                    Lighting
                </button>
                <button type="button" class="filter-chip" data-quick-filter="refrigeration">
                    <i data-lucide="refrigerator" style="width:14px;height:14px;vertical-align:middle;"></i>
                    Refrigerators
                </button>
            </div>

            <div class="filter-bottom">
                <p id="filterResults" class="filter-result-text">
                    Showing all appliance groups
                </p>

                <div class="filter-actions">
                    <button type="button" id="resetFilters" class="btn btn-primary">
                        Reset filters
                    </button>
                </div>
            </div>
        </div>

        {{-- Appliance groups --}}
        @forelse ($groups as $group)

            @php
                $groupSearch = strtolower($group->name . ' ' . $group->ar_name);
                $appliances = $group->appliances->sortBy([['sort_order', 'asc'], ['name', 'asc']])->values();
            @endphp

            <section class="panel calculator-group-panel appliance-group-card" data-group-id="{{ $group->id }}"
                data-group-search="{{ $groupSearch }}" style="margin-bottom:24px;">

                {{-- Group summary table --}}
                <div class="group-header-table-wrap">
                    <table class="data-table group-header-table">
                        <thead>
                            <tr>
                                <th>Icon</th>
                                <th>Group name</th>
                                <th>Arabic name</th>
                                <th>Appliances</th>
                                <th>Sort order</th>
                                <th>Status</th>
                                <th class="group-actions-column">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td class="group-icon-cell">
                                    @if ($group->icon)
                                        @if (preg_match('/^[a-z0-9-]+$/', $group->icon))
                                            <span class="group-icon" aria-hidden="true">
                                                <i data-lucide="{{ $group->icon }}"></i>
                                            </span>
                                        @else
                                            <span class="group-icon" aria-hidden="true">
                                                {{ $group->icon }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="group-icon">—</span>
                                    @endif
                                </td>

                                <td>
                                    <span class="group-name">{{ $group->name }}</span>
                                </td>

                                <td>
                                    <span class="group-arabic-name" dir="rtl" lang="ar">
                                        {{ $group->ar_name }}
                                    </span>
                                </td>

                                <td>
                                    <span class="group-appliance-count">
                                        {{ $group->appliances_count }}
                                    </span>
                                    <span class="group-appliance-label">
                                        {{ $group->appliances_count == 1 ? 'appliance' : 'appliances' }}
                                    </span>
                                </td>

                                <td>{{ $group->sort_order }}</td>

                                <td>
                                    @if ($group->is_active)
                                        <span class="pill pill-green">Active</span>
                                    @else
                                        <span class="pill pill-red">Inactive</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="group-table-actions">
                                        <a href="{{ route('admin.calculator-groups.edit', $group->id) }}"
                                            class="row-action row-action-icon" title="Edit appliance group"
                                            aria-label="Edit {{ $group->name }}">
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none"
                                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                                stroke-linejoin="round" aria-hidden="true">
                                                <path d="M12 20h9" />
                                                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" />
                                            </svg>
                                        </a>

                                        <form action="{{ route('admin.calculator-groups.destroy', $group->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Delete this group and all its appliances?');"
                                            class="company-delete-form">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="row-action row-action-icon row-action-delete"
                                                title="Delete appliance group" aria-label="Delete {{ $group->name }}">
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
                        </tbody>
                    </table>
                </div>

                {{-- Appliance details table --}}
                <div class="appliance-table-wrap">
                    <table class="data-table appliance-table">
                        <colgroup>
                            <col class="column-number">
                            <col class="column-appliance">
                            <col class="column-arabic">
                            <col class="column-watts">
                            <col class="column-order">
                            <col class="column-status">
                        </colgroup>

                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Appliance</th>
                                <th>Arabic name</th>
                                <th>Power rating</th>
                                <th>Sort order</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($appliances as $appliance)
                                <tr class="appliance-row"
                                    data-appliance-search="{{ strtolower($appliance->name . ' ' . $appliance->ar_name) }}">
                                    <td>{{ $loop->iteration }}</td>

                                    <td>
                                        <span class="appliance-name">{{ $appliance->name }}</span>
                                        <span class="appliance-id">ID: {{ $appliance->id }}</span>
                                    </td>

                                    <td>
                                        <div class="appliance-arabic" dir="rtl" lang="ar">
                                            {{ $appliance->ar_name }}
                                        </div>
                                    </td>

                                    <td>
                                        <span class="appliance-watts">
                                            {{ number_format($appliance->watts) }} W
                                        </span>
                                    </td>

                                    <td>
                                        <span class="appliance-order">{{ $appliance->sort_order }}</span>
                                    </td>

                                    <td>
                                        @if ($appliance->is_active)
                                            <span class="pill pill-green">Active</span>
                                        @else
                                            <span class="pill pill-red">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="empty-row">
                                        No appliances in this group.
                                        <a href="{{ route('admin.calculator-groups.edit', $group->id) }}">
                                            Add appliances
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Expand/collapse controls --}}
                <div class="group-footer">
                    <div class="filter-actions">
                        <a href="{{ route('admin.calculator-groups.edit', $group->id) }}" class="btn btn-primary">
                            Manage Appliances
                        </a>

                        @if ($appliances->count() > 4)
                            <button type="button" class="btn btn-primary toggle-appliances">
                                Show all appliances
                            </button>
                        @endif
                    </div>

                    <span class="group-footer-note appliance-count">
                        {{ $appliances->count() }} appliances
                    </span>
                </div>
            </section>

        @empty
            <section class="panel calculator-empty-state">
                <div class="panel-heading">
                    <div>
                        <h2>No appliance groups found</h2>
                        <p>Create your first group to populate the solar calculator.</p>
                    </div>
                </div>
            </section>
        @endforelse

        {{-- Pagination --}}
        @include('partials.table-pagination', ['paginator' => $groups, 'label' => 'groups'])

    </div>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.lucide) {
                window.lucide.createIcons();
            }

            const root = document.querySelector('.calculator-groups-page');
            if (!root) return;

            const searchInput = root.querySelector('#applianceSearch');
            const categoryFilter = root.querySelector('#categoryFilter');
            const resetButton = root.querySelector('#resetFilters');
            const resultsText = root.querySelector('#filterResults');
            const quickButtons = Array.from(root.querySelectorAll('[data-quick-filter]'));
            const cards = Array.from(root.querySelectorAll('.appliance-group-card'));
            const expandedCards = new WeakSet();

            function normalize(value) {
                return (value || '').toLocaleLowerCase().trim();
            }

            function getQuickCategory(card, quickFilter) {
                if (quickFilter === 'all') return true;

                const text = normalize(
                    card.dataset.groupSearch + ' ' +
                    card.querySelector('.group-name')?.textContent + ' ' +
                    card.querySelector('.group-arabic-name')?.textContent
                );

                const patterns = {
                    ac: [
                        'window', 'split', 'air conditioner', 'water air cooler',
                        'water cooler', 'مكيف', 'مبرد', 'تبريد'
                    ],
                    fans: ['fan', 'مروحة', 'مراوح'],
                    lighting: ['lighting', 'light', 'led', 'إضاءة', 'اضاءة', 'مصباح'],
                    refrigeration: [
                        'refrigerator', 'fridge', 'freezer', 'refrigeration',
                        'ثلاجة', 'فريزر', 'تبريد'
                    ]
                };

                return (patterns[quickFilter] || []).some(word => text.includes(word));
            }

            function updateView() {
                const query = normalize(searchInput.value);
                const selectedCategory = categoryFilter.value;
                const activeQuickButton = root.querySelector('[data-quick-filter].active');
                const quickFilter = activeQuickButton?.dataset.quickFilter || 'all';

                let visibleGroups = 0;
                let visibleAppliances = 0;

                cards.forEach(card => {
                    const categoryMatches =
                        selectedCategory === 'all' ||
                        card.dataset.groupId === selectedCategory;

                    const quickMatches = getQuickCategory(card, quickFilter);
                    const groupText = normalize(card.dataset.groupSearch);
                    const groupMatches = query !== '' && groupText.includes(query);

                    const rows = Array.from(card.querySelectorAll('.appliance-row'));
                    let matchingRows = 0;

                    rows.forEach(row => {
                        const matches = normalize(row.dataset.applianceSearch).includes(query);
                        row.dataset.searchMatch = matches ? '1' : '0';

                        if (matches) matchingRows++;
                    });

                    const searchMatches = !query || groupMatches || matchingRows > 0;
                    const showCard = categoryMatches && quickMatches && searchMatches;

                    card.hidden = !showCard;

                    if (!showCard) return;

                    visibleGroups++;
                    visibleAppliances += query ? matchingRows : rows.length;

                    const searchActive = query !== '';
                    const showAll = expandedCards.has(card) || searchActive;
                    let visibleRowIndex = 0;

                    rows.forEach(row => {
                        const matches = row.dataset.searchMatch === '1';

                        if (searchActive && !groupMatches) {
                            row.hidden = !matches;
                        } else if (searchActive && groupMatches) {
                            row.hidden = false;
                        } else {
                            row.hidden = !showAll && visibleRowIndex >= 4;
                            visibleRowIndex++;
                        }
                    });

                    const toggleButton = card.querySelector('.toggle-appliances');

                    if (toggleButton) {
                        toggleButton.hidden = searchActive || rows.length <= 4;
                        toggleButton.textContent = showAll ?
                            'Show fewer appliances' :
                            'Show all appliances';
                    }

                    const count = card.querySelector('.appliance-count');

                    if (count) {
                        count.textContent = searchActive ?
                            `${matchingRows} matching appliances` :
                            `${rows.length} appliances`;
                    }
                });

                resultsText.textContent =
                    `Showing ${visibleGroups} groups and ${visibleAppliances} appliances`;

                if (query) {
                    resultsText.textContent =
                        `Search results: ${visibleAppliances} matching appliances in ${visibleGroups} groups`;
                }
            }

            quickButtons.forEach(button => {
                button.addEventListener('click', function() {
                    quickButtons.forEach(item => item.classList.remove('active'));
                    button.classList.add('active');

                    categoryFilter.value = 'all';
                    updateView();
                });
            });

            cards.forEach(card => {
                const button = card.querySelector('.toggle-appliances');

                if (!button) return;

                button.addEventListener('click', function() {
                    if (expandedCards.has(card)) {
                        expandedCards.delete(card);
                    } else {
                        expandedCards.add(card);
                    }

                    updateView();
                });
            });

            searchInput.addEventListener('input', updateView);

            categoryFilter.addEventListener('change', function() {
                quickButtons.forEach(item => {
                    item.classList.toggle('active', item.dataset.quickFilter === 'all');
                });

                updateView();
            });

            resetButton.addEventListener('click', function() {
                searchInput.value = '';
                categoryFilter.value = 'all';

                quickButtons.forEach(item => {
                    item.classList.toggle('active', item.dataset.quickFilter === 'all');
                });

                cards.forEach(card => expandedCards.delete(card));

                updateView();
            });

            updateView();
        });
    </script>

@endsection

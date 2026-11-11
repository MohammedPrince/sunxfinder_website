
@extends('company.layouts.app')

@section('title', __('company/messages.projects.title'))
@section('breadcrumb', __('company/messages.projects.title'))
@section('eyebrow', __('company/messages.projects.eyebrow'))
@section('page-title', __('company/messages.projects.title'))
@section('page-description', __('company/messages.projects.page_description'))

@section('content')
    @php
        $statusLabels = [
            'accepted' => __('company/messages.common.not_started'),
            'in_progress' => __('company/messages.common.in_progress'),
            'handover_pending' => __('company/messages.common.handover_pending'),
            'completed' => __('company/messages.common.completed'),
        ];

        $statusClasses = [
            'accepted' => 'pill-orange',
            'in_progress' => 'pill-blue',
            'handover_pending' => 'pill-purple',
            'completed' => 'pill-green',
        ];
    @endphp

    @include('partials.page-guide', [
        'key' => 'company-projects',
        'title' => __('company/messages.invoices.guide_title'),
        'intro' => __('company/messages.invoices.guide_intro'),
        'steps' => [
            __('company/messages.invoices.guide_1'),
            __('company/messages.invoices.guide_2'),
            __('company/messages.invoices.guide_3'),
        ],
        'note' => __('company/messages.invoices.guide_note'),
    ])

    <div class="metrics-strip">
        <div>
            <small>{{ __('company/messages.common.not_started') }}</small>
            <strong class="{{ $metrics['accepted'] ? 'warning' : '' }}">{{ number_format($metrics['accepted']) }}</strong>
        </div>
        <div>
            <small>{{ __('company/messages.common.in_progress') }}</small>
            <strong>{{ number_format($metrics['in_progress']) }}</strong>
        </div>
        <div>
            <small>{{ __('company/messages.common.handover_pending') }}</small>
            <strong>{{ number_format($metrics['handover_pending']) }}</strong>
        </div>
        <div>
            <small>{{ __('company/messages.common.completed') }}</small>
            <strong>{{ number_format($metrics['completed']) }}</strong>
        </div>
        <div>
            <small>{{ __('company/messages.projects.total_projects') }}</small>
            <strong>{{ number_format($metrics['total']) }}</strong>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>{{ __('company/messages.projects.your_projects') }}</h2>
                <p>{{ __('company/messages.projects.list_sub') }}</p>
            </div>

            <form method="GET"
                  action="{{ route('company.projects.index') }}"
                  class="table-filters">

                <select name="status" class="select-control">
                    <option value="">{{ __('company/messages.projects.all_statuses') }}</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <input
                    type="search"
                    name="search"
                    class="table-search"
                    value="{{ request('search') }}"
                    maxlength="100"
                    placeholder="{{ __('company/messages.projects.search_placeholder') }}"
                    aria-label="{{ __('company/messages.projects.search_label') }}"
                >

                <button type="submit" class="btn btn-light">{{ __('company/messages.common.search') }}</button>

                @if (request()->hasAny(['status', 'search']))
                    <a href="{{ route('company.projects.index') }}" class="btn btn-light">
                        {{ __('company/messages.common.clear') }}
                    </a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('company/messages.common.project') }}</th>
                        <th>{{ __('company/messages.common.customer') }}</th>
                        <th>{{ __('company/messages.common.quote_name') }}</th>
                        <th>{{ __('company/messages.common.selected_offer') }}</th>
                        <th>{{ __('company/messages.common.progress') }}</th>
                        <th>{{ __('company/messages.common.status') }}</th>
                        <th>{{ __('company/messages.common.last_update') }}</th>
                        <th>{{ __('company/messages.common.next_step') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($projects as $project)
                        @php
                            $customer = $project->customer;
                            $quote = $project->quote;
                            $offer = $project->companyQuotation;

                            $customerName = $customer?->full_name
                                ?: ($customer?->username ?: __('company/messages.common.customer_number', ['id' => $project->customer_id]));

                            $reference = '#Q-' . str_pad(
                                (string) $project->quote_id,
                                5,
                                '0',
                                STR_PAD_LEFT
                            );

                            // 99% means every installation phase is done and only the handover is left.
                            [$nextLabel, $nextClass] = match (true) {
                                $project->status === 'accepted' => [__('company/messages.projects.start_installation'), 'pill-orange'],
                                $project->status === 'in_progress' && $project->progress_percent >= 99 => [__('company/messages.projects.confirm_handover'), 'pill-green'],
                                $project->status === 'in_progress' => [__('company/messages.projects.update_progress'), 'pill-blue'],
                                $project->status === 'handover_pending' => [__('company/messages.common.handover_pending'), 'pill-purple'],
                                default => [__('company/messages.projects.view_project'), 'pill-green'],
                            };

                            $idleDays = (int) $project->updated_at?->diffInDays(now());
                        @endphp

                        <tr>
                            <td>
                                <strong>{{ __('company/messages.common.project_number', ['id' => $project->id]) }}</strong>
                                <small class="cell-note">{{ __('company/messages.projects.request_ref', ['reference' => $reference]) }}</small>
                            </td>

                            <td>
                                <div class="person-cell">
                                    <span class="person-avatar">
                                        {{ name_initials($customerName) }}
                                    </span>
                                    <span>
                                        <strong>{{ $customerName }}</strong>
                                        <small>{{ $customer?->city ?: ($customer?->phone ?? __('company/messages.projects.no_contact')) }}</small>
                                    </span>
                                </div>
                            </td>

                            <td>{{ $quote?->quote_name ?? __('company/messages.common.solar_installation') }}</td>

                            <td>
                                @if ($offer)
                                    <strong>
                                        {{ $offer->currency }}
                                        {{ number_format((float) $offer->price, 2) }}
                                    </strong>
                                @else
                                    —
                                @endif
                            </td>

                            <td style="min-width:130px">
                                <strong>{{ $project->progress_percent }}%</strong>
                                <div class="mini-progress mini-progress-lg"
                                    role="progressbar" aria-valuenow="{{ $project->progress_percent }}" aria-valuemin="0" aria-valuemax="100">
                                    <span style="width: {{ $project->progress_percent }}%"></span>
                                </div>
                            </td>

                            <td>
                                <span class="pill {{ $statusClasses[$project->status] ?? 'pill-blue' }}">
                                    {{ $statusLabels[$project->status] ?? ucfirst(str_replace('_', ' ', $project->status)) }}
                                </span>
                            </td>

                            <td>
                                {{ $project->updated_at?->translatedFormat('d M Y') ?? '—' }}
                                @if ($project->status !== 'completed' && $idleDays >= \App\Support\ReportPeriod::STALLED_DAYS)
                                    <span class="pill pill-red" title="{{ __('company/messages.projects.no_update_for', ['days' => $idleDays]) }}">{{ __('company/messages.projects.days_idle', ['days' => $idleDays]) }}</span>
                                @else
                                    <small class="cell-note">{{ $project->updated_at?->diffForHumans() }}</small>
                                @endif
                            </td>

                            <td>
                                <a href="{{ route('company.projects.show', $project->id) }}" class="pill {{ $nextClass }} next-step-link">
                                    {{ $nextLabel }} {{ __('company/messages.common.arrow_next') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="dashboard-empty">
                                <strong>{{ __('company/messages.projects.empty_title') }}</strong>
                                {{ __('company/messages.projects.empty_body') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $projects, 'label' => 'projects'])
    </section>
@endsection

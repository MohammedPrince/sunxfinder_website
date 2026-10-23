@extends('admin.layouts.app')

@section('title', 'Quote Details')
@section('breadcrumb', 'Quote requests')
@section('eyebrow', 'CUSTOMER ENQUIRIES')
@section('page-title', 'Quote details')
@section('page-description', 'Review the customer enquiry, system requirements, and company assignment.')

@section('page-actions')
    <div class="quote-page-actions no-print">
        <a href="{{ route('admin.quote-requests.index') }}" class="btn btn-light">
            &larr; Back to requests
        </a>

        <button type="button" class="btn btn-primary" onclick="printQuote()">
            Print / Save PDF
        </button>
    </div>
@endsection

@section('content')
    @php
        $customer = $quote->customer;
        $company = $quote->company;

        $customerName = $customer?->full_name
            ?: ($customer?->username ?: 'Customer #' . $quote->customer_id);

        $statusLabels = [
            'pending' => 'Pending',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        $statusClasses = [
            'pending' => 'pill-yellow',
            'accepted' => 'pill-orange',
            'rejected' => 'pill-red',
            'completed' => 'pill-green',
            'cancelled' => 'pill-purple',
        ];

        $status = $quote->status;
        $reference = '#Q-' . str_pad($quote->id, 5, '0', STR_PAD_LEFT);

        $offers = $quote->companyQuotations;
        $project = $quote->project;
        $stage = $quote->stage($offers->count());

        $offerStatusLabels = [
            'submitted' => 'Awaiting customer',
            'selected' => 'Selected',
            'not_selected' => 'Not selected',
            'withdrawn' => 'Withdrawn',
        ];

        $offerStatusClasses = [
            'submitted' => 'pill-yellow',
            'selected' => 'pill-green',
            'not_selected' => 'pill-red',
            'withdrawn' => 'pill-purple',
        ];

        $projectStatusLabels = [
            'accepted' => 'Accepted · not started',
            'in_progress' => 'Installation in progress',
            'completed' => 'Completed',
        ];

        // Lowest price per currency, to mark the cheapest offer.
        $lowestByCurrency = $offers->groupBy('currency')->map(fn($group) => (float) $group->min('price'));
    @endphp

    <div class="quote-print-page">

        {{-- Print document header --}}
        <header class="quote-document-header print-only">
            <div class="quote-document-brand">SunXFinder</div>
            <div class="quote-document-subtitle">Solar Energy Quote Request</div>
            <div class="quote-document-reference">
                Reference: <strong>{{ $reference }}</strong>
            </div>
        </header>

        @include('partials.page-guide', [
            'key' => 'admin-quote-details',
            'title' => 'What this page shows',
            'intro' => 'The full story of one customer request, from what the customer asked for to the offer they chose and the installation.',
            'steps' => [
                '<strong>Progress tracker</strong> (below): where the request is right now.',
                '<strong>Offers received</strong>: every company price for this request, cheapest first.',
                '<strong>Project</strong>: installation progress after the customer accepts an offer.',
                '<strong>Request information</strong>: the energy needs the customer entered in the app calculator.',
            ],
            'note' => 'While a request is open you can remind companies, reassign it or cancel it under Admin actions. Use Print / Save PDF to share it.',
        ])

        {{-- Summary --}}
        <section class="quote-summary">
            <div class="quote-summary-item">
                <span>Request reference</span>
                <strong>{{ $reference }}</strong>
            </div>

            <div class="quote-summary-item">
                <span>Stage</span>
                <strong><span class="pill {{ $stage['class'] }}">{{ $stage['label'] }}</span></strong>
            </div>

            <div class="quote-summary-item">
                <span>Offers received</span>
                <strong>{{ $offers->count() }}</strong>
            </div>

            <div class="quote-summary-item">
                <span>Daily energy · Peak load</span>
                <strong>{{ $quote->daily_load ?: '—' }} · {{ $quote->peak_load ?: '—' }}</strong>
            </div>
        </section>

        @include('admin.quote-requests.partials.admin-actions')

        @include('partials.request-journey', [
            'quote' => $quote,
            'offersCount' => $offers->count(),
            'project' => $project,
        ])

        {{-- Offers received --}}
        <section class="panel quote-panel">
            <div class="panel-heading quote-section-heading">
                <div>
                    <h2>Offers received</h2>
                    <p>Prices companies sent for this request, cheapest first. The customer sees all of them in the app.</p>
                </div>
                <span class="pill pill-blue">{{ $offers->count() }}</span>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Total price</th>
                            <th>Offer status</th>
                            <th>Sent</th>
                            <th>Message to customer</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($offers as $offer)
                            @php
                                $isLowest = $offers->count() > 1
                                    && (float) $offer->price === ($lowestByCurrency[$offer->currency] ?? null);
                            @endphp
                            <tr class="{{ $offer->status === 'selected' ? 'row-highlight' : '' }}">
                                <td>
                                    <div class="company-cell">
                                        <span class="company-avatar solar">{{ strtoupper(mb_substr($offer->company?->name ?? '?', 0, 1)) }}</span>
                                        <span>
                                            <strong>
                                                @if ($offer->company)
                                                    <a href="{{ route('admin.companies.edit', $offer->company->id) }}">{{ $offer->company->name }}</a>
                                                @else
                                                    Company #{{ $offer->company_id }}
                                                @endif
                                            </strong>
                                            <small>
                                                {{ $offer->company?->city ?: '—' }}
                                                @if ($offer->company?->is_verified) · ✓ Verified @endif
                                            </small>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ $offer->currency }} {{ number_format((float) $offer->price, 2) }}</strong>
                                    @if ($isLowest)
                                        <small class="cell-note positive">Lowest price</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="pill {{ $offerStatusClasses[$offer->status] ?? 'pill-blue' }}">
                                        {{ $offerStatusLabels[$offer->status] ?? ucfirst($offer->status) }}
                                    </span>
                                </td>
                                <td>
                                    {{ $offer->created_at?->format('d M Y, H:i') ?? '—' }}
                                    <small class="cell-note">{{ $offer->created_at && $quote->created_at ? $quote->created_at->diffForHumans($offer->created_at, true) . ' after the request' : '' }}</small>
                                </td>
                                <td class="cell-wrap">{{ \Illuminate\Support\Str::limit($offer->message ?: '—', 140) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="dashboard-empty">
                                    <strong>No offers yet.</strong>
                                    @if ($status === 'pending')
                                        No company has answered this customer
                                        {{ $quote->created_at ? '(waiting ' . $quote->created_at->diffForHumans(null, true) . ')' : '' }}.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Project created from the accepted offer --}}
        <section class="panel quote-panel">
            <div class="panel-heading quote-section-heading">
                <div>
                    <h2>Project</h2>
                    <p>The installation the chosen company carries out for this customer.</p>
                </div>
                @if ($project)
                    <span class="pill {{ $project->status === 'completed' ? 'pill-green' : ($project->status === 'in_progress' ? 'pill-blue' : 'pill-orange') }}">
                        {{ $projectStatusLabels[$project->status] ?? ucfirst($project->status) }}
                    </span>
                @endif
            </div>

            @if ($project)
                @php
                    $milestones = $project->milestones;
                    $doneMilestones = $milestones->where('status', 'completed')->count();
                    $latestUpdates = $project->updates->take(5);
                @endphp

                <div class="quote-detail-grid">
                    <div class="quote-detail">
                        <span>Progress</span>
                        <strong>{{ $project->progress_percent }}%</strong>
                        <div class="mini-progress mini-progress-lg"><span style="width: {{ $project->progress_percent }}%"></span></div>
                    </div>
                    <div class="quote-detail">
                        <span>Milestones</span>
                        <strong>{{ $doneMilestones }} of {{ $milestones->count() }} completed</strong>
                    </div>
                    <div class="quote-detail">
                        <span>Accepted</span>
                        <strong>{{ $project->accepted_at?->format('d M Y') ?? '—' }}</strong>
                    </div>
                    <div class="quote-detail">
                        <span>Installation started</span>
                        <strong>{{ $project->started_at?->format('d M Y') ?? 'Not yet' }}</strong>
                    </div>
                    <div class="quote-detail">
                        <span>Completed</span>
                        <strong>{{ $project->completed_at?->format('d M Y') ?? 'Not yet' }}</strong>
                    </div>
                    <div class="quote-detail">
                        <span>Last activity</span>
                        <strong>{{ ($project->updates->first()?->created_at ?? $project->updated_at)?->diffForHumans() ?? '—' }}</strong>
                    </div>
                </div>

                <div class="quote-subsection">
                    <div class="quote-subsection-heading">
                        <h3>Latest updates from the company</h3>
                        <p>Most recent first. Internal notes are marked.</p>
                    </div>

                    <div class="project-activity-list">
                        @forelse ($latestUpdates as $update)
                            <article class="project-activity-item">
                                <span class="project-activity-dot"></span>
                                <div class="project-activity-content">
                                    <div class="project-activity-meta">
                                        <strong>
                                            @if ($update->old_status !== $update->new_status && $update->new_status)
                                                Status changed to {{ $projectStatusLabels[$update->new_status] ?? $update->new_status }}
                                            @else
                                                Progress update
                                            @endif
                                        </strong>
                                        <small>{{ $update->created_at?->format('d M Y, H:i') }}</small>
                                    </div>
                                    @if ($update->progress_percent !== null)
                                        <small>Progress: {{ $update->progress_percent }}%</small>
                                    @endif
                                    @if ($update->message)
                                        <p>{{ $update->message }}</p>
                                    @endif
                                    @if (!$update->visible_to_customer)
                                        <span class="pill pill-purple">Internal note</span>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <p class="report-empty">The company has not posted any updates yet.</p>
                        @endforelse
                    </div>
                </div>
            @else
                <p class="panel-empty-note">
                    @if (in_array($status, ['cancelled', 'rejected'], true))
                        No project: this request is closed.
                    @else
                        No project yet. A project starts when the customer accepts one of the offers above.
                    @endif
                </p>
            @endif
        </section>

        {{-- Request information --}}
        <section class="panel quote-panel">
            <div class="panel-heading quote-section-heading">
                <div>
                    <h2>Request information</h2>
                    <p>Details submitted by the customer</p>
                </div>

                <span class="pill {{ $statusClasses[$status] ?? 'pill-blue' }}">
                    {{ $statusLabels[$status] ?? ucfirst($status) }}
                </span>
            </div>

            <div class="quote-detail-grid">
                <div class="quote-detail">
                    <span>Request ID</span>
                    <strong>{{ $reference }}</strong>
                </div>

                <div class="quote-detail">
                    <span>Quote name</span>
                    <strong>{{ $quote->quote_name }}</strong>
                </div>

                <div class="quote-detail">
                    <span>Company</span>

                    @if ($company)
                        <strong class="quote-company-name">
                            {{ $company->name }}
                        </strong>
                        <small>{{ $project ? 'Chosen by the customer' : 'Requested directly by the customer' }}</small>
                    @else
                        <strong class="quote-company-waiting">
                            Not chosen yet
                        </strong>
                        <small>Open to all companies until the customer accepts an offer.</small>
                    @endif
                </div>

                <div class="quote-detail">
                    <span>Daily load</span>
                    <strong>{{ $quote->daily_load ?: '—' }}</strong>
                </div>

                <div class="quote-detail">
                    <span>Peak load</span>
                    <strong>{{ $quote->peak_load ?: '—' }}</strong>
                </div>

                <div class="quote-detail">
                    <span>Submitted</span>
                    <strong>{{ $quote->created_at?->format('d M Y, H:i') ?? '—' }}</strong>
                </div>

                <div class="quote-detail">
                    <span>Last updated</span>
                    <strong>{{ $quote->updated_at?->format('d M Y, H:i') ?? '—' }}</strong>
                </div>
            </div>

            {{-- Devices and watts from the app calculator --}}
            @include('partials.quote-appliances', ['quote' => $quote])

            {{-- Estimated load summary --}}
            @php
                $loadSummary = $quote->estimated_load ?? '';

                $dailyEnergy = '—';
                $peakLoad = '—';
                $panelCount = '—';
                $inverterSize = '—';
                $batteryCount = '—';
                $systemType = '—';

                if (preg_match('/Estimated load:\s*([^,.\r\n]+(?:\.\d+)?\s*kWh\/day)/i', $loadSummary, $matches)) {
                    $dailyEnergy = trim($matches[1]);
                }

                if (preg_match('/peak\s*([^,\r\n]+?)(?=\.\s*Suggested system:|[\r\n]|$)/i', $loadSummary, $matches)) {
                    $peakLoad = trim($matches[1]);
                }

                if (preg_match('/Suggested system:\s*(\d+)\s*panels?/i', $loadSummary, $matches)) {
                    $panelCount = $matches[1];
                }

                if (preg_match('/Suggested system:.*?(\d+(?:\.\d+)?)\s*kW\s*inverter/i', $loadSummary, $matches)) {
                    $inverterSize = $matches[1] . ' kW';
                }

                if (preg_match('/Suggested system:.*?(\d+)\s*batter(?:y|ies)/i', $loadSummary, $matches)) {
                    $batteryCount = $matches[1];
                }

                if (preg_match('/System type:\s*([^\r\n]+)/i', $loadSummary, $matches)) {
                    $systemType = trim($matches[1]);
                }
            @endphp

            <div class="quote-subsection">
                <div class="quote-subsection-heading">
                    <h3>Estimated load summary</h3>
                    <p>Calculator information submitted with this request</p>
                </div>

                {{-- Energy requirements --}}
                <div class="quote-energy-grid">
                    <div class="quote-energy-item">
                        <span>Daily energy</span>
                        <strong>{{ $dailyEnergy }}</strong>
                    </div>

                    <div class="quote-energy-item">
                        <span>Peak load</span>
                        <strong>{{ $peakLoad }}</strong>
                    </div>

                    <div class="quote-energy-item">
                        <span>System type</span>
                        <strong>{{ $systemType }}</strong>
                    </div>
                </div>

                {{-- Recommended equipment --}}
                <div class="quote-equipment-section">
                    <h4>Suggested equipment</h4>

                    <div class="quote-equipment-grid">
                        <div class="quote-equipment-item">
                            <span class="quote-equipment-icon">☀</span>
                            <div>
                                <small>Solar panels</small>
                                <strong>{{ $panelCount }}
                                    {{ is_numeric($panelCount) && (int) $panelCount === 1 ? 'Panel' : (is_numeric($panelCount) ? 'Panels' : '') }}</strong>
                            </div>
                        </div>

                        <div class="quote-equipment-item">
                            <span class="quote-equipment-icon">ϟ</span>
                            <div>
                                <small>Inverter</small>
                                <strong>{{ $inverterSize }}</strong>
                            </div>
                        </div>

                        <div class="quote-equipment-item">
                            <span class="quote-equipment-icon">▣</span>
                            <div>
                                <small>Batteries</small>
                                <strong>{{ $batteryCount }}
                                    {{ is_numeric($batteryCount) && (int) $batteryCount === 1 ? 'Battery' : (is_numeric($batteryCount) ? 'Batteries' : '') }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Original calculator text --}}
                <div class="quote-text-content">{{ $loadSummary ?: 'No calculator summary provided.' }}</div>
            </div>


            {{-- Customer message --}}
            <div class="quote-subsection">
                <div class="quote-subsection-heading">
                    <h3>Customer message</h3>
                    <p>Additional instructions or contact preferences</p>
                </div>

                <div class="quote-text-content">{{ $quote->message ?: 'No additional message provided.' }}</div>
            </div>
        </section>


        {{-- Customer and company information side by side --}}
        <div class="quote-parties-grid">

            {{-- Customer information --}}
            <section class="panel quote-panel quote-party-panel">
                <div class="panel-heading quote-section-heading">
                    <div>
                        <h2>Customer information</h2>
                        <p>Customer account associated with this quote</p>
                    </div>
                </div>

                <div class="quote-detail-grid quote-party-details">
                    <div class="quote-detail">
                        <span>Customer ID</span>
                        <strong>{{ $quote->customer_id }}</strong>
                    </div>

                    <div class="quote-detail">
                        <span>Full name</span>
                        <strong>{{ $customerName }}</strong>
                    </div>

                    <div class="quote-detail">
                        <span>Email</span>
                        <strong>{{ $customer?->email ?? '—' }}</strong>
                    </div>

                    <div class="quote-detail">
                        <span>Phone</span>
                        <strong>{{ $customer?->phone ?? '—' }}</strong>
                    </div>

                    <div class="quote-detail">
                        <span>City</span>
                        <strong>{{ $customer?->city ?? '—' }}</strong>
                    </div>

                    <div class="quote-detail">
                        <span>Address</span>
                        <strong>{{ $customer?->address ?? '—' }}</strong>
                    </div>
                </div>
            </section>

            {{-- Company information --}}
            <section class="panel quote-panel quote-party-panel">
                <div class="panel-heading quote-section-heading">
                    <div>
                        <h2>Company information</h2>
                        <p>{{ $project ? 'Company the customer chose' : 'Company linked to this request' }}</p>
                    </div>
                </div>

                @if ($company)
                    <div class="quote-company-card">
                        <div class="quote-company-mark" aria-hidden="true">
                            {{ strtoupper(substr($company->name, 0, 1)) }}
                        </div>

                        <div class="quote-company-details">
                            <span>Assigned solar company</span>

                            <strong class="quote-company-name">
                                {{ $company->name }}
                            </strong>

                            <small>Phone: {{ $company->phone }}</small>
                        </div>
                    </div>
                @else
                    <div class="quote-company-card">
                        <div class="quote-company-details">
                            <strong class="quote-company-waiting">
                                Not chosen yet
                            </strong>

                            <span>
                                {{ $offers->count() > 0
                                    ? 'The customer is comparing ' . $offers->count() . ' ' . \Illuminate\Support\Str::plural('offer', $offers->count()) . '.'
                                    : 'No company has sent an offer yet.' }}
                            </span>
                        </div>
                    </div>
                @endif
            </section>

        </div>


        {{-- Print footer --}}
        <footer class="quote-document-footer print-only">
            <span>SunXFinder — Solar Energy Solutions</span>
            <span>Request {{ $reference }}</span>
        </footer>
    </div>

    <style>

    </style>
    <script>
        window.addEventListener('beforeprint', function () {
            document.title = @json('Quote Details-' . $reference . ' · SunXFinder');
        });
    </script>

    <script>
        function printQuote() {
            const originalTitle = document.title;
            document.title = @json('Quote Details-' . $reference . ' · SunXFinder');

            window.print();

            window.addEventListener('afterprint', function restoreTitle() {
                document.title = originalTitle;
                window.removeEventListener('afterprint', restoreTitle);
            }, { once: true });
        }
    </script>

@endsection
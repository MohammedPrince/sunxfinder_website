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

        $customerName = $customer?->name
            ?? $customer?->full_name
            ?? $customer?->customer_name
            ?? 'Customer #' . $quote->customer_id;

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

        {{-- Summary --}}
        <section class="quote-summary">
            <div class="quote-summary-item">
                <span>Request reference</span>
                <strong>{{ $reference }}</strong>
            </div>

            <div class="quote-summary-item">
                <span>Status</span>
                <strong>{{ $statusLabels[$status] ?? ucfirst($status) }}</strong>
            </div>

            <div class="quote-summary-item">
                <span>Daily energy</span>
                <strong>{{ $quote->daily_load ?: '—' }}</strong>
            </div>

            <div class="quote-summary-item">
                <span>Peak load</span>
                <strong>{{ $quote->peak_load ?: '—' }}</strong>
            </div>
        </section>


        <p style="padding-top:1px;"></p>

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
                    <span>Service needed</span>
                    <strong>{{ $quote->service_needed }}</strong>
                </div>

                <div class="quote-detail">
                    <span>Company assignment</span>

                    @if ($company)
                        <strong class="quote-company-name">
                            {{ $company->name }}
                        </strong>
                    @else
                        <strong class="quote-company-waiting">
                            No company assigned
                        </strong>
                        <small>This request is waiting for a solar company.</small>
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
                        <p>Provider assigned to this request</p>
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
                                No company assigned
                            </strong>

                            <span>
                                This request is waiting for a solar company.
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
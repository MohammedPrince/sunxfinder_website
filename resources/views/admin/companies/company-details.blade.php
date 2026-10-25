@extends('admin.layouts.app')

@section('title', $company->name . ' details')
@section('breadcrumb', 'Company details')
@section('eyebrow', 'COMPANY DIRECTORY')
@section('page-title', $company->name)
@section('page-description', 'Company profile, customers, quote activity, offers, projects and ratings.')

@section('page-actions')
    <a href="{{ route('admin.companies.index') }}" class="btn btn-light">Back to companies</a>
    <button type="button" class="btn btn-light" onclick="window.print()" title="Print, or choose “Save as PDF” as the printer">
        Print / Save PDF
    </button>
    <a href="{{ route('admin.companies.edit', $company->id) }}" class="btn btn-primary">Edit company</a>
@endsection

@section('content')
    @php
        $decodeList = static function ($value) {
            if (is_array($value)) {
                return $value;
            }

            if (!is_string($value) || trim($value) === '') {
                return [];
            }

            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [$value];
        };

        $cleanDescription = static function ($value) {
            $value = (string) ($value ?? '');
            $value = preg_replace('/<\s*br\s*\/?>|<\/\s*(p|div|li|h[1-6])\s*>/i', "\n", $value);
            $value = strip_tags($value);
            $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $value = preg_replace("/[ \t]+\n/", "\n", $value);

            return trim(preg_replace("/\n{3,}/", "\n\n", $value));
        };

        // Services are saved as "a, b, c" (or ، in Arabic); older rows may hold a JSON array.
        $serviceItems = static function ($value) use ($decodeList) {
            $list = $decodeList($value);

            return count($list) === 1
                ? app(\App\Helpers\Helper::class)->splitList(strip_tags((string) $list[0]))
                : array_values(array_filter(array_map('trim', $list)));
        };

        $services = $serviceItems($company->services);
        $arabicServices = $serviceItems($company->ar_services);
        $description = $cleanDescription($company->description);
        $arabicDescription = $cleanDescription($company->ar_description);
        $logoUrl = app(\App\Helpers\Helper::class)->publicUrl($company->logo_path);
        $initial = mb_strtoupper(mb_substr($company->name ?? '?', 0, 1, 'UTF-8'), 'UTF-8');
        $isPublic = $company->slug && $company->status === 'approved' && $company->is_active;
        $productCount = $company->products()->count();

        $quoteStatusLabels = [
            'pending' => 'Pending',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        // Each status card draws its counts as bars relative to that card's total.
        $statusCards = [
            'Quote requests' => collect($quoteStatusLabels)
                ->mapWithKeys(fn($label, $status) => [$label => (int) ($quoteStatusCounts[$status] ?? 0)])->all(),
            'Offers' => collect($offerStatusCounts)
                ->mapWithKeys(fn($count, $status) => [ucfirst(str_replace('_', ' ', $status)) => (int) $count])->all(),
            'Projects' => collect($projectStatusCounts)
                ->mapWithKeys(fn($count, $status) => [ucfirst(str_replace('_', ' ', $status)) => (int) $count])->all(),
            'Ratings' => [
                'Visible' => (int) $ratingSummary['reviews'],
                'Hidden' => max(0, $ratingCount - (int) $ratingSummary['reviews']),
            ],
        ];
    @endphp

    {{-- Shown only on paper / PDF --}}
    <div class="cd-print-title">
        <strong>SunXFinder · Company report</strong>
        <span>{{ $company->name }} · Printed {{ now()->format('M d, Y H:i') }}</span>
    </div>

    {{-- Header --}}
    <section class="panel cd-hero">
        <div class="cd-hero-main">
            <div class="cd-logo {{ $logoUrl ? 'has-logo' : '' }}" dir="auto">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $company->name }} logo"
                        onerror="this.parentElement.classList.remove('has-logo');this.replaceWith(document.createTextNode({{ json_encode($initial) }}))">
                @else
                    {{ $initial }}
                @endif
            </div>

            <div class="cd-identity">
                <h2 dir="auto">{{ $company->name }}</h2>
                <p class="cd-meta">
                    <span>{{ '@' . $company->username }}</span>
                    <span aria-hidden="true">·</span>
                    <span>Joined {{ $company->created_at?->format('M d, Y') ?? '—' }}</span>
                </p>

                <div class="cd-chips">
                    <span class="cd-chip">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12Z" /><circle cx="12" cy="9" r="2.5" /></svg>
                        {{ $company->city ? $company->city . ', Sudan' : 'City not set' }}
                    </span>
                    @if ($company->email)
                        <a class="cd-chip" href="mailto:{{ $company->email }}">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" /></svg>
                            {{ $company->email }}
                        </a>
                    @endif
                    @if ($company->phone)
                        <a class="cd-chip" href="tel:{{ $company->phone }}" dir="ltr">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2Z" /></svg>
                            {{ $company->phone }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="company-details-badges">
            <span class="pill {{ $company->status === 'approved' ? 'pill-green' : ($company->status === 'rejected' ? 'pill-red' : 'pill-yellow') }}">
                {{ ucfirst($company->status) }}
            </span>
            <span class="pill {{ $company->is_active ? 'pill-green' : 'pill-red' }}">
                {{ $company->is_active ? 'Active' : 'Inactive' }}
            </span>
            <span class="verify {{ $company->is_verified ? 'verified' : 'pending' }}">
                {{ $company->is_verified ? 'Verified' : 'Unverified' }}
            </span>
        </div>
    </section>

    {{-- Key numbers --}}
    <section class="cd-stats">
        @foreach ([
            'Customers' => number_format($customerCount),
            'Quote requests' => number_format($quoteCount),
            'Offers submitted' => number_format($offerCount),
            'Projects' => number_format($projectCount),
            'Ratings' => number_format($ratingCount),
            'Average rating' => $ratingSummary['rating'] !== null ? number_format($ratingSummary['rating'], 1) . ' ★' : '—',
        ] as $label => $value)
            <div class="cd-stat">
                <span>{{ $label }}</span>
                <strong>{{ $value }}</strong>
            </div>
        @endforeach
    </section>

    {{-- Profile --}}
    <div class="cd-profile">
        <section class="panel">
            <div class="panel-heading"><div><h2>Contact &amp; registration</h2><p>How to reach the company and what it reported</p></div></div>
            <div class="panel-body">
                <dl class="cd-facts">
                    <div><dt>City</dt><dd>{{ $company->city ?: '—' }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $company->email ?: '—' }}</dd></div>
                    <div><dt>Phone</dt><dd dir="ltr">{{ $company->phone ?: '—' }}</dd></div>
                    <div><dt>License number</dt><dd>{{ $company->license_number ?: '—' }}</dd></div>
                    <div><dt>Years of experience</dt><dd>{{ $company->year_of_exp ?: '—' }}</dd></div>
                    <div><dt>Projects reported</dt><dd>{{ $company->number_of_projects ?? '—' }}</dd></div>
                    <div><dt>Products listed</dt><dd>{{ number_format($productCount) }}</dd></div>
                    <div>
                        <dt>Public profile</dt>
                        <dd>
                            @if ($isPublic)
                                <a href="{{ route('company-profile.show', ['slug' => $company->slug]) }}" target="_blank" rel="noopener noreferrer">Open ↗</a>
                            @else
                                Not public yet
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading"><div><h2>Services &amp; description</h2><p>What the company offers, in English and Arabic</p></div></div>
            <div class="panel-body cd-about">
                <div>
                    <h3>Services</h3>
                    <div class="cd-tags">
                        @forelse ($services as $service)
                            <span>{{ $service }}</span>
                        @empty
                            <em>—</em>
                        @endforelse
                    </div>
                </div>

                <div dir="rtl">
                    <h3>الخدمات</h3>
                    <div class="cd-tags">
                        @forelse ($arabicServices as $service)
                            <span>{{ $service }}</span>
                        @empty
                            <em>—</em>
                        @endforelse
                    </div>
                </div>

                <div>
                    <h3>Description</h3>
                    <p class="cd-copy">{{ $description ?: '—' }}</p>
                </div>

                <div dir="rtl">
                    <h3>الوصف</h3>
                    <p class="cd-copy">{{ $arabicDescription ?: '—' }}</p>
                </div>
            </div>
        </section>
    </div>

    {{-- Activity --}}
    <section class="panel">
        <div class="panel-heading"><div><h2>Activity by status</h2><p>Current counts for this company</p></div></div>
        <div class="panel-body">
            <div class="company-detail-status-grid">
                @foreach ($statusCards as $title => $counts)
                    @php($cardTotal = max(1, array_sum($counts)))
                    <div class="company-detail-status-card">
                        <h3>{{ $title }} <small>{{ number_format(array_sum($counts)) }}</small></h3>
                        @forelse ($counts as $label => $count)
                            <div class="cd-bar-row">
                                <p>{{ $label }} <strong>{{ number_format($count) }}</strong></p>
                                <div class="cd-bar"><span style="width: {{ round($count / $cardTotal * 100) }}%"></span></div>
                            </div>
                        @empty
                            <p>Nothing yet.</p>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="panel table-panel">
        <div class="panel-heading"><div><h2>Customers</h2><p>Customers connected through requests, projects, or ratings</p></div></div>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Customer</th><th>Email</th><th>Phone</th><th>City</th><th>Joined</th></tr></thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td><strong>{{ $customer->full_name ?: $customer->username }}</strong><small class="d-block">{{ '@' . $customer->username }}</small></td>
                            <td>{{ $customer->email ?: '—' }}</td>
                            <td>{{ $customer->phone ?: '—' }}</td>
                            <td>{{ $customer->city ?: '—' }}</td>
                            <td>{{ $customer->created_at?->format('M d, Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No customers are connected to this company yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($customers->total() > 0)
            <div class="no-print">@include('partials.table-pagination', ['paginator' => $customers, 'label' => 'customers'])</div>
        @endif
    </section>

    <section class="panel table-panel">
        <div class="panel-heading"><div><h2>Quote requests and offers</h2><p>Requests assigned to this company or answered with one of its offers</p></div></div>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Request</th><th>Customer</th><th>Status</th><th>Offers by company</th><th>Project status</th><th>Created</th></tr></thead>
                <tbody>
                    @forelse ($quotes as $quote)
                        @php($stage = $quote->stage($quote->company_offers_count))
                        <tr>
                            <td><a href="{{ route('admin.quote-requests.show', $quote->id) }}">#{{ $quote->id }} · {{ $quote->quote_name ?: 'Quote request' }}</a></td>
                            <td>{{ $quote->customer?->full_name ?: ($quote->customer?->username ?? '—') }}</td>
                            <td><span class="pill {{ $stage['class'] }}">{{ $stage['label'] }}</span></td>
                            <td>{{ number_format($quote->company_offers_count) }}</td>
                            <td>{{ $quote->project ? ucfirst(str_replace('_', ' ', $quote->project->status)) : '—' }}</td>
                            <td>{{ $quote->created_at?->format('M d, Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No quote requests or offers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($quotes->total() > 0)
            <div class="no-print">@include('partials.table-pagination', ['paginator' => $quotes, 'label' => 'requests'])</div>
        @endif
    </section>

    <section class="panel table-panel">
        <div class="panel-heading"><div><h2>Projects</h2><p>Installation work and progress</p></div></div>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Project</th><th>Customer</th><th>Status</th><th>Progress</th><th>Started</th><th>Completed</th></tr></thead>
                <tbody>
                    @forelse ($projects as $project)
                        <tr>
                            <td>#{{ $project->id }}{{ $project->quote?->quote_name ? ' · ' . $project->quote->quote_name : '' }}</td>
                            <td>{{ $project->customer?->full_name ?: ($project->customer?->username ?? '—') }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $project->status)) }}</td>
                            <td>{{ (int) $project->progress_percent }}%</td>
                            <td>{{ $project->started_at?->format('M d, Y') ?? '—' }}</td>
                            <td>{{ $project->completed_at?->format('M d, Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No projects yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($projects->total() > 0)
            <div class="no-print">@include('partials.table-pagination', ['paginator' => $projects, 'label' => 'projects'])</div>
        @endif
    </section>

    <section class="panel table-panel">
        <div class="panel-heading"><div><h2>Ratings</h2><p>Customer feedback and visibility</p></div></div>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Customer</th><th>Rating</th><th>Comment</th><th>Visibility</th><th>Date</th></tr></thead>
                <tbody>
                    @forelse ($ratings as $rating)
                        <tr>
                            <td>{{ $rating->customer?->full_name ?: ($rating->customer?->username ?? '—') }}</td>
                            <td>★ {{ $rating->stars }} / 5</td>
                            <td>{{ $rating->comment ?: '—' }}</td>
                            <td><span class="pill {{ $rating->is_visible ? 'pill-green' : 'pill-yellow' }}">{{ $rating->is_visible ? 'Visible' : 'Hidden' }}</span></td>
                            <td>{{ $rating->created_at?->format('M d, Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No ratings yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($ratings->total() > 0)
            <div class="no-print">@include('partials.table-pagination', ['paginator' => $ratings, 'label' => 'ratings'])</div>
        @endif
    </section>
@endsection

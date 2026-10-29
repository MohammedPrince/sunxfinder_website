@extends('admin.layouts.app')

@section('title', 'Complaint #' . $complaint->id)
@section('breadcrumb', 'Customer complaints / #' . $complaint->id)
@section('eyebrow', 'CUSTOMER SUPPORT')
@section('page-title', 'Complaint #' . $complaint->id)
@section('page-description', 'Review the report and coordinate follow-up with the company and customer.')

@section('page-actions')
    <a class="btn btn-light" href="{{ route('admin.complaints.index') }}">← All complaints</a>
@endsection

@section('content')
    @php
        $company = $complaint->company;
        $customer = $complaint->customer;
        $statuses = ['pending' => 'Pending', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'rejected' => 'Rejected'];
        $statusClasses = ['pending' => 'pill-orange', 'in_progress' => 'pill-blue', 'resolved' => 'pill-green', 'rejected' => 'pill-red'];
        $categories = [
            'product_issue' => 'Product issue', 'service_issue' => 'Service issue', 'pricing_issue' => 'Pricing issue',
            'delivery_issue' => 'Delivery issue', 'customer_service' => 'Customer service', 'other' => 'Other',
        ];
        $categoryLabel = $categories[$complaint->category] ?? str($complaint->category)->replace('_', ' ')->title();
        $statusLabel = $statuses[$complaint->status] ?? str($complaint->status)->replace('_', ' ')->title();
        $customerName = $customer?->full_name ?: $customer?->username ?: 'Customer removed';
        $companyName = $company?->name ?? 'Company removed';
        $customerHasEmail = (bool) filter_var($customer?->email, FILTER_VALIDATE_EMAIL);
        $companyHasEmail = (bool) filter_var($company?->email, FILTER_VALIDATE_EMAIL);
        $helper = app(\App\Helpers\Helper::class);
        $photos = collect($complaint->photos ?? [])
            ->map(fn ($photo) => is_array($photo) ? ($photo['path'] ?? '') : $photo)
            ->filter()
            ->map(fn ($path) => $helper->publicUrl($path))
            ->values();
        $initial = fn ($name) => mb_strtoupper(mb_substr(trim($name), 0, 1)) ?: '?';
        $parties = [
            [
                'role' => 'Company',
                'name' => $companyName,
                'model' => $company,
                'url' => $company && !$company->trashed() ? route('admin.companies.details', $company->id) : null,
                'avatar' => 'avatar-orange',
            ],
            [
                'role' => 'Customer',
                'name' => $customerName,
                'model' => $customer,
                'url' => $customer && !$customer->trashed() ? route('admin.customers.edit', $customer->id) : null,
                'avatar' => 'avatar-blue',
            ],
        ];
    @endphp

    @include('partials.page-guide', [
        'key' => 'admin-complaint-details',
        'title' => 'How to handle a complaint',
        'intro' => 'Review what the customer reported, get the company\'s side, then close the complaint with a reply to the customer.',
        'steps' => [
            '<strong>Review</strong>: read the report and open the photos. Set the status to "In progress" when you start.',
            '<strong>Notify company</strong>: email the company the complaint. The customer\'s name, email and phone are removed from that email.',
            '<strong>Reply to customer</strong>: write a reply and send it as an in-app notification or by email. Leave the reply empty to only change the status.',
            '<strong>Close</strong>: set the status to "Resolved" or "Rejected" once the customer has been answered.',
        ],
        'note' => 'Whenever you save with text in the reply box, that text is sent to the customer. Your last reply is shown above the form for reference.',
    ])

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="panel admin-project-hero">
        <div class="admin-project-hero-top">
            <div class="admin-project-hero-title">
                <span class="pill {{ $statusClasses[$complaint->status] ?? 'pill-blue' }}">{{ $statusLabel }}</span>
                <h2>{{ $categoryLabel }}</h2>
                <p>Reported by {{ $customerName }} about {{ $companyName }}</p>
            </div>
        </div>

        <div class="admin-project-stats">
            <div>
                <small>Received</small>
                <strong>{{ $complaint->created_at?->format('M j, Y') ?? '—' }}</strong>
                <span>{{ $complaint->created_at?->diffForHumans() }}</span>
            </div>
            <div>
                <small>Photos</small>
                <strong>{{ $photos->count() }}</strong>
                <span>attached</span>
            </div>
            <div>
                <small>Customer reply</small>
                <strong>{{ $complaint->customer_replied_at ? 'Sent' : 'Not sent' }}</strong>
                <span>{{ $complaint->customer_replied_at?->format('M j, Y H:i') ?? 'No reply yet' }}</span>
            </div>
            <div>
                <small>Company notified</small>
                <strong>{{ $complaint->company_notified_at ? 'Yes' : 'No' }}</strong>
                <span>{{ $complaint->company_notified_at?->format('M j, Y H:i') ?? 'Not emailed yet' }}</span>
            </div>
        </div>
    </section>

    <div class="admin-project-layout">
        <div class="admin-project-main">
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>Customer report</h2>
                        <p>Submitted {{ $complaint->created_at?->format('M j, Y H:i') }}</p>
                    </div>
                    <span class="complaint-category">{{ $categoryLabel }}</span>
                </div>

                <div class="complaint-description">{{ $complaint->description }}</div>

                <h3 class="complaint-subheading">Photos</h3>
                @if ($photos->isNotEmpty())
                    <div class="complaint-photos">
                        @foreach ($photos as $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" title="Open photo {{ $loop->iteration }}">
                                <img src="{{ $url }}" alt="Complaint photo {{ $loop->iteration }}" loading="lazy">
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="report-empty">No photos attached.</p>
                @endif
            </section>

            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>Update and reply</h2>
                        <p>Change the complaint status and, optionally, send a response to the customer.</p>
                    </div>
                </div>

                @if ($complaint->admin_reply)
                    <div class="complaint-last-reply">
                        <small>Last reply{{ $complaint->customer_replied_at ? ' · sent ' . $complaint->customer_replied_at->format('M j, Y H:i') : '' }}</small>
                        <p>{{ $complaint->admin_reply }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.complaints.update', $complaint->id) }}" class="form-grid">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $complaint->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="reply_channel">Send reply by</label>
                        <select id="reply_channel" name="reply_channel">
                            <option value="notification" @selected(old('reply_channel') !== 'email')>In-app notification</option>
                            <option value="email" @selected(old('reply_channel') === 'email') @disabled(!$customerHasEmail)>
                                Email{{ $customerHasEmail ? '' : ' (no valid customer email)' }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="reply">Reply to customer</label>
                        <textarea id="reply" name="reply" rows="6" maxlength="5000"
                            placeholder="Write the response for the customer. Leave empty to only update the status.">{{ old('reply') }}</textarea>
                        <span class="form-hint">The reply is sent only when this field is filled in.</span>
                    </div>

                    <div class="form-actions full-width">
                        <button class="btn btn-primary" type="submit">Save and send reply</button>
                    </div>
                </form>
            </section>
        </div>

        <aside class="admin-project-side">
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>People involved</h2>
                        <p>Contact details for follow-up.</p>
                    </div>
                </div>

                <div class="admin-project-parties">
                    @foreach ($parties as $party)
                        @php $model = $party['model']; @endphp
                        <div class="admin-project-party">
                            <div class="admin-project-party-head">
                                <span class="person-avatar {{ $party['avatar'] }}">{{ $initial($party['name']) }}</span>
                                <div>
                                    <small>{{ $party['role'] }}</small>
                                    @if ($party['url'])
                                        <a href="{{ $party['url'] }}"><strong>{{ $party['name'] }}</strong></a>
                                    @else
                                        <strong>{{ $party['name'] }}</strong>
                                    @endif
                                    @if ($model?->trashed())
                                        <span class="pill pill-red">Deleted account</span>
                                    @elseif ($model)
                                        <span class="admin-project-party-city">{{ $model->city ?: 'City not set' }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="admin-project-party-links">
                                @if ($model?->phone)
                                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $model->phone) }}">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z" /></svg>
                                        {{ $model->phone }}
                                    </a>
                                @endif
                                @if ($model?->email)
                                    <a href="mailto:{{ $model->email }}">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-10 6L2 7" /></svg>
                                        {{ $model->email }}
                                    </a>
                                @endif
                                @if (!$model?->phone && !$model?->email)
                                    <small>No {{ strtolower($party['role']) }} contact details are available.</small>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>Notify company</h2>
                        <p>Emails the complaint details with the customer's name, email and phone removed.</p>
                    </div>
                </div>

                <dl class="admin-project-dates">
                    <div class="{{ $complaint->company_notified_at ? 'is-done' : '' }}">
                        <dt>Last sent</dt>
                        <dd>
                            {{ $complaint->company_notified_at?->format('M j, Y H:i') ?? 'Not sent' }}
                            @if ($complaint->company_notified_at)<small>{{ $complaint->company_notified_at->diffForHumans() }}</small>@endif
                        </dd>
                    </div>
                </dl>

                <form method="POST" action="{{ route('admin.complaints.company-email', $complaint->id) }}" class="complaint-notify-form">
                    @csrf
                    <button class="btn btn-light" type="submit" @disabled(!$companyHasEmail)>
                        {{ $complaint->company_notified_at ? 'Email company again' : 'Email company' }}
                    </button>
                    @unless ($companyHasEmail)
                        <span class="form-hint">The company has no valid email address.</span>
                    @endunless
                </form>
            </section>
        </aside>
    </div>
@endsection

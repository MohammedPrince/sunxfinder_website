@extends('admin.layouts.app')

@section('title', 'Notification Details')
@section('breadcrumb', 'Notifications / Details')
@section('eyebrow', 'CUSTOMER COMMUNICATION')
@section('page-title', 'Notification Details')
@section('page-description', 'Review notification content, audience, and recipient read status.')

@section('page-actions')
    <a href="{{ route('admin.notifications.index') }}" class="btn btn-primary">← Back to Notifications</a>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
        </div>
    @endif

    <div class="metrics-strip">
        <div><small>Total recipients</small><strong>{{ number_format($notification->recipients_count ?? $notification->recipients()->count()) }}</strong></div>
        <div><small>Read</small><strong>{{ number_format($readCount ?? 0) }}</strong></div>
        <div><small>Unread</small><strong>{{ number_format($unreadCount ?? max(0, ($notification->recipients_count ?? $notification->recipients()->count()) - ($readCount ?? 0))) }}</strong></div>
        <div><small>Published</small><strong>{{ $notification->published_at?->format('M d, Y') ?? '—' }}</strong></div>
    </div>

    <section class="panel">
        <div class="panel-heading"><h2>Notification overview</h2><p>Notification #{{ $notification->id }}</p></div>
        <div class="panel-body">
            <div class="form-grid">
                <div class="form-group"><label>Type</label><div class="form-control">{{ ucfirst(str_replace('_', ' ', $notification->type)) }}</div></div>
                <div class="form-group"><label>Audience</label><div class="form-control">{{ match ($notification->audience_type) {
                    'all' => 'All customers',
                    'city' => 'Customers in ' . $notification->audience_city,
                    default => 'Selected customers',
                } }}</div></div>
                <div class="form-group"><label>Status</label><div><span class="pill {{ $notification->status === 'published' ? 'pill-green' : 'pill-yellow' }}">{{ ucfirst($notification->status) }}</span></div></div>
                <div class="form-group"><label>Created</label><div>{{ $notification->created_at?->format('M d, Y H:i') ?? '—' }}</div></div>
                <div class="form-group"><label>Sender</label><div>{{ ucfirst($notification->sender_type) }}{{ $notification->sender_id ? ' #' . $notification->sender_id : '' }}</div></div>
            </div>
        </div>
    </section>

    @if ($notification->isCampaign())
        {{-- Campaign: the card as customers see it in the app, in both languages. --}}
        @php
            $company = $notification->company;
            $discount = $notification->offerDiscountPercent();
            $badges = ['offer' => ['Offer', 'عرض'], 'new_company' => ['New company', 'شركة جديدة'], 'promotion' => ['News', 'أخبار']];
        @endphp
        <section class="panel">
            <div class="panel-heading">
                <h2>Campaign as customers see it</h2>
                <p>
                    @if ($notification->link_url)
                        Button opens <a href="{{ $notification->link_url }}" target="_blank" rel="noopener">{{ $notification->link_url }}</a>.
                    @endif
                    @if ($notification->offer_expires_at)
                        Offer {{ $notification->offer_expires_at->isPast() ? 'expired' : 'valid until' }} {{ $notification->offer_expires_at->format('M d, Y H:i') }}.
                    @endif
                </p>
            </div>
            <div class="panel-body admin-project-layout">
                @foreach (['en', 'ar'] as $lang)
                    @php
                        $ar = $lang === 'ar';
                        $message = $ar ? ($notification->message_ar ?: $notification->message_en) : $notification->message_en;
                    @endphp
                    <div class="campaign-detail" dir="{{ $ar ? 'rtl' : 'ltr' }}" @if ($ar) lang="ar" @endif>
                        <article class="campaign-card">
                            @if ($notification->image_path)
                                <img class="campaign-card-image" src="{{ $notification->imageUrl() }}" alt="">
                            @endif
                            <div class="campaign-card-body">
                                <span class="campaign-badge" data-type="{{ $notification->type }}">{{ $badges[$notification->type][$ar ? 1 : 0] ?? ucfirst($notification->type) }}</span>
                                <h3>{{ $ar ? ($notification->title_ar ?: $notification->title_en) : $notification->title_en }}</h3>
                                @if ($company)
                                    <div class="campaign-company">
                                        @if ($company->logo_path)
                                            <img src="{{ app(\App\Helpers\Helper::class)->publicUrl($company->logo_path) }}" alt="">
                                        @endif
                                        <span><strong>{{ $company->name }}</strong><small>{{ $company->city }}</small></span>
                                    </div>
                                @endif
                                @if ($notification->offer_title_en)
                                    <div class="campaign-offer">
                                        <span>{{ $ar ? ($notification->offer_title_ar ?: $notification->offer_title_en) : $notification->offer_title_en }}</span>
                                        @if ($notification->offer_price !== null)
                                            <div>
                                                <strong>{{ number_format((float) $notification->offer_price) }} {{ $notification->offer_currency }}</strong>
                                                @if ($discount)
                                                    <del>{{ number_format((float) $notification->offer_old_price) }} {{ $notification->offer_currency }}</del>
                                                    <em>{{ $discount }}{{ $ar ? '٪ خصم' : '% off' }}</em>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                <div class="campaign-message">
                                    @if ($notification->format === 'html')
                                        {{-- Sanitized with Helper::sanitizeRichText when it was sent. --}}
                                        {!! $message !!}
                                    @else
                                        <div style="white-space:pre-line">{{ $message }}</div>
                                    @endif
                                </div>
                                @if ($notification->link_url)
                                    <span class="campaign-cta">{{ ($ar ? $notification->link_label_ar : $notification->link_label_en) ?: ($ar ? 'عرض' : 'View') }}</span>
                                @endif
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        </section>
    @else
        <section class="panel">
                <div class="panel-heading"><h2>English content</h2><p>Content shown to English-language customers.</p></div>
                <div class="panel-body">
                    <div class="form-group"><label>Title</label><div><strong>{{ $notification->title_en }}</strong></div></div>
                    <div class="form-group"><label>Message</label><div style="white-space:pre-line">{{ $notification->message_en }}</div></div>
                </div>
        </section>

    <section class="panel">
        <div class="panel-heading"><h2>Arabic content</h2><p>Content shown to Arabic-language customers.</p></div>
        <div class="panel-body" dir="rtl" lang="ar">
            <div class="form-group"><label>العنوان</label><div><strong>{{ $notification->title_ar ?: '—' }}</strong></div></div>
            <div class="form-group"><label>الرسالة</label><div style="white-space:pre-line">{{ $notification->message_ar ?: '—' }}</div></div>
        </div>
    </section>
    @endif

      @if (isset($recipients))
        <section class="panel table-panel">
            <div class="panel-heading table-toolbar">
                <div>
                    <h2>Recipients</h2>
                    <p>Customer contact details, registered devices, and read status.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="data-table js-data-table">
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Phone</th>
                            <th>FCM Token</th>
                            <th>Language</th>
                            <th>Read Status</th>
                            <th>Read At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recipients as $recipient)
                            @php
                                $customer = $recipient->customer;
                                $devices = $customer?->deviceTokens ?? collect();
                            @endphp

                            @if ($devices->isNotEmpty())
                                @foreach ($devices as $device)
                                    <tr>
                                        <td>{{ $customer?->full_name ?? '-' }}</td>
                                        <td>{{ $customer?->phone ?? '-' }}</td>
                                        <td>
                                            <span title="{{ $device->fcm_token }}">
                                                {{ $device->fcm_token }}
                                            </span>
                                        </td>
                                        <td>{{ strtoupper($device->lang ?? 'en') }}</td>
                                        <td>
                                            <span class="pill {{ $recipient->read_at ? 'pill-green' : 'pill-yellow' }}">
                                                {{ $recipient->read_at ? 'Read' : 'Unread' }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $recipient->read_at?->format('M d, Y H:i') ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td>{{ $customer?->full_name ?? '-' }}</td>
                                    <td>{{ $customer?->phone ?? '-' }}</td>
                                    <td colspan="2">No registered device</td>
                                    <td>
                                        <span class="pill {{ $recipient->read_at ? 'pill-green' : 'pill-yellow' }}">
                                            {{ $recipient->read_at ? 'Read' : 'Unread' }}
                                        </span>
                                    </td>
                                    <td>{{ $recipient->read_at?->format('M d, Y H:i') ?? '—' }}</td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">
                                    No recipients found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (method_exists($recipients, 'links'))
                @include('partials.table-pagination', [
                    'paginator' => $recipients,
                    'label' => 'recipients',
                ])
            @endif
        </section>
    @endif
@endsection

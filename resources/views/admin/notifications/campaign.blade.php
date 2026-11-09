@extends('admin.layouts.app')

@section('title', 'New customer campaign')
@section('breadcrumb', 'Notifications / Customer campaign')
@section('eyebrow', 'CUSTOMER COMMUNICATION')
@section('page-title', 'Customer campaign')
@section('page-description', 'Tell customers about a company offer, a new company or SunXFinder news, with an image, a link and a rich message.')

@section('page-actions')
    <a href="{{ route('admin.notifications.index') }}" class="btn btn-light">← Notifications</a>
@endsection

@section('content')
    @php
        $type = old('type', $presetCompanyId ? 'offer' : 'promotion');
        $audience = old('audience_type', $presetCustomerId ? 'selected' : 'all');
        $selectedCustomers = array_map('intval', old('customer_ids', $presetCustomerId ? [$presetCustomerId] : []));
        $format = old('format', 'html');
        $hasLink = (bool) old('has_link', 0);
        $companyId = (int) old('company_id', $presetCompanyId);
        $offerSource = old('offer_source', 'existing');
        $companyJson = $companies->mapWithKeys(fn($c) => [$c->id => [
            'name' => $c->name,
            'city' => $c->city,
            'logo' => app(\App\Helpers\Helper::class)->publicUrl($c->logo_path),
        ]]);
        // Same card fields the server copies from the offer (Notification::offerCardFields).
        $packageOfferJson = $packageOffers->mapWithKeys(fn($o) => [$o->id => [
            'company' => (int) $o->company_id,
            'title_en' => $o->title_en,
            'title_ar' => $o->title_ar,
            'price' => (float) $o->price,
            'old_price' => (float) $o->discount > 0 ? (float) $o->subtotal : null,
            'currency' => $o->currency,
            'expires_at' => $o->expires_at?->format('Y-m-d\TH:i'),
            'image' => $o->imageUrl(),
        ]]);
    @endphp

    @include('partials.page-guide', [
        'key' => 'admin-campaign',
        'title' => 'How customer campaigns work',
        'intro' => 'A campaign is a rich notification: customers get a push on their phone and keep the full message in the app inbox.',
        'steps' => [
            '<strong>Pick what it is about</strong>: a company offer, a new company joining, or general news.',
            '<strong>Choose who gets it</strong>: all app customers, everyone in one city, or hand-picked customers.',
            '<strong>Write it in English and Arabic</strong>. Each customer sees their app language; the push shows the plain text, the app shows the formatted message.',
            '<strong>Add an image, the offer and a button</strong> if you like. The preview on the right shows both the push and the in-app card.',
        ],
        'note' => 'Only customers signed in to the app (' . number_format($appCustomerCount) . ' now) can receive campaigns. Sending cannot be undone, but deleting the notification removes it from the app inbox.',
    ])

    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following:</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data" id="campaignForm" action="{{ route('admin.notifications.campaign.store') }}">
        @csrf

        <div class="slider-form-layout">
            <div class="slider-form-main">
                {{-- 1. Topic and audience --}}
                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>1. What is it about, and who gets it?</h2>
                            <p>The topic sets the badge customers see in the app.</p>
                        </div>
                    </div>

                    <div class="campaign-choice-grid" role="radiogroup" aria-label="Campaign topic">
                        @foreach ([
                            'offer' => ['Company offer', 'A ready system from one company that customers can accept in the app.'],
                            'new_company' => ['New company', 'Introduce a company that just joined.'],
                            'promotion' => ['News / promotion', 'SunXFinder news, tips or a general offer.'],
                        ] as $value => [$label, $hint])
                            <label class="campaign-choice">
                                <input type="radio" name="type" value="{{ $value }}" @checked($type === $value)>
                                <span><strong>{{ $label }}</strong><small>{{ $hint }}</small></span>
                            </label>
                        @endforeach
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="audience_type">Send to *</label>
                            <select class="select-control" id="audience_type" name="audience_type" required>
                                <option value="all" @selected($audience === 'all')>All app customers ({{ number_format($appCustomerCount) }})</option>
                                <option value="city" @selected($audience === 'city') @disabled(count($cities) === 0)>Customers in one city</option>
                                <option value="selected" @selected($audience === 'selected')>Selected customers</option>
                            </select>
                        </div>

                        <div class="form-group" id="campaignCityField" @if ($audience !== 'city') hidden @endif>
                            <label for="audience_city">City *</label>
                            <select class="select-control" id="audience_city" name="audience_city">
                                <option value="">Choose a city…</option>
                                @foreach ($cities as $city => $count)
                                    <option value="{{ $city }}" @selected(old('audience_city') === $city)>{{ $city }} ({{ $count }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group full-width" id="campaignCustomersField" @if ($audience !== 'selected') hidden @endif>
                            <label for="campaignCustomerSearch">Customers * <span class="cell-note" id="campaignSelectedCount"></span></label>
                            <input type="search" class="form-control" id="campaignCustomerSearch" placeholder="Filter by name, phone or city…">
                            <div class="campaign-customer-list" id="campaignCustomerList">
                                @forelse ($customers as $customer)
                                    <label data-search="{{ mb_strtolower($customer->full_name . ' ' . $customer->username . ' ' . $customer->phone . ' ' . $customer->city) }}">
                                        <input type="checkbox" name="customer_ids[]" value="{{ $customer->id }}" @checked(in_array($customer->id, $selectedCustomers, true))>
                                        <span>
                                            <strong>{{ $customer->full_name ?: $customer->username }}</strong>
                                            <small>{{ $customer->phone }}{{ $customer->city ? ' · ' . $customer->city : '' }}</small>
                                        </span>
                                    </label>
                                @empty
                                    <p class="cell-note">No customers are signed in to the app yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </section>

                {{-- 2. Company --}}
                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>2. Featured company</h2>
                            <p id="campaignCompanyHint">Optional for news. Customers can open the company page from the notification.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="company_id">Company <span id="campaignCompanyRequired" hidden>*</span></label>
                        <select class="select-control" id="company_id" name="company_id">
                            <option value="">No company</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected($companyId === (int) $company->id)>
                                    {{ $company->name }}{{ $company->city ? ' — ' . $company->city : '' }}
                                </option>
                            @endforeach
                        </select>
                        <span class="form-hint">Only active, verified companies are listed.</span>
                    </div>
                </section>

                {{-- 3. Message --}}
                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>3. Message</h2>
                            <p>Customers see the version in their app language. Arabic falls back to English when empty.</p>
                        </div>
                    </div>

                    <label class="toggle-card" for="formatHtml">
                        <span class="toggle-card-copy">
                            <strong>Formatted message (HTML)</strong>
                            <small>Bold, italic, lists and links inside the app. Allowed tags: b, strong, i, em, u, s, p, br, ul, ol, li, a, h3, h4, blockquote, small. Anything else is removed.</small>
                        </span>
                        <input type="hidden" name="format" value="text">
                        <input class="toggle-input" type="checkbox" id="formatHtml" name="format" value="html" @checked($format === 'html')>
                        <span class="toggle-switch" aria-hidden="true"></span>
                    </label>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="title_en">Title (English) *</label>
                            <input type="text" class="form-control" id="title_en" name="title_en" maxlength="180" required
                                value="{{ old('title_en', '20% off solar systems this week') }}">
                        </div>
                        <div class="form-group">
                            <label for="title_ar">Title (Arabic)</label>
                            <input type="text" class="form-control" id="title_ar" name="title_ar" maxlength="180" dir="rtl" lang="ar"
                                value="{{ old('title_ar', 'خصم 20٪ على أنظمة الطاقة الشمسية هذا الأسبوع') }}">
                        </div>

                        @foreach (['en' => ['Message (English) *', 'ltr', 'Write the message…'], 'ar' => ['Message (Arabic)', 'rtl', 'اكتب نص الرسالة…']] as $lang => [$label, $dir, $placeholder])
                            <div class="form-group">
                                <label for="message_{{ $lang }}">{{ $label }}</label>
                                <div class="campaign-toolbar" data-target="message_{{ $lang }}" role="toolbar" aria-label="Formatting">
                                    <button type="button" data-wrap="b" title="Bold"><b>B</b></button>
                                    <button type="button" data-wrap="i" title="Italic"><i>I</i></button>
                                    <button type="button" data-wrap="u" title="Underline"><u>U</u></button>
                                    <button type="button" data-list="ul" title="Bulleted list">• List</button>
                                    <button type="button" data-link title="Link">Link</button>
                                    <button type="button" data-wrap="p" title="Paragraph">¶</button>
                                </div>
                                <textarea class="form-control" id="message_{{ $lang }}" name="message_{{ $lang }}" rows="7" maxlength="5000"
                                    dir="{{ $dir }}" @if ($lang === 'ar') lang="ar" @else required @endif
                                    placeholder="{{ $placeholder }}">{{ old('message_' . $lang, $lang === 'ar' ? "استفيدوا من خصم 20٪ على أنظمة الطاقة الشمسية هذا الأسبوع.\n\nاكتشفوا الشركات الموثوقة وقارنوا الحلول المتاحة عبر SunXFinder.\n\nالعرض لفترة محدودة. يرجى التواصل مع الشركة لمعرفة التفاصيل." : "Save 20% on solar energy systems this week.\n\nDiscover trusted companies and compare available solutions on SunXFinder.\n\nThis offer is available for a limited time. Contact the company for details.") }}</textarea>
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- 4. Image --}}
                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>4. Image</h2>
                            <p>Optional. Shown in the push (Android and iOS) and at the top of the in-app card.</p>
                        </div>
                    </div>
                    <label class="slider-dropzone" for="image">
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                        <strong>Choose an image</strong>
                        <small>JPG, PNG or WebP, at least 300 × 150 px. Best at 1200 × 600 px (2:1).
                            Bigger images are resized here to 1200 px wide in high quality, so any photo fits the
                            server limit of {{ round($maxUploadKb / 1024, 1) }} MB.</small>
                    </label>
                    <div class="image-resize-info" id="campaignImageInfo" aria-live="polite" hidden></div>
                </section>

                <section class="panel">
                    <label class="toggle-card" for="is_sponsored">
                        <span class="toggle-card-copy">
                            <strong>Sponsored campaign</strong>
                            <small>Turn on for a paid push. Customers who switched off sponsored notifications are skipped.</small>
                        </span>
                        <input type="hidden" name="is_sponsored" value="0">
                        <input class="toggle-input" type="checkbox" id="is_sponsored" name="is_sponsored" value="1" @checked((bool) old('is_sponsored'))>
                        <span class="toggle-switch" aria-hidden="true"></span>
                    </label>
                </section>

                {{-- 5. Company offer: a package offer customers can accept (same builder as the company panel) --}}
                <section class="panel" id="campaignOfferPanel" @if ($type !== 'offer') hidden @endif>
                    <div class="panel-heading">
                        <div>
                            <h2>5. The offer *</h2>
                            <p>Customers see the offer card and can accept it in the app with their devices. Accepting creates the project for the company right away.</p>
                        </div>
                    </div>

                    <div class="campaign-choice-grid" role="radiogroup" aria-label="Offer source">
                        <label class="campaign-choice">
                            <input type="radio" name="offer_source" value="existing" @checked($offerSource === 'existing')>
                            <span><strong>Use one of the company's offers</strong><small>An open offer the company already made.</small></span>
                        </label>
                        <label class="campaign-choice">
                            <input type="radio" name="offer_source" value="new" @checked($offerSource === 'new')>
                            <span><strong>Build a new offer</strong><small>From the company's products. It is saved for the company.</small></span>
                        </label>
                    </div>

                    <div class="form-group" id="campaignExistingOffer">
                        <label for="package_offer_id">Offer *</label>
                        <select class="select-control" id="package_offer_id" name="package_offer_id">
                            <option value="">Choose an offer…</option>
                            @foreach ($packageOffers as $packageOffer)
                                <option value="{{ $packageOffer->id }}" data-company="{{ $packageOffer->company_id }}"
                                    @selected((int) old('package_offer_id') === (int) $packageOffer->id)>
                                    {{ $packageOffer->title_en }} · up to {{ $packageOffer->maxLoadLabel() }} kW · {{ number_format((float) $packageOffer->price) }} {{ $packageOffer->currency }}
                                </option>
                            @endforeach
                        </select>
                        <span class="form-hint" id="campaignNoOffers" hidden>This company has no open offers. Build a new one instead.</span>
                    </div>

                    <div id="campaignNewOffer">
                        @include('partials.package-offer-builder', ['base' => null, 'products' => $products])
                    </div>
                    <span class="form-hint">The image in section 4 is used for the offer too. Without one, the offer's own image is sent.</span>
                </section>

                {{-- 6. Button --}}
                <section class="panel">
                    <label class="toggle-card" for="has_link">
                        <span class="toggle-card-copy">
                            <strong>6. Add a button</strong>
                            <small>Opens a website or a screen in the app. Without it, a featured company still opens on tap.</small>
                        </span>
                        <input type="hidden" name="has_link" value="0">
                        <input class="toggle-input" type="checkbox" id="has_link" name="has_link" value="1" @checked($hasLink)>
                        <span class="toggle-switch" aria-hidden="true"></span>
                    </label>

                    <div class="form-grid" id="campaignLinkFields" @if (!$hasLink) hidden @endif>
                        <div class="form-group full-width">
                            <label for="link_url">Link *</label>
                            <input type="text" class="form-control" id="link_url" name="link_url" maxlength="500" inputmode="url"
                                value="{{ old('link_url') }}" placeholder="https://… or sunxfinder://company/company-slug">
                            <span class="form-hint">https:// opens in the browser; sunxfinder://company/{slug} opens a company in the app.</span>
                        </div>
                        <div class="form-group">
                            <label for="link_label_en">Button text (English)</label>
                            <input type="text" class="form-control" id="link_label_en" name="link_label_en" maxlength="60"
                                value="{{ old('link_label_en') }}" placeholder="View offer">
                        </div>
                        <div class="form-group">
                            <label for="link_label_ar">Button text (Arabic)</label>
                            <input type="text" class="form-control" id="link_label_ar" name="link_label_ar" maxlength="60" dir="rtl" lang="ar"
                                value="{{ old('link_label_ar') }}" placeholder="عرض العرض">
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('admin.notifications.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="campaignSubmit">Send campaign</button>
                    </div>
                </section>
            </div>

            {{-- Live preview --}}
            <aside class="slider-form-side">
                <section class="panel slider-preview-panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Preview</h2>
                            <p>The push on the lock screen, then the card inside the app.</p>
                        </div>
                    </div>

                    <div class="campaign-preview" id="campaignPreview">
                        <div class="campaign-push">
                            <span class="campaign-push-app">SunXFinder · now</span>
                            <div class="campaign-push-row">
                                <div>
                                    <strong id="pvPushTitle">Your title</strong>
                                    <p id="pvPushBody">Your message</p>
                                </div>
                                <img id="pvPushImage" alt="" hidden>
                            </div>
                        </div>

                        <article class="campaign-card">
                            <img class="campaign-card-image" id="pvImage" alt="" hidden>
                            <div class="campaign-card-body">
                                <span class="campaign-badge" id="pvBadge">News</span>
                                <h3 id="pvTitle">Your title</h3>
                                <div class="campaign-company" id="pvCompany" hidden>
                                    <img id="pvCompanyLogo" alt="" hidden>
                                    <span><strong id="pvCompanyName"></strong><small id="pvCompanyCity"></small></span>
                                </div>
                                <div class="campaign-offer" id="pvOffer" hidden>
                                    <span id="pvOfferTitle"></span>
                                    <div>
                                        <strong id="pvOfferPrice"></strong>
                                        <del id="pvOfferOld"></del>
                                        <em id="pvOfferDiscount" hidden></em>
                                    </div>
                                    <small id="pvOfferExpiry"></small>
                                </div>
                                <div class="campaign-message" id="pvMessage"></div>
                                <span class="campaign-cta" id="pvCta" hidden>View</span>
                            </div>
                        </article>
                    </div>

                    <div class="slider-preview-lang" role="group" aria-label="Preview language">
                        <button type="button" class="is-active" data-lang="en">English</button>
                        <button type="button" data-lang="ar">العربية</button>
                    </div>
                </section>
            </aside>
        </div>
    </form>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset_v('css/package-offer-builder.css') }}">
@endpush

@section('scripts')
    <script src="{{ asset_v('admin/js/image-resizer.js') }}"></script>
    @include('partials.package-offer-builder-script')
    <script>
        (function () {
            const $ = (id) => document.getElementById(id);
            const form = $('campaignForm');
            const companies = @json($companyJson);
            const maxBytes = {{ (int) $maxUploadKb }} * 1024;
            const labels = {
                en: { offer: 'Offer', new_company: 'New company', promotion: 'News', view: 'View', until: 'Valid until ', off: '% off' },
                ar: { offer: 'عرض', new_company: 'شركة جديدة', promotion: 'أخبار', view: 'عرض', until: 'صالح حتى ', off: '٪ خصم' },
            };
            let lang = 'en', imageUrl = null;

            // --- Audience ---
            const audience = $('audience_type');
            const syncAudience = () => {
                $('campaignCityField').hidden = audience.value !== 'city';
                $('audience_city').required = audience.value === 'city';
                $('campaignCustomersField').hidden = audience.value !== 'selected';
            };
            audience.addEventListener('change', syncAudience);

            const customerBoxes = () => Array.from(document.querySelectorAll('#campaignCustomerList input[type=checkbox]'));
            const syncSelected = () => {
                const count = customerBoxes().filter((box) => box.checked).length;
                $('campaignSelectedCount').textContent = count ? '(' + count + ' selected)' : '';
            };
            $('campaignCustomerList').addEventListener('change', syncSelected);
            $('campaignCustomerSearch').addEventListener('input', (event) => {
                const term = event.target.value.trim().toLowerCase();
                document.querySelectorAll('#campaignCustomerList label').forEach((row) => {
                    row.hidden = term !== '' && !row.dataset.search.includes(term);
                });
            });

            // --- Topic and company ---
            const typeValue = () => (form.querySelector('input[name=type]:checked') || {}).value || 'promotion';
            const syncType = () => {
                const needsCompany = typeValue() !== 'promotion';
                $('company_id').required = needsCompany;
                $('campaignCompanyRequired').hidden = !needsCompany;
                $('campaignCompanyHint').textContent = needsCompany
                    ? 'Required: the company this offer or introduction is about.'
                    : 'Optional for news. Customers can open the company page from the notification.';
            };
            form.querySelectorAll('input[name=type]').forEach((radio) => radio.addEventListener('change', () => { syncType(); render(); }));

            // --- Toggles ---
            const toggle = (checkboxId, fieldsId, requiredIds) => {
                const box = $(checkboxId);
                const sync = () => {
                    $(fieldsId).hidden = !box.checked;
                    requiredIds.forEach((id) => { $(id).required = box.checked; });
                };
                box.addEventListener('change', () => { sync(); render(); });
                sync();
            };
            toggle('has_link', 'campaignLinkFields', ['link_url']);

            // --- Company offer: one of the company's open offers, or a new one from the shared builder ---
            const packageOffers = @json($packageOfferJson);
            const builder = document.querySelector('#campaignNewOffer [data-package-builder]');
            let builderSummary = null;
            const offerSource = () => (form.querySelector('input[name=offer_source]:checked') || {}).value || 'existing';
            const syncOffer = () => {
                const isOffer = typeValue() === 'offer';
                const building = isOffer && offerSource() === 'new';
                const companyId = $('company_id').value;
                const select = $('package_offer_id');

                $('campaignOfferPanel').hidden = !isOffer;
                form.querySelectorAll('input[name=offer_source]').forEach((radio) => { radio.disabled = !isOffer; });

                // Only this company's open offers.
                let visible = 0;
                Array.from(select.options).forEach((option) => {
                    if (!option.value) return;
                    option.hidden = option.dataset.company !== companyId;
                    if (!option.hidden) visible++;
                });
                if (select.selectedOptions[0]?.hidden) select.value = '';
                $('campaignExistingOffer').hidden = !isOffer || building;
                select.disabled = !isOffer || building;
                select.required = isOffer && !building;
                $('campaignNoOffers').hidden = visible > 0 || !companyId;

                // The builder is posted only when building, with the company's products.
                $('campaignNewOffer').hidden = !building;
                window.PackageOfferBuilder.setCompany(builder, companyId);
                window.PackageOfferBuilder.setEnabled(builder, building);
            };
            builder.addEventListener('pkg:change', (event) => { builderSummary = event.detail; });
            form.querySelectorAll('input[name=offer_source]').forEach((radio) => radio.addEventListener('change', () => { syncOffer(); render(); }));
            form.querySelectorAll('input[name=type]').forEach((radio) => radio.addEventListener('change', () => { syncOffer(); render(); }));
            $('company_id').addEventListener('change', () => { syncOffer(); render(); });

            // The offer card shown in the preview, the same fields the server copies from the offer.
            const offerCard = () => {
                if (typeValue() !== 'offer') return null;
                if (offerSource() === 'new') {
                    if (!builderSummary) return null;
                    return {
                        title_en: builderSummary.titleEn, title_ar: builderSummary.titleAr,
                        price: builderSummary.total,
                        old_price: builderSummary.discount > 0 ? builderSummary.subtotal : null,
                        currency: builderSummary.currency, expires_at: builderSummary.expiresAt, image: null,
                    };
                }
                return packageOffers[$('package_offer_id').value] || null;
            };

            // --- Formatting toolbar ---
            const isHtml = () => $('formatHtml').checked;
            const syncFormat = () => {
                document.querySelectorAll('.campaign-toolbar').forEach((bar) => { bar.hidden = !isHtml(); });
            };
            $('formatHtml').addEventListener('change', () => { syncFormat(); render(); });

            const wrapSelection = (textarea, before, after) => {
                const start = textarea.selectionStart, end = textarea.selectionEnd;
                const selected = textarea.value.slice(start, end);
                textarea.setRangeText(before + selected + after, start, end, selected ? 'end' : 'start');
                if (!selected) textarea.selectionStart = textarea.selectionEnd = start + before.length;
                textarea.focus();
                render();
            };
            document.querySelectorAll('.campaign-toolbar').forEach((bar) => {
                const textarea = $(bar.dataset.target);
                bar.addEventListener('click', (event) => {
                    const button = event.target.closest('button');
                    if (!button) return;
                    if (button.dataset.wrap) {
                        wrapSelection(textarea, '<' + button.dataset.wrap + '>', '</' + button.dataset.wrap + '>');
                    } else if (button.dataset.list) {
                        const lines = textarea.value.slice(textarea.selectionStart, textarea.selectionEnd).split('\n').filter(Boolean);
                        const items = (lines.length ? lines : ['']).map((line) => '<li>' + line + '</li>').join('\n');
                        textarea.setRangeText('<ul>\n' + items + '\n</ul>', textarea.selectionStart, textarea.selectionEnd, 'end');
                        textarea.focus();
                        render();
                    } else if (button.hasAttribute('data-link')) {
                        const url = prompt('Link address (https://…)', 'https://');
                        if (url && /^(https?:\/\/|sunxfinder:\/\/|mailto:|tel:)/i.test(url)) {
                            wrapSelection(textarea, '<a href="' + url.replace(/"/g, '%22') + '">', '</a>');
                        }
                    }
                });
            });

            // Same allowlist as the server, so the preview matches what customers get.
            const allowed = ['P', 'BR', 'B', 'STRONG', 'I', 'EM', 'U', 'S', 'UL', 'OL', 'LI', 'A', 'H3', 'H4', 'BLOCKQUOTE', 'SMALL'];
            const sanitize = (html) => {
                const doc = new DOMParser().parseFromString('<div>' + html + '</div>', 'text/html');
                const walk = (node) => {
                    Array.from(node.childNodes).forEach((child) => {
                        if (child.nodeType === 8) return child.remove();
                        if (child.nodeType !== 1) return;
                        if (['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'EMBED', 'SVG', 'FORM'].includes(child.tagName)) return child.remove();
                        walk(child);
                        if (!allowed.includes(child.tagName)) return child.replaceWith(...child.childNodes);
                        const href = child.getAttribute('href') || '';
                        Array.from(child.attributes).forEach((attribute) => child.removeAttribute(attribute.name));
                        if (child.tagName === 'A') {
                            if (/^(https?:\/\/|mailto:|tel:|sunxfinder:\/\/)/i.test(href)) child.setAttribute('href', href);
                            else child.replaceWith(...child.childNodes);
                        }
                    });
                };
                const root = doc.body.firstChild;
                walk(root);
                return root.innerHTML;
            };
            const plain = (html) => {
                const div = document.createElement('div');
                div.innerHTML = sanitize(html.replace(/<(br|\/p|\/li|\/h[34])[^>]*>/gi, ' $&'));
                return div.textContent.replace(/\s+/g, ' ').trim();
            };

            // --- Image: resized in the browser to the 1200 px the server stores ---
            const submit = $('campaignSubmit');
            window.AdminImageResizer.attach({
                input: $('image'),
                info: $('campaignImageInfo'),
                submit: submit,
                maxBytes: maxBytes,
                targetWidth: 1200,
                ratio: 2,
                onReady: (file, url) => { imageUrl = url; render(); },
            });

            // --- Preview ---
            const pick = (en, ar) => (lang === 'ar' && $(ar).value.trim()) ? $(ar).value.trim() : $(en).value.trim();
            const money = (value) => Number(value).toLocaleString(lang === 'ar' ? 'ar-EG' : 'en-US', { maximumFractionDigits: 2 });
            const render = () => {
                const t = labels[lang];
                const title = pick('title_en', 'title_ar') || (lang === 'ar' ? 'العنوان' : 'Your title');
                const message = pick('message_en', 'message_ar');
                const html = isHtml() ? sanitize(message) : message.replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])).replace(/\n/g, '<br>');

                $('campaignPreview').dir = lang === 'ar' ? 'rtl' : 'ltr';
                $('pvPushTitle').textContent = title;
                $('pvPushBody').textContent = (isHtml() ? plain(message) : message) || (lang === 'ar' ? 'نص الرسالة' : 'Your message');
                $('pvTitle').textContent = title;
                $('pvMessage').innerHTML = html || '<span class="cell-note">' + (lang === 'ar' ? 'نص الرسالة' : 'Your message') + '</span>';
                $('pvBadge').textContent = t[typeValue()];
                $('pvBadge').dataset.type = typeValue();

                // The campaign image wins; otherwise the chosen offer's own image is sent.
                const card = offerCard();
                const shownImage = imageUrl || card?.image || null;
                ['pvImage', 'pvPushImage'].forEach((id) => { $(id).hidden = !shownImage; if (shownImage) $(id).src = shownImage; });

                const company = companies[$('company_id').value];
                $('pvCompany').hidden = !company;
                if (company) {
                    $('pvCompanyName').textContent = company.name;
                    $('pvCompanyCity').textContent = company.city || '';
                    $('pvCompanyLogo').hidden = !company.logo;
                    if (company.logo) $('pvCompanyLogo').src = company.logo;
                }

                $('pvOffer').hidden = !card;
                if (card) {
                    const price = Number(card.price), old = Number(card.old_price || 0);
                    const currency = ' ' + card.currency;
                    $('pvOfferTitle').textContent = (lang === 'ar' && card.title_ar) ? card.title_ar : (card.title_en || '');
                    $('pvOfferPrice').textContent = price >= 0 ? money(price) + currency : '';
                    $('pvOfferOld').textContent = old > price ? money(old) + currency : '';
                    $('pvOfferDiscount').hidden = !(old > price && price >= 0);
                    $('pvOfferDiscount').textContent = Math.round((old - price) / old * 100) + t.off;
                    const expiry = card.expires_at;
                    $('pvOfferExpiry').textContent = expiry ? t.until + new Date(expiry).toLocaleDateString(lang === 'ar' ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '';
                }

                const hasLink = $('has_link').checked;
                $('pvCta').hidden = !hasLink;
                $('pvCta').textContent = pick('link_label_en', 'link_label_ar') || t.view;
            };

            form.addEventListener('input', render);
            form.addEventListener('change', render);
            document.querySelectorAll('.slider-preview-lang button').forEach((button) => {
                button.addEventListener('click', () => {
                    lang = button.dataset.lang;
                    document.querySelectorAll('.slider-preview-lang button').forEach((b) => b.classList.toggle('is-active', b === button));
                    render();
                });
            });

            // --- Send ---
            form.addEventListener('submit', (event) => {
                if (audience.value === 'selected' && !customerBoxes().some((box) => box.checked)) {
                    event.preventDefault();
                    alert('Select at least one customer.');
                    return;
                }
                const who = audience.value === 'all' ? 'all {{ number_format($appCustomerCount) }} app customers'
                    : audience.value === 'city' ? 'customers in ' + $('audience_city').value
                    : customerBoxes().filter((box) => box.checked).length + ' selected customer(s)';
                if (typeValue() === 'offer' && offerSource() === 'new' && !builderSummary?.valid) {
                    event.preventDefault();
                    alert('The new offer must total at least 1,000.');
                    return;
                }
                if (!confirm('Send this campaign to ' + who + '? This cannot be undone.')) {
                    event.preventDefault();
                    return;
                }
                submit.disabled = true;
                submit.textContent = 'Sending…';
            });

            syncAudience();
            syncSelected();
            syncType();
            syncFormat();
            syncOffer();
            render();
        })();
    </script>
@endsection

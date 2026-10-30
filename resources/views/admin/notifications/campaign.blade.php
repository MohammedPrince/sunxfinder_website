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
        $hasOffer = (bool) old('has_offer', 0);
        $hasLink = (bool) old('has_link', 0);
        $companyId = (int) old('company_id', $presetCompanyId);
        $companyJson = $companies->mapWithKeys(fn($c) => [$c->id => [
            'name' => $c->name,
            'city' => $c->city,
            'logo' => app(\App\Helpers\Helper::class)->publicUrl($c->logo_path),
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
                            'offer' => ['Company offer', 'A discount or package from one company.'],
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
                                <option value="city" @selected($audience === 'city') @disabled($cities->isEmpty())>Customers in one city</option>
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
                                value="{{ old('title_en') }}" placeholder="e.g. 20% off solar systems this week">
                        </div>
                        <div class="form-group">
                            <label for="title_ar">Title (Arabic)</label>
                            <input type="text" class="form-control" id="title_ar" name="title_ar" maxlength="180" dir="rtl" lang="ar"
                                value="{{ old('title_ar') }}" placeholder="مثال: خصم 20٪ على أنظمة الطاقة الشمسية هذا الأسبوع">
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
                                    placeholder="{{ $placeholder }}">{{ old('message_' . $lang) }}</textarea>
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

                {{-- 5. Offer --}}
                <section class="panel">
                    <label class="toggle-card" for="has_offer">
                        <span class="toggle-card-copy">
                            <strong>5. Include an offer</strong>
                            <small>Shows a price card with the discount and an expiry date.</small>
                        </span>
                        <input type="hidden" name="has_offer" value="0">
                        <input class="toggle-input" type="checkbox" id="has_offer" name="has_offer" value="1" @checked($hasOffer)>
                        <span class="toggle-switch" aria-hidden="true"></span>
                    </label>

                    <div class="form-grid" id="campaignOfferFields" @if (!$hasOffer) hidden @endif>
                        <div class="form-group">
                            <label for="offer_title_en">Offer title (English) *</label>
                            <input type="text" class="form-control" id="offer_title_en" name="offer_title_en" maxlength="150"
                                value="{{ old('offer_title_en') }}" placeholder="e.g. 5 kW system, installed">
                        </div>
                        <div class="form-group">
                            <label for="offer_title_ar">Offer title (Arabic)</label>
                            <input type="text" class="form-control" id="offer_title_ar" name="offer_title_ar" maxlength="150" dir="rtl" lang="ar"
                                value="{{ old('offer_title_ar') }}" placeholder="مثال: نظام 5 كيلوواط مع التركيب">
                        </div>
                        <div class="form-group">
                            <label for="offer_price">Offer price</label>
                            <input type="number" class="form-control" id="offer_price" name="offer_price" min="0" step="0.01"
                                value="{{ old('offer_price') }}" placeholder="e.g. 2400000">
                        </div>
                        <div class="form-group">
                            <label for="offer_old_price">Old price <small class="cell-note">(shows the discount)</small></label>
                            <input type="number" class="form-control" id="offer_old_price" name="offer_old_price" min="0" step="0.01"
                                value="{{ old('offer_old_price') }}" placeholder="e.g. 3000000">
                        </div>
                        <div class="form-group">
                            <label for="offer_currency">Currency</label>
                            <select class="select-control" id="offer_currency" name="offer_currency">
                                @foreach (['SDG', 'USD'] as $currency)
                                    <option value="{{ $currency }}" @selected(old('offer_currency', 'SDG') === $currency)>{{ $currency }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="offer_expires_at">Valid until</label>
                            <input type="datetime-local" class="form-control" id="offer_expires_at" name="offer_expires_at"
                                value="{{ old('offer_expires_at') }}">
                        </div>
                    </div>
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

@section('scripts')
    <script src="{{ asset_v('admin/js/image-resizer.js') }}"></script>
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
            toggle('has_offer', 'campaignOfferFields', ['offer_title_en']);
            toggle('has_link', 'campaignLinkFields', ['link_url']);

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

                ['pvImage', 'pvPushImage'].forEach((id) => { $(id).hidden = !imageUrl; if (imageUrl) $(id).src = imageUrl; });

                const company = companies[$('company_id').value];
                $('pvCompany').hidden = !company;
                if (company) {
                    $('pvCompanyName').textContent = company.name;
                    $('pvCompanyCity').textContent = company.city || '';
                    $('pvCompanyLogo').hidden = !company.logo;
                    if (company.logo) $('pvCompanyLogo').src = company.logo;
                }

                const hasOffer = $('has_offer').checked;
                $('pvOffer').hidden = !hasOffer;
                if (hasOffer) {
                    const price = parseFloat($('offer_price').value), old = parseFloat($('offer_old_price').value);
                    const currency = ' ' + $('offer_currency').value;
                    $('pvOfferTitle').textContent = pick('offer_title_en', 'offer_title_ar');
                    $('pvOfferPrice').textContent = price >= 0 ? money(price) + currency : '';
                    $('pvOfferOld').textContent = old > price ? money(old) + currency : '';
                    $('pvOfferDiscount').hidden = !(old > price && price >= 0);
                    $('pvOfferDiscount').textContent = Math.round((old - price) / old * 100) + t.off;
                    const expiry = $('offer_expires_at').value;
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
            render();
        })();
    </script>
@endsection

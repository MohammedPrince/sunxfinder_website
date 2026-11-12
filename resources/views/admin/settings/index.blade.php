@extends('admin.layouts.app')
@section('title', 'Settings')
@section('breadcrumb', 'Settings')
@section('eyebrow', 'SYSTEM')
@section('page-title', 'Settings')
@section('page-description', 'App rules, app Home sections, website maintenance mode and which API the app uses.')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
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

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        {{-- App online/offline for all customers. The app reads this from /main-data. --}}
        @php
            $appOffline = $settings['app_offline'];
        @endphp
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>
                        Mobile app status
                        <span class="pill {{ $appOffline['enabled'] ? 'pill-red' : 'pill-green' }}">
                            {{ $appOffline['enabled'] ? 'Offline' : 'Online' }}
                        </span>
                    </h2>
                    <p>
                        Take the app offline for all customers, for example during an update. The app shows your message
                        instead of its normal screens. The website and the admin and company panels keep working.
                    </p>
                </div>
            </div>

            <div class="panel-body">
                <label class="toggle-card" for="app_offline_enabled">
                    <span class="toggle-card-copy">
                        <strong>Set the app offline</strong>
                        <small>Each customer's app goes offline the next time it loads its data.</small>
                    </span>
                    <input type="hidden" name="app_offline_enabled" value="0">
                    <input class="toggle-input" type="checkbox" id="app_offline_enabled" name="app_offline_enabled"
                        value="1" @checked(old('app_offline_enabled', $appOffline['enabled']))>
                    <span class="toggle-switch" aria-hidden="true"></span>
                </label>

                <div class="form-grid" style="margin-top:18px">
                    <div class="form-group">
                        <label for="app_offline_message_en">Offline message (English)</label>
                        <textarea class="form-control" id="app_offline_message_en" name="app_offline_message_en" rows="3"
                            maxlength="1000" placeholder="e.g. SunXFinder is being updated. Please try again in a few hours.">{{ old('app_offline_message_en', $appOffline['message_en']) }}</textarea>
                        <small class="form-hint">Required while the app is offline.</small>
                        @error('app_offline_message_en')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="app_offline_message_ar">Offline message (Arabic)</label>
                        <textarea class="form-control" id="app_offline_message_ar" name="app_offline_message_ar" rows="3"
                            maxlength="1000" dir="rtl" lang="ar" placeholder="مثال: يجري تحديث SunXFinder. يرجى المحاولة بعد بضع ساعات.">{{ old('app_offline_message_ar', $appOffline['message_ar']) }}</textarea>
                        <small class="form-hint">Required while the app is offline.</small>
                        @error('app_offline_message_ar')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </div>
        </section>

        {{-- Website design: new or classic --}}
        @php $siteDesign = old('site_design', $settings['site_design']); @endphp
        <section class="panel" id="site-design">
            <div class="panel-heading">
                <div>
                    <h2>
                        Website design
                        <span class="pill pill-green">{{ $settings['site_design'] === 'classic' ? 'Classic design is live' : 'New design is live' }}</span>
                    </h2>
                    <p>Choose the look of the public website: home page, company profiles, sign-up, terms and the maintenance page.
                        Content and links stay the same. Visitors see the change within a minute.</p>
                </div>
            </div>

            <div class="panel-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="site_design">Design</label>
                        <select class="select-control" id="site_design" name="site_design" required>
                            <option value="new" @selected($siteDesign === 'new')>New design (navy header, phone preview)</option>
                            <option value="classic" @selected($siteDesign === 'classic')>Classic design (the earlier light look)</option>
                        </select>
                        <small class="form-hint">After saving, open <a href="{{ url('/') }}" target="_blank" rel="noopener">the website</a> to check it.</small>
                        @error('site_design')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </section>

        <section class="panel">
            @php
                $companyAds = $settings['company_ads'];
                $adsAudience = old('company_ads_audience', $companyAds['audience']);
                $adsCompanyIds = array_map('intval', (array) old('company_ads_company_ids', $companyAds['company_ids']));
            @endphp

            <div class="panel-heading">
                <div>
                    <h2>Company Advertising</h2>
                    <p>Choose which companies see Advertising in their portal. Admin Ad Sales always stays available.</p>
                </div>
            </div>

            <div class="panel-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="company_ads_audience">Show Advertising to</label>
                        <select class="select-control" id="company_ads_audience" name="company_ads_audience" required>
                            <option value="all" @selected($adsAudience === 'all')>All companies</option>
                            <option value="selected" @selected($adsAudience === 'selected')>Selected companies only</option>
                        </select>
                        @error('company_ads_audience')<small class="field-error">{{ $message }}</small>@enderror
                    </div>

                    <div class="form-group" id="companyAdsPicker" @if ($adsAudience !== 'selected') hidden @endif>
                        <label for="company_ads_company_ids">Companies</label>
                        <select class="select-control" id="company_ads_company_ids" name="company_ads_company_ids[]" multiple size="8">
                            @foreach ($settings['notification_access_companies'] as $company)
                                <option value="{{ $company->id }}" @selected(in_array((int) $company->id, $adsCompanyIds, true))>
                                    {{ $company->name }}{{ $company->city ? ' · ' . $company->city : '' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-hint">Hold Ctrl (Windows) or Command (Mac) to select several. With none selected, no company sees Advertising.</small>
                        @error('company_ads_company_ids')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </section>

        <script>
            document.getElementById('company_ads_audience').addEventListener('change', event => {
                document.getElementById('companyAdsPicker').hidden = event.target.value !== 'selected';
            });
        </script>

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Quote requests</h2>
                    <p>Limits on what customers can send from the app, and which customers companies can notify.</p>
                </div>
            </div>

            <div class="panel-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="max_open_quotes">Open requests per customer <b style="color: red">*</b></label>
                        <input class="form-control" type="number" id="max_open_quotes" name="max_open_quotes"
                            min="1" max="50" required
                            value="{{ old('max_open_quotes', $settings['max_open_quotes']) }}">
                        <small class="form-hint">
                            A request is open until the customer accepts an offer. At the limit, the app blocks sending
                            new requests and the server rejects them.
                        </small>
                        @error('max_open_quotes')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                {{-- Higher limit for one customer. These fields belong to #customerQuoteLimitForm
                     (below the main form: forms cannot be nested), via the form="" attribute. --}}
                @php
                    $limitCustomers = $settings['quote_limit_customers'];
                    $defaultLimit = $settings['max_open_quotes'];
                    $maxPerCustomer = \App\Models\Setting::MAX_OPEN_QUOTES_PER_CUSTOMER;
                    $raisedCustomers = $limitCustomers->filter(fn($c) => $c->max_open_quotes !== null);
                    $customerLabel = fn($c) => ($c->full_name ?: $c->username) . ' · ' . $c->phone;
                @endphp

                <div style="margin-top:22px;padding-top:18px;border-top:1px solid #e6edf5">
                    <h3 style="margin:0 0 4px;font-size:16px">Limit for a specific customer</h3>
                    <p class="form-hint" style="margin:0 0 14px">
                        Everyone gets the default above ({{ $defaultLimit }}). You can set a customer's own limit from 1 to
                        {{ $maxPerCustomer }} open requests. Only registered customers with at least one completed
                        project are listed.
                    </p>

                    @if ($limitCustomers->isEmpty())
                        <p class="form-hint">No customer has a completed project yet.</p>
                    @else
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="limit_customer_id">Customer</label>
                                <select class="form-control" id="limit_customer_id" name="customer_id"
                                    form="customerQuoteLimitForm" required>
                                    <option value="">Choose a customer…</option>
                                    @foreach ($limitCustomers as $customer)
                                        <option value="{{ $customer->id }}">
                                            {{ $customerLabel($customer) }}
                                            ({{ $customer->completed_projects }} completed{{ $customer->max_open_quotes !== null ? ', now ' . $customer->max_open_quotes : '' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="limit_value">Open requests allowed</label>
                                <select class="form-control" id="limit_value" name="limit" form="customerQuoteLimitForm" required>
                                    @for ($n = 1; $n <= $maxPerCustomer; $n++)
                                        <option value="{{ $n }}" @selected($n === min($defaultLimit + 1, $maxPerCustomer))>
                                            {{ $n }} {{ $n === 1 ? 'request' : 'requests' }}{{ $n === $defaultLimit ? ' (default)' : '' }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <p></p>
                        <button type="submit" class="btn btn-primary" form="customerQuoteLimitForm">Save customer limit</button>
                    @endif

                    @if ($raisedCustomers->isNotEmpty())
                        <div class="table-responsive" style="margin-top:18px">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Completed projects</th>
                                        <th>Open requests allowed</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($raisedCustomers as $customer)
                                        <tr>
                                            <td><strong>{{ $customerLabel($customer) }}</strong></td>
                                            <td>{{ $customer->completed_projects }}</td>
                                            <td><span class="pill pill-green">{{ $customer->max_open_quotes }}</span></td>
                                            <td>
                                                <button type="submit" class="btn btn-light" style="margin-top:8px" form="customerQuoteLimitResetForm"
                                                    name="customer_id" value="{{ $customer->id }}"
                                                    onclick="return confirm('Set this customer back to the default limit of {{ $defaultLimit }}?');">
                                                    Back to default
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Open all customers for one company. These fields belong to #companyNotificationAccessForm
                     (below the main form: forms cannot be nested), via the form="" attribute. --}}
                @php
                    $accessCompanies = $settings['notification_access_companies'];
                    $openCompanies = $accessCompanies->where('can_notify_all_customers', true);
                    $closedCompanies = $accessCompanies->where('can_notify_all_customers', false);
                @endphp

                <div style="margin-top:22px;padding-top:18px;border-top:1px solid #e6edf5">
                    <h3 style="margin:0 0 4px;font-size:16px">Company notifications</h3>
                    <p class="form-hint" style="margin:0 0 14px">
                        By default a company can notify only its own customers: those who accepted its offer. Open all customers for a specific company to let it notify every active customer with a
                        registered device. It still sees phone numbers and emails only for its own customers.
                        Set a monthly limit for each opened company; leave it empty for no limit. Only sends that reach
                        beyond its own customers count, and they skip customers who switched off sponsored notifications.
                    </p>

                    @if ($closedCompanies->isNotEmpty())
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="notification_company_id">Company</label>
                                <select class="form-control" id="notification_company_id" name="company_id"
                                    form="companyNotificationAccessForm" required>
                                    <option value="">Choose a company…</option>
                                    @foreach ($closedCompanies as $company)
                                        <option value="{{ $company->id }}">
                                            {{ $company->name }}{{ $company->city ? ' · ' . $company->city : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <p></p>
                        <button type="submit" class="btn btn-primary" form="companyNotificationAccessForm"
                            onclick="return confirm('Let this company send notifications to all customers?');">
                            Open all customers
                        </button>
                    @endif

                    @if ($openCompanies->isNotEmpty())
                        <div class="table-responsive" style="margin-top:18px">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Company</th>
                                        <th>City</th>
                                        <th>Can notify</th>
                                        <th>This month</th>
                                        <th>Monthly limit</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($openCompanies as $company)
                                        <tr>
                                            <td><strong>{{ $company->name }}</strong></td>
                                            <td>{{ $company->city }}</td>
                                            <td><span class="pill pill-green">All customers</span></td>
                                            <td>{{ number_format($company->allCustomerSendsThisMonth()) }}</td>
                                            <td style="min-width:190px">
                                                <div style="display:flex;gap:6px">
                                                    <input class="form-control" type="number" min="0" max="100000" name="monthly_limit"
                                                        form="companyLimitForm{{ $company->id }}" value="{{ $company->all_customers_monthly_limit }}"
                                                        placeholder="No limit" aria-label="Monthly limit for {{ $company->name }}">
                                                    <button type="submit" class="btn btn-light" form="companyLimitForm{{ $company->id }}">Save</button>
                                                </div>
                                            </td>
                                            <td>
                                                <button type="submit" class="btn btn-light" style="margin-top:8px" form="companyNotificationResetForm"
                                                    name="company_id" value="{{ $company->id }}"
                                                    onclick="return confirm('Limit this company to its own customers again?');">
                                                    Back to default
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

            </div>
        </section>

        {{-- Website maintenance mode --}}
        @php
            $maintenance = $settings['maintenance'];
            $maintenanceOn = (bool) old('maintenance_enabled', $maintenance['enabled']);
            $until = old('maintenance_until', $maintenance['until'] ? \Illuminate\Support\Carbon::parse($maintenance['until'])->format('Y-m-d\TH:i') : '');
        @endphp
        <section class="panel {{ $maintenance['enabled'] ? 'maintenance-on' : '' }}">
            <div class="panel-heading">
                <div>
                    <h2>
                        Website maintenance mode
                        <span class="pill {{ $maintenance['enabled'] ? 'pill-red' : 'pill-green' }}">
                            {{ $maintenance['enabled'] ? 'ON: website closed' : 'OFF: website live' }}
                        </span>
                    </h2>
                    <p>
                        Visitors see a maintenance page instead of the website (HTTP 503). You still see the website
                        while signed in as admin. The admin and company panels, the mobile app and Terms/Privacy keep
                        working.
                    </p>
                </div>
            </div>

            <div class="panel-body">
                <label class="toggle-card" for="maintenance_enabled">
                    <span class="toggle-card-copy">
                        <strong>Show the maintenance page</strong>
                        <small>Turn on before big updates; turn off when you are done.</small>
                    </span>
                    <input type="hidden" name="maintenance_enabled" value="0">
                    <input class="toggle-input" type="checkbox" id="maintenance_enabled" name="maintenance_enabled"
                        value="1" @checked($maintenanceOn)>
                    <span class="toggle-switch" aria-hidden="true"></span>
                </label>

                <div class="form-grid" style="margin-top:18px">
                    <div class="form-group">
                        <label for="maintenance_title_en">Title (English)</label>
                        <input class="form-control" type="text" id="maintenance_title_en" name="maintenance_title_en"
                            maxlength="150" placeholder="e.g. We will be back soon"
                            value="{{ old('maintenance_title_en', $maintenance['title_en']) }}">
                        @error('maintenance_title_en')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="maintenance_title_ar">Title (Arabic)</label>
                        <input class="form-control" type="text" id="maintenance_title_ar" name="maintenance_title_ar"
                            maxlength="150" dir="rtl" lang="ar" placeholder="مثال: الموقع تحت الصيانة"
                            value="{{ old('maintenance_title_ar', $maintenance['title_ar']) }}">
                        @error('maintenance_title_ar')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="maintenance_message_en">Message (English)</label>
                        <textarea class="form-control" id="maintenance_message_en" name="maintenance_message_en" rows="4"
                            maxlength="2000" placeholder="e.g. We are upgrading SunXFinder to serve you better. Please check back in a few hours.">{{ old('maintenance_message_en', $maintenance['message_en']) }}</textarea>
                        @error('maintenance_message_en')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="maintenance_message_ar">Message (Arabic)</label>
                        <textarea class="form-control" id="maintenance_message_ar" name="maintenance_message_ar" rows="4"
                            maxlength="2000" dir="rtl" lang="ar" placeholder="مثال: نعمل على تحديث SunXFinder لخدمتك بشكل أفضل. يرجى العودة بعد بضع ساعات.">{{ old('maintenance_message_ar', $maintenance['message_ar']) }}</textarea>
                        @error('maintenance_message_ar')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="maintenance_until">Expected back (optional)</label>
                        <input class="form-control" type="datetime-local" id="maintenance_until" name="maintenance_until"
                            value="{{ $until }}">
                        <small class="form-hint">Shown on the maintenance page while it is in the future.</small>
                        @error('maintenance_until')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-light">View website ↗</a>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </div>
        </section>
    </form>

    {{-- App Home sections. The app reads these from /main-data and GET /api/v1/customer/home-sections. --}}
    @php
        $liveSlides = $settings['live_slides_by_placement'];
    @endphp
    <section class="panel" id="app-home" style="margin-top:24px">
        <div class="panel-heading">
            <div>
                <h2>App Home</h2>
                <p>
                    Choose what the app Home screen shows. Changes reach the app within five minutes, or right away
                    when the customer pulls to refresh. Choose where each banner shows in
                    <a href="{{ route('admin.app-sliders.index') }}">App Slider</a> (Placement).
                </p>
            </div>
        </div>

        <div class="panel-body" style="display:grid;gap:16px">
            @foreach ($settings['home_sections'] as $section)
                @php
                    $isOld = old('key') === $section->key;
                    $isVisible = (bool) ($isOld ? old('is_visible') : $section->is_visible);
                    $showCompanies = (bool) ($isOld ? old('show_companies') : $section->show_companies);
                    $featuredSlides = (int) ($liveSlides['home_featured'] ?? 0);
                @endphp
                <form method="POST" action="{{ route('admin.settings.home-sections.update', $section->key) }}"
                    style="border:1px solid #e6edf5;border-radius:14px;padding:16px">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="key" value="{{ $section->key }}">

                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:12px">
                        <strong style="font-size:16px">{{ \App\Models\HomeSection::SECTIONS[$section->key] ?? $section->key }}</strong>
                        <span class="pill {{ $section->is_visible ? 'pill-green' : 'pill-red' }}">
                            {{ $section->is_visible ? 'Shown' : 'Hidden' }}
                        </span>
                    </div>

                    <label class="toggle-card" for="home_{{ $section->key }}_visible">
                        <span class="toggle-card-copy">
                            <strong>Show this section</strong>
                            <small>Off hides the whole section, heading included.</small>
                        </span>
                        <input type="hidden" name="is_visible" value="0">
                        <input class="toggle-input" type="checkbox" id="home_{{ $section->key }}_visible" name="is_visible"
                            value="1" @checked($isVisible)>
                        <span class="toggle-switch" aria-hidden="true"></span>
                    </label>

                    <label class="toggle-card" for="home_{{ $section->key }}_companies" style="margin-top:12px">
                        <span class="toggle-card-copy">
                            <strong>Show companies in this section</strong>
                            <small>
                                Off shows only the banners placed in this section
                                ({{ $featuredSlides }} live {{ $featuredSlides === 1 ? 'slide' : 'slides' }} now).
                                With neither companies nor banners, the app hides the section.
                            </small>
                        </span>
                        <input type="hidden" name="show_companies" value="0">
                        <input class="toggle-input" type="checkbox" id="home_{{ $section->key }}_companies" name="show_companies"
                            value="1" @checked($showCompanies)>
                        <span class="toggle-switch" aria-hidden="true"></span>
                    </label>

                    <div class="form-grid" style="margin-top:18px">
                        <div class="form-group">
                            <label for="home_{{ $section->key }}_title_en">Title (English)</label>
                            <input class="form-control" type="text" id="home_{{ $section->key }}_title_en" name="title_en"
                                maxlength="60" placeholder="Leave empty for the app's own title"
                                value="{{ $isOld ? old('title_en') : $section->title_en }}">
                            @if ($isOld)
                                @error('title_en')<small class="field-error">{{ $message }}</small>@enderror
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="home_{{ $section->key }}_title_ar">Title (Arabic)</label>
                            <input class="form-control" type="text" id="home_{{ $section->key }}_title_ar" name="title_ar"
                                maxlength="60" dir="rtl" lang="ar" placeholder="اتركه فارغاً لاستخدام عنوان التطبيق"
                                value="{{ $isOld ? old('title_ar') : $section->title_ar }}">
                            @if ($isOld)
                                @error('title_ar')<small class="field-error">{{ $message }}</small>@enderror
                            @endif
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top:12px">Save {{ \App\Models\HomeSection::SECTIONS[$section->key] ?? $section->key }}</button>
                </form>
            @endforeach
        </div>
    </section>

    {{-- API selector + accessibility. The app reads this from GET /api/v1/api-config. --}}
    @php
        $apiEndpoints = $settings['api_endpoints'];
        $activeApi = $apiEndpoints->firstWhere('is_active', true);
    @endphp
    <section class="panel" id="api-endpoints" style="margin-top:24px">
        <div class="panel-heading">
            <div>
                <h2>
                    API selector &amp; accessibility
                    @if ($activeApi)
                        <span class="pill {{ $activeApi->is_online ? 'pill-green' : 'pill-red' }}">
                            App uses {{ $activeApi->name }} · {{ $activeApi->is_online ? 'Online' : 'Offline' }}
                        </span>
                    @endif
                </h2>
                <p>
                    The mobile app asks <code>{{ url('/api/v1/api-config') }}</code> on start which API to call.
                    Exactly one API is active at a time. Turn an API offline to stop the app using it; while the active
                    API is offline, the app shows its offline message instead.
                </p>
            </div>
        </div>

        <div class="panel-body" style="display:grid;gap:16px">
            @forelse ($apiEndpoints as $endpoint)
                <div style="border:1px solid {{ $endpoint->is_active ? '#fc5202' : '#e6edf5' }};border-radius:14px;padding:16px">
                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:12px">
                        <strong style="font-size:16px">{{ $endpoint->name }}</strong>
                        @if ($endpoint->is_active)
                            <span class="pill pill-blue">Active: the app uses this API</span>
                        @endif
                        <span class="pill {{ $endpoint->is_online ? 'pill-green' : 'pill-red' }}">
                            {{ $endpoint->is_online ? 'Online' : 'Offline' }}
                        </span>
                    </div>

                    <form method="POST" action="{{ route('admin.settings.api-endpoints.update', $endpoint->id) }}">
                        @csrf
                        @method('PUT')
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="api_name_{{ $endpoint->id }}">Name</label>
                                <input class="form-control" type="text" id="api_name_{{ $endpoint->id }}" name="name"
                                    maxlength="30" required value="{{ $endpoint->name }}">
                            </div>
                            <div class="form-group">
                                <label for="api_url_{{ $endpoint->id }}">Base URL</label>
                                <input class="form-control" type="url" id="api_url_{{ $endpoint->id }}" name="base_url"
                                    maxlength="191" required value="{{ $endpoint->base_url }}" dir="ltr">
                            </div>
                        </div>

                        <label class="toggle-card" for="api_online_{{ $endpoint->id }}" style="margin-top:12px">
                            <span class="toggle-card-copy">
                                <strong>Online</strong>
                                <small>Off = the app treats this API as unavailable.</small>
                            </span>
                            <input type="hidden" name="is_online" value="0">
                            <input class="toggle-input" type="checkbox" id="api_online_{{ $endpoint->id }}" name="is_online"
                                value="1" @checked($endpoint->is_online)>
                            <span class="toggle-switch" aria-hidden="true"></span>
                        </label>

                        <details style="margin-top:12px" @if (!$endpoint->is_online) open @endif>
                            <summary style="cursor:pointer">Offline message (optional)</summary>
                            <div class="form-grid" style="margin-top:10px">
                                <div class="form-group">
                                    <label for="api_off_en_{{ $endpoint->id }}">English</label>
                                    <textarea class="form-control" id="api_off_en_{{ $endpoint->id }}" name="offline_message_en"
                                        rows="2" maxlength="500" placeholder="Default: SunXFinder is temporarily unavailable for maintenance. Please try again later.">{{ $endpoint->offline_message_en }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label for="api_off_ar_{{ $endpoint->id }}">Arabic</label>
                                    <textarea class="form-control" id="api_off_ar_{{ $endpoint->id }}" name="offline_message_ar"
                                        rows="2" maxlength="500" dir="rtl" lang="ar" placeholder="الافتراضي: تطبيق SunXFinder غير متاح مؤقتاً للصيانة. يرجى المحاولة لاحقاً.">{{ $endpoint->offline_message_ar }}</textarea>
                                </div>
                            </div>
                        </details>

                        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:14px">
                            <button type="submit" class="btn btn-primary">Save {{ $endpoint->name }}</button>
                            @unless ($endpoint->is_active)
                                <button type="submit" class="btn btn-light" form="apiActivate{{ $endpoint->id }}">Make active</button>
                                <button type="submit" class="btn btn-light" form="apiDelete{{ $endpoint->id }}">Delete</button>
                            @endunless
                        </div>
                    </form>

                    @unless ($endpoint->is_active)
                        <form id="apiActivate{{ $endpoint->id }}" method="POST"
                            action="{{ route('admin.settings.api-endpoints.activate', $endpoint->id) }}"
                            onsubmit="return confirm('Switch the app to {{ $endpoint->name }} ({{ $endpoint->base_url }})? Apps pick this up on their next start.');">
                            @csrf
                            @method('PATCH')
                        </form>
                        <form id="apiDelete{{ $endpoint->id }}" method="POST"
                            action="{{ route('admin.settings.api-endpoints.destroy', $endpoint->id) }}"
                            onsubmit="return confirm('Delete the {{ $endpoint->name }} API?');">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endunless
                </div>
            @empty
                <p class="form-hint">No API yet. The first one you add becomes the active API.</p>
            @endforelse

            <details @if ($apiEndpoints->isEmpty() || old('base_url')) open @endif>
                <summary style="cursor:pointer;font-weight:700">+ Add API</summary>
                <form method="POST" action="{{ route('admin.settings.api-endpoints.store') }}" style="margin-top:12px">
                    @csrf
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="api_new_name">Name</label>
                            <input class="form-control" type="text" id="api_new_name" name="name" maxlength="30" required
                                placeholder="e.g. STG" value="{{ old('name') }}">
                        </div>
                        <div class="form-group">
                            <label for="api_new_url">Base URL</label>
                            <input class="form-control" type="url" id="api_new_url" name="base_url" maxlength="191" required
                                placeholder="https://example.com/api/v1/customer" value="{{ old('base_url') }}" dir="ltr">
                        </div>
                    </div>
                    <input type="hidden" name="is_online" value="1">
                    <button type="submit" class="btn btn-primary" style="margin-top:12px">Add API</button>
                </form>
            </details>
        </div>
    </section>

    {{-- Targets of the per-customer limit fields in the Quote requests panel --}}
    <form id="customerQuoteLimitForm" method="POST" action="{{ route('admin.settings.customer-quote-limit') }}">
        @csrf
    </form>
    <form id="customerQuoteLimitResetForm" method="POST" action="{{ route('admin.settings.customer-quote-limit') }}">
        @csrf
    </form>

    {{-- Targets of the company notification fields in the Quote requests panel --}}
    <form id="companyNotificationAccessForm" method="POST" action="{{ route('admin.settings.company-notification-access') }}">
        @csrf
        <input type="hidden" name="can_notify_all" value="1">
    </form>
    @foreach ($settings['notification_access_companies']->where('can_notify_all_customers', true) as $company)
        <form id="companyLimitForm{{ $company->id }}" method="POST" action="{{ route('admin.settings.company-notification-limit') }}">
            @csrf
            <input type="hidden" name="company_id" value="{{ $company->id }}">
        </form>
    @endforeach
    <form id="companyNotificationResetForm" method="POST" action="{{ route('admin.settings.company-notification-access') }}">
        @csrf
        <input type="hidden" name="can_notify_all" value="0">
    </form>
@endsection

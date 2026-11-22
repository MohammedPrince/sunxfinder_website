<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\PackageOffer;
use App\Models\Product;
use App\Models\RatingValue;
use App\Models\Setting;
use App\Services\Company\CompanyService;
use App\Support\InvoicePdf;
use App\Support\OfferTerms;
use App\Support\ReportPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;


class MainController extends Controller
{
    protected $companyService;
    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    public function dashboard()
    {
        $result = $this->companyService->getDashboardData();

        return view('company.dashboard', $result);
    }

    //Reports Start

    public function reports(Request $request)
    {
        $report = $this->companyService->getCompanyReportsData($this->validatedReportFilters($request));

        return view('company.reports.index', array_merge($report, [
            'presets' => ReportPeriod::PRESETS,
            'stalledDays' => ReportPeriod::STALLED_DAYS,
        ]));
    }

    public function exportReport(Request $request, string $section)
    {
        abort_unless(in_array($section, ['offers', 'projects', 'customers', 'package_offers'], true), 404);

        $export = $this->companyService->getCompanyReportExport($section, $this->validatedReportFilters($request));

        return response()->streamDownload(function () use ($export) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM so Excel shows Arabic names correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $export['headers']);

            foreach ($export['rows'] as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $export['filename'], ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validatedReportFilters(Request $request): array
    {
        return $request->validate([
            'preset' => ['nullable', Rule::in(array_keys(ReportPeriod::PRESETS))],
            'from' => ['nullable', 'date', 'required_if:preset,custom'],
            'to' => ['nullable', 'date', 'required_if:preset,custom'],
        ]);
    }

    //Reports End

    public function index()
    {
        $companyId = session('company_id');

        if (!$companyId) {
            return redirect()
                ->route('company.login')
                ->with('error', __('company/messages.profile.login_required'));
        }

        $result = $this->companyService->getCompany($companyId);

        if (!$result['success']) {
            return redirect()
                ->route('company.login')
                ->with('error', $result['message']);
        }

        $company = $result['company'];

        return view('company.index', compact('company'));
    }

    public function updateProfile(Request $request)
    {
        $company = Auth::guard('company')->user();

        if (!$company) {
            return redirect()->route('company.login');
        }

        $validated = $request->validate([
            // English and/or Arabic letters, digits and . , & ' ( ) - (same rule as sign-up).
            'name' => ['required', 'string', 'max:200', 'min:10', 'regex:/\A[A-Za-z0-9\s.,&\'()\-\p{Arabic}]+\z/u'],
            'city' => [
                'required',
                'string',
                Rule::in([
                    'Khartoum',
                    'Port Sudan',
                    'Atbara',
                    'Wad Madani',
                    'Merowe',
                    'Kassala',
                    'Al Qadarif',
                    'Dongola',
                    'Halfa',
                ]),
            ],
            'license_number' => ['required', 'string', 'max:191'],
            'description' => ['required', 'string', 'max:5000'],
            'ar_description' => ['nullable', 'string', 'max:5000'],
            'services' => ['required', 'string', 'max:3000'],
            'ar_services' => ['nullable', 'string', 'max:3000'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => [
                'required',
                'email',
                'max:191',
                Rule::unique('tbl_solar_companies', 'email')
                    ->ignore($company->id),
            ],
            'social_media' => ['nullable', 'string', 'max:2000'],
            'logo' => [
                empty($company->logo_path) ? 'required' : 'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
                'required_with:password_confirmation',
                'confirmed',
            ],

            'password_confirmation' => [
                'nullable',
                'string',
                'required_with:password',
            ],
            'year_of_exp' => ['required', 'integer', 'min:1'],
            'number_of_projects' => ['required', 'integer', 'min:1'],
        ], [
            'name.regex' => __('company/messages.profile.name_regex'),
        ]);

        /*
         * Keep the current logo unless a new image was uploaded.
         * The logo file is handled by the controller; database updates
         * are delegated through AdminService/AdminRepository.
         */
        if ($request->hasFile('logo')) {
            $slug = $company->slug ?: Str::slug($company->name);
            $directory = public_path("main/images/company/{$slug}");

            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            $extension = strtolower(
                $request->file('logo')->getClientOriginalExtension()
            );



            $filename = "{$slug}-slogan.{$extension}";

            // Remove previous versions if the image extension changed.
            foreach (['jpg', 'jpeg', 'png', 'webp'] as $oldExtension) {
                $oldFile = $directory . DIRECTORY_SEPARATOR
                    . "{$slug}-slogan.{$oldExtension}";

                if (File::exists($oldFile) && $oldExtension !== $extension) {
                    File::delete($oldFile);
                }
            }

            $request->file('logo')->move($directory, $filename);
            \App\Helpers\Helper::shrinkImage($directory . DIRECTORY_SEPARATOR . $filename);

            // Keep the URL format used by the existing company records.
            $validated['logo_path'] = asset(
                "main/images/company/{$slug}/{$filename}"
            );
        }

        unset($validated['logo']);

        if ($request->filled('password')) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make(
                $request->input('password')
            );
        }


        $result = $this->companyService->updateCompanyProfile(
            $company->id,
            $validated
        );

        if (!$result['success']) {
            return back()
                ->withInput()
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('company.profile')
            ->with('success', __('company/messages.profile.updated'));
    }

    private function companyId(): int
    {
        $companyId = session('company_id');

        abort_unless($companyId && is_numeric($companyId), 403);

        return (int) $companyId;
    }

    public function showProducts(Request $request)
    {
        $products = $this->companyService->list(
            $this->companyId(),
            $request->only(['search', 'type'])
        );

        return view('company.products.index', compact('products'));
    }

    public function create()
    {
        $suppliers = $this->companyService->suppliers();

        return view('company.products.form', [
            'product' => null,
            'suppliers' => $suppliers,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $this->companyService->save($this->companyId(), $data);

        return redirect()
            ->route('company.products.index')
            ->with('success', __('company/messages.products.added'));
    }

    public function edit(int $product)
    {
        $companyId = $this->companyId();

        $product = $this->companyService->find($companyId, $product);
        $suppliers = $this->companyService->suppliers();

        return view('company.products.form', compact(
            'product',
            'suppliers'
        ));
    }

    public function update(Request $request, int $product)
    {
        $data = $this->validatedData($request);

        $this->companyService->save(
            $this->companyId(),
            $data,
            $product
        );

        return redirect()
            ->route('company.products.index')
            ->with('success', __('company/messages.products.updated'));
    }

    public function destroy(int $product)
    {
        $this->companyService->delete($this->companyId(), $product);

        return redirect()
            ->route('company.products.index')
            ->with('success', __('company/messages.products.deleted'));
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'supplier_id' => [
                'required',
                'integer',
                'exists:tbl_suppliers,id',
            ],
            'name' => ['required', 'string', 'max:150'],
            'model' => ['nullable', 'string', 'max:100'],
            'type' => [
                'required',
                Rule::in(['inverter', 'solar_panel', 'battery', 'other']),
            ],
            'description' => ['nullable', 'string', 'max:3000'],
            'is_active' => ['sometimes', 'boolean'],
            'specs' => ['nullable', 'array', 'max:20'],
            'specs.*.name' => ['nullable', 'string', 'max:100'],
            'specs.*.value' => ['nullable', 'string', 'max:255'],
        ], [], app()->isLocale('ar') ? [
            // ar/validation.php names these fields for the company form.
            'name' => __('company/messages.products.name'),
            'description' => __('company/messages.products.description'),
        ] : []);
    }

    //Quote Requests Start

    public function quoteRequests(Request $request)
    {
        $filters = $request->validate([
            'status' => [
                'nullable',
                'string',
                'in:pending,accepted,rejected,completed,cancelled',
            ],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $quotes = $this->companyService->getQuoteRequests($filters);
        $metrics = $this->companyService->getQuoteRequestMetrics();

        return view('company.quote-requests.index', compact(
            'quotes',
            'metrics',
            'filters'
        ));
    }

    public function companyInvoices(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $invoices = $this->companyService->getCompanyInvoices($filters);

        return view('company.invoices.index', compact('invoices', 'filters'));
    }

    public function deleteCompanyInvoice(int $id)
    {
        $result = $this->companyService->deleteCompanyInvoice($id);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function quoteRequestDetails(int $id)
    {
        $project = null;

        $quote = $this->companyService->getQuoteRequestById($id);

        if (!$quote) {
            return redirect()
                ->route('company.quote-requests.index')
                ->with('error', __('company/messages.quote_requests.not_available'));
        }

        $companyQuotation = $this->companyService->getCompanyQuotationForQuote($id);

        $competitorPrices = $this->companyService->getCompetitorOfferPrices($id);

        if ($companyQuotation && $companyQuotation->status === 'selected') {
            $project = $this->companyService->getProjectForQuote($quote->id);
        }

        $invoiceNumber = 'INV-' . now()->format('Ymd') . '-Q'
            . str_pad((string) $quote->id, 5, '0', STR_PAD_LEFT)
            . '-C' . auth()->guard('company')->id();

        // The customer accepted one of this company's package offers: its items fill a new offer.
        $packageOffer = $this->companyService->getPackageOfferForQuote($quote);
        $products = $this->companyService->getCompanyProductOptions();

        // A new offer starts from the warranty and terms of the company's last offer.
        $lastOfferTerms = $companyQuotation ? null : $this->companyService->getLastOfferTerms();

        return view('company.quote-requests.form', compact(
            'quote',
            'companyQuotation',
            'competitorPrices',
            'project',
            'invoiceNumber',
            'packageOffer',
            'products',
            'lastOfferTerms'
        ));
    }

    public function submitQuoteOffer(Request $request, int $id)
    {
        $validated = $request->validate([
            // With an invoice, the price comes from the invoice balance.
            'price' => [
                'exclude_if:include_invoice,1',
                'required',
                'numeric',
                'min:1000',
                'max:9999999999999',
            ],
            'currency' => [
                'required',
                'string',
                'in:SDG,USD',
            ],
            'message' => [
                'required',
                'string',
                'max:2000',
            ],
            'ar_message' => [
                'required',
                'string',
                'max:2000',
            ],
            'include_invoice' => ['nullable', 'boolean'],
            'invoice_number' => ['exclude_unless:include_invoice,1', 'required', 'string', 'max:60'],
            'invoice_date' => ['exclude_unless:include_invoice,1', 'required', 'date_format:Y-m-d'],
            'invoice_notes' => ['exclude_unless:include_invoice,1', 'nullable', 'string', 'max:2000'],
            'invoice_discount' => ['exclude_unless:include_invoice,1', 'nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'invoice_tax_rate' => ['exclude_unless:include_invoice,1', 'nullable', 'numeric', 'min:0', 'max:100'],
            'invoice_shipping' => ['exclude_unless:include_invoice,1', 'nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'invoice_items' => ['exclude_unless:include_invoice,1', 'required', 'array', 'min:1', 'max:25'],
            'invoice_items.*.description' => ['exclude_unless:include_invoice,1', 'required', 'string', 'min:2', 'max:255'],
            'invoice_items.*.quantity' => ['exclude_unless:include_invoice,1', 'required', 'numeric', 'gt:0', 'max:10000'],
            'invoice_items.*.unit_price' => ['exclude_unless:include_invoice,1', 'required', 'numeric', 'min:0', 'max:9999999999999'],
            // Picked from the company's products (checked against the company in the repository).
            'invoice_items.*.product_id' => ['exclude_unless:include_invoice,1', 'nullable', 'integer', 'min:1'],
            'invoice_items.*.type' => ['exclude_unless:include_invoice,1', 'nullable', Rule::in(array_keys(Product::TYPES))],
            // Warranty and terms (all optional).
            ...OfferTerms::rules(),
        ], [
            'price.required' => __('company/messages.quote.price_required'),
            'price.numeric' => __('company/messages.quote.price_numeric'),
            'price.min' => __('company/messages.quote.price_min'),
        ], OfferTerms::attributes());

        $this->companyService->submitQuoteOffer($id, $validated);

        return redirect()
            ->route('company.quote-requests.show', $id)
            ->with('success', __('company/messages.quote.offer_saved'));
    }

    public function showQuoteInvoice(int $id)
    {
        $invoice = $this->companyService->getQuoteInvoice($id);
        if (!$invoice) {
            return redirect()->route('company.quote-requests.show', $id)
                ->with('error', __('company/messages.quote.no_invoice'));
        }

        return view('company.quote-requests.invoice', compact('invoice'));
    }

    public function downloadQuoteInvoice(int $id)
    {
        $invoice = $this->companyService->getQuoteInvoice($id);
        if (!$invoice) {
            return redirect()->route('company.quote-requests.show', $id)
                ->with('error', __('company/messages.quote.no_invoice'));
        }

        // The PDF library comes from Composer; if the server has not installed it, offer printing instead.
        if (!class_exists(\Mpdf\Mpdf::class)) {
            report(new \RuntimeException('mpdf/mpdf is missing: run composer install on this server.'));

            return redirect()->route('company.quote-requests.invoice', $id)
                ->with('error', __('company/messages.invoices.pdf_unavailable'));
        }

        $pdf = InvoicePdf::render($invoice);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf;
        }, InvoicePdf::filename($invoice), ['Content-Type' => 'application/pdf']);
    }

    //Quote Requests End

    //Package Offers Start

    public function packageOffers(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'ended'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $offers = $this->companyService->getPackageOffers($filters);

        return view('company.offers.index', compact('offers', 'filters'));
    }

    /** ?from={id} starts from a copy of an earlier offer (a sent offer cannot be edited). */
    public function createPackageOffer(Request $request)
    {
        $source = $request->integer('from')
            ? $this->companyService->findPackageOffer($request->integer('from'))
            : null;

        return view('company.offers.form', [
            'offer' => null,
            'source' => $source,
            'products' => $this->companyService->getCompanyProductOptions(),
        ]);
    }

    public function storePackageOffer(Request $request)
    {
        $offer = $this->companyService->savePackageOffer($this->validatedPackageOffer($request), null, $request->file('image'));

        return redirect()
            ->route('company.offers.show', $offer->id)
            ->with('success', __('company/messages.package_offers.saved'));
    }

    public function showPackageOffer(int $offer)
    {
        $details = $this->companyService->getPackageOfferDetails($offer);

        // Offers go to the company's own customers only.
        return view('company.offers.show', $details + [
            'customers' => $this->companyService->getOfferRecipients(),
        ]);
    }

    public function editPackageOffer(int $offer)
    {
        $offer = $this->companyService->findPackageOffer($offer);

        if ($offer->isLocked()) {
            return redirect()
                ->route('company.offers.show', $offer->id)
                ->with('error', __('company/messages.package_offers.locked'));
        }

        return view('company.offers.form', [
            'offer' => $offer,
            'source' => null,
            'products' => $this->companyService->getCompanyProductOptions(),
        ]);
    }

    public function updatePackageOffer(Request $request, int $offer)
    {
        $this->companyService->savePackageOffer($this->validatedPackageOffer($request), $offer, $request->file('image'));

        return redirect()
            ->route('company.offers.show', $offer)
            ->with('success', __('company/messages.package_offers.saved'));
    }

    public function closePackageOffer(int $offer)
    {
        $this->companyService->closePackageOffer($offer);

        return back()->with('success', __('company/messages.package_offers.closed'));
    }

    public function destroyPackageOffer(int $offer)
    {
        $result = $this->companyService->deletePackageOffer($offer);

        return $result['success']
            ? redirect()->route('company.offers.index')->with('success', $result['message'])
            : back()->with('error', $result['message']);
    }

    public function sendPackageOffer(Request $request, int $offer)
    {
        $validated = $request->validate([
            'customer_ids' => ['required', 'array', 'min:1'],
            'customer_ids.*' => ['required', 'integer', 'min:1'],
            'message_en' => ['nullable', 'string', 'max:1000'],
            'message_ar' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $this->companyService->sendPackageOffer($offer, $validated);

        return redirect()
            ->route('company.offers.show', $offer)
            ->with('success', $result['message']);
    }

    /** The shared offer builder fields (package[...], same as the admin campaign) plus an optional image. */
    private function validatedPackageOffer(Request $request): array
    {
        $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=300,min_height=150'],
        ], [], ['image' => __('company/messages.package_offers.image')]);

        return $request->validate(PackageOffer::rules(), [], PackageOffer::attributes())['package'];
    }

    //Package Offers End

    //Notifications Managment End
    public function notifications(Request $request)
    {
        $filters = $request->validate([
            'type' => [
                'nullable',
                'string',
                Rule::in([
                    'general',
                    'quote',
                    'promotion',
                    'account',
                    'support',
                    'maintenance',
                ]),
            ],
            'status' => ['nullable', Rule::in(['published', 'draft'])],
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        $result = $this->companyService->getNotifications($filters);

        return view('company.notifications.index', [
            'notifications' => $result['notifications'],
            'publishedCount' => $result['publishedCount'],
            'draftCount' => $result['draftCount'],
            'recipientCount' => $result['recipientCount'],
        ]);
    }

    public function createNotification(Request $request)
    {
        $result = $this->companyService->getNotificationFormData();

        // Opened from Company Customers: ?customer_ids[]=5 (one customer) or ?to=company-customers (all of them).
        $preselected = $request->query('to') === 'company-customers'
            ? $this->companyService->getCompanyCustomerIds()
            : array_map('intval', array_filter((array) $request->query('customer_ids', []), 'is_numeric'));

        return view('company.notifications.create', [
            'customers' => $result['customers'],
            'canNotifyAll' => $result['canNotifyAll'],
            'sendLimit' => $result['sendLimit'],
            'preselected' => $preselected,
        ]);
    }

    public function storeNotification(Request $request)
    {
        $validated = $request->validate([
            'type' => [
                'required',
                Rule::in([
                    'general',
                    'quote',
                    'promotion',
                    'account',
                    'support',
                    'maintenance',
                ]),
            ],
            'audience_type' => ['required', Rule::in(['selected'])],
            'customer_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'customer_ids.*' => ['required', 'integer', 'min:1'],
            'title_en' => ['required', 'string', 'max:180'],
            'title_ar' => ['nullable', 'string', 'max:180'],
            'message_en' => ['required', 'string', 'max:5000'],
            'message_ar' => ['nullable', 'string', 'max:5000'],
        ]);

        $result = $this->companyService->publishNotification($validated);

        if (!$result['success']) {
            return back()
                ->withInput()
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('company.notifications.index')
            ->with('success', $result['message']);
    }
    public function showNotification(int $notification)
    {
        $result = $this->companyService->getNotificationDetails($notification);

        if (!$result['success']) {
            return redirect()
                ->route('company.notifications.index')
                ->with('error', $result['message']);
        }

        return view('company.notifications.show', [
            'notification' => $result['notification'],
            'readCount' => $result['readCount'],
            'unreadCount' => $result['unreadCount'],
            'recipients' => $result['recipients'],
        ]);
    }

    public function destroyNotification(int $notification)
    {
        $result = $this->companyService->deleteNotification($notification);

        return redirect()->route('company.notifications.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    //Notifications Managment Start

    //Advertising Start

    public function companyAds()
    {
        return view('company.ads.index', $this->companyService->getCompanyAds());
    }

    public function createAdRequest()
    {
        return view('company.ads.create');
    }

    public function storeAdRequest(Request $request)
    {
        $validated = $request->validate([
            'product' => ['required', Rule::in(\App\Models\AdSale::REQUESTABLE_PRODUCTS)],
            'platform' => ['required', Rule::in(['app'])],
            // 'platform' => ['required', Rule::in(['app', 'website', 'both'])],
            'starts_on' => ['required', 'date', 'after_or_equal:today'],
            'weeks' => ['required', 'integer', 'between:1,52'],
            'link_url' => ['nullable', 'url:http,https', 'max:500'],
            // 'artwork' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'request_note' => ['nullable', 'string', 'max:1000'],
        ]);

        // Only admin adds ad images for now.
        $this->companyService->requestAd($validated, null);
        // $this->companyService->requestAd($validated, $request->file('artwork'));

        return redirect()->route('company.ads.index')->with('success', __('company/messages.ads.requested'));
    }

    public function cancelAdRequest(int $id)
    {
        $result = $this->companyService->cancelAdRequest($id);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function companyAdReport(Request $request)
    {
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);

        return view('company.ads.report', $this->companyService->getCompanyAdReport($data['month'] ?? now()->format('Y-m')));
    }

    //Advertising End

    //Ratings Start

    /** Customers who accepted this company's offers (Customers → Company Customers). */
    public function companyCustomers(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $customers = $this->companyService->getCompanyCustomers($filters);
        $summary = $this->companyService->getCompanyCustomerSummary();

        return view('company.customers.index', compact('customers', 'summary'));
    }

    public function ratings(Request $request)
    {
        $filters = $request->validate([
            'stars' => ['nullable', 'integer', 'between:1,5'],
        ]);

        $ratings = $this->companyService->getCompanyRatings($filters);
        $summary = $this->companyService->getCompanyRatingSummary();
        $valueLabels = RatingValue::labels(app()->getLocale());

        return view('company.ratings.index', compact('ratings', 'summary', 'valueLabels'));
    }

    /** Company Manual: static help text from resources/lang/{en,ar}/company/manual.php. */
    public function manual()
    {
        $hasAdvertising = Setting::companyAdsEnabled((int) auth('company')->id());

        return view('company.manual', compact('hasAdvertising'));
    }

    //Ratings End
}

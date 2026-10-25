<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\RatingValue;
use App\Services\Company\CompanyService;
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
        abort_unless(in_array($section, ['offers', 'projects', 'customers'], true), 404);

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
                ->with('error', 'Please log in to continue.');
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
            'name' => ['required', 'string', 'max:191'],
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
            ->with('success', 'Company profile updated successfully.');
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
            ->with('success', 'Product added successfully.');
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
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(int $product)
    {
        $this->companyService->delete($this->companyId(), $product);

        return redirect()
            ->route('company.products.index')
            ->with('success', 'Product deleted successfully.');
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
        ]);
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

    public function quoteRequestDetails(int $id)
    {
        $project = null;

        $quote = $this->companyService->getQuoteRequestById($id);

        abort_if(!$quote, 404);

        $companyQuotation = $this->companyService->getCompanyQuotationForQuote($id);

        $competitorPrices = $this->companyService->getCompetitorOfferPrices($id);

        if ($companyQuotation && $companyQuotation->status === 'selected') {
            $companyId = auth()->guard('company')->id();

            $project = \App\Models\Project::query()
                ->where('quote_id', $quote->id)
                ->where('company_id', $companyId)
                ->first();
        }

        return view('company.quote-requests.form', compact(
            'quote',
            'companyQuotation',
            'competitorPrices',
            'project'
        ));
    }

    public function submitQuoteOffer(Request $request, int $id)
    {
        $validated = $request->validate([
            'price' => [
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
        ], [
            'price.required' => 'Please enter your total offer price.',
            'price.numeric' => 'Please enter a valid total price.',
            'price.min' => 'The total offer price must be at least 1,000.',
        ]);

        $this->companyService->submitQuoteOffer($id, $validated);

        return redirect()
            ->route('company.quote-requests.show', $id)
            ->with('success', 'Your offer has been saved successfully.');
    }

    //Quote Requests End

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
            'audience_type' => ['required', Rule::in(['all', 'selected'])],
            'customer_ids' => [
                'required_if:audience_type,selected',
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
        $valueLabels = RatingValue::labels();

        return view('company.ratings.index', compact('ratings', 'summary', 'valueLabels'));
    }

    //Ratings End
}

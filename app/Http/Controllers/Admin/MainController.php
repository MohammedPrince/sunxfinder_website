<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ArchivedItem;
use App\Models\AppVersion;
use App\Repositories\Admin\AdminRepository;
use App\Models\Customer;
use App\Models\CustomerComplaint;
use App\Models\CompanyQuotation;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Rating;
use App\Models\RatingValue;
use App\Models\SolarCompany;
use App\Models\SentEmail;
use App\Services\Admin\AdminService;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MainController extends Controller
{
    protected $adminService;
    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function dashboard()
    {
        $result = $this->adminService->getDashboardData();

        return view('admin.dashboard', $result);
    }

    //Reports Start

    public function reports(Request $request)
    {
        $filters = $this->validatedReportFilters($request);

        $report = $this->adminService->getReportsData($filters);

        return view('admin.reports.index', array_merge($report, [
            'presets' => AdminRepository::REPORT_PRESETS,
            'stalledDays' => AdminRepository::REPORT_STALLED_DAYS,
        ]));
    }

    public function exportReport(Request $request, string $section)
    {
        abort_unless(in_array($section, ['companies', 'quote_requests', 'customers'], true), 404);

        $export = $this->adminService->getReportExport($section, $this->validatedReportFilters($request));

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
            'preset' => ['nullable', Rule::in(array_keys(AdminRepository::REPORT_PRESETS))],
            'from' => ['nullable', 'date', 'required_if:preset,custom'],
            'to' => ['nullable', 'date', 'required_if:preset,custom'],
        ]);
    }

    //Reports End

    public function index()
    {
        $companies = $this->adminService->getCompanies();

        $verifiedCount = $this->adminService->verifiedCount();

        $pendingCount = $this->adminService->pendingCount();

        $inactiveCount = $this->adminService->inactiveCount();

        return view('admin.companies.index', compact(
            'companies',
            'verifiedCount',
            'pendingCount',
            'inactiveCount'
        ));
    }

    public function loginAsCompany(Request $request, int $id)
    {
        $company = SolarCompany::query()->findOrFail($id);

        abort_unless(
            $company->is_active && in_array($company->status, ['pending', 'approved'], true),
            403,
            'This company account is inactive or not eligible to log in.'
        );

        $guard = Auth::guard('company');
        $impersonation = $request->session()->get('admin_company_impersonation');

        // Keep a company already signed in through the normal login form so it
        // can be restored when the admin finishes this company session. If the
        // admin is already in another company, that one is simply replaced and
        // the original real company stays remembered.
        $realCompanyId = $impersonation
            ? ($impersonation['previous_company_id'] ?? null)
            : $guard->id();

        // Never use $guard->logout() here: it cycles the remember token and would
        // sign the switched-out company out of its own devices too.
        $guard->login($company);

        if ($realCompanyId && (int) $realCompanyId === $company->id) {
            // Back to the real company itself: nothing to restore later.
            $request->session()->forget('admin_company_impersonation');
        } else {
            $request->session()->put('admin_company_impersonation', [
                'previous_company_id' => $realCompanyId,
                'company_id' => $company->id,
            ]);
        }
        $request->session()->regenerate();
        $request->session()->put('company_id', $company->id);

        ActivityLogger::log(
            'admin_company_login',
            "Admin signed in as company {$company->name}",
            $company,
            ['company_id' => $company->id],
            ActivityLogger::actorFor(Auth::guard('admin')->user()),
            'auth'
        );

        return redirect()->route('company.dashboard');
    }

    public function companyDetails(int $id)
    {
        $company = SolarCompany::query()->findOrFail($id);
        $companyId = (int) $company->id;

        $offerBase = CompanyQuotation::query()->where('company_id', $companyId);
        $projectBase = Project::query()->where('company_id', $companyId);
        $ratingBase = Rating::query()->where('company_id', $companyId);
        $quoteBase = Quote::query()->where(function ($query) use ($companyId) {
            $query->where('company_id', $companyId)
                ->orWhereHas('companyQuotations', fn($offers) => $offers->where('company_id', $companyId));
        });

        $customerQuoteIds = Quote::query()
            ->where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhereHas('companyQuotations', fn($offers) => $offers->where('company_id', $companyId));
            })
            ->select('customer_id');

        $customersBase = Customer::query()->where(function ($query) use ($companyId, $customerQuoteIds) {
            $query->whereIn('id', $customerQuoteIds)
                ->orWhereIn('id', Project::query()->where('company_id', $companyId)->select('customer_id'))
                ->orWhereIn('id', Rating::query()->where('company_id', $companyId)->select('customer_id'));
        });

        $quoteStatusCounts = (clone $quoteBase)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $offerStatusCounts = (clone $offerBase)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $projectStatusCounts = (clone $projectBase)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $quotes = (clone $quoteBase)
            ->with(['customer:id,full_name,username', 'project:id,quote_id,status,progress_percent'])
            ->withCount(['companyQuotations as company_offers_count' => fn($offers) => $offers->where('company_id', $companyId)])
            ->latest()
            ->paginate(10, ['*'], 'quotes_page');

        $customers = (clone $customersBase)
            ->latest()
            ->paginate(10, ['*'], 'customers_page');

        $projects = (clone $projectBase)
            ->with(['customer:id,full_name,username', 'quote:id,quote_name'])
            ->latest()
            ->paginate(10, ['*'], 'projects_page');

        $ratings = (clone $ratingBase)
            ->with('customer:id,full_name,username')
            ->latest()
            ->paginate(10, ['*'], 'ratings_page');

        $ratingSummary = $company->ratingSummary();
        $quoteCount = (clone $quoteBase)->count();
        $offerCount = (clone $offerBase)->count();
        $projectCount = (clone $projectBase)->count();
        $ratingCount = (clone $ratingBase)->count();
        $customerCount = (clone $customersBase)->distinct()->count('id');

        return view('admin.companies.company-details', compact(
            'company',
            'quotes',
            'customers',
            'projects',
            'ratings',
            'quoteStatusCounts',
            'offerStatusCounts',
            'projectStatusCounts',
            'ratingSummary',
            'quoteCount',
            'offerCount',
            'projectCount',
            'ratingCount',
            'customerCount'
        ));
    }

    public function createCompany()
    {
        return view('admin.companies.add_company');
    }

    public function addNewCompany(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:200'],
            'username' => [
                'required',
                'string',
                'max:191',
                'unique:tbl_solar_companies,username',
            ],
            'email' => [
                'nullable',
                'email',
                'max:191',
                'unique:tbl_solar_companies,email',
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'license_number' => ['required', 'string', 'max:191'],
            'services' => ['required', 'string', 'max:5000'],
            'ar_services' => ['required', 'string', 'max:5000'],
            'password' => ['required', 'string', 'min:8'],
            'description' => ['nullable', 'string'],
            'ar_description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(SolarCompany::EDITABLE_STATUSES)],
            'is_verified' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'year_of_exp' => ['required', 'integer', 'min:1'],
            'number_of_projects' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except('password'));
        }

        $result = $this->adminService->addNewCompany(
            $validator->validated()
        );

        if (!$result['success']) {
            return back()
                ->withInput($request->except('password'))
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.companies.index')
            ->with('success', $result['message']);
    }

    public function editCompany(int $id)
    {
        $result = $this->adminService->getCompanyById($id);

        if (!$result['success']) {
            return redirect()
                ->route('admin.companies.index')
                ->with('error', $result['message']);
        }

        $company = $result['company'];

        return view('admin.companies.add_company', compact('company'));
    }

    public function updateCompany(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'license_number' => ['required', 'string', 'max:191'],
            'services' => ['required', 'string', 'max:5000'],
            'ar_services' => ['required', 'string', 'max:5000'],
            'description' => ['nullable', 'string'],
            'ar_description' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:5'],
            'status' => ['required', Rule::in(SolarCompany::EDITABLE_STATUSES)],
            'is_verified' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'year_of_exp' => ['required', 'integer', 'min:1'],
            'number_of_projects' => ['required', 'integer', 'min:1'],
        ]);

        $result = $this->adminService->updateCompany($id, $validated);

        if (!$result['success']) {
            return back()
                ->withInput($request->except('password'))
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.companies.index')
            ->with('success', $result['message']);
    }

    public function deleteCompany(int $id)
    {
        $result = $this->adminService->deleteCompany($id);

        if (!$result['success']) {
            return redirect()
                ->route('admin.companies.index')
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.companies.index')
            ->with('success', $result['message']);
    }

    //Manage Customers Start
    public function createCustomer()
    {
        $customer = new Customer();

        return view('admin.customers.add_customer', compact('customer'));
    }

    public function storeCustomer(Request $request)
    {
        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:191',
                Rule::unique('tbl_customers', 'username'),
            ],
            'full_name' => ['required', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => [
                'nullable',
                'email',
                'max:191',
                Rule::unique('tbl_customers', 'email'),
            ],
            'gender' => ['required', Rule::in(['male', 'female'])],
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
            'address' => ['nullable', 'string', 'max:5000'],
            'status' => [
                'required',
                Rule::in(['active', 'pending', 'suspended', 'deleted']),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $result = $this->adminService->createCustomer($validated);

        if (!$result['success']) {
            return back()
                ->withInput($request->except([
                    'password',
                    'password_confirmation',
                ]))
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.customers.index')
            ->with('success', $result['message']);
    }

    /** Stop / re-allow the mobile app for one customer (from the Customer directory modal). */
    public function updateCustomerAppStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'app_status' => ['required', Rule::in(['active', 'stopped'])],
            'message_en' => ['required_if:app_status,stopped', 'nullable', 'string', 'max:1000'],
            'message_ar' => ['required_if:app_status,stopped', 'nullable', 'string', 'max:1000'],
            'push' => ['nullable', 'boolean'],
        ], [
            'message_en.required_if' => 'Write the English message the customer will see.',
            'message_ar.required_if' => 'Write the Arabic message the customer will see.',
        ]);

        $result = $this->adminService->setCustomerAppStatus(
            $id,
            $data['app_status'],
            $data['message_en'] ?? null,
            $data['message_ar'] ?? null,
            $request->boolean('push')
        );

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function showCustomers(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'pending', 'suspended', 'deleted'])],
        ]);

        $customers = $this->adminService->getCustomers(10, $filters['status'] ?? null);

        $totalCount = $this->adminService->customerAccountCount();
        $activeCount = $this->adminService->activeCustomerAccountCount();
        $pendingCount = $this->adminService->pendingCustomerAccountCount();
        $inactiveCount = $this->adminService->inactiveCustomerAccountCount();
        $signedInCount = $this->adminService->signedInCustomerCount();

        return view('admin.customers.index', compact(
            'customers',
            'totalCount',
            'activeCount',
            'pendingCount',
            'inactiveCount',
            'signedInCount'
        ));
    }

    // Sign one customer out of the app (from the edit page)
    public function signOutCustomer(int $id)
    {
        $result = $this->adminService->signOutCustomer($id);

        $redirect = $result['success']
            ? redirect()->route('admin.customers.edit', $id)
            : redirect()->route('admin.customers.index');

        return $redirect->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    // Sign every customer out of the app
    public function forceLogoutCustomers()
    {
        $result = $this->adminService->forceLogoutAllCustomers();

        return redirect()
            ->route('admin.customers.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    // Show the customer edit form
    public function editCustomer(int $id)
    {
        $result = $this->adminService->getCustomerById($id);

        if (!$result['success']) {
            return redirect()
                ->route('admin.customers.index')
                ->with('error', $result['message']);
        }

        $customer = $result['customer'];

        return view('admin.customers.add_customer', compact('customer'));
    }

    // Update customer
    public function updateCustomer(Request $request, int $id)
    {
        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:191',
                Rule::unique('tbl_customers', 'username')->ignore($id),
            ],
            'full_name' => ['required', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => [
                'nullable',
                'email',
                'max:191',
                Rule::unique('tbl_customers', 'email')->ignore($id),
            ],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:5000'],
            'status' => [
                'required',
                Rule::in(['active', 'pending', 'suspended', 'deleted']),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $result = $this->adminService->updateCustomer($id, $validated);

        if (!$result['success']) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.customers.index')
            ->with('success', $result['message']);
    }
    // Delete customer
    public function destroyCustomer(int $id)
    {
        $result = $this->adminService->deleteCustomer($id);

        if (!$result['success']) {
            return redirect()
                ->route('admin.customers.index')
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.customers.index')
            ->with('success', $result['message']);
    }

    //Appliance Groups and Items Start
    public function showApplianceGroups()
    {
        $result = $this->adminService->showApplianceGroups();

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        $groups = $result['groups'];

        return view('admin.calculator-groups.index', compact('groups'));
    }

    public function createApplianceGroup()
    {
        $result = $this->adminService->createApplianceGroup();

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return view('admin.calculator-groups.form', [
            'nextSortOrder' => $result['nextSortOrder'] ?? 1,
        ]);
    }

    public function storeApplianceGroup(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                'regex:/\A[A-Za-z0-9\s.,&\'()\-]+\z/',
            ],
            'ar_name' => ['required', 'string', 'max:150'],
            'icon' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],

            'appliances' => ['required', 'array', 'min:1'],
            'appliances.*.name' => [
                'required',
                'string',
                'max:150',
                'regex:/\A[A-Za-z0-9\s.,&\'()\-]+\z/',
            ],
            'appliances.*.ar_name' => ['required', 'string', 'max:150'],
            'appliances.*.watts' => ['required', 'integer', 'min:1'],
            'appliances.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'appliances.*.is_active' => ['required', 'boolean'],
        ]);

        $result = $this->adminService->storeApplianceGroup($validated);

        if (!$result['success']) {
            return back()
                ->withInput()
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.calculator-groups.index')
            ->with('success', $result['message']);
    }

    public function editApplianceGroup(int $id)
    {
        $result = $this->adminService->editApplianceGroup($id);

        if (!$result['success']) {
            return redirect()
                ->route('admin.calculator-groups.index')
                ->with('error', $result['message']);
        }

        return view('admin.calculator-groups.form', [
            'group' => $result['group'],
        ]);
    }

    public function updateApplianceGroup(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                'regex:/\A[A-Za-z0-9\s.,&\'()\-]+\z/',
            ],
            'ar_name' => ['required', 'string', 'max:150'],
            'icon' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],

            'appliances' => ['required', 'array', 'min:1'],
            'appliances.*.id' => ['nullable', 'integer', 'min:1'],
            'appliances.*.name' => [
                'required',
                'string',
                'max:150',
                'regex:/\A[A-Za-z0-9\s.,&\'()\-]+\z/',
            ],
            'appliances.*.ar_name' => ['required', 'string', 'max:150'],
            'appliances.*.watts' => ['required', 'integer', 'min:1'],
            'appliances.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'appliances.*.is_active' => ['required', 'boolean'],
        ]);

        $result = $this->adminService->updateApplianceGroup($id, $validated);

        if (!$result['success']) {
            return back()
                ->withInput()
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.calculator-groups.index')
            ->with('success', $result['message']);
    }

    public function destroyApplianceGroup(int $id)
    {
        $result = $this->adminService->destroyApplianceGroup($id);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.calculator-groups.index')
            ->with('success', $result['message']);
    }

    //Appliance Groups and Items End

    //Supplier and Products Managment Start

    public function showSuppliers()
    {
        $result = $this->adminService->showSuppliers();
        if (!$result['success'])
            return back()->with('error', $result['message']);
        return view('admin.suppliers.index', ['suppliers' => $result['suppliers']]);
    }

    public function createSupplier()
    {
        return view('admin.suppliers.form', ['supplier' => null]);
    }

    public function storeSupplier(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'website' => ['nullable', 'url', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);
        $result = $this->adminService->storeSupplier($validated);
        if (!$result['success'])
            return back()->withInput()->with('error', $result['message']);
        return redirect()->route('admin.suppliers.index')->with('success', $result['message']);
    }

    public function editSupplier(int $id)
    {
        $result = $this->adminService->editSupplier($id);
        if (!$result['success'])
            return redirect()->route('admin.suppliers.index')->with('error', $result['message']);
        return view('admin.suppliers.form', ['supplier' => $result['supplier']]);
    }

    public function updateSupplier(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'website' => ['nullable', 'url', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);
        $result = $this->adminService->updateSupplier($id, $validated);
        if (!$result['success'])
            return back()->withInput()->with('error', $result['message']);
        return redirect()->route('admin.suppliers.index')->with('success', $result['message']);
    }

    public function destroySupplier(int $id)
    {
        $result = $this->adminService->destroySupplier($id);
        if (!$result['success'])
            return back()->with('error', $result['message']);
        return redirect()->route('admin.suppliers.index')->with('success', $result['message']);
    }

    public function showProducts()
    {
        $result = $this->adminService->showProducts();
        if (!$result['success'])
            return back()->with('error', $result['message']);
        return view('admin.products.index', [
            'products' => $result['products'],
            'suppliers' => $result['suppliers'],
        ]);
    }

    public function createProduct()
    {
        $result = $this->adminService->productFormData();
        if (!$result['success'])
            return back()->with('error', $result['message']);
        return view('admin.products.form', [
            'product' => null,
            'suppliers' => $result['suppliers'],
            'types' => Product::TYPES,
        ]);
    }

    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:tbl_suppliers,id'],
            'name' => ['required', 'string', 'max:180'],
            'model' => ['nullable', 'string', 'max:150'],
            'type' => ['required', 'in:inverter,solar_panel,battery,other'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
            'specs' => ['nullable', 'array', 'max:30'],
            'specs.*.spec_name' => ['required_with:specs', 'string', 'max:100'],
            'specs.*.spec_value' => ['required_with:specs', 'string', 'max:150'],
        ]);
        $result = $this->adminService->storeProduct($validated);
        if (!$result['success'])
            return back()->withInput()->with('error', $result['message']);
        return redirect()->route('admin.products.index')->with('success', $result['message']);
    }

    public function editProduct(int $id)
    {
        $result = $this->adminService->editProduct($id);
        if (!$result['success'])
            return redirect()->route('admin.products.index')->with('error', $result['message']);
        return view('admin.products.form', [
            'product' => $result['product'],
            'suppliers' => $result['suppliers'],
            'types' => Product::TYPES,
        ]);
    }

    public function updateProduct(Request $request, int $id)
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:tbl_suppliers,id'],
            'name' => ['required', 'string', 'max:180'],
            'model' => ['nullable', 'string', 'max:150'],
            'type' => ['required', 'in:inverter,solar_panel,battery,other'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
            'specs' => ['nullable', 'array', 'max:30'],
            'specs.*.spec_name' => ['required_with:specs', 'string', 'max:100'],
            'specs.*.spec_value' => ['required_with:specs', 'string', 'max:150'],
        ]);
        $result = $this->adminService->updateProduct($id, $validated);
        if (!$result['success'])
            return back()->withInput()->with('error', $result['message']);
        return redirect()->route('admin.products.index')->with('success', $result['message']);
    }

    public function destroyProduct(int $id)
    {
        $result = $this->adminService->destroyProduct($id);
        if (!$result['success'])
            return back()->with('error', $result['message']);
        return redirect()->route('admin.products.index')->with('success', $result['message']);
    }

    //Supplier and Products Managment End

    //Company Carousel Start

    public function companyCarouselIndex()
    {
        $result = $this->adminService->getCompanyCarouselPageData();
        return view('admin.company-carousel.index', ['entries' => $result['entries']]);
    }

    public function companyCarouselCreate()
    {
        $result = $this->adminService->getCompanyCarouselFormData();
        $nextSortOrder = $this->adminService->getNextCompanyCarouselSortOrder();

        return view('admin.company-carousel.form', ['entry' => null, 'companies' => $result['companies'], 'nextSortOrder' => $nextSortOrder,]);
    }

    public function companyCarouselStore(Request $request)
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer', 'min:1'],
            'platform' => ['required', 'in:app,website,both'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'interval_seconds' => ['required', 'integer', 'min:1', 'max:3600'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $result = $this->adminService->createCompanyCarouselEntry($data);
        return $result['success']
            ? redirect()->route('admin.company-carousel.index')->with('success', $result['message'])
            : back()->withInput()->with('error', $result['message']);
    }

    public function companyCarouselEdit(int $id)
    {
        $result = $this->adminService->getCompanyCarouselFormData($id);
        abort_unless($result['entry'], 404);
        return view('admin.company-carousel.form', ['entry' => $result['entry'], 'companies' => $result['companies']]);
    }

    public function companyCarouselUpdate(Request $request, int $id)
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer', 'min:1'],
            'platform' => ['required', 'in:app,website,both'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'interval_seconds' => ['required', 'integer', 'min:1', 'max:3600'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $result = $this->adminService->updateCompanyCarouselEntry($id, $data);
        return $result['success']
            ? redirect()->route('admin.company-carousel.index')->with('success', $result['message'])
            : back()->withInput()->with('error', $result['message']);
    }

    public function companyCarouselDestroy(int $id)
    {
        $result = $this->adminService->deleteCompanyCarouselEntry($id);
        return redirect()->route('admin.company-carousel.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }
    //Company Carousel End

    //App Slider Start

    public function appSliders(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', Rule::in(['live', 'scheduled', 'expired', 'inactive'])],
        ]);
        $result = $this->adminService->getAppSliderPageData($filters);

        return view('admin.app-sliders.index', [
            'sliders' => $result['sliders'],
            'metrics' => $result['metrics'],
            'filters' => $filters,
        ]);
    }

    public function createAppSlider()
    {
        return view('admin.app-sliders.form', [
            'slider' => null,
            'nextSortOrder' => $this->adminService->getNextAppSliderSortOrder(),
            'maxUploadKb' => $this->appSliderMaxUploadKb(),
        ]);
    }

    public function storeAppSlider(Request $request)
    {
        $data = $this->validateAppSlider($request, true);
        $result = $this->adminService->createAppSlider($data, $request->file('image'), Auth::guard('admin')->id());

        return $result['success']
            ? redirect()->route('admin.app-sliders.index')->with('success', $result['message'])
            : back()->withInput()->with('error', $result['message']);
    }

    public function editAppSlider(int $id)
    {
        $slider = $this->adminService->findAppSlider($id);
        abort_unless($slider, 404);

        return view('admin.app-sliders.form', [
            'slider' => $slider,
            'nextSortOrder' => $slider->sort_order,
            'maxUploadKb' => $this->appSliderMaxUploadKb(),
        ]);
    }

    public function updateAppSlider(Request $request, int $id)
    {
        $data = $this->validateAppSlider($request, false);
        $result = $this->adminService->updateAppSlider($id, $data, $request->file('image'));

        return $result['success']
            ? redirect()->route('admin.app-sliders.index')->with('success', $result['message'])
            : back()->withInput()->with('error', $result['message']);
    }

    public function toggleAppSlider(int $id)
    {
        $result = $this->adminService->toggleAppSlider($id);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function destroyAppSlider(int $id)
    {
        $result = $this->adminService->deleteAppSlider($id);

        return redirect()->route('admin.app-sliders.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /** 4 MB, or less when the server's PHP upload limit is lower. */
    private function appSliderMaxUploadKb(): int
    {
        return min(4096, \App\Helpers\Helper::serverUploadLimitKb());
    }

    private function validateAppSlider(Request $request, bool $creating): array
    {
        $maxKb = $this->appSliderMaxUploadKb();
        $maxLabel = round($maxKb / 1024, 1) . ' MB';

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description_ar' => ['nullable', 'string', 'max:255'],
            'description_en' => ['nullable', 'string', 'max:255'],
            // The browser shrinks large images first; the stored copy is converted to a light WebP.
            'image' => [$creating ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . $maxKb, 'dimensions:min_width=600,min_height=200'],
            'has_link' => ['nullable', 'boolean'],
            // Web links, or in-app links such as sunxfinder://company/{slug}.
            'link_url' => ['nullable', 'required_if:has_link,1', 'string', 'max:500', 'regex:#^(https?|sunxfinder)://[^\s]+$#i'],
            'interval_seconds' => ['required', 'integer', 'min:2', 'max:60'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ], [
            'image.dimensions' => 'The slide image must be at least 600 px wide and 200 px tall.',
            'image.uploaded' => "The server rejected the image, usually because it is larger than {$maxLabel}. Choose a smaller image or compress it and try again.",
            'image.max' => "The slide image may not be larger than {$maxLabel}.",
            'link_url.required_if' => 'Enter the link the slide opens, or turn off "Slide opens a link".',
            'link_url.regex' => 'The link must start with https://, http:// or sunxfinder://',
        ]);

        $data['has_link'] = $request->boolean('has_link');
        $data['is_active'] = $request->boolean('is_active');
        unset($data['image']);

        return $data;
    }

    //App Slider End

    //App Versions Start

    public function appVersions()
    {
        $versions = $this->adminService->getAppVersions();

        // Only one version row per platform; hide "Add" once both exist.
        $missingPlatforms = array_diff_key(
            AppVersion::PLATFORMS,
            $versions->keyBy('platform')->all()
        );

        $apk = \App\Support\ApkDownload::info();
        $apkChunkBytes = \App\Support\ApkDownload::chunkBytes();
        $playStoreButton = \App\Models\Setting::showPlayStoreButton();
        $playStoreUrl = $versions->firstWhere('platform', 'android')?->app_url;

        return view('admin.app-versions.index', compact('versions', 'missingPlatforms', 'apk', 'apkChunkBytes', 'playStoreButton', 'playStoreUrl'));
    }

    public function createAppVersion()
    {
        return view('admin.app-versions.form', ['version' => null]);
    }

    public function storeAppVersion(Request $request)
    {
        $data = $this->validatedAppVersion($request);

        $result = $this->adminService->createAppVersion($data);

        return $result['success']
            ? redirect()->route('admin.app-versions.index')->with('success', $result['message'])
            : back()->withInput()->with('error', $result['message']);
    }

    public function editAppVersion(int $id)
    {
        $version = $this->adminService->findAppVersion($id);

        abort_if(!$version, 404);

        return view('admin.app-versions.form', compact('version'));
    }

    public function updateAppVersion(Request $request, int $id)
    {
        $data = $this->validatedAppVersion($request, $id);

        $result = $this->adminService->updateAppVersion($id, $data);

        return $result['success']
            ? redirect()->route('admin.app-versions.index')->with('success', $result['message'])
            : back()->withInput()->with('error', $result['message']);
    }

    public function destroyAppVersion(int $id)
    {
        $result = $this->adminService->deleteAppVersion($id);

        return redirect()->route('admin.app-versions.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    private function validatedAppVersion(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'platform' => [
                'required',
                Rule::in(array_keys(AppVersion::PLATFORMS)),
                Rule::unique('tbl_app_versions', 'platform')->ignore($ignoreId)->whereNull('deleted_at'),
            ],
            // e.g. 1.6, 1.6.2, 2.0.0.1
            'minimum_version' => ['required', 'string', 'max:20', 'regex:/^\d+(\.\d+){0,3}$/'],
            'app_url' => ['required', 'url:https', 'max:500'],
            'force_update' => ['nullable', 'boolean'],
            'description_en' => ['nullable', 'string', 'max:500'],
            'description_ar' => ['nullable', 'string', 'max:500'],
        ], [
            'platform.unique' => 'A version for this platform already exists. Edit it instead.',
            'minimum_version.regex' => 'Use numbers separated by dots, e.g. 1.6.2.',
            'app_url.url' => 'Enter a valid https:// store link.',
        ]);

        $data['force_update'] = $request->boolean('force_update');

        return $data;
    }

    //App Versions End

    //APK Direct Download Start

    public function uploadApkChunk(Request $request)
    {
        $data = $request->validate([
            'upload_id' => ['required', 'string', 'regex:/^[A-Za-z0-9]{16,64}$/'],
            'index' => ['required', 'integer', 'min:0', 'max:5000'],
            'chunk' => ['required', 'file'],
        ]);

        $result = $this->adminService->storeApkChunk(
            $data['upload_id'],
            (int) $data['index'],
            $request->file('chunk')->getRealPath()
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function finishApkUpload(Request $request)
    {
        $data = $request->validate([
            'upload_id' => ['required', 'string', 'regex:/^[A-Za-z0-9]{16,64}$/'],
            'total' => ['required', 'integer', 'min:1', 'max:5000'],
            'version' => ['nullable', 'string', 'max:20', 'regex:/^\d+(\.\d+){0,3}$/'],
        ], [
            'version.regex' => 'Use numbers separated by dots, e.g. 1.6.2.',
        ]);

        $result = $this->adminService->finishApkUpload($data['upload_id'], (int) $data['total'], $data['version'] ?? null);

        if ($result['success']) {
            session()->flash('success', $result['message']);
        }

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function toggleApkDownload(Request $request)
    {
        $result = $this->adminService->setApkDownloadEnabled($request->boolean('enabled'));

        return redirect()->route('admin.app-versions.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function togglePlayStoreButton(Request $request)
    {
        $result = $this->adminService->setPlayStoreButtonEnabled($request->boolean('enabled'));

        return redirect()->route('admin.app-versions.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function deleteApk()
    {
        $result = $this->adminService->deleteApk();

        return redirect()->route('admin.app-versions.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    //APK Direct Download End

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

        $quotes = $this->adminService->getQuoteRequests($filters);
        $metrics = $this->adminService->getQuoteRequestMetrics();

        return view('admin.quote-requests.index', compact(
            'quotes',
            'metrics',
            'filters'
        ));
    }

    public function quoteRequestDetails(int $id)
    {
        $quote = $this->adminService->getQuoteRequestById($id);

        abort_if(!$quote, 404);

        $assignableCompanies = $this->adminService->getAssignableCompanies();
        $nudgedAt = $this->adminService->getQuoteNudgedAt($id);

        return view('admin.quote-requests.form', compact('quote', 'assignableCompanies', 'nudgedAt'));
    }

    public function projects(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['accepted', 'in_progress', 'completed'])],
            'company_id' => ['nullable', 'integer', 'min:1'],
            'customer' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
            'last_activity_from' => ['nullable', 'date'],
            'last_activity_to' => ['nullable', 'date', 'after_or_equal:last_activity_from'],
            'stalled' => ['nullable', 'boolean'],
        ]);

        $projects = $this->adminService->getAdminProjects($filters);
        $metrics = $this->adminService->getAdminProjectMetrics();
        $companies = $this->adminService->getAdminProjectCompanies();
        $stalledDays = \App\Support\ReportPeriod::STALLED_DAYS;

        return view('admin.projects.index', compact('projects', 'metrics', 'companies', 'filters', 'stalledDays'));
    }

    public function projectDetails(int $id)
    {
        $project = $this->adminService->getAdminProjectById($id);

        abort_if(!$project, 404);

        $stalledDays = \App\Support\ReportPeriod::STALLED_DAYS;
        $lastActivity = $project->updates->first()?->created_at ?? $project->updated_at;
        $isStalled = in_array($project->status, ['accepted', 'in_progress'], true)
            && $lastActivity?->lt(now()->subDays($stalledDays));

        return view('admin.projects.show', compact('project', 'stalledDays', 'lastActivity', 'isStalled'));
    }

    public function complaints(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'in_progress', 'resolved', 'rejected'])],
            'category' => ['nullable', Rule::in(['product_issue', 'service_issue', 'pricing_issue', 'delivery_issue', 'customer_service', 'other'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $query = CustomerComplaint::query()->with(['company:id,name', 'customer:id,full_name,username,email,phone']);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['category'])) $query->where('category', $filters['category']);
        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(fn ($q) => $q->where('description', 'like', $term)
                ->orWhere('id', is_numeric($filters['search']) ? (int) $filters['search'] : -1)
                ->orWhereHas('company', fn ($company) => $company->where('name', 'like', $term))
                ->orWhereHas('customer', fn ($customer) => $customer->where('email', 'like', $term)->orWhere('username', 'like', $term)));
        }
        $complaints = $query->latest()->paginate(20)->withQueryString();
        return view('admin.complaints.index', compact('complaints', 'filters'));
    }

    public function complaintDetails(int $id)
    {
        $complaint = CustomerComplaint::with(['company', 'customer'])->findOrFail($id);
        return view('admin.complaints.show', compact('complaint'));
    }

    public function updateComplaint(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'in_progress', 'resolved', 'rejected'])],
            'reply' => ['nullable', 'string', 'max:5000'],
            'reply_channel' => ['nullable', Rule::in(['notification', 'email'])],
        ]);
        $complaint = CustomerComplaint::with(['company', 'customer'])->findOrFail($id);
        $complaint->status = $data['status'];
        if (array_key_exists('reply', $data) && $data['reply'] !== null && trim($data['reply']) !== '') {
            $complaint->admin_reply = trim($data['reply']);
        }
        $complaint->save();

        if (!empty($data['reply'])) {
            $channel = $data['reply_channel'] ?? 'notification';
            if ($channel === 'email') {
                abort_unless(filter_var($complaint->customer?->email, FILTER_VALIDATE_EMAIL), 422, 'The customer has no valid email address.');
                Mail::raw($data['reply'], function ($mail) use ($complaint) {
                    $mail->to($complaint->customer->email)->subject('SunXFinder complaint update #' . $complaint->id);
                });
            } else {
                $this->adminService->publishNotification([
                    'sender_type' => 'admin',
                    'sender_id' => Auth::id(),
                    'audience_type' => 'selected',
                    'customer_ids' => [$complaint->customer_id],
                    'type' => 'support',
                    'title_en' => 'Complaint update',
                    'title_ar' => 'تحديث بشأن الشكوى',
                    'message_en' => 'Complaint #' . $complaint->id . ': ' . $data['reply'],
                    'message_ar' => 'الشكوى رقم ' . $complaint->id . ': ' . $data['reply'],
                ]);
            }
            $complaint->forceFill(['customer_replied_at' => now()])->save();
        }
        return back()->with('success', 'Complaint updated successfully.');
    }

    public function emailComplaintToCompany(int $id)
    {
        $complaint = CustomerComplaint::with('company')->findOrFail($id);
        abort_unless(filter_var($complaint->company?->email, FILTER_VALIDATE_EMAIL), 422, 'The company has no valid email address.');
        $categories = [
            'product_issue' => 'Product issue', 'service_issue' => 'Service issue', 'pricing_issue' => 'Pricing issue',
            'delivery_issue' => 'Delivery issue', 'customer_service' => 'Customer service', 'other' => 'Other',
        ];
        $description = $complaint->description;
        $customerName = trim((string) ($complaint->customer?->full_name ?? ''));
        if ($customerName !== '') {
            $description = str_ireplace($customerName, '[customer]', $description);
        }
        $body = "A customer submitted a complaint related to your company. The customer's name and contact details are intentionally omitted.\n\n"
            . 'Complaint reference: #' . $complaint->id . "\n"
            . 'Category: ' . ($categories[$complaint->category] ?? $complaint->category) . "\n\n"
            . "Complaint details:\n" . $description . "\n\nPlease review this issue and follow up through your SunXFinder company account.";
        Mail::raw($body, function ($mail) use ($complaint) {
            $mail->to($complaint->company->email)->subject('Customer complaint #' . $complaint->id . ' - SunXFinder');
        });
        $complaint->forceFill(['company_notified_at' => now()])->save();
        return back()->with('success', 'The company was emailed without customer identifying details.');
    }

    public function cancelQuoteRequest(Request $request, int $id)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:300'],
            'notify_customer' => ['nullable', 'boolean'],
        ], [
            'reason.required' => 'Write why the request is cancelled (kept in the activity log).',
        ]);

        $result = $this->adminService->cancelQuoteRequest($id, $data['reason'], $request->boolean('notify_customer'));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function reassignQuoteRequest(Request $request, int $id)
    {
        $data = $request->validate([
            // Empty = open to all companies
            'company_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $result = $this->adminService->reassignQuoteRequest($id, isset($data['company_id']) ? (int) $data['company_id'] : null);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function nudgeCompaniesForQuote(int $id)
    {
        $result = $this->adminService->nudgeCompaniesForQuote($id);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    //Background Jobs Start

    public function backgroundJobs(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $overview = $this->adminService->getQueueOverview();
        $failedJobs = $this->adminService->getFailedJobs($filters);

        return view('admin.queue.index', compact('overview', 'failedJobs'));
    }

    public function showFailedJob(string $uuid)
    {
        $job = $this->adminService->getFailedJob($uuid);

        abort_if(!$job, 404);

        return view('admin.queue.show', compact('job'));
    }

    public function retryFailedJob(string $uuid)
    {
        $result = $this->adminService->retryFailedJobs([$uuid]);

        return redirect()->route('admin.queue.index')->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function retryAllFailedJobs()
    {
        $result = $this->adminService->retryFailedJobs('all');

        return redirect()->route('admin.queue.index')->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function deleteFailedJob(string $uuid)
    {
        $result = $this->adminService->deleteFailedJobs([$uuid]);

        return redirect()->route('admin.queue.index')->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function deleteAllFailedJobs()
    {
        $result = $this->adminService->deleteFailedJobs('all');

        return redirect()->route('admin.queue.index')->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    //Background Jobs End

    //Quote Requests End

    //Ratings Start

    public function ratings(Request $request)
    {
        $filters = $request->validate([
            'company_id' => ['nullable', 'integer', 'min:1'],
            'stars' => ['nullable', 'integer', 'between:1,5'],
            'visibility' => ['nullable', 'in:visible,hidden'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $ratings = $this->adminService->getRatings($filters);
        $metrics = $this->adminService->getRatingMetrics();
        $companies = $this->adminService->getRatingCompanyOptions();

        return view('admin.ratings.index', compact('ratings', 'metrics', 'companies', 'filters'));
    }

    public function showRating(int $id)
    {
        $rating = $this->adminService->getRatingById($id);

        abort_if(!$rating, 404);

        $valueLabels = RatingValue::labels();

        return view('admin.ratings.show', compact('rating', 'valueLabels'));
    }

    public function toggleRatingVisibility(int $id)
    {
        $rating = $this->adminService->getRatingById($id);

        abort_if(!$rating, 404);

        $result = $this->adminService->setRatingVisibility($id, !$rating->is_visible);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function destroyRating(int $id)
    {
        $result = $this->adminService->deleteRating($id);

        return redirect()->route('admin.ratings.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    //Ratings End

    //Rating Questions Start

    public function ratingQuestions()
    {
        $questions = $this->adminService->getRatingQuestions();
        $valueLabels = RatingValue::labels();

        return view('admin.rating-questions.index', compact('questions', 'valueLabels'));
    }

    public function createRatingQuestion()
    {
        return view('admin.rating-questions.form', ['question' => null]);
    }

    public function storeRatingQuestion(Request $request)
    {
        $result = $this->adminService->createRatingQuestion($this->validatedRatingQuestion($request));

        return $result['success']
            ? redirect()->route('admin.rating-questions.index')->with('success', $result['message'])
            : back()->withInput()->with('error', $result['message']);
    }

    public function editRatingQuestion(int $id)
    {
        $question = $this->adminService->findRatingQuestion($id);

        abort_if(!$question, 404);

        return view('admin.rating-questions.form', compact('question'));
    }

    public function updateRatingQuestion(Request $request, int $id)
    {
        $result = $this->adminService->updateRatingQuestion($id, $this->validatedRatingQuestion($request));

        return $result['success']
            ? redirect()->route('admin.rating-questions.index')->with('success', $result['message'])
            : back()->withInput()->with('error', $result['message']);
    }

    public function destroyRatingQuestion(int $id)
    {
        $result = $this->adminService->deleteRatingQuestion($id);

        return redirect()->route('admin.rating-questions.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    private function validatedRatingQuestion(Request $request): array
    {
        $data = $request->validate([
            'title_en' => ['required', 'string', 'max:100'],
            'title_ar' => ['required', 'string', 'max:100'],
            'question_en' => ['nullable', 'string', 'max:255'],
            'question_ar' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    //Rating Questions End

    //Rating Values Start

    public function ratingValues()
    {
        $values = $this->adminService->getRatingValues();

        return view('admin.rating-values.index', compact('values'));
    }

    public function updateRatingValues(Request $request)
    {
        $range = range(RatingValue::MIN, RatingValue::MAX);

        $rules = [];

        foreach ($range as $value) {
            $rules["values.{$value}.label_en"] = ['required', 'string', 'max:50'];
            $rules["values.{$value}.label_ar"] = ['required', 'string', 'max:50'];
        }

        $data = $request->validate($rules, [
            'values.*.label_en.required' => 'Every value needs an English label.',
            'values.*.label_ar.required' => 'Every value needs an Arabic label.',
        ]);

        $result = $this->adminService->updateRatingValues(array_intersect_key($data['values'], array_flip($range)));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    //Rating Values End

    //Settings Start

    public function settings()
    {
        $settings = $this->adminService->getSettings();

        return view('admin.settings.index', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'max_open_quotes' => ['required', 'integer', 'min:1', 'max:50'],
            'maintenance_enabled' => ['nullable', 'boolean'],
            'maintenance_title_en' => ['nullable', 'required_if:maintenance_enabled,1', 'string', 'max:150'],
            'maintenance_title_ar' => ['nullable', 'required_if:maintenance_enabled,1', 'string', 'max:150'],
            'maintenance_message_en' => ['nullable', 'required_if:maintenance_enabled,1', 'string', 'max:2000'],
            'maintenance_message_ar' => ['nullable', 'required_if:maintenance_enabled,1', 'string', 'max:2000'],
            'maintenance_until' => ['nullable', 'date'],
        ], [
            'maintenance_title_en.required_if' => 'Write the English maintenance title.',
            'maintenance_title_ar.required_if' => 'Write the Arabic maintenance title.',
            'maintenance_message_en.required_if' => 'Write the English maintenance message.',
            'maintenance_message_ar.required_if' => 'Write the Arabic maintenance message.',
        ]);

        $data['maintenance_enabled'] = $request->boolean('maintenance_enabled');

        $result = $this->adminService->updateSettings($data);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /** Raise (or reset with an empty limit) one customer's open-request limit. */
    public function updateCustomerQuoteLimit(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:' . \App\Models\Setting::MAX_OPEN_QUOTES_PER_CUSTOMER],
        ], [
            'customer_id.required' => 'Choose a customer.',
        ]);

        $result = $this->adminService->setCustomerQuoteLimit(
            (int) $data['customer_id'],
            isset($data['limit']) ? (int) $data['limit'] : null
        );

        return redirect()->route('admin.settings.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    // Send Email (System → Send Email): admin writes to a solar company from info@

    public function sendEmail(Request $request)
    {
        $companies = $this->adminService->getCompanyEmailOptions();
        $prefillEmail = $request->query('email');
        $prefillEmail = is_string($prefillEmail) && filter_var($prefillEmail, FILTER_VALIDATE_EMAIL)
            ? $prefillEmail
            : '';
        $sentEmails = SentEmail::query()->latest('sent_at')->paginate(15);

        return view('admin.send-email.index', compact('companies', 'prefillEmail', 'sentEmails'));
    }

    public function sendEmailSubmit(Request $request)
    {
        $rawEmails = $request->input('email', '');
        $parsedEmails = is_string($rawEmails)
            ? preg_split('/[,\s]+/', trim($rawEmails), -1, PREG_SPLIT_NO_EMPTY)
            : [];

        $emails = [];
        $seenEmails = [];
        foreach ($parsedEmails as $email) {
            $email = trim($email);
            $normalizedEmail = strtolower($email);
            if ($email !== '' && ! isset($seenEmails[$normalizedEmail])) {
                $emails[] = $email;
                $seenEmails[$normalizedEmail] = true;
            }
        }

        $request->merge(['recipient_emails' => $emails]);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'max:5000'],
            'recipient_emails' => ['required', 'array', 'min:1', 'max:50'],
            'recipient_emails.*' => ['required', 'email', 'max:191'],
            'lang' => ['required', 'in:en,ar'],
            'message' => ['required', 'string', 'max:5000'],
        ], [
            'recipient_emails.max' => 'You can send to up to 50 email addresses at a time.',
            'recipient_emails.*.email' => 'Enter valid email addresses separated by commas or spaces.',
        ]);
        $data['emails'] = $data['recipient_emails'];

        $result = $this->adminService->sendCompanyEmail($data);

        return $result['success']
            ? redirect()->route('admin.send-email.index')->with('success', $result['message'])
            : back()->withInput()->with('error', $result['message']);
    }

    public function resendSentEmail(SentEmail $sentEmail)
    {
        $result = $this->adminService->sendCompanyEmail([
            'emails' => [$sentEmail->recipient_email],
            'subject' => $sentEmail->subject,
            'message' => $sentEmail->message,
            'lang' => $sentEmail->lang,
            'company_name' => $sentEmail->recipient_name,
        ]);

        return redirect()->route('admin.send-email.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function deleteSentEmail(SentEmail $sentEmail)
    {
        app(\App\Helpers\Helper::class)->archiveItem($sentEmail, 'sent_email', null, 'website');
        $sentEmail->delete();

        return redirect()->route('admin.send-email.index')->with('success', 'Sent email removed from history.');
    }

    // API endpoints (Settings → API selector / accessibility)

    public function storeApiEndpoint(Request $request)
    {
        $result = $this->adminService->createApiEndpoint($this->validatedApiEndpoint($request));

        return redirect()->to(route('admin.settings.index') . '#api-endpoints')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function updateApiEndpoint(Request $request, int $id)
    {
        $result = $this->adminService->updateApiEndpoint($id, $this->validatedApiEndpoint($request, $id));

        return redirect()->to(route('admin.settings.index') . '#api-endpoints')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function activateApiEndpoint(int $id)
    {
        $result = $this->adminService->activateApiEndpoint($id);

        return redirect()->to(route('admin.settings.index') . '#api-endpoints')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function destroyApiEndpoint(int $id)
    {
        $result = $this->adminService->deleteApiEndpoint($id);

        return redirect()->to(route('admin.settings.index') . '#api-endpoints')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    private function validatedApiEndpoint(Request $request, ?int $ignoreId = null): array
    {
        // Compare URLs the way they are saved (no trailing slash).
        $request->merge(['base_url' => rtrim(trim((string) $request->input('base_url')), '/')]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9 _-]+$/'],
            'base_url' => [
                'required',
                'url:https',
                'max:191',
                Rule::unique('tbl_api_endpoints', 'base_url')->ignore($ignoreId),
            ],
            'is_online' => ['nullable', 'boolean'],
            'offline_message_en' => ['nullable', 'string', 'max:500'],
            'offline_message_ar' => ['nullable', 'string', 'max:500'],
        ], [
            'name.regex' => 'Use letters, numbers, spaces, - or _ (e.g. PRD, DEV).',
            'base_url.url' => 'Enter a full https:// address, e.g. https://sunxfinder.com/api/v1/customer.',
            'base_url.unique' => 'This API address is already in the list.',
        ]);

        $data['is_online'] = $request->boolean('is_online');

        return $data;
    }

    //Settings End

    //Database Backups Start

    public function databaseBackups()
    {
        $backups = $this->adminService->getDatabaseBackups();

        return view('admin.backups.index', compact('backups'));
    }

    public function createDatabaseBackup()
    {
        $result = $this->adminService->createDatabaseBackup();

        return redirect()->route('admin.backups.index')->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function downloadDatabaseBackup(string $file)
    {
        $path = $this->adminService->getDatabaseBackupPath($file);

        abort_if(!$path, 404);

        return response()->download($path, $file, ['Content-Type' => 'application/sql']);
    }

    public function deleteDatabaseBackup(string $file)
    {
        $result = $this->adminService->deleteDatabaseBackup($file);

        return redirect()->route('admin.backups.index')->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    //Database Backups End

    //Archives Start

    public function archives(Request $request)
    {
        $filters = $request->validate([
            'type' => ['nullable', 'string', Rule::in([
                'customer', 'product', 'prodcut', 'product_spec', 'offer', 'requestd_quote', 'requested_quote',
                'notification', 'customer_notification', 'company', 'project', 'project_milestone', 'project_update',
                'rating', 'rating_answer', 'supplier', 'appliance', 'appliance_group', 'company_carousel', 'app_slider', 'app_version',
                'rating_question', 'customer_device_token', 'api_endpoint', 'sent_email', 'other',
            ])],
            'source' => ['nullable', Rule::in(['website', 'app'])],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $archives = $this->adminService->getArchivedItems($filters);
        $types = [
            'customer' => 'Customer', 'product' => 'Product', 'prodcut' => 'Product (legacy)', 'product_spec' => 'Product spec',
            'offer' => 'Offer', 'requestd_quote' => 'Quote request (app)', 'requested_quote' => 'Quote request',
            'notification' => 'Notification', 'customer_notification' => 'Customer notification', 'company' => 'Company',
            'project' => 'Project', 'project_milestone' => 'Project milestone', 'project_update' => 'Project update',
            'rating' => 'Rating', 'rating_answer' => 'Rating answer', 'supplier' => 'Supplier', 'appliance' => 'Appliance',
            'appliance_group' => 'Appliance group', 'company_carousel' => 'Company carousel', 'app_slider' => 'App slide', 'app_version' => 'App version',
            'rating_question' => 'Rating question', 'customer_device_token' => 'Device token', 'api_endpoint' => 'API endpoint',
            'sent_email' => 'Sent email', 'other' => 'Other',
        ];

        return view('admin.archives.index', compact('archives', 'filters', 'types'));
    }

    public function deleteArchivedItem(ArchivedItem $archivedItem)
    {
        $this->adminService->deleteArchivedItem($archivedItem);

        return redirect()->route('admin.archives.index', request()->query())->with('success', 'Archive entry moved to trash.');
    }

    //Archives End

    //Activity Logs Start

    public function activityLogs(Request $request)
    {
        $filters = $this->validatedActivityLogFilters($request);

        $logs = $this->adminService->getActivityLogs($filters);
        $metrics = $this->adminService->getActivityLogMetrics();

        return view('admin.logs.index', compact('logs', 'metrics', 'filters'));
    }

    public function showActivityLog(int $id)
    {
        $log = $this->adminService->getActivityLogById($id);

        abort_if(!$log, 404);

        return view('admin.logs.show', compact('log'));
    }

    public function exportActivityLogs(Request $request)
    {
        $logs = $this->adminService->activityLogsForExport($this->validatedActivityLogFilters($request));

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM so Excel shows Arabic names correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID', 'Date', 'Actor type', 'Actor', 'Action', 'Category', 'Record', 'Description', 'IP address', 'Method', 'URL']);

            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->id,
                    $log->created_at?->format('Y-m-d H:i:s'),
                    ActivityLog::ACTOR_TYPES[$log->actor_type] ?? $log->actor_type,
                    $log->actor_name,
                    ActivityLog::ACTIONS[$log->action] ?? $log->action,
                    ActivityLog::CATEGORIES[$log->category] ?? $log->category,
                    $log->subject_type ? "{$log->subject_type} #{$log->subject_id} {$log->subject_label}" : '',
                    $log->description,
                    $log->ip_address,
                    $log->method,
                    $log->url,
                ]);
            }

            fclose($out);
        }, 'activity-logs-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validatedActivityLogFilters(Request $request): array
    {
        return $request->validate([
            'actor_type' => ['nullable', Rule::in(array_keys(ActivityLog::ACTOR_TYPES))],
            'actor_id' => ['nullable', 'integer', 'min:1'],
            'action' => ['nullable', Rule::in(array_keys(ActivityLog::ACTIONS))],
            'category' => ['nullable', Rule::in(array_keys(ActivityLog::CATEGORIES))],
            'subject_type' => ['nullable', 'string', 'max:60', 'alpha'],
            'subject_id' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
    }

    //Activity Logs End

      // Notifications Management Start

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
                    'offer',
                    'new_company',
                ]),
            ],
            'status' => ['nullable', Rule::in(['published', 'draft'])],
            'search' => ['nullable', 'string', 'max:150'],
        ]);

        $result = $this->adminService->getNotifications($filters);

        return view('admin.notifications.index', [
            'notifications' => $result['notifications'],
            'publishedCount' => $result['publishedCount'],
            'draftCount' => $result['draftCount'],
            'recipientCount' => $result['recipientCount'],
        ]);
    }

    public function createNotification()
    {
        $result = $this->adminService->getNotificationFormData();

        return view('admin.notifications.create', [
            'customers' => $result['customers'],
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

        $result = $this->adminService->publishNotification($validated);

        if (!$result['success']) {
            return back()
                ->withInput()
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', $result['message']);
    }

    public function showNotification(int $notification)
    {
        $result = $this->adminService->getNotificationDetails($notification);

        if (!$result['success']) {
            return redirect()
                ->route('admin.notifications.index')
                ->with('error', $result['message']);
        }

        return view('admin.notifications.show', [
            'notification' => $result['notification'],
            'readCount' => $result['readCount'],
            'unreadCount' => $result['unreadCount'],
            'recipients' => $result['recipients'],
        ]);
    }

    public function destroyNotification(int $notification)
    {
        $result = $this->adminService->deleteNotification($notification);

        return redirect()->route('admin.notifications.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    // Customer campaigns: rich notifications (offer, new company, news) with image and link.
    public function createCampaign(Request $request)
    {
        $data = $this->adminService->getCampaignFormData();

        return view('admin.notifications.campaign', $data + [
            'maxUploadKb' => $this->appSliderMaxUploadKb(),
            // Prefill from Customers ("send to this customer") or a company link.
            'presetCustomerId' => $request->integer('customer_id') ?: null,
            'presetCompanyId' => $request->integer('company_id') ?: null,
        ]);
    }

    public function storeCampaign(Request $request)
    {
        $maxKb = $this->appSliderMaxUploadKb();

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(\App\Models\Notification::CAMPAIGN_TYPES))],
            'audience_type' => ['required', Rule::in(['all', 'city', 'selected'])],
            'audience_city' => ['nullable', 'required_if:audience_type,city', 'string', 'max:100'],
            'customer_ids' => ['nullable', 'required_if:audience_type,selected', 'array', 'min:1'],
            'customer_ids.*' => ['integer', 'min:1'],
            'title_en' => ['required', 'string', 'max:180'],
            'title_ar' => ['nullable', 'string', 'max:180'],
            'format' => ['required', Rule::in(['text', 'html'])],
            'message_en' => ['required', 'string', 'max:5000'],
            'message_ar' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . $maxKb, 'dimensions:min_width=300,min_height=150'],
            'has_link' => ['nullable', 'boolean'],
            'link_url' => ['nullable', 'required_if:has_link,1', 'string', 'max:500', 'regex:#^(https?|sunxfinder)://[^\s]+$#i'],
            'link_label_en' => ['nullable', 'string', 'max:60'],
            'link_label_ar' => ['nullable', 'string', 'max:60'],
            'company_id' => ['nullable', Rule::requiredIf(in_array($request->input('type'), ['offer', 'new_company'], true)), 'integer', Rule::exists('tbl_solar_companies', 'id')->whereNull('deleted_at')],
            'has_offer' => ['nullable', 'boolean'],
            'offer_title_en' => ['nullable', 'required_if:has_offer,1', 'string', 'max:150'],
            'offer_title_ar' => ['nullable', 'string', 'max:150'],
            'offer_price' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'offer_old_price' => ['nullable', 'numeric', 'min:0', 'max:999999999', 'gt:offer_price'],
            'offer_currency' => ['nullable', Rule::in(['SDG', 'USD'])],
            'offer_expires_at' => ['nullable', 'date', 'after:now'],
        ], [
            'image.uploaded' => 'The image could not be uploaded. The server accepts files up to ' . round($maxKb / 1024, 1) . ' MB.',
            'image.max' => 'The image must be ' . round($maxKb / 1024, 1) . ' MB or smaller.',
            'link_url.regex' => 'The link must start with https:// (or sunxfinder:// for a screen in the app).',
            'company_id.required' => 'Choose the company this campaign is about.',
            'offer_title_en.required_if' => 'Give the offer a short title, e.g. "20% off 5 kW systems".',
            'offer_old_price.gt' => 'The old price must be higher than the offer price.',
            'audience_city.required_if' => 'Choose the city to send to.',
            'customer_ids.required_if' => 'Select at least one customer.',
        ]);

        $result = $this->adminService->publishCampaign($data, $request->file('image'), Auth::guard('admin')->id());

        return redirect()
            ->route('admin.notifications.show', $result['notification_id'])
            ->with('success', $result['message']);
    }

    // Notifications Management End



}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AppVersion;
use App\Repositories\Admin\AdminRepository;
use App\Models\Customer;
use App\Models\Product;
use App\Models\RatingValue;
use App\Models\SolarCompany;
use App\Services\Admin\AdminService;
use Illuminate\Http\Request;
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

    /** One piece of a chunked APK upload (see ApkDownload::chunkBytes()). */
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

    public function sendEmail()
    {
        $companies = $this->adminService->getCompanyEmailOptions();

        return view('admin.send-email.index', compact('companies'));
    }

    public function sendEmailSubmit(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:191'],
            'lang' => ['required', 'in:en,ar'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $result = $this->adminService->sendCompanyEmail($data);

        return $result['success']
            ? redirect()->route('admin.send-email.index')->with('success', $result['message'])
            : back()->withInput()->with('error', $result['message']);
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

    // Notifications Management End



}

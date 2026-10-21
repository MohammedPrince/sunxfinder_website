<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
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
            'status' => ['required', 'in:pending,approved,rejected'],
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
            'name' => ['required', 'string', 'max:200'],
            'username' => ['required', 'string', 'max:191'],
            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'license_number' => ['required', 'string', 'max:191'],
            'services' => ['required', 'string', 'max:5000'],
            'ar_services' => ['required', 'string', 'max:5000'],
            'description' => ['nullable', 'string'],
            'ar_description' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8'],
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
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
                'required',
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

    public function showCustomers()
    {
        $customers = $this->adminService->getCustomers();

        $totalCount = $this->adminService->customerAccountCount();
        $activeCount = $this->adminService->activeCustomerAccountCount();
        $pendingCount = $this->adminService->pendingCustomerAccountCount();
        $inactiveCount = $this->adminService->inactiveCustomerAccountCount();

        return view('admin.customers.index', compact(
            'customers',
            'totalCount',
            'activeCount',
            'pendingCount',
            'inactiveCount'
        ));
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
                'required',
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

        return view('admin.quote-requests.form', compact('quote'));
    }

    //Quote Requests End

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

    // Notifications Management End



}

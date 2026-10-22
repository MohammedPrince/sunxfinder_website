<?php

namespace App\Repositories\Admin;

use App\Models\Appliance;
use App\Models\ApplianceGroup;
use App\Models\AppVersion;
use App\Models\CompanyCarousel;
use App\Models\Customer;
use App\Models\CustomerNotification;
use App\Models\Notification;
use App\Models\Product;
use App\Models\ProductSpec;
use App\Models\Quote;
use App\Models\SolarCompany;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Builder;
use App\Models\CustomerDeviceToken;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class AdminRepository
{
    public function __construct()
    {
        //
    }

    //Dashboard Start

    public function getDashboardData(): array
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonthNoOverflow()->startOfMonth();

        // Percentage change of records created this month against last month.
        $monthlyChange = function (string $modelClass) use ($thisMonth, $lastMonth): ?int {
            $current = $modelClass::query()->where('created_at', '>=', $thisMonth)->count();
            $previous = $modelClass::query()
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            return $previous > 0
                ? (int) round(($current - $previous) / $previous * 100)
                : null;
        };

        $companyCounts = SolarCompany::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $companies = [
            'total' => (int) $companyCounts->sum(),
            'approved' => (int) ($companyCounts['approved'] ?? 0),
            'pending' => (int) ($companyCounts['pending'] ?? 0),
            'new_this_month' => SolarCompany::query()->where('created_at', '>=', $thisMonth)->count(),
            'change' => $monthlyChange(SolarCompany::class),
        ];
        // Rejected and any other non-approved, non-pending status.
        $companies['other'] = $companies['total'] - $companies['approved'] - $companies['pending'];

        $quotes = [
            'total' => Quote::query()->count(),
            'pending' => Quote::query()->where('status', 'pending')->count(),
            'change' => $monthlyChange(Quote::class),
        ];

        $customers = [
            'total' => $this->customerAccountCount(),
            'active' => $this->activeCustomerAccountCount(),
            'change' => $monthlyChange(Customer::class),
        ];

        // Daily activity for the last 7 days, including today.
        $start = now()->subDays(6)->startOfDay();

        $perDay = fn(string $modelClass) => $modelClass::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $requestsPerDay = $perDay(Quote::class);
        $companiesPerDay = $perDay(SolarCompany::class);

        $activity = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->toDateString();

            $activity[] = [
                'label' => $date->format('D'),
                'date' => $date->format('d M'),
                'requests' => (int) ($requestsPerDay[$key] ?? 0),
                'companies' => (int) ($companiesPerDay[$key] ?? 0),
            ];
        }

        $latestCompanies = SolarCompany::query()
            ->latest('created_at')
            ->latest('id')
            ->limit(5)
            ->get(['id', 'name', 'username', 'city', 'status', 'is_verified', 'is_active', 'created_at']);

        return [
            'companies' => $companies,
            'quotes' => $quotes,
            'customers' => $customers,
            'activity' => $activity,
            'latestCompanies' => $latestCompanies,
        ];
    }

    //Dashboard End

    //Compaines Managment Start

    public function getCompanies()
    {
        return SolarCompany::query()
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(10);
    }

    public function verifiedCount()
    {
        return SolarCompany::where('is_verified', true)->count();
    }

    public function pendingCount(): int
    {
        return SolarCompany::where('status', 'pending')->count();
    }

    public function inactiveCount()
    {
        return SolarCompany::where('is_active', false)->count();
    }

    public function addNewCompany(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $baseSlug = Str::slug($data['name']);

                if ($baseSlug === '') {
                    $baseSlug = 'solar-company';
                }

                // Generate a unique slug using the model
                $slug = $baseSlug;
                $counter = 1;

                while (SolarCompany::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter++;
                }

                // Create the company using Eloquent
                SolarCompany::create([
                    'name' => $data['name'],
                    'slug' => $slug,
                    'username' => $data['username'],
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'city' => $data['city'] ?? null,
                    'license_number' => $data['license_number'] ?? null,
                    'services' => $data['services'],
                    'ar_services' => $data['ar_services'],
                    'description' => $data['description'] ?? null,
                    'ar_description' => $data['ar_description'] ?? null,
                    'password' => Hash::make($data['password']),
                    'status' => $data['status'],
                    'is_verified' => (int) $data['is_verified'],
                    'is_active' => (int) $data['is_active'],
                    'year_of_exp' => (int) $data['year_of_exp'],
                    'number_of_projects' => (int) $data['number_of_projects'],

                ]);

                return [
                    'success' => true,
                    'message' => 'Company created successfully.',
                ];
            });
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to create the company. Please check the submitted information and try again.',
            ];
        }
    }
    public function getCompanyById(int $id): array
    {
        $company = SolarCompany::find($id);

        if (!$company) {
            return [
                'success' => false,
                'message' => 'Company not found.',
            ];
        }

        return [
            'success' => true,
            'company' => $company,
        ];
    }

    public function updateCompany(int $id, array $data): array
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $company = SolarCompany::find($id);

                if (!$company) {
                    return [
                        'success' => false,
                        'message' => 'Company not found.',
                    ];
                }

                // Validate unique fields in the repository.
                $validator = Validator::make($data, [
                    'username' => [
                        'required',
                        'string',
                        'max:191',
                        Rule::unique('tbl_solar_companies', 'username')
                            ->ignore($company->id),
                    ],
                    'email' => [
                        'nullable',
                        'email',
                        'max:191',
                        Rule::unique('tbl_solar_companies', 'email')
                            ->ignore($company->id),
                    ],
                ]);

                if ($validator->fails()) {
                    return [
                        'success' => false,
                        'message' => $validator->errors()->first(),
                    ];
                }

                $company->name = $data['name'];
                $company->username = $data['username'];
                $company->email = $data['email'] ?? null;
                $company->phone = $data['phone'] ?? null;
                $company->city = $data['city'] ?? null;
                $company->license_number = $data['license_number'] ?? null;
                $company->services = $data['services'] ?? null;
                $company->ar_services = $data['ar_services'] ?? null;
                $company->description = $data['description'] ?? null;
                $company->ar_description = $data['ar_description'] ?? null;
                $company->status = $data['status'];
                $company->is_verified = (int) $data['is_verified'];
                $company->is_active = (int) $data['is_active'];
                $company->year_of_exp = $data['year_of_exp'] ?? null;
                $company->number_of_projects = $data['number_of_projects'] ?? null;

                // Only change the password when a new one is supplied.
                if (!empty($data['password'])) {
                    $company->password = Hash::make($data['password']);
                }

                $company->save();

                return [
                    'success' => true,
                    'message' => 'Company updated successfully.',
                ];
            });
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to update the company. Please try again.',
            ];
        }
    }
    public function deleteCompany(int $id): array
    {
        try {
            $company = SolarCompany::find($id);

            if (!$company) {
                return [
                    'success' => false,
                    'message' => 'Company not found.',
                ];
            }

            $company->delete();

            return [
                'success' => true,
                'message' => 'Company deleted successfully.',
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to delete the company. Please try again.',
            ];
        }
    }

    //Compaines Managment END

    //Customer Managment START

    public function createCustomer(array $data): array
    {
        try {
            $customer = new Customer();

            $customer->username = $data['username'];
            $customer->full_name = $data['full_name'];
            $customer->phone = $data['phone'] ?? null;
            $customer->email = $data['email'];
            $customer->gender = $data['gender'];
            $customer->city = $data['city'] ?? null;
            $customer->address = $data['address'] ?? null;
            $customer->status = $data['status'];

            // Only active customers can have active accounts.
            $customer->is_active = $data['status'] === 'active' ? 1 : 0;

            // The Customer model's hashed cast hashes this password.
            $customer->password = $data['password'];

            if (!$customer->save()) {
                return [
                    'success' => false,
                    'message' => 'Unable to create the customer.',
                ];
            }

            return [
                'success' => true,
                'message' => 'Customer created successfully.',
                'customer' => $customer,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to create the customer. Please try again.',
            ];
        }
    }

    public function getCustomers(int $perPage = 10)
    {
        return Customer::query()
            ->orderByDesc('created_at')
            ->latest()
            ->paginate($perPage);

    }

    public function customerAccountCount(): int
    {
        return Customer::count();
    }

    public function activeCustomerAccountCount(): int
    {
        return Customer::where('status', 'active')
            ->where('is_active', true)
            ->count();
    }

    public function pendingCustomerAccountCount(): int
    {
        return Customer::where('status', 'pending')->count();
    }

    public function inactiveCustomerAccountCount(): int
    {
        return Customer::where('is_active', false)->count();
    }

    public function getCustomerById(int $id): array
    {
        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return [
                    'success' => false,
                    'message' => 'Customer not found.',
                ];
            }

            return [
                'success' => true,
                'customer' => $customer,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to retrieve the customer.',
            ];
        }
    }

    public function updateCustomer(int $id, array $data): array
    {

        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return [
                    'success' => false,
                    'message' => 'Customer not found.',
                ];
            }

            $customer->username = $data['username'];
            $customer->full_name = $data['full_name'];
            $customer->phone = $data['phone'] ?? null;
            $customer->email = $data['email'];
            $customer->gender = $data['gender'];
            $customer->city = $data['city'] ?? null;
            $customer->address = $data['address'] ?? null;
            $customer->status = $data['status'];

            // Set is_active automatically based on customer status.
            $customer->is_active = ($data['status'] === 'active') ? 1 : 0;

            // Update password only when a new password is provided.
            if (!empty($data['password'])) {
                $customer->password = $data['password'];
            }

            if (!$customer->save()) {
                return [
                    'success' => false,
                    'message' => 'Unable to update the customer.',
                ];
            }

            return [
                'success' => true,
                'message' => 'Customer updated successfully.',
                'customer' => $customer,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to update the customer. Please try again.',
            ];
        }
    }

    public function deleteCustomer(int $id): array
    {
        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return [
                    'success' => false,
                    'message' => 'Customer not found.',
                ];
            }

            $customer->status = 'deleted';
            $customer->is_active = false;

            if (!$customer->save()) {
                return [
                    'success' => false,
                    'message' => 'Unable to delete the customer.',
                ];
            }

            // Soft delete: automatically sets deleted_at.
            if (!$customer->delete()) {
                return [
                    'success' => false,
                    'message' => 'Unable to delete the customer.',
                ];
            }

            return [
                'success' => true,
                'message' => 'Customer deleted successfully.',
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to delete the customer. Please try again.',
            ];
        }
    }

    //Customer Managment End


    // Appliance Groups and Items Start

    public function showApplianceGroups(): array
    {
        try {
            $groups = ApplianceGroup::with('appliances')
                ->withCount('appliances')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(10);

            return [
                'success' => true,
                'message' => 'Appliance groups retrieved successfully.',
                'groups' => $groups,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to retrieve appliance groups.',
                'groups' => null,
            ];
        }
    }

    public function createApplianceGroup(): array
    {
        $lastSortOrder = ApplianceGroup::max('sort_order') ?? 0;

        return [
            'success' => true,
            'message' => 'Appliance group form is ready.',
            'nextSortOrder' => $lastSortOrder + 1,
        ];
    }

    public function storeApplianceGroup(array $data): array
    {
        try {
            $group = DB::transaction(function () use ($data) {
                $group = new ApplianceGroup();

                $group->name = $data['name'];
                $group->ar_name = $data['ar_name'];
                $group->icon = $data['icon'] ?? null;
                $group->sort_order = $data['sort_order'] ?? 0;
                $group->is_active = (bool) $data['is_active'];

                $group->save();

                foreach ($data['appliances'] as $index => $item) {
                    $appliance = new Appliance();

                    $appliance->group_id = $group->id;
                    $appliance->name = $item['name'];
                    $appliance->ar_name = $item['ar_name'];
                    $appliance->watts = (int) $item['watts'];
                    $appliance->sort_order = $item['sort_order'] ?? $index;
                    $appliance->is_active = (bool) $item['is_active'];

                    $appliance->save();
                }

                return $group;
            });

            return [
                'success' => true,
                'message' => 'Appliance group and appliances created successfully.',
                'group' => $group,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to create the appliance group. Please try again.',
            ];
        }
    }
    public function editApplianceGroup(int $id): array
    {
        $group = ApplianceGroup::with('appliances')->find($id);

        if (!$group) {
            return [
                'success' => false,
                'message' => 'Appliance group not found.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Appliance group retrieved successfully.',
            'group' => $group,
        ];
    }

    public function updateApplianceGroup(int $id, array $data): array
    {
        try {
            $result = DB::transaction(function () use ($id, $data) {
                $group = ApplianceGroup::find($id);

                if (!$group) {
                    return [
                        'success' => false,
                        'message' => 'Appliance group not found.',
                    ];
                }

                $group->name = $data['name'];
                $group->ar_name = $data['ar_name'];
                $group->icon = $data['icon'] ?? null;
                $group->sort_order = $data['sort_order'] ?? 0;
                $group->is_active = (bool) $data['is_active'];
                $group->save();

                $submittedIds = [];

                foreach ($data['appliances'] as $index => $item) {
                    if (!empty($item['id'])) {
                        $appliance = Appliance::where('group_id', $group->id)
                            ->whereKey($item['id'])
                            ->first();

                        if (!$appliance) {
                            return [
                                'success' => false,
                                'message' => 'An appliance does not belong to this group or no longer exists.',
                            ];
                        }

                        $submittedIds[] = $appliance->id;
                    } else {
                        $appliance = new Appliance();
                        $appliance->group_id = $group->id;
                    }

                    $appliance->name = $item['name'];
                    $appliance->ar_name = $item['ar_name'];
                    $appliance->watts = (int) $item['watts'];
                    $appliance->sort_order = $item['sort_order'] ?? $index;
                    $appliance->is_active = (bool) $item['is_active'];
                    $appliance->save();

                    if (empty($item['id'])) {
                        $submittedIds[] = $appliance->id;
                    }
                }

                $removedAppliances = Appliance::where('group_id', $group->id);

                if (!empty($submittedIds)) {
                    $removedAppliances->whereNotIn('id', $submittedIds);
                }

                $removedAppliances->get()->each(function (Appliance $appliance) {
                    $appliance->delete();
                });


                return [
                    'success' => true,
                    'message' => 'Appliance group and appliances updated successfully.',
                ];
            });

            return $result;
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to update the appliance group. Please try again.',
            ];
        }
    }

    public function destroyApplianceGroup(int $id): array
    {
        try {
            return DB::transaction(function () use ($id) {
                $group = ApplianceGroup::find($id);

                if (!$group) {
                    return [
                        'success' => false,
                        'message' => 'Appliance group not found.',
                    ];
                }

                Appliance::where('group_id', $group->id)
                    ->get()
                    ->each(function (Appliance $appliance) {
                        $appliance->delete();
                    });

                $group->delete();

                return [
                    'success' => true,
                    'message' => 'Appliance group deleted successfully.',
                ];
            });
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to delete the appliance group. Please try again.',
            ];
        }
    }

    //Appliance Groups and Items End

    //Supplier and Products Managment Start

    public function showSuppliers(): array
    {
        try {
            $suppliers = Supplier::withCount('products')->orderBy('name')->paginate(10);
            return ['success' => true, 'message' => 'Suppliers retrieved successfully.', 'suppliers' => $suppliers];
        } catch (Throwable $e) {
            Log::error('Unable to retrieve suppliers.', ['exception' => $e]);
            return ['success' => false, 'message' => 'Unable to retrieve suppliers.'];
        }
    }

    public function storeSupplier(array $data): array
    {
        try {
            $supplier = new Supplier();
            $supplier->name = trim($data['name']);
            $supplier->description = $data['description'] ?? null;
            $supplier->website = $data['website'] ?? null;
            $supplier->is_active = (bool) $data['is_active'];
            $supplier->save();
            return ['success' => true, 'message' => 'Supplier created successfully.', 'supplier' => $supplier];
        } catch (Throwable $e) {
            Log::error('Unable to create supplier.', ['exception' => $e]);
            return ['success' => false, 'message' => 'Unable to create supplier. Check whether the name already exists.'];
        }
    }

    public function editSupplier(int $id): array
    {
        $supplier = Supplier::find($id);
        return $supplier
            ? ['success' => true, 'message' => 'Supplier retrieved successfully.', 'supplier' => $supplier]
            : ['success' => false, 'message' => 'Supplier not found.'];
    }

    public function updateSupplier(int $id, array $data): array
    {
        try {
            $supplier = Supplier::find($id);
            if (!$supplier)
                return ['success' => false, 'message' => 'Supplier not found.'];
            $supplier->name = trim($data['name']);
            $supplier->description = $data['description'] ?? null;
            $supplier->website = $data['website'] ?? null;
            $supplier->is_active = (bool) $data['is_active'];
            $supplier->save();
            return ['success' => true, 'message' => 'Supplier updated successfully.', 'supplier' => $supplier];
        } catch (Throwable $e) {
            Log::error('Unable to update supplier.', ['supplier_id' => $id, 'exception' => $e]);
            return ['success' => false, 'message' => 'Unable to update supplier. Check whether the name already exists.'];
        }
    }

    public function destroySupplier(int $id): array
    {
        $supplier = Supplier::withCount('products')->find($id);
        if (!$supplier)
            return ['success' => false, 'message' => 'Supplier not found.'];
        if ($supplier->products_count > 0)
            return ['success' => false, 'message' => 'This supplier still has products. Delete or reassign its products first.'];
        $supplier->delete();
        return ['success' => true, 'message' => 'Supplier deleted successfully.'];
    }

    public function showProducts(): array
    {
        try {
            $products = Product::with(['supplier', 'specs'])->orderBy('name')->paginate(10);
            $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
            return ['success' => true, 'message' => 'Products retrieved successfully.', 'products' => $products, 'suppliers' => $suppliers];
        } catch (Throwable $e) {
            Log::error('Unable to retrieve products.', ['exception' => $e]);
            return ['success' => false, 'message' => 'Unable to retrieve products.'];
        }
    }

    public function productFormData(): array
    {
        return ['success' => true, 'message' => 'Product form data retrieved successfully.', 'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get()];
    }

    public function storeProduct(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $product = new Product();
                $product->supplier_id = (int) $data['supplier_id'];
                $product->name = trim($data['name']);
                $product->model = isset($data['model']) ? trim($data['model']) : null;
                $product->type = $data['type'];
                $product->description = $data['description'] ?? null;
                $product->is_active = (bool) $data['is_active'];
                $product->save();
                $this->syncProductSpecs($product, $data['specs'] ?? []);
                return ['success' => true, 'message' => 'Product created successfully.', 'product' => $product];
            });
        } catch (Throwable $e) {
            Log::error('Unable to create product.', ['exception' => $e]);
            return ['success' => false, 'message' => 'Unable to create product.'];
        }
    }

    public function editProduct(int $id): array
    {
        $product = Product::with('specs')->find($id);
        if (!$product)
            return ['success' => false, 'message' => 'Product not found.'];
        return ['success' => true, 'message' => 'Product retrieved successfully.', 'product' => $product, 'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get()];
    }

    public function updateProduct(int $id, array $data): array
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $product = Product::find($id);
                if (!$product)
                    return ['success' => false, 'message' => 'Product not found.'];
                $product->supplier_id = (int) $data['supplier_id'];
                $product->name = trim($data['name']);
                $product->model = isset($data['model']) ? trim($data['model']) : null;
                $product->type = $data['type'];
                $product->description = $data['description'] ?? null;
                $product->is_active = (bool) $data['is_active'];
                $product->save();
                $this->syncProductSpecs($product, $data['specs'] ?? []);
                return ['success' => true, 'message' => 'Product updated successfully.', 'product' => $product];
            });
        } catch (Throwable $e) {
            Log::error('Unable to update product.', ['product_id' => $id, 'exception' => $e]);
            return ['success' => false, 'message' => 'Unable to update product.'];
        }
    }

    public function destroyProduct(int $id): array
    {
        $product = Product::find($id);
        if (!$product)
            return ['success' => false, 'message' => 'Product not found.'];
        $product->delete();
        return ['success' => true, 'message' => 'Product deleted successfully.'];
    }

    private function syncProductSpecs(Product $product, array $specs): void
    {
        $product->specs()->delete();
        $seen = [];
        foreach ($specs as $spec) {
            $name = trim((string) ($spec['spec_name'] ?? ''));
            $value = trim((string) ($spec['spec_value'] ?? ''));
            if ($name === '' && $value === '')
                continue;
            if ($name === '' || $value === '')
                throw new \InvalidArgumentException('Each specification needs both a name and a value.');
            $key = mb_strtolower($name);
            if (isset($seen[$key]))
                throw new \InvalidArgumentException('Specification names must be unique for each product.');
            $seen[$key] = true;
            $specModel = new ProductSpec();
            $specModel->product_id = $product->id;
            $specModel->spec_name = $name;
            $specModel->spec_value = $value;
            $specModel->save();
        }
    }


    //Supplier and Products Managment End

    //Company Carousel Start

    public function getCompanyCarouselEntries()
    {
        return CompanyCarousel::with('company')->orderBy('platform')->orderBy('sort_order')->orderByDesc('id')->paginate(10);
    }

    public function findCompanyCarouselEntry(int $id): ?CompanyCarousel
    {
        return CompanyCarousel::with('company')->find($id);
    }

    public function getCompaniesForCarouselSelect()
    {
        return SolarCompany::query()
            ->where('status', 'approved')
            ->where('is_active', 1)
            ->where('is_verified', 1)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getNextCompanyCarouselSortOrder(): int
    {
        return ((int) CompanyCarousel::max('sort_order')) + 1;
    }

    public function createCompanyCarouselEntry(array $data): array
    {
        try {
            // 1. Ensure the company exists, is approved, and is verified.
            $companyExists = SolarCompany::query()
                ->whereKey($data['company_id'])
                ->where('status', 'approved')
                ->where('is_verified', 1)
                ->exists();

            if (!$companyExists) {
                return [
                    'success' => false,
                    'message' => 'The selected company was not found or is not approved and verified.',
                ];
            }

            $newPlatform = $data['platform'];

            // 2. Get existing platform entries for this company.
            $existingPlatforms = CompanyCarousel::query()
                ->where('company_id', $data['company_id'])
                ->pluck('platform')
                ->toArray();

            // 3. Check platform conflicts.
            $hasConflict = match ($newPlatform) {
                // "both" is allowed only when the company has no existing entries.
                'both' => count($existingPlatforms) > 0,

                // "app" cannot coexist with another "app" or "both" entry.
                'app' => in_array('app', $existingPlatforms, true)
                    || in_array('both', $existingPlatforms, true),

                // "website" cannot coexist with another "website" or "both" entry.
                'website' => in_array('website', $existingPlatforms, true)
                    || in_array('both', $existingPlatforms, true),

                // Reject unsupported platform values.
                default => true,
            };

            if ($hasConflict) {
                $message = match ($newPlatform) {
                    'both' => 'This company already has a carousel entry. App and website cannot be selected.',
                    'app' => 'This company already has an entry for the mobile app.',
                    'website' => 'This company already has an entry for the website.',
                    default => 'The selected platform is invalid.',
                };

                return [
                    'success' => false,
                    'message' => $message,
                ];
            }

            // 4. Create the carousel entry.
            $entry = new CompanyCarousel();

            $this->assignCompanyCarouselFields($entry, $data);

            $entry->save();

            return [
                'success' => true,
                'message' => 'Carousel entry created successfully.',
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to create the carousel entry. Please try again.',
            ];
        }
    }

    public function updateCompanyCarouselEntry(int $id, array $data): array
    {
        try {
            $entry = CompanyCarousel::find($id);
            if (!$entry)
                return ['success' => false, 'message' => 'Carousel entry not found.'];
            if (!SolarCompany::whereKey($data['company_id'])->exists()) {
                return ['success' => false, 'message' => 'The selected company was not found.'];
            }
            $this->assignCompanyCarouselFields($entry, $data);
            $entry->save();
            return ['success' => true, 'message' => 'Carousel entry updated successfully.'];
        } catch (Throwable $e) {
            report($e);
            return ['success' => false, 'message' => 'Unable to update the carousel entry. Please try again.'];
        }
    }

    public function deleteCompanyCarouselEntry(int $id): array
    {
        try {
            $entry = CompanyCarousel::find($id);
            if (!$entry)
                return ['success' => false, 'message' => 'Carousel entry not found.'];
            $entry->delete();
            return ['success' => true, 'message' => 'Carousel entry deleted successfully.'];
        } catch (Throwable $e) {
            report($e);
            return ['success' => false, 'message' => 'Unable to delete the carousel entry. Please try again.'];
        }
    }

    private function assignCompanyCarouselFields(CompanyCarousel $entry, array $data): void
    {
        $entry->company_id = (int) $data['company_id'];
        $entry->platform = $data['platform'];
        $entry->sort_order = (int) $data['sort_order'];
        $entry->interval_seconds = (int) $data['interval_seconds'];
        $entry->is_active = (bool) ($data['is_active'] ?? false);
        $entry->starts_at = $data['starts_at'] ?? null;
        $entry->ends_at = $data['ends_at'] ?? null;
    }

    //App Versions Start

    public function getAppVersions()
    {
        return AppVersion::query()
            ->with('creator:id,username')
            ->orderBy('platform')
            ->get();
    }

    public function findAppVersion(int $id): ?AppVersion
    {
        return AppVersion::find($id);
    }

    public function createAppVersion(array $data): array
    {
        try {
            $version = new AppVersion();
            $this->assignAppVersionFields($version, $data);
            $version->created_by = auth('admin')->id();
            $version->save();

            return [
                'success' => true,
                'message' => AppVersion::PLATFORMS[$version->platform] . ' app version saved successfully.',
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to save the app version. Please try again.',
            ];
        }
    }

    public function updateAppVersion(int $id, array $data): array
    {
        try {
            $version = AppVersion::find($id);

            if (!$version) {
                return ['success' => false, 'message' => 'App version not found.'];
            }

            $this->assignAppVersionFields($version, $data);
            $version->save();

            return [
                'success' => true,
                'message' => AppVersion::PLATFORMS[$version->platform] . ' app version updated successfully.',
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to update the app version. Please try again.',
            ];
        }
    }

    public function deleteAppVersion(int $id): array
    {
        try {
            $version = AppVersion::find($id);

            if (!$version) {
                return ['success' => false, 'message' => 'App version not found.'];
            }

            $version->delete();

            return ['success' => true, 'message' => 'App version deleted successfully.'];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to delete the app version. Please try again.',
            ];
        }
    }

    private function assignAppVersionFields(AppVersion $version, array $data): void
    {
        $version->platform = $data['platform'];
        $version->minimum_version = $data['minimum_version'];
        $version->app_url = $data['app_url'];
        $version->force_update = (bool) ($data['force_update'] ?? false);
        $version->description_en = $data['description_en'] ?? null;
        $version->description_ar = $data['description_ar'] ?? null;
    }

    //App Versions End

    // Call from your existing public/mobile home-data repository/service.
    public function getCompanyCarouselForPlatform(string $platform)
    {
        abort_unless(in_array($platform, ['app', 'website'], true), 422);
        return CompanyCarousel::currentlyVisible()
            ->whereIn('platform', [$platform, 'both'])
            ->with('company')->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn(CompanyCarousel $entry) => [
                'carousel_id' => $entry->id,
                'company_id' => $entry->company_id,
                'company' => $entry->company,
                'sort_order' => $entry->sort_order,
                'interval_seconds' => $entry->interval_seconds,
                'platform' => $entry->platform,
            ]);
    }

    //Company Carousel End

    //Quote Requests Start

    public function getQuoteRequests(array $filters = []): LengthAwarePaginator
    {
        $query = Quote::query()
            ->with(['customer', 'company'])
            ->when(!empty($filters['status']), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);

                $query->where(function ($q) use ($search) {
                    if (ctype_digit($search)) {
                        $q->orWhere('id', (int) $search)
                            ->orWhere('customer_id', (int) $search);
                    }

                    $q->orWhere('service_needed', 'like', "%{$search}%")
                        ->orWhere('estimated_load', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        })
                        ->orWhereHas('company', function ($companyQuery) use ($search) {
                            $companyQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return $query->paginate(10)->withQueryString();
    }

    public function getQuoteRequestMetrics(): array
    {
        $query = Quote::query();

        return [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'in_progress' => (clone $query)->where('status', 'accepted')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
        ];
    }

    public function getQuoteRequestById(int $id): ?Quote
    {
        return Quote::query()
            ->with(['customer', 'company'])
            ->find($id);
    }

    //Quote Requests End

    //Notifications Start

    public function publishNotification(array $data): Notification
    {
        $startedAt = microtime(true);

        $context = [
            'sender_type' => $data['sender_type'] ?? 'admin',
            'audience_type' => $data['audience_type'] ?? 'selected',
        ];

        Log::info('SunXFinder notification: process started', $context);

        try {
            $result = (new Notification())
                ->getConnection()
                ->transaction(function () use ($data, $context) {
                    Log::info('SunXFinder notification: transaction started', $context);

                    $senderType = $data['sender_type'] ?? 'admin';
                    $senderId = $data['sender_id'] ?? null;
                    $audienceType = $data['audience_type'] ?? 'selected';

                    if ($senderType === 'company' && $audienceType === 'all') {
                        Log::warning(
                            'SunXFinder notification: company broadcast rejected',
                            $context
                        );

                        throw ValidationException::withMessages([
                            'audience_type' =>
                                'Companies cannot broadcast to all customers.',
                        ]);
                    }

                    Log::info('SunXFinder notification: resolving recipients', [
                        ...$context,
                        'provided_customer_count' =>
                            count($data['customer_ids'] ?? []),
                    ]);

                    $customerIds = $audienceType === 'all'
                        ? Customer::query()
                            ->whereHas('deviceTokens')
                            ->pluck('id')
                            ->map(fn($id) => (int) $id)
                            ->all()
                        : array_values(array_unique(
                            array_map('intval', $data['customer_ids'] ?? [])
                        ));

                    Log::info('SunXFinder notification: recipients resolved', [
                        ...$context,
                        'customer_count' => count($customerIds),
                    ]);

                    if (empty($customerIds)) {
                        Log::warning(
                            'SunXFinder notification: no recipients selected',
                            $context
                        );

                        throw ValidationException::withMessages([
                            'customer_ids' => 'Select at least one customer.',
                        ]);
                    }

                    $existingIds = Customer::query()
                        ->whereIn('id', $customerIds)
                        ->pluck('id')
                        ->map(fn($id) => (int) $id)
                        ->all();

                    if (count($existingIds) !== count($customerIds)) {
                        Log::warning(
                            'SunXFinder notification: invalid customer IDs',
                            [
                                ...$context,
                                'requested_count' => count($customerIds),
                                'valid_count' => count($existingIds),
                            ]
                        );

                        throw ValidationException::withMessages([
                            'customer_ids' =>
                                'One or more selected customers are invalid.',
                        ]);
                    }

                    Log::info('SunXFinder notification: creating notification');

                    $notification = Notification::create([
                        'sender_type' => $senderType,
                        'sender_id' => $senderId,
                        'type' => $data['type'],
                        'title_en' => $data['title_en'],
                        'title_ar' => $data['title_ar'] ?? null,
                        'message_en' => $data['message_en'],
                        'message_ar' => $data['message_ar'] ?? null,
                        'audience_type' => $audienceType,
                        'status' => 'published',
                        'published_at' => now(),
                    ]);

                    Log::info(
                        'SunXFinder notification: notification created',
                        [
                            'notification_id' => $notification->id,
                            ...$context,
                        ]
                    );

                    $now = now();
                    $insertedCount = 0;

                    foreach (array_chunk($customerIds, 500) as $chunk) {
                        $rows = array_map(
                            fn($customerId) => [
                                'notification_id' => $notification->id,
                                'customer_id' => $customerId,
                                'read_at' => null,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ],
                            $chunk
                        );

                        CustomerNotification::insert($rows);
                        $insertedCount += count($rows);
                    }

                    Log::info(
                        'SunXFinder notification: recipient records inserted',
                        [
                            'notification_id' => $notification->id,
                            'recipient_count' => $insertedCount,
                        ]
                    );

                    return [
                        'notification' => $notification,
                        'customerIds' => $customerIds,
                    ];
                });

            Log::info('SunXFinder notification: transaction committed', [
                'notification_id' => $result['notification']->id,
                'customer_count' => count($result['customerIds']),
            ]);
        } catch (Throwable $e) {
            Log::error('SunXFinder notification: database/process failure', [
                ...$context,
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }

        $notification = $result['notification'];
        $customerIds = $result['customerIds'];

        Log::info('SunXFinder notification: looking up device tokens', [
            'notification_id' => $notification->id,
            'customer_count' => count($customerIds),
        ]);

        try {
            $devices = CustomerDeviceToken::query()
                ->whereIn('customer_id', $customerIds)
                ->get([
                    'id',
                    'customer_id',
                    'fcm_token',
                    'lang',
                    'platform',
                ]);

            Log::info('SunXFinder notification: device tokens loaded', [
                'notification_id' => $notification->id,
                'device_count' => $devices->count(),
                'customer_count_with_devices' =>
                    $devices->pluck('customer_id')->unique()->count(),
            ]);
        } catch (Throwable $e) {
            Log::error('SunXFinder notification: device lookup failed', [
                'notification_id' => $notification->id,
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        if ($devices->isEmpty()) {
            Log::warning('SunXFinder notification: no device tokens found', [
                'notification_id' => $notification->id,
                'customer_count' => count($customerIds),
            ]);

            return $notification;
        }

        try {
            Log::info('SunXFinder notification: initializing Firebase messaging', [
                'notification_id' => $notification->id,
            ]);

            $messaging = app(Messaging::class);

            Log::info('SunXFinder notification: Firebase messaging initialized', [
                'notification_id' => $notification->id,
            ]);
        } catch (Throwable $e) {
            Log::critical('SunXFinder notification: Firebase initialization failed', [
                'notification_id' => $notification->id,
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }

        $successCount = 0;
        $failureCount = 0;

        foreach ($devices as $device) {
            $lang = strtolower($device->lang ?? 'en') === 'ar'
                ? 'ar'
                : 'en';

            $title = $lang === 'ar'
                ? ($notification->title_ar ?: $notification->title_en)
                : $notification->title_en;

            $body = $lang === 'ar'
                ? ($notification->message_ar ?: $notification->message_en)
                : $notification->message_en;

            // Never log the actual FCM token.
            $tokenFingerprint = substr(
                hash('sha256', $device->fcm_token),
                0,
                12
            );

            Log::info('SunXFinder notification: sending push', [
                'notification_id' => $notification->id,
                'device_id' => $device->id,
                'customer_id' => $device->customer_id,
                'platform' => $device->platform ?? null,
                'lang' => $lang,
                'token_fingerprint' => $tokenFingerprint,
            ]);

            try {
                $message = CloudMessage::new()
                    ->toToken($device->fcm_token)
                    ->withNotification(
                        FcmNotification::create($title, $body)
                    )
                    ->withData([
                        'notification_id' => (string) $notification->id,
                        'type' => (string) $notification->type,
                        'lang' => $lang,
                    ]);

                $sendResult = $messaging->send($message);

                $successCount++;

                Log::info('SunXFinder notification: Firebase accepted push', [
                    'notification_id' => $notification->id,
                    'device_id' => $device->id,
                    'customer_id' => $device->customer_id,
                    'token_fingerprint' => $tokenFingerprint,
                    'firebase_result_type' => get_debug_type($sendResult),
                ]);
            } catch (Throwable $e) {
                $failureCount++;

                Log::error('SunXFinder notification: push failed', [
                    'notification_id' => $notification->id,
                    'device_id' => $device->id,
                    'customer_id' => $device->customer_id,
                    'token_fingerprint' => $tokenFingerprint,
                    'exception_class' => get_class($e),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
        }

        Log::info('SunXFinder notification: delivery process completed', [
            'notification_id' => $notification->id,
            'device_count' => $devices->count(),
            'firebase_accepted_count' => $successCount,
            'failed_count' => $failureCount,
            'duration_ms' => round((microtime(true) - $startedAt) * 1000),
        ]);

        return $notification;
    }


    public function getNotifications(array $filters = []): array
    {
        $query = Notification::query()
            ->withCount('recipients')
            ->when(
                !empty($filters['type']),
                fn(Builder $q) => $q->where('type', $filters['type'])
            )
            ->when(
                !empty($filters['status']),
                fn(Builder $q) => $q->where('status', $filters['status'])
            )
            ->when(!empty($filters['search']), function (Builder $q) use ($filters) {
                $search = $filters['search'];

                $q->where(function (Builder $subQuery) use ($search) {
                    $subQuery->where('title_en', 'like', "%{$search}%")
                        ->orWhere('title_ar', 'like', "%{$search}%")
                        ->orWhere('message_en', 'like', "%{$search}%")
                        ->orWhere('message_ar', 'like', "%{$search}%");
                });
            });

        $notifications = $query
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $publishedCount = Notification::where('status', 'published')->count();
        $draftCount = Notification::where('status', 'draft')->count();

        $recipientCount = DB::table('tbl_customer_notifications')
            ->count();

        return [
            'notifications' => $notifications,
            'publishedCount' => $publishedCount,
            'draftCount' => $draftCount,
            'recipientCount' => $recipientCount,
        ];
    }

    public function getNotificationFormData(): array
    {
        $customers = Customer::query()
            ->whereHas('deviceTokens')
            ->select('id', 'full_name', 'username', 'phone')
            ->orderBy('username')
            ->get();

        return [
            'customers' => $customers,
        ];
    }


    public function getNotificationDetails(int $id): array
    {
        $notification = Notification::query()
            ->withCount('recipients')
            ->find($id);

        if (!$notification) {
            return [
                'success' => false,
                'message' => app()->getLocale() === 'ar'
                    ? 'الإشعار غير موجود.'
                    : 'Notification not found.',
            ];
        }

        $readCount = CustomerNotification::query()
            ->where('notification_id', $id)
            ->whereNotNull('read_at')
            ->count();

        $unreadCount = max(
            0,
            $notification->recipients_count - $readCount
        );

        $recipients = CustomerNotification::query()
            ->with([
                'customer:id,full_name,phone',
                'customer.deviceTokens:id,customer_id,fcm_token,lang,platform',
            ])
            ->where('notification_id', $id)
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return [
            'success' => true,
            'notification' => $notification,
            'readCount' => $readCount,
            'unreadCount' => $unreadCount,
            'recipients' => $recipients,
        ];
    }


    //Notifications End


}

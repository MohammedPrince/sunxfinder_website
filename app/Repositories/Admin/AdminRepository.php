<?php

namespace App\Repositories\Admin;
use App\Models\Appliance;
use App\Models\ApplianceGroup;
use App\Models\CompanyCarousel;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductSpec;
use App\Models\Quote;
use App\Models\SolarCompany;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminRepository
{
    public function __construct()
    {
        //
    }

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


}

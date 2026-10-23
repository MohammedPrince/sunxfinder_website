<?php

namespace App\Repositories\API;

use App\Helpers\Helper;
use App\Jobs\SendCompanyOfferDecisionEmail;
use App\Mail\CompanyOfferDecisionMail;
use App\Models\ApplianceGroup;
use App\Models\AppVersion;
use App\Models\CompanyCarousel;
use App\Models\CompanyQuotation;
use App\Models\Customer;
use App\Models\CustomerDeviceToken;
use App\Repositories\OtpRepository;
use App\Models\CustomerNotification;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectUpdate;
use App\Models\Quote;
use App\Models\Rating;
use App\Models\RatingQuestion;
use App\Models\RatingValue;
use App\Models\Setting;
use App\Models\SolarCompany;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;


class CustomerRepository
{
    protected Helper $helper;

    protected OtpRepository $otpRepository;

    public function __construct(Helper $helper, OtpRepository $otpRepository)
    {
        $this->helper = $helper;
        $this->otpRepository = $otpRepository;
    }

    public function login(array $data): array
    {
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        if ($username === '' || $password === '') {
            return [
                'success' => false,
                'code' => 422,
                'message' => 'Username and password are required.',
            ];
        }

        $rateLimitKey = 'customer-login:'
            . strtolower($username)
            . '|'
            . request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return [
                'success' => false,
                'code' => 429,
                'message' => 'Too many login attempts. Please try again in'
                    . ceil($seconds / 60)
                    . ' minute(s).',
                'retry_after' => $seconds,
            ];
        }

        $customer = Customer::where('username', $username)->first();

        if (!$customer) {
            RateLimiter::hit($rateLimitKey, 300);

            return [
                'success' => false,
                'code' => 401,
                'message' => 'Invalid username or password.',
                'attempts_remaining' => max(
                    0,
                    3 - RateLimiter::attempts($rateLimitKey)
                ),
            ];
        }

        if (!$customer->is_active) {
            return [
                'success' => false,
                'code' => 403,
                'message' => 'Account inactive or deleted.',
            ];
        }

        if (!Hash::check($password, $customer->password)) {
            RateLimiter::hit($rateLimitKey, 300);

            return [
                'success' => false,
                'code' => 401,
                'message' => 'Invalid username or password.',
                'attempts_remaining' => max(
                    0,
                    3 - RateLimiter::attempts($rateLimitKey)
                ),
            ];
        }

        RateLimiter::clear($rateLimitKey);

        // Revoke previous tokens if only one active device is permitted.
        $customer->tokens()->delete();

        $expiresAt = Carbon::now()->addYear();

        // Shown in the admin customers list. Not in $fillable on purpose.
        $customer->forceFill(['last_login_at' => now()])->saveQuietly();

        $token = $customer->createToken(
            'sunxfinder-customer-mobile-app',
            ['*'],
            $expiresAt
        );

        $customerDetails = [
            'id' => $customer->id,
            'username' => $customer->username,
            'full_name' => $customer->full_name ?? $customer->name ?? null,
            'email' => $customer->email ?? null,
            'phone' => $customer->phone ?? null,
            'gender' => $customer->gender ?? null,
            'city' => $customer->city ?? null,
            'address' => $customer->address ?? null,
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toISOString(),
        ];

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Login successful.',
            'customerDetails' => $customerDetails,
        ];
    }


    public function mainData(): array
    {
        try {
            // Authentication stays in the Repository.
            $customer = $this->helper->isCustomerAuthenticated();

            if (!$customer) {
                return [
                    'success' => false,
                    'code' => 401,
                    'message' => 'Unauthenticated.',
                ];
            }

            $customerId = (int) $customer->id;

            // Cities and profile.
            $citiesResult = $this->getCities();

            if (!($citiesResult['success'] ?? false)) {
                return [
                    'success' => false,
                    'code' => $citiesResult['code'] ?? 500,
                    'message' => $citiesResult['message'] ?? 'Unable to retrieve cities.',
                ];
            }

            $cities = $citiesResult['cities'] ?? [];


            $profileResult = $this->getProfile();

            if (!($profileResult['success'] ?? false)) {
                return [
                    'success' => false,
                    'code' => $profileResult['code'] ?? 500,
                    'message' => $profileResult['message'] ?? 'Unable to retrieve profile.',
                ];
            }

            $profile = $profileResult['profile'] ?? null;

            // Admin-editable app settings the app reads on start.
            $settings = [
                'max_open_quotes' => Setting::maxOpenQuotes(),
            ];

            // Services: getServices() returns both languages under data.
            $servicesResult = $this->getServices();

            if (!($servicesResult['success'] ?? false)) {
                return [
                    'success' => false,
                    'code' => $servicesResult['code'] ?? 500,
                    'message' => $servicesResult['message']
                        ?? 'Unable to retrieve services.',
                ];
            }

            $services = $servicesResult['data']['services'] ?? [];
            $servicesAr = $servicesResult['data']['services_ar'] ?? [];

            // Carousels.
            $carouselsResult = $this->getCarousels();

            if (!($carouselsResult['success'] ?? false)) {
                return [
                    'success' => false,
                    'code' => $carouselsResult['code'] ?? 500,
                    'message' => $carouselsResult['message']
                        ?? 'Unable to retrieve carousels.',
                ];
            }

            $carousels = $carouselsResult['carousels'] ?? [];

            // Companies: method requires filters and returns a paginated result.
            $companiesResult = $this->getCompanies([
                'per_page' => 50,
                'page' => 1,
                'sort' => 'newest',
            ]);

            if (!($companiesResult['success'] ?? false)) {
                return [
                    'success' => false,
                    'code' => $companiesResult['code'] ?? 500,
                    'message' => $companiesResult['message']
                        ?? 'Unable to retrieve companies.',
                ];
            }

            $companies = $companiesResult['companies'] ?? [];

            // Calculator data.
            $calculator = $this->appliancesCalculator();

            // Existing quotes: pass filters and preserve the existing quote format.
            $quotesResult = $this->getMyQuotes([]);

            if (!($quotesResult['success'] ?? false)) {
                return [
                    'success' => false,
                    'code' => $quotesResult['code'] ?? 500,
                    'message' => $quotesResult['message']
                        ?? 'Unable to retrieve quote requests.',
                ];
            }

            $myQuotes = $quotesResult['quotes'] ?? [];

            // Notifications toggle follows the customer's most recently used device token.
            $customer = $this->helper->isCustomerAuthenticated();

            $notificationsEnabled = (bool) CustomerDeviceToken::query()
                ->where('customer_id', $customer->id)
                ->orderByDesc('last_used_at')
                ->value('is_active');

            // TODO: Replace with your actual customer notification query.
            $notifications = [
                'enabled' => $notificationsEnabled,
                'unread_count' => 0,
                'items' => [],
            ];

            return [
                'success' => true,
                'code' => 200,
                'message' => 'Main data loaded.',
                'profile' => $profile,
                'cities' => $cities,
                'services' => $services,
                'services_ar' => $servicesAr,
                'carousels' => $carousels,
                'companies' => $companies,
                'calculator' => $calculator,
                'my_quotes' => [
                    'quotes' => $myQuotes,
                ],
                'notifications' => $notifications,
                'app_version' => $this->getAppVersions($customerId),
                'settings' => $settings,
                'rating_form' => $this->getRatingForm()['data'],
                'meta' => [
                    'companies_total' => $companiesResult['meta']['total']
                        ?? count($companies),
                    'generated_at' => now()->utc()->toISOString(),
                ],
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Unable to retrieve main data.',
            ];
        }
    }

    /**
     * Minimum supported app version per platform, managed from the admin panel.
     * A platform without a configured version is returned as null.
     * "desc" follows the language of the customer's most recently used device.
     */
    private function getAppVersions(int $customerId): array
    {
        $deviceLang = CustomerDeviceToken::query()
            ->where('customer_id', $customerId)
            ->orderByDesc('last_used_at')
            ->value('lang');

        $lang = strtolower($deviceLang ?? 'en') === 'ar' ? 'ar' : 'en';

        $defaultDesc = [
            'en' => [
                'force' => 'A new version of SunXFinder is required. Please update the app to continue.',
                'optional' => 'A new version of SunXFinder is available. Update now for the latest features and fixes.',
            ],
            'ar' => [
                'force' => 'يلزم تحديث تطبيق SunXFinder إلى الإصدار الجديد للمتابعة.',
                'optional' => 'يتوفر إصدار جديد من تطبيق SunXFinder. حدّث الآن للحصول على أحدث الميزات والإصلاحات.',
            ],
        ];

        $versions = AppVersion::query()
            ->get([
                'platform',
                'minimum_version',
                'app_url',
                'force_update',
                'description_en',
                'description_ar',
                'updated_at',
            ])
            ->keyBy('platform');

        $result = [];

        foreach (array_keys(AppVersion::PLATFORMS) as $platform) {
            $version = $versions->get($platform);

            $result[$platform] = $version ? [
                'minimum_version' => $version->minimum_version,
                'app_url' => $version->app_url,
                'force_update' => $version->force_update,
                'lang' => $lang,
                // Admin message in the device language, or the default when left empty.
                'desc' => $version->{"description_{$lang}"}
                    ?: $defaultDesc[$lang][$version->force_update ? 'force' : 'optional'],
                'updated_at' => $version->updated_at?->toISOString(),
            ] : null;
        }

        return $result;
    }

    public function createAccount($data): array
    {
        // Check whether the username, phone, or email already exists.
        $existingCustomer = Customer::query()
            ->where('is_active', 1)
            ->where(function ($query) use ($data) {
                $query->where('username', $data['username'])
                    ->orWhere('phone', $data['phone']);

                if (!empty($data['email'])) {
                    $query->orWhere('email', $data['email']);
                }
            });

        if ($existingCustomer->exists()) {
            return [
                'success' => false,
                'code' => 409,
                'message' => 'Account already exists.',
            ];
        }

        $firstName = $data['first_name'] ?? null;
        $secondName = $data['second_name'] ?? null;
        $thirdName = $data['third_name'] ?? null;

        $fullName = trim(implode(' ', array_filter([
            $firstName,
            $secondName,
            $thirdName,
        ], fn($name) => $name !== null && $name !== '')));

        try {
            $customer = Customer::create([
                'username' => $data['username'],
                'first_name' => $firstName,
                'second_name' => $secondName,
                'third_name' => $thirdName,
                'full_name' => $fullName !== '' ? $fullName : null,
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'gender' => $data['gender'] ?? null,
                'city' => $data['city'] ?? null,
                'address' => $data['address'] ?? null,
                'password' => Hash::make($data['password']),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            return [
                'success' => false,
                'code' => 409,
                'message' => 'Account already exists.',
            ];
        }

        if (!$customer) {
            return [
                'success' => false,
                'code' => 500,
                'message' => 'Account not created.',
            ];
        }

        $customerAccount = [
            'username' => $customer->username,
            'full_name' => $customer->full_name,
            'first_name' => $customer->first_name,
            'second_name' => $customer->second_name,
            'third_name' => $customer->third_name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'gender' => $customer->gender,
            'city' => $customer->city,
            'address' => $customer->address,
        ];

        return [
            'success' => true,
            'code' => 201,
            'message' => 'Account created successfully.',
            'account' => $customerAccount,
        ];
    }

    public function getProfile(): array
    {
        try {
            // Get the authenticated customer from the session/auth helper.
            $customer = $this->helper->isCustomerAuthenticated();

            if (!$customer) {
                return [
                    'success' => false,
                    'code' => 401,
                    'message' => 'Unauthenticated.',
                    'profile' => null,
                ];
            }

            // Get the full name.
            $fullName = trim((string) ($customer->full_name ?? ''));

            if ($fullName === '') {
                $fullName = trim((string) ($customer->name ?? ''));
            }

            // Split the name into first, second, and third names.
            $nameParts = preg_split('/\s+/u', $fullName, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            $firstName = $nameParts[0] ?? '';
            $secondName = $nameParts[1] ?? '';
            $thirdName = count($nameParts) > 2
                ? implode(' ', array_slice($nameParts, 2))
                : '';

            $profile = [
                'username' => $customer->username ?? null,
                'full_name' => $fullName,
                'first_name' => $firstName,
                'second_name' => $secondName,
                'third_name' => $thirdName,
                'phone' => $customer->phone ?? null,
                'email' => $customer->email ?? null,
                'gender' => $customer->gender ?? null,
                'city' => $customer->city ?? null,
                'address' => $customer->address ?? null,
            ];

            return [
                'success' => true,
                'code' => 200,
                'message' => 'Profile retrieved successfully.',
                'profile' => $profile,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Unable to retrieve profile.',
                'profile' => null,
            ];
        }
    }

    public function updateProfile(array $data): array
    {
        try {
            // Identify the authenticated customer.
            $customer = $this->helper->isCustomerAuthenticated();

            if (!$customer) {
                return [
                    'success' => false,
                    'code' => 401,
                    'message' => 'Unauthenticated.',
                    'data' => null,
                ];
            }

            // Combine the name fields.
            $fullName = trim(implode(' ', array_filter([
                trim((string) ($data['first_name'] ?? '')),
                trim((string) ($data['second_name'] ?? '')),
                trim((string) ($data['third_name'] ?? '')),
            ], fn($name) => $name !== '')));

            // Update only the authenticated customer's profile.
            //  $customer->username = $data['username'];

            // Support either full_name or name as the stored name column.
            if (array_key_exists('full_name', $customer->getAttributes())) {
                $customer->full_name = $fullName;
            } elseif (array_key_exists('name', $customer->getAttributes())) {
                $customer->name = $fullName;
            } else {
                return [
                    'success' => false,
                    'code' => 500,
                    'message' => 'Customer name column was not found.',
                    'data' => null,
                ];
            }

            $customer->phone = $data['phone'];
            $customer->email = $data['email'];
            $customer->gender = $data['gender'] ?? null;
            $customer->city = $data['city'] ?? null;
            $customer->address = $data['address'] ?? null;
            // if (!empty($data['password'])) {
            //     $customer->password = Hash::make($data['password']);
            // }

            $customer->save();

            return [
                'success' => true,
                'code' => 200,
                'message' => 'Profile updated successfully.',
                'data' => [
                    // 'username' => $customer->username,
                    'full_name' => $fullName,
                    'first_name' => $data['first_name'],
                    'second_name' => $data['second_name'],
                    'third_name' => $data['third_name'] ?? '',
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'gender' => $customer->gender,
                    'city' => $customer->city,
                    'address' => $customer->address,
                ],
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Unable to update profile. Please try again.',
                'data' => null,
            ];
        }
    }

    public function getCompanies(array $filters): array
    {
        try {

            $search = trim((string) ($filters['search'] ?? ''));
            $city = trim((string) ($filters['city'] ?? ''));
            $service = trim((string) ($filters['service'] ?? ''));
            $sort = (string) ($filters['sort'] ?? '');

            $perPage = (int) ($filters['per_page'] ?? 10);
            $perPage = max(1, min($perPage, 50));

            $page = (int) ($filters['page'] ?? 1);
            $page = max(1, $page);

            $query = $this->helper->visibleCompanies();

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $like = '%' . $search . '%';
                    $q->where('name', 'like', $like)
                        ->orWhere('city', 'like', $like)
                        ->orWhere('services', 'like', $like)
                        ->orWhere('ar_services', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('ar_description', 'like', $like);
                });
            }

            if ($city !== '') {
                $query->where('city', $city);
            }

            if ($service !== '') {
                $like = '%' . $service . '%';
                $query->where(function ($q) use ($like) {
                    $q->where('services', 'like', $like)
                        ->orWhere('ar_services', 'like', $like);
                });
            }

            // Average stars and count of visible ratings (read by transformCompany).
            $query->withAvg(['ratings as visible_rating' => fn($q) => $q->where('is_visible', true)], 'stars')
                ->withCount(['ratings as visible_reviews' => fn($q) => $q->where('is_visible', true)]);

            if ($sort === 'name') {
                $query->orderBy('name');
            } elseif ($sort === 'rating') {
                // Highest rated first; unrated companies last, verified ones before the rest.
                $query->orderByRaw('visible_rating IS NULL')
                    ->orderByDesc('visible_rating')
                    ->orderByDesc('is_verified')
                    ->orderByDesc('created_at');
            } else {
                $query->orderByDesc('created_at');
            }

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            $companies = [];



            foreach ($paginator->items() as $company) {
                $companyData = $this->helper->transformCompany($company);

                $companyData['description'] = $this->helper->plainText(
                    $company->description
                );

                $companyData['ar_description'] = $this->helper->plainText(
                    $company->ar_description
                );

                $companies[] = $companyData;
            }

            return [
                'success' => true,
                'code' => 200,
                'message' => 'Companies retrieved successfully.',
                'companies' => $companies,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => max(1, $paginator->lastPage()),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Unable to retrieve companies.',
            ];
        }
    }

    public function getCompany(int $id): array
    {
        try {
            $company = $this->helper->visibleCompanies()->where('id', $id)->first();

            if (!$company) {
                return [
                    'success' => false,
                    'code' => 404,
                    'message' => 'Company not found.',
                ];
            }

            return [
                'success' => true,
                'code' => 200,
                'message' => 'Company retrieved successfully.',
                'company' => $this->helper->transformCompany($company, true),
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Unable to retrieve company details.',
            ];
        }
    }

    public function getCities(): array
    {

        $cities = [
            'Khartoum' => 'الخرطوم',
            'Port Sudan' => 'بورتسودان',
            'Atbara' => 'عطبرة',
            'Wad Madani' => 'مدني',
            'Merowe' => 'مروي',
            'Kassala' => 'كسلا',
            'Al Qadarif' => 'القضارف',
            'Dongola' => 'دنقلا',
            'Halfa' => 'حلفا',
        ];

        $list = [];
        $id = 1;

        foreach ($cities as $name => $arName) {
            $list[] = [
                'id' => $id++,
                'name' => $name,
                'ar_name' => $arName,
            ];
        }

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Cities retrieved successfully.',
            'cities' => $list,
        ];
    }

    public function getServices(): array
    {
        if (!$this->helper->isCustomerAuthenticated()) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Unauthenticated.',
            ];
        }

        try {
            $companies = SolarCompany::query()
                ->where('is_active', 1)
                ->where('status', 'approved')
                ->where('is_verified', 1)
                ->get(['services', 'ar_services']);

            $cleanServices = function ($value): array {
                return collect(explode(',', (string) $value))
                    ->map(fn($service) => trim($service))
                    ->filter(fn($service) => $service !== ''
                        && !in_array(mb_strtolower($service), [
                            'none',
                            'null',
                            'n/a',
                            'na',
                            'لاتوجد',
                            'لا توجد',
                            'لا يوجد',
                        ], true))
                    ->unique(fn($service) => mb_strtolower($service))
                    ->values()
                    ->all();
            };

            $services = $cleanServices(
                $companies->pluck('services')->filter()->implode(',')
            );

            $servicesAr = $cleanServices(
                $companies->pluck('ar_services')->filter()->implode(',')
            );

            return [
                'success' => true,
                'code' => 200,
                'message' => 'Services retrieved successfully.',
                'data' => [
                    'services' => $services,
                    'services_ar' => $servicesAr,
                ],
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Unable to retrieve services.',
                'data' => [
                    'services' => [],
                    'services_ar' => [],
                ],
            ];
        }
    }

    public function appliancesCalculator(): array
    {
        try {
            $groups = ApplianceGroup::query()
                ->with([
                    'appliances' => function ($query) {
                        $query->orderBy('sort_order')
                            ->orderBy('id');
                    },
                ])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            return [
                'success' => true,
                'code' => 200,
                'message' => 'Calculator appliances retrieved successfully.',
                'groups' => $groups,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Unable to retrieve calculator appliances.',
                'groups' => [],
            ];
        }
    }


    public function getCarousels(): array
    {
        if (!$this->helper->isCustomerAuthenticated()) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Unauthenticated.',
            ];
        }

        try {
            $now = now();

            /** @var \Illuminate\Database\Eloquent\Collection<int, CompanyCarousel> $carouselRows */
            $carouselRows = CompanyCarousel::query()
                ->where('is_active', true)
                ->whereIn('platform', ['app', 'both'])

                // Carousel schedule
                ->where(function ($query) use ($now) {
                    $query->whereNull('starts_at')
                        ->orWhere('starts_at', '<=', $now);
                })
                ->where(function ($query) use ($now) {
                    $query->whereNull('ends_at')
                        ->orWhere('ends_at', '>=', $now);
                })

                // Only approved, verified, active companies
                ->whereHas('company', function ($query) {
                    $query->where('status', 'approved')
                        ->where('is_verified', true)
                        ->where('is_active', true);
                })

                ->with('company')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->limit(3)
                ->get();

            $carousels = $carouselRows
                ->map(function (CompanyCarousel $carousel): array {
                    return [
                        'id' => (int) $carousel->id,
                        'sort_order' => (int) $carousel->sort_order,
                        'interval_seconds' => (int) $carousel->interval_seconds,
                        'platform' => $carousel->platform,
                        'company' => $this->helper->transformCompany(
                            $carousel->company
                        ),
                    ];
                })
                ->values()
                ->all();

            return [
                'success' => true,
                'code' => 200,
                'message' => 'Carousels retrieved successfully.',
                'carousels' => $carousels,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Unable to retrieve carousels.',
                'carousels' => [],
            ];
        }
    }


    public function storeQuote(array $data): array
    {

        $customer = $this->helper->isCustomerAuthenticated();

        if (!$customer) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Unauthenticated.',
                'quote' => null,
            ];
        }

        // Verify the submitted customer ID against the authenticated customer.
        if (
            isset($data['customer_id']) &&
            (int) $data['customer_id'] !== (int) $customer->id
        ) {
            return [
                'success' => false,
                'code' => 403,
                'message' => 'The customer ID does not match the authenticated account.',
                'quote' => null,
            ];
        }

        // Always use the authenticated customer's ID.
        $data['customer_id'] = (int) $customer->id;

        $existingQuote = Quote::query()
            ->where('customer_id', $data['customer_id'])
            ->where('peak_load', $data['peak_load'])
            ->where('daily_load', $data['daily_load'])
            ->whereIn('status', ['pending', 'accepted'])
            ->first();

        if ($existingQuote) {
            return [
                'success' => false,
                'code' => 409,
                'message' => 'You have already submitted this quote.',
                'quote' => [
                    'id' => $existingQuote->id,
                    'customer_id' => $existingQuote->customer_id,
                    'company_id' => $existingQuote->company_id,
                    'quote_name' => $existingQuote->quote_name,
                    'service_needed' => $existingQuote->quote_name,
                    'estimated_load' => $existingQuote->estimated_load,
                    'peak_load' => $existingQuote->peak_load,
                    'daily_load' => $existingQuote->daily_load,
                    'message' => $existingQuote->message,
                    'status' => $existingQuote->status,
                    'created_at' => $existingQuote->created_at,
                ],
            ];
        }

        // Open = still pending, i.e. no offer accepted yet. Accepting an offer frees a slot.
        $maxOpenQuotes = Setting::maxOpenQuotes();

        $openQuotes = Quote::query()
            ->where('customer_id', $data['customer_id'])
            ->where('status', 'pending')
            ->count();

        if ($openQuotes >= $maxOpenQuotes) {
            return [
                'success' => false,
                'code' => 422,
                'message' => "You can have up to {$maxOpenQuotes} open requests at a time. "
                    . 'Accept an offer or close a request to make room.',
                'quote' => null,
                'data' => [
                    'reason' => 'quote_limit_reached',
                    'max_open_quotes' => $maxOpenQuotes,
                ],
            ];
        }

        try {
            // Validate the target company when this is not an open quote.
            if ($data['company_id'] !== null) {
                $companyExists = SolarCompany::query()
                    ->whereKey($data['company_id'])
                    ->where('status', 'approved')
                    ->where('is_verified', 1)
                    ->where('is_active', 1)
                    ->exists();

                if (!$companyExists) {
                    return [
                        'success' => false,
                        'code' => 422,
                        'message' => 'The selected company is unavailable.',
                        'quote' => null,
                    ];
                }
            }

            $quote = DB::transaction(function () use ($data) {
                $quote = new Quote();

                $quote->customer_id = $data['customer_id'];
                $quote->company_id = $data['company_id'];
                $quote->quote_name = $data['quote_name'];
                $quote->estimated_load = $data['estimated_load'];
                $quote->peak_load = $data['peak_load'];
                $quote->daily_load = $data['daily_load'];
                $quote->message = $data['message'];
                $quote->status = 'pending';

                $quote->save();

                return $quote;
            });

            return [
                'success' => true,
                'code' => 201,
                'message' => 'Quote submitted successfully.',
                'quote' => [
                    'id' => $quote->id,
                    'customer_id' => $quote->customer_id,
                    'company_id' => $quote->company_id,
                    'quote_name' => $quote->quote_name,
                    'service_needed' => $quote->quote_name,
                    'estimated_load' => $quote->estimated_load,
                    'message' => $quote->message,
                    'status' => $quote->status,
                    'created_at' => $quote->created_at,
                ],
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Unable to submit quote. Please try again later.',
                'quote' => null,
            ];
        }
    }

    public function getMyQuotes(array $filters): array
    {
        try {
            // Authenticate the customer.
            $customer = $this->helper->isCustomerAuthenticated();

            if (!$customer) {
                return [
                    'success' => false,
                    'code' => 401,
                    'message' => 'Unauthenticated.',
                    'quotes' => [],
                    'meta' => null,
                ];
            }

            $search = trim((string) ($filters['search'] ?? ''));
            $lowPrice = trim((string) ($filters['low_price'] ?? ''));
            $sort = (string) ($filters['sort'] ?? '');

            $perPage = (int) ($filters['per_page'] ?? 10);
            $perPage = max(1, min($perPage, 50));

            $page = max(1, (int) ($filters['page'] ?? 1));

            $customerId = $customer->id;

            // Build the query for this customer only.
            $query = Quote::query()
                ->where('customer_id', $customerId)
                ->with([
                    'companyQuotations' => function ($offerQuery) {
                        $offerQuery
                            ->with('company')
                            ->where('status', '!=', 'withdrawn');
                    },

                    // Include the project associated with this quote.
                    'project' => function ($projectQuery) {
                        $projectQuery->select([
                            'id',
                            'quote_id',
                            'customer_id',
                            'company_id',
                            'status',
                            'progress_percent',
                            'accepted_at',
                            'started_at',
                            'completed_at',
                        ]);
                    },

                    // The customer's own rating, so the app hides "Rate Company" once rated.
                    'rating:id,quote_id,customer_id,stars',
                ]);

            // Search quote fields.
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('quote_name', 'like', '%' . $search . '%')
                        ->orWhere('estimated_load', 'like', '%' . $search . '%')
                        ->orWhere('message', 'like', '%' . $search . '%');
                });
            }

            // Filter quotes that have at least one offer at or below this price.
            if ($lowPrice !== '' && is_numeric($lowPrice) && (float) $lowPrice >= 0) {
                $query->whereHas('companyQuotations', function ($offerQuery) use ($lowPrice) {
                    $offerQuery
                        ->where('status', '!=', 'withdrawn')
                        ->where('price', '<=', (float) $lowPrice);
                });
            }

            // Sort quote requests.
            switch ($sort) {
                case 'oldest':
                    $query->orderBy('created_at')->orderBy('id');
                    break;

                case 'newest':
                default:
                    $query->orderByDesc('created_at')->orderByDesc('id');
                    break;
            }

            // Paginate in the database instead of loading every quote.
            $paginator = $query->paginate(
                $perPage,
                ['*'],
                'page',
                $page
            );

            /** @var \Illuminate\Support\Collection<int, Quote> $quoteRows */
            $quoteRows = $paginator->getCollection();

            // Average stars per company on this page, in one query.
            $companyRatings = $this->companyRatingSummaries(
                $quoteRows->flatMap(fn(Quote $quote) => $quote->companyQuotations->pluck('company_id'))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all()
            );

            // Build the API response.
            $quotes = $quoteRows
                ->map(function (Quote $quote) use ($companyRatings, $customerId) {
                    $ownRating = $quote->rating && (int) $quote->rating->customer_id === (int) $customerId
                        ? $quote->rating
                        : null;

                    return [
                        'id' => $quote->id,
                        'reference' => '#Q-' . str_pad(
                            (string) $quote->id,
                            5,
                            '0',
                            STR_PAD_LEFT
                        ),
                        'quote_name' => $quote->quote_name,
                        // Kept for app builds that still read the old key.
                        'service_needed' => $quote->quote_name,
                        'customer_rating' => $ownRating?->stars,
                        'estimated_load' => $quote->estimated_load,
                        'daily_load' => $quote->daily_load,
                        'peak_load' => $quote->peak_load,
                        'message' => $quote->message,

                        // Overall customer quote-request status.
                        'status' => $quote->status,

                        'created_at' => $quote->created_at?->toISOString(),
                        'updated_at' => $quote->updated_at?->toISOString(),

                        // Associated project and its current status.
                        'project_id' => $quote->project?->id,
                        'project_status' => $quote->project?->status,
                        'project_progress_percent' => $quote->project
                            ? (int) $quote->project->progress_percent
                            : 0,

                        'project_accepted_at' => $quote->project?->accepted_at?->toISOString(),
                        'project_started_at' => $quote->project?->started_at?->toISOString(),
                        'project_completed_at' => $quote->project?->completed_at?->toISOString(),

                        // Individual company offers and their statuses.
                        'offers' => $quote->companyQuotations
                            ->map(function ($offer) use ($companyRatings) {
                                $summary = $companyRatings[(int) $offer->company_id] ?? null;

                                return [
                                    'id' => $offer->id,
                                    'company_id' => $offer->company?->id,
                                    'company_name' => $offer->company?->name,
                                    'company_logo' => $offer->company?->logo_path,
                                    // Null until the company has at least one rating.
                                    'company_rating' => $summary['rating'] ?? null,
                                    'company_reviews' => $summary['reviews'] ?? 0,
                                    'price' => (float) $offer->price,
                                    'currency' => $offer->currency,
                                    'message' => $offer->message,
                                    'ar_message' => $offer->ar_message,
                                    'status' => $offer->status,
                                    'created_at' => $offer->created_at?->toISOString(),
                                ];
                            })
                            ->values(),
                    ];
                })
                ->values();

            return [
                'success' => true,
                'code' => 200,
                'message' => 'Quote requests retrieved successfully.',
                'quotes' => $quotes,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => max(1, $paginator->lastPage()),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Unable to retrieve quote requests.',
                'quotes' => [],
                'meta' => null,
            ];
        }
    }



    public function acceptOffer(array $data): array
    {
        try {
            // Identify the authenticated customer.
            $customer = $this->helper->isCustomerAuthenticated();

            if (!$customer) {
                return [
                    'success' => false,
                    'code' => 401,
                    'message' => 'Unauthenticated.',
                    'data' => null,
                ];
            }

            return DB::transaction(function () use ($data, $customer) {

                // Lock the quote while processing acceptance.
                $quote = Quote::query()
                    ->where('id', $data['quote_id'])
                    ->where('customer_id', $customer->id)
                    ->lockForUpdate()
                    ->first();

                if (!$quote) {
                    return [
                        'success' => false,
                        'code' => 404,
                        'message' => 'Quote request not found.',
                        'data' => null,
                    ];
                }

                // Lock and verify the selected offer.
                $offer = CompanyQuotation::query()
                    ->where('id', $data['offer_id'])
                    ->where('quote_id', $data['quote_id'])
                    ->where('company_id', $data['company_id'])
                    ->where('customer_id', $customer->id)
                    ->lockForUpdate()
                    ->first();

                if (!$offer) {
                    return [
                        'success' => false,
                        'code' => 404,
                        'message' => 'The selected offer was not found for this quote and company.',
                        'data' => null,
                    ];
                }

                if (in_array($quote->status, ['accepted', 'cancelled', 'rejected'], true)) {
                    return [
                        'success' => false,
                        'code' => 409,
                        'message' => 'This quote request can no longer accept offers.',
                        'data' => null,
                    ];
                }

                // Only submitted offers can be accepted.
                if ($offer->status !== 'submitted') {
                    return [
                        'success' => false,
                        'code' => 409,
                        'message' => 'This offer is no longer available for acceptance.',
                        'data' => null,
                    ];
                }

                // Select the accepted offer.
                CompanyQuotation::query()
                    ->where('quote_id', $quote->id)
                    ->where('id', $offer->id)
                    ->update([
                        'status' => 'selected',
                        'updated_at' => now(),
                    ]);

                // Mark other submitted offers as not selected.
                // Preserve offers already withdrawn.
                CompanyQuotation::query()
                    ->where('quote_id', $quote->id)
                    ->where('id', '!=', $offer->id)
                    ->where('status', 'submitted')
                    ->update([
                        'status' => 'not_selected',
                        'updated_at' => now(),
                    ]);

                // Update the original quote request.
                $quote->company_id = $offer->company_id;
                $quote->status = 'accepted';
                $quote->save();

                // Create the project for the accepted offer.
                $project = Project::query()->create([
                    'quote_id' => $quote->id,
                    'company_quotation_id' => $offer->id,
                    'customer_id' => $customer->id,
                    'company_id' => $offer->company_id,
                    'status' => 'accepted',
                    'progress_percent' => 0,
                    'accepted_at' => now(),
                ]);

                // Create the default installation checklist.
                $milestones = [
                    [
                        'title' => 'Site inspection',
                        'description' => 'Visit the customer site and confirm installation details.',
                        'sort_order' => 1,
                    ],
                    [
                        'title' => 'Equipment procurement',
                        'description' => 'Order and receive solar panels, inverter, and batteries.',
                        'sort_order' => 2,
                    ],
                    [
                        'title' => 'Installation & wiring',
                        'description' => 'Install solar panels, inverter, and complete wiring.',
                        'sort_order' => 3,
                    ],
                    [
                        'title' => 'System testing',
                        'description' => 'Test system performance and safety.',
                        'sort_order' => 4,
                    ],
                    [
                        'title' => 'Customer handover',
                        'description' => 'Explain system operation and hand over to the customer.',
                        'sort_order' => 5,
                    ],
                ];

                foreach ($milestones as $milestone) {
                    ProjectMilestone::query()->create([
                        'project_id' => $project->id,
                        'title' => $milestone['title'],
                        'description' => $milestone['description'],
                        'status' => 'pending',
                        'sort_order' => $milestone['sort_order'],
                    ]);
                }

                // Record the initial project activity.
                ProjectUpdate::query()->create([
                    'project_id' => $project->id,
                    'updated_by_type' => 'customer',
                    'updated_by_id' => $customer->id,
                    'old_status' => null,
                    'new_status' => 'accepted',
                    'progress_percent' => 0,
                    'message' => 'The customer accepted the offer. The project is ready for preparation.',
                    'visible_to_customer' => true,
                ]);

                // Email the company that its offer was accepted (queued until commit).
                SendCompanyOfferDecisionEmail::dispatch($offer->id, CompanyOfferDecisionMail::ACCEPTED);

                return [
                    'success' => true,
                    'code' => 200,
                    'message' => 'Offer accepted successfully and project created.',
                    'data' => [

                        'quote_id' => (int) $quote->id,
                        'offer_id' => (int) $offer->id,
                        'company_id' => (int) $offer->company_id,
                        'project_id' => (int) $project->id,
                        'offer_status' => 'selected',
                        'quote_status' => 'accepted',
                        'project_status' => 'accepted',
                        'progress_percent' => 0,
                        'milestones_created' => count($milestones),
                    ],
                ];
            });
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Unable to accept the offer. Please try again.',
                'data' => null,
            ];
        }
    }

    public function rejectOffer(array $data): array
    {
        try {
            // Identify the authenticated customer.
            $customer = $this->helper->isCustomerAuthenticated();

            if (!$customer) {
                return [
                    'success' => false,
                    'code' => 401,
                    'message' => 'Unauthenticated.',
                    'data' => null,
                ];
            }

            $offer = CompanyQuotation::query()
                ->where('id', $data['offer_id'])
                ->where('quote_id', $data['quote_id'])
                ->where('company_id', $data['company_id'])
                ->whereHas('quote', function ($query) use ($customer) {
                    $query->where('customer_id', $customer->id);
                })
                ->where('status', 'submitted')
                ->first();

            if (!$offer) {
                return [
                    'success' => false,
                    'code' => 404,
                    'message' => 'Offer not found, cannot be rejected.',
                    'data' => null,
                ];
            }

            $offer->status = 'withdrawn';
            $offer->save();

            // Email the company that its offer was rejected.
            SendCompanyOfferDecisionEmail::dispatch($offer->id, CompanyOfferDecisionMail::REJECTED);

            return [
                'success' => true,
                'code' => 200,
                'message' => 'Offer rejected successfully.',
                'data' => [
                    'offer_id' => $offer->id,
                    'quote_id' => $offer->quote_id,
                    'company_id' => $offer->company_id,
                    'status' => $offer->status,
                ],
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Unable to reject the offer. Please try again.',
                'data' => null,
            ];
        }
    }

    //Ratings Start

    /**
     * Customer rates the company whose offer they accepted on a quote.
     * One rating per quote; overall stars 1-5 plus optional 1-4 answers per question.
     */
    public function submitRating(array $data): array
    {
        try {
            $customer = $this->helper->isCustomerAuthenticated();

            if (!$customer) {
                return [
                    'success' => false,
                    'code' => 401,
                    'message' => 'Unauthenticated.',
                    'data' => null,
                ];
            }

            $quote = Quote::query()->with('project:id,quote_id')->find($data['quote_id']);

            if (!$quote) {
                return [
                    'success' => false,
                    'code' => 404,
                    'message' => 'Quote request not found.',
                    'data' => null,
                ];
            }

            if ((int) $quote->customer_id !== (int) $customer->id) {
                return [
                    'success' => false,
                    'code' => 403,
                    'message' => 'This quote request does not belong to your account.',
                    'data' => null,
                ];
            }

            // Only the company whose offer was accepted can be rated.
            $acceptedOffer = CompanyQuotation::query()
                ->where('quote_id', $quote->id)
                ->where('status', 'selected')
                ->first();

            if (!$acceptedOffer) {
                return [
                    'success' => false,
                    'code' => 422,
                    'message' => 'You can rate a company after accepting its offer.',
                    'data' => ['reason' => 'no_accepted_offer'],
                ];
            }

            if ((int) $acceptedOffer->company_id !== (int) $data['company_id']) {
                return [
                    'success' => false,
                    'code' => 422,
                    'message' => 'You can only rate the company whose offer you accepted.',
                    'data' => ['reason' => 'company_mismatch'],
                ];
            }

            $alreadyRated = Rating::query()
                ->where('customer_id', $customer->id)
                ->where('quote_id', $quote->id)
                ->exists();

            if ($alreadyRated) {
                return $this->alreadyRatedResponse();
            }

            // Answers: only active questions, each once, value on the 1-4 scale.
            $activeQuestionIds = RatingQuestion::query()->active()->pluck('id')->map(fn($id) => (int) $id)->all();

            $answers = collect($data['answers'] ?? [])
                ->filter(fn($answer) => in_array((int) $answer['question_id'], $activeQuestionIds, true))
                ->keyBy(fn($answer) => (int) $answer['question_id'])
                ->map(fn($answer) => (int) $answer['value']);

            $comment = trim((string) ($data['comment'] ?? ''));

            $rating = DB::transaction(function () use ($customer, $quote, $acceptedOffer, $data, $answers, $comment) {
                $rating = Rating::query()->create([
                    'customer_id' => $customer->id,
                    'company_id' => $acceptedOffer->company_id,
                    'quote_id' => $quote->id,
                    // Taken from the server, not the request, so they always match the quote.
                    'offer_id' => $acceptedOffer->id,
                    'project_id' => $quote->project?->id,
                    'stars' => (int) $data['stars'],
                    'comment' => $comment !== '' ? $comment : null,
                    'is_visible' => true,
                ]);

                foreach ($answers as $questionId => $value) {
                    $rating->answers()->create([
                        'question_id' => $questionId,
                        'value' => $value,
                    ]);
                }

                return $rating;
            });

            $summary = $this->companyRatingSummaries([(int) $rating->company_id])[(int) $rating->company_id]
                ?? ['rating' => null, 'reviews' => 0];

            return [
                'success' => true,
                'code' => 201,
                'message' => 'Thank you for your rating',
                'data' => [
                    'ratingDetails' => [
                        'id' => (int) $rating->id,
                        'quote_id' => (int) $rating->quote_id,
                        'company_id' => (int) $rating->company_id,
                        'offer_id' => (int) $rating->offer_id,
                        'project_id' => $rating->project_id ? (int) $rating->project_id : null,
                        'stars' => (int) $rating->stars,
                        'comment' => $rating->comment,
                        'answers' => $answers->map(fn($value, $questionId) => [
                            'question_id' => (int) $questionId,
                            'value' => $value,
                        ])->values()->all(),
                        'created_at' => $rating->created_at?->toISOString(),
                        'company_rating' => $summary['rating'],
                        'company_reviews' => $summary['reviews'],
                    ],
                ],
            ];
        } catch (UniqueConstraintViolationException $e) {
            // Two taps at once: the unique index stops the second row.
            return $this->alreadyRatedResponse();
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Unable to submit your rating. Please try again.',
                'data' => null,
            ];
        }
    }

    /** Active questions and the 1-4 answer labels, for the app's rating screen. */
    public function getRatingForm(): array
    {
        try {
            /** @var \Illuminate\Database\Eloquent\Collection<int, RatingQuestion> $questionRows */
            $questionRows = RatingQuestion::query()
                ->active()
                ->ordered()
                ->get(['id', 'title_en', 'title_ar', 'question_en', 'question_ar']);

            $questions = $questionRows
                ->map(fn(RatingQuestion $question) => [
                    'id' => (int) $question->id,
                    'title_en' => $question->title_en,
                    'title_ar' => $question->title_ar,
                    'question_en' => $question->question_en,
                    'question_ar' => $question->question_ar,
                ])
                ->values()
                ->all();

            /** @var \Illuminate\Database\Eloquent\Collection<int, RatingValue> $valueRows */
            $valueRows = RatingValue::query()
                ->orderBy('value')
                ->get(['value', 'label_en', 'label_ar']);

            $values = $valueRows
                ->map(fn(RatingValue $value) => [
                    'value' => (int) $value->value,
                    'label_en' => $value->label_en,
                    'label_ar' => $value->label_ar,
                ])
                ->values()
                ->all();

            return [
                'success' => true,
                'code' => 200,
                'message' => 'Rating form retrieved successfully.',
                'data' => [
                    'questions' => $questions,
                    'values' => $values,
                    'stars' => ['min' => Rating::MIN_STARS, 'max' => Rating::MAX_STARS],
                ],
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Unable to load the rating form.',
                'data' => null,
            ];
        }
    }

    /**
     * Average stars and count of visible ratings, keyed by company ID.
     * Companies without ratings are left out.
     *
     * @return array<int, array{rating: float, reviews: int}>
     */
    public function companyRatingSummaries(array $companyIds): array
    {
        if (empty($companyIds)) {
            return [];
        }

        return Rating::query()
            ->visible()
            ->whereIn('company_id', $companyIds)
            ->groupBy('company_id')
            ->selectRaw('company_id, AVG(stars) as avg_stars, COUNT(*) as total')
            ->get()
            ->mapWithKeys(fn($row) => [
                (int) $row->company_id => [
                    'rating' => round((float) $row->avg_stars, 1),
                    'reviews' => (int) $row->total,
                ],
            ])
            ->all();
    }

    private function alreadyRatedResponse(): array
    {
        return [
            'success' => false,
            'code' => 422,
            'message' => 'You have already rated this company for this request',
            'data' => ['reason' => 'already_rated'],
        ];
    }

    //Ratings End

    public function getProjectProgress(int $projectId): Project|array|null
    {
        $customer = $this->helper->isCustomerAuthenticated();

        if (!$customer) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Unauthenticated.',

            ];
        }

        $customerId = data_get($customer, 'id');

        if (!$customerId) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Unable to identify the authenticated customer.',
            ];
        }

        return Project::query()
            ->whereKey($projectId)
            ->where('customer_id', $customerId)
            ->with([
                'quote',
                'customer',
                'companyQuotation',
                'milestones' => function ($query) {
                    $query->orderBy('sort_order')
                        ->orderBy('id');
                },
                'updates' => function ($query) {
                    $query->where('visible_to_customer', true)
                        ->orderByDesc('created_at');
                },
            ])
            ->first();
    }

    public function saveCustomerDeviceToken(
        int $customerId,
        string $token,
        string $platform,
        string $lang = 'en',
        ?string $deviceName = null,
        ?bool $isActive = null
    ): CustomerDeviceToken {
        $lang = strtolower($lang) === 'ar' ? 'ar' : 'en';

        $tokenHash = hash('sha256', $token);

        $values = [
            'customer_id' => $customerId,
            'fcm_token' => $token,
            'platform' => $platform,
            'lang' => $lang,
            'device_name' => $deviceName,
            'last_used_at' => now(),
        ];

        // Only change the toggle when the client sends it, so a plain
        // token refresh doesn't re-enable notifications the user turned off.
        if ($isActive !== null) {
            $values['is_active'] = $isActive;
        }

        return CustomerDeviceToken::updateOrCreate(
            ['token_hash' => $tokenHash],
            $values
        );
    }

    public function getCustomerNotifications(array $filters = []): array
    {
        // Resolve the authenticated customer in the repository.
        $customer = $this->helper->isCustomerAuthenticated();

        if (!$customer) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Unauthenticated.',

            ];
        }

        $customerId = $customer->id;

        $query = CustomerNotification::query()
            ->with('notification')
            ->where('customer_id', $customerId)
            ->whereHas('notification', function ($query) {
                $query->where('status', 'published');
            });

        $unreadCount = (clone $query)
            ->whereNull('read_at')
            ->count();

        $perPage = min(
            max((int) ($filters['per_page'] ?? 20), 1),
            100
        );

        $recipients = $query
            ->latest('created_at')
            ->paginate($perPage);

        /** @var \Illuminate\Support\Collection<int, CustomerNotification> $recipientRows */
        $recipientRows = $recipients->getCollection();

        $notifications = $recipientRows
            ->map(function (CustomerNotification $recipient) {
                $notification = $recipient->notification;

                if (!$notification) {
                    return null;
                }

                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title_en,
                    'ar_title' => $notification->title_ar,
                    'body' => $notification->message_en,
                    'ar_body' => $notification->message_ar,

                    'read' => $recipient->read_at !== null,
                    'created_at' => $recipient->created_at
                        ? $recipient->created_at->utc()->format(
                            'Y-m-d\TH:i:s\Z'
                        )
                        : null,
                ];
            })
            ->filter()
            ->values();

        return [
            'unread_count' => $unreadCount,
            'notifications' => $notifications->all(),
            'pagination' => [
                'current_page' => $recipients->currentPage(),
                'last_page' => $recipients->lastPage(),
                'per_page' => $recipients->perPage(),
                'total' => $recipients->total(),
            ],
        ];
    }

    public function markNotificationAsRead($notificationId): array
    {

        $customer = $this->helper->isCustomerAuthenticated();

        if (!$customer) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Unauthenticated.',

            ];
        }


        $customerID = $customer->id;

        $notification = CustomerNotification::query()->where('id', $notificationId)->where('customer_id', $customerID)->first();

        if (!$notification) {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Notification not found',
            ];
        }

        // Mark as read
        if (is_null($notification->read_at)) {
            $notification->update([
                'read_at' => now(),
            ]);
        }

        // Get remaining unread count
        $unreadCount = CustomerNotification::query()->where('customer_id', $customerID)->whereNull('read_at')->count();

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Notification marked as read successfully',
            'notificationDetails' => [
                'id' => $notification->id,
                'is_read' => true,
                'read_at' => $notification->fresh()->read_at?->toISOString(),
            ],
            'unread_count' => $unreadCount,
        ];
    }

    public function markAllNotificationsAsRead(): array
    {

        $customer = $this->helper->isCustomerAuthenticated();

        if (!$customer) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Unauthenticated.',

            ];
        }

        $customerID = $customer->id;

        // Mark all unread notifications as read
        $updatedCount = CustomerNotification::query()->where('customer_id', $customerID)->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        return [
            'success' => true,
            'code' => 200,
            'message' => 'All notifications marked as read successfully',
            'notificationDetails' => [
                'updated_count' => $updatedCount,
                'unread_count' => 0,
            ],
        ];
    }


    public function deleteAccount(): array
    {
        try {
            $customer = $this->helper->isCustomerAuthenticated();

            if (!$customer) {
                return [
                    'success' => false,
                    'code' => 401,
                    'message' => 'Unauthenticated.',
                ];
            }

            // Revoke all API tokens belonging to this customer.
            $customer->tokens()->delete();

            // Soft-delete the customer account, if SoftDeletes is enabled.
            $customer->delete();

            return [
                'success' => true,
                'code' => 200,
                'message' => 'Your account has been deleted successfully.',
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Unable to delete your account. Please try again.',
            ];
        }
    }

    public function logout(): array
    {
        $customer = auth('sanctum')->user();

        if (!$customer) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Unauthenticated.',
            ];
        }

        $token = $customer->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Logged out successfully.',
        ];
    }

    //Password Reset (public, no token) Start

    /**
     * Step 1: phone + email must belong to the same active customer; send a 6-digit code.
     */
    public function forgotPassword(array $data): array
    {
        $lang = $data['lang'];
        $channel = $data['channel'];

        if (!in_array($channel, OtpRepository::availableChannels(), true)) {
            return $this->passwordResetError(422, 'channel_unavailable', $lang, [
                'available_channels' => OtpRepository::availableChannels(),
            ]);
        }

        $found = $this->findCustomerForPasswordReset($data['phone'], $data['email'], $lang);

        if (!$found['success']) {
            return $found;
        }

        $customer = $found['customer'];

        $sent = $this->otpRepository->sendPasswordResetCode(
            'customer',
            strtolower($customer->email),
            (string) ($customer->full_name ?: $customer->username),
            'customer',
            $lang,
            $channel,
            $customer->phone
        );

        if (!$sent['success']) {
            return $this->passwordResetError(503, $sent['reason'], $lang);
        }

        return [
            'success' => true,
            'code' => 200,
            'message' => $this->passwordResetMessage($channel === 'sms' ? 'code_sent_sms' : 'code_sent_email', $lang),
            'data' => [
                'channel' => $channel,
                'sent_to' => $channel === 'sms'
                    ? $this->maskPhone($customer->phone)
                    : $this->maskEmail($customer->email),
                'otp_length' => 6,
                'expires_in' => OtpRepository::EXPIRES_SECONDS,
                'available_channels' => OtpRepository::availableChannels(),
            ],
        ];
    }

    /**
     * Step 2: check the code; a correct code is exchanged for a reset token.
     */
    public function verifyPasswordResetOtp(array $data): array
    {
        $lang = $data['lang'];

        $found = $this->findCustomerForPasswordReset($data['phone'], $data['email'], $lang);

        if (!$found['success']) {
            return $found;
        }

        $email = strtolower($found['customer']->email);

        $check = $this->otpRepository->verifyPasswordResetCode('customer', $email, $data['otp']);

        if (!$check['success']) {
            return $this->passwordResetError(422, $check['reason'], $lang, array_filter([
                'attempts_remaining' => $check['attempts_remaining'] ?? null,
            ], fn($value) => $value !== null));
        }

        $this->otpRepository->consume($check['otp']);

        $token = $this->otpRepository->issueResetToken('customer', $email);

        return [
            'success' => true,
            'code' => 200,
            'message' => $this->passwordResetMessage('code_verified', $lang),
            'data' => [
                'reset_token' => $token['token'],
                'expires_in' => OtpRepository::RESET_TOKEN_EXPIRES_SECONDS,
            ],
        ];
    }

    /**
     * Step 3: set the new password with the reset token. Signs the customer out everywhere.
     */
    public function resetPassword(array $data): array
    {
        $lang = $data['lang'];

        $found = $this->findCustomerForPasswordReset($data['phone'], $data['email'], $lang);

        if (!$found['success']) {
            return $found;
        }

        $customer = $found['customer'];
        $email = strtolower($customer->email);

        $check = $this->otpRepository->verifyResetToken('customer', $email, $data['reset_token']);

        if (!$check['success']) {
            return $this->passwordResetError(422, $check['reason'], $lang);
        }

        // The "hashed" cast hashes the new password.
        $customer->password = $data['password'];
        $customer->save();

        // Revoking the Sanctum tokens signs the app out on every device; it must log in again.
        $customer->tokens()->delete();

        $this->otpRepository->consume($check['otp']);

        return [
            'success' => true,
            'code' => 200,
            'message' => $this->passwordResetMessage('password_reset', $lang),
        ];
    }

    private function findCustomerForPasswordReset(string $phone, string $email, string $lang): array
    {
        $customer = Customer::query()
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])
            ->first();

        // Phones are stored as 0XXXXXXXXX or +249XXXXXXXXX: compare the last 9 digits.
        $lastDigits = fn(?string $value) => substr(preg_replace('/\D/', '', (string) $value), -9);

        if (!$customer || $lastDigits($customer->phone) === '' || $lastDigits($customer->phone) !== $lastDigits($phone)) {
            return $this->passwordResetError(404, 'account_not_found', $lang);
        }

        if (!$customer->is_active) {
            return $this->passwordResetError(403, 'account_inactive', $lang);
        }

        return ['success' => true, 'customer' => $customer];
    }

    private function passwordResetError(int $code, string $reason, string $lang, array $extra = []): array
    {
        return [
            'success' => false,
            'code' => $code,
            'reason' => $reason,
            'message' => $this->passwordResetMessage($reason, $lang),
            'extra' => $extra,
        ];
    }

    private function passwordResetMessage(string $key, string $lang): string
    {
        $messages = [
            'code_sent_email' => [
                'en' => 'We sent a 6-digit code to your email. It expires in 1 minute.',
                'ar' => 'أرسلنا رمزاً من 6 أرقام إلى بريدك الإلكتروني. تنتهي صلاحيته خلال دقيقة واحدة.',
            ],
            'code_sent_sms' => [
                'en' => 'We sent a 6-digit code to your phone. It expires in 1 minute.',
                'ar' => 'أرسلنا رمزاً من 6 أرقام إلى هاتفك. تنتهي صلاحيته خلال دقيقة واحدة.',
            ],
            'code_verified' => [
                'en' => 'Code verified. You can now set a new password.',
                'ar' => 'تم التحقق من الرمز. يمكنك الآن تعيين كلمة مرور جديدة.',
            ],
            'password_reset' => [
                'en' => 'Your password has been reset. Please log in with your new password.',
                'ar' => 'تمت إعادة تعيين كلمة المرور. يرجى تسجيل الدخول بكلمة المرور الجديدة.',
            ],
            'account_not_found' => [
                'en' => 'No account matches this phone number and email.',
                'ar' => 'لا يوجد حساب مطابق لرقم الهاتف والبريد الإلكتروني.',
            ],
            'account_inactive' => [
                'en' => 'This account is inactive. Please contact support.',
                'ar' => 'هذا الحساب غير نشط. يرجى التواصل مع الدعم.',
            ],
            'channel_unavailable' => [
                'en' => 'SMS is not available yet. Please use email.',
                'ar' => 'الرسائل النصية غير متاحة حالياً. يرجى استخدام البريد الإلكتروني.',
            ],
            'send_failed' => [
                'en' => 'We could not send the code right now. Please try again in a moment.',
                'ar' => 'تعذّر إرسال الرمز حالياً. يرجى المحاولة بعد قليل.',
            ],
            'no_active_code' => [
                'en' => 'No active code was found. Please request a new code.',
                'ar' => 'لا يوجد رمز صالح. يرجى طلب رمز جديد.',
            ],
            'expired' => [
                'en' => 'This code has expired. Please request a new code.',
                'ar' => 'انتهت صلاحية الرمز. يرجى طلب رمز جديد.',
            ],
            'too_many_attempts' => [
                'en' => 'Too many incorrect attempts. Please request a new code.',
                'ar' => 'محاولات خاطئة كثيرة. يرجى طلب رمز جديد.',
            ],
            'incorrect_code' => [
                'en' => 'The code is incorrect.',
                'ar' => 'الرمز غير صحيح.',
            ],
            'invalid_reset_token' => [
                'en' => 'Your reset session has expired. Please request a new code.',
                'ar' => 'انتهت جلسة إعادة التعيين. يرجى طلب رمز جديد.',
            ],
        ];

        return $messages[$key][$lang] ?? $messages[$key]['en'] ?? $key;
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($name, 0, 2) . str_repeat('*', max(3, mb_strlen($name) - 2)) . '@' . $domain;
    }

    private function maskPhone(?string $phone): string
    {
        $phone = (string) $phone;

        return substr($phone, 0, 3) . str_repeat('*', max(0, strlen($phone) - 5)) . substr($phone, -2);
    }

    //Password Reset End

}

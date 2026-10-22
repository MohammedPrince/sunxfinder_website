<?php

namespace App\Repositories\Company;

use App\Models\CompanyQuotation;
use App\Models\Customer;
use App\Models\CustomerNotification;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quote;
use App\Models\SolarCompany;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;
use Illuminate\Database\Eloquent\Builder;
use App\Models\CustomerDeviceToken;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Illuminate\Support\Facades\Log;


class CompanyRepository
{
    public function __construct()
    {
        //
    }


    //Company profile START

    public function getCompany(int $id): array
    {
        try {
            $company = SolarCompany::find($id);

            if (!$company) {
                return [
                    'success' => false,
                    'message' => 'Company not found.',
                ];
            }

            return [
                'success' => true,
                'message' => 'Company retrieved successfully.',
                'company' => $company,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to retrieve company details.',
            ];
        }
    }


    public function updateCompanyProfile(int $id, array $data): array
    {
        try {
            $company = SolarCompany::find($id);

            if (!$company) {
                return [
                    'success' => false,
                    'message' => 'Company not found.',
                ];
            }

            $company->name = $data['name'];
            $company->city = $data['city'];
            $company->license_number = $data['license_number'];
            $company->description = $data['description'];
            $company->ar_description = $data['ar_description'] ?? null;
            $company->services = $data['services'];
            $company->ar_services = $data['ar_services'] ?? null;
            $company->phone = $data['phone'];
            $company->email = $data['email'];
            $company->social_media = $data['social_media'] ?? null;
            $company->year_of_exp = $data['year_of_exp'] ?? null;
            $company->number_of_projects = $data['number_of_projects'] ?? null;

            // Update password only when a new password is provided.
            // The password is already hashed by the controller.
            if (!empty($data['password'])) {
                $company->password = $data['password'];
            }

            // Update logo only when a new logo is provided.
            if (!empty($data['logo_path'])) {
                $company->logo_path = $data['logo_path'];
            }

            if (!$company->save()) {
                return [
                    'success' => false,
                    'message' => 'Unable to update the company profile.',
                ];
            }

            return [
                'success' => true,
                'message' => 'Company profile updated successfully.',
                'company' => $company->fresh(),
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'An error occurred while updating the company profile.',
            ];
        }
    }

    public function paginateForCompany(
        int $companyId,
        array $filters = []
    ): LengthAwarePaginator {
        $query = Product::query()
            ->where('company_id', $companyId)
            ->with(['supplier', 'specs'])
            ->latest();

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        return $query->paginate(10)->withQueryString();
    }

    public function findForCompany(
        int $companyId,
        int $productId
    ): Product {
        return Product::query()
            ->where('company_id', $companyId)
            ->with(['supplier', 'specs'])
            ->findOrFail($productId);
    }

    public function createForCompany(
        int $companyId,
        array $data
    ): Product {
        $product = new Product();

        // Never accept company_id from the submitted form.
        $product->company_id = $companyId;
        $product->supplier_id = $data['supplier_id'];
        $product->name = $data['name'];
        $product->model = $data['model'] ?? null;
        $product->type = $data['type'];
        $product->description = $data['description'] ?? null;
        $product->is_active = $data['is_active'] ?? true;
        $product->save();

        return $product;
    }

    public function updateForCompany(
        int $companyId,
        int $productId,
        array $data
    ): Product {
        $product = $this->findForCompany($companyId, $productId);

        $product->supplier_id = $data['supplier_id'];
        $product->name = $data['name'];
        $product->model = $data['model'] ?? null;
        $product->type = $data['type'];
        $product->description = $data['description'] ?? null;
        $product->is_active = $data['is_active'] ?? false;
        $product->save();

        return $product;
    }

    public function deleteForCompany(
        int $companyId,
        int $productId
    ): bool {
        $product = $this->findForCompany($companyId, $productId);

        return (bool) $product->delete();
    }

    //Company profile END


    //Dashboard Start

    public function getDashboardData(): array
    {
        $companyId = $this->authenticatedCompanyId();
        $company = Auth::guard('company')->user();

        // Requests assigned to this company, open to all, or already offered on.
        $visibleQuotes = Quote::query()->visibleToCompany($companyId);

        $pendingRequests = (clone $visibleQuotes)
            ->where('status', 'pending')
            ->count();

        $awaitingOffer = (clone $visibleQuotes)
            ->where('status', 'pending')
            ->whereDoesntHave('companyQuotations', function ($quotationQuery) use ($companyId) {
                $quotationQuery->where('company_id', $companyId);
            })
            ->count();

        $offerCounts = CompanyQuotation::query()
            ->where('company_id', $companyId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $offers = [
            'selected' => (int) ($offerCounts['selected'] ?? 0),
            'submitted' => (int) ($offerCounts['submitted'] ?? 0),
            'not_selected' => (int) ($offerCounts['not_selected'] ?? 0),
            'withdrawn' => (int) ($offerCounts['withdrawn'] ?? 0),
        ];
        $offers['total'] = array_sum($offers);

        // Win rate only counts offers the customer has already decided on.
        $decidedOffers = $offers['selected'] + $offers['not_selected'];
        $offers['win_rate'] = $decidedOffers > 0
            ? (int) round($offers['selected'] / $decidedOffers * 100)
            : null;

        $projectCounts = Project::query()
            ->where('company_id', $companyId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $projects = [
            'accepted' => (int) ($projectCounts['accepted'] ?? 0),
            'in_progress' => (int) ($projectCounts['in_progress'] ?? 0),
            'completed' => (int) ($projectCounts['completed'] ?? 0),
        ];
        $projects['active'] = $projects['accepted'] + $projects['in_progress'];
        $projects['average_progress'] = (int) round((float) Project::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['accepted', 'in_progress'])
            ->avg('progress_percent'));

        $products = [
            'total' => Product::query()->where('company_id', $companyId)->count(),
            'active' => Product::query()->where('company_id', $companyId)->where('is_active', true)->count(),
        ];

        // Daily activity for the last 7 days, including today.
        $start = now()->subDays(6)->startOfDay();

        $requestsPerDay = (clone $visibleQuotes)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $offersPerDay = CompanyQuotation::query()
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $activity = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->toDateString();

            $activity[] = [
                'label' => $date->format('D'),
                'date' => $date->format('d M'),
                'requests' => (int) ($requestsPerDay[$key] ?? 0),
                'offers' => (int) ($offersPerDay[$key] ?? 0),
            ];
        }

        $latestRequests = (clone $visibleQuotes)
            ->with([
                'customer',
                'companyQuotations' => function ($quotationQuery) use ($companyId) {
                    $quotationQuery
                        ->where('company_id', $companyId)
                        ->select(['id', 'quote_id', 'company_id', 'price', 'currency', 'status']);
                },
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return [
            'company' => $company,
            'pendingRequests' => $pendingRequests,
            'awaitingOffer' => $awaitingOffer,
            'offers' => $offers,
            'projects' => $projects,
            'products' => $products,
            'activity' => $activity,
            'latestRequests' => $latestRequests,
        ];
    }

    //Dashboard End


    //Quote Requests Start

    private function authenticatedCompanyId(): int
    {
        $company = Auth::guard('company')->user();

        abort_if(
            !$company || !$company->getAuthIdentifier(),
            403,
            'Company authentication required.'
        );

        return (int) $company->getAuthIdentifier();
    }

    public function getQuoteRequests(array $filters = []): LengthAwarePaginator
    {
        $companyId = $this->authenticatedCompanyId();

        $query = Quote::query()
            ->with([
                'customer',
                'company',
                'companyQuotations' => function ($quotationQuery) use ($companyId) {
                    // Load this company's own offers, including withdrawn offers.
                    $quotationQuery
                        ->where('company_id', $companyId)
                        ->select([
                            'id',
                            'quote_id',
                            'company_id',
                            'price',
                            'currency',
                            'status',
                            'created_at',
                            'updated_at',
                        ]);
                },
            ])
            // Requests assigned to this company, available to all, or already offered on.
            ->visibleToCompany($companyId)
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
                        ->orWhere('daily_load', 'like', "%{$search}%")
                        ->orWhere('peak_load', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return $query->paginate(10)->withQueryString();
    }

    public function getQuoteRequestMetrics(): array
    {
        $companyId = $this->authenticatedCompanyId();

        $query = Quote::query()->visibleToCompany($companyId);

        return [
            'total' => (clone $query)->count(),

            'pending' => (clone $query)
                ->where('status', 'pending')
                ->count(),

            'in_progress' => (clone $query)
                ->where('status', 'accepted')
                ->count(),

            'completed' => (clone $query)
                ->where('status', 'completed')
                ->count(),
        ];
    }

    public function getQuoteRequestById(int $id): ?Quote
    {
        $companyId = $this->authenticatedCompanyId();

        return Quote::query()
            ->with(['customer', 'company'])
            ->where('id', $id)
            ->visibleToCompany($companyId)
            ->first();
    }

    public function getQuoteRequestCount(): int
    {
        $companyId = $this->authenticatedCompanyId();

        return Quote::query()
            ->where('company_id', $companyId)
            ->count();
    }

    public function submitQuoteOffer(int $quoteId, array $data): CompanyQuotation
    {
        $company = Auth::guard('company')->user();

        abort_if(
            !$company || !$company->getAuthIdentifier(),
            403,
            'Company authentication required.'
        );

        $companyId = (int) $company->getAuthIdentifier();

        return DB::transaction(function () use ($quoteId, $companyId, $data) {
            // Lock the request to prevent offer submission while it is being closed.
            $quote = Quote::query()
                ->whereKey($quoteId)
                ->lockForUpdate()
                ->firstOrFail();

            // Offers can only be created or edited while the request is pending.
            if ($quote->status !== 'pending') {
                throw ValidationException::withMessages([
                    'price' => 'This request is no longer accepting or updating offers.',
                ]);
            }

            // Find this company's existing offer for the request.
            $quotation = CompanyQuotation::query()
                ->where('quote_id', $quote->id)
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->first();

            // Allow marketplace requests, requests assigned to this company,
            // or requests this company has already offered on.
            if (
                !$quotation &&
                $quote->company_id !== null &&
                (int) $quote->company_id !== $companyId
            ) {
                abort(403, 'This quote request is not available to your company.');
            }

            if ($quotation) {
                // Once selected by the customer, the offer is permanently locked.
                if ($quotation->status === 'selected') {
                    throw ValidationException::withMessages([
                        'price' => 'The customer has selected your offer. You can no longer edit it.',
                    ]);
                }

                // Only submitted offers may be edited.
                if ($quotation->status !== 'submitted') {
                    throw ValidationException::withMessages([
                        'price' => 'This offer cannot be edited because it is no longer active.',
                    ]);
                }

                // Update the existing row; do not insert another quotation.
                $quotation->customer_id = $quote->customer_id;
                $quotation->price = $data['price'];
                $quotation->currency = $data['currency'];
                $quotation->message = $data['message'] ?? null;
                $quotation->ar_message = $data['ar_message'] ?? null;
                $quotation->save();

                return $quotation->refresh();
            }

            // No existing offer: create the first offer for this company/request.
            $quotation = new CompanyQuotation();
            $quotation->quote_id = $quote->id;
            $quotation->customer_id = $quote->customer_id;
            $quotation->company_id = $companyId;
            $quotation->price = $data['price'];
            $quotation->currency = $data['currency'];
            $quotation->message = $data['message'] ?? null;
            $quotation->ar_message = $data['ar_message'] ?? null;
            $quotation->status = 'submitted';
            $quotation->save();

            return $quotation;
        });
    }

    public function getCompanyQuotationForQuote(int $quoteId): ?CompanyQuotation
    {
        $company = Auth::guard('company')->user();

        abort_if(
            !$company || !$company->getAuthIdentifier(),
            403,
            'Company authentication required.'
        );

        return CompanyQuotation::query()
            ->where('quote_id', $quoteId)
            ->where('company_id', $company->getAuthIdentifier())
            // Include withdrawn offers so the company sees its final offer status.
            ->first();
    }

    public function getCompetitorOfferPrices(int $quoteId)
    {
        $company = Auth::guard('company')->user();

        abort_if(
            !$company || !$company->getAuthIdentifier(),
            403,
            'Company authentication required.'
        );

        return CompanyQuotation::query()
            ->where('quote_id', $quoteId)
            ->where('company_id', '!=', $company->getAuthIdentifier())
            ->where('status', 'submitted')
            ->orderBy('currency')
            ->orderBy('price')
            ->get(['price', 'currency']);
    }

    //Quote Requests End


    //Notifications Start

    private function companyNotificationsQuery(): Builder
    {
        return Notification::query()
            ->where('sender_type', 'company')
            ->where('sender_id', $this->authenticatedCompanyId());
    }

    public function publishNotification(array $data): Notification
    {
        $startedAt = microtime(true);

        // Never accept the sender from the submitted form.
        $data['sender_type'] = 'company';
        $data['sender_id'] = $this->authenticatedCompanyId();

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
        $query = $this->companyNotificationsQuery()
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

        $publishedCount = $this->companyNotificationsQuery()->where('status', 'published')->count();
        $draftCount = $this->companyNotificationsQuery()->where('status', 'draft')->count();

        $recipientCount = CustomerNotification::query()
            ->whereIn('notification_id', $this->companyNotificationsQuery()->select('id'))
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
        // Other senders' notifications are reported as not found.
        $notification = $this->companyNotificationsQuery()
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

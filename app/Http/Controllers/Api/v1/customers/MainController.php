<?php

namespace App\Http\Controllers\Api\v1\customers;

use App\Http\Controllers\Controller;
use App\Services\API\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Throwable;



class MainController extends Controller
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $data = ['username' => trim($validated['username']), 'password' => $validated['password'],];

        $result = $this->customerService->login($data);

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => [
                    'customerDetails' => $result['customerDetails'],
                ],
            ], $result['code']);
        }

        return response()->json([
            'status' => 'error',
            'code' => $result['code'],
            'error' => $result['message'],
            'attempts_remaining' => $result['attempts_remaining'] ?? null,
            'retry_after' => $result['retry_after'] ?? null,
        ], $result['code']);
    }

    public function mainData(): JsonResponse
    {
        try {
            $result = $this->customerService->mainData();

            if (!$result['success']) {
                return response()->json([
                    'status' => 'error',
                    'code' => $result['code'],
                    'error' => $result['message'],
                ], $result['code']);
            }

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Main Data Retrieved Successfully',
                'data' => [
                    'profile' => $result['profile'],
                    'cities' => $result['cities'],
                    'services' => $result['services'],
                    'services_ar' => $result['services_ar'],
                    'carousels' => $result['carousels'],
                    'companies' => $result['companies'],
                    'calculator' => $result['calculator'],
                    'my_quotes' => $result['my_quotes'],
                    'notifications' => $result['notifications'],
                    'app_version' => $result['app_version'],
                    // {"status":"active"|"stopped","message":"...in app language", ...}. Set by admin per customer.
                    'app_status' => $result['app_status'],
                    // e.g. {"max_open_quotes": 2}, editable from admin Settings.
                    'settings' => $result['settings'],
                    // Rating questions and the 1-4 answer labels.
                    'rating_form' => $result['rating_form'],
                ],
                'meta' => $result['meta'],
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'error' => 'Unable to retrieve main data.',
            ], 500);
        }
    }

    public function createAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => [
                'required',
                'string',
                'max:15',
                'min:6',
                'regex:/^[a-zA-Z0-9_]+$/',
            ],
            'first_name' => ['nullable', 'string', 'max:15', 'min:3'],
            'second_name' => ['nullable', 'string', 'max:15', 'min:3'],
            'third_name' => ['nullable', 'string', 'max:15'],
            'phone' => ['required', 'string', 'max:10', 'min:10'],
            'email' => ['nullable', 'email', 'max:50'],
            'gender' => ['nullable', 'in:male,female'],
            'city' => ['nullable', 'string', 'max:15', 'min:3'],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => [
                'required',
                'string',
                'min:5',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
            'fcm_token' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'in:android,ios'],
            'device_name' => ['nullable', 'string', 'max:150'],
            'lang' => ['nullable', 'in:en,ar'],
        ]);

        $isArabic = app()->getLocale() === 'ar';

        if ($validator->fails()) {
            $errors = $validator->errors();

            $message = $isArabic
                ? 'يرجى مراجعة البيانات المدخلة والمحاولة مرة أخرى.'
                : 'Please review the provided information and try again.';

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $errors,
            ], 422);
        }

        // Check duplicates separately to return one generic message.
        // Only active accounts (is_active = 1) reserve a username/phone/email;
        // an inactive account's values are released in the repository on sign-up.
        $duplicateField = null;

        if (\DB::table('tbl_customers')->where('username', $request->username)->where('is_active', 1)->exists()) {
            $duplicateField = 'username';
        } elseif (\DB::table('tbl_customers')->where('phone', $request->phone)->where('is_active', 1)->exists()) {
            $duplicateField = 'phone';
        } elseif (
            $request->filled('email') &&
            \DB::table('tbl_customers')->where('email', $request->email)->where('is_active', 1)->exists()
        ) {
            $duplicateField = 'email';
        }

        if ($duplicateField !== null) {
            $fieldNames = [
                'username' => $isArabic ? 'اسم المستخدم' : 'username',
                'phone' => $isArabic ? 'رقم الهاتف' : 'phone number',
                'email' => $isArabic ? 'البريد الإلكتروني' : 'email address',
            ];

            $message = $isArabic
                ? "تعذّر إكمال العملية. {$fieldNames[$duplicateField]} مستخدم بالفعل. يرجى استخدام إسم أخر."
                : "Unable to complete the operation. The {$fieldNames[$duplicateField]} is already in use. Please use a different one.";

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => [
                    $duplicateField => [$message],
                ],
            ], 422);
        }

        try {
            $result = $this->customerService->createAccount(
                $validator->validated()
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                        ?? 'Unable to create account.',
                ], $result['code'] ?? 500);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message']
                    ?? 'Account created successfully.',
                'data' => [
                    'account' => $result['account'],
                ],
            ], $result['code'] ?? 201);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to create account.',
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function getProfile()
    {
        $result = $this->customerService->getProfile();

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => [
                    'profile' => $result['profile'],
                ],
            ], $result['code']);
        }

        return response()->json([
            'status' => 'error',
            'code' => $result['code'],
            'error' => $result['message'],
        ], $result['code']);
    }

    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'username' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:15', 'min:3'],
            'second_name' => ['required', 'string', 'max:15', 'min:3'],
            'third_name' => ['nullable', 'string', 'max:15', 'min:3'],
            'phone' => ['required', 'string', 'max:10', 'min:10'],
            'email' => ['required', 'email', 'max:50'],
            'gender' => ['nullable', 'in:male,female'],
            'city' => ['nullable', 'string', 'max:15', 'min:3'],
            'address' => ['nullable', 'string', 'max:500'],
            // 'password' => [
            //     'nullable',
            //     'string',
            //     'min:5',
            //     'confirmed',
            //     'regex:/[A-Z]/',
            //     'regex:/[a-z]/',
            //     'regex:/[0-9]/',
            //     'regex:/[^A-Za-z0-9]/',
            // ],
            // 'password_confirmation' => [
            //     'required_with:password',
            //     'string',
            // ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code' => 422,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
                'data' => null,
            ], 422);
        }

        $result = $this->customerService->updateProfile(
            $validator->validated()
        );

        return response()->json($result, $result['code']);
    }

    public function getCities()
    {

        $result = $this->customerService->getCities();

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => [
                    'cities' => $result['cities'],
                ],
            ], $result['code']);
        }

        return response()->json([
            'status' => 'error',
            'code' => $result['code'],
            'error' => $result['message'],
        ], $result['code']);
    }

    public function getCompanies(Request $request)
    {

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:191'],
            'city' => ['nullable', 'string', 'max:100'],
            'service' => ['nullable', 'string', 'max:191'],
            'sort' => ['nullable', 'string', 'in:rating,name'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $result = $this->customerService->getCompanies($validated);

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => [
                    'companies' => $result['companies'],
                ],
                'meta' => $result['meta'],
            ], $result['code']);
        }

        return response()->json([
            'status' => 'error',
            'code' => $result['code'],
            'error' => $result['message'],
        ], $result['code']);
    }

    public function getCompany($id)
    {

        $result = $this->customerService->getCompany((int) $id);

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => [
                    'company' => $result['company'],
                ],
            ], $result['code']);
        }

        return response()->json([
            'status' => 'error',
            'code' => $result['code'],
            'error' => $result['message'],
        ], $result['code']);
    }

    /** Company by slug, for "Open in App" links (sunxfinder://company/{slug}). Same payload as /companies/{id}. */
    public function getCompanyBySlug(string $slug)
    {
        $result = $this->customerService->getCompanyBySlug($slug);

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => [
                    'company' => $result['company'],
                ],
            ], $result['code']);
        }

        return response()->json([
            'status' => 'error',
            'code' => $result['code'],
            'error' => $result['message'],
        ], $result['code']);
    }

    public function getServices()
    {
        $result = $this->customerService->getServices();

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => [
                    'services' => $result['data']['services'] ?? [],
                    'services_ar' => $result['data']['services_ar'] ?? [],
                ],
            ], $result['code']);
        }

        return response()->json([
            'status' => 'error',
            'code' => $result['code'],
            'error' => $result['message'],
        ], $result['code']);
    }

    public function getCarousels()
    {
        $result = $this->customerService->getCarousels();

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => [
                    'carousels' => $result['carousels'],
                ],
            ], $result['code']);
        }

        return response()->json([
            'status' => 'error',
            'code' => $result['code'],
            'error' => $result['message'],
            'data' => [
                'carousels' => [],
            ],
        ], $result['code']);
    }
    public function appliancesCalculator()
    {
        $result = $this->customerService->appliancesCalculator();

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => [
                    'groups' => $result['groups'],
                ],
            ], $result['code']);
        }

        return response()->json([
            'status' => 'error',
            'code' => $result['code'],
            'error' => $result['message'],
            'data' => [
                'groups' => [],
            ],
        ], $result['code']);
    }

    public function storeQuote(Request $request)
    {
        // Older app builds send "service_needed"; newer ones send "quote_name".
        if (!$request->filled('quote_name') && $request->filled('service_needed')) {
            $request->merge(['quote_name' => $request->input('service_needed')]);
        }

        // appliances_ids may arrive as a JSON array; store it as a string.
        if (is_array($request->input('appliances_ids'))) {
            $request->merge(['appliances_ids' => json_encode(array_values($request->input('appliances_ids')))]);
        }

        $validator = Validator::make($request->all(), [
            'company_id' => ['present', 'nullable', 'integer', 'min:1'],
            'quote_name' => ['required', 'string', 'max:100'],
            'estimated_load' => ['present', 'string', 'max:50000'],
            'peak_load' => ['required', 'string', 'max:255'],
            'daily_load' => ['required', 'string', 'max:255'],
            'message' => ['present', 'nullable', 'string', 'max:5000'],
            // Devices from the calculator (tbl_appliances ids), e.g. "1,4,4,9" or "1:2,4:1".
            'appliances_ids' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
                'data' => null,
            ], 422);
        }

        $validated = $validator->validated();

        $result = $this->customerService->storeQuote([
            'company_id' => $validated['company_id'] === null
                ? null
                : (int) $validated['company_id'],
            'quote_name' => $validated['quote_name'],
            'estimated_load' => $validated['estimated_load'],
            'peak_load' => $validated['peak_load'],
            'daily_load' => $validated['daily_load'],
            'appliances_ids' => isset($validated['appliances_ids']) ? trim($validated['appliances_ids']) : null,
            'message' => $validated['message'],
        ]);

        $httpCode = $result['code'] ?? 500;

        return response()->json([
            'status' => ($result['success'] ?? false) ? 'success' : 'error',
            'code' => $httpCode,
            'message' => $result['message'] ?? 'Unable to submit quote.',
            // Limit errors carry {reason: quote_limit_reached} so the app can show its own wording.
            'data' => $result['quote'] ?? ($result['data'] ?? null),
        ], $httpCode);
    }

    public function getMyQuotes(Request $request)
    {

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:191'],
            'low_price' => ['nullable', 'string', 'max:100'],
            // 'Accepted'=> ['nullable', 'string', 'max:100'],
            // 'Awaiting Offers'=> ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'string', 'in:rating,name'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $result = $this->customerService->getMyQuotes($validated);

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => [
                    'quotes' => $result['quotes'],
                ],
                'meta' => $result['meta'],
            ], $result['code']);
        }

        return response()->json([
            'status' => 'error',
            'code' => $result['code'],
            'error' => $result['message'],
            'data' => [
                'quotes' => [],
            ],
        ], $result['code']);
    }

    public function acceptOffer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'offer_id' => ['required', 'integer', 'min:1'],
            'quote_id' => ['required', 'integer', 'min:1'],
            'company_id' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
                'data' => null,
            ], 422);
        }

        $result = $this->customerService->acceptOffer($validator->validated());

        $httpCode = $result['code'] ?? (
            ($result['success'] ?? false) ? 200 : 500
        );

        return response()->json([
            'status' => ($result['success'] ?? false) ? 'success' : 'error',
            'code' => $httpCode,
            'message' => $result['message'] ?? 'Unable to accept offer.',
            'data' => $result['data'] ?? null,
        ], $httpCode);
    }

    public function rejectOffer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'offer_id' => ['required', 'integer', 'min:1'],
            'quote_id' => ['required', 'integer', 'min:1'],
            'company_id' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
                'data' => null,
            ], 422);
        }

        $result = $this->customerService->rejectOffer($validator->validated());

        $httpCode = $result['code'] ?? (
            ($result['success'] ?? false) ? 200 : 500
        );

        return response()->json([
            'status' => ($result['success'] ?? false) ? 'success' : 'error',
            'code' => $httpCode,
            'message' => $result['message'] ?? 'Unable to reject offer.',
            'data' => $result['data'] ?? null,
        ], $httpCode);
    }


    public function submitRating(Request $request)
    {
        // The app omits a blank comment; treat "" the same as missing.
        if (trim((string) $request->input('comment', '')) === '') {
            $request->request->remove('comment');
        }

        $validator = Validator::make($request->all(), [
            'quote_id' => ['required', 'integer', 'min:1'],
            'company_id' => ['required', 'integer', 'min:1'],
            'offer_id' => ['nullable', 'integer', 'min:1'],
            'project_id' => ['nullable', 'integer', 'min:1'],
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
            // Optional per-question answers on the 1-4 scale.
            'answers' => ['nullable', 'array', 'max:20'],
            'answers.*.question_id' => ['required_with:answers', 'integer', 'distinct', 'min:1'],
            'answers.*.value' => ['required_with:answers', 'integer', 'min:1', 'max:4'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
                'data' => null,
            ], 422);
        }

        $result = $this->customerService->submitRating($validator->validated());

        $httpCode = $result['code'] ?? 500;

        return response()->json([
            'status' => ($result['success'] ?? false) ? 'success' : 'error',
            'code' => $httpCode,
            'message' => $result['message'] ?? 'Unable to submit your rating.',
            'data' => $result['data'] ?? null,
        ], $httpCode);
    }

    public function getRatingForm()
    {
        $result = $this->customerService->getRatingForm();

        $httpCode = $result['code'] ?? 500;

        return response()->json([
            'status' => ($result['success'] ?? false) ? 'success' : 'error',
            'code' => $httpCode,
            'message' => $result['message'] ?? 'Unable to load the rating form.',
            'data' => $result['data'] ?? null,
        ], $httpCode);
    }

    public function getProjectProgress(int $projectId)
    {

        $project = $this->customerService->getProjectProgress($projectId);

        if (is_array($project) && isset($project['success']) && !$project['success']) {
            return response()->json($project, $project['code'] ?? 500);
        }

        if (!$project) {
            return response()->json([
                'success' => false,
                'code' => 404,
                'message' => 'Project not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'code' => 200,
            'message' => 'Project progress retrieved successfully.',
            'data' => $project,
        ]);
    }

    public function saveCustomerDeviceToken(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
            'platform' => ['required', 'in:android,ios'],
            'lang' => ['required', 'in:en,ar'],
            'device_name' => ['nullable', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        try {
            // Resolve the authenticated customer using your existing
            // authentication flow in MainRepository.
            $result = $this->customerService->saveCustomerDeviceToken(
                (int) $request->user()->id,
                $validated['token'],
                $validated['platform'],
                $validated['lang'],
                $validated['device_name'] ?? null,
                $request->has('is_active') ? $request->boolean('is_active') : null
            );

            return response()->json([
                'success' => true,
                'message' => 'Device token saved successfully.',
            ], 200);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to save device token.',
            ], 500);
        }
    }


    public function getNotifications(Request $request)
    {
        $result = $this->customerService->getCustomerNotifications(
            $request->query()
        );

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => $result,
        ], 200);
    }

    //Read One
    public function markNotificationAsRead($notificationId)
    {
        $result = $this->customerService->markNotificationAsRead($notificationId);

        if ($result['success']) {

            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => [
                    'notificationDetails' => $result['notificationDetails'],
                    'unread_count' => $result['unread_count'],
                ],
            ], $result['code']);

        }

        return response()->json([
            'status' => 'error',
            'code' => $result['code'],
            'error' => $result['message'],
        ], $result['code']);
    }

    //Read All
    public function markAllNotificationsAsRead()
    {
        $result = $this->customerService->markAllNotificationsAsRead();

        if ($result['success']) {

            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => [
                    'notificationDetails' => $result['notificationDetails'],
                ],
            ], $result['code']);

        }

        return response()->json([
            'status' => 'error',
            'code' => $result['code'],
            'error' => $result['message'],
        ], $result['code']);
    }


    public function logout()
    {

        $result = $this->customerService->logout();

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
            ], $result['code']);
        } else {
            return response()->json([
                'status' => 'error',
                'code' => $result['code'],
                'error' => $result['message']
            ], $result['code']);
        }
    }

    //Password Reset (public) Start

    // Step 1: phone + email -> 6-digit code sent by email (SMS when enabled).
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'min:9', 'max:15'],
            'email' => ['required', 'email', 'max:191'],
            'channel' => ['nullable', 'in:email,sms'],
            'lang' => ['nullable', 'in:en,ar'],
        ]);

        $data['channel'] = $data['channel'] ?? 'email';
        $data['lang'] = $this->passwordResetLang($request);

        return $this->passwordResetResponse($this->customerService->forgotPassword($data));
    }

    // Step 2: phone + email + code -> reset_token (valid 10 minutes).
    public function verifyPasswordResetOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'min:9', 'max:15'],
            'email' => ['required', 'email', 'max:191'],
            'otp' => ['required', 'digits:6'],
            'lang' => ['nullable', 'in:en,ar'],
        ]);

        $data['lang'] = $this->passwordResetLang($request);

        return $this->passwordResetResponse($this->customerService->verifyPasswordResetOtp($data));
    }

    // Step 3: phone + email + reset_token + new password -> done, log in again.
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'min:9', 'max:15'],
            'email' => ['required', 'email', 'max:191'],
            'reset_token' => ['required', 'string', 'size:64'],
            // Same rules as registration.
            'password' => [
                'required',
                'string',
                'min:5',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
            'lang' => ['nullable', 'in:en,ar'],
        ]);

        $data['lang'] = $this->passwordResetLang($request);

        return $this->passwordResetResponse($this->customerService->resetPassword($data));
    }

    private function passwordResetLang(Request $request): string
    {
        $lang = $request->input('lang') ?? substr((string) $request->header('Accept-Language'), 0, 2);

        return $lang === 'ar' ? 'ar' : 'en';
    }

    private function passwordResetResponse(array $result): JsonResponse
    {
        if ($result['success']) {
            return response()->json(array_filter([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
                'data' => $result['data'] ?? null,
            ], fn($value) => $value !== null), $result['code']);
        }

        return response()->json(array_merge([
            'status' => 'error',
            'code' => $result['code'],
            'reason' => $result['reason'],
            'error' => $result['message'],
        ], $result['extra'] ?? []), $result['code']);
    }

    //Password Reset End

    public function deleteAccount()
    {
        $result = $this->customerService->deleteAccount();

        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'code' => $result['code'],
                'message' => $result['message'],
            ], $result['code']);
        } else {
            return response()->json([
                'status' => 'error',
                'code' => $result['code'],
                'error' => $result['message']
            ], $result['code']);
        }
    }
}

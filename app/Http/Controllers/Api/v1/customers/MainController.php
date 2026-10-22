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
        $duplicateField = null;

        if (\DB::table('tbl_customers')->where('username', $request->username)->exists()) {
            $duplicateField = 'username';
        } elseif (\DB::table('tbl_customers')->where('phone', $request->phone)->exists()) {
            $duplicateField = 'phone';
        } elseif (
            $request->filled('email') &&
            \DB::table('tbl_customers')->where('email', $request->email)->exists()
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


    // public function mainData()
    // {

    //     $result = $this->customerService->mainData();

    //     if (!$result['success']) {
    //         return response()->json([
    //             'status' => 'error',
    //             'code' => $result['code'],
    //             'error' => $result['message'],
    //         ], $result['code']);
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'code' => 200,
    //         'message' => 'Main Data Retrieved Successfully',
    //         'data' => [
    //             'studentDetails' => $result['studentDetails'],
    //             'semesterResult' => $result['semesterResult'],
    //             'feeDetails' => $result['feeDetails'],
    //             'timetable' => $result['timetable'],
    //             'appStatus' => $result['appStatus'],
    //             'appVersion' => $result['appVersion'],
    //         ],
    //     ]);
    // }

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
        $validator = Validator::make($request->all(), [
            'company_id' => ['present', 'nullable', 'integer', 'min:1'],
            'service_needed' => ['required', 'string', 'max:100'],
            'estimated_load' => ['present', 'string', 'max:50000'],
            'peak_load' => ['required', 'string', 'max:255'],
            'daily_load' => ['required', 'string', 'max:255'],
            'message' => ['present', 'nullable', 'string', 'max:5000'],
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
            'service_needed' => $validated['service_needed'],
            'estimated_load' => $validated['estimated_load'],
            'peak_load' => $validated['peak_load'],
            'daily_load' => $validated['daily_load'],
            'message' => $validated['message'],
        ]);

        $httpCode = $result['code'] ?? 500;

        return response()->json([
            'status' => ($result['success'] ?? false) ? 'success' : 'error',
            'code' => $httpCode,
            'message' => $result['message'] ?? 'Unable to submit quote.',
            'data' => $result['quote'] ?? null,
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

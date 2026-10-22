<?php

namespace App\Http\Controllers;

use App\Services\MainService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Kreait\Firebase\Contract\Messaging;

class WebsiteController extends Controller
{
    protected $mainService;
    public function __construct(MainService $mainService)
    {
        $this->mainService = $mainService;
    }

    public function index()
    {
        $companies = $this->mainService->getWebsiteCompanyCarousel();

        $verifiedCount = $this->mainService->verifiedCount();
        $pendingCount = $this->mainService->pendingCount();
        $inactiveCount = $this->mainService->inactiveCount();

        $sessionId = session()->getId();
        $this->mainService->recordVisitor($sessionId);
        $visitorCount = $this->mainService->getVisitorCount();
        $appLinks = $this->mainService->getAppDownloadLinks();

        return view('visitors.home', compact(
            'companies',
            'verifiedCount',
            'pendingCount',
            'inactiveCount',
            'visitorCount',
            'appLinks'
        ));
    }

    public function registerCompany()
    {
        return view('visitors.create-company-account');
    }

    public function storeCompany(Request $request)
    {

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'min:10', 'regex:/\A[A-Za-z0-9\s.,&\'()\-]+\z/',],
            'username' => [
                'required',
                'string',
                'max:191',
                'regex:/\A[A-Za-z0-9\s.,&\'()\-]+\z/',
                Rule::unique('tbl_solar_companies', 'username'),
            ],

            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('tbl_solar_companies', 'email'),
            ],

            'phone' => ['required', 'string', 'max:10', 'min:10'],
            'city' => ['required', 'string', 'max:100'],
            'services' => ['required', 'string', 'max:5000'],
            'ar_services' => ['required', 'string', 'max:5000'],
            'description' => ['required', 'string'],
            'ar_description' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[a-z]/',      // At least one lowercase letter
                'regex:/[A-Z]/',      // At least one uppercase letter
                'regex:/[0-9]/',      // At least one number
                'regex:/[^A-Za-z0-9]/', // At least one special character
            ],
            'social_media' => ['nullable', 'string', 'max:5000'],
            'logo_path' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'year_of_exp' => ['required', 'integer', 'min:1'],
            'number_of_projects' => ['required', 'integer', 'min:1'],
        ]);

        $uploadedPath = null;

        try {
            if ($request->hasFile('logo_path')) {
                $companyFolder = Str::slug($validated['name']) ?: 'solar-company';

                $destinationPath = public_path(
                    'main/images/company/' . $companyFolder
                );

                File::ensureDirectoryExists($destinationPath);

                $file = $request->file('logo_path');
                $extension = strtolower($file->extension());

                // Example: princeoo-slogan.png
                $fileName = $companyFolder . '-logo.' . $extension;

                $file->move($destinationPath, $fileName);

                $uploadedPath = $destinationPath . DIRECTORY_SEPARATOR . $fileName;

                // Store the relative path in the database.
                $validated['logo_path'] = asset(
                    'main/images/company/' . $companyFolder . '/' . $fileName
                );
            }

            $result = $this->mainService->registerCompany($validated);

            if (!$result['success']) {
                if ($uploadedPath && File::exists($uploadedPath)) {
                    File::delete($uploadedPath);
                }

                return back()
                    ->withInput($request->except([
                        'password',
                        'password_confirmation',
                    ]))
                    ->with('error', $result['message']);
            }

            return redirect()
                ->route('company.register')
                ->with(
                    'success',
                    trans('messages.Your registration has been submitted for review.')
                );

        } catch (\Throwable $e) {
            if ($uploadedPath && File::exists($uploadedPath)) {
                File::delete($uploadedPath);
            }

            report($e);

            return back()
                ->withInput($request->except([
                    'password',
                    'password_confirmation',
                ]))
                ->with('error', 'Registration could not be completed. Please try again.');
        }
    }
    public function showCompany(string $slug)
    {
        $company = $this->mainService->getCompanyBySlug($slug);

        abort_if(!$company, 404);

        $isArabic = app()->getLocale() === 'ar';

        $companyName = $company->name;
        $companyCity = $company->city ?: trans('messages.Sudan');

        $companyDescription = $isArabic
            ? ($company->ar_description ?: $company->description)
            : ($company->description ?: $company->ar_description);

        // The existing Blade view displays this value as escaped text.
        $companyDescription = strip_tags($companyDescription ?? '');

        $services = $isArabic
            ? ($company->ar_services ?: $company->services)
            : ($company->services ?: $company->ar_services);

        $companyServices = collect(explode(',', $services ?? ''))
            ->map(fn($service) => trim($service))
            ->filter()
            ->values()
            ->all();

        $companyLogo = $company->logo_path
            ? asset('storage/' . $company->logo_path)
            : null;

        $companyCover = null;
        $companyVerified = (bool) $company->is_verified;

        $companyRating = 4.0;
        $companyReviews = 0;

        $companyYears = null;
        $companyProducts = $company->products;
        $companyReviewsList = [];

        $companyEmail = $company->email;
        $companyPhone = $company->phone;
        $companyWebsite = $company->website ?? null;

        return view('visitors.company-profile', compact(
            'company',
            'companyName',
            'companyCity',
            'companyDescription',
            'companyServices',
            'companyLogo',
            'companyCover',
            'companyVerified',
            'companyRating',
            'companyReviews',
            'companyYears',
            'companyProducts',
            'companyReviewsList',
            'companyEmail',
            'companyPhone',
            'companyWebsite'
        ));
    }

    public function registerCustomer()
    {
        // Store links for the "account created" message.
        $appLinks = $this->mainService->getAppDownloadLinks();

        return view('visitors.create-customer-account', compact('appLinks'));
    }

    public function storeCustomer(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'username' => [
                'required',
                'string',
                'max:15',
                'min:6',
                'regex:/^[a-zA-Z0-9_]+$/',
                'unique:tbl_customers,username',
            ],
            'first_name' => ['required', 'string', 'max:15', 'min:3'],
            'second_name' => ['required', 'string', 'max:15', 'min:3'],
            'third_name' => ['required', 'string', 'max:15', 'min:3'],
            'phone' => [
                'required',
                'string',
                'max:10',
                'min:10',
                'unique:tbl_customers,phone',
            ],
            'email' => [
                'required',
                'email',
                'max:100',
                'unique:tbl_customers,email',
            ],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'city' => [
                'required',
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
            'address_details' => ['required', 'string', 'max:1000'],
            'password' => [
                'required',
                'string',
                'min:5',
                'confirmed',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
        ]);

        if ($validator->fails()) {

            $errors = $validator->errors();

            // Find a field that failed its "unique" rule. Checked by rule name,
            // so it works whatever the language of the error messages.
            $failedRules = $validator->failed();
            $duplicateField = collect(['username', 'phone', 'email'])
                ->first(fn($field) => isset($failedRules[$field]['Unique']));

            if ($duplicateField) {
                $isArabic = app()->getLocale() === 'ar';

                $fieldName = match ($duplicateField) {
                    'username' => $isArabic ? 'اسم المستخدم' : 'username',
                    'phone' => $isArabic ? 'رقم الهاتف' : 'phone number',
                    'email' => $isArabic ? 'البريد الإلكتروني' : 'email address',
                    default => $isArabic ? 'البيانات المدخلة' : 'provided details',
                };

                $message = $isArabic
                    ? "تعذّر إكمال التسجيل. {$fieldName} مستخدم بالفعل. يرجى استخدام قيمة أخرى."
                    : "Registration could not be completed. The {$fieldName} is already in use. Please use a different one.";

                return back()
                    ->withInput($request->except([
                        'password',
                        'password_confirmation',
                    ]))
                    ->withErrors([
                        'registration' => $message,
                    ]);
            }

            return back()
                ->withInput($request->except([
                    'password',
                    'password_confirmation',
                ]))
                ->withErrors($errors);
        }

        $validated = $validator->validated();

        $validated['full_name'] = trim(implode(' ', [
            $validated['first_name'],
            $validated['second_name'],
            $validated['third_name'],
        ]));

        $validated['address'] = $validated['city'] . ', ' .
            trim($validated['address_details']);

        unset(
            $validated['first_name'],
            $validated['second_name'],
            $validated['third_name'],
            $validated['address_details']
        );

        $result = $this->mainService->storeCustomer($validated);

        if ($result['success']) {
            return redirect()
                ->route('customer.register')
                ->with('success', $result['message']);
        }

        return back()
            ->withInput($request->except([
                'password',
                'password_confirmation',
            ]))
            ->withErrors(['registration' => $result['message']]);
    }


    //Test Push Notifications
    public function testPush(Messaging $messaging)
    {
        $token = 'fb0CWo-WSYCaZL00Q1Bwp0:APA91bEiHmF6MsgrHQDBqxOz3Qa7Zb4U9FX6wJWVw9_KGRHBB4RuD3MXuAzpdUNcLjYGJmHHpzGuLrzUEE5wEiVH0makP2ubr8jfnNZuUKz7srYq6qxFnTs';

        $message = CloudMessage::new()
            ->toToken($token)
            ->withNotification(
                FcmNotification::create(
                    'SunXFinder Test',
                    'Your push notifications are working!'
                )
            );

        $result = $messaging->send($message);

        return response()->json([
            'success' => true,
            'message_id' => $result,
        ]);
    }
}

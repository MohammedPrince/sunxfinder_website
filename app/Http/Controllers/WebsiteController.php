<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Services\MainService;
use App\Models\Setting;
use App\Support\ApkDownload;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
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

        // Counts are only display numbers: a few minutes old is fine, and it saves
        // a COUNT(*) on the ever-growing visitors table for every home page view.
        $verifiedCount = Cache::remember('home:verified-count', 300, fn() => $this->mainService->verifiedCount());

        $sessionId = session()->getId();
        $this->mainService->recordVisitor($sessionId);
        $visitorCount = Cache::remember('home:visitor-count', 300, fn() => $this->mainService->getVisitorCount());
        $appLinks = $this->mainService->getAppDownloadLinks();

        // Admin → App Versions can hide the Google Play button (both places on the home page).
        if (!Setting::showPlayStoreButton()) {
            unset($appLinks['android']);
        }

        // App section buttons: the uploaded APK (when the admin shows it) and the
        // Android store link only when it really points to Google Play.
        $apkDownloadUrl = ApkDownload::info()['visible'] ? route('app.download') : null;
        $playStoreUrl = str_contains((string) ($appLinks['android'] ?? ''), 'play.google.com')
            ? $appLinks['android']
            : null;

        return view('visitors.home', compact(
            'companies',
            'verifiedCount',
            'visitorCount',
            'appLinks',
            'apkDownloadUrl',
            'playStoreUrl'
        ));
    }

    public function downloadApk()
    {
        abort_unless(ApkDownload::info()['visible'] && is_file(ApkDownload::path()), 404);

        return response()->download(ApkDownload::path(), ApkDownload::downloadName(), [
            'Content-Type' => 'application/vnd.android.package-archive',
            'Cache-Control' => 'no-cache',
        ]);
    }

    /**
     * "Open in App" for a company page. When the app is installed and App/Universal
     * Links are set up, the phone opens the app before this page even loads. Otherwise
     * this page tries the app's link scheme and falls back to the store or the website.
     */
    public function openCompanyInApp(Request $request, string $slug)
    {
        $company = $this->mainService->getCompanyBySlug($slug);

        abort_if(!$company, 404);

        $app = config('services.mobile_app');
        $storeLinks = $this->mainService->getAppDownloadLinks();
        $userAgent = (string) $request->userAgent();

        $platform = match (true) {
            (bool) preg_match('/android/i', $userAgent) => 'android',
            (bool) preg_match('/iphone|ipad|ipod/i', $userAgent) => 'ios',
            default => 'other',
        };

        $profileUrl = route('company-profile.show', ['slug' => $company->slug, 'lang' => app()->getLocale()]);
        $storeUrl = $storeLinks[$platform] ?? null;
        $fallbackUrl = $storeUrl ?: $profileUrl;

        // sunxfinder://company/{slug}
        $schemeUrl = $app['scheme'] . '://company/' . rawurlencode($company->slug);

        // Android Chrome opens the app if installed, otherwise goes to the fallback by itself.
        $intentUrl = 'intent://company/' . rawurlencode($company->slug) . '#Intent;scheme=' . $app['scheme']
            . ($app['android_package'] ? ';package=' . $app['android_package'] : '')
            . ';S.browser_fallback_url=' . rawurlencode($fallbackUrl) . ';end';

        return view('visitors.open-in-app', compact(
            'company',
            'platform',
            'schemeUrl',
            'intentUrl',
            'storeUrl',
            'profileUrl'
        ));
    }

    /** Android App Links verification. Empty until ANDROID_PACKAGE_NAME is set. */
    public function androidAssetLinks()
    {
        $app = config('services.mobile_app');

        $fingerprints = collect(explode(',', (string) $app['android_sha256']))
            ->map(fn($fingerprint) => strtoupper(trim($fingerprint)))
            ->filter()
            ->values()
            ->all();

        $statements = $app['android_package'] && $fingerprints
            ? [[
                'relation' => ['delegate_permission/common.handle_all_urls'],
                'target' => [
                    'namespace' => 'android_app',
                    'package_name' => $app['android_package'],
                    'sha256_cert_fingerprints' => $fingerprints,
                ],
            ]]
            : [];

        return response()->json($statements, 200, [], JSON_UNESCAPED_SLASHES);
    }

    /** iOS Universal Links verification. Lists no apps until IOS_APP_ID is set. */
    public function appleAppSiteAssociation()
    {
        $appId = config('services.mobile_app.ios_app_id');

        return response()->json([
            'applinks' => [
                'details' => $appId ? [[
                    'appIDs' => [$appId],
                    'components' => [['/' => '/open/*', 'comment' => 'Open in App links']],
                ]] : [],
            ],
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /** Home page "Contact us" form: emails the message to info@sunxfinder.com. */
    public function sendContactMessage(Request $request)
    {
        // Back to the form on the home page, in the visitor's language.
        $backToForm = route('home', ['lang' => app()->getLocale()]) . '#contact';

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:10','min:9', 'regex:/^[0-9+\s()-]+$/'],
            'sender_type' => ['required', Rule::in(['customer', 'company', 'other'])],
            'subject' => ['required', 'string', 'min:3', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            // Honeypot: hidden from people, filled in by spam bots.
            'website' => ['nullable', 'max:0'],
        ], [], [
            'name' => trans('messages.Full name'),
            'email' => trans('messages.Email address'),
            'phone' => trans('messages.Phone number'),
            'sender_type' => trans('messages.I am a'),
            'subject' => trans('messages.Subject'),
            'message' => trans('messages.Message'),
        ]);

        if ($validator->fails()) {
            // A filled honeypot is a bot: pretend it worked.
            if ($validator->errors()->has('website')) {
                return redirect()->to($backToForm)->with('contact_success', true);
            }

            return redirect()->to($backToForm)->withErrors($validator, 'contact')->withInput();
        }

        $result = $this->mainService->sendContactMessage($validator->validated());

        return $result['success']
            ? redirect()->to($backToForm)->with('contact_success', true)
            : redirect()->to($backToForm)->withInput()->with('contact_error', true);
    }

    public function registerCompany()
    {
        return view('visitors.create-company-account');
    }

    public function storeCompany(Request $request)
    {

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200', 'min:10', 'regex:/\A[A-Za-z0-9\s.,&\'()\-\p{Arabic}]+\z/u',],
            'username' => [
                'required',
                'string',
                'max:191',
                // English letters, numbers and _ (e.g. tesla_solar), plus the separators allowed before.
                'regex:/\A[A-Za-z0-9_\s.,&\'()\-]+\z/',
                Rule::unique('tbl_solar_companies', 'username'),
            ],

            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('tbl_solar_companies', 'email'),
            ],

            'phone' => ['required', 'string', 'max:10', 'min:9'],
            'city' => ['required', 'string', 'max:100'],
            // Comma-separated list. English: Latin letters only. Arabic: Arabic letters only.
            // Digits, spaces and . , & ' ( ) - / + are allowed in both (same as the form script).
            'services' => ['required', 'string', 'max:5000', 'regex:/\A[A-Za-z0-9\s.,&\'()\-\/+]+\z/'],
            'ar_services' => ['required', 'string', 'max:5000', 'regex:/\A[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}0-9\s.,&\'()\-\/+]+\z/u'],
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
        ], [
            'name.regex' => trans("messages.The company name may only contain English or Arabic letters, numbers, spaces and . , & ' ( ) -"),
            'services.regex' => trans('messages.Services in English must not contain Arabic letters.'),
            'ar_services.regex' => trans('messages.Services in Arabic must not contain English letters.'),
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
                Helper::shrinkImage($uploadedPath);

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

        // Plain text for the view. Text pasted into the editor often joins words with
        // &nbsp; (non-breaking spaces), which stops the browser wrapping the line.
        $companyDescription = html_entity_decode(strip_tags($companyDescription ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $companyDescription = trim(preg_replace('/[\x{00A0}\x{202F}\s]+/u', ' ', $companyDescription));

        $services = $isArabic
            ? ($company->ar_services ?: $company->services)
            : ($company->services ?: $company->ar_services);

        // One card per service: split on English or Arabic commas (، ) like the app does.
        $companyServices = app(Helper::class)->splitList(strip_tags($services ?? ''));

        // Company profile uploads are stored under public/main/images/company
        // and logo_path may contain a full URL from the host used at upload time.
        $companyLogo = app(Helper::class)->publicUrl($company->logo_path);

        $companyCover = null;
        $companyVerified = (bool) $company->is_verified;

        // Visible ratings only; rating is null (shows "Not rated yet") until the first one.
        $ratingSummary = $this->mainService->getCompanyRatingSummary($company);
        $companyRating = $ratingSummary['rating'];
        $companyReviews = $ratingSummary['reviews'];

        $companyYears = $company->year_of_exp;
        $companyProducts = $company->products;
        $companyReviewsList = $this->mainService->getCompanyReviews($company->id);

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
                'min:9',
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

        $message = Helper::withNotificationSound(CloudMessage::new())
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

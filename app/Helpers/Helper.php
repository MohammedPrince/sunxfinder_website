<?php

namespace App\Helpers;

use App\Models\SolarCompany;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\ProductSpec;
use App\Models\CompanyQuotation;
use App\Models\CustomerDeviceToken;
use App\Models\CustomerNotification;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectUpdate;
use App\Models\Quote;
use App\Models\Rating;
use Illuminate\Support\Facades\DB;
use App\Repositories\Admin\AdminRepository;
use App\Repositories\Company\CompanyRepository;
use Kreait\Firebase\Messaging\CloudMessage;
use Throwable;


class Helper
{

    public function isCustomerAuthenticated(): ?\App\Models\Customer
    {
        $customer = auth('sanctum')->user();

        return $customer instanceof \App\Models\Customer
            ? $customer
            : null;
    }

    public function transformCompany(
        SolarCompany $company,
        bool $detailed = false
    ): array {
        // Lists preload these with withAvg/withCount; otherwise query them here.
        $attributes = $company->getAttributes();

        $ratingSummary = array_key_exists('visible_rating', $attributes)
            ? [
                'rating' => $company->visible_rating !== null ? round((float) $company->visible_rating, 1) : null,
                'reviews' => (int) ($company->visible_reviews ?? 0),
            ]
            : $company->ratingSummary();

        $data = [
            'id' => (int) $company->id,
            'name' => $company->name,
            'slug' => $company->slug,
            'city' => $company->city,
            'logo_url' => $this->publicUrl($company->logo_path),
            'cover_image_url' => null,
            // Null until the company has at least one visible rating.
            'rating' => $ratingSummary['rating'],
            'reviews_count' => $ratingSummary['reviews'],
            'services' => $this->splitList($company->services),
            'ar_services' => $this->splitList($company->ar_services),
            'is_verified' => (bool) $company->is_verified,
            'year_of_exp' => $company->year_of_exp,
            'number_of_projects' => $company->number_of_projects,
            'status' => $company->status,

        ];

        if (!$detailed) {
            return $data;
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, Product> $productRows */
        $productRows = Product::query()
            ->where('company_id', $company->id)
            ->with(['supplier', 'specs'])
            ->get();

        $products = $productRows
            ->map(function (Product $product): array {
                return [
                    'id' => (int) $product->id,
                    'name' => $product->name,
                    'model' => $product->model,
                    'type' => $product->type,
                    'description' => $this->plainText(
                        $product->description
                    ),
                    'supplier' => $product->supplier ? [
                        'id' => (int) $product->supplier->id,
                        'name' => $product->supplier->name,
                    ] : null,
                    'specifications' => $product->specs
                        ->map(fn(ProductSpec $spec): array => [
                            'name' => $spec->spec_name,
                            'value' => $spec->spec_value,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        return $data + [
            'description' => $this->plainText($company->description),
            'ar_description' => $this->plainText($company->ar_description),
            'phone' => $company->phone,
            'email' => $company->email,
            'license_number' => $company->license_number,
            'social_links' => $this->parseSocialLinks(
                $company->social_media
            ),
            'years_experience' => null,
            'projects_completed' => null,
            'support_hours' => null,
            'products' => $products,
            'reviews' => $this->companyReviews($company->id),
        ];
    }

    /**
     * Latest visible ratings of a company, for its public page and the app.
     * Customer names are shortened ("Mohammed A.") for privacy.
     */
    public function companyReviews(int $companyId, int $limit = 10): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Rating> $ratings */
        $ratings = Rating::query()
            ->visible()
            ->where('company_id', $companyId)
            ->with('customer:id,full_name,username')
            ->latest()
            ->limit($limit)
            ->get();

        return $ratings
            ->map(fn(Rating $rating): array => [
                'id' => (int) $rating->id,
                // Full name as-is (falls back to username); the app shows the first word only.
                'full_name' => trim((string) ($rating->customer?->full_name ?: $rating->customer?->username)) ?: null,
                'username' => $rating->customer?->username,
                // Shortened name ("Mohammed A.") for the public website.
                'customer' => $this->shortCustomerName($rating->customer),
                'rating' => (int) $rating->stars,
                'comment' => $rating->comment,
                'date' => $rating->created_at?->toDateString(),
            ])
            ->values()
            ->all();
    }

    public function shortCustomerName(?\App\Models\Customer $customer): string
    {
        $name = trim((string) ($customer?->full_name ?: $customer?->username));

        if ($name === '') {
            return 'Customer';
        }

        $parts = preg_split('/\s+/u', $name);

        return count($parts) > 1
            ? $parts[0] . ' ' . mb_strtoupper(mb_substr(end($parts), 0, 1)) . '.'
            : $parts[0];
    }

    public function plainText(?string $html): string
    {
        if (!$html) {
            return '';
        }

        // Keep paragraph/line breaks as spaces so words don't run together.
        $text = preg_replace('/<(br|\/p|\/div|\/li|\/h[1-6])[^>]*>/i', ' ', $html);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    public function splitList(?string $value): array
    {
        if (!$value) {
            return [];
        }

        return collect(preg_split('/[,\r\n]+/', $value))
            ->map(fn($item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    public function publicUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }

    /**
     * Shrinks an uploaded image in place so its longest side is at most $maxSide px,
     * keeping the same file name and format (stored URLs stay valid).
     * Uploads can be 2 MB; a 512px logo is a few dozen KB. Never fails the upload.
     */
    public static function shrinkImage(string $path, int $maxSide = 512): void
    {
        try {
            if (!function_exists('imagecreatefromstring') || !is_file($path)) {
                return;
            }

            [$width, $height, $type] = getimagesize($path) ?: [0, 0, 0];

            if (!$width || !$height) {
                return;
            }

            $scale = min(1, $maxSide / max($width, $height));
            $newWidth = max(1, (int) round($width * $scale));
            $newHeight = max(1, (int) round($height * $scale));

            $source = imagecreatefromstring(file_get_contents($path));

            if (!$source) {
                return;
            }

            $target = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($target, false);
            imagesavealpha($target, true);
            imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            $tmp = $path . '.tmp';

            $saved = match ($type) {
                IMAGETYPE_JPEG => imagejpeg($target, $tmp, 82),
                IMAGETYPE_PNG => imagepng($target, $tmp, 9),
                IMAGETYPE_WEBP => imagewebp($target, $tmp, 82),
                default => false,
            };

            imagedestroy($source);
            imagedestroy($target);

            // Keep the original if re-encoding didn't make it smaller.
            if ($saved && filesize($tmp) < filesize($path)) {
                rename($tmp, $path);
            } elseif (is_file($tmp)) {
                unlink($tmp);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function parseSocialLinks(?string $value): array
    {
        $links = [
            'website' => null,
            'facebook' => null,
            'instagram' => null,
            'whatsapp' => null,
        ];

        if (!$value) {
            return $links;
        }

        $decoded = json_decode($value, true);

        if (is_array($decoded)) {
            foreach ($links as $key => $unused) {
                if (!empty($decoded[$key]) && is_string($decoded[$key])) {
                    $links[$key] = trim($decoded[$key]);
                }
            }

            return $links;
        }

        foreach ($this->splitList($value) as $item) {
            $url = $item;

            if (!Str::startsWith($url, ['http://', 'https://'])) {
                $url = 'https://' . ltrim($url, '/');
            }

            $host = strtolower((string) parse_url($url, PHP_URL_HOST));

            if (Str::contains($host, 'facebook')) {
                $links['facebook'] ??= $url;
            } elseif (Str::contains($host, 'instagram')) {
                $links['instagram'] ??= $url;
            } elseif (Str::contains($host, ['wa.me', 'whatsapp'])) {
                $links['whatsapp'] ??= $url;
            } else {
                $links['website'] ??= $url;
            }
        }

        return $links;
    }

    public function visibleCompanies()
    {
        return SolarCompany::query()
            ->whereIn('status', ['approved', 'pending'])
            ->where('is_active', true)
            ->where('is_verified', true);
    }

    /**
     * Builds the push title/body for a customer in the app language.
     * Supported types: offer_new, offer_updated, start_installation, project_completed, account_approved,
     * request_cancelled ($price carries the request reference). $price is shown as given
     * (e.g. "1,500 SDG").
     */
    public function notificationsTemplate($customer_id, $lang, $type, $price = null): array
    {
        
        $lang = strtolower((string) $lang) === 'ar' ? 'ar' : 'en';

        $name = trim((string) \App\Models\Customer::query()->whereKey($customer_id)->value('full_name'));

        $templates = [
            'offer_new' => [
                'en' => [
                    'title' => 'New offer received',
                    'body' => $price !== null
                        ? 'You received a new offer of :price on your quote request.'
                        : 'You received a new offer on your quote request.',
                ],
                'ar' => [
                    'title' => 'عرض سعر جديد',
                    'body' => $price !== null
                        ? 'وصلك عرض سعر جديد بقيمة :price على طلبك.'
                        : 'وصلك عرض سعر جديد على طلبك.',
                ],
            ],
            'offer_updated' => [
                'en' => [
                    'title' => 'Offer updated',
                    'body' => $price !== null
                        ? 'An offer on your quote request was updated to :price.'
                        : 'An offer on your quote request was updated.',
                ],
                'ar' => [
                    'title' => 'تم تحديث عرض السعر',
                    'body' => $price !== null
                        ? 'تم تحديث عرض السعر على طلبك إلى :price.'
                        : 'تم تحديث عرض السعر على طلبك.',
                ],
            ],
            'start_installation' => [
                'en' => [
                    'title' => 'Installation started',
                    'body' => 'The installation of your solar system has started. You can follow its progress in the app.',
                ],
                'ar' => [
                    'title' => 'بدأ التركيب',
                    'body' => 'بدأ تركيب نظامك الشمسي. يمكنك متابعة مراحل التقدم من التطبيق.',
                ],
            ],
            'account_approved' => [
                'en' => [
                    'title' => 'Your account is approved',
                    'body' => 'Your SunXFinder account has been approved. You can now log in and request offers from solar companies.',
                ],
                'ar' => [
                    'title' => 'تم اعتماد حسابك',
                    'body' => 'تم اعتماد حسابك في SunXFinder. يمكنك الآن تسجيل الدخول وطلب عروض من شركات الطاقة الشمسية.',
                ],
            ],
            // :price carries the request reference here, e.g. "#Q-00031".
            'request_cancelled' => [
                'en' => [
                    'title' => 'Quote request cancelled',
                    'body' => 'Your quote request :price has been cancelled by the SunXFinder team, and any waiting offers were withdrawn. You can send a new request from the app at any time, or contact us if you have any questions.',
                ],
                'ar' => [
                    'title' => 'تم إلغاء طلب عرض السعر',
                    'body' => 'تم إلغاء طلب عرض السعر :price من قبل فريق SunXFinder، وتم سحب العروض المنتظرة. يمكنك إرسال طلب جديد من التطبيق في أي وقت، أو التواصل معنا إذا كان لديك أي استفسار.',
                ],
            ],
            'project_completed' => [
                'en' => [
                    'title' => 'Your solar system is ready',
                    'body' => 'Your project is complete and has been handed over to you. Please rate the company in the app.',
                ],
                'ar' => [
                    'title' => 'نظامك الشمسي جاهز',
                    'body' => 'اكتمل مشروعك وتم تسليمه لك. نرجو تقييم الشركة من التطبيق.',
                ],
            ],
        ];

        $template = $templates[$type][$lang] ?? [
            'title' => $lang === 'ar' ? 'إشعار جديد' : 'New notification',
            'body' => $lang === 'ar' ? 'لديك إشعار جديد.' : 'You have a new notification.',
        ];

        $body = str_replace(':price', (string) $price, $template['body']);

        if ($name !== '') {
            $body = $lang === 'ar'
                ? "مرحباً {$name}، {$body}"
                : "Hi {$name}, " . lcfirst($body);
        }

        return [
            'title' => $template['title'],
            'body' => $body,
        ];
    }

    /**
     * Push notification to the customer about a new/updated offer.
     * A failed push must not fail the offer itself.
     */
    public function notifyCustomerAboutOffer(CompanyQuotation $quotation, string $type): void
    {
        if (!$quotation->customer_id) {
            return;
        }

        $price = number_format((float) $quotation->price) . ' ' . $quotation->currency;

        $this->notifyCustomer((int) $quotation->customer_id, $type, $price);
    }

    /**
     * Push notification to the customer about their project:
     * start_installation (installation started) or project_completed (handed over).
     * Skipped when the customer has no device token registered.
     */
    public function notifyCustomerAboutProject(Project $project, string $type): void
    {
        if (!$project->customer_id) {
            return;
        }

        $hasToken = CustomerDeviceToken::query()
            ->where('customer_id', $project->customer_id)
            ->exists();

        if (!$hasToken) {
            return;
        }

        $this->notifyCustomer((int) $project->customer_id, $type);
    }

    /**
     * Adds the SunXFinder notification sound to a push message.
     * Android plays it through the channel; iOS needs the file extension.
     * FCM_SOUND=default uses the phone's normal sound (no app file needed).
     */
    public static function withNotificationSound(CloudMessage $message): CloudMessage
    {
        $sound = trim((string) config('services.fcm.sound')) ?: 'default';
        $channelId = trim((string) config('services.fcm.android_channel_id'));

        $androidNotification = ['sound' => $sound];

        // Without a channel, Android uses the app's default FCM channel.
        if ($channelId !== '') {
            $androidNotification['channel_id'] = $channelId;
        }

        return $message
            ->withAndroidConfig([
                'priority' => 'high',
                'notification' => $androidNotification,
            ])
            ->withApnsConfig([
                'payload' => [
                    'aps' => [
                        'sound' => $sound === 'default' ? 'default' : $sound . '.wav',
                    ],
                ],
            ]);
    }

    /**
     * Soft deletes a customer and everything linked to them, in one transaction:
     * quote requests, company offers on them, projects (with milestones and updates)
     * and in-app notifications get deleted_at; ratings (no soft delete) are hidden.
     * Then signs the customer out of every device. Used by admin delete and app "delete account".
     *
     * @return array{quotes: int, offers: int, projects: int}
     */
    public function softDeleteCustomerData(\App\Models\Customer $customer): array
    {
        $counts = DB::transaction(function () use ($customer) {
            $customer->status = 'deleted';
            $customer->is_active = false;
            $customer->save();

            $quoteIds = Quote::query()->where('customer_id', $customer->id)->pluck('id');

            $projectIds = Project::query()
                ->where('customer_id', $customer->id)
                ->orWhereIn('quote_id', $quoteIds)
                ->pluck('id');

            // Builder delete() on SoftDeletes models only sets deleted_at.
            ProjectMilestone::query()->whereIn('project_id', $projectIds)->delete();
            ProjectUpdate::query()->whereIn('project_id', $projectIds)->delete();
            $projects = Project::query()->whereIn('id', $projectIds)->delete();

            // Company offers on this customer's requests.
            $offers = CompanyQuotation::query()->whereIn('quote_id', $quoteIds)->delete();
            $quotes = Quote::query()->whereIn('id', $quoteIds)->delete();

            CustomerNotification::query()->where('customer_id', $customer->id)->delete();

            // Ratings have no soft delete: hide them so they leave company averages.
            Rating::query()->where('customer_id', $customer->id)->update(['is_visible' => false]);

            $customer->delete();

            return compact('quotes', 'offers', 'projects');
        });

        // A deleted account must not stay signed in or keep receiving pushes.
        $customer->tokens()->delete();
        CustomerDeviceToken::query()->where('customer_id', $customer->id)->update(['is_active' => false]);

        return $counts;
    }

    /**
     * Push notification to the customer that admin approved their account.
     * Always saved in-app: a pending customer could not log in yet, so they
     * may have no device token and will see it after their first login.
     */
    public function notifyCustomerAccountApproved(int $customerId): void
    {
        $this->notifyCustomer($customerId, 'account_approved', null, 'admin');
    }

    /**
     * Saves the notification for the customer and pushes it to their devices,
     * each in its own app language. A failed push never throws.
     * $sender: "company" (logged-in company) or "admin" (logged-in admin).
     */
    public function notifyCustomer(int $customerId, string $type, $price = null, string $sender = 'company'): void
    {
        $en = $this->notificationsTemplate($customerId, 'en', $type, $price);
        $ar = $this->notificationsTemplate($customerId, 'ar', $type, $price);

        $data = [
            'type' => $type,
            'audience_type' => 'selected',
            'customer_ids' => [$customerId],
            'title_en' => $en['title'],
            'message_en' => $en['body'],
            'title_ar' => $ar['title'],
            'message_ar' => $ar['body'],
        ];

        try {
            // Resolved here, not injected: both repositories already depend on Helper.
            if ($sender === 'admin') {
                app(AdminRepository::class)->publishNotification($data + [
                    'sender_type' => 'admin',
                    'sender_id' => auth('admin')->id(),
                ]);
            } else {
                app(CompanyRepository::class)->publishNotification($data);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
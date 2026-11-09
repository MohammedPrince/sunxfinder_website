# SunXFinder: Sending WhatsApp Messages to Customers

## The short answer

Use the **WhatsApp Cloud API** from Meta. It is the official API, Meta hosts it, and Laravel calls it with a plain HTTP request. No extra package is needed.

- **WhatsApp does not replace app pushes.** FCM pushes are free; every WhatsApp message to a customer is charged. Keep FCM as the main channel and add WhatsApp next to it.
- **Customers must opt in** before you message them. Marketing messages need a separate opt-in that customers can turn off.
- **You can only start a conversation with an approved template.** You write the template in Meta's dashboard, Meta approves it, and the backend fills in the variables.
- **Check two things before building:** whether Meta accepts your business for WhatsApp in Sudan, and how you will pay Meta. See [Risks to check first](#risks-to-check-first).

---

## 1. How it works

```
Admin / company panel                Laravel                         Meta
─────────────────────               ───────                         ────
Publish notification ──► publishNotification()
                          ├─ FCM push (as today)
                          └─ dispatch SendWhatsAppTemplate jobs
                                       │ queue worker
                                       ▼
                              WhatsAppClient::sendTemplate() ──► POST /{phone_number_id}/messages
                                                                      │
Customer's WhatsApp ◄──────────────────────────────────────────────── delivered
                                                                      │
                              WhatsAppWebhookController ◄──── status: sent, delivered, read, failed
                              (updates tbl_whatsapp_messages,    incoming: "STOP", button replies
                               handles opt-out)
```

### What you can send

| Type | When | Example in SunXFinder | Cost |
| --- | --- | --- | --- |
| **Utility template** | About a request the customer made | "You received a new offer", "Installation started" | Charged per message (free inside an open 24-hour window) |
| **Marketing template** | Promotions | Campaign push, package offer, seasonal offer | Charged per message, the highest price |
| **Authentication template** | One-time codes | Password reset OTP, instead of SMS | Charged per message, usually cheap |
| **Free-form message** | Only within 24 hours after the customer last wrote to you | Replying to a customer's question | Free |

Meta charges per delivered template message. The price depends on the customer's country and the template category. Check Meta's current rate card for Sudan before you plan the budget.

---

## 2. Setup in Meta (one time)

| Step | Where | Notes |
| --- | --- | --- |
| 1. Business portfolio | business.facebook.com | Use the same business as the Facebook page. |
| 2. Business verification | Business settings → Security centre | Needs company documents. Messaging limits stay low until it is verified. |
| 3. Create an app | developers.facebook.com → My apps → Create → type **Business** | |
| 4. Add the **WhatsApp** product | App dashboard | Meta gives you a test number. You can message up to 5 numbers for free while testing. |
| 5. Add your real phone number | WhatsApp → API setup → Add phone number | The number **must not** be in use on the normal WhatsApp or WhatsApp Business app. Use a new SIM, or delete the WhatsApp account on it first. |
| 6. Display name | Same page | "SunXFinder". Meta reviews it. |
| 7. Payment method | WhatsApp Manager → Payment settings | Needed before you can send real messages. |
| 8. Permanent token | Business settings → System users → Add (admin) → Generate token | Permissions: `whatsapp_business_messaging`, `whatsapp_business_management`. The token from the API setup page expires in 24 hours; do not use it in production. |
| 9. Webhook | App → WhatsApp → Configuration | URL `https://sunxfinder.com/api/webhooks/whatsapp`, your verify token, and subscribe to the **messages** field. |
| 10. Templates | WhatsApp Manager → Message templates | Create them in Arabic **and** English, using the same name. See [section 3](#3-templates-to-create). |

Write down these values for `.env`:

- Phone number ID (not the phone number itself)
- WhatsApp Business Account ID
- System user token
- App secret (App settings → Basic)

### Messaging limits

New numbers can message about 250 different customers per day. The limit rises step by step (1K, 10K, 100K, then unlimited) as long as message quality stays good. If customers block or report you, quality drops and the limit goes down. That is another reason to respect opt-in and keep marketing messages rare.

---

## 3. Templates to create

Template variables are `{{1}}`, `{{2}}` and so on. The backend fills them in order. Create every template in both `ar` and `en`, under the same name.

| Name | Category | Header | Body (Arabic) | Button |
| --- | --- | --- | --- | --- |
| `offer_received` | Utility | — | مرحباً {{1}}، وصلك عرض سعر جديد بقيمة {{2}} على طلبك في SunXFinder. افتح التطبيق للمقارنة والقبول. | URL: `https://sunxfinder.com/app/quotes/{{1}}` |
| `project_update` | Utility | — | مرحباً {{1}}، {{2}}. يمكنك متابعة التفاصيل من تطبيق SunXFinder. | URL to the app |
| `campaign_offer` | Marketing | Image | {{1}} — {{2}} | URL: `https://sunxfinder.com/{{1}}` |
| `package_offer` | Marketing | Image | مرحباً {{1}}، أرسلت لك {{2}} باقة طاقة شمسية جاهزة: {{3}}. | URL to the package |

Template rules:

- Meta rejects templates that start or end with a variable, or that are mostly variables. Keep real text around them.
- Marketing templates automatically get a **"Stop promotions"** button. Handle it in the webhook (see [section 6](#6-webhook)).
- Name the company in package offers, and say that the message comes through SunXFinder.
- Price text is a plain variable, for example `1,500,000 SDG`. You can reuse the formatting in `Helper::notifyCustomerAboutOffer`.

---

## 4. Database

> Dev runs MySQL with MyISAM, and production may not. Use plain indexed columns, not foreign keys, so the migration runs the same way on both.

```php
// database/migrations/2026_11_10_100000_add_whatsapp_to_tbl_customers.php
Schema::table('tbl_customers', function (Blueprint $table) {
    // Service messages: offers, project updates.
    $table->boolean('whatsapp_opt_in')->default(false)->after('sponsored_push_enabled');
    // Promotions: separate consent, can be switched off on its own.
    $table->boolean('whatsapp_marketing_opt_in')->default(false)->after('whatsapp_opt_in');
    $table->timestamp('whatsapp_opt_in_at')->nullable()->after('whatsapp_marketing_opt_in');
});
```

```php
// database/migrations/2026_11_10_110000_create_tbl_whatsapp_messages_table.php
Schema::create('tbl_whatsapp_messages', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('customer_id')->index();
    $table->unsignedBigInteger('notification_id')->nullable()->index();
    $table->string('template', 100);
    $table->string('category', 20);              // utility | marketing | authentication
    $table->string('lang', 5);
    $table->string('wa_message_id')->nullable()->unique();
    $table->string('status', 20)->default('queued'); // queued, sent, delivered, read, failed
    $table->string('error_code', 20)->nullable();
    $table->string('error_message')->nullable();
    $table->timestamp('sent_at')->nullable();
    $table->timestamp('delivered_at')->nullable();
    $table->timestamp('read_at')->nullable();
    $table->timestamps();
});
```

Do **not** store the phone number in this table. It is already on the customer, and the project rule is that phone numbers are never logged.

---

## 5. Backend code

### 5.1 Config

`.env`:

```dotenv
WHATSAPP_ENABLED=false
WHATSAPP_API_VERSION=v23.0
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_BUSINESS_ACCOUNT_ID=
WHATSAPP_ACCESS_TOKEN=
WHATSAPP_APP_SECRET=
WHATSAPP_WEBHOOK_VERIFY_TOKEN=pick-a-long-random-string
WHATSAPP_DEFAULT_COUNTRY_CODE=249
WHATSAPP_MARKETING_WEEKLY_LIMIT=2
```

Set `WHATSAPP_API_VERSION` to the newest Graph API version shown in your app dashboard.

`config/services.php`:

```php
'whatsapp' => [
    'enabled' => (bool) env('WHATSAPP_ENABLED', false),
    'api_version' => env('WHATSAPP_API_VERSION', 'v23.0'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    'app_secret' => env('WHATSAPP_APP_SECRET'),
    'verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
    'country_code' => env('WHATSAPP_DEFAULT_COUNTRY_CODE', '249'),
    'marketing_weekly_limit' => (int) env('WHATSAPP_MARKETING_WEEKLY_LIMIT', 2),
],
```

### 5.2 Phone numbers

The app saves local numbers, 9 or 10 digits (`0912345678` or `912345678`). WhatsApp needs the full international number without `+`: `249912345678`.

```php
// app/Services/WhatsApp/WhatsAppPhone.php
namespace App\Services\WhatsApp;

final class WhatsAppPhone
{
    /** Returns the number in WhatsApp format (e.g. 249912345678), or null if it is not valid. */
    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        $cc = (string) config('services.whatsapp.country_code');

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        // Already international: 249 + 9 digits.
        if (str_starts_with($digits, $cc) && strlen($digits) === strlen($cc) + 9) {
            return $digits;
        }

        $local = ltrim($digits, '0');

        return strlen($local) === 9 ? $cc . $local : null;
    }
}
```

### 5.3 Client

```php
// app/Services/WhatsApp/WhatsAppException.php
namespace App\Services\WhatsApp;

class WhatsAppException extends \RuntimeException
{
    /** Errors where retrying will not help. */
    private const PERMANENT = [
        131026, // Recipient is not on WhatsApp / cannot receive
        131050, // User stopped marketing messages
        131051, // Unsupported message type
        132000, 132001, 132005, 132007, 132012, // Template missing, wrong params, paused, etc.
        100,    // Invalid parameter
    ];

    public function isPermanent(): bool
    {
        return in_array($this->getCode(), self::PERMANENT, true);
    }
}
```

```php
// app/Services/WhatsApp/WhatsAppClient.php
namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;

class WhatsAppClient
{
    /**
     * Sends an approved template. Returns Meta's message id (wamid...).
     *
     * @param array $bodyParams   Values for {{1}}, {{2}}, ... in the body, in order.
     * @param string|null $imageUrl  Public HTTPS image for templates with an image header.
     * @param string|null $buttonParam Value for {{1}} in a dynamic URL button.
     *
     * @throws WhatsAppException
     */
    public function sendTemplate(
        string $to,
        string $template,
        string $lang,
        array $bodyParams = [],
        ?string $imageUrl = null,
        ?string $buttonParam = null,
    ): string {
        $components = [];

        if ($imageUrl) {
            $components[] = [
                'type' => 'header',
                'parameters' => [['type' => 'image', 'image' => ['link' => $imageUrl]]],
            ];
        }

        if ($bodyParams) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(
                    fn ($value) => ['type' => 'text', 'text' => (string) $value],
                    array_values($bodyParams)
                ),
            ];
        }

        if ($buttonParam !== null) {
            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [['type' => 'text', 'text' => $buttonParam]],
            ];
        }

        $response = Http::withToken(config('services.whatsapp.access_token'))
            ->acceptJson()
            ->timeout(15)
            ->post($this->url('messages'), [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => $lang],
                    'components' => $components,
                ],
            ]);

        if ($response->failed()) {
            throw new WhatsAppException(
                (string) $response->json('error.message', 'WhatsApp request failed'),
                (int) $response->json('error.code', $response->status())
            );
        }

        return (string) $response->json('messages.0.id');
    }

    private function url(string $path): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/%s',
            config('services.whatsapp.api_version'),
            config('services.whatsapp.phone_number_id'),
            $path
        );
    }
}
```

### 5.4 Queued job

Sending runs on the queue (`QUEUE_CONNECTION=database`, as today), so publishing a notification stays fast and a failed WhatsApp send never breaks the FCM push.

```php
// app/Jobs/SendWhatsAppTemplate.php
namespace App\Jobs;

use App\Models\Customer;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppClient;
use App\Services\WhatsApp\WhatsAppException;
use App\Services\WhatsApp\WhatsAppPhone;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;

class SendWhatsAppTemplate implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(
        public int $customerId,
        public string $template,
        public string $category,          // utility | marketing
        public array $bodyParams = [],
        public ?string $imageUrl = null,
        public ?string $buttonParam = null,
        public ?int $notificationId = null,
        public ?string $lang = null,      // null = customer's language
    ) {}

    public function middleware(): array
    {
        return [new RateLimited('whatsapp')];
    }

    public function handle(WhatsAppClient $client): void
    {
        if (!config('services.whatsapp.enabled')) {
            return;
        }

        $customer = Customer::find($this->customerId);
        $to = WhatsAppPhone::normalize($customer?->phone);

        if (!$customer || !$to || !$this->allowed($customer)) {
            return;
        }

        $lang = $this->lang ?? $this->customerLang($customer);

        $log = WhatsAppMessage::firstOrCreate(
            ['customer_id' => $customer->id, 'notification_id' => $this->notificationId, 'template' => $this->template],
            ['category' => $this->category, 'lang' => $lang, 'status' => 'queued']
        );

        if ($log->wa_message_id) {
            return; // Already sent on an earlier try.
        }

        try {
            $id = $client->sendTemplate($to, $this->template, $lang, $this->bodyParams, $this->imageUrl, $this->buttonParam);

            $log->update(['wa_message_id' => $id, 'status' => 'sent', 'sent_at' => now()]);
        } catch (WhatsAppException $e) {
            $log->update(['status' => 'failed', 'error_code' => (string) $e->getCode(), 'error_message' => mb_substr($e->getMessage(), 0, 250)]);

            if ($e->getCode() === 131050) {
                $customer->update(['whatsapp_marketing_opt_in' => false]);
            }

            Log::warning('SunXFinder WhatsApp: send failed', [
                'customer_id' => $customer->id,
                'template' => $this->template,
                'error_code' => $e->getCode(),
            ]);

            if ($e->isPermanent()) {
                $this->fail($e);
                return;
            }

            throw $e; // Temporary error: the queue retries with backoff.
        }
    }

    private function allowed(Customer $customer): bool
    {
        if (!$customer->whatsapp_opt_in) {
            return false;
        }

        if ($this->category !== 'marketing') {
            return true;
        }

        if (!$customer->whatsapp_marketing_opt_in) {
            return false;
        }

        $sentThisWeek = WhatsAppMessage::query()
            ->where('customer_id', $customer->id)
            ->where('category', 'marketing')
            ->where('created_at', '>=', now()->subDays(7))
            ->whereNotNull('wa_message_id')
            ->count();

        return $sentThisWeek < config('services.whatsapp.marketing_weekly_limit');
    }

    private function customerLang(Customer $customer): string
    {
        $lang = $customer->deviceTokens()->latest()->value('lang');

        return strtolower((string) $lang) === 'en' ? 'en' : 'ar';
    }
}
```

Add a small `App\Models\WhatsAppMessage` model (`$table = 'tbl_whatsapp_messages'`, `$guarded = ['id']`, date casts).

Rate limit, in `AppServiceProvider::boot()`:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('whatsapp', fn () => Limit::perSecond(20));
```

Run a worker in production (Supervisor or a cron that runs `php artisan queue:work --stop-when-empty`). The existing email jobs need one as well.

### 5.5 Where to call it

**Campaign and package pushes:** in `AdminRepository::publishNotification` (and the company version in `CompanyRepository::publishNotification`), after the FCM block. Add an optional `send_whatsapp` field to the form:

```php
if (!empty($data['send_whatsapp']) && config('services.whatsapp.enabled')) {
    $template = $notification->package_offer_id ? 'package_offer' : 'campaign_offer';

    Customer::query()
        ->whereIn('id', $customerIds)
        ->where('whatsapp_opt_in', true)
        ->where('whatsapp_marketing_opt_in', true)
        ->pluck('id')
        ->each(fn ($customerId) => SendWhatsAppTemplate::dispatch(
            customerId: (int) $customerId,
            template: $template,
            category: 'marketing',
            bodyParams: [$notification->title_ar ?: $notification->title_en, Str::limit($helper->plainText($notification->message_ar ?: $notification->message_en), 200)],
            imageUrl: $notification->imageUrl(),
            buttonParam: $notification->company_id ? 'companies/' . $notification->company_id : '',
            notificationId: $notification->id,
        ));
}
```

The body values above are in Arabic. If you want each customer's language, build the parameters inside the job from `notification_id` after the language is known. That is the cleaner version once the first test works.

Keep the existing rules: **company senders cannot broadcast**, and companies get WhatsApp only if the admin allows it and you charge for it. WhatsApp costs you money on every message.

**Offers and projects (utility):** in `Helper::notifyCustomerAboutOffer` and `notifyCustomerAboutProject`. Recommended: send WhatsApp only when the customer has **no** device token, or when the push was not opened after a few hours. This keeps the cost low.

```php
SendWhatsAppTemplate::dispatch(
    customerId: (int) $quotation->customer_id,
    template: 'offer_received',
    category: 'utility',
    bodyParams: [$customerName, $price],
    buttonParam: (string) $quotation->quote_id,
);
```

**OTP (optional):** add a `WhatsAppSmsSender` that implements `App\Contracts\SmsSender` with an authentication template, then add `'whatsapp' => new WhatsAppSmsSender(...)` to the `match` in `AppServiceProvider`. Password reset codes can then go by WhatsApp, with `SMS_DRIVER=whatsapp`.

---

## 6. Webhook

Meta calls this URL for message statuses and for customer replies.

`routes/api.php` (outside any auth middleware; API routes have no CSRF check):

```php
use App\Http\Controllers\Api\WhatsAppWebhookController;

Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle'])
    ->middleware('throttle:300,1');
```

```php
// app/Http/Controllers/Api/WhatsAppWebhookController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\WhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WhatsAppWebhookController extends Controller
{
    private const STOP_WORDS = ['stop', 'unsubscribe', 'إيقاف', 'ايقاف', 'الغاء', 'إلغاء'];
    private const START_WORDS = ['start', 'subscribe', 'اشتراك'];

    /** Meta checks the URL once when you save it. PHP turns "hub.mode" into "hub_mode". */
    public function verify(Request $request)
    {
        $ok = $request->query('hub_mode') === 'subscribe'
            && hash_equals((string) config('services.whatsapp.verify_token'), (string) $request->query('hub_verify_token'));

        return $ok ? response((string) $request->query('hub_challenge'), 200) : response('Forbidden', 403);
    }

    public function handle(Request $request)
    {
        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), (string) config('services.whatsapp.app_secret'));

        if (!hash_equals($expected, (string) $request->header('X-Hub-Signature-256'))) {
            return response('Invalid signature', 401);
        }

        foreach ($request->input('entry', []) as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                foreach ($value['statuses'] ?? [] as $status) {
                    $this->updateStatus($status);
                }

                foreach ($value['messages'] ?? [] as $message) {
                    $this->handleIncoming($message);
                }
            }
        }

        // Always answer 200 quickly, or Meta retries and may disable the webhook.
        return response()->json(['ok' => true]);
    }

    private function updateStatus(array $status): void
    {
        $log = WhatsAppMessage::where('wa_message_id', $status['id'] ?? '')->first();

        if (!$log) {
            return;
        }

        $at = Carbon::createFromTimestamp((int) ($status['timestamp'] ?? time()));
        $state = $status['status'] ?? '';

        $update = match ($state) {
            'delivered' => ['status' => 'delivered', 'delivered_at' => $at],
            'read' => ['status' => 'read', 'read_at' => $at],
            'failed' => [
                'status' => 'failed',
                'error_code' => (string) ($status['errors'][0]['code'] ?? ''),
                'error_message' => mb_substr((string) ($status['errors'][0]['title'] ?? ''), 0, 250),
            ],
            default => [],
        };

        // Statuses can arrive out of order: never move "read" back to "delivered".
        if ($update && !($log->status === 'read' && $state === 'delivered')) {
            $log->update($update);
        }

        if (($status['errors'][0]['code'] ?? null) === 131050) {
            Customer::whereKey($log->customer_id)->update(['whatsapp_marketing_opt_in' => false]);
        }
    }

    private function handleIncoming(array $message): void
    {
        $text = mb_strtolower(trim(
            $message['text']['body']
                ?? $message['button']['text']        // Quick reply, e.g. "Stop promotions"
                ?? ''
        ));

        $customer = $this->findCustomer((string) ($message['from'] ?? ''));

        if (!$customer || $text === '') {
            return;
        }

        if (in_array($text, self::STOP_WORDS, true) || str_contains($text, 'stop promotions')) {
            $customer->update(['whatsapp_marketing_opt_in' => false]);
        } elseif (in_array($text, self::START_WORDS, true)) {
            $customer->update(['whatsapp_marketing_opt_in' => true, 'whatsapp_opt_in' => true, 'whatsapp_opt_in_at' => now()]);
        }

        // Other messages: a 24-hour free reply window is now open.
        // Later: forward them to a support inbox or show them in the admin panel.
    }

    /** "from" is international (249912345678); customers are stored locally (0912345678 or 912345678). */
    private function findCustomer(string $from): ?Customer
    {
        $cc = (string) config('services.whatsapp.country_code');
        $local = str_starts_with($from, $cc) ? substr($from, strlen($cc)) : $from;

        return Customer::whereIn('phone', [$local, '0' . $local, $from, '+' . $from])->first();
    }
}
```

For local testing, Meta must reach your machine, so expose it with a tunnel (ngrok or Cloudflare Tunnel) and put that HTTPS URL in the webhook settings.

---

## 7. Customer opt-in

You need consent from the customer, saved with the date.

**Mobile app:**

- Sign-up: an unchecked box, "Send me updates about my requests on WhatsApp" (`whatsapp_opt_in`).
- Notification settings: two switches, "WhatsApp updates" and "WhatsApp offers and promotions" (`whatsapp_marketing_opt_in`).

**API:** extend the existing `GET/POST /notification-preferences` (`CustomerService::getNotificationPreferences` / `updateNotificationPreferences`) with the two new fields. Set `whatsapp_opt_in_at = now()` when one of them is turned on.

**Website and Facebook:** a "Chat with us on WhatsApp" link (`https://wa.me/249XXXXXXXXX?text=...`). A customer who writes first opens a free 24-hour window. Do not count that as marketing consent.

**Terms and Privacy:** add WhatsApp to the list of channels, and explain how to stop messages (reply "إيقاف" / "STOP", or use the app setting). Edit them from Admin → System → Privacy & Terms.

---

## 8. Admin panel

| Where | What to add |
| --- | --- |
| Create notification | A "Also send on WhatsApp" checkbox, shown only when `WHATSAPP_ENABLED=true`. Show the number of opted-in customers and the estimated cost. |
| Notification details | WhatsApp counts: sent, delivered, read, failed. |
| Customer details | WhatsApp opt-in status and date. |
| Reports | WhatsApp messages per month and per category, so you can match them to Meta's bill. |
| Company notification access | Keep WhatsApp off for companies by default; sell it as a paid extra. |

---

## 9. Testing

1. Use the Meta **test number** and add your own phone as a test recipient in API setup.
2. Send the built-in `hello_world` template first:

   ```bash
   curl -X POST "https://graph.facebook.com/v23.0/PHONE_NUMBER_ID/messages" \
     -H "Authorization: Bearer TOKEN" -H "Content-Type: application/json" \
     -d '{"messaging_product":"whatsapp","to":"2499XXXXXXXX","type":"template","template":{"name":"hello_world","language":{"code":"en_US"}}}'
   ```

3. Run `php artisan tinker` and dispatch `SendWhatsAppTemplate` for your own customer account.
4. Check that the webhook moves the row to `delivered`, then `read`.
5. Reply "STOP" and check that `whatsapp_marketing_opt_in` becomes false.
6. Add feature tests with `Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]])])`, plus one test for the webhook signature check.

---

## Risks to check first

| Risk | What to do |
| --- | --- |
| **Sudan eligibility** | Meta limits WhatsApp Business in some countries and for sanctioned parties. Check the current WhatsApp Business policy and try business verification before you write code. |
| **Paying Meta** | Meta needs an international card or invoicing. If you cannot pay directly, use a Meta Business Solution Provider (for example 360dialog, Twilio or Infobip). The code stays almost the same: only the URL and token change. Twilio uses its own API format. |
| **Cost** | Every template message costs money. Use FCM first, WhatsApp as a fallback, and keep the weekly marketing limit. |
| **Quality rating** | If too many customers block you, Meta lowers your limit or pauses templates. Send only to customers who opted in. |
| **The phone number** | Once a number is on the API, it no longer works in the normal WhatsApp app. Use a separate number for support chats if you need one. |

---

## Build order

| Step | Item | Size |
| --- | --- | --- |
| 1 | Meta setup, verification, test number, `hello_world` works | No code |
| 2 | Migrations, config, `WhatsAppPhone`, `WhatsAppClient`, job | Small |
| 3 | Webhook with signature check, status updates and STOP | Small |
| 4 | Opt-in fields in `/notification-preferences` and the app | Small (backend + app) |
| 5 | `offer_received` utility template for customers without a device token | Small |
| 6 | "Also send on WhatsApp" in admin campaigns, with counts | Small |
| 7 | WhatsApp OTP through `SmsSender` | Small |
| 8 | Paid WhatsApp sends for companies | Medium |

## Decisions for you

1. WhatsApp for service messages only, or for marketing too?
2. Send utility messages to everyone, or only to customers without the app?
3. Use password reset OTPs over WhatsApp instead of SMS?
4. Pay Meta directly, or go through a provider?
5. Which phone number becomes the official SunXFinder WhatsApp number?

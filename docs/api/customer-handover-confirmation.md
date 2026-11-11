# Handover confirmation: backend done

**For:** the Flutter app developer
**Date:** 11 Nov 2026
**Answers:** "Backend changes: customer confirms the handover" (11 Nov 2026)

Everything in your checklist is done. **3 details differ from your note.** They are marked ⚠️ below.

**Update (same day): the owner said yes to both open questions.** Both are built:

- **Auto-confirm after 7 days.** See §7.
- **"Report a problem".** See §8.

§9 lists what to change in the app for these two.

Base URL: `https://sunxfinder.com/api/v1/customer`. No field was removed or renamed.

---

## 1. Project status `handover_pending`

When the company completes the last milestone ("Customer handover") in its dashboard:

- `project.status = "handover_pending"`, `progress_percent = 100`, `handover_requested_at = now`
- `completed_at` stays `null`, and `quote.status` stays `"accepted"`
- The company can no longer change the project or set it to completed. Its dashboard shows "Waiting for customer confirmation".
- The customer gets a push and an inbox notification.

⚠️ **Notification type is `handover_requested`, not `project`.** It follows the existing project notification types (`start_installation`, …). It carries `quote_id` like the others:

| | EN | AR |
|---|---|---|
| title | Please confirm the handover | يرجى تأكيد الاستلام |
| body | Hi {name}, your installation is finished. Please confirm the handover in the app, or report a problem. If you do neither within 7 days, the handover is confirmed automatically. | مرحباً {name}، اكتمل تركيب منظومتك. يرجى تأكيد الاستلام من التطبيق أو الإبلاغ عن مشكلة. إذا لم تفعل خلال 7 أيام، يتم تأكيد الاستلام تلقائياً. |

On tap, open the request (`quote_id`). It has the "Confirm handover" and "Report a problem" buttons.

The old `project_completed` push ("Your solar system is ready") is no longer sent. The customer is the one who completes the project now.

**Existing data:** the migration sets `handover_requested_at` and `handover_confirmed_at` on all projects that are already `completed`, so customers are not asked about old jobs.

## 2. New fields

| `/project-progress/{id}` (`data.…`) | `/my-quotes`, `/main-data` (on each quote) | Type | Meaning |
|---|---|---|---|
| `status` | `project_status` | string | can now be `handover_pending` |
| `handover_requested_at` | `project_handover_requested_at` | ISO or `null` | when the company finished. `null` again after a reported problem, until the company finishes again. |
| `handover_auto_confirm_at` | `project_handover_auto_confirm_at` | ISO or `null` | **when it is confirmed automatically.** Only set while `handover_pending`; it is `handover_requested_at` + 7 days. |
| `handover_confirmed_at` | `project_handover_confirmed_at` | ISO or `null` | when it was confirmed |
| `handover_confirmed_by` | `project_handover_confirmed_by` | `"customer"`, `"auto"` or `null` | who confirmed it |
| `handover_problem_at` | `project_handover_problem_at` | ISO or `null` | the customer's last "Report a problem" |
| `handover_problem_message` | (not in My Quotes) | string or `null` | what they wrote |

The older `offer_status` key on the quote is the project status too, so it can also be `handover_pending`. If the app decides anything from `offer_status` (for example, allowing delete), treat `handover_pending` like `in_progress`.

Also new: each milestone in `/project-progress` has `ar_title` and `ar_description` (Arabic for the 5 default stages; custom stages repeat the company's text).

Sample quote from `/my-quotes` while waiting for the customer (offer `items` and `invoice` left out to keep it short):

```json
{
    "id": 1,
    "reference": "#Q-00001",
    "quote_name": "Home system",
    "service_needed": "Home system",
    "customer_rating": null,
    "estimated_load": "Estimated load: 20 kWh/day",
    "daily_load": "20 kWh",
    "peak_load": "4.2 kW",
    "appliances_ids": null,
    "message": null,
    "package_offer_id": null,
    "status": "accepted",
    "offer_status": "handover_pending",
    "created_at": "2026-11-11T14:05:12.000000Z",
    "updated_at": "2026-11-11T14:05:13.000000Z",
    "project_id": 1,
    "project_status": "handover_pending",
    "project_progress_percent": 100,
    "project_accepted_at": "2026-11-11T14:05:13.000000Z",
    "project_started_at": "2026-11-11T14:05:13.000000Z",
    "project_completed_at": null,
    "project_handover_requested_at": "2026-11-11T14:05:13.000000Z",
    "project_handover_confirmed_at": null,
    "project_handover_auto_confirm_at": "2026-11-18T14:05:13.000000Z",
    "project_handover_confirmed_by": null,
    "project_handover_problem_at": null,
    "offers": [
        {
            "id": 1,
            "company_id": 1,
            "company_name": "Red Sea Solar",
            "company_logo": null,
            "company_rating": null,
            "company_reviews": 0,
            "price": 1500000,
            "currency": "SDG",
            "message": null,
            "ar_message": null,
            "terms": null,
            "terms_display": [],
            "valid_until": null,
            "status": "selected",
            "created_at": "2026-11-11T14:05:12.000000Z",
            "subtotal": "1500000.00",
            "discount": null,
            "tax_percent": null,
            "tax": null,
            "shipping": null,
            "total": "1500000.00"
        }
    ]
}
```

## 3. `POST /quote-complete`

Body `{ "quote_id": 123 }`.

| Case | Response |
|---|---|
| Project `handover_pending` | **200**: project `completed` (`completed_at`, `handover_confirmed_at` set), quote `completed`. The company gets an email, and its project count goes up. |
| Already confirmed | **200**, same body (safe to repeat) |
| Company not finished yet (`accepted` or `in_progress`) | **422**, `reason: "installation_not_finished"`. The message is translated: "The company has not finished the installation yet. You can confirm once the company marks it as finished." / "لم تنتهِ الشركة من التركيب بعد. يمكنك التأكيد بعد أن تعلن الشركة انتهاء العمل." |
| No accepted offer on the request | **422**, `reason: "no_accepted_offer"` |
| Someone else's quote, or unknown | **404** |

Success body:

```json
{ "status": "success", "message": "Handover confirmed.", "data": { "quote_id": 123, "quote_status": "completed" } }
```

(`message` is "تم تأكيد الاستلام." when the customer's app language is Arabic.)

⚠️ The company is notified **by email** ("Customer confirmed the handover"). The company dashboard has no notification inbox. The project page there switches to "Completed".

## 4. Rating only after the confirmation

- `POST /rating` before the confirmation: **422** with `data.reason: "handover_not_confirmed"`, message "You can rate the company after you confirm the handover." / "يمكنك تقييم الشركة بعد تأكيد استلام المنظومة."
- ⚠️ `GET /rating-form` takes no request, so it can't tell which job is meant. Send `?quote_id=123` to have it checked: **422** (same reason) before the confirmation, **404** for someone else's quote. Without `quote_id` it returns the form as before.

## 5. "Special offer" / "عرض خاص"

- **Push sent by a company:** the title is now "Special offer: {offer title}" / "عرض خاص: {offer title}". The default body starts with "A special offer: …" / "عرض خاص: …". The offer card fields (`offer_title_en`, …) are unchanged, so you can keep showing the plain title on the card.
- **Notification `type` is still `offer`.** Show it as "Special offer" / "عرض خاص".
- **Company dashboard:** "Package offers" is now "Special offers" / "العروض الخاصة". The admin campaign type is renamed the same way.

## 6. The whole flow

```
company finishes ──► handover_pending ──┬── customer taps "Confirm handover" ──► completed (confirmed_by: customer)
     ▲                (7-day period)    ├── nobody answers for 7 days ────────► completed (confirmed_by: auto)
     │                                  └── customer taps "Report a problem" ─► in_progress
     └──────────── company fixes it and finishes again (new 7-day period) ◄──────────┘
```

## 7. Auto-confirm after 7 days

If the customer neither confirms nor reports a problem within **7 days** of the company finishing, the server confirms the handover for them. A job runs every hour, so it happens within an hour after `handover_auto_confirm_at`.

The result is the same as the customer confirming: project `completed`, quote `completed`, and rating opens. The difference is `handover_confirmed_by: "auto"`. The company gets an email ("Handover confirmed automatically"), and the customer gets a push and an inbox notification:

| | EN | AR |
|---|---|---|
| type | `handover_auto_confirmed` (with `quote_id`) | |
| title | Handover confirmed | تم تأكيد الاستلام |
| body | Hi {name}, we did not hear from you within 7 days, so the handover of your solar system was confirmed automatically. You can now rate the company in the app. | مرحباً {name}، لم يصلنا ردّك خلال 7 أيام، لذلك تم تأكيد استلام منظومتك تلقائياً. يمكنك الآن تقييم الشركة من التطبيق. |

On tap, open the request and offer the rating.

If the app calls `/quote-complete` after the auto-confirm (for example from an old screen), it gets **200** as usual. Nothing breaks.

## 8. Report a problem: `POST /handover-problem`

Instead of confirming, the customer says the job is not finished.

Body:

```json
{ "quote_id": 123, "message": "Two panels are not connected and the inverter shows an error." }
```

`message` is required, 10 to 1000 characters.

| Case | Response |
|---|---|
| Project `handover_pending` | **200** (below) |
| Not waiting for confirmation (company not finished, already confirmed, or a problem already reported) | **422**, `reason: "not_waiting_confirmation"`, translated message |
| `message` missing, too short or too long | **422**, `errors.message`; `message` is the first error, translated |
| Someone else's quote, or unknown | **404** |
| More than 10 calls a minute | **429** |

Success:

```json
{
  "status": "success",
  "message": "Thank you. We told the company about the problem. The project is back in progress until they fix it.",
  "data": { "quote_id": 123, "quote_status": "accepted", "project_id": 45, "project_status": "in_progress", "complaint_id": 9 }
}
```

The message is "شكرًا لك. أبلغنا الشركة بالمشكلة. عاد المشروع إلى قيد التنفيذ حتى تقوم بحلها." when the app language is Arabic. Show it as is.

What happens on the server:

- **Project:** `in_progress` at 99%, and the "Customer handover" milestone goes back to `in_progress`. `handover_requested_at` and `handover_auto_confirm_at` become `null`, so the 7-day period stops. `handover_problem_at` and `handover_problem_message` are saved. The quote stays `accepted`.
- **Project timeline:** an entry by the customer is added, with their message (visible in `/project-progress` `updates`).
- **Company:** gets an email with the customer's message. Its project page shows the problem in red.
- **SunXFinder team:** a **complaint** is opened (category `service_issue`) and appears in the customer's `GET /complaints`. Its `complaint_id` is in the response.
- **Afterwards:** when the company fixes it and finishes again, the project goes back to `handover_pending`. The customer gets the `handover_requested` push again, with a new 7-day period. The customer can report a problem again if needed.

## 9. What to change in the app

1. **Model:** read `handover_auto_confirm_at`, `handover_confirmed_by` and `handover_problem_at` (with the `project_` prefix in My Quotes).
2. **Waiting screen** (`project_status == "handover_pending"`):
   - Main button: "Confirm handover" / "تأكيد الاستلام" calls `/quote-complete`. This already exists.
   - Second button: **"Report a problem" / "الإبلاغ عن مشكلة"**. It opens a text box ("Describe the problem" / "صف المشكلة", 10 to 1000 characters) and calls `POST /handover-problem`. On 200, show `message` and reload the request.
   - Under the buttons: "If you don't answer, it will be confirmed automatically on {date}" / "إذا لم ترد، سيتم التأكيد تلقائياً في {date}", using `handover_auto_confirm_at` in local time.
3. **After a reported problem** (`project_status == "in_progress"` and `handover_problem_at` is set): show a note, "You reported a problem on {date}. The company is fixing it." / "أبلغت عن مشكلة بتاريخ {date}. الشركة تعمل على حلها." Hide both buttons until it is `handover_pending` again.
4. **Completed:** if `handover_confirmed_by == "auto"`, you may add "Confirmed automatically" / "تم التأكيد تلقائياً" under "Completed". Rating works the same either way.
5. **Notifications:** handle the new type `handover_auto_confirmed` (open the request, offer the rating). `handover_requested` can arrive more than once for the same request (after a reported problem).
6. **Status labels:** your existing labels are fine. `handover_pending` shows "Confirm handover" / "أكّد الاستلام", and `completed` shows "Completed" / "مكتمل".

## 10. Tests

Feature tests on the MySQL test database: **113 passed** (882 assertions). Unit tests: **7 passed**. The handover cases:

- the company finishing → `handover_pending`, request stays `accepted`, customer notified with `quote_id`, the company can't complete it, new fields in My Quotes and project progress, company pages render
- confirming before the company finishes → 422, including when installation is done but the handover step isn't
- the owner confirms → 200, both statuses `completed`, dates set, `confirmed_by: "customer"`; confirming twice → 200 both times, counted and emailed once; someone else's quote → 404
- rating and the rating form before confirmation → 422, after → 201 / 200
- **auto-confirm:** not done after 6 days, done after 7 (`confirmed_by: "auto"`, company count +1, company email, customer push mentioning 7 days, rating opens), and running the job again changes nothing
- **report a problem:** back to `in_progress` with the handover step reopened, complaint opened, company emailed with the message; confirming or reporting again → 422; the company sees it, finishes again → a new 7-day period, then the customer confirms
- **report a problem errors:** not waiting → 422 `not_waiting_confirmation`, short or missing message → 422, someone else's quote → 404
- the 3 company emails (confirmed, auto, problem) render

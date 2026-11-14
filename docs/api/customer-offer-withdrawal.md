# Company withdraws after the customer accepted: backend done

**For:** the Flutter app developer
**Date:** 14 Nov 2026

A company can now back out of a job **after** the customer accepted its offer. It can also ask the customer for a **new price** before it starts. The customer never loses the request: it opens again, and the other companies' offers can be chosen.

Base URL: `https://sunxfinder.com/api/v1/customer`. No field was removed or renamed. **2 new endpoints, 2 new notification types, 3 new fields, 1 new error.**

⚠️ **One change to existing behaviour:** an **expired** offer can no longer be accepted (§6). Until now the server allowed it, as `customer-offer-terms.md` said.

---

## 1. The whole flow

```
customer accepts offer ──► project "accepted" (not started)
   │
   ├─ company withdraws ───────────────────────────────► request reopens (§2)
   │
   ├─ company asks for a new price ─► customer answers (§4)
   │        ├─ Accept  ─► offer gets the new price, job carries on
   │        └─ Decline ─► request reopens (§2)
   │
   └─ company starts work ─► "in_progress" / "handover_pending"
            └─ company asks SunXFinder to cancel ─► admin decides
                     ├─ approved ─► request reopens (§2)
                     └─ rejected ─► job carries on (nothing changes in the app)
```

Companies that withdraw often (3 times in 30 days) need SunXFinder's approval for each new withdrawal. They are also listed lower in `GET /companies` (all sorts except `sort=name`). The app does not need to do anything for this.

## 2. What "the request reopens" means

When the deal ends, the server does all of this at once:

| | Before | After |
|---|---|---|
| `quote.status` | `accepted` | **`pending`** |
| The project | `accepted` / `in_progress` / … | **gone**: `project_id`, `project_status` and `offer_status` are `null` |
| The withdrawing company's offer | `selected` | `withdrawn` (hidden from `offers`, as before) |
| The other companies' offers | `not_selected` | **`submitted`**: the customer can accept them now |
| Who can send offers | — | **every company** (also for requests that came from a package offer) |

- The other offers keep their own `valid_until`. Some may already be expired; see §6.
- The companies whose offers came back get an email asking them to check their price.
- `GET /project-progress/{old_project_id}` now returns **404**. If the app is on that screen, go back to the request and reload it.
- The request counts as an open request again, toward the customer's open-request limit.
- With no project left, the customer can delete the request (`/archive`) as with any pending request.

## 3. New field on each quote: `company_withdrawal`

In `/my-quotes` and `/main-data`, on each quote. It is set **only while `status == "pending"`** after a company left. Otherwise it is `null`, and it goes back to `null` once the customer accepts another offer.

```json
"company_withdrawal": {
    "company_id": 1,
    "company_name": "Red Sea Solar",
    "reason": "out_of_stock",
    "reason_label_en": "The equipment is out of stock",
    "reason_label_ar": "المعدات غير متوفرة حالياً",
    "withdrawn_at": "2026-11-14T13:05:00.000000Z",
    "reopened_offers": 1
}
```

`reopened_offers` is how many other offers came back. When it is `0`, the customer waits for new offers.

`reason` values (show `reason_label_en` / `reason_label_ar`; do not translate the codes yourself):

| `reason` | EN | AR |
|---|---|---|
| `price_changed` | Prices or the exchange rate changed | تغيّرت الأسعار أو سعر الصرف |
| `out_of_stock` | The equipment is out of stock | المعدات غير متوفرة حالياً |
| `outside_coverage` | The site is outside the company's service area | الموقع خارج نطاق خدمة الشركة |
| `site_not_suitable` | The site is not suitable for this system | الموقع غير مناسب لهذه المنظومة |
| `customer_unreachable` | The company could not reach the customer | تعذّر على الشركة التواصل مع العميل |
| `no_capacity` | The company cannot take on the job at this time | لا تستطيع الشركة تنفيذ العمل في الوقت الحالي |
| `customer_declined_price` | The customer declined the new price | رفض العميل السعر الجديد |
| `other` | Other reason | سبب آخر |

The company's free-text note is **not** sent to the app. It goes only to the SunXFinder team.

## 4. Price change

### 4.1 New field: `price_change`

On each quote in `/my-quotes` and `/main-data`, and in `GET /project-progress/{id}` as `data.price_change`. It is `null` unless a new price is waiting for the customer's answer. There is at most one at a time, and only while the project is `accepted` (not started).

```json
"price_change": {
    "id": 7,
    "offer_id": 31,
    "project_id": 45,
    "company_id": 1,
    "company_name": "Red Sea Solar",
    "old_price": 1500000,
    "new_price": 1650000,
    "currency": "SDG",
    "change_percent": 10,
    "reason": "price_changed",
    "reason_label_en": "Prices or the exchange rate changed",
    "reason_label_ar": "تغيّرت الأسعار أو سعر الصرف",
    "note": "The inverter price went up by 10%.",
    "status": "pending",
    "created_at": "2026-11-14T13:05:00.000000Z"
}
```

- `change_percent` can be negative (a lower price). `note` can be `null`. The customer **may** see it, because the company writes it for them.
- Other `reason` values: `equipment_changed` (The equipment had to change / اضطرت الشركة لتغيير المعدات), `site_inspection` (The site inspection showed more work is needed / أظهرت معاينة الموقع أن العمل أكبر من المتوقع), `other`.
- While it is waiting, the company **cannot start the installation**.
- The company can take it back. `price_change` then goes back to `null`, and the old price stands.

### 4.2 `POST /price-changes/{id}/accept`

No body. The offer's `price` becomes `new_price`, and the job carries on.

```json
{
  "status": "success",
  "message": "New price accepted. The company can now start the installation.",
  "data": {
    "price_change_id": 7, "quote_id": 123, "offer_id": 31,
    "price": 1650000, "currency": "SDG",
    "quote_status": "accepted", "project_id": 45, "project_status": "accepted"
  }
}
```

### 4.3 `POST /price-changes/{id}/decline`

No body. The deal with this company ends, and the request reopens exactly as in §2. `company_withdrawal.reason` is then `customer_declined_price`. This does not count against the company.

```json
{
  "status": "success",
  "message": "You declined the new price. Your request is open again, so you can choose another offer.",
  "data": {
    "price_change_id": 7, "quote_id": 123,
    "quote_status": "pending", "project_id": null, "project_status": null,
    "reopened_offers": 1
  }
}
```

### 4.4 Errors (both endpoints)

| Case | Response |
|---|---|
| Already answered, taken back by the company, or the job started | **422**, `reason: "price_change_closed"`, message "This price change is no longer waiting for your answer." / "لم يعد طلب تعديل السعر هذا بانتظار ردك." |
| Someone else's, or unknown id | **404** |
| No token | **401** |
| More than 20 calls a minute | **429** |

All messages follow the app language (Arabic when the device token's `lang` is `ar`). Show `message` as is.

### 4.5 Offer totals after an accepted price change

The offer's `total` (and `price`) is the new price. If the offer has an invoice, its lines no longer add up to the total. The new key `adjustment` on each offer carries the difference, for example `"150000.00"` or `"-20000.00"`. It is `null` when there is no invoice or no difference. Show it as one more line: "Price adjustment" / "تعديل السعر".

## 5. Notifications

Both carry `quote_id`, like the other request notifications.

| type | EN title | EN body | AR title | AR body |
|---|---|---|---|---|
| `offer_withdrawn` | A company withdrew from your request | Hi {name}, your request #Q-00123 is open again. Red Sea Solar can no longer carry it out (the equipment is out of stock). You can choose another offer or wait for new ones. | انسحبت شركة من طلبك | مرحباً {name}، طلبك #Q-00123 مفتوح من جديد. لم تعد Red Sea Solar قادرة على تنفيذه (المعدات غير متوفرة حالياً). يمكنك اختيار عرض آخر أو انتظار عروض جديدة. |
| `offer_price_changed` | Price change on your accepted offer | Hi {name}, the company Red Sea Solar asks to change the price of your request #Q-00123 from 1,500,000 SDG to 1,650,000 SDG (prices or the exchange rate changed). Please accept or decline in the app. If you decline, your request opens again for other offers. | طلب تعديل سعر العرض المقبول | مرحباً {name}، تطلب شركة Red Sea Solar تعديل سعر طلبك #Q-00123 من 1,500,000 SDG إلى 1,650,000 SDG (تغيّرت الأسعار أو سعر الصرف). يرجى القبول أو الرفض من التطبيق. إذا رفضت، يُفتح طلبك من جديد لعروض أخرى. |

- **`offer_withdrawn`**: on tap, open the request (`quote_id`) and reload it. It is also sent when SunXFinder approves a company's cancellation after work started.
- **`offer_price_changed`**: on tap, open the request. It shows the price-change card (§7).
- The customer gets **no** push when they decline a price change themselves. The response message is enough.

## 6. ⚠️ Expired offers can no longer be accepted

`POST /offer-accept` on an offer whose `valid_until` is before today:

```json
{
  "status": "error",
  "code": 409,
  "message": "This offer has expired. Ask the company to renew it, or choose another offer.",
  "data": { "reason": "offer_expired", "valid_until": "2026-11-10" }
}
```

(The Arabic message is "انتهت صلاحية هذا العرض. اطلب من الشركة تحديثه، أو اختر عرضاً آخر.") The last valid day itself still works. Offers without a validity (`valid_until: null`) never expire. The company renews an offer by editing it, which moves `valid_until` forward.

This matters most after a reopen (§2): an older offer that came back may already be expired.

## 7. What to change in the app

1. **Model:** read `company_withdrawal` and `price_change` on each quote, `price_change` on project progress, and `adjustment` on each offer.
2. **Request screen, reopened** (`status == "pending"` and `company_withdrawal != null`): show a banner at the top.
   - EN: "{company_name} withdrew from this job ({reason_label_en}). Your request is open again: choose another offer below, or wait for new ones."
   - AR: "انسحبت {company_name} من هذا العمل ({reason_label_ar}). طلبك مفتوح من جديد: اختر عرضاً آخر أدناه، أو انتظر عروضاً جديدة."
   - The offers list below works as usual.
3. **Price-change card** (`price_change != null`): show it above the project progress.
   - Content: old price struck through, new price, `change_percent` ("+10%"), the reason label and `note`.
   - Buttons: **"Accept new price" / "قبول السعر الجديد"** calls `/price-changes/{id}/accept`. **"Decline" / "رفض"** first asks for confirmation:
     - EN: "Decline the new price? Your deal with {company_name} ends and your request opens again for other offers."
     - AR: "هل تريد رفض السعر الجديد؟ ينتهي اتفاقك مع {company_name} ويُفتح طلبك من جديد لعروض أخرى."
     - Then it calls `/price-changes/{id}/decline`.
   - On 200, show `message` and reload the request. On 422 `price_change_closed`, show `message` and reload.
4. **Notifications:** handle `offer_withdrawn` and `offer_price_changed` (open the request by `quote_id`, then reload).
5. **Project progress:** a **404** for a project the app had open means the deal ended. Go back to the request and reload it.
6. **Accept offer:** grey out the "Accept" button when `valid_until` is before today, with "Expired" / "منتهي الصلاحية". Also handle 409 `offer_expired` in case the date passed while the screen was open.
7. **Offer details:** when `adjustment` is not null, add the line "Price adjustment" / "تعديل السعر" before the total.

There are no new project statuses in the app. A cancelled project simply disappears, and the request goes back to `pending`.

## 8. Tests

Feature tests on the MySQL test database: the 13 new tests in `tests/Feature/OfferWithdrawalTest.php` pass. They cover:

- withdrawing before the start: request `pending` and open to all companies, other offer `submitted` with its validity unchanged, push `offer_withdrawn` with company and reason, My Quotes `company_withdrawal`, the customer accepts the other offer (new project, banner gone)
- withdrawal validation; another company's project → 404
- after the start: request needs a note, one at a time; admin approves (request reopens, push, waived count) or rejects (job carries on, company emailed and sees the note); the company takes its request back
- 3 withdrawals in 30 days: flagged, listed after other companies in `GET /companies`, next withdrawal needs approval; waiving clears the flag; the count drops after 30 days
- price change: start blocked while waiting, push with both prices, `price_change` in My Quotes and project progress, accept (price and commission updated, company emailed), answering twice → 422; decline (request reopens, not counted); validation (same price, too low, second change, after the start, someone else's → 404)
- expired offer → 409 `offer_expired`; the last valid day still works
- the 5 new company emails render; company pages render in Arabic with no missing text

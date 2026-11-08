# Package offers: app API

Companies and SunXFinder admins can push a **package offer** to customers: a ready solar system (panels, inverter, batteries, installation…) for loads up to a set size, for example "5 kW home system, 2,300,000 SDG", usually with a picture.

- A **company** sends its offers to its own customers (people who accepted one of its offers before).
- An **admin** sends a company's offer as a "Company offer" campaign, to all customers, one city, or selected customers.

For the app both look the same: a notification with `type: "offer"`, an image, an offer card and a `package_offer` object.

The customer opens it from the notification and can **accept** or **decline**. To accept, they add their devices in the load calculator; the peak load must not be above the offer's limit (`max_load_kw`). **Accepting is final on both sides**: the request is saved as accepted, the company's offer as selected (with its invoice), and a project is created, waiting for the company to start it. The company does not send another offer, and the customer does not accept again.

Base URL: `https://sunxfinder.com/api/v1/customer`. Every endpoint needs `Authorization: Bearer {token}` and `Accept: application/json`.

Older app versions keep working: they show the notification and its offer card as before, but cannot accept.

---

## Changes

### Update 3: offer images, and one kind of "Company offer"

| | Before | Now |
|---|---|---|
| Offer image | none | `GET /package-offers/{id}` returns `image` (`{ url, width, height }` or `null`). Show it at the top of the Offer details screen. |
| Push and inbox image | only on admin campaigns | Every package offer notification carries the image (the offer's own, or the campaign image an admin uploaded): `image_url` in the push data, `image` in the notification object, the same as other campaigns. |
| `type: "offer"` notifications | could be a hand-typed price card **without** `package_offer` | New ones always have `package_offer`. Older ones in the inbox may still have `offer` but `package_offer: null`: show the card, but **no Accept / Decline** buttons. |

No new endpoints. No field was removed or renamed.

### Update 2: accepting is final

| | Before | Now |
|---|---|---|
| After `POST /package-offers/{id}/accept` | Request `pending`. The company reviewed it and sent an offer, which the customer accepted with `POST /offer-accept`. | Request `accepted`, offer `selected`, **project created** (status `accepted`, waiting to start). Nothing else to do. |
| Accept response (201) | `data.quote` and `data.package_offer` | Adds `quote_id`, `offer_id`, `company_id`, `project_id`, `offer_status`, `quote_status`, `project_status` and `progress_percent`, the same keys as `POST /offer-accept`. `data.quote.status` is now `accepted`. |
| After accepting, open… | the request, waiting for offers | the **project** (`GET /project-progress/{project_id}`), or the request in My Quotes, which now shows the accepted offer, its invoice and the project |
| `offer_received` push from the company | yes | no (the customer just accepted) |
| Decline after accepting | 409, "cancel the request" | 409 `already_accepted`: the project already exists |

All other endpoints, fields and error reasons are unchanged.

---

## 1. The flow

```
Push "5 kW home system"  ──tap──►  Offer details screen
                                    │
                    ┌───────────────┴────────────────┐
                 Decline                          Accept
           POST /package-offers/{id}/decline        │
                                                    ▼
                                  Load calculator, limited to max_load_kw
                                                    │
                                  Confirm ("Accept for 2,300,000 SDG?")
                                                    │
                                  POST /package-offers/{id}/accept
                                                    │
                                                    ▼
                         Accepted on both sides, in one step:
                           • request   status "accepted"
                           • offer     status "selected" (package price, items, invoice)
                           • project   status "accepted" (waiting to start)
                                                    │
                                                    ▼
                         Open the project. The company starts it and sends
                         progress updates, as for any other project.
```

---

## 2. What the app needs to build

1. **Route the push and deep link** `sunxfinder://package-offer/{id}` to a new **Offer details** screen (section 5.1).
2. **Offer details screen**: image (when set), company, title, description, items, price, "Up to X kW", expiry, and **Accept** / **Decline** buttons.
3. **Calculator in "offer mode"**: open the existing load calculator with a limit of `max_load_kw`. Show the limit and the current peak load ("4.2 of 5 kW"), and do not let the customer send a load above it (disable "Send", or block adding the device that goes over). The server checks this too.
4. **Confirm, then send** with `POST /package-offers/{id}/accept` instead of `POST /quote` (section 5.2). Accepting is final, so ask first, for example "Accept this offer for 2,300,000 SDG? The company will start your project."
5. **After accepting**, open the project screen with `data.project_id` (`GET /project-progress/{project_id}`), or My Quotes. Do **not** call `POST /offer-accept`.
6. **My Quotes**: a request with `package_offer_id` came from an offer. It looks like any accepted request (offer `selected`, invoice, project). Show a small "From offer" label if you like.
7. **Inbox list**: no change needed. Optionally show "Accepted" / "Declined" from `package_offer.my_response`.

---

## 3. Push payload (FCM)

Same as other campaigns, with one new key:

```json
{
  "notification_id": "41",
  "type": "offer",
  "lang": "ar",
  "company_id": "3",
  "image_url": "https://sunxfinder.com/main/images/package-offers/20261108120000-ab12cd34.webp",
  "package_offer_id": "12"
}
```

- All values are strings. `package_offer_id` is **only present for package offers**. `image_url` is only present when there is an image; the system notification also shows it (Android and iOS rich push).
- **On tap:** if `package_offer_id` is present, open the Offer details screen for that id and mark the notification as read (`POST /notifications/{notification_id}/read`). Otherwise behave as today.
- Pass `notification_id` back when accepting or declining (optional, used for reporting).

---

## 4. Notification object: new fields

`GET /notifications` and `GET /notifications/{id}` return two additions. See `customer-notifications.md` for the rest of the object.

```json
{
  "id": 41,
  "type": "offer",
  "title": "5 kW home system",
  "ar_title": "منظومة 5 كيلوواط",
  "image": {
    "url": "https://sunxfinder.com/main/images/package-offers/20261108120000-ab12cd34.webp",
    "width": 1200,
    "height": 600
  },
  "offer": {
    "title": "5 kW home system",
    "ar_title": "منظومة 5 كيلوواط",
    "price": 2300000,
    "old_price": 2400000,
    "currency": "SDG",
    "discount_percent": 4,
    "expires_at": "2026-11-30T21:00:00Z",
    "is_expired": false
  },
  "package_offer": {
    "id": 12,
    "max_load_kw": 5,
    "status": "active",
    "can_respond": true,
    "my_response": null
  },
  "action": {
    "type": "package_offer",
    "url": "sunxfinder://package-offer/12",
    "label": "View offer",
    "ar_label": "تفاصيل العرض"
  }
}
```

| Field | Type | Notes |
|---|---|---|
| `image` | object\|null | Unchanged field (see `customer-notifications.md`). For package offers it is the offer image, or the campaign image when an admin uploaded one. |
| `package_offer` | object\|null | Only for package offers. `null` for every other notification, including older `type: "offer"` items that only have a price card: show those without Accept / Decline. |
| `package_offer.status` | `active` \| `expired` \| `closed` | `closed` also covers a deleted offer or a company no longer on the app. |
| `package_offer.can_respond` | bool | Show Accept / Decline only when `true`. |
| `package_offer.my_response` | object\|null | `{ "status": "accepted" \| "declined", "quote_id": 88 \| null, "responded_at": "…Z" }` |
| `action.type` | string | **New value** `package_offer`: open the Offer details screen for `package_offer.id`. If an admin also added a custom button, `action` is that button instead, but `package_offer` is still set, so still show Accept / Decline. |

---

## 5. Endpoints

### 5.1 Offer details: `GET /package-offers/{id}`

Only works for customers who were sent the offer. Anyone else gets `404`.

**200:**

```json
{
  "status": "success",
  "code": 200,
  "message": "Offer retrieved successfully.",
  "data": {
    "package_offer": {
      "id": 12,
      "title": "5 kW home system",
      "ar_title": "منظومة 5 كيلوواط",
      "description": "Installed in 3 days. 5 year warranty.",
      "ar_description": "التركيب خلال 3 أيام. ضمان 5 سنوات.",
      "image": {
        "url": "https://sunxfinder.com/main/images/package-offers/20261108120000-ab12cd34.webp",
        "width": 1200,
        "height": 600
      },
      "company": {
        "id": 3,
        "name": "Red Sea Solar",
        "slug": "red-sea-solar",
        "city": "Port Sudan",
        "logo_url": "https://sunxfinder.com/…/logo.png",
        "is_verified": true
      },
      "max_load_kw": 5,
      "items": [
        { "type": "solar_panel", "product_id": 4, "description": "Tiger Neo 550W", "quantity": 6, "unit_price": 200000, "line_total": 1200000 },
        { "type": "inverter", "product_id": 6, "description": "Deye 5kW", "quantity": 1, "unit_price": 900000, "line_total": 900000 },
        { "type": "other", "product_id": null, "description": "Installation", "quantity": 1, "unit_price": 300000, "line_total": 300000 }
      ],
      "subtotal": 2400000,
      "discount": 100000,
      "price": 2300000,
      "currency": "SDG",
      "discount_percent": 4,
      "expires_at": "2026-11-30T21:00:00Z",
      "is_expired": false,
      "status": "active",
      "can_respond": true,
      "my_response": null
    }
  }
}
```

| Field | Notes |
|---|---|
| `ar_title` / `ar_description` | Use for Arabic users; fall back to `title` / `description` when null. |
| `image` | `{ url, width, height }` or `null`. Usually 2:1 (1200 × 600). Use `width` / `height` to reserve the space before it loads. This is the offer's own image; it can differ from the notification image when an admin uploaded a campaign image. |
| `items[].type` | `solar_panel`, `inverter`, `battery`, `other` or `null`. Use it for an icon. |
| `items[].description` | Text written by the company (not translated). |
| `price` | What the customer pays: `subtotal - discount`. |
| `company` | `null` if the company is no longer on the app (then `status` is `closed`). |
| `max_load_kw` | The calculator limit for this offer. |
| `my_response.quote_id` | After accepting: the accepted request, so "View project" can find it in My Quotes. |

**404:** `{ "status": "error", "code": 404, "message": "Offer not found.", "data": null }`

### 5.2 Accept: `POST /package-offers/{id}/accept`

Send it from the calculator, with the same load fields as `POST /quote`:

```json
{
  "peak_load": "4.2 kW",
  "peak_load_kw": 4.2,
  "daily_load": "20 kWh",
  "estimated_load": "Estimated load: 20 kWh/day, peak 4.2 kW. Suggested system: …",
  "appliances_ids": [{ "id": 1, "qty": 2 }, { "id": 4, "qty": 1 }],
  "message": "Please call before visiting.",
  "quote_name": "Home system",
  "notification_id": 41
}
```

| Field | Required | Notes |
|---|---|---|
| `peak_load` | yes | Same text as `/quote`, e.g. `"4.2 kW"`. |
| `peak_load_kw` | **recommended** | The calculator's peak load **as a number in kW**. The server checks this against `max_load_kw`. Without it, the server reads the number from `peak_load` (`"4.2 kW"`, `"3500 W"`, `"٤ كيلوواط"`). |
| `daily_load` | yes | Same as `/quote`. |
| `appliances_ids` | **yes** | The devices, same formats as `/quote` (array or string). The company uses them to plan the installation. |
| `estimated_load` | no | Same as `/quote`. |
| `message` | no | Note to the company. |
| `quote_name` | no | Defaults to the offer title in the customer's language. |
| `notification_id` | no | The notification it was opened from. |

**201:** accepted on both sides. The top-level keys are the same as `POST /offer-accept`, plus the new request and the updated offer.

```json
{
  "status": "success",
  "code": 201,
  "message": "Offer accepted and project created. The company will contact you to start the installation.",
  "data": {
    "quote_id": 88,
    "offer_id": 140,
    "company_id": 3,
    "project_id": 57,
    "offer_status": "selected",
    "quote_status": "accepted",
    "project_status": "accepted",
    "progress_percent": 0,
    "quote": {
      "id": 88,
      "reference": "#Q-00088",
      "company_id": 3,
      "package_offer_id": 12,
      "quote_name": "5 kW home system",
      "estimated_load": "…",
      "peak_load": "4.2 kW",
      "daily_load": "20 kWh",
      "appliances_ids": "[{\"id\":1,\"qty\":2},{\"id\":4,\"qty\":1}]",
      "message": "Please call before visiting.",
      "status": "accepted",
      "created_at": "2026-11-08T12:00:00.000000Z"
    },
    "package_offer": { "…": "same object as 5.1, with my_response.status = accepted and can_respond = false" }
  }
}
```

| Field | Notes |
|---|---|
| `project_id` | The new project. Open it with `GET /project-progress/{project_id}`. Its status is `accepted` (waiting to start) until the company starts it. |
| `offer_id` | The company's offer on this request, already `selected`. In My Quotes it has the package items and an invoice (`invoice_number` like `PKG-12-Q00088`). |
| `quote_id` | The request, already `accepted`. |

After this, open the project (or My Quotes). There is no second offer to accept, so do **not** call `POST /offer-accept`. The company starts the project, and its progress updates arrive as for any other project.

**Errors.** The app should switch on `data.reason`:

| HTTP | `data.reason` | Meaning | Suggested app text |
|---|---|---|---|
| 422 | `load_exceeds_offer` | Peak load above the limit. `data.max_load_kw` and `data.peak_load_kw` are included. | "Your load is 6.3 kW. This offer covers up to 5 kW. Remove some devices." |
| 422 | `invalid_load` | No usable peak load was sent. | Send `peak_load_kw`. |
| 409 | `offer_closed` | The offer ended, expired or the company left. | "This offer has ended." |
| 409 | `already_accepted` | Already accepted. `data.quote_id` is the accepted request. | Open that request and its project. |
| 404 | none | Not sent to this customer, or unknown id. | |
| 422 | none (`errors` object) | Validation failed (missing fields). | |

Accepting a package offer is not blocked by the customer's open-requests limit (`quote_limit_reached` only applies to `POST /quote`).

### 5.3 Decline: `POST /package-offers/{id}/decline`

```json
{ "notification_id": 41 }
```

The body is optional. **200:**

```json
{
  "status": "success",
  "code": 200,
  "message": "Offer declined.",
  "data": { "package_offer": { "…": "my_response.status = declined, can_respond stays true while the offer is open" } }
}
```

A customer who declined can still accept later while the offer is open. Declining after accepting returns **409** `already_accepted`, because the offer is already accepted and its project created.

---

## 6. My Quotes

Each quote in `POST /my-quotes` (and `main-data`) has a new field:

```json
"package_offer_id": 12
```

It is `null` for normal requests. A request from a package offer comes back already accepted: `status` is `accepted`, `project_id` is set, and it has one offer with `status` `selected` and its invoice. Show it like any other accepted request. `POST /offer-accept` and `POST /offer-reject` are unchanged for normal requests and are not used for package offers.

---

## 7. Quick checklist

- [ ] Handle `package_offer_id` in the push and the `sunxfinder://package-offer/{id}` deep link.
- [ ] Offer details screen (`GET /package-offers/{id}`) with the `image` on top, right-to-left with the `ar_*` fields.
- [ ] Older `type: "offer"` notifications with `package_offer: null`: show the card only, no Accept / Decline.
- [ ] Accept / Decline only when `can_respond` is true; show "You accepted / declined" from `my_response`.
- [ ] Calculator limited to `max_load_kw`; send `peak_load_kw` as a number.
- [ ] Confirm before accepting (it is final), then handle the `data.reason` errors.
- [ ] After accepting, open the project (`data.project_id`). Do not call `/offer-accept`.

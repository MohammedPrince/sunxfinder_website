# Package offers: app API

Companies (and SunXFinder admins) can now push a **package offer** to customers: a ready solar system (panels, inverter, batteries, installation…) for loads up to a set size, for example "5 kW home system, 2,300,000 SDG".

The customer opens it from the notification and can **accept** or **decline**. To accept, they add their devices in the load calculator; the peak load must not be above the offer's limit (`max_load_kw`). The request then goes to that company only. The company checks the devices and sends a normal offer, which the customer accepts in My Quotes as today.

Base URL: `https://sunxfinder.com/api/v1/customer`. Every endpoint needs `Authorization: Bearer {token}` and `Accept: application/json`.

Older app versions keep working: they show the notification and its offer card as before, but cannot accept.

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
                                  POST /package-offers/{id}/accept
                                                    │
                                                    ▼
                         New request in My Quotes ("waiting for the company's offer")
                                                    │
                                  The company reviews the devices and sends its offer
                                  (normal "offer_received" push, normal offer card)
                                                    │
                                  Customer accepts it: POST /offer-accept (unchanged)
```

---

## 2. What the app needs to build

1. **Route the push and deep link** `sunxfinder://package-offer/{id}` to a new **Offer details** screen (section 3).
2. **Offer details screen**: company, title, description, items, price, "Up to X kW", expiry, and **Accept** / **Decline** buttons.
3. **Calculator in "offer mode"**: open the existing load calculator with a limit of `max_load_kw`. Show the limit and the current peak load ("4.2 of 5 kW"), and do not let the customer send a load above it (disable "Send" or block adding the device that goes over). The server checks this too.
4. **Send** with `POST /package-offers/{id}/accept` instead of `POST /quote` (section 5).
5. **My Quotes**: a request with `package_offer_id` came from an offer. Show a small "From offer" label if you like. Nothing else changes there.
6. **Inbox list**: no change needed. Optionally show "Accepted" / "Declined" from `package_offer.my_response`.

---

## 3. Push payload (FCM)

Same as other campaigns, with one new key:

```json
{
  "notification_id": "41",
  "type": "offer",
  "lang": "ar",
  "company_id": "3",
  "package_offer_id": "12"
}
```

- All values are strings. `package_offer_id` is **only present for package offers**.
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
| `package_offer` | object\|null | Only for package offers. `null` for every other notification. |
| `package_offer.status` | `active` \| `expired` \| `closed` | `closed` also covers a deleted offer or a company no longer on the app. |
| `package_offer.can_respond` | bool | Show Accept / Decline only when `true`. |
| `package_offer.my_response` | object\|null | `{ "status": "accepted" \| "declined", "quote_id": 88 \| null, "responded_at": "…Z" }` |
| `action.type` | string | **New value** `package_offer`: open the Offer details screen for `package_offer.id`. (If an admin also added a custom button, `action` is that button instead, but `package_offer` is still set, so still show Accept / Decline.) |

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
| `items[].type` | `solar_panel`, `inverter`, `battery`, `other` or `null`. Use it for an icon. |
| `items[].description` | Text written by the company (not translated). |
| `price` | What the customer pays: `subtotal - discount`. |
| `company` | `null` if the company is no longer on the app (then `status` is `closed`). |
| `max_load_kw` | The calculator limit for this offer. |

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
| `appliances_ids` | **yes** | The devices, same formats as `/quote` (array or string). The company needs them to review the request. |
| `estimated_load` | no | Same as `/quote`. |
| `message` | no | Note to the company. |
| `quote_name` | no | Defaults to the offer title in the customer's language. |
| `notification_id` | no | The notification it was opened from. |

**201:** the new request, plus the updated offer.

```json
{
  "status": "success",
  "code": 201,
  "message": "Offer accepted. The company will review your devices and send you its offer.",
  "data": {
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
      "status": "pending",
      "created_at": "2026-11-08T12:00:00.000000Z"
    },
    "package_offer": { "…": "same object as 5.1, with my_response.status = accepted and can_respond = false" }
  }
}
```

After this, open My Quotes (or the request). It shows as waiting for offers; the company's offer arrives with the normal `offer_received` push.

**Errors.** The app should switch on `data.reason`:

| HTTP | `data.reason` | Meaning | Suggested app text |
|---|---|---|---|
| 422 | `load_exceeds_offer` | Peak load above the limit. `data.max_load_kw` and `data.peak_load_kw` are included. | "Your load is 6.3 kW. This offer covers up to 5 kW. Remove some devices." |
| 422 | `invalid_load` | No usable peak load was sent. | Send `peak_load_kw`. |
| 409 | `offer_closed` | The offer ended, expired or the company left. | "This offer has ended." |
| 409 | `already_accepted` | Already accepted. `data.quote_id` is the request it opened. | Open that request. |
| 404 | none | Not sent to this customer, or unknown id. | |
| 422 | none (`errors` object) | Validation failed (missing fields). | |

Accepting an offer does **not** count toward the customer's open-requests limit check (`quote_limit_reached` only applies to `POST /quote`).

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

A customer who declined can still accept later while the offer is open. Declining after accepting returns **409** `already_accepted`; to drop it, they cancel the request as usual.

---

## 6. My Quotes

Each quote in `POST /my-quotes` (and `main-data`) has a new field:

```json
"package_offer_id": 12
```

It is `null` for normal requests. Everything else about the request and the company's offer is unchanged, including `POST /offer-accept` and `POST /offer-reject`.

---

## 7. Quick checklist

- [ ] Handle `package_offer_id` in the push and the `sunxfinder://package-offer/{id}` deep link.
- [ ] Offer details screen (`GET /package-offers/{id}`), RTL with the `ar_*` fields.
- [ ] Accept / Decline only when `can_respond` is true; show "You accepted / declined" from `my_response`.
- [ ] Calculator limited to `max_load_kw`; send `peak_load_kw` as a number.
- [ ] Handle `data.reason` errors from accept.
- [ ] After accepting, go to the new request in My Quotes.

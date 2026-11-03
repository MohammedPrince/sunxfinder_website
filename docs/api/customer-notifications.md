# Customer notifications & campaigns: app API

Admins can now send **campaigns** to customers: rich notifications about a company offer, a new company, or SunXFinder news. A campaign can include an image, formatted (HTML) text, a featured company, an offer price card and a button.

Campaigns use the same inbox as all other notifications, so the existing list, unread count and read endpoints still work. This document covers the fields that were added.

Base URL: `https://sunxfinder.com/api/v1/customer`. Every endpoint needs `Authorization: Bearer {token}` and `Accept: application/json`.

---

## 1. Push payload (FCM)

The system notification shows the **title**, a **plain-text body** (up to ~230 characters) and the **image** when there is one. The `data` part is what the app reads when the customer taps the notification:

```json
{
  "notification_id": "8",
  "type": "offer",
  "lang": "ar",
  "image_url": "https://sunxfinder.com/main/images/notifications/20261030141954-xkikrjwn.webp",
  "link_url": "https://sunxfinder.com/offers",
  "company_id": "1"
}
```

- All values are **strings**, as FCM requires.
- `image_url`, `link_url` and `company_id` are **only sent when set**.
- **On tap:** open the notification details screen with `GET /notifications/{notification_id}`, then mark it as read.

---

## 2. List: `GET /notifications?per_page=20&page=1`

`per_page` can be 1–100 (default 20). The newest notification comes first.

```json
{
  "status": "success",
  "code": 200,
  "data": {
    "unread_count": 1,
    "notifications": [ { /* Notification object, see section 5 */ } ],
    "pagination": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 1 }
  }
}
```

---

## 3. Details: `GET /notifications/{id}` (new)

`{id}` is the notification `id` from the list or the push.

**200:**

```json
{
  "status": "success",
  "code": 200,
  "data": { "notification": { /* Notification object, see section 5 */ } }
}
```

**404:**

```json
{ "status": "error", "code": 404, "error": "Notification not found" }
```

This endpoint does **not** mark the notification as read. Call section 4 for that.

---

## 4. Mark as read: `POST /notifications/{id}/read`

`{id}` is the **notification id**, the same `id` returned by the list and the push.

> **Fixed:** this endpoint used to match a different internal id, so it often marked the wrong item or returned 404. It now works with the `id` the app already has. No app change is needed.

```json
{
  "status": "success",
  "code": 200,
  "message": "Notification marked as read successfully",
  "data": {
    "notificationDetails": { "id": 8, "is_read": true, "read_at": "2026-10-30T14:20:26.000000Z" },
    "unread_count": 0
  }
}
```

`POST /notifications/read-all` is unchanged.

---

## 5. Notification object

```json
{
  "id": 8,
  "type": "offer",
  "title": "Summer offer",
  "ar_title": "عرض الصيف",
  "body": "Get 20% off Panels Install",
  "ar_body": "خصم 20٪",
  "format": "html",
  "body_html": "<p>Get <b>20%</b> off</p><ul><li>Panels</li><li>Install</li></ul>",
  "ar_body_html": "<p>خصم <b>20٪</b></p>",
  "image": {
    "url": "https://sunxfinder.com/main/images/notifications/20261030141954-xkikrjwn.webp",
    "width": 1200,
    "height": 600
  },
  "company": {
    "id": 1,
    "name": "Sunrise Solar Solutions",
    "slug": "sunrise-solar-solutions",
    "city": "Khartoum",
    "logo_url": "https://sunxfinder.com/…/logo.png",
    "is_verified": true
  },
  "offer": {
    "title": "5 kW system",
    "ar_title": "نظام 5 كيلو",
    "price": 800,
    "old_price": 1000,
    "currency": "SDG",
    "discount_percent": 20,
    "expires_at": "2026-11-06T14:19:54Z",
    "is_expired": false
  },
  "action": {
    "type": "url",
    "url": "https://sunxfinder.com",
    "label": "Book now",
    "ar_label": "احجز"
  },
  "read": false,
  "read_at": null,
  "created_at": "2026-10-30T14:19:55Z"
}
```

### Fields

| Field | Type | Notes |
|---|---|---|
| `id` | int | Notification id. Use it for the details and read endpoints. |
| `type` | string | Existing types: `general`, `quote`, `account`, `support`, `maintenance`, `promotion`. **New:** `offer` (company offer) and `new_company` (company just joined). Use it for the badge or icon. |
| `title` / `ar_title` | string / string\|null | Use `ar_title` for Arabic users, falling back to `title` when it is null. |
| `body` / `ar_body` | string / string\|null | **Always plain text.** Safe to show in lists and in older app versions. Same Arabic fallback rule. |
| `format` | `"text"` \| `"html"` | `"html"` means the details screen should render `body_html` / `ar_body_html`. |
| `body_html` / `ar_body_html` | string\|null | Sanitized HTML. Only these tags appear: `p br b strong i em u s ul ol li a h3 h4 blockquote small`. The only attribute is `href` on `a`, with an `https://`, `http://`, `mailto:`, `tel:` or `sunxfinder://` URL. `null` when `format` is `"text"`. If `ar_body_html` is null, use `body_html`. |
| `image` | object\|null | Usually 2:1. Use `width`/`height` to reserve space before it loads. |
| `company` | object\|null | Featured company. Open its page with `slug` (same as `GET /companies/slug/{slug}`) or `id`. |
| `offer` | object\|null | Price card. `price`, `old_price`, `discount_percent` and `expires_at` can each be null. `discount_percent` is only set when `old_price > price`. |
| `offer.is_expired` | bool | True after `expires_at`. Grey the card out and/or show "Offer ended". |
| `action` | object\|null | The main button (see below). |
| `read` / `read_at` | bool / ISO-8601\|null | |
| `created_at` | ISO-8601 (UTC) | |

### `action.type`

| Value | What the app should do |
|---|---|
| `url` | Open `url` in the browser or an in-app browser. |
| `deep_link` | `url` starts with `sunxfinder://`, e.g. `sunxfinder://company/{slug}`. Route it inside the app. |
| `company` | There is no custom button but a company is featured: open that company. `url` is `sunxfinder://company/{slug}`. |
| `null` | No button. |

Use `action.ar_label` as the button text for Arabic users, otherwise `action.label`. Both are always filled.

---

## 6. Rendering checklist

1. **Inbox list:** title, plain `body` (2 lines), a small `image.url` thumbnail when present, and a badge by `type`.
2. **Details screen:**
   - Image on top.
   - Title.
   - Company chip (logo, name, city), tappable.
   - Offer card: price in `currency`, old price struck through, "{discount}% off", "Valid until {date}".
   - The message: render `body_html` with an HTML widget when `format == "html"`, otherwise show `body` as text. In Flutter, `flutter_html` or `flutter_widget_from_html_core` handles the tag list above, and link taps should go through the same handler as `action`.
   - The `action` button, full width, at the bottom.
3. **Arabic:** lay the screen out right to left, and use the `ar_*` fields with the English fallback.
4. **Mark as read** when the details screen opens.

**Older app versions** keep working. They show `title` and `body` (plain text) and ignore the new fields.

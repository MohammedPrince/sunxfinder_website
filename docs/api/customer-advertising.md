# Sponsored ads: app API

Base path: `/api/v1/customer`. All endpoints need the customer's Sanctum token.

Older app versions keep working. New fields can be ignored, but the app needs an update to show the "Sponsored" label and the opt-out switch.

## Sliders

`data.sliders` in `POST /main-data` and `GET /sliders` has these new fields:

```json
{
  "id": 7,
  "link_url": "https://sunxfinder.com/go/slider/7/app",
  "target_url": "https://example.com/offer",
  "is_sponsored": true,
  "sponsored_label": "ممول"
}
```

| Field | Notes |
| --- | --- |
| `link_url` | Open this as before. For web links it counts the click, then redirects to the advertiser |
| `target_url` | The real link. Use only for display, because opening it does not count a click |
| `is_sponsored` | Show the label when `true` |
| `sponsored_label` | In the customer's app language. Null when not sponsored |

Links that start with `sunxfinder://` are returned unchanged. The app opens them itself, so it must report the tap with `POST /ad-click`.

## Carousels

`data.carousels` entries have `is_sponsored` and `sponsored_label`. Report each tap with `POST /ad-click`.

## Report a tap

`POST /ad-click`

```json
{ "ad_type": "carousel", "ad_id": 3 }
```

| Field | Values |
| --- | --- |
| `ad_type` | `slider` or `carousel` |
| `ad_id` | The `id` from the list |

Response: `200` with `"message": "Click recorded."`, or `404` if the ad no longer exists. The result does not need to block navigation.

**When to call it:**

| Tap on | Call `/ad-click`? |
| --- | --- |
| Carousel entry | Yes |
| Slider with a `sunxfinder://` link | Yes |
| Slider with an `https://` link | **No**, opening `link_url` already counts it |

## Views

The app does nothing. The server counts one view per customer, per ad, per day when it returns the list.

## Sponsored notification setting

`GET /notification-preferences`

```json
{
  "status": "success",
  "code": 200,
  "data": { "sponsored_push_enabled": true }
}
```

`POST /notification-preferences`

```json
{ "sponsored_push_enabled": false }
```

The response has the same `data` as the `GET`.

This stops sponsored pushes only. Updates about the customer's own requests and projects, and messages from companies they have dealt with, keep arriving. The existing device switch still turns off all pushes.

Suggested switch, in Settings:

| Language | Text |
| --- | --- |
| English | Sponsored notifications |
| Arabic | الإشعارات الممولة |

## Notifications

Each item in `GET /notifications` and `GET /notifications/{id}` has `is_sponsored`. Show the label when it is `true`.

# App online / offline: app API

Admins can take the app offline for all customers from **System → Settings → Mobile app status**. The app reads this from `POST /main-data`, in `data.app_availability`.

## Field

While online:

```json
"app_availability": {
  "status": "online",
  "is_online": true,
  "message": null,
  "message_en": null,
  "message_ar": null,
  "lang": "ar",
  "changed_at": "2026-10-31T10:15:00.000000Z"
}
```

While offline:

```json
"app_availability": {
  "status": "offline",
  "is_online": false,
  "message": "يجري تحديث SunXFinder. يرجى المحاولة بعد بضع ساعات.",
  "message_en": "SunXFinder is being updated. Please try again in a few hours.",
  "message_ar": "يجري تحديث SunXFinder. يرجى المحاولة بعد بضع ساعات.",
  "lang": "ar",
  "changed_at": "2026-10-31T10:15:00.000000Z"
}
```

| Field | Type | Notes |
| --- | --- | --- |
| `status` | string | `online` or `offline` |
| `is_online` | boolean | Use this for the check |
| `message` | string or null | In the customer's app language. Null while online |
| `message_en`, `message_ar` | string or null | Both languages, in case the customer changed language. Null while online |
| `lang` | string | The language of `message`, from the customer's most recently used device |
| `changed_at` | string or null | When an admin last changed the status. Null if it was never set |

## What the app must do

1. After every `POST /main-data`, check `is_online`.
2. If it is `false`, show `message` on a blocking screen with a **Try again** button that calls `POST /main-data` again.
3. If it becomes `true`, return to the normal screens.

The switch is checked by the app. While offline, the server keeps answering every endpoint, including `POST /main-data`, so the app can see when it comes back online.

The new status is returned as soon as an admin saves it, but an open app only sees it on its next `POST /main-data`. To react sooner, call it when the app returns to the foreground.

## Related fields

| Field | Applies to | Set from |
| --- | --- | --- |
| `app_availability` (`/main-data`) | All customers | Settings → Mobile app status |
| `app_status` (`/main-data`) | One customer | Customer page in the admin panel |
| `active.is_online` (`GET /api-config`) | Everyone using that API server, before login | Settings → API servers |

Check `app_availability` before `app_status`, so an offline message is shown instead of a personal one.

`/main-data` needs a login, so a customer on the login screen does not see `app_availability` until after signing in.

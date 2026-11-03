# Privacy Policy & Terms: app API

The text of these pages is managed by admins in **System → Privacy & Terms**. Each endpoint returns one page in one language, so the app does not have to pick between English and Arabic fields.

Base URL: `https://sunxfinder.com/api/v1/customer`. Both endpoints need `Authorization: Bearer {token}` and `Accept: application/json`.

| Method | Endpoint | Returns |
| --- | --- | --- |
| GET | `/privacy-policy` | Privacy Policy |
| GET | `/terms` | Terms and Conditions |

## Language

1. If the request has `?lang=en` or `?lang=ar`, that language is returned.
2. Otherwise the language saved through `POST /device-token` is used, from the customer's most recently used device.
3. A customer with no saved device gets English.

Send `?lang=` right after the customer switches language, unless the app has already saved the new language with `POST /device-token`. Any other `lang` value is ignored.

## Response

```json
{
  "status": "success",
  "code": 200,
  "message": "Page retrieved successfully.",
  "data": {
    "page": {
      "key": "terms",
      "lang": "en",
      "label": "Terms & Conditions",
      "title": "Terms and Conditions",
      "intro": "Please read these terms carefully before using SunXFinder...",
      "last_updated": "2026-10-31",
      "url": "https://sunxfinder.com/terms?lang=en",
      "sections": [
        {
          "number": 1,
          "title": "Acceptance of These Terms",
          "body": "<p>These Terms and Conditions govern your access to...</p>"
        }
      ],
      "contacts": [
        {
          "title": "Questions About These Terms?",
          "body": "<p>Email: <a href=\"mailto:info@sunxfinder.com\">info@sunxfinder.com</a></p>"
        }
      ]
    }
  }
}
```

| Field | Type | Notes |
| --- | --- | --- |
| `key` | string | `privacy` or `terms` |
| `lang` | string | The language returned: `en` or `ar`. Use `ar` to lay the page out right to left |
| `label` | string or null | Small label above the title |
| `title`, `intro` | string | Plain text |
| `last_updated` | string or null | `YYYY-MM-DD`. Format it for the customer's language |
| `url` | string | The same page on the website, for a browser or web view |
| `sections` | array | In display order. Show as `{number}. {title}` |
| `contacts` | array | Closing boxes, shown after the sections. May be empty, and may hold more than one |

`body` is **HTML**, so it must be rendered, not shown as text. It can contain `p`, `br`, `b`, `strong`, `i`, `em`, `u`, `s`, `ul`, `ol`, `li`, `a`, `h3`, `h4`, `blockquote` and `small`. Links can be `https://`, `mailto:` or `tel:`.

## Errors

| Code | Body | When |
| --- | --- | --- |
| 401 | Standard unauthenticated response | Missing or expired token |
| 429 | Standard rate limit response | More than 60 requests a minute |
| 500 | `{ "status": "error", "code": 500, "error": "Unable to load the page. Please try again later." }` | Server problem |

## Before login

These endpoints need a token, so they cannot be called from the login or sign-up screens. Open the website pages there instead:

- `https://sunxfinder.com/privacy-policy?lang=ar`
- `https://sunxfinder.com/terms?lang=ar`

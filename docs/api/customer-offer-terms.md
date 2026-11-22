# Offer warranty and terms: app API

Companies can now add **warranty and terms** to the offer they send on a quote request. Before, this was only free text in the message. All fields are optional:

- **Warranty** for panels, inverter, batteries and installation (workmanship), each in years or months
- **Installation time** in days
- **Free maintenance** in months
- **Payment terms**, one of 5 fixed options
- **Offer validity**: 3, 7, 14, 30, 60 or 90 days
- **Included in the price**, from a fixed list (delivery, installation, testing…)
- **Extra benefits**: free text written by the company, in English and/or Arabic

Customers can now compare offers on more than price.

Base URL: `https://sunxfinder.com/api/v1/customer`. **No new endpoints.** No field was removed or renamed. Older app builds keep working and simply do not show the new fields.

---

## 1. Where the new fields are

The 3 new keys are on each item of `offers[]`, wherever My Quotes data is returned:

| Endpoint | Path |
|---|---|
| `POST /my-quotes` | `data.quotes[].offers[]` |
| `POST /main-data` | `data.my_quotes.quotes[].offers[]` |

Both return exactly the same offer object.

| Key | Type | Meaning |
|---|---|---|
| `terms_display` | array | **Use this to show the terms.** Ready-made lines in English and Arabic. `[]` when the company added none. |
| `terms` | object or `null` | The raw structured values, for logic such as sorting or badges ("longest panel warranty"). `null` when none. |
| `valid_until` | `"YYYY-MM-DD"` or `null` | Last day the offer is valid. `null` when the company set no validity. |

---

## 2. Example

```json
{
  "id": 41,
  "company_name": "Red Sea Solar",
  "price": 5000,
  "currency": "SDG",
  "message": "Our offer.",
  "ar_message": "عرضنا.",
  "terms": {
    "warranties": {
      "panels":       { "value": 25, "unit": "years" },
      "inverter":     { "value": 5,  "unit": "years" },
      "installation": { "value": 12, "unit": "months" }
    },
    "installation_days": 3,
    "payment_terms": "deposit_50",
    "validity_days": 14,
    "included": ["delivery", "installation", "testing"],
    "extras": [{ "en": "Free 24/7 phone support", "ar": "دعم هاتفي مجاني" }]
  },
  "terms_display": [
    { "key": "warranty_panels",       "label": "Panels warranty",       "ar_label": "ضمان الألواح",   "value": "25 years",      "ar_value": "25 سنة" },
    { "key": "warranty_inverter",     "label": "Inverter warranty",     "ar_label": "ضمان المحول",    "value": "5 years",       "ar_value": "5 سنوات" },
    { "key": "warranty_installation", "label": "Installation warranty", "ar_label": "ضمان التركيب",   "value": "12 months",     "ar_value": "12 شهرًا" },
    { "key": "installation_time",     "label": "Installation time",     "ar_label": "مدة التركيب",    "value": "Within 3 days", "ar_value": "خلال 3 أيام" },
    { "key": "payment_terms",         "label": "Payment terms",         "ar_label": "شروط الدفع",     "value": "50% deposit, 50% after installation", "ar_value": "مقدم 50%، و50% بعد التركيب" },
    { "key": "included",              "label": "Included in the price", "ar_label": "مشمول في السعر", "value": "Delivery to site, Installation, Testing and commissioning", "ar_value": "التوصيل إلى الموقع، التركيب، الاختبار والتشغيل" },
    { "key": "extra",                 "label": "Extra",                 "ar_label": "ميزة إضافية",    "value": "Free 24/7 phone support", "ar_value": "دعم هاتفي مجاني" },
    { "key": "validity",              "label": "Offer valid for",       "ar_label": "العرض صالح لمدة", "value": "14 days",      "ar_value": "14 يومًا" }
  ],
  "valid_until": "2026-11-25",
  "status": "submitted"
}
```

An offer without terms has `"terms": null`, `"terms_display": []` and `"valid_until": null`.

---

## 3. `terms_display` lines

Each line is `{ key, label, ar_label, value, ar_value }`. In Arabic, show `ar_label` and `ar_value`; otherwise show `label` and `value`. The same pattern is used for `message` / `ar_message`. The server already handles Arabic plurals (سنة واحدة، سنتان، 5 سنوات، 25 سنة), so **do not build the text yourself**. Lines come in a fixed order. Show them in that order and skip nothing.

Use `key` only to pick an icon. The keys are:

| `key` | Suggested icon | Can repeat? |
|---|---|---|
| `warranty_panels` | shield / solar panel | no |
| `warranty_inverter` | shield / bolt | no |
| `warranty_batteries` | shield / battery | no |
| `warranty_installation` | shield / wrench | no |
| `installation_time` | calendar / clock | no |
| `free_maintenance` | wrench | no |
| `payment_terms` | wallet | no |
| `included` | check list | no |
| `extra` | star / gift | **yes**, one line per extra (up to 10) |
| `validity` | hourglass | no |

New keys may be added later. **Show a line with an unknown key using a default icon.** Never drop it.

---

## 4. `terms` raw values (optional, for logic)

| Field | Type | Values |
|---|---|---|
| `warranties.{part}` | `{ value: int, unit: "years" \| "months" }` | parts: `panels`, `inverter`, `batteries`, `installation`. A part is missing when not given. |
| `installation_days` | int | 1–365 |
| `free_maintenance_months` | int | 1–120 |
| `payment_terms` | string | `on_completion`, `deposit_50`, `deposit_30`, `full_upfront`, `installments` |
| `validity_days` | int | 1–365 |
| `included` | string[] | `delivery`, `installation`, `mounting`, `cabling`, `testing`, `site_visit`, `training`, `after_sales` |
| `extras` | `{ en: string\|null, ar: string\|null }[]` | free text by the company; one of the two may be `null` |

Every field is optional, and **object key order is not guaranteed**: read by key. To compare warranties, convert months to years (`months / 12`).

---

## 5. What to change in the app

1. **Model**: on the offer model, add `termsDisplay` (list of `{key, label, arLabel, value, arValue}`, default `[]`), `terms` (`Map<String, dynamic>?`) and `validUntil` (`DateTime?`, parsed from `"YYYY-MM-DD"`).
2. **Offer details screen**: add a **"Warranty and terms" / "الضمان والشروط"** section under the price and message. Show one row per `terms_display` line (icon, label, value). Hide the section when the list is empty.
3. **Offer card in the offers list** (optional, recommended): show 1 or 2 short badges taken from `terms`, for example "Panels 25 yr" and "Inverter 5 yr", so customers can compare at a glance.
4. **Validity**: when `valid_until` is set, show "Valid until 25 Nov 2026" / "صالح حتى 25 نوفمبر 2026". After that date, show it as **expired** in a muted colour. The server does **not** block accepting an expired offer, so this is information only. The company can renew the offer by editing it, which moves `valid_until` forward.
5. **Compare offers** (optional): if there is a compare view, add rows for panel and inverter warranty and installation time from `terms`. Show "—" where an offer has none.

### Flutter sketch

```dart
class OfferTermLine {
  final String key, label, arLabel, value, arValue;
  OfferTermLine.fromJson(Map<String, dynamic> j)
      : key = j['key'] ?? '',
        label = j['label'] ?? '',
        arLabel = j['ar_label'] ?? j['label'] ?? '',
        value = j['value'] ?? '',
        arValue = j['ar_value'] ?? j['value'] ?? '';

  String labelFor(bool isArabic) => isArabic ? arLabel : label;
  String valueFor(bool isArabic) => isArabic ? arValue : value;
}

// In Offer.fromJson:
termsDisplay = ((j['terms_display'] as List?) ?? [])
    .map((e) => OfferTermLine.fromJson(e as Map<String, dynamic>))
    .toList();
terms = j['terms'] as Map<String, dynamic>?;
validUntil = j['valid_until'] != null ? DateTime.tryParse(j['valid_until']) : null;
```

---

## 6. Related changes customers may notice

- When the company picks a payment term, the offer's invoice uses it for `invoice.payment_terms` (for example `"50% deposit, 50% after installation"`). Before, this was always `"Due upon acceptance"`, which is still the value when no payment term is picked.
- Offers that come from accepting a **package offer** have no terms yet (`terms: null`).

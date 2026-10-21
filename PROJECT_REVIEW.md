# SunXFinder — Project Review (Docs + Code)

Reviewed: 2026-10-21 · Repo: `C:\wamp64\www\GitRepo\sunXfinder` · Laravel 12 / PHP 8.2 / Sanctum 4

Priority key: 🔴 fix now · 🟠 fix soon · 🟢 nice to have

---

## 1. Markdown files — status

| File | State | Verdict |
|---|---|---|
| `README.md` | Stock Laravel skeleton README + one broken line at the end | 🔴 Replace completely |
| `PERFORMANCE_OPTIMIZATION_RECOMMENDATIONS.md` | Partly outdated / partly already done | 🟠 Update |
| `CLAUDE.md` | Missing | 🟠 Add |
| `docs/API.md` (mobile API reference) | Missing | 🔴 Add (Flutter app depends on it) |
| `docs/DATABASE.md` | Missing | 🟠 Add |
| `docs/DEPLOYMENT.md` (dev.sunxfinder.com) | Missing | 🟠 Add |
| `CHANGELOG.md` | Missing | 🟢 Add |

### 1.1 README.md — problems
- Content is the default Laravel text (sponsors, Taylor Otwell's security e-mail, etc.). Nothing about SunXFinder.
- The last line `# sunxfinder_website` is saved in **UTF-16** (null bytes between letters, appended with PowerShell `echo >>`). It renders as garbage on GitHub. Re-save the file as UTF-8.

### 1.2 README.md — what it should contain
1. **What SunXFinder is** — solar-company directory + quote marketplace for Sudan, English/Arabic (RTL).
2. **Three areas of the app**
   - Public website (`/`, `/companies/{slug}`, registration, terms, privacy)
   - Admin panel (`/admin`, guard `admin`, middleware `admin.auth`)
   - Company portal (`/company`, guard `company`, middleware `company.auth`)
   - Customer mobile API (`/api/v1/customer`, Sanctum tokens, `json.res`)
3. **Architecture** — Controller → Service → Repository (`app/Services`, `app/Repositories`), `Helper` class, `asset_v()` cache-busting helper.
4. **Core flow** — Customer submits Quote → companies send CompanyQuotation offers → customer accepts one → a Project is created → company posts Milestones/Updates → customer tracks progress in the app.
5. **Requirements & local setup** (WAMP / MySQL, not SQLite):
   ```bash
   composer install
   cp .env.example .env   # then set DB_CONNECTION=mysql etc.
   php artisan key:generate
   php artisan migrate
   php artisan storage:link
   ```
6. **Environment variables** that matter (DB, MAIL, APP_URL, SANCTUM settings).
7. **Localization** — `resources/lang/{en,ar}/messages.php`, `?lang=ar` + `locale` cookie (`SetLocale` middleware, web only).
8. **Links** to `docs/API.md`, `docs/DATABASE.md`, `docs/DEPLOYMENT.md`, `CLAUDE.md`.

### 1.3 PERFORMANCE_OPTIMIZATION_RECOMMENDATIONS.md — what to update
- 🔴 **Wrong build tool.** It recommends Laravel Mix, `webpack.mix.js` and `mix('js/app.js')`. The project uses **Vite** (`vite.config.js`, `laravel-vite-plugin`) — replace with `@vite(...)` or your own `asset_v()`.
- **Already done — mark as ✅:**
  - Long cache headers + gzip in `public/.htaccess`
  - Cache busting via `asset_v()` (`app/helpers.php`)
  - WebP logo (`visitors/images/sunxfinder-logo.webp`, `sunxfinder-logo-480.png`)
  - `critical.css` exists in `public/visitors/css/`
- The Apache `ExpiresByType` sample in the doc conflicts with what is actually in `.htaccess` (1 month vs 1 year) — remove the sample and point to the real file.
- Remove the `MinifyHtml` middleware example — the regex `\s+ → ' '` breaks `<pre>`, `<textarea>` and inline JS. Not recommended.
- The "Current estimate" metrics table is a guess, not a measurement. Replace with real PageSpeed numbers or delete.
- **Add the real heavy assets found** (see §3.4).

---

## 2. Bugs & risks found in code (should be documented AND fixed)

### 🔴 2.1 Public `/clear` route
`routes/web.php` — anyone on the internet can run `GET /clear` → `optimize`, `cache:clear`, `config:clear`. Remove it or put it behind `admin.auth`.

### 🔴 2.2 Public e-mail preview routes
`GET /email-en` and `GET /email-ar` are open in production. Wrap in `if (app()->environment('local'))` or remove.

### 🔴 2.3 Migrations do not match the models — fresh `migrate` will fail
- `create_tbl_quote_requests_table` creates `customer_name, email, phone, system_type, requirements…`
- `create_quotes_table` creates **`tbl_quotes`** with `customer_id, company_id, service_needed, estimated_load…`
- But `App\Models\Quote` uses **`tbl_quote_requests`** with the `tbl_quotes` columns.
- `add_load_fields_to_tbl_quotes_table` alters `tbl_quote_requests` `->after('estimated_load')` — that column doesn't exist there → **MySQL error on a fresh database** (e.g. setting up a new server).
- `tbl_company_quotations.quote_id` and `tbl_projects.quote_id` reference `tbl_quote_requests`.

**Fix:** pick one table (`tbl_quote_requests`), make one migration create it with the real columns (`customer_id, company_id, service_needed, estimated_load, peak_load, daily_load, message, status`), and delete the `tbl_quotes` migration. Test with `php artisan migrate:fresh` on an empty DB.

### 🟠 2.4 Duplicate / empty migrations
`2026_10_16_092700_create_appliance_groups_table.php` and `2026_10_16_092705_create_appliances_table.php` create empty tables (`id` + timestamps) that nothing uses. The real ones are `tbl_appliance_groups` / `tbl_appliances`. Delete the two empty migrations.

### 🟠 2.5 Customer account deletion has no route
`MainController::deleteAccount()` and the repository method exist, but `routes/api.php` has no route for it. **Google Play requires in-app account deletion.** Add:
```php
Route::delete('/account', [MainController::class, 'deleteAccount']);
```

### 🟠 2.6 Inconsistent API response shape
- `login`, `logout`, `deleteAccount` → `{ status: "success"|"error", code, message | error }`
- `getProjectProgress`, `rejectOffer` → `{ success: true|false, code, message, data }`

The Flutter app needs one envelope. Choose one (recommend `{ success, code, message, data, errors }`) and document it in `docs/API.md`.

### 🟠 2.7 `app/Http/Kernel.php` is dead code
Laravel 12 ignores it; middleware is registered in `bootstrap/app.php` (which is correct). The Kernel also references classes that don't exist (`EncryptCookies`, `VerifyCsrfToken`, `Authenticate`, `RedirectIfAuthenticated`). Delete it to avoid confusion.

### 🟠 2.8 Company profile URL is doubled
`Route::put('/company/profile', …)` is inside the `company` prefix → real URL is `/company/company/profile`. Change to `'/profile'`.

### 🟢 2.9 Smaller items
- `AppServiceProvider`: `$myActiveProjectCount` is only defined inside `if ($company)` → "undefined variable" if the layout renders without a logged-in company. Initialise it to `0`.
- `CustomerRepository::login`: message `"try again in" . ceil(...)` is missing a space → "try again in5 minute(s)".
- `GET /api/user` (default Laravel) is unused — remove.
- `/main-data` and `/my-quotes` are `POST` but only read data — consider `GET` (document it either way).
- Commented "TODO" routes at the bottom of `api.php` (`/saved-companies`, `/notifications`) — track in docs instead.
- Two helper places: `app/helpers.php` and `app/Helpers/Helper.php` — document which is for what, or merge.
- `.env.example` still says `APP_NAME=Laravel`, `DB_CONNECTION=sqlite` — update to SunXFinder + MySQL. `database/database.sqlite` is unused.
- `composer.json` still says `"name": "laravel/laravel"`, description "skeleton application".
- Tests: only the two Laravel example tests. No API tests for login / quote / accept-offer.

---

## 3. Content for the missing docs

### 3.1 `docs/API.md` — endpoints to document
Base: `https://dev.sunxfinder.com/api/v1/customer` · header `Accept: application/json` · auth `Authorization: Bearer <token>`

| Method | Path | Auth | Throttle | Controller method |
|---|---|---|---|---|
| POST | `/login` | – | 5/min (+3 tries / 5 min per user+IP) | `login` |
| POST | `/register` | – | 5/min | `createAccount` |
| POST | `/main-data` | ✔ | 60/min | `mainData` |
| GET | `/cities` | ✔ | 60/min | `getCities` |
| GET | `/companies` | ✔ | 60/min | `getCompanies` |
| GET | `/companies/{id}` | ✔ | 60/min | `getCompany` |
| GET | `/services` | ✔ | 60/min | `getServices` |
| GET | `/carousels` | ✔ | 60/min | `getCarousels` |
| GET | `/calculator-items` | ✔ | 60/min | `appliancesCalculator` |
| POST | `/my-quotes` | ✔ | 60/min | `getMyQuotes` |
| POST | `/quote` | ✔ | 60/min | `storeQuote` |
| POST | `/offer-accept` | ✔ | 60/min | `acceptOffer` |
| POST | `/offer-reject` | ✔ | 60/min | `rejectOffer` |
| GET | `/profile` | ✔ | 60/min | `getProfile` |
| POST | `/profile` | ✔ | 60/min | `updateProfile` |
| GET | `/project-progress/{projectId}` | ✔ | **none** | `getProjectProgress` |
| POST | `/logout` | ✔ | **none** | `logout` |
| *(missing)* | `/account` DELETE | ✔ | – | `deleteAccount` |

For each: request fields + validation rules, success example, error examples (401, 403, 422, 429).
Also document token rules: **one active device** (login deletes all old tokens), token **expires after 1 year**.

### 3.2 `docs/DATABASE.md`
Tables: `tbl_admins`, `tbl_solar_companies`, `tbl_customers`, `tbl_suppliers`, `tbl_products`, `tbl_product_specs`, `tbl_appliance_groups`, `tbl_appliances`, `tbl_company_carousels`, `tbl_quote_requests`, `tbl_company_quotations`, `tbl_projects`, `tbl_project_milestones`, `tbl_project_updates`, `personal_access_tokens`.
Document the status values:
- Company: `pending | approved | …` + `is_active`, `is_verified`
- Customer: `active | pending | suspended | deleted` + `is_active`
- Quote: `pending | accepted | rejected | completed | cancelled`
- CompanyQuotation: includes `withdrawn`, `selected`, `submitted`
- Project: default `accepted`, `progress_percent 0–100`

### 3.3 `docs/DEPLOYMENT.md`
- Server: dev.sunxfinder.com (Apache, `public/` as document root)
- Why `public/.htaccess` has `DirectorySlash Off` and the `^(admin|company)/?$` rule (public folders with the same names as routes)
- Production commands: `composer install --no-dev -o`, `php artisan migrate --force`, `config:cache`, `route:cache`, `view:cache`, `storage:link`
- `.env` production values: `APP_ENV=production`, `APP_DEBUG=false`

### 3.4 Heavy assets (add to the performance doc)
| File | Size | Action |
|---|---|---|
| `public/main/js/lucide.min.js` | 455 KB | Load only used icons, or inline SVGs |
| `public/main/js/quill.js` | 209 KB | Load only on pages with the editor |
| `public/visitors/images/sunxfinder-logo-email.png` | 1.5 MB | **Used in e-mails** — compress to < 50 KB |
| `public/admin/images/sunxfinder-logo.png` | 653 KB | Compress / WebP |
| `public/admin/images/profile_pic.png` | 964 KB | Compress |
| `public/*/images/OLDsun.png` (×2) | 1.8 MB each | Delete (unused "OLD") |
| `public/main/images/company/*/*.png` | 1–1.5 MB each | Resize on upload (max ~400 px) |
| `public/favicon.ico` | 0 bytes | Add a real favicon |
| `public/visitors/js/script.js` | 0 bytes | Delete |
| `resources/views/visitors/welcome.blade.php` | 82 KB | Default Laravel page — delete if unused |

---

## 4. Suggested `CLAUDE.md` (short)
```md
# SunXFinder — notes for Claude Code
- Laravel 12, PHP 8.2, MySQL (WAMP locally). Do NOT use SQLite.
- Pattern: Controller → Service → Repository. Business logic lives in Repositories.
- Guards: admin (session), company (session), customers via Sanctum tokens (api/v1/customer).
- Middleware is registered in bootstrap/app.php (Kernel.php is not used).
- All tables are prefixed tbl_. Quote model → tbl_quote_requests.
- Every user-facing string goes in resources/lang/{en,ar}/messages.php (Arabic is RTL).
- Static assets: use asset_v('path') for cache busting, not asset().
- API JSON envelope: { success, code, message, data, errors } — keep it consistent.
- After schema changes, run `php artisan migrate:fresh` on a scratch DB to prove migrations work.
```

---

## 5. Action checklist
- [ ] 🔴 Remove/protect `/clear`, `/email-en`, `/email-ar`
- [ ] 🔴 Fix quote migrations (§2.3) and test `migrate:fresh`
- [ ] 🔴 Replace README.md (UTF-8)
- [ ] 🔴 Write `docs/API.md`
- [ ] 🟠 Add DELETE `/account` route
- [ ] 🟠 Unify API response envelope
- [ ] 🟠 Delete `app/Http/Kernel.php` and the 2 empty appliance migrations
- [ ] 🟠 Fix `/company/company/profile`
- [ ] 🟠 Update performance doc (Vite, done items, heavy assets)
- [ ] 🟠 Add CLAUDE.md, DATABASE.md, DEPLOYMENT.md
- [ ] 🟢 Update `.env.example`, `composer.json` name, add API tests, CHANGELOG

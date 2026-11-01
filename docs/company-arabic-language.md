# Add Arabic Language Support to the Company Portal

## Goal

Add a complete Arabic interface to the company portal only. Preserve the current English company portal and keep the admin portal and public website unchanged.

## Current project setup

- Website translations are in `resources/lang/en` and `resources/lang/ar`.
- Each locale currently has `messages.php` and `validation.php`.
- Company pages use `resources/views/company/layouts/app.blade.php` and load the shared `admin.css` plus `company-profile.css`.
- Company login uses the shared `resources/views/admin/auth/login.blade.php` template, so any company-specific language control must render only in company login mode. Do not expose or change it on admin login.
- The existing `SetLocale` middleware supports `?lang=ar` and `?lang=en`, stores an explicit choice in a cookie, and defaults to English. Reuse it for company login and authenticated company pages.

## Translation files

Create a separate company translation directory inside each existing locale so company wording stays independent from public-site and admin wording:

```text
resources/lang/
|-- en/
|   `-- company/
|       `-- messages.php
`-- ar/
    `-- company/
        `-- messages.php
```

Use matching keys in both files. Group them by company portal area, for example `navigation.dashboard`, `quote_requests.title`, `projects.actions.update`, `invoices.title`, and `profile.social_links`. Reference nested translations with Laravel keys such as `__('company/messages.navigation.dashboard')`.

Translate all fixed text in the company portal, including:

- Sidebar groups, page titles, breadcrumbs, and descriptions.
- Quote requests, offers, projects, invoices, products, customers, ratings, and notifications.
- Buttons, actions, links, table headings, filters, and empty states.
- Form labels, placeholders, help text, validation messages, and confirmation dialogs.
- Flash messages, alerts, statuses, tooltips, reports, dashboard cards, and pagination.
- Company login, forgot-password, reset-password, and account-status screens.

Keep customer, company, product, and other database content as entered. Do not translate names, user-authored descriptions, email addresses, or other record values.

## Arabic styles and RTL layout

- Keep `public/company/css/company-profile.css` as the existing English company stylesheet.
- Add `public/company/css/company-ar.css` for Arabic-specific RTL overrides.
- Load `company-ar.css` after the current styles only when the locale is Arabic and only from company layouts or company login mode. Do not apply it to admin or visitor pages.
- Set the document language and direction from the active locale (`lang="ar" dir="rtl"` for Arabic; `lang="en" dir="ltr"` for English).
- In Arabic, align the company sidebar, navigation icons and counts, page actions, forms, tables, dropdowns, filters, and pagination for RTL reading.
- Preserve meaningful arrow direction in back/next controls. Do not mirror logos or non-directional icons.
- Keep the English company portal visually unchanged when Arabic is not selected.

## Company login language selector

Show an Arabic / English selector on the company login screen before the company signs in. Selecting a language should use `?lang=ar` or `?lang=en` and preserve that choice through login into the company portal using the existing locale cookie.

The login Blade template is shared with admin. Render the selector and any company-specific RTL login styling only when the login request is for the company portal; do not change the admin login experience.

## Acceptance criteria

1. Company pages remain English by default and retain their current appearance.
2. The company login screen offers Arabic and English before authentication.
3. Opening a company URL with `?lang=ar` renders Arabic text, `lang="ar"`, and RTL layout.
4. Opening a company URL with `?lang=en` returns the company UI to English and LTR layout.
5. The selected language persists from company login through subsequent company navigation.
6. Company navigation, forms, validation, flash messages, tables, reports, invoices, and dialogs use translation keys instead of hard-coded English strings.
7. Arabic pages have no clipped or overlapping text, controls, icons, or table actions at desktop and mobile widths.
8. Admin pages and public visitor pages are unaffected by the company Arabic stylesheet or company translation files.

## Suggested implementation order

1. Create matching English and Arabic company message files and translate shared navigation first.
2. Add the language selector conditionally to company login mode and carry the selected locale through authentication.
3. Update the company layout to set `lang`/`dir` and conditionally load the Arabic stylesheet.
4. Translate shared company header, footer, and reusable components.
5. Replace hard-coded strings across company pages by feature area, keeping matching keys in both locales.
6. Add and refine RTL overrides in `company-ar.css` without changing English or shared admin styles.
7. Review representative quote, project, invoice, product, customer, notification, profile, and login flows in both languages and at mobile widths.

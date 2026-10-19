# Contract: HTTP Routes and Views

**Feature**: [spec.md](../spec.md) | **Date**: 2026-10-19

The site exposes HTML pages only; there is no JSON API. All routes are in `routes/web.php` and
use the `web` middleware group (session, CSRF).

## Public pages (existing; behaviour change: data source + responsive layout)

| Method | URI         | Name       | Controller                      | View             | Must show (from `$company`) |
|--------|-------------|------------|---------------------------------|------------------|-----------------------------|
| GET    | `/`         | `home`     | `PageController@home`           | `pages.home`     | `name` (title, header, footer), `tagline` (h1), `description` |
| GET    | `/about`    | `about`    | `PageController@about`          | `pages.about`    | `name` (h1 "About {name}"), `founded_year`, `mission` |
| GET    | `/projects` | `projects` | `PageController@projects`       | `pages.projects` | `name` (title "Projects - {name}") |
| GET    | `/contact`  | `contact`  | `PageController@contact`        | `pages.contact`  | `email` as `mailto:`, `phone` as `tel:`, `address` |

All four pages return 200 for guests, keep their current wording, and render without errors if
the profile row is missing.

## Authentication (new)

| Method | URI       | Name      | Middleware               | Controller                         | Success                          | Failure |
|--------|-----------|-----------|--------------------------|------------------------------------|----------------------------------|---------|
| GET    | `/login`  | `login`   | `guest`                  | `Auth\LoginController@create`      | 200, view `auth.login`           | signed-in user: redirect to `admin.company.edit` |
| POST   | `/login`  | `login.store` | `guest`              | `Auth\LoginController@store`       | regenerate session, redirect to intended or `admin.company.edit` | back to `/login` with `email` error and old email; after 5 failures per minute (email+IP) the error says how many seconds to wait |
| POST   | `/logout` | `logout`  | `auth`                   | `Auth\LoginController@destroy`     | log out, invalidate session, regenerate token, redirect to `home` | guest: redirect to `login` |

Login form fields: `email` (required, email), `password` (required), `remember` (optional
checkbox).

## Admin: company profile (new)

| Method | URI              | Name                   | Middleware | Controller                                  | Success | Failure |
|--------|------------------|------------------------|------------|---------------------------------------------|---------|---------|
| GET    | `/admin/company` | `admin.company.edit`   | `auth`     | `Admin\CompanyProfileController@edit`       | 200, view `admin.company.edit`, form pre-filled from the stored row | guest: 302 to `login` |
| PUT    | `/admin/company` | `admin.company.update` | `auth`     | `Admin\CompanyProfileController@update`     | update the row, clear cache, redirect to `admin.company.edit` with flash `status` = "Company details saved." | invalid: redirect back with per-field errors and old input, row unchanged; guest: 302 to `login`, row unchanged |

Form fields and rules: `name`, `tagline`, `description`, `founded_year`, `mission`, `email`,
`phone`, `address`. Rules are in [research R6](../research.md#r6-validation-rules-for-the-edit-form).

**Expired session/CSRF token** on any `admin/*`, `login` or `logout` request: instead of the 419
page, redirect to `login` with flash `status` = "Your session expired. Please sign in again.";
nothing is saved.

If the row is missing, `update` creates it. This is the only code path besides the migration
that can insert it.

## Shared view contract

- `$company` (`App\Models\CompanyProfile`) is available in every `layouts.*`, `pages.*`,
  `admin.*` and `auth.*` view through a view composer.
- `layouts.app` provides `@yield('title', $company->name)`, `@stack('meta')` and
  `@yield('content')`.
- The navigation shows inline links at ≥640px and a `<details>` "Menu" disclosure below 640px.
  The active link keeps today's styling.
- A signed-in user sees no extra public navigation; admin pages carry their own "Sign out"
  button.
- `auth.*` and `admin.*` views push `<meta name="robots" content="noindex, nofollow">`.

## Console command (new)

`php artisan app:create-admin` prompts for name, email (must be unique in `users`) and password
(hidden, min 12 characters, entered twice). It creates a `User` and prints a confirmation. Exit
code 0 on success, 1 on validation failure.

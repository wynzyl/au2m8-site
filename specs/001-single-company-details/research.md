# Research: Single Source for Company Details

**Feature**: [spec.md](./spec.md) | **Plan**: [plan.md](./plan.md) | **Date**: 2026-10-19

Every unknown in the plan's Technical Context is resolved here. Laravel facts were checked against
the Laravel 13.x documentation (authentication, validation, cache, Eloquent events).

## R1. Where the company details are stored

- **Decision**: One Eloquent model `CompanyProfile` backed by a `company_profiles` table that holds
  a single row. The migration inserts that row with today's approved values.
- **Rationale**: The maintainer asked for editable, database-backed data. Inserting the row in the
  migration means `php artisan migrate --force` on deploy is enough, and a fresh environment
  never lacks the record. There is no separate seeding step to forget.
- **Alternatives considered**:
  - `config/company.php`: one source, but needs a code change and deploy to edit. Rejected
    because of the input "dynamic and save in a database".
  - Generic key/value `settings` table: flexible, but loses column types and validation per field
    and is speculative for 8 fixed fields (YAGNI).
  - Seeder only: production deploys don't run seeders, so the record could be missing.

## R2. Reading the profile on every page without a query each time

- **Decision**: `CompanyProfile::current()` returns
  `Cache::rememberForever('company_profile', fn () => static::query()->first())`. The model's
  `booted()` method registers `saved` and `deleted` listeners that call
  `Cache::forget('company_profile')`.
- **Rationale**: Chosen by the maintainer. A cache that never expires but is cleared on every save
  gives zero `company_profiles` queries per page view and instant updates (FR-008, SC-006). With
  the `database` cache store, the read is still one indexed lookup on the `cache` table. Eloquent fires `saved`
  even when nothing changed, which is harmless.
- **Implementation note (found during `/speckit-implement`)**: Laravel 13's cache config ships
  `serializable_classes => false`, so a cached model comes back as `__PHP_Incomplete_Class` from
  any serializing store (`database`, `file`, …); the `array` store used in tests hides this. The
  cache therefore holds the model's raw attribute array, and `current()` rebuilds the model with
  `newFromBuilder()`. This keeps the hardening intact (no class allow-list). A regression test
  runs against the `database` store.
- **Caveat**: Mass `CompanyProfile::query()->update(...)` skips model events. The admin update path
  MUST call `save()`/`update()` on the model instance. This is covered by a test.
- **Store**: The existing `CACHE_STORE=database` locally and `array` in tests. No new
  infrastructure.
- **Alternatives considered**: query every request (simpler, but the maintainer chose caching);
  a time-based TTL (would show stale values after a save).

## R3. Missing record fallback

- **Decision**: When `first()` returns `null`, `current()` returns an unsaved
  `new CompanyProfile(['name' => config('app.name')])`. Views print each optional detail only when
  it's filled. A `null` result is not cached, so a later insert is picked up.
- **Rationale**: This covers the "record missing" edge case without errors. `APP_NAME` is changed
  to `Au2m8` in `.env.example` and `.env` so the fallback and the default title match.

- **Home heading (decided during implementation)**: When the tagline is empty, the Home `<h1>`
  shows the company name instead of being hidden as T014 described. Hiding it would leave the
  page without its one `<h1>` (constitution Principle IV). The description paragraph is still
  hidden when empty.

## R4. Making the profile available to views

- **Decision**: A view composer in `AppServiceProvider::boot()` shares `$company`
  (a `CompanyProfile`) with `layouts.*`, `pages.*`, `admin.*` and `auth.*` views.
  `PageController` stops passing `company` arrays. It keeps passing page-specific data
  (`services`, `team`, `projects`).
- **Rationale**: The layout needs the name on every page, so passing it from each controller
  method would duplicate it again. A composer limited to named view groups avoids the
  boot-time query that `View::share` would cause in console commands and tests.
- **View change**: Array access `$company['name']` becomes property access `$company->name`.

## R5. Sign-in for the admin screen

- **Decision**: Hand-written session login with `Auth::attempt()` and
  `$request->session()->regenerate()`, following the "Manually Authenticating Users" docs. A
  `LoginRequest` form request validates input and throttles with `RateLimiter` keyed on
  lowercased email plus IP, allowing 5 attempts and a 60s decay (FR-011). Logout invalidates
  the session and regenerates the CSRF token.
- **Rationale**: The project has no starter kit. Fortify or Breeze would add a dependency and
  scaffolding (registration, reset, profile) that the spec excludes. Two controller actions and
  one form request are smaller, and no new dependency is needed (constitution: dependencies need
  approval).
- **Accounts**: There is no registration route. Owners are created with a new artisan command
  `app:create-admin`, which prompts for name, email and a hidden password using the bundled
  `laravel/prompts`. Users are stored in the existing `users` table and `User` model.
- **Expired sessions**: Laravel turns a CSRF token mismatch into a 419 `HttpException` before
  render callbacks run. A render callback in `bootstrap/app.php` turns a 419 on `admin/*` and
  `login` routes into a redirect to `login` with a "session expired" flash (spec edge case).
- **Authorization**: Every authenticated user is a site owner (spec: no roles). Routes use the
  `auth` middleware, and the login route is named `login` so guests are redirected there.
- **Alternatives considered**: Fortify (new dependency, unused features); HTTP Basic auth (no
  logout, poor on mobile, credentials sent on every request).

## R6. Validation rules for the edit form

- **Decision**: `UpdateCompanyProfileRequest` with these rules:
  - `name`: required, string, max:100
  - `tagline`: required, string, max:150
  - `description`, `mission`, `address`: required, string, max:500
  - `email`: required, email:rfc, max:255
  - `phone`: required, string, max:30, regex:/^[0-9+().\-\s]+$/
  - `founded_year`: required, integer, between 1900 and the current year
  `authorize()` returns `true`, because the `auth` middleware already guards the route.
- **Rationale**: This maps one-to-one to FR-010. Old input is re-shown through `old()`.

## R7. Responsive and mobile navigation

- **Decision**: Below the `sm` breakpoint (640px), the nav links sit inside a native
  `<details>`/`<summary>` disclosure styled as a menu button with an icon, `aria-label`
  "Menu", a 44px tap target and the links stacked full-width. At `sm` and above, the existing
  inline links are shown and the disclosure is hidden.
- **Rationale**: `<details>` works without JavaScript (Principle I) and is keyboard and
  screen-reader accessible by default. It also needs no new script. The desktop look is
  unchanged (spec Story 3, scenario 4).
- **Alternatives considered**: a button with a JS toggle (needs a no-JS fallback and more code);
  a CSS checkbox hack (poor accessibility).

## R8. Mobile layout adjustments

- **Decision**:
  - Page wrappers use `px-4 sm:px-6` and `py-12 sm:py-20`. Headings use
    `text-3xl sm:text-4xl`.
  - The footer stacks (`flex-col gap-2 sm:flex-row`).
  - Long contact values get `break-words`.
  - Email and phone become `mailto:`/`tel:` links. The `tel:` value is the stored phone with
    everything except digits and `+` removed, which is derived and not stored.
  - Project images get explicit `width`/`height`, `loading="lazy"` and `decoding="async"`.
  - Admin forms use full-width inputs with `text-base`, which stops iOS zooming on focus.
- **Verification**: Pest checks the markup (menu present, `tel:`/`mailto:` hrefs). Overflow at
  320/375/768/1280 is checked with Playwright by measuring
  `document.documentElement.scrollWidth <= innerWidth` per [quickstart.md](./quickstart.md).

## R9. Testing approach

- **Decision**: Enable `RefreshDatabase` for feature tests in `tests/Pest.php`. The migration's
  seed row then exists in every test. Add a `CompanyProfileFactory` for tests that need other
  values. Tests are listed in [plan.md](./plan.md#testing-plan).
- **Rationale**: Pages now depend on the database. In-memory SQLite (`phpunit.xml`) keeps the
  suite fast.

## R10. Search-engine exclusion for admin pages

- **Decision**: The layout gets `@stack('meta')`. Admin and login views push
  `<meta name="robots" content="noindex, nofollow">` to it (FR-016).

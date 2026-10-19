# Implementation Plan: Single Source for Company Details

**Branch**: `001-single-company-details` | **Date**: 2026-10-19 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `specs/001-single-company-details/spec.md`, plus planning
input: "I want the company data to be dynamic and save in a database, Site must be responsive and
ready for mobile."

## Summary

Move every company-wide detail (name, tagline, description, founding year, mission, email, phone,
address) into a single-row `company_profiles` table. The migration creates the row with today's
values. The profile is read through a cache that is cleared on save and shared with all views by
a view composer, so `PageController` and the Blade views no longer hard-code it. A
login-protected admin screen at `/admin/company` lets site owners edit the profile. Login is
hand-written session auth with throttling, and owners are created with `app:create-admin`. The
layout and pages also become fully responsive: a `<details>` mobile menu below 640px, stacked
footer, fluid spacing and type, tap-to-call/email links, and lazy-sized images. No new
dependencies.

## Technical Context

**Language/Version**: PHP 8.4 (Herd 8.4.25), Laravel 13.x

**Primary Dependencies**: laravel/framework 13 (Eloquent, Auth, Cache, Validation, RateLimiter,
bundled laravel/prompts), Blade, Tailwind CSS v4 via `@tailwindcss/vite`, Vite 8. No additions.

**Storage**: SQLite (`DB_CONNECTION=sqlite`) for the new `company_profiles` table and the
existing `users` and `sessions` tables. Cache uses the `database` store locally and `array` in
tests.

**Testing**: Pest 5 feature tests with `RefreshDatabase` on in-memory SQLite. Playwright MCP
handles the viewport overflow checks.

**Target Platform**: Web server via Laravel Herd locally. Browsers: current evergreen desktop and
mobile (iOS Safari, Chrome Android).

**Project Type**: Server-rendered web application (single Laravel project).

**Performance Goals**: No query to `company_profiles` per public page view once the cache is
warm. With `CACHE_STORE=database` the cache read is one indexed lookup on the `cache` table,
within SC-006's 10%-of-baseline budget. The edit is visible on the next page load after saving (FR-008).

**Constraints**: Pages work without JavaScript. WCAG 2.1 AA. No horizontal overflow from 320px
wide. Tap targets ≥44px. No new Composer or npm packages.

**Scale/Scope**: 1 profile row, a handful of owner accounts, 4 public pages and 2 admin/auth
screens. Low traffic marketing site.

All items are resolved. See [research.md](./research.md) R1–R10.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle / Rule | Status | Notes |
|---|---|---|
| I. Server-Rendered Simplicity: no DB-backed CMS without spec + maintainer approval | **Justified exception** | The maintainer explicitly requested DB storage and an edit screen (2026-10-19), recorded in spec Assumptions. Scope is limited to one model and one form, not a general CMS. See Complexity Tracking. |
| I. Pages render without JS | Pass | The mobile menu uses native `<details>`. No new JS. |
| I. No speculative abstractions | Pass | No service or repository layers. Uses a model static `current()`, one composer closure and two thin controllers. |
| II. Laravel conventions: named routes, `route()`, `make:*`, typed PHP, shared layout | Pass | Every route is named (see [contracts/routes.md](./contracts/routes.md)). Files come from `make:model -mf`, `make:controller`, `make:request`, `make:command` and `make:test --pest`. |
| III. Every page tested, tests in the same change | Pass | See the Testing Plan below. All 4 public pages, plus login, admin, cache and command tests. |
| IV. WCAG AA, 320px, lazy images, unique titles | Pass (after T039) | Covered by FR-012–FR-016, research R7/R8 and the quickstart Playwright checks. The existing `text-gray-400` labels on white (≈2.5:1), the orange tag chips (≈3.4:1), and `.btn-primary`/`.text-accent` (#F97316, ≈2.8:1) fail 4.5:1 and are fixed in T039 with `text-gray-600` and the new `--color-primary-strong` #C2410C (≈5.2:1). |
| V. Truthful content, single source | Pass | This is the purpose of the feature. Initial values are copied verbatim from the current code. |
| Dependencies need approval | Pass | None added. Fortify and Breeze were rejected (R5). |
| No new top-level directories | Pass | Only `app/Http/Requests`, `app/Http/Controllers/{Auth,Admin}`, `app/Console/Commands` and `resources/views/{auth,admin}`, all under existing base folders. |
| Secrets in `.env` | Pass | No secrets added. Owner passwords are hashed in the DB and never committed. |

**Gate result**: PASS with one justified exception.

**Post-design re-check (after Phase 1)**: PASS. The data model, contracts and quickstart add no
further abstractions, dependencies or directories. The exception stays limited to
`company_profiles` and `/admin/company`.

## Project Structure

### Documentation (this feature)

```text
specs/001-single-company-details/
├── plan.md              # This file
├── research.md          # Phase 0 decisions R1–R10
├── data-model.md        # CompanyProfile + reused User
├── quickstart.md        # Validation guide
├── contracts/
│   └── routes.md        # HTTP routes, view contract, console command
├── checklists/
│   └── requirements.md  # Spec quality checklist
└── tasks.md             # Created by /speckit-tasks
```

### Source Code (repository root)

```text
app/
├── Console/Commands/
│   └── CreateAdmin.php                    # NEW  app:create-admin
├── Http/
│   ├── Controllers/
│   │   ├── PageController.php             # EDIT drop company arrays, keep page data
│   │   ├── Auth/LoginController.php       # NEW  create / store / destroy
│   │   └── Admin/CompanyProfileController.php  # NEW edit / update
│   └── Requests/
│       ├── LoginRequest.php               # NEW  rules + RateLimiter throttle
│       └── UpdateCompanyProfileRequest.php # NEW rules (R6)
├── Models/
│   └── CompanyProfile.php                 # NEW  current(), telephoneHref(), cache busting
└── Providers/
    └── AppServiceProvider.php             # EDIT view composer for $company

database/
├── factories/CompanyProfileFactory.php    # NEW
└── migrations/xxxx_create_company_profiles_table.php  # NEW  table + initial row

resources/views/
├── layouts/app.blade.php                  # EDIT $company->name, @stack('meta'), mobile nav, footer
├── pages/{home,about,projects,contact}.blade.php  # EDIT $company->*, responsive classes, tel/mailto
├── auth/login.blade.php                   # NEW
├── admin/company/edit.blade.php           # NEW
└── components/form/field.blade.php        # NEW  shared labelled input/textarea + error (used by both forms)

routes/web.php                             # EDIT login/logout + admin routes
tests/Pest.php                             # EDIT enable RefreshDatabase for Feature
tests/Feature/
├── PublicPagesTest.php                    # NEW
├── CompanyProfileTest.php                 # NEW  current(), cache, fallback, telephoneHref
├── Admin/CompanyProfileEditTest.php       # NEW
├── Auth/LoginTest.php                     # NEW
└── CreateAdminCommandTest.php             # NEW
.env.example, .env                         # EDIT APP_NAME=Au2m8
```

**Structure Decision**: Single Laravel project, keeping the existing `app/`, `resources/views/`,
`routes/`, `database/` and `tests/` layout. New code goes in conventional Laravel subfolders
created by `make:*` generators.

## Implementation Order

1. **Foundation**: migration with the initial row, the `CompanyProfile` model and factory,
   `RefreshDatabase` in `tests/Pest.php`, and `CompanyProfileTest` (cache, bust on save,
   fallback, `telephoneHref`).
2. **Story 1 (P1)**: view composer; rewrite `PageController` and the four views plus the layout
   to use `$company`; `PublicPagesTest` asserts the content of each page; the grep check (SC-001).
3. **Story 2 (P2)**: routes, `LoginRequest`, `LoginController`, `auth/login` view, `LoginTest`;
   then `UpdateCompanyProfileRequest`, `Admin\CompanyProfileController`, `admin/company/edit`
   view, `CompanyProfileEditTest`; then the `app:create-admin` command and its test.
4. **Story 3 (P2)**: responsive layout (mobile `<details>` nav, footer stack, spacing and type),
   `tel:`/`mailto:` links, image attributes, responsive admin forms; Pest markup assertions; the
   Playwright overflow pass from the quickstart.
5. **Gates**: Pint, the full test suite, `npm run build`, and visual checks at mobile and desktop
   widths.

Stories 2 and 3 are independent of each other once Story 1 is done.

## Testing Plan

| Test file | Covers |
|---|---|
| `CompanyProfileTest` | `current()` returns the migrated row. A second call makes no query (cached). `save()` clears the cache, so the next `current()` sees the new value. The missing row falls back to `config('app.name')` without throwing and isn't cached. `telephoneHref()` gives `tel:+639190959251`. |
| `PublicPagesTest` | Each of the 4 pages returns 200 and shows its fields from the contract. The name appears in the title, header and footer. A name with `&`/`<` is escaped. Pages still render with no profile row. Contact has the `mailto:`/`tel:` hrefs. The layout contains the mobile `<details>` menu with all 4 links. |
| `Auth/LoginTest` | The login page renders with noindex. Valid credentials log in and redirect to the admin. A wrong password leaves you a guest with an error. The 6th attempt within a minute is throttled. Logout logs out. A signed-in user visiting `/login` is redirected. |
| `Admin/CompanyProfileEditTest` | A guest GET/PUT redirects to login and leaves the row unchanged. The owner sees a pre-filled form. A valid PUT updates the row and the public pages show the new value right away. Each FR-010 rule rejects bad input (dataset) and keeps the row unchanged. Saving with no changes succeeds. |
| `CreateAdminCommandTest` | Creates a user with a hashed password. Rejects a duplicate email and a short password. |

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Database-backed, editable content (Principle I) | The maintainer requires the owner to change company details without a code change or deploy | `config/company.php` gives a single source but still needs a developer and a deploy for every edit |
| Authentication + admin screen (Principle I) | Editing must be private (FR-009); the owner isn't a developer | Editing via tinker or a seeder needs shell access. HTTP Basic auth has no logout and poor mobile usability. Fortify/Breeze add a dependency and unused features. |

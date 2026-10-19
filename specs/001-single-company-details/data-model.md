# Data Model: Single Source for Company Details

**Feature**: [spec.md](./spec.md) | **Plan**: [plan.md](./plan.md) | **Date**: 2026-10-19

## CompanyProfile (new)

Table `company_profiles`. It holds exactly one row: the site's public identity.

| Field          | Type                  | Null | Rules (see FR-010)                                   | Initial value |
|----------------|-----------------------|------|------------------------------------------------------|---------------|
| `id`           | bigint, PK            | no   | auto-increment                                      | 1 |
| `name`         | string(100)           | no   | required, max 100                                    | `Au2m8` |
| `tagline`      | string(150)           | no   | required, max 150                                    | `Building the web, streamlining the process.` |
| `description`  | text                  | no   | required, max 500                                    | `We craft modern, performant web applications using the latest technologies.` |
| `founded_year` | unsigned smallint     | no   | required, integer, 1900 to the current year          | `2010` |
| `mission`      | text                  | no   | required, max 500                                    | `To deliver exceptional web solutions that help businesses thrive in the digital age.` |
| `email`        | string(255)           | no   | required, RFC email, max 255                         | `wenzel@au2m8.com` |
| `phone`        | string(30)            | no   | required, max 30, only digits, space, `+ - . ( )`    | `+63 (919) 095-9251` |
| `address`      | string(500)           | no   | required, max 500                                    | `Poblacion, Urdaneta City, Pangasinan, 2428 Philippines` |
| `created_at`   | timestamp             | yes  |                                                      | migration time |
| `updated_at`   | timestamp             | yes  | "last changed" from the spec's Key Entities          | migration time |

**Model**: `App\Models\CompanyProfile`

- Mass-assignable (`#[Fillable]`, matching `User`): all fields except `id` and the timestamps.
- Casts: `founded_year` → `integer`.
- `HasFactory` with `CompanyProfileFactory`, which uses realistic Faker values within the rules.
- `public static function current(): self` returns the row (its attribute array is what's
  cached, rebuilt with `newFromBuilder()`), or the unsaved fallback from
  [research R3](./research.md#r3-missing-record-fallback).
- `public function telephoneHref(): string` returns `tel:` plus the phone stripped to digits and `+`
  (derived, not stored).
- `booted()`: `saved` and `deleted` both forget cache key `company_profile`.

**Cardinality**: one row. The application only ever reads `first()` and updates that instance.
No create or delete paths are exposed. A unique constraint isn't needed because nothing inserts
a second row. The migration is the only writer of new rows.

**State/lifecycle**:

```text
migrate ──insert──▶ [stored] ──owner saves valid form──▶ [stored, updated_at bumped] ──▶ cache cleared
                       │                                              ▲
                       └────────────── page view: read via cache ─────┘
```

**Migration rollback**: `down()` drops the table.

## User (existing, unchanged)

The `users` table and `App\Models\User` are reused as **Site Owner**: name, email, hashed
password. No schema change. Rows are created only by `php artisan app:create-admin`. Every user
is a site owner.

## Page-specific content (unchanged)

`services`, `team` and `projects` stay as arrays in `PageController` and are out of scope.

## Removed duplication

| Value                         | Before (locations)                                                    | After |
|-------------------------------|-----------------------------------------------------------------------|-------|
| Company name                  | `PageController` ×3, `layouts/app.blade.php` ×4, `projects.blade.php` ×1 | `company_profiles.name` |
| Email / phone / address       | `PageController::contact()`                                           | `company_profiles.*` |
| Tagline / description         | `PageController::home()`                                              | `company_profiles.*` |
| Founded / mission             | `PageController::about()`                                             | `company_profiles.*` |

The initial values also appear once in the migration's insert, which FR-005 allows.

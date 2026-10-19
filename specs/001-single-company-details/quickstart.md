# Quickstart: Validate "Single Source for Company Details"

**Feature**: [spec.md](./spec.md) | **Plan**: [plan.md](./plan.md)

Run commands from PowerShell in the repository root. Herd's PHP (8.4.25) is on the PowerShell
path. The plain `php` in Git Bash is 8.4.0 and fails Composer's platform check.

## Prerequisites

- Laravel Herd serving `http://au2ma8-site.test`
- `.env` with `DB_CONNECTION=sqlite`, `CACHE_STORE=database` and `APP_NAME=Au2m8`

## 1. Apply schema and initial data

```powershell
php artisan migrate --no-interaction
php artisan tinker --execute 'dump(App\Models\CompanyProfile::current()->only("name","email","phone"));'
```

Expected: one `company_profiles` row with name `Au2m8`, email `wenzel@au2m8.com` and phone
`+63 (919) 095-9251`. Schema and fields are in [data-model.md](./data-model.md).

## 2. Automated tests

```powershell
php artisan test --compact --filter=PublicPages
php artisan test --compact --filter=CompanyProfile
php artisan test --compact --filter=Login
php artisan test --compact --filter=CreateAdmin
php artisan test --compact
```

Expected: all pass. Together they cover Stories 1–3, FR-001–FR-016 and the edge cases in the
spec.

## 3. No duplicated values (SC-001)

```powershell
git grep -n -e "wenzel@au2m8.com" -e "095-9251" -e "Urdaneta" -e "Au2m8" -- app resources routes config
```

Expected: no matches in `app`, `resources`, `routes` or `config`. The only allowed copies are in
the migration under `database/migrations/` and in `.env*` (`APP_NAME`).

## 4. Owner edits details (Story 2)

```powershell
php artisan app:create-admin
```

1. Open `http://au2ma8-site.test/admin/company` while signed out. Expected: redirected to
   `/login`.
2. Sign in with the account you just created. Expected: the edit form shows the current values.
3. Change the phone to `+63 (900) 000-0000` and save. Expected: "Company details saved."
4. Open `/contact`. Expected: the new phone is shown, and its link is `tel:+639000000000`.
5. Submit a blank name. Expected: an error beside the name field; the other fields keep your
   input; `/contact` is unchanged.
6. Put the original phone back and click "Sign out".
7. On `/login`, enter a wrong password 6 times. Expected: the 6th attempt is refused with a
   wait message.

## 5. Mobile check (Story 3, SC-005)

With the Playwright MCP tools, for each URL (`/`, `/about`, `/projects`, `/contact`, `/login`,
and `/admin/company` while signed in) at widths 320, 375, 768 and 1280:

- `browser_resize` to the width (height 800), then `browser_navigate` to the URL.
- `browser_evaluate`: `() => document.documentElement.scrollWidth <= window.innerWidth`.
  Expected: `true`.
- Below 640px: the "Menu" control is visible, and clicking it reveals all 4 links. At 640px and
  above: the links are inline and there is no menu control.
- `browser_take_screenshot` for visual review against today's desktop layout.

## 6. Build

```powershell
npm run build
vendor/bin/pint --dirty --format agent
```

Expected: the build succeeds and Pint reports nothing left to fix.

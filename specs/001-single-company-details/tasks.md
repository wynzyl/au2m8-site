---

description: "Task list for Single Source for Company Details"
---

# Tasks: Single Source for Company Details

**Input**: Design documents from `specs/001-single-company-details/`

**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md),
[data-model.md](./data-model.md), [contracts/routes.md](./contracts/routes.md),
[quickstart.md](./quickstart.md)

**Tests**: Required. Constitution Principle III (NON-NEGOTIABLE) and FR-007 call for Pest feature
tests. Write each story's tests first and confirm they fail before implementing.

**Organization**: Tasks are grouped by user story so each story can be implemented and tested on
its own.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependency on an incomplete task)
- **[Story]**: US1, US2 or US3 from spec.md
- Paths are relative to the repository root (single Laravel project)

## Conventions for every task

- Run all `php`/`artisan`/`composer` commands from **PowerShell**. Herd PHP 8.4.25 is there;
  Git Bash `php` is 8.4.0 and fails the platform check.
- Always pass `--no-interaction` to `php artisan make:*`.
- PHP: explicit parameter and return types, curly braces on every control structure, PHPDoc
  over inline comments, and model attributes `#[Fillable]`/`#[Hidden]` to match `app/Models/User.php`.
- Links via named routes and `route()`. Blade output is escaped with `{{ }}` and never `{!! !!}`.
- Run the narrowest test after each change: `php artisan test --compact --filter=<Name>`.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Branch and environment ready

- [X] T001 Create and switch to git branch `001-single-company-details` from `main` (`git switch -c 001-single-company-details`). Before changing any code, record the SC-006 baseline: for each of `/`, `/about`, `/projects` and `/contact` on `http://au2ma8-site.test`, run 20 requests (`curl.exe -s -o NUL -w "%{time_total}\n" <url>`, discarding the first as warm-up) and write the median per page to `specs/001-single-company-details/baseline.md`
- [X] T002 [P] Set `APP_NAME=Au2m8` in `.env.example` and `.env` (currently `Laravel`). This value is the fallback name from research R3.
- [X] T003 [P] Enable `RefreshDatabase` for feature tests in `tests/Pest.php` by uncommenting `->use(RefreshDatabase::class)` in the `pest()->extend(TestCase::class)` chain, then run `php artisan test --compact` to confirm the existing example tests still pass

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: The `CompanyProfile` record, its cache and its availability in every view. All three
stories depend on this.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T004 Run `php artisan make:model CompanyProfile --migration --factory --no-interaction` to generate `app/Models/CompanyProfile.php`, `database/migrations/*_create_company_profiles_table.php` and `database/factories/CompanyProfileFactory.php`
- [X] T005 Write the migration in `database/migrations/*_create_company_profiles_table.php`. `up()` creates the table with `id()`; `string('name', 100)`; `string('tagline', 150)`; `text('description')`; `unsignedSmallInteger('founded_year')`; `text('mission')`; `string('email', 255)`; `string('phone', 30)`; `string('address', 500)`; `timestamps()`. All columns are NOT NULL except the timestamps. It then inserts exactly one row with `DB::table('company_profiles')->insert([...])` using these values verbatim: name `Au2m8`, tagline `Building the web, streamlining the process.`, description `We craft modern, performant web applications using the latest technologies.`, founded_year `2010`, mission `To deliver exceptional web solutions that help businesses thrive in the digital age.`, email `wenzel@au2m8.com`, phone `+63 (919) 095-9251`, address `Poblacion, Urdaneta City, Pangasinan, 2428 Philippines`, and `created_at`/`updated_at` = `now()`. `down()` drops the table.
- [X] T006 Implement `app/Models/CompanyProfile.php`:
  - `use HasFactory` with `/** @use HasFactory<CompanyProfileFactory> */`, and `#[Fillable(['name','tagline','description','founded_year','mission','email','phone','address'])]`.
  - `casts(): array` returns `['founded_year' => 'integer']`.
  - Constant `CACHE_KEY = 'company_profile'`.
  - `public static function current(): self`: read `Cache::get(self::CACHE_KEY)`; on a miss, query `static::query()->first()`. If the row is found, `Cache::forever()` it and return it. If not, return the uncached `new static(['name' => config('app.name')])` (research R2/R3: never cache `null`).
  - `public function telephoneHref(): string` returns `'tel:'.preg_replace('/[^0-9+]/', '', (string) $this->phone)`.
  - `protected static function booted(): void` registers `static::saved()` and `static::deleted()` closures that call `Cache::forget(self::CACHE_KEY)`.
  - Give each method a short PHPDoc.
- [X] T007 [P] Implement `database/factories/CompanyProfileFactory.php` `definition()` using `fake()` within the rules: `name` `fake()->company()` (≤100), `tagline` `fake()->catchPhrase()` (≤150), `description` and `mission` `fake()->sentence()` (≤500), `founded_year` `fake()->numberBetween(1900, (int) date('Y'))`, `email` `fake()->companyEmail()`, `phone` `'+63 (917) 555-'.fake()->numerify('####')` (only digits, space, `+ - . ( )`), `address` `fake()->address()` (≤500)
- [X] T008 Register a view composer in `app/Providers/AppServiceProvider.php` `boot()`: `View::composer(['layouts.*', 'pages.*', 'admin.*', 'auth.*'], fn (\Illuminate\View\View $view) => $view->with('company', CompanyProfile::current()))` (research R4; don't use `View::share`)
- [X] T009 Run `php artisan make:test --pest CompanyProfileTest --no-interaction` and write `tests/Feature/CompanyProfileTest.php` covering:
  - (a) `CompanyProfile::current()` returns the migrated row with name `Au2m8` and email `wenzel@au2m8.com`.
  - (b) a second `current()` call runs 0 queries against `company_profiles` (filter `DB::getQueryLog()` to entries whose `query` contains `company_profiles`, so the test holds whatever the cache store; analysis I2).
  - (c) after `$profile->update(['phone' => '+63 (900) 000-0000'])`, `current()->phone` returns the new value.
  - (d) after `CompanyProfile::query()->delete()` plus `Cache::flush()`, `current()` returns an unsaved instance (`exists === false`) whose `name` equals `config('app.name')`, and `Cache::has('company_profile')` is false.
  - (e) `telephoneHref()` on the seeded row returns `tel:+639190959251`.
  - (f) deleting via `$profile->delete()` clears the cache key.
- [X] T010 Run `php artisan migrate --no-interaction` and then `php artisan test --compact --filter=CompanyProfileTest`; all cases pass

**Checkpoint**: The record exists, is cached, and `$company` reaches every view. User stories can begin.

---

## Phase 3: User Story 1 - Visitors see consistent company details everywhere (Priority: P1) 🎯 MVP

**Goal**: Every public page and the layout read company details only from `$company`; the wording is unchanged.

**Independent Test**: `php artisan test --compact --filter=PublicPagesTest` passes, and the quickstart step 3 grep returns no matches in `app resources routes config`.

### Tests for User Story 1 ⚠️

> Write these first and confirm they fail (for example, the escaping case) before implementing.

- [X] T011 [US1] Run `php artisan make:test --pest PublicPagesTest --no-interaction` and write `tests/Feature/PublicPagesTest.php`. Use a dataset over route names `home`, `about`, `projects`, `contact` and assert each returns 200, has `<title>` containing `Au2m8`, and shows `Au2m8` in the header and in the footer `© {current year} Au2m8. All rights reserved.`. Then add per-page tests:
  - `home` shows the tagline `Building the web, streamlining the process.` and the description.
  - `about` shows `About Au2m8`, `Founded in 2010.` and the mission.
  - `projects` has the title `Projects - Au2m8`.
  - `contact` shows `wenzel@au2m8.com`, `+63 (919) 095-9251` and `Poblacion, Urdaneta City, Pangasinan, 2428 Philippines`.
  - After `CompanyProfile::query()->first()->update(['name' => 'Acme & <Co>'])`, `home` shows the escaped `Acme &amp; &lt;Co&gt;` (`assertSee('Acme & <Co>')` with escaping on) and no longer shows `Au2m8`.
  - With the row deleted, every page still returns 200 and shows `config('app.name')`.

### Implementation for User Story 1

- [X] T012 [US1] Edit `app/Http/Controllers/PageController.php`: remove every `'company' => [...]` array from `home()`, `about()` and `contact()`. `home()` passes only `services`, `about()` only `team`, and `projects()` only `projects`. `contact()` returns `view('pages.contact')` with no data. Keep the return types `View`.
- [X] T013 [US1] Edit `resources/views/layouts/app.blade.php`: change `<title>` to `@yield('title', $company->name)`; replace the literal `Au2m8` in the nav brand link, the footer `<span>` and the footer copyright with `{{ $company->name }}`; keep `{{ date('Y') }}`
- [X] T014 [P] [US1] Edit `resources/views/pages/home.blade.php`: change `$company['name']`, `$company['tagline']` and `$company['description']` to `$company->name`, `$company->tagline` and `$company->description`; wrap the tagline `<h1>` and description `<p>` in `@if ($company->tagline)` / `@if ($company->description)` so a missing record renders cleanly
- [X] T015 [P] [US1] Edit `resources/views/pages/about.blade.php`: change array access to `$company->name`, `$company->founded_year` and `$company->mission`; render the `Founded in {{ $company->founded_year }}.` sentence only `@if ($company->founded_year)`
- [X] T016 [P] [US1] Edit `resources/views/pages/projects.blade.php`: change `@section('title', 'Projects - Au2m8')` to `@section('title', 'Projects - '.$company->name)`
- [X] T017 [P] [US1] Edit `resources/views/pages/contact.blade.php`: change `$company['email']`, `$company['phone']` and `$company['address']` to property access, and wrap each of the three blocks in `@if ($company->email)` etc. Plain text only here; the links come in US3.
- [X] T018 [US1] Run `php artisan test --compact --filter=PublicPagesTest` until green, then run the quickstart step 3 check `git grep -n -e "wenzel@au2m8.com" -e "095-9251" -e "Urdaneta" -e "Au2m8" -- app resources routes config` and confirm no output (SC-001)

**Checkpoint**: US1 is complete. The public site is unchanged for visitors, and its data comes from the database.

---

## Phase 4: User Story 2 - Site owner edits company details without a code change (Priority: P2)

**Goal**: Owners sign in at `/login` and edit the profile at `/admin/company`; saves are live on the next page load.

**Independent Test**: `php artisan test --compact --filter="LoginTest|CompanyProfileEditTest|CreateAdminCommandTest"` passes, and quickstart step 4 works manually.

### Tests for User Story 2 ⚠️

- [X] T019 [P] [US2] Run `php artisan make:test --pest Auth/LoginTest --no-interaction` and write `tests/Feature/Auth/LoginTest.php`:
  - GET `route('login')` returns 200 and contains `<meta name="robots" content="noindex, nofollow">`.
  - POST `route('login.store')` with a `User::factory()` user's email and `password` authenticates (`assertAuthenticatedAs`) and redirects to `route('admin.company.edit')`.
  - A wrong password leaves you a guest with a session error on `email` and keeps the old `email` input.
  - After 5 failed attempts, the 6th returns an `email` error containing `seconds` and stays a guest even with the correct password.
  - A signed-in user GET `route('login')` is redirected to `route('admin.company.edit')`.
  - POST `route('logout')` as a user logs out (`assertGuest`) and redirects to `route('home')`.
  - A guest POST `route('logout')` redirects to `route('login')`.
  - GET `/register` returns 404, so there is no public sign-up (FR-009).
  - Session expiry (spec edge case, analysis I1): CSRF checks are skipped in tests, so register a probe route inside the test with `Route::middleware('web')->put('/admin/_expired-probe', fn () => throw new \Illuminate\Session\TokenMismatchException())`. A PUT to it redirects to `route('login')` with session `status` = `Your session expired. Please sign in again.`. A POST to `route('login.store')` that hits the same handler (probe at `/login/_expired-probe`) behaves the same way.
- [X] T020 [P] [US2] Run `php artisan make:test --pest Admin/CompanyProfileEditTest --no-interaction` and write `tests/Feature/Admin/CompanyProfileEditTest.php`:
  - A guest GET and a guest PUT to `route('admin.company.edit')`/`route('admin.company.update')` redirect to `route('login')`, and the row is unchanged.
  - The owner GET returns 200, shows the current values in the inputs (`value="wenzel@au2m8.com"`) and contains the noindex meta.
  - A valid PUT with all 8 fields (phone `+63 (900) 000-0000`) updates the row, redirects to `route('admin.company.edit')` with session `status` = `Company details saved.`, and a following GET `route('contact')` shows `+63 (900) 000-0000`.
  - A PUT with unchanged values succeeds.
  - A dataset of invalid inputs each gets `assertSessionHasErrors(<field>)` with the row unchanged: blank `name`; `name` of 101 chars; `tagline` of 151 chars; `description`/`mission`/`address` of 501 chars; `email` `not-an-email`; `phone` `call me`; `founded_year` `1899`; `founded_year` of the current year + 1; `founded_year` `abc`.
  - With the row deleted, a valid PUT creates exactly one row.
- [X] T021 [P] [US2] Run `php artisan make:test --pest CreateAdminCommandTest --no-interaction` and write `tests/Feature/CreateAdminCommandTest.php` using `$this->artisan('app:create-admin')->expectsQuestion(...)`:
  - Valid name, email and a 12+ char password entered twice exit 0 and create one `User` whose password passes `Hash::check`.
  - A duplicate email exits 1 and creates no user.
  - An 11-char password exits 1.
  - A mismatched confirmation exits 1.

### Implementation for User Story 2

- [X] T022 [US2] Add `@stack('meta')` inside `<head>` of `resources/views/layouts/app.blade.php`, just after the viewport meta (research R10)
- [X] T023 [P] [US2] Run `php artisan make:request LoginRequest --no-interaction` and implement `app/Http/Requests/LoginRequest.php`:
  - `authorize()` returns `true`.
  - `rules()` returns `email` `['required','string','email']` and `password` `['required','string']`.
  - `authenticate(): void` calls `ensureIsNotRateLimited()`. If `Auth::attempt($this->only('email','password'), $this->boolean('remember'))` fails, it calls `RateLimiter::hit($this->throttleKey(), 60)` and throws `ValidationException::withMessages(['email' => __('auth.failed')])`. On success it calls `RateLimiter::clear($this->throttleKey())`.
  - `ensureIsNotRateLimited(): void`: if `RateLimiter::tooManyAttempts($this->throttleKey(), 5)`, throw `ValidationException::withMessages(['email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($this->throttleKey()), ])])`. The framework's `auth.throttle` message uses only `:seconds`.
  - `throttleKey(): string` returns `Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip())`.
- [X] T024 [P] [US2] Run `php artisan make:request UpdateCompanyProfileRequest --no-interaction` and implement `app/Http/Requests/UpdateCompanyProfileRequest.php`:
  - `authorize()` returns `true`, because the route is behind `auth`.
  - `rules()` returns exactly:
    - `name` `['required','string','max:100']`
    - `tagline` `['required','string','max:150']`
    - `description` `['required','string','max:500']`
    - `founded_year` `['required','integer','between:1900,'.now()->year]`
    - `mission` `['required','string','max:500']`
    - `email` `['required','email:rfc','max:255']`
    - `phone` `['required','string','max:30','regex:/^[0-9+().\-\s]+$/']`
    - `address` `['required','string','max:500']`
  - Use an array shape PHPDoc on `rules()`.
- [X] T025 [P] [US2] Run `php artisan make:controller Auth/LoginController --no-interaction` and implement `app/Http/Controllers/Auth/LoginController.php`:
  - `create(): View` returns `view('auth.login')`.
  - `store(LoginRequest $request): RedirectResponse` calls `$request->authenticate()`, then `$request->session()->regenerate()`, then returns `redirect()->intended(route('admin.company.edit'))`.
  - `destroy(Request $request): RedirectResponse` calls `Auth::guard('web')->logout()`, then `$request->session()->invalidate()` and `$request->session()->regenerateToken()`, then `redirect()->route('home')`.
- [X] T026 [P] [US2] Run `php artisan make:controller Admin/CompanyProfileController --no-interaction` and implement `app/Http/Controllers/Admin/CompanyProfileController.php`:
  - `edit(): View` returns `view('admin.company.edit', ['profile' => CompanyProfile::query()->first() ?? new CompanyProfile()])`.
  - `update(UpdateCompanyProfileRequest $request): RedirectResponse` does `$profile = CompanyProfile::query()->first() ?? new CompanyProfile();`, then `$profile->fill($request->validated())->save();`, then returns `redirect()->route('admin.company.edit')->with('status', 'Company details saved.')`.
  - It must call `save()` on the instance, never a mass `query()->update()`, so the `saved` event clears the cache (research R2).
- [X] T027 [US2] Add the routes to `routes/web.php` (after the public routes, importing both controllers):
  - `Route::middleware('guest')` group: `GET /login` → `LoginController@create` named `login`, and `POST /login` → `LoginController@store` named `login.store`.
  - `Route::middleware('auth')` group: `POST /logout` → `LoginController@destroy` named `logout`; `Route::prefix('admin')->name('admin.')` with `GET /company` → `CompanyProfileController@edit` named `company.edit` and `PUT /company` → `CompanyProfileController@update` named `company.update`.
  - Then configure the guest redirect so signed-in users hitting `/login` land on `admin.company.edit`: in `bootstrap/app.php` `->withMiddleware()` add `$middleware->redirectUsersTo(fn () => route('admin.company.edit'));` (check the `redirectUsersTo` API with `search-docs` first).
  - Also in `bootstrap/app.php` `->withExceptions()`, register `$exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) { ... })`. When `$e->getStatusCode() === 419` and `$request->is('admin/*', 'login', 'login/*', 'logout')`, return `redirect()->route('login')->with('status', 'Your session expired. Please sign in again.')`; otherwise return `null`. Laravel converts `TokenMismatchException` into a 419 `HttpException` before render callbacks run, so type-hint `HttpException`, not `TokenMismatchException`. Confirm with `search-docs` "rendering exceptions".
  - Verify with `php artisan route:list --except-vendor`.
- [X] T028 [P] [US2] Create `resources/views/auth/login.blade.php`:
  - `@extends('layouts.app')`, `@section('title', 'Sign in - '.$company->name)`, `@push('meta')` the robots noindex meta.
  - A `max-w-sm mx-auto` card with an `<h1>` "Sign in" and a `POST` form to `route('login.store')` with `@csrf`.
  - Labelled `email` (`type="email"`, `autocomplete="username"`, `value="{{ old('email') }}"`, `required`, `autofocus`) and `password` (`type="password"`, `autocomplete="current-password"`, `required`) inputs, plus a "Remember me" checkbox named `remember`, wrapped in a `<label class="inline-flex min-h-11 items-center gap-2">` so the whole label is a 44px target.
  - Show `session('status')` above the form in a `role="status"` banner (used by the session-expired redirect in T027).
  - Show the `@error('email')` message under the email input with `role="alert"`.
  - A full-width `btn-primary` submit button.
  - Inputs use `w-full text-base min-h-11` and a visible `focus:ring` (44px targets; `text-base` prevents iOS zoom). Error text is `text-red-700`.
  - Wrapper `px-4 sm:px-6 py-12 sm:py-20`.
- [X] T029 [P] [US2] Create `resources/views/admin/company/edit.blade.php`:
  - `@extends('layouts.app')`, `@section('title', 'Company details - '.$company->name)`, `@push('meta')` noindex.
  - A header row with `<h1>` "Company details" and a `POST` `route('logout')` form with `@csrf` and a `btn-secondary` "Sign out" button.
  - A session `status` flash in a `role="status"` banner.
  - A form `POST` to `route('admin.company.update')` with `@csrf @method('PUT')` and labelled fields, each with `value`/content `old('<field>', $profile-><field>)` and an `@error` message (`role="alert"`, `aria-describedby` linking):
    - `name` (text, maxlength 100)
    - `tagline` (text, maxlength 150)
    - `description` (textarea, maxlength 500)
    - `founded_year` (`type="number"`, min 1900, max `{{ now()->year }}`, `inputmode="numeric"`)
    - `mission` (textarea, maxlength 500)
    - `email` (`type="email"`)
    - `phone` (`type="tel"`, maxlength 30)
    - `address` (textarea, maxlength 500)
  - A `btn-primary` "Save changes" button.
  - Single-column layout, `w-full text-base min-h-11` inputs, `sm:max-w-2xl` container, and wrapper `px-4 sm:px-6 py-12 sm:py-20`. The header row is `flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between`, buttons are `w-full sm:w-auto min-h-11`, and error text is `text-red-700`.
- [X] T030 [US2] Run `php artisan make:command CreateAdmin --no-interaction` and implement `app/Console/Commands/CreateAdmin.php`:
  - Signature `app:create-admin`, description `Create a site owner account that can edit company details`.
  - `handle(): int` uses `Laravel\Prompts` `text('Name', required: true)`, `text('Email', required: true)`, `password('Password', required: true)` and `password('Confirm password', required: true)`.
  - It validates with `Validator::make()` on `name` `['required','string','max:255']`, `email` `['required','email','max:255','unique:users,email']` and `password` `['required','string','min:12','confirmed']`. On failure it prints each error with `$this->error()` and returns `self::FAILURE`.
  - Otherwise it runs `User::create([...])` (the `hashed` cast hashes the password), prints `$this->info("Site owner {$email} created.")` and returns `self::SUCCESS`.
- [X] T031 [US2] Run `php artisan test --compact --filter="LoginTest|CompanyProfileEditTest|CreateAdminCommandTest"` until green

**Checkpoint**: US1 and US2 both work. Owners can edit details live, and guests are locked out.

---

## Phase 5: User Story 3 - Visitors on phones get a fully usable site (Priority: P2)

**Goal**: No horizontal overflow from 320px, a collapsible mobile menu without JS, 44px tap targets, and tap-to-call/email.

**Independent Test**: `php artisan test --compact --filter=MobileLayoutTest` passes, and the quickstart step 5 Playwright overflow check returns `true` for every URL × width.

### Tests for User Story 3 ⚠️

- [X] T032 [US3] Run `php artisan make:test --pest MobileLayoutTest --no-interaction` and write `tests/Feature/MobileLayoutTest.php`:
  - For each public route, the response contains a `<details` element containing `<summary` with `aria-label="Menu"`, and the menu panel contains links to all four `route()` URLs.
  - The inline desktop link list carries `hidden sm:flex`, and the `<details>` carries `sm:hidden`.
  - `contact` contains `href="mailto:wenzel@au2m8.com"` and `href="tel:+639190959251"`.
  - `projects` `<img>` tags contain `decoding="async"`, `width=` and `height=`, and every image after the first has `loading="lazy"`.
  - Contact values carry `break-words`.
  - With the profile row deleted (`CompanyProfile::query()->delete()` + `Cache::flush()`), `contact` returns 200 and contains no `href="mailto:` and no `href="tel:` (analysis U2).

### Implementation for User Story 3

- [X] T033 [US3] Rework the nav in `resources/views/layouts/app.blade.php` (research R7):
  - Container `px-4 sm:px-6`.
  - Keep the existing inline link list, now `hidden sm:flex items-center gap-8`, with today's active/inactive classes unchanged. Add `inline-flex min-h-11 items-center` to each desktop link so tablet touch targets are ≥44px (FR-014).
  - Add `<details class="sm:hidden relative group">` with `<summary aria-label="Menu" class="list-none [&::-webkit-details-marker]:hidden inline-flex items-center justify-center min-h-11 min-w-11 rounded-md text-gray-300 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white cursor-pointer">` containing an inline SVG hamburger icon (`aria-hidden="true"`) that swaps to an X via `group-open:`.
  - The panel is `absolute right-0 mt-2 w-56 rounded-lg bg-gray-900 ring-1 ring-white/10 py-2 shadow-lg z-50`, listing the same 4 links stacked as `block px-4 py-3 text-base` with the same active-state logic and `aria-current="page"` on the active link (also add `aria-current` to the desktop links).
  - Avoid duplicating the active-state logic by looping over a `@php $navLinks = [['route' => 'home', 'label' => 'Home'], ['route' => 'about', 'label' => 'About'], ['route' => 'projects', 'label' => 'Projects'], ['route' => 'contact', 'label' => 'Contact Us']]; @endphp` array in both lists.
- [X] T034 [US3] Edit the footer in `resources/views/layouts/app.blade.php`: container `px-4 sm:px-6`, inner row `flex flex-col gap-2 text-center sm:flex-row sm:items-center sm:justify-between sm:text-left`
- [X] T035 [P] [US3] Edit `resources/views/pages/home.blade.php`: wrapper `px-4 sm:px-6 py-12 sm:py-20`, hero `mb-12 sm:mb-20`, `<h1>` `text-3xl sm:text-4xl text-balance`, description `text-base sm:text-lg`
- [X] T036 [P] [US3] Edit `resources/views/pages/about.blade.php`: wrapper `px-4 sm:px-6 py-12 sm:py-20`, section `mb-12 sm:mb-16`, `<h1>` `text-3xl sm:text-4xl`, intro `text-base sm:text-lg`; add `shrink-0` to the avatar circle
- [X] T037 [P] [US3] Edit `resources/views/pages/projects.blade.php`: wrapper `px-4 sm:px-6 py-12 sm:py-20`, `<h1>` `text-3xl sm:text-4xl`; on each `<img>` add `width="1200" height="800" decoding="async"` (matching the `aspect-[3/2]` box), plus `loading="lazy"` on every image except the first (`@unless ($loop->first) loading="lazy" @endunless`), because the first is above the fold; change the card title `<h3>` to `<h2>` so the heading order is h1→h2 (Principle IV), keeping the same classes
- [X] T038 [P] [US3] Edit `resources/views/pages/contact.blade.php`: wrapper `px-4 sm:px-6 py-12 sm:py-20`, `<h1>` `text-3xl sm:text-4xl`. Render the email as `<a href="mailto:{{ $company->email }}" class="font-medium text-accent break-words inline-flex min-h-11 items-center">` and the phone as `<a href="{{ $company->telephoneHref() }}" class="font-medium text-accent inline-flex min-h-11 items-center">`. Add `break-words` to the address `<p>`. **Keep the T017 `@if ($company->email)` / `@if ($company->phone)` / `@if ($company->address)` guards around each block.** `text-accent` becomes accessible in T039.
- [X] T039 [US3] Fix text contrast to ≥4.5:1 (constitution Principle IV, analysis C1). Run after T035–T038, since it edits the same files.
  - In `resources/css/app.css` `@theme`, add `--color-primary-strong: #C2410C;` (white on it ≈5.2:1) and `--color-primary-strong-hover: #9A3412;`. Point `.btn-primary` and `.btn-primary:hover` backgrounds at them, and `.text-accent` / `.text-accent:hover` colours at them. Keep `--color-primary` #F97316 for decorative use only (for example the services `border-orange-500` bar).
  - In `resources/views/pages/home.blade.php`, `about.blade.php` and `contact.blade.php`, replace `text-gray-400` (section headings and contact labels on white, ≈2.5:1) with `text-gray-600` (#4B5563, ≈7.6:1).
  - In `resources/views/pages/projects.blade.php`, change the tag chips from `text-orange-600 bg-orange-50` to `text-orange-800 bg-orange-50`.
  - Leave `text-gray-500` (#6B7280, ≈4.8:1 on white) and the nav/footer `text-gray-400` on `bg-gray-900` (≈6.9:1) unchanged; both already pass.
  - Then add to `tests/Feature/MobileLayoutTest.php`: the `home`, `about` and `contact` responses do not contain `text-gray-400` inside `<main>`. Use `Str::between($response->getContent(), '<main', '</main>')`.
- [X] T040 [US3] Run `php artisan test --compact --filter="MobileLayoutTest|PublicPagesTest"` until green, then `npm run build`

**Checkpoint**: All three stories work on their own and together.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Constitution quality gates and end-to-end validation

- [X] T041 Run the quickstart step 5 Playwright check: for `/`, `/about`, `/projects`, `/contact`, `/login` and `/admin/company` (signed in) at widths 320, 375, 768 and 1280, `browser_evaluate` `() => document.documentElement.scrollWidth <= window.innerWidth` must return `true`. Confirm the Menu toggles below 640px and is hidden at ≥640px. Take screenshots at 375 and 1280 and compare the desktop look with the current site (SC-005, FR-012–FR-014). Contrast check (Principle IV): confirm that no body text on white in `<main>` uses a colour lighter than `text-gray-500`, and that buttons and links use the `--color-primary-strong` tokens from T039.
- [X] T042 [P] Check `browser-logs`/`browser_console_messages` for errors on every page visited in T041
- [X] T043 Run `vendor/bin/pint --dirty --format agent` and fix anything it reports
- [X] T044 Run the full suite `php artisan test --compact`; all tests pass, including the original `tests/Feature/ExampleTest.php` and `tests/Unit/ExampleTest.php`
- [ ] T045 Repeat the T001 timing (warm cache: load each page once first) and confirm each page's median is within 10% of `baseline.md` (SC-006). Then run quickstart.md steps 1–4 end to end (migrate, grep for duplicates, `app:create-admin`, sign in, edit the phone, verify `/contact`, restore the value, throttle check) and tick each expected outcome
- [X] T046 Remove the Sync Impact Report HTML comment from the top of `.specify/memory/constitution.md` if it is still present, then commit in logical groups on branch `001-single-company-details` (foundation, US1, US2, US3, polish)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: none. T002 and T003 are parallel.
- **Foundational (Phase 2)**: depends on Setup. It blocks all stories. T004 → T005 and T006 (same generator output) → T008 → T009 → T010. T007 is parallel with T005 and T006.
- **US1 (Phase 3)**: depends on Foundational.
- **US2 (Phase 4)**: depends on Foundational. It can run alongside US1 except for `layouts/app.blade.php` (T013 vs T022). Do T013 before T022.
- **US3 (Phase 5)**: depends on US1, because it edits the same views after they switch to `$company`. T039 runs after T035–T038.
- **Polish (Phase 6)**: depends on all three stories.

### User Story Dependencies

- **US1 (P1)**: Foundational only. It's the MVP.
- **US2 (P2)**: Foundational only. Once T008 shares the `$company` model, the unconverted views keep working, because Eloquent models support `$company['phone']` array access. That lets T020's `/contact` assertion pass before US1.
- **US3 (P2)**: needs US1's view edits. Independent of US2.

### Same-file sequencing (never parallel)

- `resources/views/layouts/app.blade.php`: T013 → T022 → T033 → T034
- `resources/views/pages/*.blade.php`: each page's US1 task → its US3 task (T014→T035, T015→T036, T016→T037, T017→T038), then T039 (contrast) after all four
- `bootstrap/app.php`: T027 only
- `tests/Feature/MobileLayoutTest.php`: T032 → T039
- `routes/web.php`: T027 only

### Within Each User Story

- Write the tests first and see them fail, then implement, then re-run until green.
- Form requests come before the controllers that type-hint them, and controllers before routes. Views can be done alongside controllers.

---

## Parallel Examples

### Foundational

```text
T005 Write migration in database/migrations/*_create_company_profiles_table.php
T007 Implement database/factories/CompanyProfileFactory.php
```

### User Story 1 (after T012, T013)

```text
T014 resources/views/pages/home.blade.php
T015 resources/views/pages/about.blade.php
T016 resources/views/pages/projects.blade.php
T017 resources/views/pages/contact.blade.php
```

### User Story 2

```text
# Tests together:
T019 tests/Feature/Auth/LoginTest.php
T020 tests/Feature/Admin/CompanyProfileEditTest.php
T021 tests/Feature/CreateAdminCommandTest.php
# Then implementation together:
T023 app/Http/Requests/LoginRequest.php
T024 app/Http/Requests/UpdateCompanyProfileRequest.php
T025 app/Http/Controllers/Auth/LoginController.php
T026 app/Http/Controllers/Admin/CompanyProfileController.php
T028 resources/views/auth/login.blade.php
T029 resources/views/admin/company/edit.blade.php
```

### User Story 3 (after T033, T034)

```text
T035 home.blade.php · T036 about.blade.php · T037 projects.blade.php · T038 contact.blade.php
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phase 1 Setup → Phase 2 Foundational → Phase 3 US1.
2. **Stop and validate**: `PublicPagesTest` is green and the grep is empty. The site looks identical and is now database-driven.
3. This can ship on its own; edits are done through tinker until US2 lands.

### Incremental Delivery

1. Foundation + US1 → MVP (single source, cached).
2. Add US2 → owners edit details in the browser.
3. Add US3 → mobile-ready layout and tap-to-call/email.
4. Polish → constitution gates (Pint, full suite, build, visual check) before merging.

### Single-agent order

T001 → T046 in ID order. Sequential execution respects every dependency listed above.

---

## Notes

- [P] tasks touch different files and have no unfinished dependencies.
- Constraint values (lengths, ranges, regex, the 5/minute throttle) are quoted verbatim from
  [data-model.md](./data-model.md) and [research.md](./research.md) R5/R6. Don't change them
  without amending the spec (FR-010, FR-011).
- After T026, always update the profile through a model instance (`save()`/`update()` on the
  model), never with a mass query update; otherwise the cache goes stale.
- Commit after each phase checkpoint.

---

## Phase 7: Convergence

- [X] T047 CRITICAL: Resize `public/images/Merryland.png` (1790×984) and `public/images/healthcare-dashboard.png` (1875×896) to 816×544 (2× the ≈408×272 `aspect-[3/2]` display box, center-cropped to 3:2) without adding dependencies (for example `System.Drawing` from PowerShell), then change the `<img>` attributes in `resources/views/pages/projects.blade.php` to `width="816" height="544"` per Constitution IV (partial)
- [X] T048 CRITICAL: Re-create `resources/views/auth/login.blade.php` and `resources/views/admin/company/edit.blade.php` with `php artisan make:view auth.login --no-interaction` / `make:view admin.company.edit --no-interaction`, and `resources/views/components/form/field.blade.php` with `php artisan make:component form.field --view --no-interaction`, then restore their current content verbatim (back up first) and re-run `php artisan test --compact` per Constitution II (contradicts)
- [X] T049 CRITICAL: Add explicit return types to the new closures: `: ?\Illuminate\Http\RedirectResponse` on the 419 `$exceptions->render(...)` callback in `bootstrap/app.php`, and `: void` on the three `->group(function () {...})` closures in `routes/web.php`; run `vendor/bin/pint --dirty --format agent` per Constitution II (contradicts)
- [ ] T050 With an owner account created by the user (`php artisan app:create-admin`), sign in through Playwright and run the quickstart step 5 overflow check on `/admin/company` at 320, 375, 768 and 1280 widths, including the menu-open state below 640px and the form with validation errors shown per FR-012, SC-005 (partial)
- [X] T051 Extend `tests/Feature/Admin/CompanyProfileEditTest.php`: add blank-value cases for `tagline`, `description`, `founded_year`, `mission`, `email`, `phone` and `address` to the invalid dataset, and add a test that after a failed save (blank `name`) the edit form re-renders the other submitted values (for example `assertSee('value="+63 (900) 000-0000"', false)` on the follow-up GET) per FR-010, US2/AC4 (partial)
- [X] T052 Record two implementation decisions in the design docs: add `resources/views/components/form/field.blade.php` (shared labelled input/textarea with error display) to the Source Code tree in `plan.md`, and note in `research.md` that the Home `<h1>` falls back to the company name when the tagline is empty (kept so each page has exactly one `<h1>`, Constitution IV) instead of being hidden as T014 states per plan: Source Code structure, T014 (unrequested)

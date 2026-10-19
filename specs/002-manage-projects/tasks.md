---

description: "Task list for Owner-Managed Projects"
---

# Tasks: Owner-Managed Projects

**Input**: Design documents from `specs/002-manage-projects/`

**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md),
[data-model.md](./data-model.md), [contracts/routes.md](./contracts/routes.md),
[quickstart.md](./quickstart.md)

**Tests**: Required. Constitution Principle III (NON-NEGOTIABLE) calls for a Pest feature test for
every route and for every change to a controller or view data. Write each story's tests first and
confirm they fail before implementing.

**Organization**: Tasks are grouped by user story so each story can be implemented and tested on
its own.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependency on an incomplete task)
- **[Story]**: US1, US2 or US3 from spec.md
- Paths are relative to the repository root (single Laravel project)

## Conventions for every task

- Run all `php`/`artisan` commands from **PowerShell** (Herd PHP 8.4.25, with GD JPEG + WebP).
  The Git Bash `php` (Herd Lite 8.4.0) has no GD JPEG/WebP and cannot run these tests (research R4).
- Always pass `--no-interaction` to `php artisan make:*`.
- PHP: explicit parameter and return types, constructor property promotion, curly braces on every
  control structure, PHPDoc over inline comments, `#[Fillable]` attributes as in
  `app/Models/CompanyProfile.php`.
- Links via named routes and `route()`. Blade output is escaped with `{{ }}` and never `{!! !!}`.
- Admin views follow `resources/views/admin/company/edit.blade.php`: `@extends('layouts.app')`,
  `@push('meta')` with `<meta name="robots" content="noindex, nofollow">`, container
  `max-w-4xl mx-auto px-4 sm:px-6 py-12 sm:py-20`, buttons `btn-primary`/`btn-secondary` with
  `min-h-11`, flash in `<p role="status" class="mb-6 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800">`.
- Run the narrowest test after each change: `php artisan test --compact --filter=<Name>`.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Branch, storage link and performance baseline

- [X] T001 Confirm the current branch is `002-manage-projects` (`git branch --show-current`). Before changing any code, record the SC-008 baseline: run 21 requests to the Herd URL of `/projects` (get it from Boost `get-absolute-url`) with `curl.exe -s -o NUL -w "%{time_total}\n" <url>`, discard the first, and write the median to `specs/002-manage-projects/baseline.md`
- [X] T002 Run `php artisan storage:link` so `public/storage` points at `storage/app/public` (research R3). Confirm `public/storage` is ignored by git (`git check-ignore public/storage`); if not, add `/public/storage` to `.gitignore`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: The WebP encoder, the `projects` table with its initial rows, and the `Project`
model. All three stories depend on this.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

### Encoder

- [X] T003 Run `php artisan make:test --pest WebpEncoderTest --no-interaction` and write `tests/Feature/WebpEncoderTest.php` (research R4, R15). Use a helper that writes GD-generated images to a temp file (`tempnam(sys_get_temp_dir(), 'img')`):
  - (a) a 300×200 JPEG (`imagejpeg`) → `encode()` returns bytes where `substr($bytes, 0, 4) === 'RIFF'` and `substr($bytes, 8, 4) === 'WEBP'`, and `getimagesizefromstring($bytes)` gives 300×200.
  - (b) a 300×200 PNG with a fully transparent pixel at (0,0) (`imagesavealpha`, `imagecolorallocatealpha(..., 127)`) → the WebP decoded with `imagecreatefromstring` has alpha 127 at (0,0) (`imagecolorsforindex(imagecolorat(...))['alpha']`).
  - (c) a 300×200 JPEG with an EXIF Orientation of 6, made by inserting this APP1 segment right after the 2-byte SOI marker: `"\xFF\xE1".pack('n', 2 + strlen($exif)).$exif` where `$exif = "Exif\x00\x00MM\x00\x2A\x00\x00\x00\x08\x00\x01\x01\x12\x00\x03\x00\x00\x00\x01\x00\x06\x00\x00\x00\x00\x00\x00"` → output is 200×300.
  - (d) a file containing `not an image` → throws `RuntimeException`.
  - (e) scaling down: a 3264×1000 PNG comes out 1632×500 and a 1000×3264 PNG comes out 500×1632; a 1632×1632 PNG is unchanged; an 816×544 PNG is not enlarged.
  - (f) scaling happens after EXIF rotation: the case (c) JPEG made 3264×1000 with Orientation 6 comes out 500×1632.
- [X] T004 Run `php artisan make:class Support/WebpEncoder --no-interaction` and implement `app/Support/WebpEncoder.php` (research R4): `public function encode(string $path): string`.
  - Read the file. Call `imagecreatefromstring()`; if it returns `false`, throw `RuntimeException('The image could not be read.')`.
  - When `exif_imagetype($path) === IMAGETYPE_JPEG`, read `@exif_read_data($path)['Orientation'] ?? 1` and apply: 2 → `imageflip(IMG_FLIP_HORIZONTAL)`; 3 → `imagerotate(180)`; 4 → `imageflip(IMG_FLIP_VERTICAL)`; 5 → `imagerotate(-90)` then flip horizontal; 6 → `imagerotate(-90)`; 7 → `imagerotate(90)` then flip horizontal; 8 → `imagerotate(90)`. Use a transparent background colour for rotation.
  - Call `imagepalettetotruecolor()`, `imagealphablending($image, false)` and `imagesavealpha($image, true)`.
  - Capture `imagewebp($image, null, 82)` with `ob_start()`/`ob_get_clean()`. If it fails or returns an empty string, throw `RuntimeException('The image could not be converted to WebP.')`.
  - After the alpha settings, if `max(imagesx, imagesy) > self::MAX_SIDE` (a `public const MAX_SIDE = 1632` with a PHPDoc saying it is twice the 816px display frame), compute `$scale = self::MAX_SIDE / max($width, $height)` and call `imagescale($image, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)), IMG_BICUBIC)`. Throw `RuntimeException` if that returns `false`, and re-apply `imagealphablending(false)` and `imagesavealpha(true)` to the result. Never enlarge (research R4).
  - Free the images with `unset`. Add a PHPDoc on the class and the method.
- [X] T005 Run `php artisan test --compact --filter=WebpEncoderTest`; all cases pass
- [X] T006 Convert the seed images (research R2): create `database/data/projects/` and run `php artisan tinker --execute '$e = new App\Support\WebpEncoder; file_put_contents(database_path("data/projects/merryland.webp"), $e->encode(public_path("images/Merryland.png"))); file_put_contents(database_path("data/projects/healthcare-dashboard.webp"), $e->encode(public_path("images/healthcare-dashboard.png")));'`. Check that both files are 816×544 WebP, then delete `public/images/Merryland.png` and `public/images/healthcare-dashboard.png` (`git rm`)

### Table, model, factory

- [X] T007 Run `php artisan make:model Project --migration --factory --no-interaction` to generate `app/Models/Project.php`, `database/migrations/*_create_projects_table.php` and `database/factories/ProjectFactory.php`
- [X] T008 Write `database/migrations/*_create_projects_table.php` (data-model.md):
  - `up()` creates `projects` with `id()`; `string('title', 100)`; `text('description')`; `string('image_path', 255)`; `json('tags')`; `boolean('is_published')->default(true)`; `unsignedSmallInteger('sort_order')->default(0)`; `timestamps()`; and `index(['is_published', 'sort_order', 'id'])`. All columns are NOT NULL except the timestamps.
  - It then inserts with `DB::table('projects')->insert([...])`, verbatim:
    1. `sort_order` 1, title `School Management System`, description `Complete student information system with enrollment, grades, billing, and reporting.`, `image_path` `projects/merryland.webp`, tags `json_encode(['NextJS', 'React', 'PostgreSQL'])`, `is_published` true.
    2. `sort_order` 2, title `Healthcare Management System`, description `Real-time patient monitoring system with analytics and reporting.`, `image_path` `projects/healthcare-dashboard.webp`, tags `json_encode(['Laravel', 'Livewire', 'PostgreSQL'])`, `is_published` true.
    3. Both rows get `created_at`/`updated_at` = `now()`.
  - Finally, for each of the two files, if `! Storage::disk('public')->exists("projects/{$name}")`, `put` it from `file_get_contents(database_path("data/projects/{$name}"))`.
  - `down()` drops the table and leaves the files alone.
- [X] T009 Implement `app/Models/Project.php` (data-model.md, research R7–R12):
  - `use HasFactory` with `/** @use HasFactory<ProjectFactory> */`, and `#[Fillable(['title', 'description', 'image_path', 'tags', 'is_published', 'sort_order'])]`.
  - `casts(): array` returns `['tags' => 'array', 'is_published' => 'boolean', 'sort_order' => 'integer']`.
  - `#[Scope] protected function published(Builder $query): void` adds `where('is_published', true)`, and `#[Scope] protected function ordered(Builder $query): void` adds `orderBy('sort_order')->orderBy('id')`. Check the Laravel 13 scope syntax with `search-docs` and match it.
  - `public function imageUrl(): ?string` returns `Storage::disk('public')->exists($this->image_path) ? Storage::disk('public')->url($this->image_path) : null`.
  - `public static function parseTags(?string $input): array` splits on `,`, `trim`s, drops `''`, removes later duplicates compared with `mb_strtolower`, keeps entry order and first spelling, and returns a list (`array_values`).
  - `public static function tagSuggestions(): array` takes the distinct tags across **all** projects (published or not), compared case-insensitively with the first spelling kept, sorted with `natcasesort`, as a list.
  - `public static function nextSortOrder(): int` returns `min((int) static::query()->max('sort_order') + 1, 999)`, or `1` when the table is empty.
  - `protected static function booted(): void` registers `static::deleted(fn (Project $project) => Storage::disk('public')->delete($project->image_path))`.
  - Give each method a short PHPDoc, with `@return list<string>` on the tag methods.
- [X] T010 [P] Implement `database/factories/ProjectFactory.php`:
  - `definition()`: `title` `Str::limit(fake()->catchPhrase(), 100, '')`, `description` `fake()->sentence()`, `image_path` `'projects/'.Str::random(40).'.webp'`, `tags` `fake()->randomElements(['Laravel', 'Livewire', 'PostgreSQL', 'React', 'NextJS', 'Tailwind CSS', 'Vue'], fake()->numberBetween(1, 3))`, `is_published` `true`, `sort_order` `fake()->numberBetween(3, 100)`.
  - Add a state `unpublished()` that sets `is_published` to `false`.
- [X] T011 Run `php artisan make:test --pest ProjectTest --no-interaction` and write `tests/Feature/ProjectTest.php`. Use `Storage::fake('public')` in `beforeEach` for the file cases. Cover:
  - (a) the migration creates exactly 2 rows; `Project::query()->ordered()->pluck('title')` is `['School Management System', 'Healthcare Management System']`, with the exact descriptions and tags from T008.
  - (b) `published()->ordered()` leaves out an `unpublished()` project, and two projects with equal `sort_order` come back in `id` order.
  - (c) `parseTags(' laravel, Tailwind, Laravel ,, ')` returns `['laravel', 'Tailwind']`, and `parseTags(null)` and `parseTags('')` return `[]`.
  - (d) `tagSuggestions()` includes tags from an unpublished project, lists `PostgreSQL` once, and no longer includes a tag once the only project using it is deleted.
  - (e) `nextSortOrder()` is 3 with the seed rows, 999 when a row has 999, and 1 after `Project::query()->delete()`.
  - (f) `imageUrl()` returns `null` for a missing file and a URL ending in the path after `Storage::disk('public')->put($path, 'x')`.
  - (g) `$project->delete()` removes its file from the fake disk.
- [X] T012 Run `php artisan migrate --no-interaction` (local DB), check `storage/app/public/projects/` holds the two WebP files, then run `php artisan test --compact --filter=ProjectTest`; all cases pass

**Checkpoint**: The table, initial rows, images and model are in place. User stories can begin.

---

## Phase 3: User Story 1 - Visitors see the projects the owner has published (Priority: P1) 🎯 MVP

**Goal**: `/projects` shows the stored, published projects in order, looking exactly as it does
today. There is an empty state and a placeholder for missing images.

**Independent Test**: With only the migrated data, `/projects` shows both projects with identical
wording, images and tags. Hidden projects are absent and the empty state appears with none
published.

### Tests for User Story 1 ⚠️

> Write these first and confirm they FAIL before implementation.

- [X] T013 [P] [US1] Add tests to `tests/Feature/PublicPagesTest.php` for the projects page, with `Storage::fake('public')` and `put` of `projects/merryland.webp` and `projects/healthcare-dashboard.webp` (content `'x'`) inside each test. Cover:
  - (a) `/projects` shows both titles in order (`assertSeeInOrder`), both exact descriptions, and every tag from T008, with `<img` `src` values containing `/storage/projects/merryland.webp` and `/storage/projects/healthcare-dashboard.webp`.
  - (b) a factory project with `unpublished()` and title `Secret Build` is not shown.
  - (c) after `Project::query()->update(['is_published' => false])`, the page still shows `Our Projects` and `New projects will be added soon.`, with no `<img`.
  - (d) a published project titled `R&D <Lab>` is shown escaped (`assertSee('R&amp;D &lt;Lab&gt;', false)`).
  - (e) when a project's file is missing, the page renders with one fewer `<img` and a placeholder element with `data-image-placeholder`.
- [X] T014 [P] [US1] Update `tests/Feature/MobileLayoutTest.php` `project images are sized and lazy loaded below the fold`: add `Storage::fake('public')` and `put` both seed files at the start, and also assert each `<img>` has an `alt` equal to its project title (`alt="School Management System"`, `alt="Healthcare Management System"`)

### Implementation for User Story 1

- [X] T015 [US1] Change `PageController::projects()` in `app/Http/Controllers/PageController.php` to `return view('pages.projects', ['projects' => Project::query()->published()->ordered()->get()]);` and delete the hard-coded projects array (research R13, FR-004)
- [X] T016 [US1] Update `resources/views/pages/projects.blade.php` (contracts/routes.md):
  - Use `$project->title`, `$project->description`, `$project->tags` and `$project->imageUrl()` instead of array keys.
  - When `imageUrl()` is `null`, render `<div data-image-placeholder aria-hidden="true" class="aspect-3/2 rounded-lg mb-4 bg-gray-100"></div>` in place of the image frame.
  - Keep the `<img>` attributes as they are: `alt` = title, `width="816"` `height="544"`, `decoding="async"`, and lazy loading unless `$loop->first`.
  - Show the tag row only when `$project->tags` is not empty.
  - Add `wrap-break-word` to the title, description and tags so long values wrap.
  - Wrap the grid in `@forelse`; the `@empty` branch renders `<p class="text-base text-gray-600">New projects will be added soon.</p>`.
- [X] T017 [US1] Run `php artisan test --compact --filter="PublicPagesTest|MobileLayoutTest"`; all pass. Then run the SC-003 grep from quickstart §3; it must return nothing

**Checkpoint**: The public page is fully data-driven and unchanged for visitors. This is the MVP.

---

## Phase 4: User Story 2 - Owner adds, edits and removes projects (Priority: P1)

**Goal**: Signed-in owners list, add, edit (including replacing the image) and delete projects
at `/admin/projects`, with validation, a delete confirmation and admin navigation.

**Independent Test**: Sign in, add a project with an image and two tags, and see it on
`/projects`. Edit its title and see the change. Delete it after confirming, and it is gone along
with its image file.

### Tests for User Story 2 ⚠️

> Write these first and confirm they FAIL before implementation.

- [X] T018 [P] [US2] Run `php artisan make:test --pest Admin/ProjectManagementTest --no-interaction` and write `tests/Feature/Admin/ProjectManagementTest.php`. Use `Storage::fake('public')` in `beforeEach` and a helper `validProjectInput(array $overrides = []): array` returning `title` `New Portal`, `description` `Customer portal for invoices.`, `image` `UploadedFile::fake()->image('shot.jpg', 800, 533)`, `tags` `Laravel, Tailwind CSS`, `is_published` `'1'`, `sort_order` `3`. Cover:
  - (a) Guests: a dataset of every admin project route (index, create, store, edit, update, delete, destroy, using the first seeded project) redirects to `login`, and the row count stays 2.
  - (b) The index shows both seeded titles and an unpublished factory project marked `Hidden`, with a `noindex` meta.
  - (c) Create form: it contains `value="3"` for `sort_order` and a checked `is_published`, plus a `datalist` with an `option` for `PostgreSQL` and the hint text `Tags already in use:`.
  - (d) Store: it redirects to `admin.projects.index` with `status` `Project “New Portal” added.`; the row has tags `['Laravel', 'Tailwind CSS']`, an `image_path` matching `/^projects\/[A-Za-z0-9]{40}\.webp$/`, and a file that exists on the fake disk whose first 4 bytes are `RIFF`; and `/projects` then shows `New Portal`. Storing with `UploadedFile::fake()->image('photo.jpg', 4000, 3000)` saves a file that `getimagesizefromstring` reports as 1632×1224.
  - (e) Update of `School Management System` without an image: the description changes, `image_path` is unchanged and the file still exists, with `status` `Project “School Management System” updated.`
  - (f) Update with a new image: `image_path` changes, the new file exists and the old file is missing.
  - (g) Delete page: it shows `Delete “School Management System”?` and `This cannot be undone.`
  - (h) Destroy: the row is gone, the file is missing, `status` is `Project “School Management System” deleted.`, and `/projects` no longer shows it.
  - (i) Missing record: for the edit, update, delete and destroy routes with id `999`, each redirects to `admin.projects.index` with `status` `That project no longer exists.`
  - (j) Validation dataset (store unless noted), each giving `assertSessionHasErrors($field)`, unchanged row count and `Storage::disk('public')->allFiles('projects')` empty:
    - `title` blank or 101 chars;
    - `description` blank or 501 chars;
    - `image` missing, as `UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')`, as `UploadedFile::fake()->image('big.jpg')->size(2049)`, or as `UploadedFile::fake()->image('wide.png', 4097, 10)`;
    - `tags` with 11 comma-separated tags, or a 31-char tag;
    - `sort_order` `-1`, `1000` or `abc`;
    - and on update: blank `title` leaves the row unchanged.
  - (k) Error messages: the over-2 MB case has the `image` error `The image must be 2 MB or less.` and the PDF case has `The image must be a JPEG, PNG or WebP file.`
  - (l) Encoder failure: bind a `WebpEncoder` mock that throws `RuntimeException` (`$this->mock(WebpEncoder::class)`); store gives an `image` error and no row.
  - (m) Post too large: `$this->withServerVariables(['CONTENT_LENGTH' => (string) (9 * 1024 * 1024)])->from(route('admin.projects.create'))->post(route('admin.projects.store'))` redirects to the create page with the `image` error `The image must be 2 MB or less.`
  - (n) Robots meta: every admin project view (index, create, edit, delete) includes `<meta name="robots" content="noindex, nofollow">`.
- [X] T019 [P] [US2] Run `php artisan make:test --pest Admin/AdminNavigationTest --no-interaction` and write `tests/Feature/Admin/AdminNavigationTest.php`. Signed in, extract the `<nav aria-label="Admin"` … `</nav>` block from the response:
  - On `admin.company.edit` it contains links to `admin.company.edit` and `admin.projects.index`, a `logout` form, and `aria-current="page"` on the Company details link only.
  - On `admin.projects.index` and `admin.projects.create`, `aria-current="page"` is on the Projects link only.

### Implementation for User Story 2

- [X] T020 [US2] Run `php artisan make:request StoreProjectRequest --no-interaction` and implement `app/Http/Requests/StoreProjectRequest.php` (research R5):
  - `authorize()` returns `true`, with the same PHPDoc as `UpdateCompanyProfileRequest`.
  - `rules()`:
    - `title` `['required', 'string', 'max:100']`
    - `description` `['required', 'string', 'max:500']`
    - `image` `['required', ...$this->imageRules()]`
    - `tags` `['nullable', 'string', 'max:1000', <closure>]`. The closure parses with `Project::parseTags($value)` and fails with `Add no more than 10 tags.` when there are more than 10, or `Each tag must be 30 characters or fewer.` when any `mb_strlen` > 30.
    - `is_published` `['boolean']`
    - `sort_order` `['required', 'integer', 'between:0,999']`
  - `protected function imageRules(): array` returns `[File::image()->types(['jpeg', 'png', 'webp'])->max(2048)->dimensions(Rule::dimensions()->maxWidth(4096)->maxHeight(4096))]`.
  - `messages()` maps `image.uploaded` and `image.max` → `The image must be 2 MB or less.`; `image.image`, `image.mimes` and `image.mimetypes` → `The image must be a JPEG, PNG or WebP file.`; `image.dimensions` → `The image must be no larger than 4096 pixels on either side.`
  - Use a `@return array<string, mixed>` PHPDoc on `rules()`.
- [X] T021 [US2] Run `php artisan make:request UpdateProjectRequest --no-interaction` and make `app/Http/Requests/UpdateProjectRequest.php` extend `StoreProjectRequest`, overriding `rules()` to `array_merge(parent::rules(), ['image' => ['nullable', ...$this->imageRules()]])`
- [X] T022 [US2] Run `php artisan make:controller Admin/ProjectController --resource --model=Project --no-interaction`. Remove `show`, and implement `app/Http/Controllers/Admin/ProjectController.php` with `public function __construct(private WebpEncoder $encoder) {}` (contracts/routes.md, research R10/R11):
  - `index(): View` renders `admin.projects.index` with `projects` = `Project::query()->ordered()->get()`.
  - `create(): View` renders `admin.projects.form` with `project` = `new Project(['is_published' => true, 'sort_order' => Project::nextSortOrder(), 'tags' => []])` and `tagSuggestions` = `Project::tagSuggestions()`.
  - `store(StoreProjectRequest $request): RedirectResponse`: call `$path = $this->storeImage($request->file('image'))`, then `Project::create([...validated title/description/sort_order, 'tags' => Project::parseTags($request->validated('tags')), 'is_published' => $request->boolean('is_published'), 'image_path' => $path])`. If the create throws, delete `$path` and rethrow. Redirect to the index with `status` `Project “{title}” added.`
  - `edit(Project $project): View` renders the same form with `tagSuggestions`.
  - `update(UpdateProjectRequest $request, Project $project): RedirectResponse`:
    1. When an image is present, `$newPath = $this->storeImage(...)`.
    2. `update()` the same fields, plus `image_path` when it was replaced.
    3. After a successful update, delete the old path.
    4. Redirect with `Project “{title}” updated.`
  - `delete(Project $project): View` renders `admin.projects.delete`.
  - `destroy(Project $project): RedirectResponse`: `$project->delete()` (the model listener removes the file), then redirect with `Project “{title}” deleted.`
  - `private function storeImage(UploadedFile $file): string`: encode `$file->getRealPath()`. On `RuntimeException`, throw `ValidationException::withMessages(['image' => 'This image could not be processed. Try saving it again as a JPEG or PNG.'])`. Otherwise `put` to `projects/`.`Str::random(40)`.`.webp` on the `public` disk and return the path.
  - Give each method a PHPDoc.
- [X] T023 [US2] Add routes in `routes/web.php` inside the existing `prefix('admin')->name('admin.')` group, sharing one `$projectMissing = fn (): RedirectResponse => redirect()->route('admin.projects.index')->with('status', 'That project no longer exists.');` closure:
  - `Route::resource('projects', ProjectController::class)->except('show')->missing($projectMissing);`
  - `Route::get('/projects/{project}/delete', [ProjectController::class, 'delete'])->name('projects.delete')->missing($projectMissing);`
  - Verify with `php artisan route:list --path=admin`.
- [X] T024 [US2] Render `PostTooLargeException` in `bootstrap/app.php` (research R6): add an `$exceptions->render(function (PostTooLargeException $exception, Request $request): ?RedirectResponse { ... })` that, for `$request->is('admin/*')`, returns `back()->withErrors(['image' => 'The image must be 2 MB or less.'])` and otherwise returns `null`
- [X] T025 [P] [US2] Extend `resources/views/components/form/field.blade.php` with an optional `hint` prop. When set, render `<p id="{{ $inputId }}-hint" class="mt-1 text-sm text-gray-600">{{ $hint }}</p>` below the input and include `{{ $inputId }}-hint` in `aria-describedby`, together with the error id when there is an error. Existing callers must render unchanged
- [X] T026 [P] [US2] Run `php artisan make:component admin.nav --view --no-interaction` and write `resources/views/components/admin/nav.blade.php` (research R14):
  - `<nav aria-label="Admin" class="mb-8 flex flex-wrap items-center gap-2 border-b border-gray-200 pb-4">` contains links "Company details" (`route('admin.company.edit')`, active when `request()->routeIs('admin.company.*')`) and "Projects" (`route('admin.projects.index')`, active when `request()->routeIs('admin.projects.*')`).
  - The active link gets `aria-current="page"` and `font-medium text-gray-900`; the others get `text-gray-600 hover:text-gray-900`.
  - Every link is `inline-flex min-h-11 items-center px-3 rounded-md` with a visible focus outline.
  - Then `<form method="POST" action="{{ route('logout') }}" class="sm:ml-auto">` with `@csrf` and a `btn-secondary min-h-11` "Sign out" button.
- [X] T027 [US2] In `resources/views/admin/company/edit.blade.php`, replace the sign-out `<form>` in the heading row with `<x-admin.nav />` placed above the `h1`, so the heading row keeps only the `h1`. Re-run `php artisan test --compact --filter=CompanyProfileEditTest` to confirm nothing regressed
- [X] T028 [P] [US2] Run `php artisan make:view admin.projects.index --no-interaction` (constitution §II) and write `resources/views/admin/projects/index.blade.php`:
  - `@section('title', 'Projects - ' . $company->name)`, the noindex meta, `<x-admin.nav />`, and a heading row with `h1` "Projects" and an `a.btn-primary.min-h-11` "Add project" link to `admin.projects.create`. Show the flash `status` if present.
  - A `<ul class="divide-y divide-gray-200">` with one `<li class="flex flex-wrap items-center gap-4 py-4">` per project:
    - a 96×64 thumbnail (`<img src="{{ $project->imageUrl() }}" alt="" width="96" height="64" class="h-16 w-24 rounded object-cover">`, or a `bg-gray-100` block of the same size when `imageUrl()` is null);
    - a `min-w-0 flex-1` block with the title (`font-medium wrap-break-word`) and a line `Published`/`Hidden` · `Order {{ $project->sort_order }}` in `text-sm text-gray-600`;
    - `Edit` and `Delete` links (`btn-secondary min-h-11`) to `admin.projects.edit` and `admin.projects.delete`, each with an `aria-label` that includes the title.
  - When there are no projects, show `<p>No projects yet.</p>`.
- [X] T029 [P] [US2] Run `php artisan make:view admin.projects.form --no-interaction` and write `resources/views/admin/projects/form.blade.php` for both create and edit (`$project->exists` decides):
  - Title: `Add project - {name}` or `Edit project - {name}`; noindex meta; `<x-admin.nav />`; `h1` "Add project" or "Edit project".
  - `<form method="POST" enctype="multipart/form-data" class="grid gap-6">` with action `admin.projects.store`, or `admin.projects.update` plus `@method('PUT')`, and `@csrf`.
  - `x-form.field` for `title` (maxlength 100, required) and `description` (multiline, maxlength 500, required).
  - An image block written inline in the same markup style as `x-form.field`:
    - label "Image";
    - `<input type="file" name="image" id="field-image" accept="image/jpeg,image/png,image/webp">`, `required` only when creating;
    - hint `JPEG, PNG or WebP, up to 2 MB. Large images are scaled down automatically.` (plus ` Leave empty to keep the current image.` when editing);
    - on edit, the current image as a 192×128 thumbnail with `alt="Current image"`;
    - `aria-invalid`, `aria-describedby` and `@error('image')` as in the component.
  - `x-form.field` for `tags` with value `implode(', ', $project->tags ?? [])` (the component's `old()` makes old input win), `list="tag-suggestions"`, `autocomplete="off"`, `:data-tag-suggestions="json_encode($tagSuggestions)"`, and `hint` `Separate tags with commas.` + (when not empty) ` Tags already in use: ` . `implode(', ', $tagSuggestions)` . `.`
  - `<datalist id="tag-suggestions">` with one `<option value="{{ $tag }}">` per suggestion.
  - A checkbox `is_published` (value `1`, label "Published (shown on the Projects page)", `min-h-11` label hit area), checked when `$errors->any() ? old('is_published') : $project->is_published`.
  - `x-form.field` for `sort_order` (type number, min 0, max 999, `inputmode="numeric"`, required, hint `Lower numbers appear first.`).
  - Actions: `btn-primary min-h-11` "Add project" or "Save changes", and `a.btn-secondary.min-h-11` "Cancel" to the index.
- [X] T030 [P] [US2] Run `php artisan make:view admin.projects.delete --no-interaction` and write `resources/views/admin/projects/delete.blade.php`: title `Delete project - {name}`, noindex meta, `<x-admin.nav />`, `h1` "Delete project", `<p class="mb-6 wrap-break-word">Delete “{{ $project->title }}”? This cannot be undone.</p>`, and a `<form method="POST" action="{{ route('admin.projects.destroy', $project) }}">` with `@csrf @method('DELETE')`, a `btn-primary min-h-11` "Delete project" button and an `a.btn-secondary.min-h-11` "Cancel" link to the index, stacked on mobile (`flex flex-col gap-3 sm:flex-row`)
- [X] T031 [US2] Run `php artisan test --compact --filter="ProjectManagementTest|AdminNavigationTest|CompanyProfileEditTest"`; all pass

**Checkpoint**: Owners can fully manage projects. Stories 1 and 2 both work.

---

## Phase 5: User Story 3 - Owner controls order and visibility (Priority: P2)

**Goal**: The display order and "Published" setting fully control the public page, and tag
suggestions work while typing.

**Independent Test**: Setting a project's order to 0 moves it to the top of `/projects`. Unticking
"Published" hides it publicly but keeps it in the admin list as Hidden.

### Tests for User Story 3 ⚠️

- [X] T032 [P] [US3] Add to `tests/Feature/Admin/ProjectManagementTest.php`:
  - (a) Updating `Healthcare Management System` with `sort_order` 0 (other fields unchanged) makes `/projects` list it first (`assertSeeInOrder`).
  - (b) Updating it without `is_published` in the payload sets `is_published` false; `/projects` no longer shows it, and the index shows it with `Hidden`.
  - (c) Storing with `sort_order` 2 (a tie with the seeded project 2) lists the new project after `Healthcare Management System`.
  - (d) After a failed update with `is_published` omitted, the form re-renders the checkbox unchecked.

### Implementation for User Story 3

- [X] T033 [US3] Add the tag-suggestion enhancement to `resources/js/app.js` (research R7). For each `input[data-tag-suggestions]`:
  - Parse the JSON list and find its `datalist` through the `list` attribute.
  - On `input` (and once on load), split the value on `,`. Take the completed parts (all but the last, trimmed, non-empty) as `prefix`, and rebuild the datalist options as `prefix.join(', ') + ', ' + tag` (just `tag` when `prefix` is empty) for each suggestion not already in `prefix` (case-insensitive).
  - Use `replaceChildren` and `document.createElement('option')`; no libraries.
  - Run `npm run build`.
- [X] T034 [US3] Run `php artisan test --compact --filter=ProjectManagementTest`; all pass. Then in a browser on the Herd URL, type `Laravel, Po` in the tags field and confirm the browser suggests `Laravel, PostgreSQL`

**Checkpoint**: All three stories work independently.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T035 Run `vendor/bin/pint --dirty --format agent`
- [X] T036 Run `npm run build` and then the full suite `php artisan test --compact`; everything passes
- [ ] T037 Walk through [quickstart.md](./quickstart.md) §§1–6 on the Herd site
- [X] T038 Check layout with Playwright MCP (quickstart §7):
  - Pages: `/projects`, `/admin/projects`, `/admin/projects/create`, an edit page and a delete page.
  - At 320, 375, 768 and 1280px wide, `document.documentElement.scrollWidth <= window.innerWidth` holds and every link and button is ≥44px tall.
  - With JavaScript disabled, the create form still saves tags and the delete confirmation still works.
  - Fix any overflow in the affected view.
- [X] T039 Measure SC-008:
  - Temporarily add 18 published projects locally with `php artisan tinker --execute 'App\Models\Project::factory()->count(18)->create();'`.
  - Copy one seed WebP to each new `image_path` so images resolve.
  - Repeat the T001 measurement and record the result in `specs/002-manage-projects/baseline.md`. The median must be within 10% of the baseline.
  - Then delete the 18 projects (`Project::query()->where('id', '>', 2)->get()->each->delete()`).
- [ ] T040 List the deploy steps in the PR description (not a new doc file): `php artisan storage:link`, a persistent `public` disk, and GD with WebP on the server (research R3, R4)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: none
- **Foundational (Phase 2)**: after Setup; blocks every story
- **US1 (Phase 3)**: after Foundational
- **US2 (Phase 4)**: after Foundational. Its store test (T018 d) checks `/projects`, so finish
  US1 first when working alone.
- **US3 (Phase 5)**: after US2 (it uses the US2 form and controller)
- **Polish (Phase 6)**: after the stories you intend to ship

### Within Each Phase

- T003 → T004 → T005 → T006 (the encoder must exist to convert the seeds)
- T007 → T008 → T009 → T011 → T012; T010 can run beside T009
- US1: T013, T014 (tests) → T015 → T016 → T017
- US2: T018, T019 (tests) → T020 → T021 → T022 → T023 → T024; T025, T026 → T027; T028–T030 after T025/T026 → T031
- US3: T032 → T033 → T034

### Parallel Opportunities

- T010 beside T009
- T013 and T014
- T018 and T019; then T025 and T026; then T028, T029 and T030

## Parallel Example: User Story 2

```text
# Tests together:
T018 tests/Feature/Admin/ProjectManagementTest.php
T019 tests/Feature/Admin/AdminNavigationTest.php

# Shared components together:
T025 resources/views/components/form/field.blade.php
T026 resources/views/components/admin/nav.blade.php

# Views together (after T025, T026):
T028 resources/views/admin/projects/index.blade.php
T029 resources/views/admin/projects/form.blade.php
T030 resources/views/admin/projects/delete.blade.php
```

## Implementation Strategy

### MVP First (User Story 1 Only)

Phases 1–3 move the portfolio into the database with no visible change. Projects are still edited
by a developer (tinker) at that point, but the data source is final.

### Incremental Delivery

1. Setup + Foundational → encoder, table, model
2. US1 → data-driven public page (MVP)
3. US2 → owner CRUD with images, validation and admin nav
4. US3 → ordering/visibility checks and tag-typing enhancement
5. Polish → gates, layout pass, performance check

### Single-agent order

T001 → T040 in ID order.

## Notes

- [P] tasks touch different files and have no dependency on an incomplete task.
- Commit after each phase with a focused message (for example `store portfolio projects in the database`).
- Never call `Project::query()->delete()` in application code: bulk deletes skip the `deleted`
  listener and would leave image files behind. Tests may, since they use a fake disk.

---

## Phase 7: Convergence

- [X] T041 In the `PostTooLargeException` renderer in `bootstrap/app.php`, send a request with no signed-in user to `route('login')` instead of back with the image error, and add a case to `tests/Feature/Admin/ProjectManagementTest.php` where a guest's oversized POST to `admin.projects.store` redirects to `login` and the row count stays 2, per FR-014, US2/AC8 (partial)
- [X] T042 Add a case to `tests/Feature/WebpEncoderTest.php` where a 4032×3024 JPEG comes out 1632×1224, covering the phone-photo upload that `ProjectManagementTest` checks at only 3264×2448 because of test-process memory, per spec edge case "larger than 1632 pixels" and T018(d) (partial)
- [X] T043 Add `loading="lazy"` to every thumbnail after the first in `resources/views/admin/projects/index.blade.php` (`@unless ($loop->first)`), per FR-018 (partial)
- [X] T044 Update research.md R4 to record that `WebpEncoder` scales down with `imagecopyresampled` before applying EXIF rotation, instead of `imagescale(IMG_BICUBIC)` after it. Reason: it keeps peak memory near the source bitmap plus the 1632px copy, and a quarter turn does not change the longest side, so output sizes are unchanged. Per plan: R4 encoder decision (contradicts)
- [X] T045 Update research.md R6 to record why `ValidatePostSize` moved from global middleware into the `web` group, right after `StartSession` and before the CSRF check (`bootstrap/app.php`). In the global position the flashed image error was lost, and the CSRF check would fail first because PHP discards the whole body, token included. Per plan: R6 (unrequested)

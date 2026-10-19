# Research: Owner-Managed Projects

**Feature**: [spec.md](./spec.md) | **Plan**: [plan.md](./plan.md) | **Date**: 2026-10-19

Every unknown in the plan's Technical Context is resolved here. Laravel facts were checked against
the Laravel 13.x documentation (validation of files, resource routes, file storage, HTTP tests).
Environment facts were checked on this machine.

## R1. How projects are stored

- **Decision**: One Eloquent model `Project` backed by a `projects` table: `title`,
  `description`, `image_path`, `tags` (JSON array), `is_published`, `sort_order`, timestamps.
- **Rationale**: Matches the spec's Project entity one to one. Tags are a short list of labels with
  no data of their own (spec Key Entities), so a JSON column cast to `array` is enough; suggestions
  are computed from it (R7).
- **Alternatives considered**:
  - `tags` table with a pivot: proper normalisation, but adds two tables, a model and sync logic
    for at most 10 labels on a few dozen projects. Rejected (YAGNI, Principle I).
  - Comma-separated string column: simplest, but every reader would re-parse it and JSON keeps
    the exact spelling of each tag. Rejected.

## R2. Initial data: the two existing projects

- **Decision**: The migration that creates `projects` also inserts the two current projects
  (same pattern as `company_profiles`, feature 001 R1), with `sort_order` 1 and 2 and
  `is_published = true`. Their images are converted to WebP once during implementation with the
  same encoder as uploads (R4), committed as `database/data/projects/merryland.webp` and
  `database/data/projects/healthcare-dashboard.webp`, and copied by the migration onto the `public`
  disk at `projects/merryland.webp` and `projects/healthcare-dashboard.webp` when not already
  there. The old PNGs in `public/images/` are deleted.
- **Rationale**: `php artisan migrate --force` on deploy is enough, and a fresh environment never
  shows an empty portfolio. Keeping the committed source outside the `public` disk means deleting
  a seeded project through the admin removes only the served copy, never a tracked file.
  Converting ahead of time keeps image processing out of the migration.
- **Note**: Both PNGs are 816×544, so the WebP copies keep that size (FR-011a: under 1632px, so not scaled).
- **Alternatives considered**: a seeder (production deploys don't run seeders); converting inside
  the migration (needs GD WebP at migrate time and makes the migration slow and fragile); keeping
  the PNGs in `public/images` and pointing the rows at them (two storage locations, and deleting
  a project could not remove its image).

## R3. Where uploaded images live

- **Decision**: The `public` disk (`storage/app/public`, served at `/storage` after
  `php artisan storage:link`), under `projects/`, with a random 40-character name plus `.webp`.
  `image_path` stores the path relative to the disk; the URL comes from
  `Storage::disk('public')->url()`.
- **Rationale**: Laravel's standard place for user-uploaded public files; no new configuration.
  Random names avoid collisions and stop old URLs from serving a new image.
- **Deploy prerequisite**: `public/storage` does not exist yet, so `php artisan storage:link` must
  run locally and on deploy. The production host must keep the `public` disk between deploys (a
  persistent volume or an object-storage bucket configured as that disk); configuring that is
  outside this feature.

## R4. Converting uploads to WebP (FR-011a)

- **Decision**: A small class `App\Support\WebpEncoder` with one method,
  `encode(string $path): string`, that returns WebP bytes or throws `RuntimeException`. It uses
  PHP's bundled GD: `imagecreatefromstring()`; for JPEG, applies the EXIF `Orientation` tag
  (`exif_read_data` + `imagerotate`/`imageflip`) so phone photos are not shown sideways;
  `imagepalettetotruecolor()`; `imagealphablending(false)` + `imagesavealpha(true)` so PNG/WebP
  transparency survives; then, when either side exceeds `WebpEncoder::MAX_SIDE` (1632), scale down
  proportionally with `imagescale($image, $newWidth, $newHeight, IMG_BICUBIC)`, where the scale
  factor is `1632 / max(width, height)` and the new sides are rounded to whole pixels (minimum 1).
  The scaled copy also gets alpha saving. Images at or under 1632px are never enlarged. Finally,
  `imagewebp(..., quality: 82)` is captured with output buffering.
- **Why 1632px**: project cards show the image in an 816×544 frame (about 400 CSS px wide on
  desktop, full width on phones). 1632 is twice that frame, so images stay sharp on high-density
  screens while meeting constitution Principle IV ("Images MUST be sized for their display
  size"). Scaling happens after the EXIF rotation, so the limit applies to the image as displayed.
- **Rationale**: The maintainer chose WebP and, after analysis finding C1, automatic downscaling
  (Clarifications). GD with WebP and JPEG
  support ships with Herd's PHP 8.4.25 (`gd_info()`: JPEG ✓, PNG ✓, WebP ✓), so no Composer
  package is needed. The encoder is a class rather than a controller method because it is used by
  both store and update and by the one-time conversion of the seed images (R2), and it can be
  tested directly with real image bytes.
- **Implementation note (convergence T044)**: the encoder departs from the steps above in two
  ways. Both keep memory low; output sizes are the same.
  - It scales down with `imagecopyresampled()` into a new truecolor image instead of
    `imagescale(IMG_BICUBIC)`. GD's generic bicubic scale is two-pass and allocates an
    intermediate bitmap (about 20 MB for a 12-megapixel photo), which exhausted the 128M limit in
    testing. Resampled copying needs no buffer beyond the result and averages every source pixel,
    which suits large reductions.
  - It scales **before** applying the EXIF orientation, so only the small copy is rotated.
    `imagerotate()` allocates a second full-size bitmap. A quarter turn does not change the
    longest side, so the scaled-then-rotated size equals the rotated-then-scaled size (for
    example, 3264×1000 with orientation 6 still comes out 500×1632).
  - Measured peak for a 4032×3024 JPEG: about 63 MB including about 5 MB of baseline.
- **Environment caveat**: the Git Bash `php` (Herd Lite 8.4.0) has GD without JPEG or WebP. Tests
  and Artisan commands MUST run with Herd's PHP (`php` in PowerShell →
  `~/.config/herd/bin/php.bat`). The production PHP MUST have GD with WebP and JPEG support.
- **Alternatives considered**: `intervention/image` (adds a dependency and needs approval for a
  ten-line job); Imagick (not installed); converting in the browser (needs JavaScript, against
  Principle I).

## R5. Validation (FR-010)

- **Decision**: Two form requests, `StoreProjectRequest` and `UpdateProjectRequest` (the latter
  extends the former and makes `image` optional), matching `UpdateCompanyProfileRequest`.

  | Field | Rules |
  |---|---|
  | `title` | required, string, max 100 |
  | `description` | required, string, max 500 |
  | `image` | store: required; update: nullable. `File::image()->types(['jpeg','png','webp'])->max(2048)` with `Rule::dimensions()->maxWidth(4096)->maxHeight(4096)` |
  | `tags` | nullable, string, plus a closure rule: after parsing (R8) at most 10 tags, each at most 30 characters |
  | `is_published` | boolean (unchecked checkbox = not present = `false`) |
  | `sort_order` | required, integer, between 0 and 999 |

  Custom messages: `image.uploaded` and `image.max` both say "The image must be 2 MB or less.";
  `image.dimensions` says "The image must be no larger than 4096 pixels on either side.";
  `image.image`/`image.mimes` say "The image must be a JPEG, PNG or WebP file."
- **Why `image.uploaded` matters**: Herd's `upload_max_filesize` is exactly `2M`, so a file a
  little over 2 MB is dropped by PHP before Laravel sees it and fails the `uploaded` rule, not
  `max`. Both must give the same message.
- **4096px limit (planning amendment to FR-010)**: a 2 MB file can decode to a very large
  bitmap (GD needs about 4 bytes per pixel), and the full image is decoded before it is scaled
  down. 4096×4096 is about 67 MB, plus about 11 MB for the 1632px copy, inside the 128M
  `memory_limit`, and it still allows 12-megapixel phone photos (4032×3024). The spec's FR-010 is
  updated to match.
- **Encoding failure**: if `WebpEncoder` throws, the controller throws
  `ValidationException::withMessages(['image' => 'This image could not be processed. Try saving it again as a JPEG or PNG.'])`,
  so nothing is saved and the old image stays.

## R6. Requests larger than `post_max_size`

- **Decision**: In `bootstrap/app.php`, render `PostTooLargeException` for `admin/*` requests as a
  redirect back with the error `image` → "The image must be 2 MB or less." and no old input.
- **Rationale**: Laravel's global `ValidatePostSize` middleware rejects bodies over
  `post_max_size` (8M here) with a 413 page before validation runs. Redirecting keeps the owner on
  the form with a clear message (spec edge case "image over 2 MB"). The form's other fields are
  lost in this one case because PHP discarded the whole body.
- **Implementation note (convergence T045)**: `ValidatePostSize` is also moved out of the global
  middleware and into the `web` group, sorted right after `StartSession` and before the CSRF check
  (`bootstrap/app.php`: `remove`, `web(prepend:)`, `appendToPriorityList`). Two reasons:
  - In the global position the exception is rendered before the session starts, so the flashed
    `image` error never reached the owner.
  - If the check ran later, after the CSRF check, the token check would fail first, because PHP
    discards the whole body, `_token` included. The owner would then see "Your session expired"
    instead of the size message.

  Because the check now runs before `auth`, the renderer sends a request with no signed-in user to
  `login` (FR-014, convergence T041). The check no longer covers routes outside the `web` group;
  this app has none that accept bodies.

## R7. Tag suggestions (FR-012a)

- **Decision**: `Project::tagSuggestions(): array` returns every distinct tag across all projects
  (case-insensitive, first spelling kept, sorted alphabetically). The form renders them two ways:
  1. A visible hint under the field: "Tags already in use: Laravel, Livewire, …". This works
     without JavaScript and on every device.
  2. A `<datalist>` attached to the tags input, enhanced by about 20 lines of vanilla JavaScript in
     `resources/js/app.js`: on input, it rebuilds the options as "text typed up to the last comma"
     plus each unused tag, so the browser's native suggestion list works for the tag currently
     being typed.
- **Rationale**: Principle I allows JavaScript only as progressive enhancement. A plain datalist
  only matches the start of the whole value, so it stops helping after the first comma; the small
  script fixes that, and the visible hint covers browsers and devices where datalists don't show.
- **Alternatives considered**: a tag-picker library (dependency and heavy JS); a hint only (does
  not "suggest as the owner types").

## R8. Tag parsing (FR-012)

- **Decision**: `Project::parseTags(?string $input): array` splits on commas, trims each part,
  drops empty parts, and removes later duplicates that differ only in letter case, keeping the
  order entered and the first spelling. It is used by the validation closure (R5) and when saving.
  The edit form shows tags joined with `", "`.

## R9. Ordering and defaults (FR-002, FR-013)

- **Decision**: Scopes `published()` (`is_published = true`) and `ordered()` (`sort_order`
  ascending, then `id` ascending). The create form is pre-filled with
  `min(max(sort_order) + 1, 999)` (or 1 if there are none) and with "Published" checked.
- **Rationale**: Ties broken by `id` give the "added first comes first" rule from Story 3,
  scenario 3, and a stable order on every load.

## R10. Admin routes, delete confirmation and missing records

- **Decision**: `Route::resource('projects', Admin\ProjectController::class)->except('show')`
  inside the existing `auth` + `admin.` group, plus
  `GET admin/projects/{project}/delete` → `delete` (name `admin.projects.delete`), a confirmation
  page whose form sends `DELETE`. Both use `->missing()` to redirect to `admin.projects.index`
  with flash `status` = "That project no longer exists."
- **Rationale**: The confirmation step must work without JavaScript (Principle I, FR-009), so it
  is its own page rather than a `confirm()` dialog. `missing()` is Laravel's documented hook for
  the "deleted in another tab" edge case. Session expiry on `admin/*` is already handled in
  `bootstrap/app.php` (feature 001).

## R11. Image file lifecycle (FR-009, FR-011)

- **Decision**:
  - Create: encode and store the file, then create the row; if the insert throws, delete the new
    file.
  - Update with a new image: store the new file, update the row, then delete the old file.
  - Delete: `Project::booted()` registers a `deleted` listener that deletes `image_path` from the
    `public` disk, so every delete path cleans up.
- **Rationale**: The old file is removed only after the row points at the new one, so a failure
  never leaves a project without an image.

## R12. Missing image file on the public page

- **Decision**: `Project::imageUrl(): ?string` returns `null` when the file is not on the disk;
  the card then shows a neutral `bg-gray-100` block with the same 3:2 frame (no `<img>`).
- **Rationale**: Covers the spec edge case without a broken-image icon. One file-existence check
  per card on the local disk is negligible for tens of projects. If the disk ever moves to object
  storage, revisit this, since each check would become a network call.

## R13. Public Projects page

- **Decision**: `PageController::projects()` passes
  `Project::query()->published()->ordered()->get()`. The view keeps today's markup (3:2 frame,
  `object-cover`, `width="816" height="544"`, `decoding="async"`, lazy loading after the first)
  and reads `$project->title`, `->description`, `->tags` and `->imageUrl()`. With no projects it
  shows "New projects will be added soon." in place of the grid.
- **Rationale**: One indexed query on a table of tens of rows is well within SC-008, so no cache
  is added (unlike the company profile, which is read on every page).

## R14. Admin navigation (FR-015)

- **Decision**: A Blade component `x-admin.nav` with links to "Company details" and "Projects"
  (`aria-current="page"` on the active one, 44px targets) and the existing "Sign out" form. It
  replaces the sign-out block on `admin/company/edit` and appears on every project screen.
- **Rationale**: Principle II requires shared markup through components, not copies.

## R15. Testing approach

- **Decision**: Pest feature tests with `Storage::fake('public')` and
  `UploadedFile::fake()->image(...)` (needs GD, so Herd's PHP). Encoder tests use real generated
  JPEG/PNG bytes and assert the output starts with the `RIFF....WEBP` signature and keeps its
  width and height when at or under 1632px, and that a 3264×1000 image comes out 1632×500. An
  oversized-upload test uses `UploadedFile::fake()->image(...)->size(2049)`.
- **Note**: The migration copies the two seed images onto the real `public` disk the first time
  tests run (it skips existing files). This is harmless: that folder is git-ignored.

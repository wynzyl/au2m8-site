# Implementation Plan: Owner-Managed Projects

**Branch**: `002-manage-projects` | **Date**: 2026-10-19 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `specs/002-manage-projects/spec.md`

## Summary

Move the portfolio from the hard-coded array in `PageController::projects()` into a `projects`
table. The migration inserts today's two projects, and their images are pre-converted to WebP.
The public Projects page lists published projects ordered by `sort_order`, then `id`, and shows
an empty-state message when there are none. Behind the existing owner sign-in, a resource
controller at `/admin/projects` lets owners list, add, edit and delete projects. Deleting goes
through a confirmation page that works without JavaScript. Uploads are JPEG, PNG or WebP, up to
2 MB and 4096px on each side. They are converted to WebP by a small GD-based
`WebpEncoder`, which scales anything larger than 1632px on a side down to fit and never enlarges
smaller images, and stored on the `public` disk. Old files are removed when an image is
replaced or its project is deleted. Tags are typed as a comma-separated list. Existing tags are
suggested through a visible hint and a progressively enhanced `<datalist>`. A shared
`x-admin.nav` links the company and projects screens. No new dependencies.

## Technical Context

**Language/Version**: PHP 8.4 (Herd 8.4.25), Laravel 13.37

**Primary Dependencies**: laravel/framework 13 (Eloquent, Validation incl. `File::image()`,
Filesystem, resource routes with `missing()`), PHP's bundled GD with WebP and JPEG support, plus
the `exif` extension. Front end: Blade, Tailwind CSS v4 via `@tailwindcss/vite`, Vite 8, and a few
lines of vanilla JS in `resources/js/app.js`. No additions.

**Storage**: SQLite for the new `projects` table. Image files go on the `public` disk
(`storage/app/public/projects`, served via `php artisan storage:link`). The seed image sources
are in `database/data/projects/`.

**Testing**: Pest 5 feature tests with `RefreshDatabase` on in-memory SQLite,
`Storage::fake('public')` and `UploadedFile::fake()->image()`. Run with Herd's PHP, not Git
Bash's Herd Lite, which lacks GD JPEG/WebP. Playwright MCP covers the viewport checks.

**Target Platform**: Web server via Laravel Herd locally. Production needs GD with WebP, a
persistent `public` disk and `storage:link`. Browsers: current evergreen desktop and mobile.

**Project Type**: Server-rendered web application (single Laravel project).

**Performance Goals**: The public page makes one indexed query and one file-existence check per
card. With 20 projects, the median response stays within 10% of today (SC-008). Changes show on
the next page load (FR-008), with no cache to clear.

**Constraints**: Pages and admin forms work without JavaScript. WCAG 2.1 AA. No horizontal
overflow from 320px wide. Tap targets ≥44px. Image decode must stay within the 128M memory
limit, hence the 4096px cap. Herd's `upload_max_filesize` is 2M and `post_max_size` is 8M (R5, R6).

**Scale/Scope**: Tens of projects, a handful of owners, 1 public page changed, 4 new admin views
(index, form, delete, nav component) and 1 changed admin view.

All items are resolved. See [research.md](./research.md) R1–R15.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle / Rule | Status | Notes |
|---|---|---|
| I. No DB-backed CMS without spec + maintainer approval | **Justified exception** | The maintainer asked for "a CRUD function that can be saved in the database" (spec Input and Assumptions). The scope is one model with one resource controller, not a general CMS. See Complexity Tracking. |
| I. Pages render without JS | Pass | Delete confirmation is a page, not a `confirm()` dialog. Tag suggestions have a no-JS visible hint, and the script only enhances the `<datalist>` (R7). |
| I. No speculative abstractions | Pass | The one new class, `WebpEncoder`, is shared by store, update and the seed conversion (R4). There are no repositories or services, and tags are JSON rather than extra tables (R1). |
| II. Laravel conventions: named routes, `route()`, `make:*`, typed PHP, shared layout | Pass | `Route::resource` plus one named `delete` route ([contracts/routes.md](./contracts/routes.md)). Files come from `make:model -mf`, `make:controller --resource`, `make:request`, `make:class`, `make:component` and `make:test --pest`. Admin chrome is shared through `x-admin.nav`, and forms reuse `x-form.field`. |
| III. Every page tested, tests in the same change | Pass | See the Testing Plan. `/projects` keeps its existing tests, rewritten for stored data. Every admin route has guest, happy-path and failure tests. |
| IV. WCAG AA, 320px, sized and lazy images, unique titles | Pass | Uploads are scaled down to at most 1632px, twice the 816px display frame (R4), so images are sized for their display. This was resolved after analysis finding C1 had flagged unscaled uploads up to 4096px. `alt` = title, lazy after the first image, declared width and height in a fixed 3:2 frame, a unique `<title>` per admin screen, 44px targets, `aria-current` in the admin nav, and errors linked by `aria-describedby` through `x-form.field`. |
| V. Truthful content, single source | Pass | The initial rows copy today's two projects verbatim. No invented projects. Project content is removed from `PageController` (SC-003). The owner is responsible for the truth of new entries (spec Assumptions). |
| Dependencies need approval | Pass | None added. `intervention/image` was rejected (R4). |
| No new top-level directories | Pass | New folders `app/Support`, `database/data/projects`, `resources/views/admin/projects` and `resources/views/components/admin` all sit under existing base folders. |
| Secrets in `.env` | Pass | No secrets added. |

**Gate result**: PASS with one justified exception, the same kind already accepted for feature 001.

**Post-design re-check (after Phase 1)**: PASS. The data model, contracts and quickstart add no
dependencies or top-level directories. The only new class is `WebpEncoder`, and only one small
script is added, which is a progressive enhancement. The exception stays limited to `projects`
and `/admin/projects`.

**Planning amendment to the spec**: FR-010 now also refuses images larger than 4096 pixels on
either side, to keep decoding within memory (R5).

## Project Structure

### Documentation (this feature)

```text
specs/002-manage-projects/
├── plan.md              # This file
├── research.md          # Phase 0 decisions R1–R15
├── data-model.md        # Project, image files, derived tags
├── quickstart.md        # Validation guide
├── contracts/
│   └── routes.md        # Public page, admin routes, shared view contract
├── checklists/
│   └── requirements.md  # Spec quality checklist
└── tasks.md             # Created by /speckit-tasks
```

### Source Code (repository root)

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── PageController.php              # EDIT projects() reads Project::published()->ordered()
│   │   └── Admin/ProjectController.php     # NEW  index/create/store/edit/update/delete/destroy
│   └── Requests/
│       ├── StoreProjectRequest.php         # NEW  rules + messages (R5)
│       └── UpdateProjectRequest.php        # NEW  extends Store, image optional
├── Models/
│   └── Project.php                         # NEW  casts, scopes, imageUrl, parseTags, tagSuggestions, nextSortOrder, deleted listener
└── Support/
    └── WebpEncoder.php                     # NEW  GD → WebP, EXIF orientation, alpha (R4)

bootstrap/app.php                           # EDIT PostTooLargeException → back with image error on admin/* (R6)

database/
├── data/projects/{merryland,healthcare-dashboard}.webp  # NEW  pre-converted seed images
├── factories/ProjectFactory.php            # NEW  + unpublished() state
└── migrations/xxxx_create_projects_table.php  # NEW  table, index, 2 initial rows, copy seed images

public/images/{Merryland,healthcare-dashboard}.png  # DELETE (replaced by the WebP seeds)

resources/
├── js/app.js                               # EDIT tag datalist enhancement (R7)
└── views/
    ├── pages/projects.blade.php            # EDIT model properties, placeholder frame, empty state
    ├── components/admin/nav.blade.php      # NEW  Company details / Projects / Sign out
    └── admin/
        ├── company/edit.blade.php          # EDIT use <x-admin.nav />
        └── projects/
            ├── index.blade.php             # NEW
            ├── form.blade.php              # NEW  create + edit
            └── delete.blade.php            # NEW  confirmation

routes/web.php                              # EDIT resource + delete route with missing()

tests/Feature/
├── PublicPagesTest.php                     # EDIT projects page assertions from stored data
├── MobileLayoutTest.php                    # EDIT project image assertions (seeded rows)
├── ProjectTest.php                         # NEW  model: scopes, parseTags, suggestions, nextSortOrder, imageUrl, file cleanup
├── WebpEncoderTest.php                     # NEW  JPEG/PNG → WebP, size kept, alpha, EXIF, bad bytes throw
└── Admin/
    ├── ProjectManagementTest.php           # NEW  CRUD, validation dataset, image replace/delete, missing record, guests
    └── AdminNavigationTest.php             # NEW  nav on both admin screens, active link
```

**Structure Decision**: Single Laravel project with the existing `app/`, `bootstrap/`,
`database/`, `resources/`, `routes/` and `tests/` layout. New code goes in conventional
subfolders created by `make:*` generators. `database/data/` holds binary seed assets beside
the migration that uses them.

## Implementation Order

1. **Foundation**: `WebpEncoder` and its tests. Convert the two PNGs into
   `database/data/projects/*.webp` and delete the PNGs. Run `storage:link`. Add the migration
   (table, index, initial rows, copying the seed images), the `Project` model and factory, and
   `ProjectTest`.
2. **Story 1 (P1)**: `PageController::projects()` and `pages/projects.blade.php` (placeholder,
   empty state). Update `PublicPagesTest` and `MobileLayoutTest`, then run the SC-003 grep.
3. **Story 2 (P1)**: routes with `missing()`. `StoreProjectRequest` and `UpdateProjectRequest`.
   `Admin\ProjectController`. The `x-admin.nav` component, used on the company screen too. Views
   `index`, `form` (with the tag hint and datalist) and `delete`. The `PostTooLargeException`
   handler. Then `ProjectManagementTest` and `AdminNavigationTest`.
4. **Story 3 (P2)**: the published checkbox, order field and next-order default are already in
   the form. Add tests for order, ties and hiding. Add the `app.js` datalist enhancement.
5. **Gates**: Pint, the full test suite, `npm run build`, and the quickstart visual checks at
   320, 375, 768 and 1280px, including with JavaScript disabled.

Story 1 must land before Story 2 because it creates the model and table. Story 3 is a small
addition to Story 2's form.

## Testing Plan

| Test file | Covers |
|---|---|
| `WebpEncoderTest` | JPEG and PNG input give WebP bytes (`RIFF…WEBP`) with the same width and height when at or under 1632px. A 3264×1000 input comes out 1632×500, and a 1000×3264 input comes out 500×1632. PNG transparency is kept. A JPEG with EXIF orientation 6 comes out rotated, so its width and height are swapped. Non-image bytes throw `RuntimeException`. |
| `ProjectTest` | The migration creates the 2 initial rows in order with exact titles, descriptions and tags. `published()->ordered()` hides unpublished projects and breaks ties by `id`. `parseTags` trims, drops empty entries and removes duplicates ignoring case while keeping the first spelling. `tagSuggestions` covers all projects including hidden ones and forgets tags whose projects were deleted. `nextSortOrder` returns max+1, is capped at 999 and returns 1 when empty. `imageUrl` returns null for a missing file. Deleting a project deletes its file. |
| `PublicPagesTest` | `/projects` shows both seeded projects with exact wording and tags, in order. An unpublished project is absent. The empty state appears with no published projects. A title with `&`/`<` is escaped. A missing file renders the placeholder with no `<img>`. |
| `MobileLayoutTest` | Project `<img>` tags have width/height and `decoding="async"`, the first is eager and the rest `loading="lazy"`, and `alt` equals the title. |
| `Admin/ProjectManagementTest` | Guests are redirected to login on every route (GET, POST, PUT, DELETE) and nothing changes. The index lists published and hidden projects. Create stores a `.webp` on the fake disk and the project then appears publicly. The create form pre-fills the next order with Published checked. Updating without an image keeps the file, and with an image replaces it and deletes the old one. Delete confirmation shows the title, and destroy removes the row and file. Each mutation sets the expected flash. A missing project on edit, update, delete or destroy redirects to the index with "That project no longer exists." The validation dataset covers each FR-010 rule (blank or long title and description, missing image on create, wrong type, over 2 MB, over 4096px, 11 tags, a 31-character tag, order −1, 1000 or non-integer) and leaves the database and disk unchanged. An encoder failure gives an `image` error. A post-too-large request redirects back with the image error. The noindex meta is on every admin view. |
| `Admin/AdminNavigationTest` | Both admin screens render the nav with both links and Sign out, with `aria-current` on the active section. |

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Database-backed, editable portfolio (Principle I) | The maintainer requires the owner to add, edit and remove projects without a code change or deploy | A config file or array in `PageController` needs a developer and a deploy for every change |
| Uploaded files with server-side WebP conversion (Principle I) | Each project needs an image the owner supplies, and the maintainer chose WebP storage | Pasting image URLs relies on outside hosting and can't guarantee WebP. Converting in the browser needs JavaScript. `intervention/image` adds a dependency for about 30 lines of GD code. |
| Small JavaScript enhancement for tag suggestions (Principle I) | FR-012a asks for suggestions while typing, and a native `<datalist>` stops matching after the first comma | A hint alone doesn't suggest as you type. A tag-picker library adds a dependency. The script is optional, because the hint and plain typing still work without it. |

# Data Model: Owner-Managed Projects

**Feature**: [spec.md](./spec.md) | **Plan**: [plan.md](./plan.md) | **Date**: 2026-10-19

## Project (new)

Table `projects`. One row per portfolio project; any number may exist.

| Field          | Type                 | Null | Rules (see FR-010)                                                     | Default |
|----------------|----------------------|------|------------------------------------------------------------------------|---------|
| `id`           | bigint, PK           | no   | auto-increment; breaks `sort_order` ties                               | |
| `title`        | string(100)          | no   | required, max 100                                                      | |
| `description`  | text                 | no   | required, max 500                                                      | |
| `image_path`   | string(255)          | no   | path on the `public` disk, `projects/<random>.webp`; set by the server | |
| `tags`         | json                 | no   | array of 0–10 strings, each ≤ 30 characters, unique ignoring case      | `[]` |
| `is_published` | boolean              | no   | boolean                                                                | `true` |
| `sort_order`   | unsigned smallint    | no   | required, integer, 0–999                                               | `0` (forms pre-fill the next value, R9) |
| `created_at`   | timestamp            | yes  | "when it was added"                                                    | |
| `updated_at`   | timestamp            | yes  | "last changed"                                                         | |

**Index**: `(is_published, sort_order, id)` for the public listing.

**Model**: `App\Models\Project`

- `#[Fillable(['title', 'description', 'image_path', 'tags', 'is_published', 'sort_order'])]`,
  matching `CompanyProfile`.
- Casts: `tags` → `array`, `is_published` → `boolean`, `sort_order` → `integer`.
- `HasFactory` with `ProjectFactory` (Faker title and sentence, 1–3 tags from a fixed list of real
  technologies, `image_path` `projects/<random>.webp`). States: `unpublished()`.
- Scopes: `published()`, `ordered()` ([research R9](./research.md#r9-ordering-and-defaults-fr-002-fr-013)).
- `public function imageUrl(): ?string`: public URL, or `null` when the file is missing
  ([R12](./research.md#r12-missing-image-file-on-the-public-page)).
- `public static function parseTags(?string $input): array`
  ([R8](./research.md#r8-tag-parsing-fr-012)).
- `public static function tagSuggestions(): array`
  ([R7](./research.md#r7-tag-suggestions-fr-012a)).
- `public static function nextSortOrder(): int`: `min(max(sort_order) + 1, 999)`, or 1 when empty.
- `booted()`: a `deleted` listener deletes `image_path` from the `public` disk
  ([R11](./research.md#r11-image-file-lifecycle-fr-009-fr-011)).

**Initial rows** (inserted by the migration, [R2](./research.md#r2-initial-data-the-two-existing-projects)):

| `sort_order` | `title` | `description` | `image_path` | `tags` |
|---|---|---|---|---|
| 1 | School Management System | Complete student information system with enrollment, grades, billing, and reporting. | `projects/merryland.webp` | `["NextJS", "React", "PostgreSQL"]` |
| 2 | Healthcare Management System | Real-time patient monitoring system with analytics and reporting. | `projects/healthcare-dashboard.webp` | `["Laravel", "Livewire", "PostgreSQL"]` |

Both have `is_published = true`. The values are copied verbatim from today's `PageController`.

## Project image (file, not a table)

A WebP file on the `public` disk under `projects/`. Exactly one per project, at most 1632px on
either side: larger uploads are scaled down proportionally, and smaller ones keep their size. Created on store, replaced on update (old file
deleted after the row is updated), and deleted when the project is deleted. The seed sources live
in `database/data/projects/` and are never deleted by the application.

## Tag (derived, not stored separately)

A string inside a project's `tags` array. The suggestion list is the set of distinct tags across
all projects, so a tag used only by a deleted project stops being suggested.

## Site owner (existing)

`App\Models\User`, unchanged. Every signed-in user may manage every project.

## Lifecycle

```text
          create (published checked)            edit: untick "Published"
 (none) ───────────────────────────► Published ◄────────────────────────► Hidden
    ▲      create (published unticked)    │      edit: tick "Published"     │
    │  ──────────────────────────────────►│─────────────────────────────────┘
    │                                     │ delete (after confirmation)
    └─────────────────────────────────────┴──► (row and image file removed)
```

Only published projects appear on `/projects`. Every project appears in the admin list.

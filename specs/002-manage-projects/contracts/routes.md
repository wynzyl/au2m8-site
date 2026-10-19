# Contract: HTTP Routes and Views

**Feature**: [spec.md](../spec.md) | **Date**: 2026-10-19

The site exposes HTML pages only; there is no JSON API. All routes are in `routes/web.php` and
use the `web` middleware group (session, CSRF).

## Public page (existing; data source changes)

| Method | URI         | Name       | Controller                | View             | Must show |
|--------|-------------|------------|---------------------------|------------------|-----------|
| GET    | `/projects` | `projects` | `PageController@projects` | `pages.projects` | Title "Projects - {company name}"; h1 "Our Projects"; for each published project in `sort_order`, then `id` order: image (`alt` = title) or a placeholder frame, title (h2), description, tags. With none: "New projects will be added soon." |

The controller passes `$projects` (`Collection<int, Project>`), replacing the hard-coded array.
The first image is eager and the rest have `loading="lazy"`. Every `<img>` keeps `width="816"`
`height="544"` `decoding="async"`.

## Admin: projects (new)

All routes are inside the existing `auth` middleware group with prefix `admin` and name prefix
`admin.`. A guest is redirected to `login` and nothing is shown or changed. An expired session on
any `admin/*` request redirects to `login` with "Your session expired. Please sign in again."
(existing handler).

| Method | URI                               | Name                    | Controller method                     | Success | Failure |
|--------|-----------------------------------|-------------------------|---------------------------------------|---------|---------|
| GET    | `/admin/projects`                 | `admin.projects.index`  | `Admin\ProjectController@index`       | 200, view `admin.projects.index`: every project in admin order with thumbnail, title, "Published"/"Hidden", order, Edit and Delete links; an "Add project" link; flash `status` if present | |
| GET    | `/admin/projects/create`          | `admin.projects.create` | `@create`                             | 200, view `admin.projects.form`, empty fields, "Published" checked, order pre-filled with the next value | |
| POST   | `/admin/projects`                 | `admin.projects.store`  | `@store` (`StoreProjectRequest`)      | image converted to WebP and stored, row created; redirect to `admin.projects.index` with `status` = "Project “{title}” added." | invalid: redirect back with per-field errors and old input (except the file), nothing stored |
| GET    | `/admin/projects/{project}/edit`  | `admin.projects.edit`   | `@edit`                               | 200, view `admin.projects.form` pre-filled; current image shown as a thumbnail | missing: redirect to index with `status` = "That project no longer exists." |
| PUT    | `/admin/projects/{project}`       | `admin.projects.update` | `@update` (`UpdateProjectRequest`)    | row updated; with a new image: new WebP stored, old file deleted; redirect to index with `status` = "Project “{title}” updated." | invalid: back with errors, row and image unchanged; missing: as above |
| GET    | `/admin/projects/{project}/delete`| `admin.projects.delete` | `@delete`                             | 200, view `admin.projects.delete`: "Delete “{title}”? This cannot be undone." with a Delete button (form `DELETE`) and a Cancel link back to the index | missing: as above |
| DELETE | `/admin/projects/{project}`       | `admin.projects.destroy`| `@destroy`                            | row and image file deleted; redirect to index with `status` = "Project “{title}” deleted." | missing: as above |

Form fields (`multipart/form-data`): `title`, `description`, `image` (file, `accept="image/jpeg,image/png,image/webp"`),
`tags` (text, comma-separated, `list` → datalist of suggestions, hint "Tags already in use: …"),
`is_published` (checkbox, value `1`), `sort_order` (number, 0–999). Rules and messages are in
[research R5](../research.md#r5-validation-fr-010).

**Request body over `post_max_size`** on any `admin/*` route: redirect back with the `image`
error "The image must be 2 MB or less." ([research R6](../research.md#r6-requests-larger-than-post_max_size)).

## Admin: company profile (existing; markup change only)

`GET /admin/company` (`admin.company.edit`) replaces its own "Sign out" block with the shared
admin navigation. Behaviour is unchanged.

## Shared view contract

- All `admin.*` views extend `layouts.app`, push `<meta name="robots" content="noindex, nofollow">`
  and render `<x-admin.nav />` above their `h1`.
- `x-admin.nav` shows "Company details" (`admin.company.edit`) and "Projects"
  (`admin.projects.index`, active for every `admin.projects.*` route) with `aria-current="page"`
  on the active link, plus the "Sign out" form. Every control is at least 44×44px and the nav wraps
  without horizontal scrolling at 320px.
- `admin.projects.form` serves both create and edit (the action, method and button label change).
  It reuses `x-form.field` for title, description, tags and order.
- After sign-in, owners still land on `admin.company.edit` (unchanged).

# Quickstart: Validate "Owner-Managed Projects"

**Feature**: [spec.md](./spec.md) | **Plan**: [plan.md](./plan.md)

Run commands from PowerShell in the repository root. PowerShell's `php` is Herd's PHP 8.4.25,
which has GD with JPEG and WebP. The `php` in Git Bash (Herd Lite 8.4.0) lacks both and cannot
run these tests ([research R4](./research.md#r4-converting-uploads-to-webp-fr-011a)).

## Prerequisites

- Laravel Herd serving the site (get the URL with Boost's `get-absolute-url`)
- An owner account (`php artisan app:create-admin` if you have none)
- Two test images: a JPEG under 2 MB, and any image over 2 MB

## 1. Storage link, schema and initial data

```powershell
php artisan storage:link
php artisan migrate --no-interaction
php artisan tinker --execute 'dump(App\Models\Project::query()->ordered()->get(["title","image_path","tags","sort_order"])->toArray());'
```

Expected: two projects, "School Management System" then "Healthcare Management System", with
`projects/*.webp` paths and the tags listed in [data-model.md](./data-model.md). Both files exist
under `storage/app/public/projects/`.

## 2. Automated tests

```powershell
php artisan test --compact --filter=WebpEncoder
php artisan test --compact --filter=Project
php artisan test --compact --filter=PublicPages
php artisan test --compact
```

Expected: all pass.

## 3. No project content left in code (SC-003)

```powershell
git grep -n -e "School Management System" -e "Healthcare Management System" -e "Merryland" -- app resources routes config public
```

Expected: no matches. The only copies are in the migration under `database/migrations/` and the
seed images in `database/data/projects/`.

## 4. Public page unchanged (Story 1, SC-004)

Open `/projects`. Expected: the same two cards, wording and tags as before, images now served from
`/storage/projects/*.webp`. In DevTools → Network, both images have type `webp`.

## 5. Owner manages projects (Story 2)

1. While signed out, open `/admin/projects`. Expected: redirected to `/login`.
2. Sign in and use the "Projects" link in the admin navigation. Expected: both projects listed as
   Published with orders 1 and 2.
3. Click "Add project". Enter a title, a description, the JPEG, and tags `laravel, Tailwind,
   Laravel ,`. While typing after the comma, the browser suggests existing tags; the hint lists
   them too. Save. Expected: "Project “…” added."; on `/projects` it is third, its tags show as
   `laravel, Tailwind` (one copy of Laravel, spaces trimmed) and its image is `.webp`.
4. Edit it, change only the description, and save. Expected: new description, same image file.
5. Edit it again and upload a different image wider than 1632px (for example a phone photo
   under 2 MB). Expected: the new image is shown, the stored `.webp` is at most 1632px on its
   longest side with the same proportions, and the previous `.webp` file is gone from
   `storage/app/public/projects/`.
6. Try to save with a blank title, then with the image over 2 MB, then with a `.txt` file renamed
   to `.jpg`. Expected: each is refused with a message beside the field ("The image must be 2 MB or
   less.", "The image must be a JPEG, PNG or WebP file."), the other entries are kept, and
   nothing changes on `/projects`.
7. Click "Delete" on the new project, then "Cancel". Expected: nothing changes. Click "Delete" and
   confirm. Expected: "Project “…” deleted.", it is gone from `/projects`, and its image file is
   removed.
8. Open the edit URL of the deleted project. Expected: back on the list with "That project no
   longer exists."

## 6. Order and visibility (Story 3)

1. Set "Healthcare Management System" to order 0. Expected: it is first on `/projects`.
2. Untick "Published" on it. Expected: gone from `/projects`, still listed as Hidden in the admin.
3. Restore both to their original state (order 2, Published).

## 7. Layout and accessibility (FR-017, FR-018, SC-007)

At 320px, 375px, 768px and 1280px widths, check `/projects`, `/admin/projects`, the add/edit form
and the delete page. Expected: no horizontal scrolling, every button and link at least 44px tall,
long titles and tags wrap. With JavaScript disabled, the add form still saves tags and the
delete confirmation still works.

## 8. Quality gates

```powershell
vendor/bin/pint --dirty --format agent
npm run build
php artisan test --compact
```

# Feature Specification: Owner-Managed Projects

**Feature Branch**: `002-manage-projects`

**Created**: 2026-10-19

**Status**: Draft

**Input**: User description: "I want the project page to be more dynamic that the admin will have
a CRUD function that can be save in the database"

## Clarifications

### Session 2026-10-19

- Q: Should each project stay as a card on the single Projects page, or also get its own page with more detail? → A: Single list of cards only (image, title, description, tags); no per-project pages or links.
- Q: Should large uploads be resized automatically or refused? → A: Files over 2 MB are refused; every accepted upload is saved in WebP format. (The original "no automatic resizing" part was superseded by the next answer.)
- Q: After analysis found that unresized images break the constitution rule "Images MUST be sized for their display size", should uploads be resized automatically? → A: Yes. Accepted images are scaled down proportionally so neither side exceeds 1632 pixels (twice the 816-pixel display frame); smaller images keep their size; the 2 MB limit stays.
- Q: Should tags be typed freely or picked from a managed list? → A: Typed freely, with tags already used on other projects suggested as the owner types.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Visitors see the projects the owner has published (Priority: P1)

A prospective client opens the Projects page and sees the company's portfolio: each project's
image, title, short description and technology tags, in the order the owner chose. The projects
come from stored data rather than being written into the site, so what visitors see is always the
current, approved portfolio.

**Why this priority**: The portfolio is the reason the page exists. Moving it to stored data,
without losing or changing anything visitors see today, is the base every other story depends on.

**Independent Test**: With the two current projects loaded as the initial data, open the Projects
page and confirm it shows both, with the same titles, descriptions, images and tags as today.

**Acceptance Scenarios**:

1. **Given** the initial data has been loaded, **When** a visitor opens the Projects page,
   **Then** it shows "School Management System" and "Healthcare Management System" with the same
   descriptions, images and tags as before this change.
2. **Given** several published projects, **When** a visitor opens the Projects page, **Then** the
   projects appear in the display order the owner set.
3. **Given** a project is marked as not published, **When** a visitor opens the Projects page,
   **Then** that project does not appear anywhere on the page.
4. **Given** no published projects exist, **When** a visitor opens the Projects page, **Then** the
   page loads, keeps its heading and introduction, and shows a short message that projects will
   be added soon instead of an empty grid.

---

### User Story 2 - Owner adds, edits and removes projects (Priority: P1)

The signed-in site owner opens a private Projects screen that lists every project, published or
not. From there they can add a new project (title, description, image, tags, published or not),
change any detail of an existing one, including replacing its image, or delete it. A saved change
appears on the public Projects page on the next page load. No developer and no redeploy are
needed.

**Why this priority**: This is the capability the request asks for. It removes the developer
bottleneck for keeping the portfolio current.

**Independent Test**: Sign in as the owner, add a project with an image and two tags, confirm it
appears on the Projects page; edit its title and confirm the new title appears; delete it and
confirm it is gone.

**Acceptance Scenarios**:

1. **Given** the owner is signed in, **When** they open the Projects screen, **Then** they see
   every project with its title, published state and display order, and a way to add a new one.
2. **Given** the owner fills in a title, description, image and tags and saves a new published
   project, **When** any visitor opens the Projects page, **Then** the new project appears with
   that content.
3. **Given** an existing project, **When** the owner changes its description and saves without
   choosing a new image, **Then** the description is updated and the current image is kept.
4. **Given** an existing project, **When** the owner uploads a new image and saves, **Then** the
   Projects page shows the new image and the old image is no longer kept or served.
5. **Given** an existing project, **When** the owner chooses to delete it, **Then** they are asked
   to confirm; after confirming, the project and its image are removed and it no longer appears
   anywhere. If they cancel, nothing changes.
6. **Given** the owner submits invalid details (for example a blank title, a file that is not an
   image, or an image that is too large), **When** they save, **Then** nothing is changed, a
   message beside each problem field explains what is wrong, and their other entries are kept.
7. **Given** a successful add, edit or delete, **When** the screen reloads, **Then** a short
   confirmation message says what was done.
8. **Given** a visitor who is not signed in, **When** they try to open, submit or delete through
   any project admin screen, **Then** they are sent to the sign-in page and nothing is shown or
   changed.

---

### User Story 3 - Owner controls order and visibility (Priority: P2)

The owner decides which projects lead the page and can prepare a project in advance, keeping it
hidden until it is ready or temporarily hiding one without deleting it.

**Why this priority**: The first projects a visitor sees carry the most weight, and being able to
draft or hide a project avoids publishing unfinished or unapproved content. Useful, but the page
works without it.

**Independent Test**: Give a project a display order that puts it first and confirm it moves to
the top of the Projects page; mark it not published and confirm it disappears from the public
page but stays in the admin list.

**Acceptance Scenarios**:

1. **Given** three published projects, **When** the owner changes one project's display order so
   it comes first and saves, **Then** it is the first project on the Projects page.
2. **Given** a published project, **When** the owner marks it not published and saves, **Then** it
   disappears from the public page but stays in the admin list, marked as not published.
3. **Given** two projects with the same display order, **When** a visitor opens the Projects page,
   **Then** they appear in a stable order (the one added first comes first) on every load.

---

### Edge Cases

- A project's title, description or tag contains characters with special meaning in web pages
  (for example `&` or `<`): they are shown as literal text.
- A project has no tags: it is shown without a tag row and the layout does not break.
- The owner enters duplicate tags or tags with extra spaces (for example "Laravel, laravel ,"):
  surrounding spaces and empty entries are dropped, and duplicates that differ only in letter case
  are kept once.
- Very long titles, descriptions or tags wrap on narrow screens instead of overflowing.
- An uploaded image has a different shape from the existing ones: the public page shows it in the
  same frame as the others, cropped to fit, without distorting it or shifting the layout.
- A project's stored image file is missing: the page still loads and the project is shown with a
  neutral placeholder area instead of a broken image.
- The owner opens the edit screen for a project that has since been deleted (for example in
  another tab): they are told it no longer exists and returned to the Projects list.
- The owner's session expires while adding or editing: nothing is saved, and they are taken to the
  sign-in page with the message "Your session expired. Please sign in again.", as on the company
  details screen.
- An image is over 2 MB: it is refused with a message telling the owner to reduce it to 2 MB or
  less; size limits are checked before any resizing.
- An image is larger than 1632 pixels on a side (for example a 4032×3024 phone photo): it is
  accepted and scaled down to fit within 1632×1632, keeping its proportions.
- A request over the server's 8 MB limit: the image is refused with the same 2 MB message, but the
  owner must re-enter the other fields because the server discards the whole submission.
- An uploaded file has an image extension but is damaged or cannot be converted to WebP: the save
  is refused with a message beside the image field and the project keeps its current image.
- The owner deletes every project: the public page shows the "projects will be added soon"
  message (User Story 1, scenario 4).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The site MUST store portfolio projects as persistent data that can be changed while
  the site is running. Each project has a title, description, image, a list of technology tags, a
  published state and a display order.
- **FR-002**: The public Projects page MUST list only published projects, ordered by display order
  (lowest first), with ties broken by the order in which projects were added.
- **FR-003**: For each listed project, the Projects page MUST show its image (described for screen
  readers by the project title), title, description and tags.
- **FR-004**: Project content MUST NOT be written out anywhere in the site's page templates or page
  logic; the only other copy allowed is the one-time initial data that creates the two existing
  projects.
- **FR-005**: After the initial data is loaded, the Projects page MUST show the same projects,
  wording, images and tags as before this change.
- **FR-006**: When there are no published projects, the Projects page MUST still load and show a
  short "projects will be added soon" message in place of the list.
- **FR-007**: A signed-in site owner MUST be able to list all projects (published and not), add a
  project, change any detail of a project, and delete a project, through private screens.
- **FR-008**: A saved add, edit or delete MUST be reflected on the public Projects page on the next
  page load.
- **FR-009**: Deleting a project MUST require an explicit confirmation step, and MUST remove the
  project and its uploaded image permanently.
- **FR-010**: Saving a project MUST be refused, with a message beside each problem field, when: the
  title is blank or exceeds 100 characters; the description is blank or exceeds 500 characters; a
  new project has no image; the image is not a JPEG, PNG or WebP file, is larger than 2 MB, or is larger than 4096 pixels on
  either side (planning amendment: keeps image processing within server memory, since the image
  is decoded before resizing); there
  are more than 10 tags or any tag exceeds 30 characters; or the display order is not a whole
  number from 0 to 999.
- **FR-011**: When editing, leaving the image empty MUST keep the current image; uploading a new
  one MUST replace it, and the replaced file MUST no longer be kept or served.
- **FR-011a**: Every accepted image MUST be saved and shown in WebP format, whatever format it was
  uploaded in. An image wider or taller than 1632 pixels MUST be scaled down proportionally so
  neither side exceeds 1632 pixels; smaller images keep their size and are never enlarged.
  If an image cannot
  be converted, the save MUST be refused with a message beside the image field and nothing
  changed.
- **FR-012**: Tags MUST be entered as a simple comma-separated list; surrounding spaces and empty
  entries MUST be dropped, and tags that differ only in letter case MUST be kept once.
- **FR-012a**: While typing tags, the owner MUST be offered the tags already used on any project
  as suggestions, using the existing spelling and letter case. Suggestions are a convenience: tags
  MUST still be enterable by typing alone, including on devices or browsers where suggestions do
  not appear. There is no separate screen for managing tags.
- **FR-013**: New projects MUST default to published, with a display order that places them after
  the existing projects.
- **FR-014**: Only signed-in site owners MUST be able to view or use the project admin screens;
  every attempt by anyone else MUST be sent to the sign-in page without showing or changing data.
- **FR-015**: The owner MUST be able to move between the company details screen and the Projects
  screen through a shared admin navigation.
- **FR-016**: After each successful add, edit or delete, the owner MUST see a confirmation message
  naming the project and what was done.
- **FR-017**: The project admin screens MUST NOT be indexed by search engines, and MUST display
  without horizontal scrolling from 320px wide upward, with tappable controls at least 44 by 44
  pixels.
- **FR-018**: Every page that shows projects MUST keep meeting the site's existing accessibility
  and image rules: images displayed in a fixed-shape frame with their width and height declared so
  the layout does not shift, images no larger than 1632 pixels on either side (FR-011a), and
  every image after the first loaded lazily.

### Key Entities

- **Project**: One piece of delivered work shown in the portfolio. Attributes: title,
  description, image, technology tags (zero to ten short labels), published state, display order,
  and when it was added and last changed. Any number may exist.
- **Tag**: A short technology label (for example "Laravel"). Tags have no data of their own
  beyond their text; the set of suggested tags is simply every distinct tag currently used on any
  project, so a tag used only by a deleted project is no longer suggested.
- **Project Image**: The picture shown for a project. Belongs to exactly one project; replacing or
  deleting the project removes the old picture.
- **Site Owner**: The existing signed-in owner from the company details feature. Any owner may
  manage all projects; there are no per-project permissions.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: The site owner can add a new project with an image and see it live on the Projects
  page in under 3 minutes, without involving a developer.
- **SC-002**: Editing or deleting an existing project takes the owner under 1 minute and is live on
  the next page load.
- **SC-003**: Portfolio content appears in 0 page templates or page logic (down from 2 projects
  written into the site today).
- **SC-004**: After the initial data is loaded, the Projects page shows the same 2 projects with
  identical wording, images and tags, confirmed by automated checks.
- **SC-005**: 100% of attempts to view, submit or delete through the project admin screens without
  signing in are refused.
- **SC-006**: 100% of invalid submissions listed in FR-010 are refused with a message beside the
  problem field, and leave stored projects unchanged.
- **SC-007**: The Projects page and project admin screens show 0 pixels of horizontal overflow at
  320px, 375px, 768px and 1280px widths.
- **SC-008**: With 20 published projects, the Projects page's median response time over 20
  requests stays within 10% of today's page on the same machine.

## Assumptions

- The maintainer's request for owner-editable, stored projects is the approval that constitution
  Principle I requires for adding database-backed content; the plan's Constitution Check records
  it.
- The two current projects ("School Management System" and "Healthcare Management System", with
  their current descriptions, images and tags) are approved and become the initial data. No
  invented projects are added (Principle V).
- The owner is responsible for making sure projects and their tags describe work the company
  actually delivered (Principle V); the site does not check this.
- The existing owner sign-in, sign-in rate limit and session-expiry handling from the company
  details feature are reused unchanged.
- Each project has exactly one image. Image galleries, videos, client names, live-site or
  case-study links, dates and per-project detail pages are out of scope; the Projects page remains
  a single list of cards, and cards do not link anywhere.
- Filtering or searching projects by tag on the public page is out of scope.
- Display order is set by typing a number; drag-and-drop reordering is out of scope.
- Images uploaded by the owner are stored by the site itself; no external image service is used.
- The site scales large images down automatically (FR-011a); the owner only needs to keep files
  at 2 MB or less. Raising the 2 MB limit is a separate decision (it also needs the server's
  upload limit raised).
- The two existing project images are also converted to WebP when they become the initial data,
  with no visible change to how they look.
- Deletion is permanent; there is no trash or undo, which is why a confirmation step is required.
- An audit history of who changed which project is out of scope.
- Only one language is supported.
- The Home page does not show projects today; featuring projects on it is a separate feature.

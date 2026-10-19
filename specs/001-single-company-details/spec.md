# Feature Specification: Single Source for Company Details

**Feature Branch**: `001-single-company-details`

**Created**: 2026-10-19

**Status**: Draft (amended 2026-10-19 during planning)

**Input**: User description: "Keep company name and contact details in one place shared by every
page and the layout (the gap against Principle V)." Planning amendment: "I want the company data
to be dynamic and save in a database, Site must be responsive and ready for mobile."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Visitors see consistent company details everywhere (Priority: P1)

A prospective client browses the Home, About, Projects and Contact pages. On every page, the
company name in the header, footer, browser tab title and page body is identical, and the email
address, phone number and street address on the Contact page are the approved, current values.

**Why this priority**: Inconsistent or stale company details lose leads and undermine trust. This
is the outcome the business actually cares about, and it is what the constitution's
"Truthful, Current Content" principle requires.

**Independent Test**: Visit each of the four public pages and confirm that every place the
company name appears shows the same value, and that the Contact page shows the approved email,
phone and address.

**Acceptance Scenarios**:

1. **Given** the site is live, **When** a visitor opens any of the four public pages, **Then**
   the company name in the header, footer, page title and body text is the same on all of them.
2. **Given** the site is live, **When** a visitor opens the Contact page, **Then** it shows
   the email `wenzel@au2m8.com`, the phone `+63 (919) 095-9251` and the address
   `Poblacion, Urdaneta City, Pangasinan, 2428 Philippines`.
3. **Given** the site is live, **When** a visitor compares the page text before and after this
   change, **Then** the wording is unchanged.

---

### User Story 2 - Site owner edits company details without a code change (Priority: P2)

The site owner signs in to a private admin screen, changes a company detail (for example a new
phone number), and saves. Every public page immediately shows the new value. No developer and no
redeploy are needed.

**Why this priority**: It removes the developer bottleneck for routine content changes and keeps
details current. It depends on Story 1's single source being in place.

**Independent Test**: Sign in as the site owner, change the phone number, save, then open the
Contact page and confirm the new number is shown and the old one is not.

**Acceptance Scenarios**:

1. **Given** the owner is signed in, **When** they open the company details screen, **Then** the
   form shows the current values of all company details.
2. **Given** the owner changes the phone number and saves, **When** any visitor opens the Contact
   page, **Then** it shows the new phone number.
3. **Given** the owner changes the company name and saves, **When** any public page is opened,
   **Then** the header, footer, page title and body all show the new name, and the old name
   appears nowhere on the page.
4. **Given** the owner submits an invalid value (for example a blank name or a malformed email),
   **When** they save, **Then** nothing is changed, the form shows what is wrong next to the
   field, and their other entries are kept.
5. **Given** a visitor is not signed in, **When** they try to open the company details screen,
   **Then** they are sent to the sign-in page and cannot view or change anything.
6. **Given** someone enters a wrong password repeatedly, **When** they exceed 5 attempts within a
   minute, **Then** further attempts are refused for a short period.

---

### User Story 3 - Visitors on phones get a fully usable site (Priority: P2)

A prospective client opens the site on a phone. Every page fits the screen without sideways
scrolling, the navigation is reachable through a compact menu, text is readable without zooming,
and they can tap the phone number to call or the email address to start an email.

**Why this priority**: Many first visits come from phones; a broken mobile layout loses the lead
as surely as a wrong phone number.

**Independent Test**: Open all four public pages and the admin screens at 320px, 375px and 768px
widths and confirm there is no horizontal scrolling, the menu opens and every link works, and
tapping the phone number and email starts a call and an email.

**Acceptance Scenarios**:

1. **Given** a screen 320px wide, **When** a visitor opens any public page, **Then** no content
   is cut off and the page does not scroll sideways.
2. **Given** a screen narrower than a tablet, **When** a visitor taps the menu control, **Then**
   all navigation links appear and each can be tapped comfortably.
3. **Given** a phone, **When** the visitor taps the phone number on the Contact page, **Then**
   the phone offers to call that number; tapping the email opens a new email to that address.
4. **Given** a desktop screen, **When** a visitor opens any page, **Then** the navigation shows
   all links inline as it does today.

---

### Edge Cases

- The company details record is missing (for example on a fresh install before setup): public
  pages must still load, showing the site's configured name and omitting missing details rather
  than failing.
- The company name contains characters with special meaning in web pages (for example `&` or
  `<`): it must be displayed as literal text.
- The copyright year in the footer must keep updating to the current year automatically; it is
  not a stored company detail.
- Page-specific wording that includes the company name (for example "About Au2m8") must take the
  name from the single source.
- The owner saves without changing anything: the save succeeds and nothing visible changes.
- Very long values (a long address or email) must wrap on narrow screens instead of overflowing.
- A signed-in owner's session expires while editing: on save nothing is changed, and they are
  taken to the sign-in page with the message "Your session expired. Please sign in again."
  instead of an error page. The same applies when the sign-in form itself has expired.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The site MUST store each company-wide detail exactly once, as persistent data that
  can be changed while the site is running: company name, tagline, short description, founding
  year, mission statement, contact email, contact phone and postal address.
- **FR-002**: The header, footer, every page's browser tab title and every page body MUST take
  the company name from that stored record.
- **FR-003**: The Contact page MUST take the email, phone and address from the stored record.
- **FR-004**: The Home page MUST take the tagline and description, and the About page MUST take
  the founding year and mission, from the stored record.
- **FR-005**: Company-wide detail values MUST NOT be written out anywhere in the site's page
  templates or page logic; the only other copy allowed is the one-time initial data that creates
  the record.
- **FR-006**: The visible wording of all four public pages MUST be identical to its state before
  this change. Layout may change only as needed to meet FR-012 to FR-015.
- **FR-007**: Every public page MUST continue to load successfully, and each page MUST have an
  automated check that it shows the company details it is responsible for.
- **FR-008**: A signed-in site owner MUST be able to view and change every company detail through
  a private screen, and a saved change MUST appear on every public page on the next page load.
- **FR-009**: Only signed-in site owners MUST be able to view or change the company details
  screen. There MUST be no public sign-up; owner accounts are created by a developer.
- **FR-010**: Saving MUST be refused, with a message beside each problem field, when: any detail
  is blank; the name exceeds 100 characters; the tagline exceeds 150; the description, mission or
  address exceed 500; the email is not a valid address; the phone contains anything other than
  digits, spaces, `+`, `-`, `.`, `(` and `)`; or the founding year is not between 1900 and the
  current year.
- **FR-011**: Sign-in attempts MUST be limited to 5 failed attempts per minute for the same email
  address and network address.
- **FR-012**: Every page, public and admin, MUST display without horizontal scrolling from 320px
  wide upward.
- **FR-013**: On screens narrower than 640px the navigation MUST collapse behind a menu control
  that works with touch, keyboard and without scripts; on wider screens all links show inline.
- **FR-014**: Tappable controls (navigation links, menu control, contact links, form buttons)
  MUST be at least 44 by 44 pixels.
- **FR-015**: The phone number MUST be a tap-to-call link and the email a tap-to-email link.
- **FR-016**: The admin screens MUST NOT be indexed by search engines.

### Key Entities

- **Company Profile**: The organisation's public identity. Attributes: name, tagline,
  description, founding year, mission, contact email, contact phone, postal address, and when it
  was last changed. There is exactly one Company Profile for the site.
- **Site Owner**: A person allowed to sign in and edit the Company Profile. Attributes: name,
  email, password. Created by a developer; any number may exist, all with the same permission.
- **Page-specific content** (out of this feature's scope): services, team members and portfolio
  projects. These belong to individual pages and stay where they are.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Each company-wide detail value appears in exactly 1 stored place, and in 0 page
  templates or page logic (down from up to 6 places for the company name today).
- **SC-002**: The site owner can change a company detail and see it live in under 1 minute,
  without involving a developer.
- **SC-003**: All 4 public pages load successfully and show wording identical to the pre-change
  version, confirmed by automated checks.
- **SC-004**: 100% of attempts to open or submit the company details screen without signing in
  are refused.
- **SC-005**: All public and admin pages show 0 pixels of horizontal overflow at 320px, 375px,
  768px and 1280px widths.
- **SC-006**: Public pages respond no slower than before the change: on the same machine, each
  page's median response time over 20 requests stays within 10% of the pre-change baseline.

## Assumptions

- The current values in the site are the approved values: name `Au2m8`, email
  `wenzel@au2m8.com`, phone `+63 (919) 095-9251`, address
  `Poblacion, Urdaneta City, Pangasinan, 2428 Philippines`, founded `2010`, plus the existing
  tagline, description and mission text. These become the initial stored record.
- The maintainer approved storing company details as editable data and adding a private admin
  screen (2026-10-19), which constitution Principle I requires.
- All site owners have the same permission; roles are out of scope. Owner passwords are at least
  12 characters.
- Password reset by email, two-factor sign-in and an audit history of changes are out of scope;
  a developer resets a forgotten password.
- Only one language is supported; translation of company details is out of scope.
- Services, team members and portfolio projects are page-specific and not stored here; making
  them editable is a separate feature.
- Showing contact details in the footer and adding structured business data for search engines
  are separate improvements and out of scope.
- The sign-in screen is reached by its address; it is not linked from the public navigation.

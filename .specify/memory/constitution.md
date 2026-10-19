# Au2m8 Site Constitution

## Core Principles

### I. Server-Rendered Simplicity

The site is a server-rendered Laravel application built on Blade views and Tailwind CSS.

- Pages MUST render meaningful content without client-side JavaScript; JavaScript is limited to
  progressive enhancement.
- SPA frameworks, Livewire, Inertia, or a database-backed CMS MUST NOT be introduced unless a
  feature specification shows that static or controller-supplied content cannot meet the need,
  and the maintainer approves the change.
- New abstractions (services, repositories, packages) MUST be justified by a current, concrete
  need; speculative structure is rejected (YAGNI).

**Rationale**: This is a marketing site of a few pages. Keeping the stack small keeps it fast,
cheap to host, and easy for any team member to change.

### II. Laravel Conventions First

- Code MUST follow the existing conventions of the application and the Laravel Boost guidelines in
  `CLAUDE.md` / `AGENTS.md`, including the rule files in `.ai/rules` when that directory exists.
- Routes MUST be named, and internal links MUST be generated with `route()`.
- New files MUST be created with `php artisan make:*` where a generator exists.
- PHP MUST use explicit parameter and return types, constructor property promotion, and curly
  braces for every control structure.
- Layout and navigation MUST be shared through `resources/views/layouts/app.blade.php` or Blade
  components; markup MUST NOT be copied between pages.

**Rationale**: Predictable structure lets humans and AI agents make safe changes quickly.

### III. Every Page Is Tested (NON-NEGOTIABLE)

- Every public route MUST have a Pest feature test that asserts a successful response and the
  key content it is responsible for (for example the heading, the project titles, or the contact
  details).
- Any change to a route, a controller method, or the data passed to a view MUST add or update
  the matching test in the same change.
- Bug fixes MUST include a test that fails before the fix and passes after it.
- Tests MUST be created with `php artisan make:test --pest` and run with
  `php artisan test --compact`.

**Rationale**: Feature tests are cheap for a small site and catch broken pages, missing
variables, and regressions before visitors see them.

### IV. Accessible, Responsive, Fast

- Pages MUST meet WCAG 2.1 AA: semantic landmarks (`nav`, `main`, `footer`), one `h1` per page,
  logical heading order, alt text for meaningful images, visible focus states, and text contrast
  of at least 4.5:1.
- Layouts MUST work from a 320px-wide viewport upward with no horizontal scrolling, and the
  navigation MUST stay usable on small screens.
- Images MUST be sized for their display size and lazy-loaded below the fold. Assets MUST be
  built through Vite; external scripts MUST NOT be added without approval.
- Every page MUST set a unique, descriptive `<title>`.

**Rationale**: The site is often the first thing a prospective client sees. It has to work for
everyone and on every device.

### V. Truthful, Current Content

- Company details (name, contact email, phone, address), team members, services, and portfolio
  projects MUST be accurate and approved by the business before publication.
- Placeholder, lorem-ipsum, or invented content (fake testimonials, clients, metrics, or
  projects) MUST NOT reach the `main` branch.
- Content that appears on more than one page (for example the company name or contact details)
  MUST come from a single source rather than being repeated in several places.
- Portfolio entries MUST describe work the company actually delivered, and their technology tags
  MUST match what was used.

**Rationale**: A company website makes public claims. Wrong contact details lose leads, and
invented claims damage trust.

## Technology & Dependency Constraints

- **Runtime**: PHP 8.4 (minimum allowed by `composer.json`: ^8.3) on Laravel 13. Locally the site
  is served by Laravel Herd; agents MUST NOT start their own server.
- **Frontend**: Blade, Tailwind CSS v4 through `@tailwindcss/vite`, and Vite.
- **Testing and style**: Pest 5 for tests and Laravel Pint for PHP formatting.
- Adding, removing, or upgrading a Composer or npm dependency MUST be approved by the maintainer
  first and recorded in the commit message.
- New top-level directories MUST NOT be created without approval.
- Secrets and environment-specific values MUST live in `.env` and MUST NOT be committed.
- Before relying on a package API, contributors MUST confirm the installed version
  (`composer show`, `package.json`) and consult the matching documentation.

## Development Workflow & Quality Gates

- Non-trivial features follow the Spec Kit flow: `/speckit-specify` → `/speckit-plan` →
  `/speckit-tasks` → `/speckit-implement`. Each plan MUST include a Constitution Check against
  Principles I–V.
- Before a change is merged, all of these MUST pass:
  1. `vendor/bin/pint --dirty --format agent` reports no remaining issues.
  2. The targeted tests pass, followed by the full suite (`php artisan test --compact`).
  3. `npm run build` succeeds when views, CSS, or JS changed.
  4. The affected pages were checked visually at mobile and desktop widths.
- Commits MUST be small and focused, with messages that describe the change (for example
  `update tagline in home view for clarity`).
- Documentation files MUST only be created when explicitly requested.

## Governance

- This constitution overrides informal practices and ad-hoc preferences. If it conflicts with
  agent guidance files (`CLAUDE.md`, `AGENTS.md`), the stricter rule applies until the conflict
  is resolved by an amendment.
- **Amendments**: proposed through `/speckit-constitution`, reviewed by the maintainer, and
  committed with a message of the form `docs: amend constitution to vX.Y.Z (<summary>)`.
  Amendments that change gates MUST state how existing code and open specs will be brought into
  compliance.
- **Versioning**: semantic versioning. MAJOR for removing a principle or redefining it in a way
  that is not backward compatible; MINOR for a new principle or section, or materially expanded
  guidance; PATCH for clarifications and wording fixes.
- **Compliance**: every plan and every review MUST check the change against this constitution.
  Exceptions MUST be written down with a justification in the plan's Complexity Tracking
  section. Compliance is reviewed again at each MINOR or MAJOR amendment.

**Version**: 1.0.0 | **Ratified**: 2026-10-19 | **Last Amended**: 2026-10-19

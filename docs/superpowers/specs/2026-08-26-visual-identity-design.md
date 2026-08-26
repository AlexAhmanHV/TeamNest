# TeamNest Visual Identity & Landing Page — Design

## Overview

TeamNest is a Laravel 12 multi-tenant mini-SaaS for team/project management (workspace-scoped RBAC, invites, Kanban board, activity/audit logs with before/after diffs, notifications, analytics, API tokens) being turned into a portfolio case, alongside VenueFlow on alexahman.se.

Right now the app has no visual identity at all: `welcome.blade.php` is the untouched default Laravel/Breeze "Let's get started" starter page, and every real screen (dashboard, projects, Kanban board, settings, etc.) is unstyled default Breeze — plain white `bg-white shadow-sm rounded-lg` cards, no color, no typography treatment, no branding. This spec covers giving it a real landing page and a real, consistent visual identity across every screen.

## Goals

- A real landing page that pitches the product's actual differentiators (workspace isolation, audit trails, Kanban) instead of the default Laravel scaffold.
- A distinct visual identity from VenueFlow (its sibling portfolio case) — same design principles, different palette — so the two don't read as the same template reskinned.
- Every one of the app's ~40 views restyled onto one consistent token/component system, not just the highest-traffic screens.
- The underlying functionality (RBAC, Kanban drag-and-drop, notifications, etc.) is unchanged — this is a visual-layer pass only.

## Non-goals

- No new features or behavior changes. Every interaction, route, and validation rule stays exactly as it is today.
- No dark/light mode toggle — dark is the only theme, matching the established portfolio taste.
- No redesign of the underlying data model, RBAC logic, or API.
- No changes to the default Laravel auth *logic* (Breeze's controllers) — only the *templates* they render.

## Visual identity foundation

**Palette:** dark base (`#0a0a0f`-range near-black, not pure black), one accent — electric indigo/blue (`#4f6bff`-range) — used sparingly: nav active states, primary buttons, the one CTA per page, key status highlights. Everything else stays grayscale (slate/zinc grays). This deliberately differs from VenueFlow's warmer near-black + red/green, so the two portfolio cases read as different products.

**Type:** heavy scale contrast between display headings (`text-5xl`–`text-7xl`, font-black) and quiet metadata labels (`text-xs uppercase tracking-widest`, muted gray) — applied consistently from the landing hero down to every in-app page header, workspace name, status badge, and timestamp.

**Layout rhythm:** row-based lists with hairline dividers (`divide-y`) for anything that's fundamentally a list — activity log, notifications, task lists, member lists — instead of boxed card grids. Cards are reserved for content that's genuinely a grouped unit (a project summary tile, a single Kanban column, a stat block).

**Implementation mechanism:** a small set of custom Tailwind component classes defined once via `@apply` in `resources/css/app.css` (mirroring the pattern already proven in VenueFlow's `vf-*` classes), plus color/font tokens added to `tailwind.config.js`. Every view composes from this shared vocabulary — changing a definition once changes it everywhere. Concretely, at minimum:

- `tn-card` — the grouped-content container (replaces ad-hoc `bg-white shadow-sm rounded-lg p-6`)
- `tn-btn-primary` / `tn-btn-secondary` — buttons (replaces Breeze's `<x-primary-button>` styling)
- `tn-input` — form fields (replaces Breeze's `<x-text-input>` styling)
- `tn-page-header` — the page title + description + optional right-aligned action block used at the top of every inner page
- `tn-badge` — small status/role labels (e.g. `admin`/`member`, task status, notification unread dot)
- `tn-nav-link` — top nav links, with an accent-underline active state (not a filled pill)

These replace the *styling* inside the existing Breeze Blade components (`primary-button.blade.php`, `text-input.blade.php`, etc.) where a like-for-like component already exists, rather than introducing a parallel component system — so call sites elsewhere in the app (`<x-primary-button>`, `<x-text-input>`) don't need to change, only what they render.

## Landing page (`welcome.blade.php`)

Single page, unauthenticated, structured like VenueFlow's demo hub:

1. **Hero:** headline "Projects, tracked. Nothing lost." at full display scale, one-line sub-copy naming the real differentiators (multi-tenant workspaces, full audit trail with before/after diffs), one primary CTA (accent-colored) linking to `/login`, one quieter secondary link to `/register`. No dedicated demo-login route exists (confirmed — nothing like VenueFlow's `/demo-login` in `routes/web.php`), but `DatabaseSeeder` does seed two demo accounts (`admin@example.com` / `member@example.com`, both password `password`, in a `demo-workspace`) — the hero displays these credentials directly (small, quiet text under the CTA), matching the pattern VenueFlow uses on its demo hub, so a visitor can log in immediately without registering.
2. **Proof row:** three quiet callouts directly under the hero — Kanban board, audit trail with diffs, workspace isolation — each a short label + one supporting line, no icon-in-a-box grid.
3. **How it works:** a 3-step row ("Skapa arbetsyta → Bjud in teamet → Följ arbetet"), same structural pattern as VenueFlow's "1/3, 2/3, 3/3" steps.
4. **Footer:** minimal — product name, "Byggt av alexahman.se" link.

The existing `@auth`/`@guest` conditional nav (Dashboard link if logged in, Login/Register if not) is preserved, restyled onto the new token set.

## App shell

- `layouts/navigation.blade.php`: restyled as a dark top nav. Workspace name (or app name if no workspace selected) in a bold slot, page links (Dashboard, Projects, Members, Activity, Notifications, Analytics, Settings) as quiet uppercase `tn-nav-link`s, active link gets the accent underline. The existing responsive/mobile collapse behavior (Breeze's Alpine-based dropdown) is preserved, restyled.
- `layouts/app.blade.php`: dark page background, content area uses `tn-page-header` at the top of the slot region consistently.
- `layouts/guest.blade.php`: dark background matching the landing page, used by all six auth screens (login, register, forgot/reset password, confirm password, verify email).

## Applying the system to every view

Every view in the app (full list below) gets restyled onto the token/component set above — swapping ad-hoc Breeze utility classes for `tn-*` classes and the shared layouts, with no change to the Blade logic, routes, or backend behavior.

**Layouts & shared components:** `layouts/app.blade.php`, `layouts/guest.blade.php`, `layouts/navigation.blade.php`, and the reusable components (`primary-button`, `secondary-button`, `danger-button`, `text-input`, `input-label`, `input-error`, `dropdown`, `dropdown-link`, `nav-link`, `responsive-nav-link`, `modal`, `application-logo`, `auth-session-status`).

**Auth:** `auth/login.blade.php`, `auth/register.blade.php`, `auth/forgot-password.blade.php`, `auth/reset-password.blade.php`, `auth/confirm-password.blade.php`, `auth/verify-email.blade.php`.

**Core app:** `welcome.blade.php`, `dashboard.blade.php`, `workspaces/index.blade.php`, `workspaces/settings.blade.php`, `members/index.blade.php`, `invitations/accept.blade.php`.

**Projects & tasks:** `projects/index.blade.php`, `projects/show.blade.php`, `projects/trash.blade.php`, `tasks/index.blade.php` (the Kanban board), `tasks/show.blade.php`, `tasks/trash.blade.php`.

**Operational:** `activity/index.blade.php`, `notifications/index.blade.php`, `analytics/index.blade.php`, `tokens/index.blade.php`.

**Profile:** `profile/edit.blade.php` and its three partials (`update-profile-information-form`, `update-password-form`, `delete-user-form`).

The Kanban board (`tasks/index.blade.php`) is the most structurally involved view — it has real interactive JS (drag-and-drop, optimistic UI) that must keep working exactly as-is; only its visual classes change, not its Alpine/JS logic or the columns' underlying data structure.

## Testing

This is a visual-only change with no behavior change, so testing is manual/visual rather than automated:

- Every existing Pest feature test must still pass unmodified (they test behavior, not markup, so they shouldn't be affected — but this is the regression check).
- Manual pass through each screen in a browser: landing page, full auth flow, dashboard, creating/viewing a project, the Kanban board (drag-and-drop still works), activity log, notifications, analytics, workspace settings, member management, invitations, API tokens, profile.
- Responsive check at mobile width for the nav and landing page at minimum.

## Future ideas (not in this spec)

- Deploying TeamNest live (a separate item from the user's 5-idea list).
- Adding portfolio screenshots once this design pass ships.
- A dedicated empty-state illustration system (currently out of scope — empty states get the new token styling but no bespoke artwork).

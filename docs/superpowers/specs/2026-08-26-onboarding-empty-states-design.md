# Onboarding & Empty States — Design

## Overview

TeamNest's dashboard and several key screens currently give a new user no guidance. Registration never creates a workspace, so a fresh signup lands on `/dashboard` with `workspace = null` (the route sits outside the `workspace` middleware group, so it doesn't redirect), which already shows "Select or create a workspace to continue." — but still renders all 6 nav-link cards below it, most of which bounce the user to an unexplained blank `/workspaces` page when clicked. Once a workspace exists, `/projects` and `/activity` render a completely blank table/list with zero rows and no explanation if nothing has been created yet.

This spec is the first of three "next level" feature passes agreed with the user (onboarding → search → real-time), sequenced first because it fixes the weakest first impression and every later feature lands in a less bare shell as a result.

## Goals

- A first-time (zero-workspace) user sees a clear welcome message and a single obvious next action instead of a bare nav grid.
- A workspace with zero projects/activity shows a short explanatory message instead of an empty table/list.
- A workspace that does have data gets a real dashboard: task stats (matching Analytics' numbers) plus a short recent-activity list, instead of only a 6-card link grid.
- All of this is view-layer plus light controller work — no new models, migrations, or behavior changes to existing features.

## Non-goals

- No guided multi-step setup wizard (rejected in favor of better empty states — faster, lower risk, matches the app's existing minimal-chrome aesthetic).
- No changes to `/members` (never actually empty — the workspace creator is always a member) or to Kanban board empty columns (normal Kanban UX, not a first-run problem).
- No changes to workspace creation logic, project creation logic, or any data model.
- No bespoke empty-state illustrations — text + one CTA only, consistent with the visual-identity spec's existing "no bespoke empty-state artwork" call.

## Screens changed

### 1. Dashboard — no-workspace state

Route: `/dashboard`. Currently a closure in `routes/web.php` returning `view('dashboard', ['workspace' => $currentWorkspace->forUser()])`. Becomes a small `DashboardController@index` method (still injecting `CurrentWorkspace`) so it can also compute stats for the populated case (see #4).

When `$workspace` is null, `dashboard.blade.php` renders only:
- Headline: "Create your first workspace"
- Body: "Workspaces keep your projects, teammates, and activity isolated from other teams."
- One primary-button CTA linking to `route('workspaces.index')`

The existing 6-card nav grid is NOT rendered in this state (it's not useful yet — every link redirects to `/workspaces` anyway via the `workspace` middleware).

### 2. `/workspaces` — zero-workspace state

`WorkspaceController@index` already passes `$workspaces` (the user's workspace list). When `$workspaces->isEmpty()`, `workspaces/index.blade.php` shows, above the existing "Your Workspaces" card:
- Headline: "No workspaces yet"
- Body: "Workspaces keep your projects, teammates, and activity isolated from other teams." (same line as the dashboard state, for consistency)

The existing "Your Workspaces" list card and "Create Workspace" form stay exactly as they are — the create form itself is the CTA, no separate button needed. When `$workspaces` is non-empty, this new block doesn't render at all (existing behavior unchanged).

### 3. `/projects` — zero-project state

`ProjectController@index` already passes `$projects` (paginated). When `$projects->isEmpty()`, the second `tn-card` in `projects/index.blade.php` (currently a bare `tn-table` with no rows) shows instead:
- Headline: "No projects yet"
- Body: "Create a project to start organizing tasks."

The "Create Project" form above stays unchanged and serves as the CTA. When `$projects` is non-empty, the table renders exactly as it does today.

### 4. `/activity` — zero-activity state

`ActivityController@index` already passes `$activities` (paginated). When `$activities->isEmpty()`, `activity/index.blade.php` shows, in place of the (currently blank) `<ul>`:
- Headline: "Nothing here yet"
- Body: "Actions your team takes will show up here."

No CTA — this is pure information, matching the read-only nature of an activity feed.

### 5. Dashboard — populated state

When `$workspace` is not null, `DashboardController@index` additionally computes the same 4 numbers `AnalyticsController@index` already computes for that workspace (reusing the identical query pattern, not extracted into a shared service — the query is ~10 lines and used in exactly two places):
- Total tasks
- Done
- Overdue
- Completion rate (%)

Plus the 5 most recent `Activity` rows for the workspace (same shape `ActivityController` already queries, just `->limit(5)` instead of paginated).

`dashboard.blade.php` renders, above the existing 6-card nav grid (which stays exactly as-is in this state):
- A 4-stat row, visually matching the existing stat-tile pattern already used in `analytics/index.blade.php` (`tn-card` per stat, `text-xs uppercase tracking-wider text-slate-500` label + `text-3xl font-extrabold` value).
- A "Recent Activity" `tn-card` listing the 5 items (actor + action + relative timestamp, same line format as `activity/index.blade.php`), with a "View all" link to `route('activity.index')`.

If the workspace exists but has zero tasks (a workspace with no projects/tasks yet), the stats row still renders with all zeros — no separate empty-state needed here, since zero is a meaningful, self-explanatory value for a counter (unlike the list screens in #2-4, where an empty list looks like a rendering bug rather than a real "0" state).

## Testing

This introduces real conditional logic (empty vs. populated branches, new stat computation), so normal TDD applies — unlike the prior visual-identity pass, which was pure CSS/markup and explicitly excluded new tests.

- Feature tests: dashboard shows the no-workspace welcome state when the user has no workspace; dashboard shows correct stat numbers and recent-activity items when the workspace has data; `/workspaces`, `/projects`, `/activity` each show their empty-state copy when their respective collection is empty, and render their normal content unchanged when it isn't.
- Full `php artisan test` run as the regression check on every task, per the project's established pattern — same baseline (currently 33 passed, 102 assertions) plus the new tests this feature adds.
- Manual browser pass: register a brand-new account and walk the full empty-workspace → create workspace → create project → create task path, confirming each empty state resolves correctly as data is added.

## Future ideas (not in this spec)

- Global search (Cmd+K) — next feature in the agreed sequence.
- Real-time updates (Laravel Reverb) — third feature in the agreed sequence; will make the dashboard's recent-activity list and stats live-updating once built, but that's out of scope here.
- A guided multi-step setup wizard, if the empty-states approach turns out to feel insufficient once live.

# Global Search (Cmd+K) — Design

## Overview

TeamNest has no way to jump directly to a project, task, or teammate — every navigation is via the top nav's list pages (Projects, Members) or by clicking through from the Kanban board. This spec adds a command-palette-style global search: Cmd/Ctrl+K (or a visible search button, for discoverability and mobile) opens a modal where typing live-filters across projects, tasks, and members in the current workspace, and Enter jumps straight to the selected result.

This is the second of three "next level" feature passes agreed with the user (onboarding/empty-states → search → real-time updates), sequenced after onboarding because it's self-contained and independent, and before real-time because it doesn't need the broadcasting infrastructure real-time will add.

## Goals

- A logged-in user can open a search palette from anywhere in the app (keyboard shortcut or a visible button) and find a project, task, or teammate by typing part of its name.
- Results update live as the user types (debounced), grouped by type, navigable by keyboard (arrows + Enter) or mouse click.
- Results are strictly scoped to the user's current workspace — no cross-tenant leakage, matching the app's existing tenancy-isolation guarantee everywhere else.
- Member results land the user on the exact matched row (scrolled into view, briefly highlighted), not just a generic list page — matching the precision of project/task results, which link straight to that entity's own page.

## Non-goals

- No fuzzy/typo-tolerant matching or a dedicated search engine (Scout, Meilisearch, etc.) — this is a small, single-tenant-at-a-time dataset; a plain `LIKE` query is the right tool, not an under-used dependency.
- No search of Activity log entries, Notifications, or any other entity — scoped to Projects, Tasks, and Members only, per the user's explicit choice.
- No changes to existing list pages' own search/filter UI (e.g. `TaskFiltersTest`'s existing status/assignee/overdue filtering on `/projects/{project}/tasks`) — this is a separate, global "jump to X" tool, not a replacement for in-page filtering.
- No persistence of search history or recent results.

## Backend: search endpoint

One new route, `GET /search`, name `search`, inside the existing `auth`/`verified`/`workspace` middleware group (same group `/members`, `/projects`, etc. already use) — so it 404s cleanly via the existing `EnsureCurrentWorkspace` middleware if no workspace is selected, consistent with every other workspace-scoped route in the app.

`SearchController::search(Request $request, CurrentWorkspace $currentWorkspace): JsonResponse`:

```php
$workspace = $currentWorkspace->requireForUser();
$q = trim($request->string('q'));

if ($q === '' || mb_strlen($q) < 2) {
    return response()->json(['projects' => [], 'tasks' => [], 'members' => []]);
}

return response()->json([
    'projects' => Project::where('workspace_id', $workspace->id)
        ->where('name', 'like', "%{$q}%")->limit(5)->get(['id', 'name']),
    'tasks' => Task::whereHas('project', fn ($p) => $p->where('workspace_id', $workspace->id))
        ->where('title', 'like', "%{$q}%")->limit(5)->get(['id', 'title']),
    'members' => $workspace->users()->where('name', 'like', "%{$q}%")->limit(5)->get(['users.id', 'users.name']),
]);
```

- A 2-character minimum avoids returning noisy single-letter matches.
- Each category is capped at 5 results — a palette, not a full search-results page.
- Member search deliberately does **not** use `MemberController`'s `manageMembers` authorization gate — finding a teammate's name to jump to is not a member-management action; any workspace member (not just admins) can search for any other member.
- Soft-deleted projects/tasks are excluded automatically (Eloquent's default global scope on both models already excludes `deleted_at IS NOT NULL` rows — confirmed by `SoftDeleteRestoreTest.php`'s existing coverage).

## Frontend: the palette

New file `resources/js/search-palette.js`, registered as `Alpine.data('searchPalette', ...)` in `resources/js/app.js` before `Alpine.start()` — following the same pattern the Kanban board (`tasks/index.blade.php`'s inline `kanbanBoard()` function) already establishes for non-trivial Alpine components in this app.

Mounted once in `layouts/navigation.blade.php`, alongside the existing nav markup:

- **Trigger:** a small search-icon `<button>` in the nav (visible on all viewport sizes, unlike the desktop-only nav links), plus a global `keydown` listener (`(e.metaKey || e.ctrlKey) && e.key === 'k'`, with `e.preventDefault()`) that opens the same palette. Both are gated on `session('current_workspace_id')` being set — mirroring how the notifications unread-count badge already scopes itself — so the trigger simply doesn't render on `/dashboard` or `/workspaces` when no workspace is selected yet.
- **Modal:** reuses the existing `<x-modal>` Blade component (already used for the delete-account confirmation on the profile page) rather than inventing new modal chrome.
- **Input & results:** one text input; on each keystroke, a 200ms-debounced `fetch('/search?q=' + encodeURIComponent(value))`; results render under three `tn-*`-styled group headings (Projects / Tasks / Members), each row styled like the app's existing `tn-row` list pattern.
- **Keyboard navigation:** Up/Down arrows move a highlighted index across the flattened (all three groups concatenated) result list; Enter navigates `window.location` to the highlighted result's URL; Escape closes the palette without navigating.
- **Result URLs:**
  - Project → `route('projects.show', $project)`
  - Task → `route('tasks.show', $task)`
  - Member → `route('members.index') . '#member-' . $member->id`

## Member result highlight

`resources/views/members/index.blade.php`'s existing member-row `<tr>` (in the "Members" table) gets `id="member-{{ $member->id }}"` added — a one-line change, no other markup affected.

A small inline script on that same page (added via an `x-data`/`x-init` block on the page's outer wrapper, consistent with the profile page's existing `x-init="setTimeout(...)"` pattern for its "Saved." message) checks `location.hash` on load: if it matches `#member-{id}`, scroll that row into view (`scrollIntoView({block: 'center'})`) and add a `bg-brand-500/10` class to it for 2 seconds via `setTimeout`, then remove it. No new dependency — same technique already used elsewhere in the app for transient UI state.

## Testing

- **`tests/Feature/SearchTest.php`** (new): the search endpoint returns matching projects/tasks/members scoped to the current workspace; returns empty arrays for a query with no matches; excludes results from a *different* workspace the user also belongs to (tenancy isolation is a first-class existing concern in this app — `TenancyIsolationTest.php` already covers it for other endpoints, so this is a real, testable risk, not a formality); enforces the 2-character minimum; excludes soft-deleted projects/tasks.
- **No automated test for the keyboard shortcut, the debounced fetch, or the scroll-and-highlight behavior** — these are browser-only JS interactions with no PHP surface to assert on. Verified manually in-browser instead, the same way the Kanban board's drag-and-drop was verified by hand in the earlier visual-identity work (dispatching real `DragEvent`s / clicking through the actual flow) rather than via PHPUnit.
- Full `php artisan test` run as the regression check after the task, per this project's established pattern. Baseline before this feature: 43 passed, 137 assertions.

## Future ideas (not in this spec)

- Real-time updates (Laravel Reverb) — third and final feature in the agreed sequence.
- Recent-searches / result ranking by recency, if the palette sees real use once deployed.
- Expanding search to Activity log entries or Notifications, if the 3-entity scope turns out to feel incomplete.

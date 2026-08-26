# Global Search (Cmd+K) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a logged-in user open a command palette (Cmd/Ctrl+K or a visible button) from anywhere in the app and jump straight to a project, task, or teammate by typing part of its name.

**Architecture:** One new JSON search endpoint (`SearchController@search`) queries projects/tasks/members scoped to the current workspace with simple `LIKE` matching. One new Alpine.js component (`resources/js/search-palette.js`) drives a modal (reusing the existing `<x-modal>` component) mounted in the nav, with a debounced fetch on each keystroke and arrow-key/Enter navigation. A small, independent addition gives member results a precise landing spot (scroll-to-and-highlight a specific row) instead of a generic list page.

**Tech Stack:** Laravel 12, Blade, Alpine.js (already the only JS framework in this app — see `resources/js/app.js`), PHPUnit (`Tests\TestCase`, not Pest), existing `tn-*` Tailwind design-system classes and the existing `<x-modal>` component (no new component classes, no new dependencies).

## Global Constraints

- No search engine dependency (Scout, Meilisearch, etc.) — plain Eloquent `LIKE` queries only, per the spec's explicit non-goal.
- Search is scoped to Projects, Tasks, and Members only — no Activity log, no Notifications.
- Results are strictly scoped to the current workspace (via `CurrentWorkspace::requireForUser()`) — tenancy isolation is a first-class existing concern in this app (see `tests/Feature/TenancyIsolationTest.php`) and must hold here too.
- 2-character minimum query length; 5 results per category maximum.
- Member search does NOT use `MemberController`'s `manageMembers` authorization gate — any workspace member can search for any other member.
- The search trigger (button + keyboard shortcut) only renders when `session('current_workspace_id')` is set, matching how the notifications unread-count badge already scopes itself in `layouts/navigation.blade.php`.
- No automated tests for the keyboard shortcut, debounced fetch, or scroll-and-highlight JS behavior — these have no PHP surface to assert on and are verified manually in-browser, consistent with how this app's Kanban drag-and-drop was verified. PHP feature tests cover only the `SearchController` endpoint itself.
- Full `php artisan test` run after every task must return the same-or-growing pass count with zero failures. Current baseline before this plan: 43 passed, 137 assertions.

---

### Task 1: Search endpoint

**Files:**
- Create: `app/Http/Controllers/SearchController.php`
- Modify: `routes/web.php` (add import + one route inside the existing `Route::middleware('workspace')->group(...)` block)
- Test: `tests/Feature/SearchTest.php`

**Interfaces:**
- Produces: `SearchController::search(Request $request, CurrentWorkspace $currentWorkspace): JsonResponse`, route name `search`, returning `{"projects": [{"id": int, "name": string}, ...], "tasks": [{"id": int, "title": string}, ...], "members": [{"id": int, "name": string}, ...]}`.
- Consumes: `App\Services\CurrentWorkspace::requireForUser(): Workspace` (existing), `App\Models\Project` (existing, has `workspace_id`, `name`, and `SoftDeletes`), `App\Models\Task` (existing, has `title`, a `project()` relation, and `SoftDeletes`), `App\Models\Workspace::users()` (existing `belongsToMany` relation to `User`, used the same way `MemberController::index` already uses it).

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/SearchTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_matching_projects_and_tasks(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $project = Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $user->id, 'name' => 'Client Onboarding']);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by_user_id' => $user->id, 'title' => 'Client kickoff call']);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->getJson(route('search', ['q' => 'client']));

        $response->assertOk();
        $response->assertJsonFragment(['id' => $project->id, 'name' => 'Client Onboarding']);
        $response->assertJsonFragment(['id' => $task->id, 'title' => 'Client kickoff call']);
    }

    public function test_search_returns_matching_members(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $member = User::factory()->create(['name' => 'Grace Hopper']);
        $workspace->users()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->getJson(route('search', ['q' => 'grace']));

        $response->assertOk();
        $response->assertJsonFragment(['id' => $member->id, 'name' => 'Grace Hopper']);
    }

    public function test_search_returns_empty_arrays_for_no_matches(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->getJson(route('search', ['q' => 'zzzzz']));

        $response->assertOk();
        $response->assertExactJson(['projects' => [], 'tasks' => [], 'members' => []]);
    }

    public function test_search_requires_minimum_two_characters(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);
        Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $user->id, 'name' => 'A']);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->getJson(route('search', ['q' => 'a']));

        $response->assertOk();
        $response->assertExactJson(['projects' => [], 'tasks' => [], 'members' => []]);
    }

    public function test_search_excludes_results_from_a_different_workspace(): void
    {
        $user = User::factory()->create();
        $workspaceA = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspaceB = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspaceA->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);
        $workspaceB->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        Project::factory()->create(['workspace_id' => $workspaceB->id, 'created_by_user_id' => $user->id, 'name' => 'Secret Project']);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspaceA->id])
            ->getJson(route('search', ['q' => 'secret']));

        $response->assertOk();
        $response->assertExactJson(['projects' => [], 'tasks' => [], 'members' => []]);
    }

    public function test_search_excludes_soft_deleted_projects(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $project = Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $user->id, 'name' => 'Deleted Project']);
        $project->delete();

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->getJson(route('search', ['q' => 'deleted']));

        $response->assertOk();
        $response->assertExactJson(['projects' => [], 'tasks' => [], 'members' => []]);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=SearchTest`
Expected: FAIL — `route('search')` doesn't exist yet, so every test fails with a `RouteNotFoundException`.

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/SearchController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Services\CurrentWorkspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request, CurrentWorkspace $currentWorkspace): JsonResponse
    {
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
    }
}
```

- [ ] **Step 4: Wire the route**

In `routes/web.php`, add the import near the other controller imports at the top of the file (alphabetical order, next to `use App\Http\Controllers\ProjectController;`):

```php
use App\Http\Controllers\SearchController;
```

Then add this line inside the existing `Route::middleware('workspace')->group(function () { ... });` block (anywhere in that block — e.g. right after the `/members` routes):

```php
        Route::get('/search', [SearchController::class, 'search'])->name('search');
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter=SearchTest`
Expected: PASS (6 tests)

- [ ] **Step 6: Run the full suite**

Run: `php artisan test`
Expected: no failures, pass count is 43 + 6 = 49 or more.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/SearchController.php routes/web.php tests/Feature/SearchTest.php
git commit -m "feat: add global search endpoint for projects, tasks, and members"
```

---

### Task 2: Search palette UI (Alpine component, modal, keyboard shortcut)

**Files:**
- Create: `resources/js/search-palette.js`
- Modify: `resources/js/app.js`
- Modify: `resources/views/layouts/navigation.blade.php`

**Interfaces:**
- Consumes: route `search` (Task 1), the existing `<x-modal>` component's `open-modal`/`close-modal` window-event contract (`window.dispatchEvent(new CustomEvent('open-modal', { detail: 'search-palette' }))` opens the modal named `search-palette` — see `resources/views/components/modal.blade.php:42`, an existing, unmodified file), `session('current_workspace_id')` (existing session key, already read elsewhere in this same file for the notifications badge).
- Produces: an `Alpine.data('searchPalette', ...)` component usable as `x-data="searchPalette()"`; no other task depends on its internals.

This task has no PHP tests (per Global Constraints) — verification is manual, described in Step 4.

- [ ] **Step 1: Create the Alpine component**

Create `resources/js/search-palette.js`:

```js
export default function searchPalette() {
    return {
        query: '',
        results: { projects: [], tasks: [], members: [] },
        highlightedIndex: 0,
        debounceTimer: null,

        openPalette() {
            this.query = '';
            this.results = { projects: [], tasks: [], members: [] };
            this.highlightedIndex = 0;
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'search-palette' }));
            this.$nextTick(() => this.$refs.searchInput && this.$refs.searchInput.focus());
        },

        onInput() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => this.fetchResults(), 200);
        },

        async fetchResults() {
            if (this.query.trim().length < 2) {
                this.results = { projects: [], tasks: [], members: [] };
                this.highlightedIndex = 0;
                return;
            }

            const response = await fetch(`/search?q=${encodeURIComponent(this.query)}`, {
                headers: { Accept: 'application/json' },
            });
            this.results = await response.json();
            this.highlightedIndex = 0;
        },

        flatResults() {
            return [
                ...this.results.projects.map((p) => ({ label: p.name, url: `/projects/${p.id}` })),
                ...this.results.tasks.map((t) => ({ label: t.title, url: `/tasks/${t.id}` })),
                ...this.results.members.map((m) => ({ label: m.name, url: `/members#member-${m.id}` })),
            ];
        },

        moveHighlight(delta) {
            const total = this.flatResults().length;
            if (total === 0) return;
            this.highlightedIndex = (this.highlightedIndex + delta + total) % total;
        },

        goToHighlighted() {
            const item = this.flatResults()[this.highlightedIndex];
            if (item) window.location.href = item.url;
        },

        isHighlighted(url) {
            const item = this.flatResults()[this.highlightedIndex];
            return item ? item.url === url : false;
        },
    };
}
```

- [ ] **Step 2: Register the component**

Replace `resources/js/app.js` entirely with:

```js
import './bootstrap';

import Alpine from 'alpinejs';
import searchPalette from './search-palette';

window.Alpine = Alpine;

Alpine.data('searchPalette', searchPalette);

Alpine.start();
```

- [ ] **Step 3: Mount the palette in the nav**

In `resources/views/layouts/navigation.blade.php`, the desktop nav's right-hand action group currently starts at (existing code, unchanged so far in this plan):

```blade
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-3">
                <form method="POST" action="{{ route('workspaces.switch') }}">
```

Insert this new block immediately before that `<div class="hidden sm:flex ...">` line (i.e. as a new sibling right after the closing `</div>` of the left-hand nav-links block, before the right-hand action group):

```blade
            @if(session('current_workspace_id'))
                <div x-data="searchPalette()" x-on:keydown.window="if ((event.metaKey || event.ctrlKey) && event.key === 'k') { event.preventDefault(); openPalette(); }" class="flex items-center">
                    <button type="button" @click="openPalette()" class="tn-nav-link flex items-center gap-1" aria-label="Search">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                    </button>

                    <x-modal name="search-palette" maxWidth="lg">
                        <div class="p-4">
                            <input
                                x-ref="searchInput"
                                type="text"
                                x-model="query"
                                x-on:input="onInput()"
                                x-on:keydown.down.prevent="moveHighlight(1)"
                                x-on:keydown.up.prevent="moveHighlight(-1)"
                                x-on:keydown.enter.prevent="goToHighlighted()"
                                class="tn-input w-full"
                                placeholder="Search projects, tasks, members..."
                                autocomplete="off"
                            >

                            <template x-if="results.projects.length">
                                <div class="mt-4">
                                    <h4 class="text-xs uppercase tracking-wider text-slate-500 mb-2">Projects</h4>
                                    <template x-for="project in results.projects" :key="'project-' + project.id">
                                        <a :href="`/projects/${project.id}`" class="tn-row block" :class="{ 'bg-brand-500/10': isHighlighted(`/projects/${project.id}`) }" x-text="project.name"></a>
                                    </template>
                                </div>
                            </template>

                            <template x-if="results.tasks.length">
                                <div class="mt-4">
                                    <h4 class="text-xs uppercase tracking-wider text-slate-500 mb-2">Tasks</h4>
                                    <template x-for="task in results.tasks" :key="'task-' + task.id">
                                        <a :href="`/tasks/${task.id}`" class="tn-row block" :class="{ 'bg-brand-500/10': isHighlighted(`/tasks/${task.id}`) }" x-text="task.title"></a>
                                    </template>
                                </div>
                            </template>

                            <template x-if="results.members.length">
                                <div class="mt-4">
                                    <h4 class="text-xs uppercase tracking-wider text-slate-500 mb-2">Members</h4>
                                    <template x-for="member in results.members" :key="'member-' + member.id">
                                        <a :href="`/members#member-${member.id}`" class="tn-row block" :class="{ 'bg-brand-500/10': isHighlighted(`/members#member-${member.id}`) }" x-text="member.name"></a>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </x-modal>
                </div>
            @endif
```

(The `@if(session('current_workspace_id'))` gate matches the existing pattern the notifications unread-count badge already uses a few lines below in this same file.)

- [ ] **Step 4: Build assets and verify manually**

Run: `npm run build`

Then, with the dev server running and logged in as a user with a current workspace selected (e.g. the seeded `admin@example.com` / `password`):
1. Press Cmd+K (Mac) or Ctrl+K (Windows/Linux) from any page — the search modal should open and the input should be focused.
2. Click the search icon button in the nav — same result.
3. Type at least 2 characters matching an existing seeded project, task, or member name — results should appear grouped under "Projects" / "Tasks" / "Members" headings within ~200ms.
4. Press Down/Up arrows — the highlighted row (indicated by a subtle background tint) should move between results, including across group boundaries.
5. Press Enter on a highlighted project or task result — the browser should navigate to that project's or task's page.
6. Press Escape — the modal should close.

- [ ] **Step 5: Run the full test suite**

Run: `php artisan test`
Expected: no failures, same pass count as after Task 1 (this task adds no new PHP tests).

- [ ] **Step 6: Commit**

```bash
git add resources/js/search-palette.js resources/js/app.js resources/views/layouts/navigation.blade.php
git commit -m "feat: add Cmd+K search palette to the nav"
```

---

### Task 3: Member result highlight

**Files:**
- Modify: `resources/views/members/index.blade.php`

**Interfaces:**
- Consumes: the member result URL shape (`/members#member-{id}`) established in Task 2's `search-palette.js`.

This task has no PHP tests (per Global Constraints, this is browser-only scroll/highlight behavior) — verification is manual, described in Step 3.

- [ ] **Step 1: Add the row anchor**

In `resources/views/members/index.blade.php`, the "Members" table's row currently reads:

```blade
                @foreach($workspace->users as $member)
                    <tr>
                        <td>{{ $member->name }}</td><td>{{ $member->email }}</td><td><span class="tn-badge-neutral">{{ $member->pivot->role }}</span></td>
```

Change the `<tr>` line to:

```blade
                @foreach($workspace->users as $member)
                    <tr id="member-{{ $member->id }}">
                        <td>{{ $member->name }}</td><td>{{ $member->email }}</td><td><span class="tn-badge-neutral">{{ $member->pivot->role }}</span></td>
```

(Only this one line changes — nothing else in the row or the rest of the file.)

- [ ] **Step 2: Add the scroll-and-highlight script**

The file currently opens with:

```blade
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
```

Change it to:

```blade
    <div class="py-8" x-data x-init="
        if (window.location.hash.startsWith('#member-')) {
            const row = document.querySelector(window.location.hash);
            if (row) {
                row.scrollIntoView({ block: 'center' });
                row.classList.add('bg-brand-500/10');
                setTimeout(() => row.classList.remove('bg-brand-500/10'), 2000);
            }
        }
    "><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
```

- [ ] **Step 3: Build assets and verify manually**

Run: `npm run build`

Then, logged in as `admin@example.com` / `password`:
1. Search for a member by name via the Cmd+K palette (Task 2) and press Enter on a member result, OR navigate directly to `/members#member-{id}` for any seeded member's ID.
2. The page should scroll so that member's row is centered in the viewport, and that row should briefly show a tinted background (roughly 2 seconds) before returning to normal.

- [ ] **Step 4: Run the full test suite**

Run: `php artisan test`
Expected: no failures, same pass count as after Task 2 (this task adds no new PHP tests).

- [ ] **Step 5: Commit**

```bash
git add resources/views/members/index.blade.php
git commit -m "feat: scroll to and highlight a member row when linked from search"
```

---

## Self-Review

**Spec coverage:**
- Search endpoint (JSON shape, workspace scoping, 2-char minimum, 5-per-category limit, member-search authorization exception): Task 1. ✅
- Alpine palette component, modal reuse, keyboard shortcut, visible button, debounced live results, arrow-key navigation, result URLs: Task 2. ✅
- Member result scroll-and-highlight: Task 3. ✅
- Testing boundary (PHP tests only for the endpoint; manual verification for all JS/browser behavior): reflected in all three tasks' step lists — Tasks 2 and 3 have no failing-test-first step, by design, matching the spec's explicit non-goal. ✅
- Non-goals (no search engine, no Activity/Notifications search, no in-page filter changes, no search history): no task introduces any of these. ✅

**Placeholder scan:** none found — every step has complete, exact code, exact file paths, and exact manual-verification steps.

**Type consistency:** `SearchController::search` returns `JsonResponse` with keys `projects`/`tasks`/`members`, each an array of `{id, name}` or `{id, title}` objects. Task 2's `search-palette.js` reads exactly these same keys (`results.projects`, `results.tasks`, `results.members`) and exactly these same per-item fields (`p.name`, `t.title`, `m.name`) — no naming drift between the backend contract defined in Task 1 and the frontend consumer defined in Task 2. Task 3's `#member-{id}` anchor format matches the URL Task 2's `flatResults()` already constructs for member results.

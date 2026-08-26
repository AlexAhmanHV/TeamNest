# Onboarding & Empty States Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give first-time and empty-state screens in TeamNest real guidance instead of bare/blank UI, and turn the dashboard into an actual summary (stats + recent activity) instead of a pure link grid.

**Architecture:** Convert the dashboard route from an inline closure to `DashboardController@index`, which computes the same task-stat query `AnalyticsController` already uses (duplicated, not extracted — it's ~10 lines and used in exactly two places) plus the 5 most recent `ActivityLog` rows, only when a workspace exists. Three other screens (`/workspaces`, `/projects`, `/activity`) get a small conditional block added to their existing Blade views for their empty-collection case — no controller changes needed there, since each already passes the relevant collection to its view.

**Tech Stack:** Laravel 12, Blade, PHPUnit (`Tests\TestCase`, not Pest), existing `tn-*` Tailwind design-system classes (no new classes).

## Global Constraints

- This is real conditional-logic work, not a visual-only pass — normal TDD applies. Write each failing test before its implementation.
- No new models, migrations, or changes to workspace/project/task creation logic — view-layer and read-only query additions only.
- Use only existing `tn-*` classes (`tn-card`, `tn-btn-primary`, `tn-link`, `tn-page-header`, `tn-page-title`, `tn-page-description`) — no new component classes.
- Exact copy strings (verbatim, from the design spec):
  - Dashboard no-workspace: headline "Create your first workspace", body "Workspaces keep your projects, teammates, and activity isolated from other teams.", CTA "Create a workspace" → `route('workspaces.index')`.
  - `/workspaces` empty: headline "No workspaces yet", same body line as above.
  - `/projects` empty: headline "No projects yet", body "Create a project to start organizing tasks."
  - `/activity` empty: headline "Nothing here yet", body "Actions your team takes will show up here."
- Full `php artisan test` run after every task must return the same-or-growing pass count with zero failures. Current baseline before this plan: 33 passed, 102 assertions.
- `CurrentWorkspace::forUser()` returns `?Workspace` (nullable) — this is how the dashboard detects the no-workspace state; do not use `requireForUser()` there (it aborts with 404, which is wrong for a legitimately-empty first-run state).

---

### Task 1: Dashboard controller, stats, recent activity, and no-workspace welcome state

**Files:**
- Create: `app/Http/Controllers/DashboardController.php`
- Modify: `routes/web.php:29-33` (replace the dashboard closure route)
- Modify: `resources/views/dashboard.blade.php` (full replacement)
- Test: `tests/Feature/DashboardTest.php`

**Interfaces:**
- Produces: `DashboardController::index(CurrentWorkspace $currentWorkspace): View`, returning `view('dashboard', [...])` with keys `workspace` (always), and when `workspace` is not null: `total`, `done`, `overdue`, `completionRate`, `recentActivities` (an `Illuminate\Support\Collection` of up to 5 `ActivityLog` models with `actor` eager-loaded).
- Consumes: `App\Services\CurrentWorkspace::forUser(): ?Workspace` (existing), `App\Enums\TaskStatus::Done` (existing), `App\Models\ActivityLog` with its existing `actor()` relation (existing).

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/DashboardTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_welcome_state_when_user_has_no_workspace(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Create your first workspace');
        $response->assertSee('Workspaces keep your projects, teammates, and activity isolated from other teams.');
    }

    public function test_dashboard_shows_stats_and_recent_activity_when_workspace_has_data(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $project = Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $user->id]);

        Task::factory()->create(['project_id' => $project->id, 'status' => 'done', 'due_date' => null, 'created_by_user_id' => $user->id]);
        Task::factory()->create(['project_id' => $project->id, 'status' => 'todo', 'due_date' => now()->subDays(2)->toDateString(), 'created_by_user_id' => $user->id]);

        ActivityLog::factory()->create([
            'workspace_id' => $workspace->id,
            'actor_user_id' => $user->id,
            'action' => 'project.created',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('total', 2);
        $response->assertViewHas('done', 1);
        $response->assertViewHas('overdue', 1);
        $response->assertViewHas('completionRate', 50.0);
        $response->assertSee('project.created');
    }

    public function test_dashboard_shows_empty_activity_message_when_workspace_has_no_tasks_or_activity(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('total', 0);
        $response->assertSee('Nothing here yet.');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=DashboardTest`
Expected: FAIL — `route('dashboard')` currently ignores session workspace state correctly, but the view has no "Create your first workspace" copy and no `total`/`done`/`overdue`/`completionRate`/`recentActivities` view data, so all three tests fail (the first two on missing text/view-data, the third on missing view data).

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/DashboardController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\ActivityLog;
use App\Services\CurrentWorkspace;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(CurrentWorkspace $currentWorkspace): View
    {
        $workspace = $currentWorkspace->forUser();

        if (! $workspace) {
            return view('dashboard', ['workspace' => null]);
        }

        $taskBase = DB::table('tasks')
            ->join('projects', 'projects.id', '=', 'tasks.project_id')
            ->where('projects.workspace_id', $workspace->id)
            ->whereNull('tasks.deleted_at');

        $total = (clone $taskBase)->count();
        $done = (clone $taskBase)->where('tasks.status', TaskStatus::Done->value)->count();
        $overdue = (clone $taskBase)
            ->whereDate('tasks.due_date', '<', now()->toDateString())
            ->where('tasks.status', '!=', TaskStatus::Done->value)
            ->count();

        return view('dashboard', [
            'workspace' => $workspace,
            'total' => $total,
            'done' => $done,
            'overdue' => $overdue,
            'completionRate' => $total > 0 ? round(($done / $total) * 100, 1) : 0,
            'recentActivities' => ActivityLog::query()
                ->with('actor')
                ->where('workspace_id', $workspace->id)
                ->latest('created_at')
                ->limit(5)
                ->get(),
        ]);
    }
}
```

- [ ] **Step 4: Wire the route**

In `routes/web.php`, add the import near the other controller imports at the top of the file (alphabetical order, next to `use App\Http\Controllers\ActivityController;`):

```php
use App\Http\Controllers\DashboardController;
```

Then replace the existing dashboard route (currently lines 29-33):

```php
    Route::get('/dashboard', function (CurrentWorkspace $currentWorkspace) {
        return view('dashboard', [
            'workspace' => $currentWorkspace->forUser(),
        ]);
    })->name('dashboard');
```

with:

```php
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
```

Confirmed by `grep -n "CurrentWorkspace" routes/web.php` during planning: the `use App\Services\CurrentWorkspace;` import at the top of `routes/web.php` (currently line 18) is used only by this closure. Remove that `use` line — it would otherwise be an unused-import lint failure under `./vendor/bin/pint --test` / `./vendor/bin/phpstan analyse`.

- [ ] **Step 5: Replace the dashboard view**

Replace `resources/views/dashboard.blade.php` entirely with:

```blade
<x-app-layout>
    <x-slot name="header">
        <div class="tn-page-header mb-0">
            <div>
                <h1 class="tn-page-title">Dashboard</h1>
                <p class="tn-page-description">{{ $workspace?->name ?? 'Select or create a workspace to continue.' }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(! $workspace)
                <div class="tn-card max-w-xl">
                    <h2 class="text-xl font-bold text-white">Create your first workspace</h2>
                    <p class="text-sm text-slate-400 mt-2">Workspaces keep your projects, teammates, and activity isolated from other teams.</p>
                    <a href="{{ route('workspaces.index') }}" class="tn-btn-primary inline-block mt-4">Create a workspace</a>
                </div>
            @else
                <div class="grid md:grid-cols-4 gap-4">
                    <div class="tn-card"><div class="text-xs uppercase tracking-wider text-slate-500">Total Tasks</div><div class="text-3xl font-extrabold text-white mt-1">{{ $total }}</div></div>
                    <div class="tn-card"><div class="text-xs uppercase tracking-wider text-slate-500">Done</div><div class="text-3xl font-extrabold text-white mt-1">{{ $done }}</div></div>
                    <div class="tn-card"><div class="text-xs uppercase tracking-wider text-slate-500">Overdue</div><div class="text-3xl font-extrabold text-white mt-1">{{ $overdue }}</div></div>
                    <div class="tn-card"><div class="text-xs uppercase tracking-wider text-slate-500">Completion Rate</div><div class="text-3xl font-extrabold text-brand-400 mt-1">{{ $completionRate }}%</div></div>
                </div>

                <div class="tn-card">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400">Recent Activity</h3>
                        <a href="{{ route('activity.index') }}" class="text-sm tn-link">View all</a>
                    </div>
                    @forelse($recentActivities as $activity)
                        <div class="py-2 border-t border-slate-800 first:border-t-0">
                            <div class="text-sm text-slate-200"><strong class="text-white">{{ $activity->actor?->name ?? 'System' }}</strong> {{ $activity->action }}</div>
                            <div class="text-xs text-slate-500">{{ $activity->created_at->diffForHumans() }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Nothing here yet.</p>
                    @endforelse
                </div>

                <div class="grid md:grid-cols-3 gap-4">
                    <a href="{{ route('projects.index') }}" class="tn-card hover:border-brand-500 transition">
                        <p class="text-sm font-semibold text-white">Projects</p>
                    </a>
                    <a href="{{ route('members.index') }}" class="tn-card hover:border-brand-500 transition">
                        <p class="text-sm font-semibold text-white">Members</p>
                    </a>
                    <a href="{{ route('activity.index') }}" class="tn-card hover:border-brand-500 transition">
                        <p class="text-sm font-semibold text-white">Activity</p>
                    </a>
                    <a href="{{ route('notifications.index') }}" class="tn-card hover:border-brand-500 transition">
                        <p class="text-sm font-semibold text-white">Notifications</p>
                    </a>
                    <a href="{{ route('analytics.index') }}" class="tn-card hover:border-brand-500 transition">
                        <p class="text-sm font-semibold text-white">Analytics</p>
                    </a>
                    <a href="{{ route('workspaces.settings.edit') }}" class="tn-card hover:border-brand-500 transition">
                        <p class="text-sm font-semibold text-white">Workspace Settings</p>
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --filter=DashboardTest`
Expected: PASS (3 tests)

- [ ] **Step 7: Run the full suite**

Run: `php artisan test`
Expected: no failures, pass count is 33 + 3 = 36 or more (exact assertion count will grow too) — zero regressions in the pre-existing 33.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/DashboardController.php routes/web.php resources/views/dashboard.blade.php tests/Feature/DashboardTest.php
git commit -m "feat: add dashboard stats, recent activity, and no-workspace welcome state"
```

---

### Task 2: `/workspaces` empty state

**Files:**
- Modify: `resources/views/workspaces/index.blade.php`
- Test: `tests/Feature/WorkspaceOnboardingTest.php`

**Interfaces:**
- Consumes: `WorkspaceController::index` already passes `$workspaces` (an `Illuminate\Support\Collection` of the user's workspaces) to this view — no controller change needed.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/WorkspaceOnboardingTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspaces_page_shows_empty_state_when_user_has_no_workspaces(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('workspaces.index'));

        $response->assertOk();
        $response->assertSee('No workspaces yet');
        $response->assertSee('Workspaces keep your projects, teammates, and activity isolated from other teams.');
    }

    public function test_workspaces_page_hides_empty_state_when_user_has_a_workspace(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id, 'name' => 'Acme']);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($user)->get(route('workspaces.index'));

        $response->assertOk();
        $response->assertDontSee('No workspaces yet');
        $response->assertSee('Acme');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=WorkspaceOnboardingTest`
Expected: FAIL — the first test fails because the empty-state copy doesn't exist yet; the second test currently passes already (existing behavior), but run both together to confirm the suite file itself is wired correctly.

- [ ] **Step 3: Add the empty state**

Replace `resources/views/workspaces/index.blade.php` entirely with:

```blade
<x-app-layout>
    <x-slot name="header">
        <h1 class="tn-page-title">Workspaces</h1>
    </x-slot>
    <div class="py-8"><div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if($workspaces->isEmpty())
            <div class="tn-card max-w-xl">
                <h2 class="text-xl font-bold text-white">No workspaces yet</h2>
                <p class="text-sm text-slate-400 mt-2">Workspaces keep your projects, teammates, and activity isolated from other teams.</p>
            </div>
        @endif
        <div class="tn-card">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-4">Your Workspaces</h3>
            <ul class="space-y-2">
                @foreach($workspaces as $workspace)
                    <li class="flex justify-between items-center tn-row">
                        <span class="text-sm text-slate-200">{{ $workspace->name }} <span class="tn-badge-neutral ml-2">{{ $workspace->pivot->role }}</span></span>
                        <form method="POST" action="{{ route('workspaces.switch') }}">@csrf
                            <input type="hidden" name="workspace_id" value="{{ $workspace->id }}">
                            <x-primary-button>{{ session('current_workspace_id') == $workspace->id ? 'Current' : 'Switch' }}</x-primary-button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="tn-card">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-4">Create Workspace</h3>
            <form method="POST" action="{{ route('workspaces.store') }}" class="space-y-3">@csrf
                <x-input-label for="name" value="Name" />
                <x-text-input name="name" id="name" class="w-full" required />
                <x-input-error :messages="$errors->get('name')" />
                <x-primary-button>Create</x-primary-button>
            </form>
        </div>
    </div></div>
</x-app-layout>
```

(Only change from the original: the new `@if($workspaces->isEmpty())` block inserted above the existing "Your Workspaces" card. Everything else — the list loop, the switch form, the create form — is byte-identical to the current file.)

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=WorkspaceOnboardingTest`
Expected: PASS (2 tests)

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: no failures, pass count grows by 2 from Task 1's total.

- [ ] **Step 6: Commit**

```bash
git add resources/views/workspaces/index.blade.php tests/Feature/WorkspaceOnboardingTest.php
git commit -m "feat: add empty state to the workspaces page"
```

---

### Task 3: `/projects` empty state

**Files:**
- Modify: `resources/views/projects/index.blade.php`
- Test: `tests/Feature/ProjectsEmptyStateTest.php`

**Interfaces:**
- Consumes: `ProjectController::index` already passes `$projects` (a paginated `LengthAwarePaginator` of `Project`) to this view — no controller change needed.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/ProjectsEmptyStateTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectsEmptyStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_page_shows_empty_state_when_workspace_has_no_projects(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('projects.index'));

        $response->assertOk();
        $response->assertSee('No projects yet');
        $response->assertSee('Create a project to start organizing tasks.');
    }

    public function test_projects_page_hides_empty_state_when_projects_exist(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);
        Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $user->id, 'name' => 'Alpha']);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('projects.index'));

        $response->assertOk();
        $response->assertDontSee('No projects yet');
        $response->assertSee('Alpha');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=ProjectsEmptyStateTest`
Expected: FAIL — the first test fails on missing empty-state copy.

- [ ] **Step 3: Add the empty state**

Replace `resources/views/projects/index.blade.php` entirely with:

```blade
<x-app-layout>
    <x-slot name="header">
        <h1 class="tn-page-title">Projects</h1>
    </x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="flex justify-end"><a href="{{ route('projects.trash') }}" class="text-sm tn-link">View Trash</a></div>
        <div class="tn-card">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">Create Project</h3>
            <form method="POST" action="{{ route('projects.store') }}" class="space-y-3">@csrf
                <x-text-input name="name" placeholder="Name" class="w-full" required />
                <textarea name="description" class="tn-input w-full" placeholder="Description"></textarea>
                <x-primary-button>Create</x-primary-button>
            </form>
        </div>

        @if($projects->isEmpty())
            <div class="tn-card">
                <h2 class="text-lg font-bold text-white">No projects yet</h2>
                <p class="text-sm text-slate-400 mt-2">Create a project to start organizing tasks.</p>
            </div>
        @else
            <div class="tn-card">
                <table class="tn-table"><thead><tr><th>Name</th><th>Actions</th></tr></thead><tbody>
                    @foreach($projects as $project)
                        <tr><td>{{ $project->name }}</td>
                            <td class="flex gap-3">
                                <a href="{{ route('projects.show', $project) }}" class="tn-link">Open</a>
                                <form method="POST" action="{{ route('projects.destroy', $project) }}">@csrf @method('DELETE')<button class="text-rose-400 hover:text-rose-300">Delete</button></form>
                            </td>
                        </tr>
                    @endforeach
                </tbody></table>
                <div class="mt-4">{{ $projects->links() }}</div>
            </div>
        @endif
    </div></div>
</x-app-layout>
```

(Only change from the original: the bare `tn-card` containing the table is now wrapped in `@if($projects->isEmpty()) ... @else ... @endif`, with the empty-state card as the `@if` branch and the original table exactly as-is in the `@else` branch. The "Create Project" card above is untouched.)

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=ProjectsEmptyStateTest`
Expected: PASS (2 tests)

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: no failures, pass count grows by 2 from Task 2's total.

- [ ] **Step 6: Commit**

```bash
git add resources/views/projects/index.blade.php tests/Feature/ProjectsEmptyStateTest.php
git commit -m "feat: add empty state to the projects page"
```

---

### Task 4: `/activity` empty state

**Files:**
- Modify: `resources/views/activity/index.blade.php`
- Test: `tests/Feature/ActivityEmptyStateTest.php`

**Interfaces:**
- Consumes: `ActivityController::index` already passes `$activities` (a paginated `LengthAwarePaginator` of `ActivityLog`) to this view — no controller change needed.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/ActivityEmptyStateTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityEmptyStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_page_shows_empty_state_when_workspace_has_no_activity(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('activity.index'));

        $response->assertOk();
        $response->assertSee('Nothing here yet');
        $response->assertSee('Actions your team takes will show up here.');
    }

    public function test_activity_page_hides_empty_state_when_activity_exists(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);
        ActivityLog::factory()->create(['workspace_id' => $workspace->id, 'actor_user_id' => $user->id, 'action' => 'project.created']);

        $response = $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('activity.index'));

        $response->assertOk();
        $response->assertDontSee('Nothing here yet');
        $response->assertSee('project.created');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=ActivityEmptyStateTest`
Expected: FAIL — the first test fails on missing empty-state copy.

- [ ] **Step 3: Add the empty state**

Replace `resources/views/activity/index.blade.php` entirely with:

```blade
<x-app-layout>
    <x-slot name="header"><h1 class="tn-page-title">Activity Feed</h1></x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 tn-card">
        @if($activities->isEmpty())
            <h2 class="text-lg font-bold text-white">Nothing here yet</h2>
            <p class="text-sm text-slate-400 mt-2">Actions your team takes will show up here.</p>
        @else
            <ul class="divide-y divide-slate-800">
                @foreach($activities as $activity)
                    <li class="py-3">
                        <div class="text-sm text-slate-200"><strong class="text-white">{{ $activity->actor?->name ?? 'System' }}</strong> {{ $activity->action }}</div>
                        <div class="text-xs text-slate-500">{{ $activity->created_at->diffForHumans() }}</div>
                        @if($activity->metadata)
                            <pre class="text-xs bg-slate-950 border border-slate-800 mt-2 p-2 rounded overflow-x-auto text-slate-400">{{ json_encode($activity->metadata, JSON_PRETTY_PRINT) }}</pre>
                        @endif
                    </li>
                @endforeach
            </ul>
            <div class="mt-4">{{ $activities->links() }}</div>
        @endif
    </div></div>
</x-app-layout>
```

(Only change from the original: the `<ul>` and pagination links are now wrapped in `@if($activities->isEmpty()) ... @else ... @endif`, with the empty-state message as the `@if` branch. The `<ul>` contents and pagination are otherwise byte-identical to the current file.)

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=ActivityEmptyStateTest`
Expected: PASS (2 tests)

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: no failures, pass count grows by 2 from Task 3's total (36 + 2 + 2 + 2 = 42 total pass count across all four tasks, up from the 33 baseline).

- [ ] **Step 6: Commit**

```bash
git add resources/views/activity/index.blade.php tests/Feature/ActivityEmptyStateTest.php
git commit -m "feat: add empty state to the activity feed page"
```

---

## Self-Review

**Spec coverage:**
- Dashboard no-workspace welcome state: Task 1. ✅
- `/workspaces` zero-workspace empty state: Task 2. ✅
- `/projects` zero-project empty state: Task 3. ✅
- `/activity` zero-activity empty state: Task 4. ✅
- Dashboard populated state (4 stats + recent activity + existing nav grid retained): Task 1. ✅
- `/members` and Kanban empty columns explicitly out of scope per the spec's non-goals — no task touches them. ✅
- Query-reuse decision (duplicate the stat query rather than extracting a shared service): followed literally in Task 1's controller — the query block is copy-derived from `AnalyticsController::index` with no new abstraction. ✅

**Placeholder scan:** none found — every step has complete, exact code, exact copy strings, and exact test assertions.

**Type consistency:** `DashboardController::index` returns `View` (matches every other controller in this codebase, e.g. `ActivityController::index`, `AnalyticsController::index`). View variable names (`total`, `done`, `overdue`, `completionRate`) match `AnalyticsController`'s existing names exactly, so a future reader comparing the two files sees consistent naming, not a divergent second convention.

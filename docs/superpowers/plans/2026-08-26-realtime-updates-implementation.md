# Real-Time Updates Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Broadcast Kanban task moves, task comments, and notifications live to other connected users via Pusher, with channel authorization matching the app's existing permission model.

**Architecture:** Three `ShouldBroadcast` event classes are dispatched via `broadcast(new Event(...))->toOthers()` from the three existing action points that already perform these changes (`TaskController::move`, `TaskCommentController::store`, `WorkspaceNotifier::notify`). `routes/channels.php` authorizes each channel using the app's existing Policies. The frontend adds `laravel-echo` + `pusher-js`, initialized once in `resources/js/bootstrap.js`, and three small, additive UI integrations (Kanban presence + live move, live comment append, live notification badge) — none of which rewrite existing logic, only extend it.

**Tech Stack:** Laravel 12 broadcasting (`pusher/pusher-php-server`), `laravel-echo` + `pusher-js` (Alpine.js remains the only UI framework), PHPUnit (`Tests\TestCase`), Pusher Channels (hosted, not self-hosted Reverb).

## Global Constraints

- `BROADCAST_CONNECTION=pusher` and all four `PUSHER_APP_*` / `VITE_PUSHER_APP_*` env vars are ALREADY set in `.env` (Pusher app "teamnest", cluster `eu`) — do not recreate the Pusher app or touch `.env` credentials. `.env.example` already documents the variable names with blank placeholders.
- `phpunit.xml` already forces `BROADCAST_CONNECTION=null` during the test suite (confirmed: `<env name="BROADCAST_CONNECTION" value="null"/>`) — no task in this plan needs to account for real broadcasting during automated tests.
- Every broadcast uses `broadcast(new Event(...))->toOthers();` — NOT `event(new Event(...))` and NOT `Event::class::dispatch(...)`. This distinction matters for testing (see Task 2's note on `Illuminate\Broadcasting\BroadcastEvent`).
- Channel authorization in `routes/channels.php` reuses the app's existing Policies (`ProjectPolicy::view`, `TaskPolicy::view`) via `$user->can(...)` — no new authorization logic is invented.
- No automated test covers actual live socket delivery (DOM updates on receipt, the presence avatar stack, Echo connecting to real Pusher) — verified manually with two browser sessions, per the spec's explicit non-goal. Automated tests cover only: event dispatch (Task 2) and channel authorization (Task 1).
- Full `php artisan test` run after every task must return the same-or-growing pass count with zero failures. Current baseline before this plan: 51 passed, 161 assertions.

---

### Task 1: Broadcasting infrastructure & channel authorization

**Files:**
- Modify: `composer.json` (add `pusher/pusher-php-server`)
- Create: `config/broadcasting.php`
- Modify: `bootstrap/app.php` (register broadcasting routes)
- Create: `routes/channels.php`
- Test: `tests/Feature/BroadcastAuthorizationTest.php`

**Interfaces:**
- Produces: three registered channel names later tasks broadcast to and subscribe on — `project.{projectId}` (presence), `task.{taskId}` (private), `user.{userId}` (private). No PHP code in later tasks calls into this task's files directly; the channel *names* are the shared contract (used as string literals in Task 2's event classes and Tasks 4-6's frontend Echo subscriptions).

- [ ] **Step 1: Add the Pusher PHP SDK**

Run: `composer require pusher/pusher-php-server`
Expected: `composer.json`'s `require` section gains a `"pusher/pusher-php-server": "^7.x"` line (exact version per whatever Composer resolves — do not pin a specific version yourself).

- [ ] **Step 2: Create the broadcasting config**

Create `config/broadcasting.php`:

```php
<?php

use Illuminate\Support\Str;

return [

    'default' => env('BROADCAST_CONNECTION', 'null'),

    'connections' => [

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'host' => env('PUSHER_HOST') ?: 'api-'.env('PUSHER_APP_CLUSTER', 'mt1').'.pusher.com',
                'port' => env('PUSHER_PORT', 443),
                'scheme' => env('PUSHER_SCHEME', 'https'),
                'encrypted' => true,
                'useTLS' => (bool) env('PUSHER_SCHEME', 'https') === 'https',
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
            ],
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
```

- [ ] **Step 3: Register the broadcasting auth route**

In `bootstrap/app.php`, add a `->withBroadcasting(...)` call to the `Application::configure()` chain, between `->withRouting(...)` and `->withMiddleware(...)`:

```php
<?php

use App\Http\Middleware\EnsureCurrentWorkspace;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'auth']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'workspace' => EnsureCurrentWorkspace::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
```

(This registers `POST /broadcasting/auth`, gated by the `web` + `auth` middleware, and loads the channel-authorization callbacks from `routes/channels.php`.)

- [ ] **Step 4: Write the channel authorization file**

Create `routes/channels.php`:

```php
<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('project.{projectId}', function (User $user, int $projectId) {
    $project = Project::find($projectId);

    if (! $project || ! $user->can('view', $project)) {
        return false;
    }

    return ['id' => $user->id, 'name' => $user->name];
});

Broadcast::channel('task.{taskId}', function (User $user, int $taskId) {
    $task = Task::find($taskId);

    return $task !== null && $user->can('view', $task);
});

Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return (int) $user->id === (int) $userId;
});
```

- [ ] **Step 5: Write the failing authorization tests**

Create `tests/Feature/BroadcastAuthorizationTest.php`. These tests hit Laravel's built-in `/broadcasting/auth` endpoint directly (the standard, documented way to test channel authorization — it exercises the real registered route, middleware, and callback together, not just the closure in isolation):

```php
<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_in_projects_workspace_can_authorize_the_project_presence_channel(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);
        $project = Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $user->id]);

        $this->actingAs($user)
            ->post('/broadcasting/auth', [
                'channel_name' => "presence-project.{$project->id}",
                'socket_id' => '1234.1234',
            ])
            ->assertOk();
    }

    public function test_user_outside_projects_workspace_cannot_authorize_the_project_presence_channel(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $owner->id]);
        $workspace->users()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);
        $project = Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $owner->id]);

        $this->actingAs($outsider)
            ->post('/broadcasting/auth', [
                'channel_name' => "presence-project.{$project->id}",
                'socket_id' => '1234.1234',
            ])
            ->assertForbidden();
    }

    public function test_user_who_can_view_the_task_can_authorize_the_task_channel(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);
        $project = Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by_user_id' => $user->id]);

        $this->actingAs($user)
            ->post('/broadcasting/auth', [
                'channel_name' => "private-task.{$task->id}",
                'socket_id' => '1234.1234',
            ])
            ->assertOk();
    }

    public function test_user_outside_the_tasks_workspace_cannot_authorize_the_task_channel(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $owner->id]);
        $workspace->users()->attach($owner->id, ['role' => 'admin', 'joined_at' => now()]);
        $project = Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by_user_id' => $owner->id]);

        $this->actingAs($outsider)
            ->post('/broadcasting/auth', [
                'channel_name' => "private-task.{$task->id}",
                'socket_id' => '1234.1234',
            ])
            ->assertForbidden();
    }

    public function test_user_can_authorize_their_own_notification_channel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/broadcasting/auth', [
                'channel_name' => "private-user.{$user->id}",
                'socket_id' => '1234.1234',
            ])
            ->assertOk();
    }

    public function test_user_cannot_authorize_another_users_notification_channel(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)
            ->post('/broadcasting/auth', [
                'channel_name' => "private-user.{$otherUser->id}",
                'socket_id' => '1234.1234',
            ])
            ->assertForbidden();
    }
}
```

- [ ] **Step 6: Run tests to verify they fail**

Run: `php artisan test --filter=BroadcastAuthorizationTest`
Expected: FAIL — `/broadcasting/auth` doesn't exist yet (404) until Steps 1-4's files are all in place. If you've already completed Steps 1-4 before writing this test file (as the step order above suggests), this should instead PASS immediately — in that case, skip to Step 8. Run this step only to confirm the test file itself is correctly wired if something seems off.

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test --filter=BroadcastAuthorizationTest`
Expected: PASS (6 tests)

- [ ] **Step 8: Run the full suite**

Run: `php artisan test`
Expected: no failures, pass count is 51 + 6 = 57 or more.

- [ ] **Step 9: Commit**

```bash
git add composer.json composer.lock config/broadcasting.php bootstrap/app.php routes/channels.php tests/Feature/BroadcastAuthorizationTest.php
git commit -m "feat: add broadcasting infrastructure and channel authorization"
```

---

### Task 2: Broadcast events

**Files:**
- Create: `app/Events/TaskMoved.php`
- Create: `app/Events/TaskCommentPosted.php`
- Create: `app/Events/NotificationCreated.php`
- Modify: `app/Http/Controllers/TaskController.php` (in `move()`)
- Modify: `app/Http/Controllers/TaskCommentController.php` (in `store()`)
- Modify: `app/Services/WorkspaceNotifier.php` (in `notify()`)
- Test: `tests/Feature/BroadcastEventsTest.php`

**Interfaces:**
- Produces: `App\Events\TaskMoved` (properties: `projectId`, `taskId`, `toStatus`, `movedBy` — all `int`/`string`, broadcasts on `presence-project.{projectId}`), `App\Events\TaskCommentPosted` (properties: `taskId`, `commentId`, `body`, `authorName`, `postedAt` — broadcasts on `private-task.{taskId}`), `App\Events\NotificationCreated` (properties: `userId`, `notificationId`, `type` — broadcasts on `private-user.{userId}`). Task 4's frontend Kanban code listens for `TaskMoved`'s exact payload keys (`taskId`, `toStatus`); Task 5 listens for `TaskCommentPosted`'s (`body`, `authorName`, `postedAt`); Task 6 listens for `NotificationCreated` (payload keys don't matter to Task 6 — it only needs the event to arrive to increment a counter).
- Consumes: `routes/channels.php`'s three channel names (Task 1) — these events broadcast on exactly those names.

**Important testing note:** these events are dispatched via `broadcast(new Event(...))->toOthers()`, NOT `event(new Event(...))`. Laravel's `broadcast()` helper wraps the event in `Illuminate\Broadcasting\BroadcastEvent` before dispatching it through the event system. This means `Event::fake()` + `Event::assertDispatched(TaskMoved::class, ...)` will NOT match — you must assert on `Illuminate\Broadcasting\BroadcastEvent::class` and inspect its `->event` property, as shown in this task's test file below. This is not optional or a mistake in the test code — it's how Laravel's broadcasting internals actually work.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/BroadcastEventsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Events\NotificationCreated;
use App\Events\TaskCommentPosted;
use App\Events\TaskMoved;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceNotifier;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BroadcastEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_moving_a_task_broadcasts_task_moved(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);
        $project = Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'status' => 'todo', 'created_by_user_id' => $user->id]);

        $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->post(route('tasks.move', $task), ['status' => 'doing'])
            ->assertRedirect();

        Event::assertDispatched(BroadcastEvent::class, function (BroadcastEvent $broadcastEvent) use ($task, $project, $user) {
            return $broadcastEvent->event instanceof TaskMoved
                && $broadcastEvent->event->projectId === $project->id
                && $broadcastEvent->event->taskId === $task->id
                && $broadcastEvent->event->toStatus === 'doing'
                && $broadcastEvent->event->movedBy === $user->id;
        });
    }

    public function test_posting_a_comment_broadcasts_task_comment_posted(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);
        $workspace->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);
        $project = Project::factory()->create(['workspace_id' => $workspace->id, 'created_by_user_id' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by_user_id' => $user->id]);

        $this->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->post(route('tasks.comments.store', $task), ['body' => 'Looks good to me'])
            ->assertRedirect();

        Event::assertDispatched(BroadcastEvent::class, function (BroadcastEvent $broadcastEvent) use ($task) {
            return $broadcastEvent->event instanceof TaskCommentPosted
                && $broadcastEvent->event->taskId === $task->id
                && $broadcastEvent->event->body === 'Looks good to me';
        });
    }

    public function test_workspace_notifier_broadcasts_notification_created(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_user_id' => $user->id]);

        $notification = app(WorkspaceNotifier::class)->notify($user->id, $workspace, 'task.mentioned', ['task_id' => 1]);

        Event::assertDispatched(BroadcastEvent::class, function (BroadcastEvent $broadcastEvent) use ($notification, $user) {
            return $broadcastEvent->event instanceof NotificationCreated
                && $broadcastEvent->event->userId === $user->id
                && $broadcastEvent->event->notificationId === $notification->id
                && $broadcastEvent->event->type === 'task.mentioned';
        });
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=BroadcastEventsTest`
Expected: FAIL — the event classes don't exist yet, and nothing broadcasts from the three call sites, so `Event::assertDispatched(BroadcastEvent::class, ...)` finds no matching dispatch.

- [ ] **Step 3: Create the three event classes**

Create `app/Events/TaskMoved.php`:

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskMoved implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $projectId,
        public int $taskId,
        public string $toStatus,
        public int $movedBy,
    ) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel("project.{$this->projectId}")];
    }

    public function broadcastWith(): array
    {
        return [
            'taskId' => $this->taskId,
            'toStatus' => $this->toStatus,
            'movedBy' => $this->movedBy,
        ];
    }
}
```

Create `app/Events/TaskCommentPosted.php`:

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCommentPosted implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $taskId,
        public int $commentId,
        public string $body,
        public string $authorName,
        public string $postedAt,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("task.{$this->taskId}")];
    }

    public function broadcastWith(): array
    {
        return [
            'commentId' => $this->commentId,
            'body' => $this->body,
            'authorName' => $this->authorName,
            'postedAt' => $this->postedAt,
        ];
    }
}
```

Create `app/Events/NotificationCreated.php`:

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $userId,
        public int $notificationId,
        public string $type,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->userId}")];
    }

    public function broadcastWith(): array
    {
        return [
            'notificationId' => $this->notificationId,
            'type' => $this->type,
        ];
    }
}
```

- [ ] **Step 4: Dispatch `TaskMoved` from `TaskController::move`**

In `app/Http/Controllers/TaskController.php`, add the import near the other `use` statements at the top of the file (alphabetical order, next to `use App\Http\Requests\...`):

```php
use App\Events\TaskMoved;
```

Then, in the `move()` method, change:

```php
    public function move(MoveTaskRequest $request, Task $task, CurrentWorkspace $currentWorkspace, UpdateTask $action): RedirectResponse
    {
        abort_unless($task->project->workspace_id === $currentWorkspace->requireForUser()->id, 404);
        $this->authorize('update', $task);

        $action->execute($task, $request->user(), [
            'status' => $request->string('status')->toString(),
        ]);

        return back()->with('status', 'Task moved.');
    }
```

to:

```php
    public function move(MoveTaskRequest $request, Task $task, CurrentWorkspace $currentWorkspace, UpdateTask $action): RedirectResponse
    {
        abort_unless($task->project->workspace_id === $currentWorkspace->requireForUser()->id, 404);
        $this->authorize('update', $task);

        $status = $request->string('status')->toString();

        $action->execute($task, $request->user(), [
            'status' => $status,
        ]);

        broadcast(new TaskMoved($task->project_id, $task->id, $status, $request->user()->id))->toOthers();

        return back()->with('status', 'Task moved.');
    }
```

- [ ] **Step 5: Dispatch `TaskCommentPosted` from `TaskCommentController::store`**

In `app/Http/Controllers/TaskCommentController.php`, add the import (alphabetical order, next to `use App\Http\Requests\StoreTaskCommentRequest;`):

```php
use App\Events\TaskCommentPosted;
```

Then, immediately after the existing `$comment = $task->comments()->create([...]);` block, add:

```php
        broadcast(new TaskCommentPosted(
            $task->id,
            $comment->id,
            $comment->body,
            $request->user()->name,
            $comment->created_at->toISOString(),
        ))->toOthers();
```

(This goes right after comment creation, before the `@email`-mention regex block that already exists below it — the broadcast doesn't depend on or affect the mention logic.)

- [ ] **Step 6: Dispatch `NotificationCreated` from `WorkspaceNotifier::notify`**

Replace `app/Services/WorkspaceNotifier.php` entirely with:

```php
<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Models\Workspace;
use App\Models\WorkspaceNotification;

class WorkspaceNotifier
{
    public function notify(int $userId, Workspace $workspace, string $type, array $data = []): WorkspaceNotification
    {
        $notification = WorkspaceNotification::create([
            'workspace_id' => $workspace->id,
            'user_id' => $userId,
            'type' => $type,
            'data' => $data,
        ]);

        broadcast(new NotificationCreated($userId, $notification->id, $type))->toOthers();

        return $notification;
    }
}
```

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test --filter=BroadcastEventsTest`
Expected: PASS (3 tests)

- [ ] **Step 8: Run the full suite**

Run: `php artisan test`
Expected: no failures, pass count grows by 3 from Task 1's total.

- [ ] **Step 9: Commit**

```bash
git add app/Events/TaskMoved.php app/Events/TaskCommentPosted.php app/Events/NotificationCreated.php app/Http/Controllers/TaskController.php app/Http/Controllers/TaskCommentController.php app/Services/WorkspaceNotifier.php tests/Feature/BroadcastEventsTest.php
git commit -m "feat: broadcast task moves, comments, and notifications"
```

---

### Task 3: Frontend Echo setup

**Files:**
- Modify: `package.json`
- Modify: `resources/js/bootstrap.js`

**Interfaces:**
- Produces: `window.Echo`, a configured Laravel Echo instance available globally to every subsequent script (Tasks 4-6 call `window.Echo.join(...)`, `window.Echo.private(...)`, etc. — no other setup needed in those tasks).
- Consumes: `VITE_PUSHER_APP_KEY`, `VITE_PUSHER_APP_CLUSTER` (already set in `.env`, per Global Constraints).

This task has no PHP tests — it's frontend build/config only. Verification is manual, described in Step 4.

- [ ] **Step 1: Add the npm dependencies**

Run: `npm install --save-dev laravel-echo pusher-js`
Expected: `package.json`'s `devDependencies` gains `laravel-echo` and `pusher-js` entries (matching this project's existing convention of keeping all frontend packages, including runtime ones like `alpinejs` and `axios`, under `devDependencies` rather than `dependencies` — follow that same pattern here, do not create a `dependencies` section).

- [ ] **Step 2: Initialize Echo**

Replace `resources/js/bootstrap.js` entirely with:

```js
import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
});
```

- [ ] **Step 3: Confirm `resources/js/app.js` still imports bootstrap first**

Read `resources/js/app.js` — it should already start with `import './bootstrap';` before the Alpine setup (this was true before this task and does not need to change; if for any reason it's missing, add it as the first line).

- [ ] **Step 4: Build and verify manually**

Run: `npm run build`
Expected: build succeeds with no errors.

Then, with the dev server running and logged in as `admin@example.com` / `password`:
1. Open the browser's developer console on any page.
2. Confirm `window.Echo` is defined and `window.Echo.connector.pusher.connection.state` eventually reads `"connected"` (it starts as `"connecting"` and transitions within a second or two on a real network connection to Pusher).
3. Confirm no console errors reference Pusher/Echo (an invalid key/cluster would surface as a connection error here — if so, re-check `.env`'s `VITE_PUSHER_APP_KEY`/`VITE_PUSHER_APP_CLUSTER` values against the Pusher dashboard).

- [ ] **Step 5: Run the full test suite**

Run: `php artisan test`
Expected: no failures, same pass count as after Task 2 (this task adds no PHP tests).

- [ ] **Step 6: Commit**

```bash
git add package.json package-lock.json resources/js/bootstrap.js
git commit -m "feat: initialize Laravel Echo with Pusher"
```

---

### Task 4: Kanban board — presence & live task moves

**Files:**
- Modify: `resources/views/tasks/index.blade.php`

**Interfaces:**
- Consumes: `window.Echo` (Task 3), the `presence-project.{projectId}` channel and `TaskMoved`'s payload shape `{ taskId, toStatus, movedBy }` (Task 2).

This task has no PHP tests — it's browser-only behavior. Verification is manual, described in Step 4.

- [ ] **Step 1: Extract the shared move logic**

In `resources/views/tasks/index.blade.php`'s `<script>` block, the existing `kanbanBoard(csrfToken, moveRouteTemplate)` function currently does the DOM-move work inline inside `dropTo()`. Extract that DOM-manipulation logic into a new `moveCardTo(taskId, toStatus)` method so both a local drag AND a remote broadcast can call the same code.

Change the function signature and body from:

```js
        function kanbanBoard(csrfToken, moveRouteTemplate) {
            return {
                draggingTask: null,
                dragOverStatus: null,
                movingTaskId: null,
                startDrag(task) {
                    this.draggingTask = task;
                },
                updateCount(status, delta) {
                    const countNode = this.$refs[`count-${status}`];
                    if (!countNode) {
                        return;
                    }

                    const current = Number.parseInt(countNode.textContent || '0', 10) || 0;
                    countNode.textContent = String(Math.max(0, current + delta));
                },
                async dropTo(status) {
                    if (!this.draggingTask || this.draggingTask.status === status || this.movingTaskId !== null) {
                        this.dragOverStatus = null;
                        return;
                    }

                    const fromStatus = this.draggingTask.status;
                    const taskId = this.draggingTask.id;
                    const taskNode = document.getElementById(`kanban-task-${taskId}`);
                    const targetColumn = this.$refs[`column-${status}`];

                    if (!taskNode || !targetColumn) {
                        this.dragOverStatus = null;
                        return;
                    }

                    this.movingTaskId = taskId;
                    const previousParent = taskNode.parentElement;
                    const previousNextSibling = taskNode.nextElementSibling;
                    targetColumn.prepend(taskNode);
                    this.updateCount(fromStatus, -1);
                    this.updateCount(status, 1);
                    this.draggingTask.status = status;

                    const route = moveRouteTemplate.replace('__TASK__', taskId);
                    const formData = new URLSearchParams();
                    formData.append('_token', csrfToken);
                    formData.append('status', status);

                    try {
                        const response = await fetch(route, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                            },
                            body: formData.toString(),
                        });

                        if (!response.ok) {
                            throw new Error('Failed to move task');
                        }
                    } catch (error) {
                        if (previousParent) {
                            if (previousNextSibling) {
                                previousParent.insertBefore(taskNode, previousNextSibling);
                            } else {
                                previousParent.appendChild(taskNode);
                            }
                        }

                        this.updateCount(status, -1);
                        this.updateCount(fromStatus, 1);
                        this.draggingTask.status = fromStatus;
                    } finally {
                        this.dragOverStatus = null;
                        this.movingTaskId = null;
                    }
                },
            };
        }
```

to:

```js
        function kanbanBoard(csrfToken, moveRouteTemplate, projectId, currentUserId) {
            return {
                draggingTask: null,
                dragOverStatus: null,
                movingTaskId: null,
                viewers: [],
                init() {
                    window.Echo.join(`project.${projectId}`)
                        .here((users) => { this.viewers = users; })
                        .joining((user) => { this.viewers.push(user); })
                        .leaving((user) => { this.viewers = this.viewers.filter((u) => u.id !== user.id); })
                        .listen('TaskMoved', (event) => {
                            if (event.movedBy === currentUserId) {
                                return;
                            }

                            const taskNode = document.getElementById(`kanban-task-${event.taskId}`);
                            if (!taskNode) {
                                return;
                            }

                            const fromStatus = ['todo', 'doing', 'done'].find((key) => this.$refs[`column-${key}`] === taskNode.parentElement);
                            if (!fromStatus || fromStatus === event.toStatus) {
                                return;
                            }

                            const moved = this.moveCardTo(event.taskId, event.toStatus);
                            if (moved) {
                                this.updateCount(fromStatus, -1);
                                this.updateCount(event.toStatus, 1);
                            }
                        });
                },
                startDrag(task) {
                    this.draggingTask = task;
                },
                updateCount(status, delta) {
                    const countNode = this.$refs[`count-${status}`];
                    if (!countNode) {
                        return;
                    }

                    const current = Number.parseInt(countNode.textContent || '0', 10) || 0;
                    countNode.textContent = String(Math.max(0, current + delta));
                },
                moveCardTo(taskId, toStatus) {
                    const taskNode = document.getElementById(`kanban-task-${taskId}`);
                    const targetColumn = this.$refs[`column-${toStatus}`];

                    if (!taskNode || !targetColumn) {
                        return null;
                    }

                    const previousParent = taskNode.parentElement;
                    const previousNextSibling = taskNode.nextElementSibling;
                    targetColumn.prepend(taskNode);

                    return { previousParent, previousNextSibling, taskNode };
                },
                async dropTo(status) {
                    if (!this.draggingTask || this.draggingTask.status === status || this.movingTaskId !== null) {
                        this.dragOverStatus = null;
                        return;
                    }

                    const fromStatus = this.draggingTask.status;
                    const taskId = this.draggingTask.id;

                    this.movingTaskId = taskId;
                    const moveResult = this.moveCardTo(taskId, status);

                    if (!moveResult) {
                        this.movingTaskId = null;
                        this.dragOverStatus = null;
                        return;
                    }

                    const { previousParent, previousNextSibling, taskNode } = moveResult;
                    this.updateCount(fromStatus, -1);
                    this.updateCount(status, 1);
                    this.draggingTask.status = status;

                    const route = moveRouteTemplate.replace('__TASK__', taskId);
                    const formData = new URLSearchParams();
                    formData.append('_token', csrfToken);
                    formData.append('status', status);

                    try {
                        const response = await fetch(route, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                                'X-Socket-ID': window.Echo.socketId(),
                            },
                            body: formData.toString(),
                        });

                        if (!response.ok) {
                            throw new Error('Failed to move task');
                        }
                    } catch (error) {
                        if (previousParent) {
                            if (previousNextSibling) {
                                previousParent.insertBefore(taskNode, previousNextSibling);
                            } else {
                                previousParent.appendChild(taskNode);
                            }
                        }

                        this.updateCount(status, -1);
                        this.updateCount(fromStatus, 1);
                        this.draggingTask.status = fromStatus;
                    } finally {
                        this.dragOverStatus = null;
                        this.movingTaskId = null;
                    }
                },
            };
        }
```

`moveCardTo` is the shared DOM-move primitive both `dropTo()` (a local drag) and `init()`'s `TaskMoved` listener (a remote move) call — it only moves the card and reports what it moved; the caller is responsible for updating counts and any local drag-state, since the two callers know their "from" column differently (`dropTo()` already tracks it via `this.draggingTask.status`; the remote listener discovers it by checking which column ref currently contains the card, since a remotely-moved card was never locally "picked up").

- [ ] **Step 2: Wire the new constructor args and add a presence avatar stack**

In the same file, change the `x-data` call and add a small viewers indicator. Change:

```blade
                <div
                    x-data="kanbanBoard('{{ csrf_token() }}', '{{ url('/tasks/__TASK__/move') }}')"
                    class="grid md:grid-cols-3 gap-4"
                >
```

to:

```blade
                <div
                    x-data="kanbanBoard('{{ csrf_token() }}', '{{ url('/tasks/__TASK__/move') }}', {{ $project->id }}, {{ auth()->id() }})"
                    class="grid md:grid-cols-3 gap-4"
                >
                    <template x-if="viewers.length">
                        <div class="md:col-span-3 flex items-center gap-2 text-xs text-slate-400 mb-1">
                            <span>Viewing now:</span>
                            <template x-for="viewer in viewers" :key="viewer.id">
                                <span class="tn-badge-neutral" x-text="viewer.name"></span>
                            </template>
                        </div>
                    </template>
```

- [ ] **Step 3: Run the full test suite**

Run: `php artisan test`
Expected: no failures, same pass count as after Task 3 (this task adds no PHP tests, and doesn't touch any PHP file).

- [ ] **Step 4: Verify manually with two browser sessions**

Run: `npm run build`

With the dev server running, open two different browser sessions (e.g. one normal window, one incognito) logged in as two different seeded users who share a workspace (`admin@example.com` and `member@example.com`, both password `password`, both in `demo-workspace`), both viewing the same project's `/projects/{id}/tasks` page:

1. Confirm each session's "Viewing now" row shows the OTHER user's name (not its own).
2. In one session, drag a task to a different column.
3. Confirm the OTHER session's board updates within ~1 second — the card visually moves to the new column and both column counts update — without that session refreshing.
4. Confirm the session that performed the drag does NOT see a duplicate/flicker move (the `movedBy === currentUserId` check in `init()`'s listener, combined with `toOthers()` server-side, should mean it only sees its own already-applied optimistic move, not a second one from the broadcast).

- [ ] **Step 5: Commit**

```bash
git add resources/views/tasks/index.blade.php
git commit -m "feat: add Kanban board presence and live task-move updates"
```

---

### Task 5: Live task comments

**Files:**
- Modify: `resources/views/tasks/show.blade.php`

**Interfaces:**
- Consumes: `window.Echo` (Task 3), the `private-task.{taskId}` channel and `TaskCommentPosted`'s payload shape `{ commentId, body, authorName, postedAt }` (Task 2).

This task has no PHP tests — it's browser-only behavior. Verification is manual, described in Step 3.

- [ ] **Step 1: Add the live-append Alpine component**

In `resources/views/tasks/show.blade.php`, the comments card currently reads:

```blade
                <div class="tn-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">Comments</h3>
                    <form method="POST" action="{{ route('tasks.comments.store', $task) }}" class="space-y-2">@csrf
                        <textarea name="body" class="tn-input w-full" rows="3" placeholder="Write a comment. Use @email for mentions." required></textarea>
                        <x-primary-button>Post comment</x-primary-button>
                    </form>
                    <div class="mt-4 space-y-3">
                        @forelse($task->comments as $comment)
                            <div class="rounded-lg border border-slate-800 p-3">
                                <div class="text-xs text-slate-500">{{ $comment->user->name }} · {{ $comment->created_at->diffForHumans() }}</div>
                                <p class="text-sm mt-1 text-slate-200">{{ $comment->body }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No comments yet.</p>
                        @endforelse
                    </div>
                </div>
```

Change it to:

```blade
                <div class="tn-card" x-data="{
                    liveComments: [],
                    init() {
                        window.Echo.private('task.{{ $task->id }}')
                            .listen('TaskCommentPosted', (event) => {
                                this.liveComments.push(event);
                            });
                    },
                }">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">Comments</h3>
                    <form method="POST" action="{{ route('tasks.comments.store', $task) }}" class="space-y-2">@csrf
                        <textarea name="body" class="tn-input w-full" rows="3" placeholder="Write a comment. Use @email for mentions." required></textarea>
                        <x-primary-button>Post comment</x-primary-button>
                    </form>
                    <div class="mt-4 space-y-3">
                        @forelse($task->comments as $comment)
                            <div class="rounded-lg border border-slate-800 p-3">
                                <div class="text-xs text-slate-500">{{ $comment->user->name }} · {{ $comment->created_at->diffForHumans() }}</div>
                                <p class="text-sm mt-1 text-slate-200">{{ $comment->body }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No comments yet.</p>
                        @endforelse
                        <template x-for="comment in liveComments" :key="comment.commentId">
                            <div class="rounded-lg border border-brand-500/40 p-3">
                                <div class="text-xs text-slate-500" x-text="comment.authorName"></div>
                                <p class="text-sm mt-1 text-slate-200" x-text="comment.body"></p>
                            </div>
                        </template>
                    </div>
                </div>
```

(The "No comments yet." empty-state text stays governed only by the server-rendered `@forelse`, matching the existing behavior — a live comment arriving doesn't retroactively hide that message if the page loaded with zero comments; this is a acceptable minor inconsistency, not worth extra Alpine logic to resolve, since a live comment event and a full page with zero comments happening simultaneously is a narrow edge case.)

- [ ] **Step 2: Run the full test suite**

Run: `php artisan test`
Expected: no failures, same pass count as after Task 4 (this task adds no PHP tests).

- [ ] **Step 3: Verify manually with two browser sessions**

Run: `npm run build`

With two browser sessions (as in Task 4) both viewing the SAME task's `/tasks/{id}` show page:

1. In one session, post a comment.
2. Confirm the OTHER session's comment list gains the new comment within ~1 second, without refreshing (it will render with a slightly different border color, from the `liveComments` template, than server-rendered ones — that's expected and fine, this is intentionally the smallest possible addition, not a full redesign of the comment list).
3. Confirm the posting session's own page (which does a normal full-page reload on submit) shows the comment normally, without duplication.

- [ ] **Step 4: Commit**

```bash
git add resources/views/tasks/show.blade.php
git commit -m "feat: live-append task comments from other viewers"
```

---

### Task 6: Live notification badge

**Files:**
- Modify: `resources/views/layouts/navigation.blade.php`

**Interfaces:**
- Consumes: `window.Echo` (Task 3), the `private-user.{userId}` channel and the `NotificationCreated` event (Task 2) — this task only needs the event to arrive, not its payload contents.

This task has no PHP tests — it's browser-only behavior. Verification is manual, described in Step 3.

- [ ] **Step 1: Convert the badge to a small Alpine component**

In `resources/views/layouts/navigation.blade.php`, the Notifications nav link currently reads:

```blade
                    <x-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                        Notifications
                        @php
                            $unreadCount = auth()->user()->workspaceNotifications()
                                ->where('workspace_id', session('current_workspace_id'))
                                ->whereNull('read_at')
                                ->count();
                        @endphp
                        @if($unreadCount > 0)
                            <span class="ml-1 tn-badge-brand">{{ $unreadCount }}</span>
                        @endif
                    </x-nav-link>
```

Change it to:

```blade
                    @php
                        $unreadCount = auth()->user()->workspaceNotifications()
                            ->where('workspace_id', session('current_workspace_id'))
                            ->whereNull('read_at')
                            ->count();
                    @endphp
                    <x-nav-link
                        :href="route('notifications.index')"
                        :active="request()->routeIs('notifications.*')"
                        x-data="{ count: {{ $unreadCount }} }"
                        x-init="window.Echo.private('user.{{ auth()->id() }}').listen('NotificationCreated', () => { count++; })"
                    >
                        Notifications
                        <span class="ml-1 tn-badge-brand" x-show="count > 0" x-text="count"></span>
                    </x-nav-link>
```

(The `@php` block moves above the component tag since it's now needed for both the initial Blade-rendered value AND the Alpine `x-data` seed — same computed value, used in two places instead of one. The badge's visibility switches from a server-rendered `@if` to a client-reactive `x-show`, and its number switches from a static `{{ }}` to a reactive `x-text` bound to `count`.)

- [ ] **Step 2: Run the full test suite**

Run: `php artisan test`
Expected: no failures, same pass count as after Task 5 (this task adds no PHP tests).

- [ ] **Step 3: Verify manually with two browser sessions**

Run: `npm run build`

With two browser sessions logged in as two different seeded users sharing a workspace:

1. Note the current unread-count badge (or its absence) in each session's nav.
2. Trigger an action that creates a notification for one of the two users while their session is open — the easiest is posting a task comment that `@mentions` the other user's seeded email (e.g. `@member@example.com` in a comment, if logged in as `admin@example.com`) on a task in the shared workspace.
3. Confirm the mentioned user's OTHER open session's badge count increments within ~1 second, without navigating or refreshing.

- [ ] **Step 4: Commit**

```bash
git add resources/views/layouts/navigation.blade.php
git commit -m "feat: live-update the notification unread-count badge"
```

---

## Self-Review

**Spec coverage:**
- Broadcasting infrastructure (Pusher config, broadcasting auth route, channel authorization mirroring existing policies): Task 1. ✅
- Three `ShouldBroadcast` events dispatched via `broadcast()->toOthers()` from the three specified call sites: Task 2. ✅
- Frontend Echo initialization: Task 3. ✅
- Kanban presence + live task moves, extending (not rewriting) the existing `kanbanBoard()` component, with the `X-Socket-ID` header addition: Task 4. ✅
- Live comment append via a small Alpine wrapper, existing POST-and-redirect form left untouched: Task 5. ✅
- Live notification badge, converting the existing server-rendered conditional to a reactive one seeded with the same initial value: Task 6. ✅
- Testing boundary (Event::fake()-based tests for dispatch in Tasks 1-2; HTTP-based channel-authorization tests in Task 1; no automated tests for live socket delivery in Tasks 3-6, manual two-browser verification instead): reflected throughout. ✅
- Non-goals (no Reverb, no AJAX rewrite of the comment form, no other event types, no chat/typing/read-receipts beyond presence): no task introduces any of these. ✅

**Placeholder scan:** none found — every step has complete, exact code, exact file paths, and exact manual-verification steps.

**Type consistency:** `TaskMoved`'s payload keys (`taskId`, `toStatus`, `movedBy`) defined in Task 2 are read by exactly those same names in Task 4's `init()` listener (`event.taskId`, `event.toStatus`, `event.movedBy`). `TaskCommentPosted`'s payload keys (`commentId`, `body`, `authorName`, `postedAt`) defined in Task 2 are read by exactly those same names in Task 5's `liveComments` template (`comment.commentId`, `comment.body`, `comment.authorName`). Channel names (`project.{projectId}`, `task.{taskId}`, `user.{userId}`) are identical strings across Task 1's `routes/channels.php` registrations, Task 2's events' `broadcastOn()` methods, and Tasks 4-6's frontend `Echo.join`/`Echo.private` calls (accounting for Echo's automatic `presence-`/`private-` prefixing, which matches how Task 1's tests also account for it when calling `/broadcasting/auth` directly).

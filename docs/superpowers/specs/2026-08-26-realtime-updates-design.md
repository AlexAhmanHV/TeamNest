# Real-Time Updates (Broadcasting) — Design

## Overview

TeamNest currently has no live cross-user updates: the Kanban board updates optimistically only for the acting user (other viewers see the old state until they reload), task comments require a page refresh to see what someone else posted, and the notification bell's unread count is only computed on page load. This spec adds real broadcasting — task moves, task comments, and notifications all push live to other connected users — using Laravel's broadcasting layer against a hosted Pusher Channels app (not self-hosted Reverb).

This is the third and final of three "next level" feature passes agreed with the user (onboarding/empty-states → global search → real-time updates), both prior passes already shipped and pushed.

## Goals

- Dragging a task to a new column on the Kanban board is visible, live, to every other user currently viewing that board — no refresh.
- Posting a task comment is visible, live, to every other user currently viewing that task — no refresh.
- A user receiving a new notification sees the nav bar's unread-count badge increment live, without navigating.
- The Kanban board shows a small presence indicator of who else is currently viewing it.
- Channel authorization enforces the exact same tenancy/permission rules the app's HTTP routes already enforce — broadcasting introduces no new way to see another workspace's or another user's data.

## Non-goals

- No self-hosted Laravel Reverb server — this uses Pusher's hosted Channels service instead (the user's explicit choice, given TeamNest isn't deployed live yet and a hosted service avoids needing a persistent WebSocket process on whatever host is chosen later).
- No AJAX rewrite of the comment-posting form — it keeps its existing plain POST-and-redirect behavior; only *other* open tabs/sessions get the live update.
- No broadcasting of any other event type (project creation, member invites, activity log, analytics) — scoped to task moves, task comments, and notifications only.
- No chat, typing indicators, or read-receipts beyond the presence roster itself.
- No automated test coverage of actual live socket delivery (DOM updates on receipt, the presence avatar stack) — verified manually with two browser sessions, since there is no PHPUnit surface for a real Pusher connection.

## Infrastructure

- **Backend**: `BROADCAST_CONNECTION` changes from `log` to `pusher`. Requires the `pusher/pusher-php-server` Composer package (Laravel's official first-party driver for Pusher-protocol services — this is the same package used whether the backend is real Pusher or self-hosted Reverb, so nothing here is Pusher-specific beyond the env values). New env vars: `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER` — the user already has a Pusher account and will supply these values when the implementation plan reaches that step.
- **Frontend**: `laravel-echo` + `pusher-js` added as npm dependencies, initialized once in `resources/js/bootstrap.js` (the existing file already scaffolded by Breeze for exactly this purpose, currently empty of Echo setup) using the `VITE_PUSHER_*` env vars Laravel's default `.env.example` already documents for this exact purpose.
- **Channel authorization**: new `routes/channels.php` (currently absent — confirmed via `ls`), registered through the existing `BroadcastServiceProvider` (also currently absent — Laravel 12's default skeleton omits it unless broadcasting is set up; created fresh here per Laravel's standard `install:broadcasting` scaffold).

## Events & channels

| Event | Dispatched from | Channel | Payload |
|---|---|---|---|
| `App\Events\TaskMoved` | `TaskController::move`, after `UpdateTask` persists the change | `presence-project.{projectId}` | `taskId`, `toStatus`, `movedBy` (user id) |
| `App\Events\TaskCommentPosted` | `TaskCommentController::store`, after the comment is created | `private-task.{taskId}` | `commentId`, `body`, `authorName`, `postedAt` |
| `App\Events\NotificationCreated` | `WorkspaceNotifier::notify`, after the row is created | `private-user.{userId}` | `notificationId`, `type` |

All three events implement `ShouldBroadcast` and are dispatched with `->toOthers()` so the acting user's own tab (which already applied the change optimistically or via its own page reload) doesn't receive a redundant echo of its own action. Each event's `broadcastWith()` returns only the minimal payload shown above — clients that need more (e.g. the full comment body for rendering) get it from that payload directly rather than re-fetching, since these payloads are already small.

`routes/channels.php` authorizes each channel using the same policy checks the equivalent HTTP route already uses:
- `project.{projectId}` (presence): authorized if the user belongs to the project's workspace (same check `TaskController` already performs before allowing access to that project's board), returning `['id' => $user->id, 'name' => $user->name]` as the presence payload.
- `task.{taskId}` (private): authorized via `TaskPolicy::view` (the same policy `TaskCommentController::store` already calls).
- `user.{userId}` (private): authorized if `(int) $userId === auth()->id()` — a user only ever needs their own notification channel.

## Kanban board integration

`resources/views/tasks/index.blade.php`'s existing `kanbanBoard()` Alpine function (its `startDrag`/`dropTo`/`updateCount` methods) is extended, not rewritten:

- An `init()` lifecycle method joins the project's presence channel, listens for `TaskMoved`, and maintains a `viewers` array rendered as a small initials-avatar stack above the board.
- The existing `dropTo()`'s move logic (moving the `<article>` DOM node between `$refs.column-*`, adjusting both column counts) is factored into a shared `moveCardTo(taskId, toStatus)` helper, called both by `dropTo()` (the local drag) and by the new `TaskMoved` listener (a remote move) — one implementation of "how a card visually moves," not two.
- If a remotely-moved task's card isn't present in the current filtered/paginated view, the listener silently no-ops — matching `dropTo()`'s own existing guard for a missing ref.
- `dropTo()`'s existing `fetch()` call gains one new header: `'X-Socket-ID': Echo.socketId()`, so the backend's `->toOthers()` can correctly identify and exclude the connection that caused the move (Echo does this automatically for axios requests; this app's hand-written `fetch()` needs it added explicitly).

## Comments & notifications integration

- `resources/views/tasks/show.blade.php`'s comment list gets a small Alpine wrapper (new `x-data`, the form and its POST/redirect behavior are otherwise untouched) that subscribes to `private-task.{id}` and prepends any `TaskCommentPosted` event's payload as a new comment block — visible only to *other* open sessions viewing that same task, since the poster's own page already shows it via the normal reload.
- `resources/views/layouts/navigation.blade.php`'s unread-count badge (currently a bare `@php` block computing a number once per page load) becomes a small Alpine component seeded with that same server-computed initial value via `x-data="{ count: {{ $unreadCount }} }"`, subscribing to `private-user.{id}` and incrementing `count` on `NotificationCreated` — the badge's existing conditional visibility (`@if($unreadCount > 0)`) becomes an Alpine `x-show="count > 0"` driven by the same reactive value.

## Testing

- Feature tests use `Illuminate\Support\Facades\Event::fake()` and assert `Event::assertDispatched(TaskMoved::class, fn ($e) => ...)` (checking the event's channel and payload) from `TaskController::move`, `TaskCommentController::store`, and `WorkspaceNotifier::notify` — the standard Laravel pattern for testing broadcast-triggering code without a live socket.
- `routes/channels.php`'s three authorization callbacks each get a direct unit-style test (calling the callback function with a user who should be denied and one who should be allowed), covering: a user outside the project's workspace is denied `project.{id}`; a user who can't view the task is denied `task.{id}`; a user is denied another user's `user.{id}` channel.
- No automated test for live DOM behavior (see Non-goals) — verified manually with two browser sessions per event type, confirmed against a real Pusher connection using the user's provided credentials.
- Full `php artisan test` run as the regression check after every task, per this project's established pattern. Baseline before this plan: 51 passed, 161 assertions.

## Future ideas (not in this spec)

- Deploying TeamNest live and self-hosting Reverb instead of Pusher, if the hosting choice later makes that preferable (the abstraction this spec uses makes that a config change, not a rewrite).
- Broadcasting additional event types (project creation, member joins) if the "feels alive" surface area is judged too narrow once this ships.
- Read receipts or typing indicators on comments, if that's ever wanted.

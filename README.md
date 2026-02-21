# Workspace Projects

Workspace Projects is a portfolio-grade Laravel Mini-SaaS for managing projects and tasks inside isolated workspaces.

## Features

- Laravel 12 + Breeze (Blade + Tailwind) authentication
- Workspace-scoped multi-tenancy with session-based current workspace
- Workspace RBAC (`admin`, `member`)
- Invitation flow with expiring token links and secure token hashing
- Queued invitation and reminder emails (database queue)
- Activity log with actor, subject, action, metadata JSON
- Task audit trail storing `before` / `after` diffs for changed tracked fields
- Project and task soft delete + restore + force delete trash views
- Task filtering by status/assignee/overdue/priority and search by title/description
- Kanban drag-and-drop board with optimistic UI updates
- Task comments with `@email` mentions + file attachments
- In-app notifications inbox with unread state
- Daily due-date reminder pipeline (queued email + in-app notification)
- Saved task filter views and bulk task actions
- Workspace settings (branding/role/timezone/retention)
- Workspace analytics dashboard (completion, overdue, workload)
- API + personal access tokens (Sanctum)
- Demo seed data and comprehensive feature tests

## Requirements

- PHP 8.2+
- Composer
- Node.js + npm (for frontend assets)
- SQLite (default) or MySQL/PostgreSQL via `.env`

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan storage:link
php artisan serve
```

## Queue + Scheduler

Default queue driver is `database`.

```bash
php artisan queue:work
```

Invitation reminders are scheduled daily through:

- Command: `php artisan workspace:send-invitation-reminders`
- Scheduler: configured in `routes/console.php`

Task due reminders:

- Command: `php artisan workspace:send-task-reminders`
- Scheduler: daily at `08:00` in `routes/console.php`

Run scheduler locally:

```bash
php artisan schedule:work
```

## Demo Accounts

- `admin@example.com` / `password`
- `member@example.com` / `password`

## Running Tests

```bash
php artisan test
```

## Quality Checks

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
```

## API Usage

Create a token from `API Tokens` page in-app (`/api-tokens`) or via `POST /api/tokens`.

Use:

- Header: `Authorization: Bearer {token}`
- Header: `X-Workspace-Id: {workspace_id}` (required when user belongs to multiple workspaces)

Endpoints:

- `GET /api/projects`
- `GET /api/projects/{project}/tasks`
- `GET /api/tokens`
- `POST /api/tokens`
- `DELETE /api/tokens/{tokenId}`

## CI

GitHub Actions pipeline (`.github/workflows/ci.yml`) runs:

- PHP syntax checks (`php -l`)
- Laravel Pint
- PHPStan/Larastan
- PHPUnit feature tests

## Key Design Choices

- Cross-workspace resource access returns `404` to prevent data leakage.
- Workspace data is always resolved against the current session workspace.
- Invitation tokens are never stored in plain text; only SHA-256 hashes are stored.
- Admin safeguards prevent removing/demoting the last admin or workspace owner.

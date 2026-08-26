<?php

namespace App\Http\Controllers;

use App\Actions\Task\AssignTask;
use App\Actions\Task\CompleteTask;
use App\Actions\Task\CreateTask;
use App\Actions\Task\DeleteTask;
use App\Actions\Task\ForceDeleteTask;
use App\Actions\Task\RestoreTask;
use App\Actions\Task\UpdateTask;
use App\Enums\TaskStatus;
use App\Events\TaskMoved;
use App\Http\Requests\AssignTaskRequest;
use App\Http\Requests\MoveTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\TaskFilterRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskSavedView;
use App\Services\CurrentWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Project $project, TaskFilterRequest $request): View
    {
        abort_unless($project->workspace_id === app(CurrentWorkspace::class)->requireForUser()->id, 404);
        $this->authorize('view', $project);

        $query = $project->tasks()->with(['assignee', 'creator']);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($priority = $request->string('priority')->toString()) {
            $query->where('priority', $priority);
        }

        $assignee = $request->string('assignee')->toString();
        if ($assignee === 'me') {
            $query->where('assigned_to_user_id', $request->user()->id);
        } elseif ($assignee === 'unassigned') {
            $query->whereNull('assigned_to_user_id');
        } elseif ($assignee !== '' && is_numeric($assignee)) {
            $query->where('assigned_to_user_id', (int) $assignee);
        }

        if ($request->boolean('overdue')) {
            $query->whereDate('due_date', '<', now()->toDateString())
                ->where('status', '!=', TaskStatus::Done->value);
        }

        if ($q = $request->string('q')->toString()) {
            $query->where(function (Builder $builder) use ($q): void {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $kanbanQuery = (clone $query)
            ->orderByRaw("CASE WHEN priority = 'high' THEN 1 WHEN priority = 'med' THEN 2 ELSE 3 END")
            ->latest();

        return view('tasks.index', [
            'project' => $project->load('workspace'),
            'tasks' => $query->latest()->paginate(12)->withQueryString(),
            'members' => $project->workspace->users()->orderBy('name')->get(),
            'savedViews' => TaskSavedView::query()
                ->where('workspace_id', $project->workspace_id)
                ->where('project_id', $project->id)
                ->where('user_id', $request->user()->id)
                ->orderBy('name')
                ->get(),
            'kanbanTasksByStatus' => $kanbanQuery
                ->get()
                ->groupBy(fn (Task $task) => $task->status->value),
        ]);
    }

    public function show(Task $task, CurrentWorkspace $currentWorkspace): View
    {
        abort_unless($task->project->workspace_id === $currentWorkspace->requireForUser()->id, 404);
        $this->authorize('view', $task);

        return view('tasks.show', [
            'task' => $task->load([
                'project.workspace',
                'assignee',
                'creator',
                'comments.user',
                'attachments.uploader',
            ]),
        ]);
    }

    public function trash(Project $project): View
    {
        abort_unless($project->workspace_id === app(CurrentWorkspace::class)->requireForUser()->id, 404);
        $this->authorize('view', $project);

        return view('tasks.trash', [
            'project' => $project,
            'tasks' => $project->tasks()->onlyTrashed()->latest('deleted_at')->paginate(12),
        ]);
    }

    public function store(StoreTaskRequest $request, Project $project, CreateTask $action): RedirectResponse
    {
        abort_unless($project->workspace_id === app(CurrentWorkspace::class)->requireForUser()->id, 404);
        $this->authorize('view', $project);

        $action->execute($project, $request->user(), $request->validated());

        return back()->with('status', 'Task created.');
    }

    public function update(UpdateTaskRequest $request, Task $task, UpdateTask $action): RedirectResponse
    {
        abort_unless($task->project->workspace_id === app(CurrentWorkspace::class)->requireForUser()->id, 404);
        $this->authorize('update', $task);

        $action->execute($task, $request->user(), $request->validated());

        return back()->with('status', 'Task updated.');
    }

    public function destroy(Task $task, DeleteTask $action): RedirectResponse
    {
        abort_unless($task->project->workspace_id === app(CurrentWorkspace::class)->requireForUser()->id, 404);
        $this->authorize('delete', $task);
        $action->execute($task, auth()->user());

        return back()->with('status', 'Task deleted.');
    }

    public function restore(Task $task, RestoreTask $action): RedirectResponse
    {
        abort_unless($task->project->workspace_id === app(CurrentWorkspace::class)->requireForUser()->id, 404);
        $this->authorize('restore', $task);
        $action->execute($task, auth()->user());

        return back()->with('status', 'Task restored.');
    }

    public function forceDelete(Task $task, ForceDeleteTask $action): RedirectResponse
    {
        abort_unless($task->project->workspace_id === app(CurrentWorkspace::class)->requireForUser()->id, 404);
        $this->authorize('forceDelete', $task);
        $action->execute($task, auth()->user());

        return back()->with('status', 'Task permanently deleted.');
    }

    public function complete(Task $task, CompleteTask $action): RedirectResponse
    {
        abort_unless($task->project->workspace_id === app(CurrentWorkspace::class)->requireForUser()->id, 404);
        $this->authorize('update', $task);
        $action->execute($task, auth()->user());

        return back()->with('status', 'Task completed.');
    }

    public function assign(AssignTaskRequest $request, Task $task, CurrentWorkspace $currentWorkspace, AssignTask $action): RedirectResponse
    {
        abort_unless($task->project->workspace_id === $currentWorkspace->requireForUser()->id, 404);
        $this->authorize('update', $task);
        $workspace = $currentWorkspace->requireForUser();
        $assigneeId = $request->validated('assigned_to_user_id');

        if ($assigneeId) {
            abort_unless($workspace->users()->whereKey($assigneeId)->exists(), 422, 'Assignee must be workspace member.');
        }

        $action->execute($task, $request->user(), $assigneeId ? (int) $assigneeId : null);

        return back()->with('status', 'Task assignee updated.');
    }

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
}

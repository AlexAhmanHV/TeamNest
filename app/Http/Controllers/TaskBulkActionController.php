<?php

namespace App\Http\Controllers;

use App\Actions\Task\AssignTask;
use App\Actions\Task\DeleteTask;
use App\Actions\Task\UpdateTask;
use App\Http\Requests\TaskBulkActionRequest;
use App\Models\Project;
use App\Services\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;

class TaskBulkActionController extends Controller
{
    public function store(
        TaskBulkActionRequest $request,
        Project $project,
        CurrentWorkspace $currentWorkspace,
        UpdateTask $updateTask,
        AssignTask $assignTask,
        DeleteTask $deleteTask
    ): RedirectResponse {
        $workspace = $currentWorkspace->requireForUser();
        abort_unless($project->workspace_id === $workspace->id, 404);
        $this->authorize('view', $project);

        $tasks = $project->tasks()->whereIn('id', $request->validated('task_ids'))->get();
        $assigneeId = $request->integer('assigned_to_user_id');
        if ($request->string('action')->toString() === 'assign' && $assigneeId) {
            abort_unless($workspace->users()->whereKey($assigneeId)->exists(), 422, 'Assignee must be workspace member.');
        }

        foreach ($tasks as $task) {
            $this->authorize('update', $task);

            $action = $request->string('action')->toString();
            if ($action === 'status') {
                $updateTask->execute($task, $request->user(), ['status' => $request->string('status')->toString()]);
            } elseif ($action === 'priority') {
                $updateTask->execute($task, $request->user(), ['priority' => $request->string('priority')->toString()]);
            } elseif ($action === 'assign') {
                $assignTask->execute($task, $request->user(), $assigneeId ?: null);
            } elseif ($action === 'delete') {
                $deleteTask->execute($task, $request->user());
            }
        }

        return back()->with('status', 'Bulk action applied.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskSavedViewRequest;
use App\Models\Project;
use App\Models\TaskSavedView;
use App\Services\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;

class TaskSavedViewController extends Controller
{
    public function store(StoreTaskSavedViewRequest $request, Project $project, CurrentWorkspace $currentWorkspace): RedirectResponse
    {
        $workspace = $currentWorkspace->requireForUser();
        abort_unless($project->workspace_id === $workspace->id, 404);
        $this->authorize('view', $project);

        TaskSavedView::updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'name' => $request->string('name')->toString(),
            ],
            [
                'filters' => array_filter($request->only(['status', 'priority', 'assignee', 'overdue', 'q']), static fn ($value) => $value !== null && $value !== ''),
            ]
        );

        return back()->with('status', 'Saved view updated.');
    }

    public function destroy(TaskSavedView $savedView, CurrentWorkspace $currentWorkspace): RedirectResponse
    {
        $workspace = $currentWorkspace->requireForUser();
        abort_unless($savedView->workspace_id === $workspace->id && $savedView->user_id === auth()->id(), 404);

        $savedView->delete();

        return back()->with('status', 'Saved view deleted.');
    }
}

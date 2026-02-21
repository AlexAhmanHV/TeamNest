<?php

namespace App\Http\Controllers;

use App\Actions\Project\CreateProject;
use App\Actions\Project\DeleteProject;
use App\Actions\Project\ForceDeleteProject;
use App\Actions\Project\RestoreProject;
use App\Actions\Project\UpdateProject;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Services\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(CurrentWorkspace $currentWorkspace): View
    {
        $workspace = $currentWorkspace->requireForUser();

        return view('projects.index', [
            'workspace' => $workspace,
            'projects' => Project::query()
                ->where('workspace_id', $workspace->id)
                ->latest()
                ->paginate(10),
        ]);
    }

    public function trash(CurrentWorkspace $currentWorkspace): View
    {
        $workspace = $currentWorkspace->requireForUser();

        return view('projects.trash', [
            'workspace' => $workspace,
            'projects' => Project::onlyTrashed()
                ->where('workspace_id', $workspace->id)
                ->latest('deleted_at')
                ->paginate(10),
        ]);
    }

    public function store(StoreProjectRequest $request, CurrentWorkspace $currentWorkspace, CreateProject $action): RedirectResponse
    {
        $workspace = $currentWorkspace->requireForUser();

        $action->execute($workspace, $request->user(), $request->validated());

        return back()->with('status', 'Project created.');
    }

    public function show(Project $project): View
    {
        abort_unless($project->workspace_id === app(CurrentWorkspace::class)->requireForUser()->id, 404);
        $this->authorize('view', $project);

        $project->load(['tasks' => fn ($query) => $query->latest(), 'workspace']);

        return view('projects.show', [
            'project' => $project,
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project, UpdateProject $action): RedirectResponse
    {
        abort_unless($project->workspace_id === app(CurrentWorkspace::class)->requireForUser()->id, 404);
        $this->authorize('update', $project);

        $action->execute($project, $request->user(), $request->validated());

        return back()->with('status', 'Project updated.');
    }

    public function destroy(Project $project, DeleteProject $action): RedirectResponse
    {
        abort_unless($project->workspace_id === app(CurrentWorkspace::class)->requireForUser()->id, 404);
        $this->authorize('delete', $project);
        $action->execute($project, auth()->user());

        return redirect()->route('projects.index')->with('status', 'Project deleted.');
    }

    public function restore(Project $project, RestoreProject $action): RedirectResponse
    {
        abort_unless($project->workspace_id === app(CurrentWorkspace::class)->requireForUser()->id, 404);
        $this->authorize('restore', $project);
        $action->execute($project, auth()->user());

        return back()->with('status', 'Project restored.');
    }

    public function forceDelete(Project $project, ForceDeleteProject $action): RedirectResponse
    {
        abort_unless($project->workspace_id === app(CurrentWorkspace::class)->requireForUser()->id, 404);
        $this->authorize('forceDelete', $project);
        $action->execute($project, auth()->user());

        return back()->with('status', 'Project permanently deleted.');
    }
}

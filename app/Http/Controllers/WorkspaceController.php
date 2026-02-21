<?php

namespace App\Http\Controllers;

use App\Actions\Workspace\CreateWorkspace;
use App\Actions\Workspace\SwitchWorkspace;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\SwitchWorkspaceRequest;
use App\Services\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function index(CurrentWorkspace $currentWorkspace): View
    {
        $user = auth()->user();

        return view('workspaces.index', [
            'workspaces' => $user->workspaces()->orderBy('name')->get(),
            'currentWorkspace' => $currentWorkspace->forUser(),
        ]);
    }

    public function store(StoreWorkspaceRequest $request, CreateWorkspace $action): RedirectResponse
    {
        $workspace = $action->execute($request->user(), $request->string('name')->toString());

        session()->put('current_workspace_id', $workspace->id);

        return redirect()->route('dashboard')->with('status', 'Workspace created.');
    }

    public function switch(SwitchWorkspaceRequest $request, SwitchWorkspace $action): RedirectResponse
    {
        $workspace = $request->user()->workspaces()->findOrFail($request->integer('workspace_id'));
        $action->execute($request->user(), $workspace);

        return back()->with('status', 'Workspace switched.');
    }
}

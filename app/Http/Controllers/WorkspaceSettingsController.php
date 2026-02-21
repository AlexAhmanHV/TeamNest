<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateWorkspaceSettingsRequest;
use App\Services\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WorkspaceSettingsController extends Controller
{
    public function edit(CurrentWorkspace $currentWorkspace): View
    {
        $workspace = $currentWorkspace->requireForUser();
        $this->authorize('update', $workspace);

        return view('workspaces.settings', [
            'workspace' => $workspace,
            'timezones' => \DateTimeZone::listIdentifiers(),
        ]);
    }

    public function update(UpdateWorkspaceSettingsRequest $request, CurrentWorkspace $currentWorkspace): RedirectResponse
    {
        $workspace = $currentWorkspace->requireForUser();
        $this->authorize('update', $workspace);

        $data = [
            'name' => $request->string('name')->toString(),
            'slug' => Str::slug($request->string('name')->toString()).'-'.$workspace->id,
            'default_invite_role' => $request->string('default_invite_role')->toString(),
            'timezone' => $request->string('timezone')->toString(),
            'retention_days' => $request->integer('retention_days') ?: null,
        ];

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('workspace-logos', 'public');
        }

        $workspace->update($data);

        return back()->with('status', 'Workspace settings saved.');
    }
}

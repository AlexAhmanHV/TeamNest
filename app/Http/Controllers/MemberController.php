<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceRole;
use App\Http\Requests\UpdateMemberRoleRequest;
use App\Models\User;
use App\Services\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(CurrentWorkspace $currentWorkspace): View
    {
        $workspace = $currentWorkspace->requireForUser()->load([
            'users' => fn ($query) => $query->orderBy('name'),
            'invitations' => fn ($query) => $query->whereNull('accepted_at')->latest(),
        ]);

        $this->authorize('manageMembers', $workspace);

        return view('members.index', [
            'workspace' => $workspace,
        ]);
    }

    public function updateRole(UpdateMemberRoleRequest $request, User $user, CurrentWorkspace $currentWorkspace): RedirectResponse
    {
        $workspace = $currentWorkspace->requireForUser();
        $this->authorize('manageMembers', $workspace);

        abort_if($workspace->owner_user_id === $user->id, 422, 'Cannot change owner role.');

        $currentAdmins = $workspace->users()->wherePivot('role', WorkspaceRole::Admin->value)->count();
        $currentRole = $workspace->users()->whereKey($user->id)->firstOrFail()->pivot->role;

        if ($currentRole === WorkspaceRole::Admin->value && $request->string('role')->toString() !== WorkspaceRole::Admin->value && $currentAdmins <= 1) {
            abort(422, 'Cannot demote last admin.');
        }

        $workspace->users()->updateExistingPivot($user->id, ['role' => $request->string('role')->toString()]);

        return back()->with('status', 'Member role updated.');
    }

    public function destroy(User $user, CurrentWorkspace $currentWorkspace): RedirectResponse
    {
        $workspace = $currentWorkspace->requireForUser();
        $this->authorize('manageMembers', $workspace);

        abort_if($workspace->owner_user_id === $user->id, 422, 'Cannot remove owner.');

        $membership = $workspace->users()->whereKey($user->id)->firstOrFail();
        if ($membership->pivot->role === WorkspaceRole::Admin->value) {
            $adminCount = $workspace->users()->wherePivot('role', WorkspaceRole::Admin->value)->count();
            abort_if($adminCount <= 1, 422, 'Cannot remove last admin.');
        }

        $workspace->users()->detach($user->id);

        return back()->with('status', 'Member removed.');
    }
}

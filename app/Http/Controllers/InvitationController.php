<?php

namespace App\Http\Controllers;

use App\Actions\Invitation\AcceptInvitation;
use App\Actions\Invitation\InviteToWorkspace;
use App\Actions\Invitation\ResendInvitation;
use App\Actions\Invitation\RevokeInvitation;
use App\Http\Requests\InviteMemberRequest;
use App\Models\Invitation;
use App\Models\User;
use App\Services\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function store(InviteMemberRequest $request, CurrentWorkspace $currentWorkspace, InviteToWorkspace $action): RedirectResponse
    {
        $workspace = $currentWorkspace->requireForUser();
        $this->authorize('invite', $workspace);

        $action->execute(
            $workspace,
            $request->user(),
            $request->string('email')->toString(),
            $request->string('role')->toString()
        );

        return back()->with('status', 'Invitation queued.');
    }

    public function resend(Invitation $invitation, CurrentWorkspace $currentWorkspace, ResendInvitation $action): RedirectResponse
    {
        $workspace = $currentWorkspace->requireForUser();
        abort_unless($invitation->workspace_id === $workspace->id, 404);
        $this->authorize('invite', $workspace);

        $action->execute($invitation, auth()->user());

        return back()->with('status', 'Invitation resent.');
    }

    public function revoke(Invitation $invitation, CurrentWorkspace $currentWorkspace, RevokeInvitation $action): RedirectResponse
    {
        $workspace = $currentWorkspace->requireForUser();
        abort_unless($invitation->workspace_id === $workspace->id, 404);
        $this->authorize('invite', $workspace);

        $action->execute($invitation, auth()->user());

        return back()->with('status', 'Invitation revoked.');
    }

    public function acceptShow(string $token, Request $request): View|RedirectResponse
    {
        $invitation = Invitation::with('workspace')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        if (! $request->user()) {
            $request->session()->put('url.intended', $request->fullUrl());

            if (User::whereRaw('LOWER(email) = ?', [strtolower($invitation->email)])->exists()) {
                return redirect()->route('login');
            }

            return redirect()->route('register', ['email' => $invitation->email]);
        }

        return view('invitations.accept', [
            'invitation' => $invitation,
            'token' => $token,
        ]);
    }

    public function accept(string $token, Request $request, AcceptInvitation $action): RedirectResponse
    {
        $request->validate([]);

        $invitation = Invitation::with('workspace')
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();

        $action->execute($invitation, $request->user());

        session()->put('current_workspace_id', $invitation->workspace_id);

        return redirect()->route('dashboard')->with('status', 'Invitation accepted.');
    }
}

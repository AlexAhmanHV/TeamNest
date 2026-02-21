<?php

namespace App\Actions\Invitation;

use App\Jobs\SendInvitationEmailJob;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ActivityLogger;
use App\Services\WorkspaceNotifier;
use Illuminate\Support\Str;

class InviteToWorkspace
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly WorkspaceNotifier $workspaceNotifier
    ) {}

    public function execute(Workspace $workspace, User $invitedBy, string $email, string $role): Invitation
    {
        $token = Str::random(64);

        $invitation = Invitation::create([
            'workspace_id' => $workspace->id,
            'email' => Str::lower($email),
            'role' => $role,
            'token_hash' => hash('sha256', $token),
            'invited_by_user_id' => $invitedBy->id,
            'expires_at' => now()->addDays(7),
        ]);

        SendInvitationEmailJob::dispatch($invitation, $token);

        $this->activityLogger->log($workspace, $invitedBy->id, 'workspace.invite.sent', $invitation, [
            'email' => $invitation->email,
            'role' => $invitation->role,
        ]);

        $existingUser = User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($invitation->email)])
            ->first();
        if ($existingUser) {
            $this->workspaceNotifier->notify($existingUser->id, $workspace, 'workspace.invite.sent', [
                'email' => $invitation->email,
                'workspace_name' => $workspace->name,
                'invited_by' => $invitedBy->name,
            ]);
        }

        return $invitation;
    }
}

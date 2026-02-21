<?php

namespace App\Actions\Invitation;

use App\Models\Invitation;
use App\Models\User;
use App\Services\ActivityLogger;

class RevokeInvitation
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function execute(Invitation $invitation, User $actor): void
    {
        abort_if($invitation->accepted_at !== null, 422, 'Cannot revoke accepted invitation.');

        $workspace = $invitation->workspace;
        $email = $invitation->email;

        $invitation->delete();

        $this->activityLogger->log($workspace, $actor->id, 'workspace.invite.revoked', null, ['email' => $email]);
    }
}

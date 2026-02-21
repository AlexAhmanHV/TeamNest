<?php

namespace App\Actions\Invitation;

use App\Jobs\SendInvitationEmailJob;
use App\Models\Invitation;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Str;

class ResendInvitation
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function execute(Invitation $invitation, User $actor): Invitation
    {
        abort_if($invitation->accepted_at !== null, 422, 'Invitation already accepted.');

        $token = Str::random(64);

        $invitation->forceFill([
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(7),
        ])->save();

        SendInvitationEmailJob::dispatch($invitation, $token);

        $this->activityLogger->log($invitation->workspace, $actor->id, 'workspace.invite.resent', $invitation, [
            'email' => $invitation->email,
        ]);

        return $invitation;
    }
}

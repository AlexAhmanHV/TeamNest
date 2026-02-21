<?php

namespace App\Actions\Invitation;

use App\Models\Invitation;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

class AcceptInvitation
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function execute(Invitation $invitation, User $user): void
    {
        abort_if($invitation->accepted_at !== null, 422, 'Invitation already accepted.');
        abort_if($invitation->expires_at->isPast(), 422, 'Invitation expired.');
        abort_unless(strtolower($invitation->email) === strtolower($user->email), 403, 'Invitation email mismatch.');

        DB::transaction(function () use ($invitation, $user): void {
            $invitation->workspace->users()->syncWithoutDetaching([
                $user->id => [
                    'role' => $invitation->role,
                    'joined_at' => now(),
                ],
            ]);

            $invitation->forceFill(['accepted_at' => now()])->save();

            $this->activityLogger->log(
                $invitation->workspace,
                $user->id,
                'workspace.invite.accepted',
                $invitation,
                ['email' => $invitation->email, 'role' => $invitation->role]
            );
        });
    }
}

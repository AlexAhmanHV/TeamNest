<?php

namespace App\Jobs;

use App\Models\Invitation;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

class SendInvitationEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Invitation $invitation, private readonly string $token) {}

    public function handle(): void
    {
        $invitation = Invitation::with('workspace')->find($this->invitation->id);

        if (! $invitation || $invitation->accepted_at !== null || $invitation->expires_at->isPast()) {
            return;
        }

        Notification::send(
            (new AnonymousNotifiable)->route('mail', $invitation->email),
            new WorkspaceInvitationNotification($invitation, $this->token)
        );
    }
}

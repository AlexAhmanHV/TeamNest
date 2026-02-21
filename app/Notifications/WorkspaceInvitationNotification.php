<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Invitation $invitation, private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You are invited to a workspace')
            ->line("You have been invited to join {$this->invitation->workspace->name} as {$this->invitation->role}.")
            ->line('This invitation expires in 7 days.')
            ->action('Accept Invitation', route('invites.accept.show', ['token' => $this->token]));
    }
}

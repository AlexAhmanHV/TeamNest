<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitationReminderNotification extends Notification implements ShouldQueue
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
            ->subject('Reminder: workspace invitation pending')
            ->line("Reminder: your invitation to {$this->invitation->workspace->name} is still pending.")
            ->action('Accept Invitation', route('invites.accept.show', ['token' => $this->token]));
    }
}

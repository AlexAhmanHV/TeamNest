<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDueReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Task $task) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Task Reminder: '.$this->task->title)
            ->line('This task is due soon or overdue.')
            ->line('Task: '.$this->task->title)
            ->line('Due: '.($this->task->due_date?->toDateString() ?? 'n/a'))
            ->action('Open Task', route('tasks.show', $this->task));
    }
}

<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskDueReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTaskReminderEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Task $task, private readonly User $user) {}

    public function handle(): void
    {
        $this->user->notify(new TaskDueReminderNotification($this->task));
    }
}

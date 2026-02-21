<?php

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use App\Jobs\SendTaskReminderEmailJob;
use App\Models\Task;
use App\Services\WorkspaceNotifier;
use Illuminate\Console\Command;

class SendTaskDueRemindersCommand extends Command
{
    protected $signature = 'workspace:send-task-reminders';

    protected $description = 'Send reminders for overdue and due-soon assigned tasks';

    public function handle(WorkspaceNotifier $notifier): int
    {
        $tasks = Task::query()
            ->with(['project.workspace', 'assignee'])
            ->whereNotNull('assigned_to_user_id')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', now()->addDay()->toDateString())
            ->where('status', '!=', TaskStatus::Done->value)
            ->get();

        foreach ($tasks as $task) {
            if (! $task->assignee || ! $task->project?->workspace) {
                continue;
            }

            SendTaskReminderEmailJob::dispatch($task, $task->assignee);

            $notifier->notify($task->assignee->id, $task->project->workspace, 'task.reminder', [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'due_date' => $task->due_date?->toDateString(),
            ]);
        }

        $this->info('Queued '.$tasks->count().' task reminders.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Actions\Task;

use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;

class UpdateTask
{
    private const AUDITABLE_FIELDS = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'assigned_to_user_id',
    ];

    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function execute(Task $task, User $user, array $data): Task
    {
        $before = [];
        $after = [];

        foreach (self::AUDITABLE_FIELDS as $field) {
            if (array_key_exists($field, $data) && $task->{$field} != $data[$field]) {
                $before[$field] = $task->{$field};
                $after[$field] = $data[$field];
            }
        }

        $task->update($data);

        $this->activityLogger->log($task->project->workspace, $user->id, 'task.updated', $task, [
            'before' => $before,
            'after' => $after,
        ]);

        return $task;
    }
}

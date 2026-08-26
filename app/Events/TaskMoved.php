<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskMoved implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $projectId,
        public int $taskId,
        public string $toStatus,
        public int $movedBy,
    ) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel("project.{$this->projectId}")];
    }

    public function broadcastWith(): array
    {
        return [
            'taskId' => $this->taskId,
            'toStatus' => $this->toStatus,
            'movedBy' => $this->movedBy,
        ];
    }
}

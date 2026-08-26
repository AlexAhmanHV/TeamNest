<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCommentPosted implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $taskId,
        public int $commentId,
        public string $body,
        public string $authorName,
        public string $postedAt,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("task.{$this->taskId}")];
    }

    public function broadcastWith(): array
    {
        return [
            'commentId' => $this->commentId,
            'body' => $this->body,
            'authorName' => $this->authorName,
            'postedAt' => $this->postedAt,
        ];
    }
}

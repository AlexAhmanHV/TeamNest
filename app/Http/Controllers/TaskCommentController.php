<?php

namespace App\Http\Controllers;

use App\Events\TaskCommentPosted;
use App\Http\Requests\StoreTaskCommentRequest;
use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\CurrentWorkspace;
use App\Services\WorkspaceNotifier;
use Illuminate\Http\RedirectResponse;

class TaskCommentController extends Controller
{
    public function store(
        StoreTaskCommentRequest $request,
        Task $task,
        CurrentWorkspace $currentWorkspace,
        ActivityLogger $activityLogger,
        WorkspaceNotifier $notifier
    ): RedirectResponse {
        $workspace = $currentWorkspace->requireForUser();
        abort_unless($task->project->workspace_id === $workspace->id, 404);
        $this->authorize('view', $task);

        $comment = $task->comments()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()->id,
            'body' => $request->string('body')->toString(),
        ]);

        broadcast(new TaskCommentPosted(
            $task->id,
            $comment->id,
            $comment->body,
            $request->user()->name,
            $comment->created_at->toISOString(),
        ))->toOthers();

        preg_match_all('/@([A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,})/', $comment->body, $matches);
        $emails = array_unique(array_map('strtolower', $matches[1] ?? []));

        if ($emails !== []) {
            $mentionedUsers = User::query()
                ->whereIn('email', $emails)
                ->whereHas('workspaces', fn ($query) => $query->whereKey($workspace->id))
                ->get();

            foreach ($mentionedUsers as $mentionedUser) {
                if ($mentionedUser->id === $request->user()->id) {
                    continue;
                }

                $notifier->notify($mentionedUser->id, $workspace, 'task.mentioned', [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'comment_id' => $comment->id,
                    'by' => $request->user()->name,
                ]);
            }
        }

        $activityLogger->log($workspace, $request->user()->id, 'task.comment.created', $task, [
            'comment_id' => $comment->id,
        ]);

        return back()->with('status', 'Comment posted.');
    }
}

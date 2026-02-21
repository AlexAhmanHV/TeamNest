<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskAttachmentRequest;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Services\ActivityLogger;
use App\Services\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskAttachmentController extends Controller
{
    public function store(StoreTaskAttachmentRequest $request, Task $task, CurrentWorkspace $currentWorkspace, ActivityLogger $activityLogger): RedirectResponse
    {
        $workspace = $currentWorkspace->requireForUser();
        abort_unless($task->project->workspace_id === $workspace->id, 404);
        $this->authorize('update', $task);

        $file = $request->file('file');
        $path = $file->store('task-attachments/'.$workspace->id, 'public');

        $attachment = $task->attachments()->create([
            'workspace_id' => $workspace->id,
            'uploaded_by_user_id' => $request->user()->id,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes' => $file->getSize(),
        ]);

        $activityLogger->log($workspace, $request->user()->id, 'task.attachment.added', $task, [
            'attachment_id' => $attachment->id,
            'name' => $attachment->original_name,
        ]);

        return back()->with('status', 'Attachment uploaded.');
    }

    public function download(TaskAttachment $attachment, CurrentWorkspace $currentWorkspace): StreamedResponse
    {
        $workspace = $currentWorkspace->requireForUser();
        abort_unless($attachment->workspace_id === $workspace->id, 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function destroy(TaskAttachment $attachment, CurrentWorkspace $currentWorkspace, ActivityLogger $activityLogger): RedirectResponse
    {
        $workspace = $currentWorkspace->requireForUser();
        abort_unless($attachment->workspace_id === $workspace->id, 404);
        $this->authorize('update', $attachment->task);

        Storage::disk($attachment->disk)->delete($attachment->path);

        $activityLogger->log($workspace, auth()->id(), 'task.attachment.deleted', $attachment->task, [
            'attachment_id' => $attachment->id,
        ]);

        $attachment->delete();

        return back()->with('status', 'Attachment removed.');
    }
}

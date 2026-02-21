<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Task: {{ $task->title }}</h2></x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white p-6 rounded shadow-sm">
                <p class="text-sm text-gray-500">Project: <a class="text-cyan-700" href="{{ route('tasks.index', $task->project) }}">{{ $task->project->name }}</a></p>
                <div class="mt-2 grid md:grid-cols-4 gap-3 text-sm">
                    <div><span class="font-medium">Status:</span> {{ $task->status->value }}</div>
                    <div><span class="font-medium">Priority:</span> {{ $task->priority->value }}</div>
                    <div><span class="font-medium">Due:</span> {{ $task->due_date?->toDateString() ?? 'n/a' }}</div>
                    <div><span class="font-medium">Assignee:</span> {{ $task->assignee?->name ?? 'Unassigned' }}</div>
                </div>
                @if($task->description)
                    <p class="mt-3 text-sm">{{ $task->description }}</p>
                @endif
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded shadow-sm">
                    <h3 class="font-semibold mb-3">Comments</h3>
                    <form method="POST" action="{{ route('tasks.comments.store', $task) }}" class="space-y-2">@csrf
                        <textarea name="body" class="rounded border-gray-300 w-full" rows="3" placeholder="Write a comment. Use @email for mentions." required></textarea>
                        <x-primary-button>Post comment</x-primary-button>
                    </form>
                    <div class="mt-4 space-y-3">
                        @forelse($task->comments as $comment)
                            <div class="border rounded p-3">
                                <div class="text-xs text-gray-500">{{ $comment->user->name }} · {{ $comment->created_at->diffForHumans() }}</div>
                                <p class="text-sm mt-1">{{ $comment->body }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No comments yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white p-6 rounded shadow-sm">
                    <h3 class="font-semibold mb-3">Attachments</h3>
                    <form method="POST" action="{{ route('tasks.attachments.store', $task) }}" enctype="multipart/form-data" class="flex gap-2 items-center">@csrf
                        <input type="file" name="file" required class="text-sm">
                        <x-primary-button>Upload</x-primary-button>
                    </form>

                    <div class="mt-4 space-y-2">
                        @forelse($task->attachments as $attachment)
                            <div class="flex items-center justify-between border rounded p-2 text-sm">
                                <a class="text-cyan-700" href="{{ route('attachments.download', $attachment) }}">{{ $attachment->original_name }}</a>
                                <form method="POST" action="{{ route('attachments.destroy', $attachment) }}">@csrf @method('DELETE')<button class="text-red-600">Remove</button></form>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No attachments yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

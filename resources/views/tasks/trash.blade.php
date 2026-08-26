<x-app-layout>
    <x-slot name="header"><p class="tn-page-title">Task Trash: {{ $project->name }}</p></x-slot>
    <div class="py-8"><div class="max-w-6xl mx-auto sm:px-6 lg:px-8 tn-card">
        <a href="{{ route('tasks.index', $project) }}" class="text-sm tn-link">Back to Tasks</a>
        <table class="tn-table mt-4"><thead><tr><th>Title</th><th>Deleted At</th><th>Actions</th></tr></thead><tbody>
            @foreach($tasks as $task)
            <tr><td>{{ $task->title }}</td><td>{{ $task->deleted_at }}</td><td class="flex gap-2">
                <form method="POST" action="{{ route('tasks.restore', $task) }}">@csrf <button class="tn-link">Restore</button></form>
                <form method="POST" action="{{ route('tasks.forceDelete', $task) }}">@csrf @method('DELETE') <button class="text-rose-400 hover:text-rose-300">Force Delete</button></form>
            </td></tr>
            @endforeach
        </tbody></table>
        <div class="mt-4">{{ $tasks->links() }}</div>
    </div></div>
</x-app-layout>
